<?php
/**
 * Twitter Feed Request Handler File.
 *
 * PHP version 5.4
 *
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
namespace Concrete\Package\TweetFeedPackage\Src;

use Log;
use Core;
use Route;
use Database;
use Exception;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Twitter Feed Request Handler Class.
 *
 * Handles all auxiliary HTTP requests for the package including
 * the callbacks / redirects to and from twitter, also the AJAX
 * requests from the block form.
 *
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
class TwitterFeedRequestHandler
{
    /**
     * A pointer to the package.
     *
     * @var \Core\Concrete\Package\Package
     */
    protected $package;

    /**
     * Contructor.
     *
     * @param void
     */
    public function __construct($package)
    {
        $this->package = $package;
        $rh = $this;

        /*
         * Returns twitter oAuth authorzation url
         * to edit form AJAX client.
         */
        Route::register(
            '/twitter-feed-package/redirect',
            function () use ($rh) {
                return $rh->handleAuthorizationRedirect();
            }
        );

        /*
         * Removes an account
         */
        Route::register(
            '/twitter-feed-package/remove-account/{acID}',
            function ($acID) use ($rh) {
                $rh->handleRemoveAccount($acID);
            }
        );

        /*
         * Provides pollable route for AJAX requests
         * to check the status of the authorization.
         */
        Route::register(
            '/twitter-feed-package/auth-status/{token}',
            function ($token) use ($rh) {
                return $rh->handleAuthorizationStatus($token);
            }
        );

        /*
         * Authorize PIN
         */
        Route::register(
            '/twitter-feed-package/auth-pin/{token}/{pin}',
            function ($token, $pin) use ($rh) {
                return $rh->handlePinAuthorization($token, $pin);
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
        if (! $valt->validate($action, $token)) {
            throw new Exception('Invalid CSRF token.');
        }
    }

    /**
     * Gets an instance of the TwitterFeedService.
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
     * Gets an instance of the account repository.
     *
     * @todo   IOC, IOC, IOC!
     * @return AccountRepository
     */
    protected function getAuthorizationRepository()
    {
        return new AuthorizedAccountRepository(Database::get());
    }

    /**
     * Crude templating engine.
     *
     * @param  string $html
     * @param  array $data Placeholder keys & values
     * @return string Compiled HTML template
     */
    protected function compile($html, $data)
    {
        foreach ($data as $placeholder => $value) {
            $html = str_replace('{{ '.$placeholder.' }}', $value, $html);
        }

        return $html;
    }

    /**
     * Compiles an template HTML file.
     *
     * @param  string $file FS path
     * @param  array $data Placeholder keys & values
     * @return string Compiled HTML template
     */
    protected function compileFromFile($file, $data)
    {
        $html = file_get_contents($file);

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
    protected function renderErrorPage(Exception $ex, $data = [])
    {
        $template_data = array_merge(
            $data,
            [
                'error' => $ex->getMessage(),
                'logo_url' => $this->package->getRelativePath().'/icon.png',
                'page_title' => t('Authorization Error'),
                'page_heading' => t('Oh No! We had a problem.'),
                'error_list_header' => t('It looks like something went wrong, the error returned was:'),
                'error_list_footer' => t('Waiting a little while and trying again <i>may</i> fix the problem, we\'ve logged more information to the system error log.'),
            ]
        );
        $html = $this->compileFromFile(__DIR__.'/../templates/error.template.html', $template_data);
        header('HTTP/1.0 500 Server Error', true, 500);
        die($html);
    }

    /**
     * Renders an exception as JSON.
     * 
     * @param  Exception $ex
     * @return void
     */
    protected function renderJsonException(Exception $ex)
    {
        header('HTTP/1.0 500 Server Error', true, 500);
        header('Content-Type', 'application/json');

        $data = [
            'message' => $ex->getMessage(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT);

        die($json);
    }

    /**
     * Outputs the authorization redirection page, this shows a 'we are redirecting
     * you to twitter' page and redirects them via JS to twitter to authorize our app.
     *
     * @return void
     */
    protected function handleAuthorizationRedirect()
    {
        // 5.7.4.X fix
        $version = explode('.', APP_VERSION);

        if (7 === intval($version[1]) && intval($version[2]) < 4) {
            $callback_url = BASE_URL.\Concrete\Core\Routing\URL::to('/twitter-feed-package/callback');
        } else {
            $callback_url = \URL::to('/twitter-feed-package/callback')->__toString();
        }

        $callback_url = 'oob';

        $a = $this->getAuthorizationRepository();

        try {
            $this->checkCSRFToken();
            $data = $this->getTwitterFeedService()->requestAuthorizationUrl($callback_url, false);
            $a->createEntry($data['oauth_token'], $data['oauth_token_secret']);
        } catch (Exception $ex) {
            $this->renderErrorPage($ex);
        }

        $template_data = [
            'token' => $data['oauth_token'],
            'url' => $data['url'],
            'logo_url' => $this->package->getRelativePath().'/icon.png',
            'page_title' => t('Redirecting to Twitter...'),
            'page_header' => t('Standby, sending you to twitter...'),
            'page_content' => t('If you are not automatically redirected to twitter, ').'<a href="'.$data['url'].'">'.t('click here').'</a>.',
        ];
        $html = $this->compileFromFile(__DIR__.'/../templates/redirect.template.html', $template_data);
        die($html);
    }

    /**
     * Authorize a PIN.
     * 
     * @return void
     */
    protected function handlePinAuthorization($token, $pin)
    {
        try {
            $this->checkCSRFToken();

            if (empty($pin)) {
                throw new Exception('No PIN was specified.');
            }

            if (empty($token)) {
                throw new Exception('No oauth token was specified.');
            }

            $a = $this->getAuthorizationRepository();

            $oauth_tokens = $a->getEntryByToken($token);

            $response = $this->getTwitterFeedService()->requestAccessToken(
                $oauth_tokens['oauth_token'],
                $oauth_tokens['oauth_token_secret'],
                intval($pin)
            );
        } catch (Exception $ex) {
            Log::addEntry($ex->getMessage() . ' in ' . $ex->getFile() . ' @ ' . $ex->getLine());
            $this->renderJsonException($ex);
        }

        $a->updateEntryByToken(
            $oauth_tokens['oauth_token'],
            $response['oauth_token'],
            $response['oauth_token_secret'],
            $response['screen_name']
        );

         return json_encode(
            [
                'acID' => $oauth_tokens['acID'],
                'twitter_handle' => $response['screen_name'],
            ]
        );
    }

    /**
     * Handles AJAX requests from the block form to
     * remove an authorized twitter account.
     *
     * @param  int $acID
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
                        [
                            'acID' => $entry['acID'],
                            'twitter_handle' => $entry['twitter_handle'],
                        ]
                    );
                }
            }
        } catch (Exception $ex) {
            $this->renderErrorPage($ex);
        }

        return json_encode('false');
    }
}
