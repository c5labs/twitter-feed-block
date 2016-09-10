<?php
defined('C5_EXECUTE') or die('Access Denied.');
/**
 * General Form Tab
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     https://c5dev.com/add-ons/twitter-feed
 */
?>
<div role="tabpanel" class="tab-pane active" id="tfGeneral">

    <div class="form-group" <?php if(count($account_list) <= 1) { ?>style="display: none;"<?php } ?>>
        <?php echo $form->label('use_account', t('Use account'))?>
        <?php echo $form->select(
            'use_account',
            $account_list,
            $use_account,
            array('style' => 'border: 1px solid #ccc')
            ); ?>
        <script>
            $(function () {
                $("#use_account").select2();
            });
        </script>
    </div>

    <div class="form-group">
        <?php echo $form->label('show_tweets_from', t('Show tweets from'))?>
        <div class="row">
            <div class="col-xs-6">
                <?php echo $form->select(
                    'show_tweets_type',
                    $type_list,
                    $show_tweets_type,
                    array('style' => 'border: 1px solid #ccc')
                ); ?>
            </div>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon">@</span>
                    <?php echo $form->text('show_tweets_from', $show_tweets_from); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <?php echo $form->label('num_tweets', t('Show'))?>
        <div class="input-group col-xs-4">
            <?php echo $form->text('num_tweets', $num_tweets); ?>
            <span class="input-group-addon"><?php echo t('tweets'); ?></span>
        </div>
    </div>

    <div class="form-group">
        <?php echo $form->label('refresh_interval', t('Refresh every'))?>
        <div class="input-group col-xs-4">
            <?php echo $form->text('refresh_interval', $refresh_interval); ?>
            <span class="input-group-addon"><?php echo t('minutes'); ?></span>
        </div>
    </div>
</div>