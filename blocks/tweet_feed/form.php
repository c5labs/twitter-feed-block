<?php
defined('C5_EXECUTE') or die('Access Denied.');
/*
 * Block Form
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <oliver@c5labs.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5labs.com/add-ons/twitter-feed
 */
?>
<div id="tfFormContainer"<?php if (0 === count($account_list)) {
    ?> class="tf-first-run"<?php 
} ?>>
    <div role="tabpanel">

        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#tfGeneral" aria-controls="tfGeneral" role="tab" data-toggle="tab"><?php echo t('General'); ?></a></li>
            <li role="presentation"><a href="#tfAppearance" aria-controls="tfAppearance" role="tab" data-toggle="tab"><?php echo t('Appearance'); ?></a></li>
            <li role="presentation"><a href="#tfAccounts" aria-controls="tfAccounts" role="tab" data-toggle="tab"><?php echo t('Accounts'); ?></a></li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">

            <?php $this->inc('form-general.php'); ?>
            <?php $this->inc('form-appearance.php'); ?>
            <?php $this->inc('form-accounts.php'); ?>

            <div role="tabpanel" class="tab-pane text-center" id="tfFirstRun">
                <p><?php echo t('To get started you need authorize your first twitter account.'); ?></p>
                <a href="javascript:void(0)" id="tfAddFirstAccount" class="btn btn-success btn-lg"><?php echo t('Add account'); ?></a>
            </div>

        </div>
    </div>
</div>
<script>
    $(function () {
        var base_url = "<?php echo View::url('/twitter-feed-package'); ?>",
            csrf_token = "<?php echo Core::make('helper/validation/token')->generate(); ?>",
            tbe = new TwitterFeedBlockEditor(base_url, csrf_token);
    <?php if (0 === count($account_list)) {
    ?>
        tbe.applyFirstRun();
    <?php 
} ?>
    });
</script>