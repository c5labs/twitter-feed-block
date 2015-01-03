<?php
/**
 * Package Controller File
 *
 * PHP version 5.3
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <green2go@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     http://codeblog.co.uk
 */
namespace Concrete\Package\TweetFeedPackage;

use Database;
use Package;
use BlockType;
use Asset;
use AssetList;
use Concrete\Package\TweetFeedPackage\Src\TwitterFeedRequestHandler;
use Concrete\Package\TweetFeedPackage\Src\TwitterFeedService;
use Concrete\Package\TweetFeedPackage\Src\AuthorizedAccountRepository;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Package Controller Class
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <green2go@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     http://codeblog.co.uk
 */
class Controller extends Package
{
    /**
     * Package Handle
     *
     * @var string
     */
    protected $pkgHandle = 'tweet_feed_package';

    /**
     * Application Version Required
     *
     * @var string
     */
    protected $appVersionRequired = '5.7.1';

    /**
     * Package Version
     *
     * @var string
     */
    protected $pkgVersion = '0.9.9';

    /**
     * Twitter oAuth Consumer Key
     * You can generate your own application key and
     * secret and replace here if required for white labeling, etc.
     *
     * @var string
     */
    protected $twitterConsumerKey = 'WJbRZLBH7L3R2LcF2G8q3c987';

    /**
     * Twitter oAuth Consumer Key Secret
     * Same as above ^
     * 
     * @var string
     */
    protected $twitterConsumerSecret = 'YGqAvTJqE1bIgAOT9ZGHKh00mvyqeaFvWYwtO1ZLe542lf0WZo';

    /**
     * Package Name
     *
     * @return string
     */
    public function getPackageName()
    {
        return t("Tweet Feed Block Components");
    }

    /**
     * Package Description
     *
     * @return string
     */
    public function getPackageDescription()
    {
        return t("A package that installs a block to allow you to add your twitter feed to any theme.");
    }

    /**
     * Twitter oAuth Consumer Key Accessor
     *
     * @return string
     */
    public function getTwitterConsumerKey()
    {
        return $this->twitterConsumerKey;
    }

    /**
     * Twitter oAuth Consumer Secret Accessor
     *
     * @return string
     */
    public function getTwitterConsumerSecret()
    {
        return $this->twitterConsumerSecret;
    }

    /**
     * Start-up Hook
     *
     * @return void
     */
    public function on_start()
    {
        $this->registerAssets();

        /* @todo Should be IOC based */
        $rh = new TwitterFeedRequestHandler($this);
    }

    /**
     * Install Hook
     *
     * @return void
     */
    public function install()
    {
        $pkg = parent::install();
        $bt = BlockType::installBlockTypeFromPackage('tweet_feed', $pkg);
    }

    /**
     * Unistall Hook
     *
     * @return void
     */
    public function uninstall()
    {
        parent::uninstall();
        $db = Database::get();
        $db->exec('DROP TABLE btTwitterFeedAuthorizations;');
        $db->exec('DROP TABLE btTwitterFeed;');
    }

    /**
     * Registers the packages (and blocks) assets
     * with the concrete5 asset pipeline.
     *
     * @return void
     */
    protected function registerAssets()
    {
        $al = AssetList::getInstance();

        // Bootstrap Tabs
        $al->register(
            'javascript',
            'bootstrap/tab',
            'assets/bootstrap.tab.js',
            array(
                'version' => '3.3.1',
                'position' => Asset::ASSET_POSITION_FOOTER,
                'minify' => true,
                'combine' => true
            ),
            $this
        );

        // Switchery
        $al->register(
            'javascript',
            'switchery/js',
            'assets/switchery.js',
            array(
                'version' => '0.7.0',
                'position' => Asset::ASSET_POSITION_FOOTER,
                'minify' => true,
                'combine' => true
            ),
            $this
        );

        $al->register(
            'css',
            'switchery/css',
            'assets/switchery.css',
            array(
                'version' => '0.7.0',
                'position' => Asset::ASSET_POSITION_HEADER,
                'minify' => true,
                'combine' => true
            ),
            $this
        );

        $al->registerGroup(
            'switchery',
            array(
                array('css', 'switchery/css'),
                array('javascript', 'switchery/js')
            )
        );

        // Block Form Stuff
        $al->register(
            'css',
            'twitterfeed/form',
            'blocks/tweet_feed/css/forms/form.css',
            array(
                'version' => '0.9.5',
                'position' => Asset::ASSET_POSITION_HEADER,
                'minify' => true,
                'combine' => true
            ),
            $this
        );
    }
}
