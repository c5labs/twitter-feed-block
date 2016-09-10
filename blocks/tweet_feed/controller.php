<?php
/**
 * Block Controller File
 *
 * PHP version 5.4
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5dev.com/add-ons/twitter-feed
 */
namespace Concrete\Package\TweetFeedPackage\Block\TweetFeed;

use Core;
use Database;
use BlockType;
use Package;
use Concrete\Core\Block\BlockController;
use Concrete\Package\TweetFeedPackage\Src\AuthorizedAccountRepository;
use Concrete\Package\TweetFeedPackage\Src\TwitterFeedService;
use Concrete\Package\TweetFeedPackage\Src\TwitterFeedFormatter;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Block Controller Class
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5dev.com/add-ons/twitter-feed
 */
class Controller extends BlockController
{
    /**
     * Block Table
     *
     * @var string
     */
    protected $btTable = 'btTwitterFeed';

    /**
     * Block Editor Interface Width
     * @var string
     */
    protected $btInterfaceWidth = "465";

    /**
     * Block Editor Interface Height
     * @var string
     */
    protected $btInterfaceHeight = "365";

    /**
     * Cache the blocks database record?
     *
     * @var boolean
     */
    protected $btCacheBlockRecord = true;

    /**
     * Cache the blocks output?
     *
     * @var boolean
     */
    protected $btCacheBlockOutput = false;

    /**
     * Cache the block output for $_POST requests?
     *
     * @var boolean
     */
    protected $btCacheBlockOutputOnPost = false;

    /**
     * Cache the blocks output for registered users?
     *
     * @var boolean
     */
    protected $btCacheBlockOutputForRegisteredUsers = false;

    /**
     * How long do we cache the block for?
     *
     * CACHE_LIFETIME = Until manually cleared or the
     * block is updated via the editor.
     *
     * @var integer
     */
    protected $btCacheBlockOutputLifetime = CACHE_LIFETIME;


    /**
     * The set within the block chooser interface
     * that this block belongs to.
     *
     * @var string
     */
    protected $btDefaultSet = 'social';

    /**
     * Account respository instance
     *
     * @todo IOC, IOC, IOC
     * @var AuthorizedAccountRepository
     */
    protected $account_repository;

    /**
     * Twitter Feed Service
     *
     * Used to making all requests to twitter, including
     * authorizing the account, etc.
     *
     * @todo IOC, IOC, IOC
     * @var TwitterFeedService
     */
    protected $twitter_feed_service;

    /**
     * Block Name
     *
     * @return string
     */
    public function getBlockTypeName()
    {
        return t("Twitter Feed");
    }

    /**
     * Block Description
     *
     * @return string
     */
    public function getBlockTypeDescription()
    {
        return t("Displays a list of users, hash tags, lists or searches latest tweets.");
    }

    /**
     * Gets this blocks package object
     *
     * @return Package
     */
    public function getPackageObject()
    {
        $bt = BlockType::getByHandle($this->btHandle);
        return Package::getByID($bt->getPackageID());
    }

    /**
     * Gets an instance of the AuthorizedAccountRespository
     *
     * @todo IOC, IOC, IOC
     * @return AuthorizedAccountRepository
     */
    protected function getAccountRepository()
    {
        if (is_null($this->account_repository)) {
            $this->account_repository = new AuthorizedAccountRepository(Database::get()); // SHOULD BE IOC
        }
        return $this->account_repository;
    }

    /**
     * Gets an instance of the TwitterFeedService
     *
     * @todo IOC, IOC, IOC
     * @return TwitterFeedService
     */
    protected function getTwitterService()
    {
        if (is_null($this->twitter_feed_service)) {
            $pkg = $this->getPackageObject();

            // Should be ioc based
            $this->twitter_feed_service = new TwitterFeedService(
                $pkg->getTwitterConsumerKey(),
                $pkg->getTwitterConsumerSecret()
            );
        }
        return $this->twitter_feed_service;
    }

    /**
     * Gets an instance of the TweetFeedFormatter
     *
     * @return TwitterFeedFormatter
     */
    protected function getFormatter()
    {
        // Twitter formatter options
        $formatter = new TwitterFeedFormatter();
        $format_options = array(
            'urls',
            'user_mentions',
            'hashtags',
            'symbols',
            'date'
        );

        if ($this->expand_pictures) {
            $format_options[] = 'media';
        } else {
            $format_options[] = array('media', 'links');
        }
        $formatter->setDefaultOptions($format_options);
        return $formatter;
    }

    /**
     * Load Hook
     *
     * @return void
     */
    protected function load()
    {
        parent::load();
        $value = json_decode($this->use_accounts);
        $this->use_accounts = $value;
        $this->set('use_accounts', $value);
    }

    /**
     * Save Hook
     *
     * @param  array  $args
     * @return boolean
     */
    public function save($args = array())
    {
        $args['refresh_interval'] = (intval($args['refresh_interval']) < 3) ? '3' : $args['refresh_interval'];
        $args['use_accounts'] = json_encode($args['use_accounts']);

        $checkboxes = array(
            'show_replies',
            'show_retweets',
            'show_date',
            'expand_pictures',
            'show_avatars',
            'show_authors'
        );

        foreach ($checkboxes as $k) {
            if (!isset($args[$k])) {
                $args[$k] = '0';
            }
        }

        $this->clearCache();

        return parent::save($args);
    }

