<?php
/**
 * Authorized Account Repository File.
 *
 * PHP version 5.4
 *
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5dev.com/add-ons/twitter-feed
 */
namespace Concrete\Package\TweetFeedPackage\Src;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Authorized Account Repository Class.
 *
 * Provides a *very* loose implementation of the respository
 * pattern which abstracts all dealings with the account
 * authorization table to this file.
 *
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5dev.com/add-ons/twitter-feed
 */
class AuthorizedAccountRepository
{
    /**
     * Database instance.
     *
     * @var \Concrete\Core\Database\DatabaseManager
     */
    protected $db;

    /**
     * Table Name.
     *
     * @var string
     */
    protected $table = 'btTwitterFeedAuthorizations';

    /**
     * The BlockType table.
     *
     * @var string
     */
    protected $blockTypeTable = 'btTwitterFeed';

    /**
     * Constructor.
     *
     * @param \Concrete\Core\Database\DatabaseManager $database
     */
    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Create entry by oAuth temporary tokens.
     *
     * @param  string $oauth_token
     * @param  string $oauth_token_secret
     * @return void
     */
    public function createEntry($oauth_token, $oauth_token_secret)
    {
        $this->db->insert(
            $this->table,
            [
                'oauth_token' => $oauth_token,
                'oauth_token_secret' => $oauth_token_secret,
            ]
        );
    }

    /**
     * Get an entry by oAuth temporary token.
     *
     * @param  string $oauth_token
     * @return void
     */
    public function getEntryByToken($oauth_token)
    {
        return $this->db->fetchAssoc(
            'select * from '.$this->table.' where oauth_token = ?',
            [$oauth_token]
        );
    }

    /**
     * Get an entry by ID.
     *
     * @param  int $acID
     * @return array
     */
    public function getEntryByID($acID)
    {
        return $this->db->fetchAssoc(
            'select * from '.$this->table.' where acID = ?',
            [$acID]
        );
    }

    /**
     * Updates an entry by temporary oAuth token.
     *
     * @param  string $oauth_token
     * @param  string $access_token
     * @param  string $access_token_secret
     * @param  string $twitter_handle
     * @return bool
     */
    public function updateEntryByToken(
        $oauth_token,
        $access_token,
        $access_token_secret,
        $twitter_handle
    ) {
        return $this->db->update(
            $this->table,
            [
                'access_token' => $access_token,
                'access_token_secret' => $access_token_secret,
                'twitter_handle' => $twitter_handle,
            ],
            [
                'oauth_token' => $oauth_token,
            ]
        );
    }

    /**
     * Removes an entry by ID.
     * 
     * @param  int $acID
     * @return bool      
     */
    public function removeEntry($acID)
    {
        // Update all blocks removing this from account selection.
        $q = 'select * from '.$this->blockTypeTable.' where use_account = ?';
        $blocks = $this->db->query($q, [$acID])->fetchAll();

        foreach ($blocks as $block) {
            $this->db->update(
                $this->blockTypeTable,
                [
                    'use_account' => null,
                ],
                [
                    'bID' => $block['bID'],
                ]
            );
        }

        return $this->db->delete(
            $this->table,
            [
                'acID' => $acID,
            ]
        );
    }

    /**
     * Gets a list of the authorized accounts.
     * 
     * @return array
     */
    public function getEntryList()
    {
        $q = 'select * from '.$this->table.' where twitter_handle IS NOT NULL';
        $accounts = $this->db->query($q)->fetchAll();

        $q = 'select * from '.$this->blockTypeTable.' where use_account = ?';
        foreach ($accounts as $k => $v) {
            $accounts[$k]['dependent_blocks'] = $this->db->query($q, [$v['acID']])->rowCount();
        }

        return $accounts;
    }
}
