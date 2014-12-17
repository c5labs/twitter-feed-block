<?php
namespace Concrete\Package\TwitterFeedPackage\Src;

use Concrete\Package\TwitterFeedPackage\Src\TwitterOAuth;
use Exception;
use Log;
use Core;

class TwitterFeedService
{
    protected $consumer_key;

    protected $consumer_secret;

    public function __construct($consumer_key, $consumer_secret)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
    }

    protected function httpInfoToString(array $http_info)
    {
        $str = '';
        foreach ($http_info as $k => $v) {
            $str .= $k . ' => ' . $v . "\r\n";
        }
        return $str;
    }
    
    public function requestAuthorizationUrl($callback_url)
    {
        /* Build TwitterOAuth object with client credentials. */
        $connection = new TwitterOAuth($this->consumer_key, $this->consumer_secret);
         
        /* Get temporary credentials. */
        $request_token = $connection->getRequestToken($callback_url);

        /* Save temporary credentials to session. */
        $token = $request_token['oauth_token'];
        $token_secret = $request_token['oauth_token_secret'];
         
        /* If last connection failed don't display authorization link. */
        switch ($connection->http_code) {
          case 200:
            /* Build authorize URL and redirect user to Twitter. */
            return array(
                'url' => $connection->getAuthorizeURL($token), 
                'oauth_token' => $token, 
                'oauth_token_secret' => $token_secret
            );
          default:
            $this->throwConnectionError($connection);
        }
    }

    public function requestAccessToken($oauth_token, $oauth_token_secret)
    {
        /* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
        $connection = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
        /* Request access tokens from twitter */
        $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
        
        /* If HTTP response is 200 continue otherwise send to connect page to retry */
        if (200 == $connection->http_code) {
          /* The user has been verified and the access tokens can be saved for future use */
          return $access_token;
        } else {
          $this->throwConnectionError($connection);
        }
    }

    protected function throwConnectionError($connection)
    {
        /* Save HTTP status for error dialog on connnect page.*/
        $error = 'Twitter responded with a bad status code (' . $connection->http_code;
        $error .= (isset($connection->http_info['content']) ? ' - ' . $connection->http_info['content'] : '');
        $error .= '), please try again.';
        Log::error($error . " Error detail:\r\n" . $this->httpInfoToString($connection->http_info));
        throw new Exception($error);
    }

    public function request(array $account, array $params, $cache_ttl)
    {
        $cache = Core::make('cache/expensive');
        $cache_key = md5(implode('', array_merge($account, $params)));
        $item = $cache->getItem($cache_key);

        if ($item->isMiss()) {
            $response = $this->getConnection($account['access_token'], $account['access_token_secret'])->get($params['request_endpoint'], $params);
            $item->set($response, $cache_ttl);
        }

        return $item->get();
    }

    public function getConnection($access_token, $access_token_secret)
    {
        return new TwitterOAuth(
            $this->consumer_key, 
            $this->consumer_secret, 
            $access_token,
            $access_token_secret
        );
    }
}
