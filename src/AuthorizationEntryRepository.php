<?php
namespace Concrete\Package\TwitterFeedPackage\Src;

class AuthorizationEntryRepository
{
    protected $db;

    protected $table = 'btTwitterFeedAuthorizations';

    protected $blockTypeTable = 'btTwitterFeed';

    public function __construct($database)
    {
        $this->db = $database;
    }

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

    public function getEntryByToken($oauth_token)
    {
        return $this->db->fetchAssoc('select * from ' . $this->table . ' where oauth_token = ?',
            array($oauth_token)
        );
    }

    public function getEntryByID($acID)
    {
        return $this->db->fetchAssoc('select * from ' . $this->table . ' where acID = ?',
            array($acID)
        );
    }

    public function updateEntryByToken($oauth_token, $access_token, $access_token_secret, $twitter_handle)
    {
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

    public function getEntryList()
    {
        $accounts = $this->db->query('select * from ' . $this->table . ' where twitter_handle IS NOT NULL')->fetchAll();
        $q = 'select * from ' . $this->blockTypeTable . ' where use_account = ?';

        foreach ($accounts as $k => $v) {
            $accounts[$k]['dependent_blocks'] = $this->db->query($q, array($v['acID']))->rowCount();
        }

        return $accounts;
    }
}
