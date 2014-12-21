/**
* Block Editor / Form Javascript File
*
* @package  TwitterFeedPackage
* @author   Oliver Green <green2go@gmail.com>
* @license  http://www.gnu.org/copyleft/gpl.html GPL3
* @link     http://codeblog.co.uk
*/
function TwitterFeedBlockEditor(base_url, csrf_token)
{
    "use strict";

    /**
     * General functions used on all editor tabs
     *
     * @package  TwitterFeedPackage
     * @author   Oliver Green <green2go@gmail.com>
     * @license  http://www.gnu.org/copyleft/gpl.html GPL3
     * @link     http://codeblog.co.uk
     */

    /**
     * First Load - Helps set 
     * the tweet source type control
     * 
     * @type {Boolean}
     */
    var first_load = true;

    /**
     * Holder for methods we'll make public
     * 
     * @type {Object}
     */
    var expose = {};

    /**
     * Removes the first run class 
     * enables the tabs
     * 
     * @return {void}
     */
    function removeFirstRun()
    {
        $('#tfFormContainer').removeClass('tf-first-run');
        $('#tfFormContainer .nav-tabs a').attr('data-toggle', 'tab').parent().removeClass('disabled');
    }
    expose.removeFirstRun = removeFirstRun;

    /**
     * Disables all but the account tab 
     * and adds the css class 
     * 
     * @return {void}
     */
    function applyFirstRun()
    {
        $('#tfFormContainer .nav-tabs a:last').tab('show');
        $('#tfFormContainer .nav-tabs a').removeAttr('data-toggle').parent().addClass('disabled');
        $('#accounts').removeClass('active');
        $('#firstRun').addClass('active');
    }
    expose.applyFirstRun = applyFirstRun;

    /**
     * Bind the general tabs tweet source control
     *
     * @return {void}
     */
    $("#show_tweets_type").change(function () {
        var $input = $('#show_tweets_from'),
        $input_addon = $(this).parent().parent().find('.input-group-addon'),
        val = $(this).val();

        if (!first_load) $input.val('');

        switch (val) {
            case 'user':
                $input_addon.html('@');
                $input.attr('placeholder', 'concrete5');
            break;
            case 'hashtag':
                $input_addon.html('#');
                $input.attr('placeholder', 'concrete5');
            break;
            case 'list':
                $input_addon.html('<span class="fa fa-list"></span>');
                $input.attr('placeholder', 'olsgreen/concrete5');
            break;
            case 'search':
                $input_addon.html('<span class="fa fa-search"></span>');
                $input.attr('placeholder', ccm_t('search-control-placeholder'));
            break;
        }

        first_load = false;
    }).trigger('change');

    /**
     * Adds an account to the account selection 
     * control on the general tab
     * 
     * @return {void}
     */
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

    /**
     * Removes a user account from the account selection 
     * control on the general tab
     * 
     * @return {void}
     */
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


    /**
     * Account Tab Specific
     *
     * @package  TwitterFeedPackage
     * @author   Oliver Green <green2go@gmail.com>
     * @license  http://www.gnu.org/copyleft/gpl.html GPL3
     * @link     http://codeblog.co.uk
     */
    
    /**
     * Account profile button click
     * 
     * @return {void}
     */
    function profileButtonHandler()
    {
        var handle = $(this).parent().parent().data('account-handle');
        window.open('https://twitter.com/' + handle);
    }
    $('.profile-btn').click(profileButtonHandler);

    /**
     * Account remove button click
     * 
     * @return {void}
     */
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
                alert(ccm_t('account-removal-failure'));
            });
        } else {
            e.preventDefault();
        }
    }
    $('.remove-btn').click(removeButtonHander);

    /**
     * Adds a user account to the UI
     * 
     * @return {void}
     */    
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

    /**
     * Removes a user account from the UI
     * 
     * @return {void}
     */  
    function removeEntry(acID)
    {
        var $row = $('#accounts tr[data-account-id=' + acID + ']');
        if (0 === $row.siblings().length) {
            $row.after($('<tr id="noAccountsRow"><td colspan="2">' + ccm_t('no-accounts-message') + '</td></tr>'));
            applyFirstRun(); // @see form.php
        }
        $row.remove();
    }

    /**
     * Create the popup window that directs 
     * the user to twitter to authorize the app
     * 
     * @return {void}
     */  
    function openOAuthWindow()
    {
        var width = parseInt($(window).width() / 2),
        centre = parseInt(($(window).width() - width) / 2);
        if(oAuthWindow == null || oAuthWindow.closed) {
            oAuthWindow = window.open(
                base_url + '/redirect?csrf_token=' + csrf_token, 
                'twitteroauth', 
                'left=' + centre + ',height=650,width=' + width + ',dialog,modal'
            );
        } else {
            oAuthWindow.focus();
        }
    }

    /**
     * Add / Authorize an account listeners
     * 
     * @return {void}
     */  
    $('#addAccount, #retryBtn, #addFirstAccount').click(function () {
        openOAuthWindow();
        showAuthorizingScreen();
        startPolling();
    });

    /**
     * Cancel authorization attempt button
     * 
     * @return {void}
     */
    $('#cancelBtn').click(function () {
        hideAuthorizingScreen();
        stopPoll();
    });

    /**
     * Show the authorizing screen while user 
     * is interacting with twitter in the other window
     * 
     * @return {void}
     */
    function showAuthorizingScreen()
    {
        $('#authorizing').addClass('active');

        if ($('#tfFormContainer').hasClass('tf-first-run')) {
            $('#firstRun').removeClass('active');
        } else {
            $('#accounts').removeClass('active');
        }
    }

    /**
     * Hide the authorizing screen
     * 
     * @return {void}
     */
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

    /**
     * Authorization Polling
     *
     * Once the window diverting the user to twitter has been opened, 
     * we start polling the backend to see whether the user has finished 
     * the authorization with twitter and that we have the correct access 
     * tokens. This allows us to automatically shut the window and update 
     * the UI with the new account.
     */
    var oAuthWindow,
    oAuthToken,
    pollInterval,
    requestInProgess = false,
    stopPollInterval = false;

    /**
     * Starts polling the backend for 
     * authorization updates
     * 
     * @return {void}
     */
    function startPolling()
    {
        clearInterval(pollInterval);
        pollInterval = setInterval(doPollAuthStatus, 1000);
    }

    /**
     * Performs the actual poll to the backend
     * 
     * @return {void}
     */
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

        if (!requestInProgess && oAuthToken) {
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
                alert(ccm_t('polling-problem'));
                stopPoll();
            })
            .always(function() {
                requestInProgess = false;
            });

        }
    }

    /**
     * Stops polling
     * 
     * @return {void}
     */
    function stopPoll()
    {
        stopPollInterval = true;
    }

    /**
     * Stop polling on block editors 
     * modal close
     * 
     * @return {void}
     */
    $( ".ccm-ui" ).parent().on("dialogbeforeclose", function (event, ui)
    {
        stopPoll();
        oAuthToken = null;
    });
    
    return expose;
}
