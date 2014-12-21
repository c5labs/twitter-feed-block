<?php
/**
 * Twitter Feed Request Handler File
 *
 * PHP version 5.3
 *
 * @package  TwitterFeedPackage
 * @author   Oliver Green <green2go@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     http://codeblog.co.uk
 */
namespace Concrete\Package\TwitterFeedPackage\Src;

use Core;
use Route;
use Database;
use Exception;
use Concrete\Core\Routing\URL;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Twitter Feed Request Handler Class
 *
 * Handles all auxiliary HTTP requests for the package including
 * the callbacks / redirects to and from twitter, also the AJAX
 * requests from the block form.
 *
 * @package  TwitterFeedPackage
 * @author   Oliver Green <green2go@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     http://codeblog.co.uk
 */
class TwitterFeedRequestHandler
{
    /**
     * A pointer to the package
     *
     * @var \Core\Concrete\Package\Package
     */
    protected $package;

    /**
     * Contructor
     *
     * @param void
     */
    public function __construct($package)
    {
        $this->package = $package;
        $rh = $this;

        /**
         * Callback route used by twitter
         */
        Route::register(
            '/twitter-feed-package/callback',
            function () use ($rh) {
                $rh->handleCallbackFromTwitter();
            }
        );

        /**
         * Returns twitter oAuth authorzation url
         * to edit form AJAX client.
         */
        Route::register(
            '/twitter-feed-package/redirect',
            function () use ($rh) {
                return $rh->handleAuthorizationRedirect();
            }
        );

        /**
         * Removes an account
         */
        Route::register(
            '/twitter-feed-package/remove-account/{acID}',
            function ($acID) use ($rh) {
                $rh->handleRemoveAccount($acID);
            }
        );

        /**
         * Provides pollable route for AJAX requests
         * to check the status of the authorization.
         */
        Route::register(
            '/twitter-feed-package/auth-status/{token}',
            function ($token) use ($rh) {
                return $rh->handleAuthorizationStatus($token);
            }
        );
    }

    /**
     * Checks for the existence of a valid
     * CSRF token in the current request.
     *
     * @param  string $action
     * @return void
     */
    protected function checkCSRFToken($action = '')
    {
        $valt = Core::make('helper/validation/token');
        $token = $_REQUEST['csrf_token'];
        if (!$valt->validate($action, $token)) {
            throw new Exception('Invalid CSRF token.');
        }
    }

    /**
     * Gets an instance of the TwitterFeedService
     *
     * @todo   IOC, IOC, IOC!
     * @return TwitterFeedService
     */
    protected function getTwitterFeedService()
    {
        return new TwitterFeedService(
            $this->package->getTwitterConsumerKey(),
            $this->package->getTwitterConsumerSecret()
        );
    }

    /**
     * Gets an instance of the account repository
     *
     * @todo   IOC, IOC, IOC!
     * @return AccountRepository
     */
    protected function getAuthorizationRepository()
    {
        return new AuthorizedAccountRepository(Database::get());
    }

    /**
     * Crude templating engine
     *
     * @param  string $html
     * @param  array $data Placeholder keys & values
     * @return string Compiled HTML template
     */
    protected function compile($html, $data)
    {
        foreach ($data as $placeholder => $value) {
            $html = str_replace('{{ ' . $placeholder . ' }}', $value, $html);
        }
        return $html;
    }

    /**
     * Compiles an template HTML file
     *
     * @param  string $file FS path
     * @param  array $data Placeholder keys & values
     * @return string Compiled HTML template
     */
    protected function compileFromFile($file, $data)
    {
        ob_start();
        include($file);
        $html = ob_get_contents();
        ob_end_clean();
        return $this->compile($html, $data);
    }

