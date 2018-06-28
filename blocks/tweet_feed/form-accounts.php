<?php
defined('C5_EXECUTE') or die('Access Denied.');
/*
 * Accounts Form Tab
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
?>
<div role="tabpanel" class="tab-pane" id="tfAccounts">
    <div class="row">
        <div class="col-xs-8">
            <p>
                <?php echo t('The accounts listed below are have been authorized with twitter.'); ?>
            </p>
        </div>
        <div class="col-xs-4 text-right">
            <a href="javascript:void(0)" id="tfAddAccount" class="btn btn-default"><?php echo t('Add account'); ?></a>
        </div>
    </div>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php echo t('Account'); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($accounts) > 0) {
    ?>
            <?php foreach ($accounts as $account) {
    ?>
            <tr data-account-id="<?php echo $account['acID'];
    ?>" data-account-handle="<?php echo $account['twitter_handle'];
    ?>" data-dependent-blocks="<?php echo $account['dependent_blocks'];
    ?>">
                <td>@<?php echo $account['twitter_handle'];
    ?></td>
                <td class="text-right">
                    <a href="javascript:void(0);" class="btn btn-sm btn-default profile-btn"><?php echo t('View Profile');
    ?></a>
                    <a href="javascript:void(0);" class="btn btn-sm btn-danger remove-btn"><?php echo t('Remove');
    ?></a>
                </td>
            </tr>
            <?php 
}
    ?>
            <?php 
} else {
    ?>
            <tr id="tfNoAccountsRow">
                <td colspan="2"><?php echo t('No accounts authorized.');
    ?></td>
            </tr>
            <?php 
} ?>
        </tbody>
    </table>
    <div class="alert alert-info" role="alert">
        <?php echo t('These accounts are <strong>global</strong>, this means they are available in any Twitter Feed block on your site, changes to the accounts here will <strong>affect all other blocks</strong>.'); ?>
    </div>
</div>

<!-- Authorizing Screen !-->
<div role="tabpanel" class="tab-pane text-center" id="tfAuthorizing">
    <img src="<?php echo $this->getBlockUrl(); ?>/load_icon.gif" alt="<?php echo t('Authorizing Account'); ?>">
    <h2><?php echo t('Authorizing Account'); ?></h2>
    <p>
        <a href="javascript:void(0)" class="btn btn-default" id="tfRetryBtn"><?php echo t('Re-try Authorization'); ?></a>
        <a href="javascript:void(0)" id="tfCancelBtn"><?php echo t('or cancel'); ?></a>
    </p>
</div>

<!-- Pin Screen !-->
<div role="tabpanel" class="tab-pane text-center" id="tfPin">
    <h2><?php echo t('Enter Twitter PIN'); ?></h2>
    <div class="input-group" style="margin: 0 auto 20px;">
        <input type="text" id="tfPinField" name="pin" placeholder="e.g. 123456" class="form-control">
    </div>
    <p>
        <a href="javascript:void(0)" class="btn btn-primary" id="tfSavePinBtn"><?php echo t('Authorize'); ?></a>
        <a href="javascript:void(0)" id="tfPinCancelBtn"><?php echo t('or cancel'); ?></a>
    </p>
</div>