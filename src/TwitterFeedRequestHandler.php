<?php
namespace Concrete\Package\TwitterFeedPackage\Src;

use Core;
use Route;
use Database;
use Exception;
use Concrete\Core\Routing\URL;

class TwitterFeedRequestHandler
{
    protected $package;

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

    protected function checkCSRFToken($action = '')
    {
        $valt = Core::make('helper/validation/token');
        $token = $_REQUEST['csrf_token'];
        if (!$valt->validate($action, $token)) {
            throw new Exception('Invalid CSRF token.');
        }
    }

    protected function getTwitterFeedService()
    {
        // Should be ioc based
        return new TwitterFeedService(
            $this->package->getTwitterConsumerKey(),
            $this->package->getTwitterConsumerSecret()
        );
    }

    protected function getAuthorizationRepository()
    {
        // Should be ioc based
        return new AuthorizationEntryRepository(Database::get());
    }

    protected function compile($html, $data)
    {
        foreach ($data as $placeholder => $value) {
            $html = str_replace('{{ ' . $placeholder . ' }}', $value, $html);
        }
        return $html;
    }

    protected function compileFromFile($file, $data)
    {
        $html = file_get_contents($file);
        return $this->compile($html, $data);
    }

    protected function renderErrorPage(Exception $ex, $data = array())
    {
        $template_data = array_merge(
            $data,
            array(
                'error' => $ex->getMessage(), 
                'logo_url' => $this->package->getRelativePath() . '/icon.png'
            )
        );
        $html = $this->compileFromFile(__DIR__ . '/../templates/error.html', $template_data);
        header("HTTP/1.0 500 Server Error", true, 500);
        die($html);
    }

    protected function handleAuthorizationRedirect()
    {   
        $callback_url = BASE_URL . URL::to('/twitter-feed-package/callback');
        $a = $this->getAuthorizationRepository();

        try {
            $this->checkCSRFToken();
            $data = $this->getTwitterFeedService()->requestAuthorizationUrl($callback_url, false);
            $a->createEntry($data['oauth_token'], $data['oauth_token_secret']);
        } catch(Exception $ex) {
            $this->renderErrorPage($ex);
        }

        $template_data = array(
            'token' => $data['oauth_token'],
            'url' => $data['url'],
            'logo_url' => $this->package->getRelativePath() . '/icon.png'
        );
        $html = $this->compileFromFile(__DIR__ . '/../templates/redirect.html', $template_data);
        die($html);
    }

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
        } catch(Exception $ex) {
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
        $html = $this->compileFromFile(__DIR__ . '/../templates/authorized.html', $template_data);
        die($html);
    }

    protected function handleRemoveAccount($acID)
    {
        $a = $this->getAuthorizationRepository();

        try {
            $this->checkCSRFToken();
            $a->removeEntry($acID);
        } catch(Exception $ex) {
            $this->renderErrorPage($ex);
        }

        return json_encode('true');
    }

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
        } catch(Exception $ex) {
            $this->renderErrorPage($ex);
        }
        return json_encode('false');
    }

}