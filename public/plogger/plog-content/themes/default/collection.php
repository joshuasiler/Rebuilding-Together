<?php plogger_get_header(); ?>

		<div id="thumbnail-container">
<?php if (plogger_has_albums()) : ?>
			<ul class="slides">
<?php while(plogger_has_albums()) : plogger_load_album();
	// Find thumbnail width/height
	$thumb_info = plogger_get_thumbnail_info();
	$thumb_width = $thumb_info['width']; // The width of the image. It is integer data type.
	$thumb_height = $thumb_info['height']; // The height of the image. It is an integer data type.
	$div_width = $thumb_width + 30; // Account for padding/border width
	$div_height = $thumb_height + 75; // Account for padding/border width
?>
				<li class="thumbnail">
					<div class="tag" style="width: <?php echo $div_width; ?>px; height: <?php echo $div_height; ?>px;">
						<a href="<?php echo plogger_get_album_url(); ?>"><img class="photos" src="<?php echo plogger_get_album_thumb(); ?>" width="<?php echo $thumb_width; ?>" height="<?php echo $thumb_height; ?>" title="<?php echo plogger_get_album_name(); ?>" alt="<?php echo plogger_get_album_name(); ?>" /></a><br />
						<a href="<?php echo plogger_get_album_url(); ?>"><?php echo plogger_get_album_name(); ?></a>
						<?php echo plogger_download_checkbox(plogger_get_album_id()); ?><br />
						<span class="meta-header">(<?php echo plogger_album_picture_count() . ' '; echo (plogger_album_picture_count() == 1) ? plog_tr('image') : plog_tr('images'); ?>)</span>
					</div><!-- /tag -->
				</li><!-- /thumbnail -->
<?php endwhile; ?>
			</ul><!-- /slides -->
<?php else : ?>
			<div id="no-pictures-msg">
				<h2><?php echo plog_tr('No Albums') ?></h2>
				<p><?php echo plog_tr('Sorry, but there are no images or albums in this collection yet.') ?></p>
			</div><!-- /no-pictures-msg -->
<?php endif; ?>
		</div><!-- /thumbnail-container -->

<?php plogger_get_footer(); ?>