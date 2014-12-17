<?php
namespace Concrete\Package\TwitterFeedPackage;

use Database;
use Package;
use BlockType;
use Asset;
use AssetList;
use Concrete\Package\TwitterFeedPackage\Src\TwitterFeedRequestHandler;
use Concrete\Package\TwitterFeedPackage\Src\TwitterFeedService;
use Concrete\Package\TwitterFeedPackage\Src\AuthorizationEntryRepository;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends Package
{
    protected $pkgHandle = 'twitter_feed_package';
    protected $appVersionRequired = '5.7.1';
    protected $pkgVersion = '0.9.4';
    protected $twitterConsumerKey = 'WJbRZLBH7L3R2LcF2G8q3c987';
    protected $twitterConsumerSecret = 'YGqAvTJqE1bIgAOT9ZGHKh00mvyqeaFvWYwtO1ZLe542lf0WZo';

    public function getPackageName()
    {
        return t("Twitter Feed Block Components");
    }

    public function getPackageDescription()
    {
        return t("A package that installs a block to allow you to add your twitter feed to any theme.");
    }

    public function getTwitterConsumerKey()
    {
        return $this->twitterConsumerKey;
    }

    public function getTwitterConsumerSecret()
    {
        return $this->twitterConsumerSecret;
    }

    public function on_start()
    {
        $this->registerAssets();
        $rh = new TwitterFeedRequestHandler($this);
    }

    public function install()
    {
        $pkg = parent::install();

        // Install the block type
        $bt = BlockType::installBlockTypeFromPackage('twitter_feed', $pkg);
    }

    public function uninstall()
    {
        parent::uninstall();

        $db = Database::get();
        $db->exec('DROP TABLE btTwitterFeedAuthorizations;');
        $db->exec('DROP TABLE btTwitterFeed;');
    }

    protected function registerAssets()
    {
        // Register assets
        $al = AssetList::getInstance();

        // Bootstrap Tabs
        $al->register(
                'javascript', 'bootstrap/tab', 'assets/bootstrap.tab.js',
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
                'javascript', 'switchery/js', 'assets/switchery.js',
                array(
                    'version' => '0.7.0',
                    'position' => Asset::ASSET_POSITION_FOOTER,
                    'minify' => true,
                    'combine' => true
                ),
                $this
        );

        $al->register(
                'css', 'switchery/css', 'assets/switchery.css',
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
                'css', 'twitterfeed/form', 'blocks/twitter_feed/css/form.css',
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
