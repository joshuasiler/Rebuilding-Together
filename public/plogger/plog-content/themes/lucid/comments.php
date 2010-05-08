<?php if (plogger_comments_on()) { ?>
				<!-- display comments for selected picture -->
				<a name="comments"></a>
				<h2 class="comment-heading"><?php echo plog_tr('Comments') ; ?></h2>

<?php if (plogger_picture_has_comments()) { ?>
					<ol class="comments">
<?php $counter = 0;
	while(plogger_picture_has_comments()) {
		plogger_load_comment();
		// This code alternates the background color every other comment
		$comment_class = ($counter % 2) ? 'comment-alt' : 'comment';
?>
						<li class="<?php echo $comment_class; ?>">
							<p><?php echo plogger_get_comment_text(); ?></p>
							<cite><?php echo plog_tr('Comment by'); ?> <?php echo (trim(plogger_get_comment_url()) != '') ? '<a href="'.plogger_get_comment_url().'" rel="nofollow">'.plogger_get_comment_author().'</a>' : ''.plogger_get_comment_author().''; ?> - <?php echo plog_tr('posted on'); ?> <?php echo plogger_get_comment_date(); ?></cite>
						</li><!-- /comment-alt comment -->
<?php $counter++; } ?>
					</ol><!-- /comments -->

<?php } else { ?>
				<p><?php echo plog_tr('No comments yet'); ?></p>

<?php }
	if (plogger_picture_allows_comments()) {
		global $config;
		if (plogger_comment_post_error()) { ?>
				<p class="errors">
					<?php echo plog_tr('Comment did not post due to the following errors:'); ?>
<?php while (plogger_has_comment_errors()) { ?>

					<br />&bull; <?php echo plogger_get_comment_error(); ?>
<?php } ?>

				</p>
<?php } ?>
<?php if (plogger_comment_moderated()) { ?>
				<p class="actions"><?php echo plog_tr('Your comment was placed in moderation, please wait for approval.'); ?><br /><?php echo plog_tr('Do not submit your comment again!'); ?></p>
<?php } ?>

<?php plogger_require_captcha(); ?>
				<a name="comment-post"></a>
				<h3 class="comment-heading"><?php echo plog_tr('Post a comment'); ?>:</h3>
					<form action="<?php echo $config['gallery_url']; ?>plog-comment.php" method="post" id="commentform">
						<p>
							<input type="text" name="author" id="author" <?php if (plogger_is_form_error('author')) { echo 'class="field-error" '; } ?>value="<?php echo plogger_get_form_author(); ?>" size="28" tabindex="1" />
							<label for="author"><?php echo plog_tr('Name'); ?></label> (<?php echo plog_tr('required'); ?>)
							<input type="hidden" name="parent" value="<?php echo plogger_get_picture_id(); ?>" />
							<input type="hidden" name="redirect" value="<?php echo str_replace('&', '&amp;', $_SERVER['REQUEST_URI']); ?>#comment-post" />
							<?php echo plogger_get_form_token(); ?>
						</p>
						<p>
							<input type="text" name="email" id="email" <?php if (plogger_is_form_error('email')) { echo 'class="field-error" '; } ?>value="<?php echo plogger_get_form_email(); ?>" size="28" tabindex="2" />
							<label for="email"><?php echo plog_tr('Email'); ?></label> (<?php echo plog_tr('required, but not publicly displayed'); ?>)
						</p>
						<p>
							<input type="text" name="url" id="url" <?php if (plogger_is_form_error('url')) { echo 'class="field-error" '; } ?>value="<?php echo plogger_get_form_url(); ?>" size="28" tabindex="3" />
							<label for="url"><?php echo plog_tr('Your Website (optional)'); ?></label>
						</p>
						<p><img src="<?php echo $config['gallery_url'].'plog-includes/plog-captcha.php'; ?>" alt="CAPTCHA Image" id="captcha-image" /></p>
						<p>
							<input type="text" name="captcha" id="captcha" <?php if (plogger_is_form_error('captcha')) { echo 'class="field-error" '; } ?>value="" size="28" tabindex="3" />
							<label for="url"><?php echo plog_tr('What does this say'); ?>?</label>
						</p>
						<p>
							<label for="comment"><?php echo plog_tr('Your Comment'); ?></label><br />
							<textarea name="comment" id="comment" <?php if (plogger_is_form_error('comment')) { echo 'class="field-error" '; } ?>cols="70" rows="4" tabindex="4"><?php echo plogger_get_form_comment(); ?></textarea>
						</p>
						<p class="inputbuttonp"><input class="submit" name="submit" type="submit" tabindex="5" value="<?php echo plog_tr('Post Comment'); ?>" /></p>
					</form><!-- /commentform -->
<?php } else { ?>
				<p class="comments-closed"><?php echo plog_tr('Comments are closed'); ?></p>

<?php } ?>
<?php } ?>