    /**
     * Compiles the error template and outputs it to the browser.
     *
     * At the time of writing concrete wasn't handling valid Response objects
     * from routes bound using closures properly (which I fixed via a pull
     * request which Andrew merged) so the only way to output a 500 is a bit
     * hacky like this.
     *
     * @param  Exception $ex
     * @param  array     $data Placeholder keys & values
     * @return void
     */
    protected function renderErrorPage(Exception $ex, $data = array())
    {
        $template_data = array_merge(
            $data,
            array(
                'error' => $ex->getMessage(),
                'logo_url' => $this->package->getRelativePath() . '/icon.png'
            )
        );
        $html = $this->compileFromFile(__DIR__ . '/../templates/error.template.php', $template_data);
        header("HTTP/1.0 500 Server Error", true, 500);
        die($html);
    }

    /**
     * Outputs the authorization redirection page, this shows a 'we are redirecting
     * you to twitter' page and redirects them via JS to twitter to authorize our app.
     *
     * @return void
     */
    protected function handleAuthorizationRedirect()
    {
        $callback_url = BASE_URL . URL::to('/twitter-feed-package/callback');
        $a = $this->getAuthorizationRepository();

        try {
            $this->checkCSRFToken();
            $data = $this->getTwitterFeedService()->requestAuthorizationUrl($callback_url, false);
            $a->createEntry($data['oauth_token'], $data['oauth_token_secret']);
        } catch (Exception $ex) {
            $this->renderErrorPage($ex);
        }

        $template_data = array(
            'token' => $data['oauth_token'],
            'url' => $data['url'],
            'logo_url' => $this->package->getRelativePath() . '/icon.png'
        );
        $html = $this->compileFromFile(__DIR__ . '/../templates/redirect.template.php', $template_data);
        die($html);
    }

    /**
     * Outputs a success or failure message to the browser after twitter
     * redirects the user back to us from the step above. It also saves the
     * long lasting access tokens to database.
     *
     * @return void
     */
    protected function handleCallbackFromTwitter()
    {
        $a = $this->getAuthorizationRepository();

        try {
            if (isset($_REQUEST['denied'])) {
                throw new Exception('You cancelled the authorization request.');
            }

            $oauth_tokens = $a->getEntryByToken($_REQUEST['oauth_token']);

            $response = $this->getTwitterFeedService()->requestAccessToken(
                $oauth_tokens['oauth_token'],
                $oauth_tokens['oauth_token_secret']
            );
        } catch (Exception $ex) {
            $this->renderErrorPage($ex);
        }

        $a->updateEntryByToken(
            $oauth_tokens['oauth_token'],
            $response['oauth_token'],
            $response['oauth_token_secret'],
            $response['screen_name']
        );

        $template_data = array(
            'twitter_handle' => $response['screen_name'],
            'logo_url' => $this->package->getRelativePath() . '/icon.png'
        );
        $html = $this->compileFromFile(__DIR__ . '/../templates/authorized.template.php', $template_data);
        die($html);
    }

    /**
     * Handles AJAX requests from the block form to
     * remove an authorized twitter account.
     *
     * @param  integer $acID
     * @return string json|html
     */
    protected function handleRemoveAccount($acID)
    {
        $a = $this->getAuthorizationRepository();

        try {
            $this->checkCSRFToken();
            $a->removeEntry($acID);
        } catch (Exception $ex) {
            $this->renderErrorPage($ex);
        }

        return json_encode('true');
    }

    /**
     * During an account authorization the block form polls for the status of the
     * authorization, this allows us to close the popup window and update the UI.
     * This method handles this request.
     *
     * @param  string $token Temporary oAuth token
     * @return string json|html
     */
    protected function handleAuthorizationStatus($token)
    {
        $a = $this->getAuthorizationRepository();

        try {
            $this->checkCSRFToken();

            if ($entry = $a->getEntryByToken($token)) {
                if ($entry['twitter_handle']) {
                    return json_encode(
                        array(
                            'acID' => $entry['acID'],
                            'twitter_handle' => $entry['twitter_handle']
                        )
                    );
                }
            }
        } catch (Exception $ex) {
            $this->renderErrorPage($ex);
        }
        return json_encode('false');
    }
}
