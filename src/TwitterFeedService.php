<?php
/**
 * Twitter Feed Service File.
 *
 * PHP version 5.4
 *
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
namespace Concrete\Package\TweetFeedPackage\Src;

use Exception;
use Log;
use Core;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Twitter Feed Service Class.
 *
 * Adds a vague level of structure in relation to dealing with the twitter API
 * using the TwitterOAuth library. All calls to twitter pass through this class,
 * it also deals with the caching.
 *
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
class TwitterFeedService
{
    /**
     * Twitter oAuth Consumer Key.
     *
     * @var string
     */
    protected $consumer_key;

    /**
     * Twitter oAuth Consumer Secret.
     *
     * @var string
     */
    protected $consumer_secret;

    /**
     * Constructor.
     *
     * @param string $consumer_key
     * @param string $consumer_secret
     */
    public function __construct($consumer_key, $consumer_secret)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
    }

    /**
     * Converts an $http_info object from a TwitterOAuth
     * connection into a string for logging. This is essentially
     * all information from curl_getinfo();.
     *
     * @param  array  $http_info
     * @return string
     */
    protected function httpInfoToString(array $http_info)
    {
        $str = '';
        foreach ($http_info as $k => $v) {
            $str .= $k.' => '.$v."\r\n";
        }

        return $str;
    }

    /**
     * Requests an authorization URL from twitter.
     *
     * @param  string $callback_url Redirect URL for after authorization
     * @return array
     */
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
                return [
                    'url' => $connection->getAuthorizeURL($token),
                    'oauth_token' => $token,
                    'oauth_token_secret' => $token_secret,
                ];
            default:
                $this->throwConnectionError($connection);
        }
    }

    /**
     * Requests long lasting access token from twitter
     * using the temporary oauth tokens provided in the callback response.
     *
     * @param  string $oauth_token
     * @param  string $oauth_token_secret
     * @return array $access_token & $access_token_secret
     */
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

    /**
     * Throws an exception & logs the errors to the system
     * log using information from the current TwitterOAuth connection.
     *
     * @param  TwitterOAuth $connection
     * @return void
     */
    protected function throwConnectionError($connection)
    {
        /* Save HTTP status for error dialog on connnect page.*/
        $error = 'Twitter responded with a bad status code ('.$connection->http_code;
        $error .= (isset($connection->http_info['content']) ? ' - '.$connection->http_info['content'] : '');
        $error .= '), please try again.';
        Log::error($error." Error detail:\r\n".$this->httpInfoToString($connection->http_info));
        throw new Exception($error);
    }

    /**
     * Gets and sets up an instance of TwitterOAuth.
     *
     * @param  array  $account  Contains our access tokens
     * @return TwitterOAuth
     */
    protected function getConnection(array $account)
    {
        return new TwitterOAuth(
            $this->consumer_key,
            $this->consumer_secret,
            $account['access_token'],
            $account['access_token_secret']
        );
    }

    /**
     * Gets a cache key for the current feed based 
     * on account & query parameters.
     * 
     * @param  array  $account
     * @param  arra   $params 
     * @return string
     */
    protected function getCacheKey(array $account, array $params)
    {
        return md5(implode('', array_merge($account, $params)));
    }

    /**
     * Makes a genric request to twitter with our long lasting
     * access tokens.
     *
     * @param  array   $account    Contains our access tokens
     * @param  array   $params     Params sent to twitter
     * @param  int $cache_ttl  Time in seconds to cache the request
     * @return array|object
     */
    public function request(array $account, array $params, $cache_ttl = 300)
    {
        $cache = Core::make('cache/expensive');
        $item = $cache->getItem($this->getCacheKey($account, $params));

        if ($item->isMiss()) {
            $response = $this->getConnection($account)->get($params['request_endpoint'], $params);
            $item->set($response, $cache_ttl);
        } else {
            $response = $item->get();
        }

        return $response;
    }

    /**
     * Clears feed cache.
     * 
     * @param  array  $account [description]
     * @param  array  $params  [description]
     * @return void
     */
    public function clearCache(array $account, array $params)
    {
        $cache = Core::make('cache/expensive');
        $item = $cache->getItem($this->getCacheKey($account, $params));
        $item->clear();
    }
}
