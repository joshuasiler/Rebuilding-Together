<?php plogger_get_header(); ?>

	<div id="big-picture-container">

<?php if (plogger_has_pictures()) : ?>
		<script type="text/javascript">
			<!--
			slides = new slideshow("slides");
			slides.prefetch = 2;
			slides.timeout = 4000;
			// slides.repeat = true;
			slides.redirect = "<?php echo plogger_slideshow_redirect(); ?>";
<?php while(plogger_has_pictures()) : $pic = plogger_load_picture(); ?>
			// Output a line of javascript for each image
			s = new slide("<?php echo plogger_get_picture_thumb(THUMB_LARGE); ?>",
			"<?php echo plogger_get_source_picture_url(); ?>",
			"<?php echo plogger_get_picture_caption('code'); ?>",
			"_self","","","<?php echo basename($pic['path']); ?>");
			slides.add_slide(s);
<?php endwhile; ?>
			// -->
		</script>

		<?php echo generate_slideshow_nav(); ?>

		<div id="picture-holder">
			<a href="javascript:slides.hotlink()"><img id="slideshow_image" class="photos-large" src="about:blank" title="<?php echo plogger_get_picture_caption(); ?>" alt="<?php echo plogger_get_picture_caption(); ?>" /></a>
		</div><!-- /picture-holder -->

		<script type="text/javascript">
			<!--
			if (document.images) {
				slides.set_image(document.images.slideshow_image);
				slides.textid = "picture_caption"; // optional
				slides.imagenameid = "image_name"; // optional
				slides.update();
				slides.play();
			}
			//-->
		</script>

<?php else : ?>
		<div id="no-pictures-msg">
			<h2><?php echo plog_tr('No Images') ?></h2>
			<p><?php echo plog_tr('Sorry, but there are no images in this album to create a slideshow with.') ?></p>
		</div><!-- /no-pictures-msg -->
<?php endif; ?>

	</div><!-- /big-picture-container -->
<?php plogger_get_footer(); ?>