<?php
defined('C5_EXECUTE') or die('Access Denied.');
/**
 * Block Form
 *
 * @package  TwitterFeedPackage
 * @author   Oliver Green <green2go@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     http://codeblog.co.uk
 */
?>
<div id="tfFormContainer"<?php if (0 === count($account_list)) {?> class="tf-first-run"<?php } ?>>
    <div role="tabpanel">

        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#general" aria-controls="general" role="tab" data-toggle="tab"><?php echo t('General'); ?></a></li>
            <li role="presentation"><a href="#appearance" aria-controls="appearance" role="tab" data-toggle="tab"><?php echo t('Appearance'); ?></a></li>
            <li role="presentation"><a href="#accounts" aria-controls="accounts" role="tab" data-toggle="tab"><?php echo t('Accounts'); ?></a></li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">

            <?php $this->inc('form-general.php'); ?>
            <?php $this->inc('form-appearance.php'); ?>
            <?php $this->inc('form-accounts.php'); ?>

            <div role="tabpanel" class="tab-pane text-center" id="firstRun">
                <p><?php echo t('To get started you need authorize your first twitter account.'); ?></p>
                <a href="javascript:void(0)" id="addFirstAccount" class="btn btn-success btn-lg"><?php echo t('Add account'); ?></a>
            </div>

        </div>
    </div>
</div>
<script>

    function removeFirstRun()
    {
        $('#tfFormContainer').removeClass('tf-first-run');
        $('#tfFormContainer .nav-tabs a').attr('data-toggle', 'tab').parent().removeClass('disabled');
    }

    function applyFirstRun()
    {
        $('#tfFormContainer .nav-tabs a:last').tab('show');
        $('#tfFormContainer .nav-tabs a').removeAttr('data-toggle').parent().addClass('disabled');
        $('#accounts').removeClass('active');
        $('#firstRun').addClass('active');
    }

    <?php if (0 === count($account_list)) { ?>
    $(function () {
        applyFirstRun();
    });
    <?php } ?>
</script>