<?php
defined('C5_EXECUTE') or die('Access Denied.');
/**
 * Appearance Form Tab
 *
 * @package  TweetFeedPackage
 * @author   Oliver Green <green2go@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL3
 * @link     http://codeblog.co.uk
 */
?>
<div role="tabpanel" class="tab-pane" id="tfAppearance">
    
    <table class="table table-striped">
        <tr>
            <td><?php echo $form->label('show_replies', t('Show replies'))?></td>
            <td><?php echo $form->checkbox('show_replies', '1', $show_replies); ?></td>
        </tr>
        <tr>
            <td><?php echo $form->label('show_retweets', t('Show retweets'))?></td>
            <td><?php echo $form->checkbox('show_retweets', '1', $show_retweets); ?></td>
        </tr>
        <tr>
            <td><?php echo $form->label('show_date', t('Show tweet date & time'))?></td>
            <td><?php echo $form->checkbox('show_date', '1', $show_date); ?></td>
        </tr>
        <tr>
            <td><?php echo $form->label('expand_pictures', t('Expand tweet pictures from links'))?></td>
            <td><?php echo $form->checkbox('expand_pictures', '1', $expand_pictures); ?></td>
        </tr>
        <tr>
            <td><?php echo $form->label('show_authors', t('Show tweet authors'))?></td>
            <td><?php echo $form->checkbox('show_authors', '1', $show_authors); ?></td>
        </tr>
        <tr>
            <td><?php echo $form->label('show_avatars', t('Show tweet author avatars'))?></td>
            <td><?php echo $form->checkbox('show_avatars', '1', $show_avatars); ?></td>
        </tr>
    </table>

</div>
<script>
    $(function () {
        setTimeout(function () {
            $('#appearance input[type="checkbox"]').each(function () {
                var init = new Switchery(this, { size: 'small' });
            });
        }, 1000);
    });
</script>