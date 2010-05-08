<?php plogger_get_header(); ?>

		<div id="thumbnail-container">
<?php if (plogger_has_pictures()) : ?>
			<div id="overlay">&nbsp;</div>
			<ul class="slides">
<?php while(plogger_has_pictures()) : plogger_load_picture();
	// Find thumbnail width/height
	$thumb_info = plogger_get_thumbnail_info();
	$thumb_width = $thumb_info['width']; // The width of the image. It is integer data type.
	$thumb_height = $thumb_info['height']; // The height of the image. It is an integer data type.
	$div_width = $thumb_width + 30; // Account for padding/border width
	$div_height = $thumb_height + 75; // Account for padding/border width
	// Generate XHTML with thumbnail and link to picture view
	$img_id = "thumb-".plogger_get_picture_id();
	$imgtag = '<img id="'.$img_id.'" onmouseout="document.getElementById(\'overlay\').style.visibility = \'hidden\';" onmouseover="display_overlay(\''.$img_id.'\', \''.plogger_picture_comment_count().'\')" class="photos" src="'.plogger_get_picture_thumb().'" width="'.$thumb_width.'" height="'.$thumb_height.'" title="'.plogger_get_picture_caption().'" alt="'.plogger_get_picture_caption().'" />';
?>
				<li class="thumbnail">
					<div class="tag" style="width: <?php echo $div_width; ?>px; height: <?php echo $div_height; ?>px;">
						<a href="<?php echo plogger_get_picture_url(); ?>"><?php echo $imgtag; ?></a><br />
						<span><?php echo plogger_get_picture_caption(); ?> <?php echo plogger_download_checkbox(plogger_get_picture_id()); ?></span>
					</div><!-- /tag -->
				</li><!-- /thumbnail -->
<?php endwhile; ?>
			</ul><!-- /slides -->
<?php else : ?>
			<div id="no-pictures-msg">
				<h2><?php echo plog_tr('Search Results') ?></h2>
				<p><?php echo plog_tr('Sorry, but there are no images that matched your search terms.') ?></p>
			</div><!-- /no-pictures-msg -->
<?php endif; ?>
		</div><!-- /thumbnail-container -->

<?php plogger_get_footer(); ?>