<?php
defined('C5_EXECUTE') or die('Access Denied.');
/**
 * Accounts Form Tab
 *
 * @package  TwitterFeedPackage
 * @author   Oliver Green <green2go@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     http://codeblog.co.uk
 */
?>
<div role="tabpanel" class="tab-pane" id="accounts">
    <div class="row">
        <div class="col-xs-9">
            <p>
                <?php echo t('The accounts listed below are have been authorized with twitter.'); ?>
            </p>
        </div>
        <div class="col-xs-3 text-right">
            <a href="javascript:void(0)" id="addAccount" class="btn btn-default"><?php echo t('Add account'); ?></a>
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
            <?php if (count($accounts) > 0) { ?>
            <?php foreach ($accounts as $account) { ?>
            <tr data-account-id="<?php echo $account['acID']; ?>" data-account-handle="<?php echo $account['twitter_handle']; ?>" data-dependent-blocks="<?php echo $account['dependent_blocks']; ?>">
                <td>@<?php echo $account['twitter_handle']; ?></td>
                <td class="text-right">
                    <a href="javascript:void(0);" class="btn btn-sm btn-default profile-btn"><?php echo t('View Profile'); ?></a>
                    <a href="javascript:void(0);" class="btn btn-sm btn-danger remove-btn"><?php echo t('Remove'); ?></a>
                </td>
            </tr>
            <?php } ?>
            <?php } else { ?>
            <tr id="noAccountsRow">
                <td colspan="2"><?php echo t('No accounts authorized.'); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <div class="alert alert-info" role="alert">
        <?php echo t('These accounts are <strong>global</strong>, this means they are available in any Twitter Feed block on your site, changes to these accounts here will <strong>affect all other blocks</strong>.'); ?>
    </div>
</div>

<!-- Authorizing Screen !-->
<div role="tabpanel" class="tab-pane text-center" id="authorizing">
    <img src="<?php echo $this->getBlockUrl(); ?>/load_icon.gif" alt="<?php echo t('Authorizing Account'); ?>">
    <h2><?php echo t('Authorizing Account'); ?></h2>
    <p>
        <a href="javascript:void(0)" class="btn btn-default" id="retryBtn"><?php echo t('Re-try Authorization'); ?></a>
        <a href="javascript:void(0)" id="cancelBtn"><?php echo t('or cancel'); ?></a>
    </p>
</div>

<script>

    var base_url = "<?php echo View::url('/twitter-feed-package'); ?>",
    csrf_token = "<?php echo Core::make('helper/validation/token')->generate(); ?>";


    $(function () {

        $('.profile-btn').click(profileButtonHandler);

        function profileButtonHandler()
        {
            window.open('https://twitter.com/' + $(this).parent().parent().data('account-handle'));
        }

        $('.remove-btn').click(removeButtonHander);

        function removeButtonHander(e)
        {
            var $row = $(this).parent().parent(),
            msg = ccm_t('confirm-account-removal-no-dependents'),
            dependent_blocks = $(this).parent().parent().data('dependent-blocks');

            if (dependent_blocks > 0) {
                msg = ccm_t('confirm-account-removal-dependents').replace('{{ num_blocks }}', dependent_blocks);
            }

            if (confirm(msg)) {
                $.ajax({
                    url: base_url + "/remove-account/" +  $row.data('account-id'),
                    type: 'POST',
                    data: { csrf_token: csrf_token },
                })
                .done(function(data) {
                    removeEntry($row.data('account-id'));
                    removeFromUseAccount($row.data('account-id'));
                })
                .fail(function() {
                    console.log("error");
                })
                .always(function() {
                    console.log("complete");
                });
            } else {
                e.preventDefault();
            }
        }

        function addToUseAccount(acID, twitter_handle)
        {
            $('#use_account').append($('<option></option>').prop('value', acID).html(twitter_handle));

            var data = $('#use_account').select2("val");

            if (0 === data.length) {
                $('#use_account').select2('val', [{ id: acID.toString() }]);
                $('#show_tweets_type option:first-child').attr('selected', 'selected');
            }

            if ($('#use_account option').length > 1) {
                $('#use_account').parent().css({ display: 'block' });
            }
        }

        function removeFromUseAccount(acID)
        {
            var data = $('#use_account').select2("val");

            for (var k in data) {
                if (data[k] == acID) {
                    delete(data[k]);
                }
            }

            $('#use_account option[value=' + acID + ']').remove();

            if ($('#use_account option').length > 0) {
                $('#use_account option:first-child').attr('selected', 'selected');
                $('#show_tweets_type option:first-child').attr('selected', 'selected');
            }

            if ($('#use_account option').length <= 1) {
                $('#use_account').parent().css({ display: 'none' });
            }
        }

        function addEntry(acID, handle)
        {
            var $tbody = $('#accounts table tbody'),
            $tr = $('<tr></tr>').attr('data-account-id', acID).attr('data-account-handle', handle),
            $handleCol = $('<td></td>').html('@' + handle),
            $buttonCol = $('<td></td>').addClass('text-right'),
            $profileButton = $('<a href="javascript:void(0);" class="btn btn-sm btn-default profile-btn">' + ccm_t('view-profile-button') + '</a>'),
            $removeButton = $('<a href="javascript:void(0);" class="btn btn-sm btn-danger remove-btn">' + ccm_t('remove-account-button') + '</a>');

            $profileButton.click(profileButtonHandler);
            $removeButton.click(removeButtonHander);

            $tbody.append($tr.append($handleCol, $buttonCol.append($profileButton, " ", $removeButton)));
            $('#noAccountsRow').remove();
        }

        function removeEntry(acID)
        {
            var $row = $('#accounts tr[data-account-id=' + acID + ']');
            if (0 === $row.siblings().length) {
                $row.after($('<tr id="noAccountsRow"><td colspan="2">' + ccm_t('no-accounts-message') + '</td></tr>'));
                applyFirstRun(); // @see form.php
            }
            $row.remove();
        }

        var oAuthWindow,
        oAuthToken,
        pollInterval,
        requestInProgess = false,
        stopPollInterval = false;

        function openOAuthWindow()
        {
            var width = parseInt($(window).width() / 2);
            centre = parseInt(($(window).width() - width) / 2);
            if(oAuthWindow == null || oAuthWindow.closed) {
                oAuthWindow = window.open(base_url + '/redirect?csrf_token=' + csrf_token, 'twitter-oauth', 'left=' + centre + ',height=650,width=' + width + ',dialog,modal');
            } else {
                oAuthWindow.focus();
            }
        }

        $('#addAccount, #retryBtn, #addFirstAccount').click(function () {
            showAuthorizingScreen();
            openOAuthWindow();
            queuePollAuthStatus();
        });

        $('#cancelBtn').click(function () {
            hideAuthorizingScreen();
            stopPoll();
        });

        function showAuthorizingScreen()
        {
            $('#authorizing').addClass('active');

            if ($('#tfFormContainer').hasClass('tf-first-run')) {
                $('#firstRun').removeClass('active');
            } else {
                $('#accounts').removeClass('active');
            }
        }

        function hideAuthorizingScreen(accountAdded)
        {
            $('#authorizing').removeClass('active');

            if ($('#tfFormContainer').hasClass('tf-first-run') && !accountAdded) {
                $('#firstRun').addClass('active');
            } else {
                $('#firstRun').removeClass('active');
                $('#accounts').addClass('active');
                removeFirstRun(); // @see form.php
            }
        }

        $( ".ccm-ui" ).parent().on("dialogbeforeclose", function (event, ui)
        {
            stopPoll();
            oAuthToken = null;
        });

        function stopPoll()
        {
            stopPollInterval = true;
        }

        function queuePollAuthStatus()
        {
            clearInterval(pollInterval);
            pollInterval = setInterval(doPollAuthStatus, 1000);
        }

        function doPollAuthStatus()
        {   
            if (stopPollInterval) {
                clearInterval(pollInterval);
                stopPollInterval = false;
                return;
            }

            try {
                if (oAuthWindow.TwitterFeedOAuthToken) {
                    oAuthToken = oAuthWindow.TwitterFeedOAuthToken;
                }
            } catch(e) {}

            console.log('Trying to poll...');
            if (!requestInProgess && oAuthToken) {
                console.log(oAuthToken);
                requestInProgess = true;
                $.ajax({
                    url: base_url + '/auth-status/' + oAuthToken + '?csrf_token=' + csrf_token,
                    type: 'GET',
                    dataType: 'json',
                })
                .done(function(data) {
                    if (data && 'false' !== data) {
                        oAuthWindow.close();
                        stopPoll();
                        oAuthToken = null;
                        addEntry(data.acID, data.twitter_handle);
                        addToUseAccount(data.acID, '@' + data.twitter_handle);
                        hideAuthorizingScreen(true);
                    }
                })
                .fail(function() {
                    console.log("error");
                })
                .always(function() {
                    console.log("complete");
                    requestInProgess = false;
                });

            }
        }


    });
</script>