    /**
     * Javascript Translation strings
     * 
     * @return array
     */
    public function getJavaScriptStrings() {
        return array(
            'confirm-account-removal-no-dependents' => t('Are you sure you want to remove this account?'),
            'confirm-account-removal-dependents' => t('This account has {{ num_blocks }} block(s) using it, are you sure you want to remove it?'),
            'view-profile-button' => t('View Profile'),
            'remove-account-button' => t('Remove'),
            'no-accounts-message' => t('No accounts authorized'),
            'search-control-placeholder' => t('your search term'),
            'account-removal-failure' => t('Failed to remove the account, please try again later.'),
            'polling-problem' => t('There was a problem getting the status of the authorization, you will need to close and re-open the block editor window for the account to become visible.'),
        );
    }

    /**
     * Add Form Hook
     *
     * @return  void
     */
    public function add()
    {
        // Defaults
        $this->set('show_replies', '0');
        $this->set('show_retweets', '0');
        $this->set('show_date', '1');
        $this->set('expand_pictures', '1');
        $this->set('show_avatars', '1');
        $this->set('show_authors', '1');
        $this->set('num_tweets', '5');
        $this->set('refresh_interval', '30');
        $this->set('show_tweets_from', 'concrete5');
        $this->set('show_tweets_type', 'statuses/user_timeline');
        $this->form();
    }

    /**
     * Edit Form Hook
     *
     * @return void
     */
    public function edit()
    {
        $this->form();
    }

    /**
     * Prepares resources for the blocks form / editor view
     *
     * @return void
     */
    protected function form()
    {
        $this->requireAsset('css', 'twitterfeed/form');
        $this->requireAsset('select2');
        $this->requireAsset('switchery');
        $this->requireAsset('javascript', 'bootstrap/tab');

        $accounts = $this->getAccountRepository()->getEntryList();
        $this->set('accounts', $accounts);

        $account_list = array();
        foreach ($accounts as $k => $account) {
            $account_list[$account['acID']] = '@' . $account['twitter_handle'];
        }

        $this->set('account_list', $account_list);

        $type_list = array(
            'user' => t('User'),
            'hashtag' => t('Hashtag'),
            'list' => t('List'),
            'search' => t('Search'),
        );

        $this->set('type_list', $type_list);
    }

    /**
     * Gets parameter sets for the various types
     * of feed we offer users
     *
     * @param  string $type
     * @return array
     */
    protected function getRequestTypeParameters($type)
    {
        switch ($type) {
            case 'user':
                return array(
                    'screen_name' => $this->show_tweets_from,
                    'request_endpoint' => 'statuses/user_timeline',
                );
            case 'list':
                $arr = explode('/', $this->show_tweets_from);
                return array(
                    'owner_screen_name' => $arr[0],
                    'slug' => $arr[1],
                    'request_endpoint' => 'lists/statuses',
                );
            case 'hashtag':
                return array(
                    'q' => '#' . $this->show_tweets_from,
                    'request_endpoint' => 'search/tweets',
                );
            case 'search':
                return array(
                    'q' => $this->show_tweets_from,
                    'request_endpoint' => 'search/tweets',
                );
            break;
            default:
                return array();
        }
    }

    /**
     * Get the request parameters for the current configuration
     * 
     * @return array
     */
    protected function getCurrentRequestParams()
    {
        $params = array(
            'count' => $this->num_tweets * 10,
            'exclude_replies' => ('1' == $this->show_replies) ?  'false' : 'true',
            'include_rts' => $this->show_retweets,
            'contributor_details' => 'false',
        );
        $params = array_merge($params, $this->getRequestTypeParameters($this->show_tweets_type));
        return $params;
    }

    /**
     * Clear feed cache for the current configuration
     * 
     * @return void
     */
    protected function clearCache()
    {
        $account = $this->getAccountRepository()->getEntryByID($this->use_account);
        $params = $this->getCurrentRequestParams();
        if (is_array($account) && is_array($params)) {
            $this->getTwitterService()->clearCache($account, $params);
        }
    }

    /**
     * View Hook
     *
     * @return void
     */
    public function view()
    {
        $account = $this->getAccountRepository()->getEntryByID($this->use_account);
        $this->set('account', $account);

        if (is_array($account)) {

            $params = $this->getCurrentRequestParams();
            $tweets = $this->getTwitterService()->request($account, $params, ($this->refresh_interval * 60));

            if (isset($tweets->errors)) {
                $this->set('error', $tweets->errors[0]);
                $this->set('tweets', array());
            } else {

                if (is_array($tweets->statuses)) {
                    $tweets = $tweets->statuses;
                }

                if (is_array($tweets)) {
                    $tweets = array_slice($tweets, 0, $this->num_tweets);
                    $formatter = $this->getFormatter();
                    $tweets = $formatter->format($tweets, $format_options);
                    $this->set('tweets', $tweets);
                } else {
                    $this->set('error', t('The twitter feed returned was in a bad format.'));
                    $this->set('tweets', array());
                }
            }
        }
    }
}
