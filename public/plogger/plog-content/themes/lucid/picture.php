<?php plogger_get_header(); ?>

		<div id="inner-wrapper">

			<div id="big-picture-container">
<?php if (plogger_has_pictures()) : while(plogger_has_pictures()) : plogger_load_picture(); // Equivalent to the WordPress loop
	// Set variables for the picture
	$thumb_info = plogger_get_thumbnail_info();
	$thumb_width = $thumb_info['width']; // The width of the image. It is integer data type.
	$thumb_height = $thumb_info['height']; // The height of the image. It is an integer data type.
	// Generate XHTML with thumbnail and link to picture view.
	$imgtag = '<img class="photos-large" src="'.plogger_get_picture_thumb(THUMB_LARGE).'" width="'.$thumb_width.'" height="'.$thumb_height.'" title="'.plogger_get_picture_caption('clean').'" alt="'.plogger_get_picture_caption('clean').'" />';
?>
				<div><h2 id="picture-caption"><?php echo plogger_get_picture_caption(); ?></h2></div>
				<div><h2 class="date"><?php echo plogger_get_picture_date()?></h2></div>

				<div id="nav-link-img-prev"><?php echo plogger_get_prev_picture_link(); ?></div>
				<div id="nav-link-img-next"><?php echo plogger_get_next_picture_link(); ?></div>

				<div id="picture-holder"><a accesskey="v" href="<?php echo plogger_get_source_picture_url(); ?>"><?php echo $imgtag; ?></a></div>

				<p id="picture-description"><?php echo plogger_get_picture_description(); ?></p>

				<div id="exif-toggle-container">
					<div id="exif-toggle"><?php echo plogger_get_detail_link(); ?></div>
<?php echo generate_exif_table(plogger_get_picture_id()); ?>
				</div><!-- /exif-toggle-container -->

<?php echo plogger_display_comments(); ?>
			</div><!-- /big-picture-container -->

<?php endwhile; ?>
<?php else : ?>
			<div id="no-pictures-msg">
				<h2><?php echo plog_tr('Not Found') ?></h2>
				<p><?php echo plog_tr('Sorry, but the image that you requested does not exist.') ?></p>
			</div><!-- /no-pictures-msg -->
<?php endif; ?>
		</div><!-- /inner-wrapper -->
<?php plogger_get_footer(); ?>