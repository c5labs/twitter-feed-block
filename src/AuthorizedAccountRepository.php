<?php
/**
 * Authorized Account Repository File
 *
 * PHP version 5.3
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <green2go@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     http://codeblog.co.uk
 */
namespace Concrete\Package\TweetFeedPackage\Src;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Authorized Account Repository Class
 *
 * Provides a *very* loose implementation of the respository
 * pattern which abstracts all dealings with the account
 * authorization table to this file.
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <green2go@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     http://codeblog.co.uk
 */
class AuthorizedAccountRepository
{
    /**
     * Database instance
     *
     * @var \Concrete\Core\Database\DatabaseManager
     */
    protected $db;

    /**
     * Table Name
     *
     * @var string
     */
    protected $table = 'btTwitterFeedAuthorizations';

    /**
     * The BlockType table
     *
     * @var string
     */
    protected $blockTypeTable = 'btTwitterFeed';

    /**
     * Constructor
     *
     * @param \Concrete\Core\Database\DatabaseManager $database
     */
    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Create entry by oAuth temporary tokens
     *
     * @param  string $oauth_token
     * @param  string $oauth_token_secret
     * @return void
     */
    public function createEntry($oauth_token, $oauth_token_secret)
    {
        $this->db->insert(
            $this->table,
            array(
                'oauth_token' => $oauth_token,
                'oauth_token_secret' => $oauth_token_secret
            )
        );
    }

    /**
     * Get an entry by oAuth temporary token
     *
     * @param  string $oauth_token
     * @return void
     */
    public function getEntryByToken($oauth_token)
    {
        return $this->db->fetchAssoc(
            'select * from ' . $this->table . ' where oauth_token = ?',
            array($oauth_token)
        );
    }

    /**
     * Get an entry by ID
     *
     * @param  integer $acID
     * @return array
     */
    public function getEntryByID($acID)
    {
        return $this->db->fetchAssoc(
            'select * from ' . $this->table . ' where acID = ?',
            array($acID)
        );
    }

    /**
     * Updates an entry by temporary oAuth token
     *
     * @param  string $oauth_token
     * @param  string $access_token
     * @param  string $access_token_secret
     * @param  string $twitter_handle
     * @return boolean
     */
    public function updateEntryByToken(
        $oauth_token,
        $access_token,
        $access_token_secret,
        $twitter_handle
    ) {
        return $this->db->update(
            $this->table,
            array(
                'access_token' => $access_token,
                'access_token_secret' => $access_token_secret,
                'twitter_handle' => $twitter_handle
            ),
            array(
                'oauth_token' => $oauth_token,
            )
        );
    }

    /**
     * Removes an entry by ID
     * 
     * @param  integer $acID
     * @return boolean      
     */
    public function removeEntry($acID)
    {
        // Update all blocks removing this from account selection.
        $q = 'select * from ' . $this->blockTypeTable . ' where use_account = ?';
        $blocks = $this->db->query($q, array($acID))->fetchAll();

        foreach ($blocks as $block) {

            $this->db->update(
                $this->blockTypeTable,
                array(
                    'use_account' => null
                ),
                array(
                    'bID' => $block['bID'],
                )
            );
        }

        return $this->db->delete(
            $this->table,
            array(
                'acID' => $acID,
            )
        );
    }

    /**
     * Gets a list of the authorized accounts
     * 
     * @return array
     */
    public function getEntryList()
    {
        $q = 'select * from ' . $this->table . ' where twitter_handle IS NOT NULL';
        $accounts = $this->db->query($q)->fetchAll();

        $q = 'select * from ' . $this->blockTypeTable . ' where use_account = ?';
        foreach ($accounts as $k => $v) {
            $accounts[$k]['dependent_blocks'] = $this->db->query($q, array($v['acID']))->rowCount();
        }

        return $accounts;
    }
}
