<?php
/**
 * Package Controller File.
 *
 * PHP version 5.4
 *
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
namespace Concrete\Package\TweetFeedPackage;

defined('C5_EXECUTE') or die('Access Denied.');

use Asset;
use AssetList;
use BlockType;
use Package;
use Concrete\Package\TweetFeedPackage\Src\TwitterFeedRequestHandler;
use Database;
use Illuminate\Filesystem\Filesystem;

/**
 * Package Controller Class.
 *
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
class Controller extends Package
{
    /**
     * Package Handle.
     *
     * @var string
     */
    protected $pkgHandle = 'tweet_feed_package';

    /**
     * Application Version Required.
     *
     * @var string
     */
    protected $appVersionRequired = '5.7.1';

    /**
     * Package Version.
     *
     * @var string
     */
    protected $pkgVersion = '1.0.1';

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
     * Same as above ^.
     * 
     * @var string
     */
    protected $twitterConsumerSecret = 'YGqAvTJqE1bIgAOT9ZGHKh00mvyqeaFvWYwtO1ZLe542lf0WZo';

    /**
     * Keep me updated interest ID.
     * 
     * @var string
     */
    public $interest_id = 'f1ed077b16';

    /**
     * Package Name.
     *
     * @return string
     */
    public function getPackageName()
    {
        return t('Twitter Feed Block Components');
    }

    /**
     * Package Description.
     *
     * @return string
     */
    public function getPackageDescription()
    {
        return t('A package that installs a block to allow you to add your twitter feed to any theme.');
    }

    /**
     * Twitter oAuth Consumer Key Accessor.
     *
     * @return string
     */
    public function getTwitterConsumerKey()
    {
        return $this->twitterConsumerKey;
    }

    /**
     * Twitter oAuth Consumer Secret Accessor.
     *
     * @return string
     */
    public function getTwitterConsumerSecret()
    {
        return $this->twitterConsumerSecret;
    }

    /**
     * Get a helper instance.
     * 
     * @param  mixed $pkg
     * @return \C5dev\Package\Thanks\PackageInstallHelper
     */
    protected function getHelperInstance($pkg)
    {
        if (! class_exists('\C5dev\Package\Thanks\PackageInstallHelper')) {
            // Require composer
            $filesystem = new Filesystem();
            $filesystem->getRequire(__DIR__.'/vendor/autoload.php');
        }

        return new \C5dev\Package\Thanks\PackageInstallHelper($pkg);
    }

    /**
     * Start-up Hook.
     *
     * @return void
     */
    public function on_start()
    {
        $this->registerAssets();

        /* @todo Should be IOC based */
        $rh = new TwitterFeedRequestHandler($this);

        // Check whether we have just installed the package 
        // and should redirect to intermediate 'thank you' page.
        $this->getHelperInstance($this)->checkForPostInstall();
    }

    /**
     * Install Hook.
     *
     * @return void
     */
    public function install()
    {
        $pkg = parent::install();

        $bt = BlockType::installBlockTypeFromPackage('tweet_feed', $pkg);

        // Install the 'thank you' page if needed.
        $this->getHelperInstance($pkg)->addThanksPage();

        return $pkg;
    }

    /**
     * Unistall Hook.
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
    public function registerAssets()
    {
        $al = AssetList::getInstance();

        // Bootstrap Tabs
        $al->register(
            'javascript',
            'bootstrap/tab',
            'assets/bootstrap.tab.js',
            [
                'version' => '3.3.1',
                'position' => Asset::ASSET_POSITION_FOOTER,
                'minify' => true,
                'combine' => true,
            ],
            $this
        );

        // Switchery
        $al->register(
            'javascript',
            'switchery/js',
            'assets/switchery.js',
            [
                'version' => '0.7.0',
                'position' => Asset::ASSET_POSITION_FOOTER,
                'minify' => true,
                'combine' => true,
            ],
            $this
        );

        $al->register(
            'css',
            'switchery/css',
            'assets/switchery.css',
            [
                'version' => '0.7.0',
                'position' => Asset::ASSET_POSITION_HEADER,
                'minify' => true,
                'combine' => true,
            ],
            $this
        );

        $al->registerGroup(
            'switchery',
            [
                ['css', 'switchery/css'],
                ['javascript', 'switchery/js'],
            ]
        );

        // Block Form Stuff
        $al->register(
            'css',
            'twitterfeed/form',
            'blocks/tweet_feed/css/forms/form.css',
            [
                'version' => '0.9.5',
                'position' => Asset::ASSET_POSITION_HEADER,
                'minify' => true,
                'combine' => true,
            ],
            $this
        );
    }
}
