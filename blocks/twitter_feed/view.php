<?php defined('C5_EXECUTE') or die("Access Denied.");
$c = Page::getCurrentPage();

$tf_class = 'tf-tweets';
if ($show_avatars) {
    $tf_class .= ' avatars';
}
?>
<div class="tf-container">

<?php if (isset($error)) { ?>

    <span class="tf-error">
        <?php echo t('There was a problem retreving the tweets:'); ?>
        <p class="tf-error-message">
            <?php echo $error->message; ?>
        </p>
    </span>

<?php } elseif (is_array($account)) { ?>

    <?php if (count($tweets) > 0) { ?>
    
    <ul class="<?php echo $tf_class; ?>">

        <?php foreach ($tweets as $tweet) { ?>
        
        <li class="tf-tweet">

            <?php if ($show_authors) { ?>
            <div class="tf-meta">
                <span class="tf-name">
                    <?php echo $tweet->name; ?>
                </span>
                <span class="tf-screen-name">
                    <?php echo $tweet->screen_name; ?>
                </span>
            </div>
            <?php } ?>

            <div class="tf-body">
                <?php echo $tweet->text; ?>
            </div>

            <div class="tf-meta">
                <?php if ($show_avatars) { ?>
                <span class="tf-avatar">
                    <img src="<?php echo $tweet->avatar_url; ?>" alt="<?php echo $tweet->screen_name; ?>">
                </span>
                <?php } ?>

                <?php if ($show_date) { ?>
                <span class="tf-date">
                    <?php echo $tweet->created_at; ?>
                </span>
                <?php } ?>
            </div>

        </li>
        
        <?php } ?>

    </ul>

    <?php } else { ?>
        <div class="tf-no-tweets-found">No tweets found.</div>
    <?php } ?>

<?php } elseif ($c->isEditMode()) { ?>
    <div class="ccm-edit-mode-disabled-item">In-Active Twitter Feed Block.</div>
<?php } ?>

</div>