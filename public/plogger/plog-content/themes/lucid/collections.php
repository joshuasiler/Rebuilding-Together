<?php plogger_get_header(); ?>

		<div id="thumbnail-container">
<?php if (plogger_has_collections()) : ?>
			<div id="collections">
<?php while(plogger_has_collections()) : plogger_load_collection();
	// Find thumbnail width/height
	$thumb_info = plogger_get_thumbnail_info();
	$thumb_width = $thumb_info['width']; // The width of the image. It is integer data type.
	$thumb_height = $thumb_info['height']; // The height of the image. It is an integer data type.
?>
				<div class="collection">
					<a href="<?php echo plogger_get_collection_url(); ?>"><img class="photos" src="<?php echo plogger_get_collection_thumb(); ?>" width="<?php echo $thumb_width; ?>" height="<?php echo $thumb_height; ?>" title="<?php echo plogger_get_collection_description(); ?>" alt="<?php echo plogger_get_collection_description(); ?>" /></a>
					<h2><a href="<?php echo plogger_get_collection_url(); ?>"><?php echo plogger_get_collection_name(); ?></a></h2>
					<?php echo plogger_download_checkbox(plogger_get_collection_id()); ?>

					<span class="meta-header"><?php echo plog_tr('Contains'); ?> <?php echo plogger_collection_album_count() . ' '; echo (plogger_collection_album_count() == 1) ? plog_tr('Album') : plog_tr('Albums'); ?></span>
					<p class="description"><?php echo plogger_get_collection_description(); ?></p>
				</div><!-- /collection -->
<?php endwhile; ?>
			</div><!-- /collections -->
<?php else : ?>
			<div id="no-pictures-msg">
				<h2><?php echo plog_tr('No Images') ?></h2>
				<p><?php echo plog_tr('Sorry, but there are no images in this gallery yet.') ?></p>
			</div><!-- /no-pictures-msg -->
<?php endif; ?>
		</div><!-- /thumbnail-container -->
<?php plogger_get_footer(); ?>