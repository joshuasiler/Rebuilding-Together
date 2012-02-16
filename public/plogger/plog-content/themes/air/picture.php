<?php plogger_get_header(); ?>

	<div id="big-picture-container">

<?php if (plogger_has_pictures()) : while(plogger_has_pictures()) : plogger_load_picture(); // Equivalent to the WordPress loop
	// Find thumbnail width/height
	$thumb_info = plogger_get_thumbnail_info();
	$thumb_width = $thumb_info['width']; // The width of the image. It is integer data type.
	$thumb_height = $thumb_info['height']; // The height of the image. It is an integer data type.
?>
		<div id="nav-link-img-prev"><?php echo plogger_get_prev_picture_link(); ?></div>
		<div id="nav-link-img-next"><?php echo plogger_get_next_picture_link(); ?></div>

		<h2 class="picture-title"><?php echo plogger_get_picture_caption(); ?></h2>
		<h2 class="date"><?php echo plogger_get_picture_date(); ?></h2>

		<div id="picture-holder">
			<a accesskey="v" href="<?php echo plogger_get_source_picture_url(); ?>"><img class="photos-large" src="<?php echo plogger_get_picture_thumb(THUMB_LARGE); ?>" width="<?php echo $thumb_width; ?>" height="<?php echo $thumb_height; ?>" title="<?php echo plogger_get_picture_caption('clean'); ?>" alt="<?php echo plogger_get_picture_caption('clean'); ?>" /></a>
		</div><!-- /picture-holder -->

		<p id="picture-description"><?php echo plogger_get_picture_description(); ?></p>
		<div id="exif-toggle"><?php echo plogger_get_detail_link(); ?></div>
		<div id="exif-toggle-container">
<?php echo generate_exif_table(plogger_get_picture_id()); ?>
		</div><!-- /exif-toggle-container -->

<?php if (plogger_get_thumbnail_nav() != '') { ?>
		<div class="clearfix">
<?php echo plogger_get_thumbnail_nav(); ?>
		</div><!-- /clearfix -->
<?php } ?>

<?php echo plogger_display_comments(); ?>
<?php endwhile; ?>
<?php else : ?>
		<div id="no-pictures-msg">
			<h2><?php echo plog_tr('Not Found') ?></h2>
			<p><?php echo plog_tr('Sorry, but the image that you requested does not exist.') ?></p>
		</div><!-- /no-pictures-msg -->
	<?php endif; ?>
	</div><!-- /big-picture-container -->
<?php plogger_get_footer(); ?>
