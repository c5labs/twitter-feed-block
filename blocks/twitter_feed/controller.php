<?php
namespace Concrete\Package\TwitterFeedPackage\Block\TwitterFeed;

use Core;
use Database;
use BlockType;
use Package;
use Concrete\Core\Block\BlockController;
use Concrete\Package\TwitterFeedPackage\Src\AuthorizedAccountRepository;
use Concrete\Package\TwitterFeedPackage\Src\TwitterFeedService;
use Concrete\Package\TwitterFeedPackage\Src\TwitterFeedFormatter;

defined('C5_EXECUTE') or die('Access Denied.');
/**
 * The controller for the box block.
 *
 * @package Blocks
 * @subpackage Content
 * @author Oliver Green <oliver@devisegraphics.co.uk>
 * @license GPL
 *
 */
class Controller extends BlockController 
{
    protected $btTable = 'btTwitterFeed';
    protected $btInterfaceWidth = "465";
    protected $btInterfaceHeight = "365";
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = false;
    protected $btCacheBlockOutputOnPost = false;
    protected $btCacheBlockOutputForRegisteredUsers = false;
    protected $btDefaultSet = 'social';

    /**
     *  CSRF -> Validation\CSRF\Token all forms and routes
     *  Load tweets via AJAX so not to slow page load?? Or background job?
     *  Translations
     */
    
    protected $account_repository;
    protected $twitter_feed_service;

    public function getBlockTypeDescription() {
        return t("Displays a list of users latest tweets.");
    }

    public function getBlockTypeName() {
        return t("Twitter Feed");
    }

    public function getPackageObject()
    {
        $bt = BlockType::getByHandle($this->btHandle);
        return Package::getByID($bt->getPackageID());
    }

    protected function getAccountRepository()
    {
        if (is_null($this->account_repository)) {
            $this->account_repository = new AuthorizedAccountRepository(Database::get()); // SHOULD BE IOC
        }
        return $this->account_repository;
    }

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

    protected function load()
    {
        parent::load();
        $value = json_decode($this->use_accounts);
        $this->use_accounts = $value;
        $this->set('use_accounts', $value);
    }

    public function save($args = array())
    {
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

        return parent::save($args);
    }

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
        $this->set('refresh_interval', '5');
        $this->set('show_tweets_from', 'concrete5');
        $this->set('show_tweets_type', 'statuses/user_timeline');
        $this->form();
    }

    public function edit()
    {
        $this->form();
    }

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
            'user' => 'User',
            'hashtag' => 'Hashtag',
            'list' => 'List',
            'search' => 'Search',
        );

        $this->set('type_list', $type_list);
    }

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
        }
    }

    public function view()
    {
        $account = $this->getAccountRepository()->getEntryByID($this->use_account);
        $this->set('account', $account);

        if (is_array($account)) {   

            $params = array(
                'count' => $this->num_tweets * 10,
                'exclude_replies' => ('1' == $this->show_replies) ?  'false' : 'true',
                'include_rts' => $this->show_retweets,
                'contributor_details' => 'false',
            );
            $params = array_merge($params, $this->getRequestTypeParameters($this->show_tweets_type));

            $tweets = $this->getTwitterService()->request($account, $params, ($this->refresh_interval * 60));

            if (isset($tweets->errors)) {
                $this->set('error', $tweets->errors[0]);
                $this->set('tweets', array());
            } else {

                if (is_array($tweets->statuses)) {
                    $tweets = $tweets->statuses;
                }

                $tweets = array_slice($tweets, 0, $this->num_tweets);
                $formatter = $this->getFormatter();
                $tweets = $formatter->format($tweets, $format_options);
                $this->set('tweets', $tweets);
            }
        }
    }
}
