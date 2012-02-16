<?php echo plogger_download_selected_form_end(); ?>

	</div><!-- /main-container-->

	<div id="pagination"><?php echo plogger_pagination_control(); ?></div>

	<div id="footer">
		<div id="slideshow-link"><?php echo plogger_slideshow_link(); ?></div>
		<div id="sortby-container">
				<?php echo generate_jump_menu(); ?>
		</div><!-- /sortby-container -->
	</div><!-- /footer -->

<?php if ($GLOBALS['plogger_level'] == 'collections') { // display gallery stats only if at collection level ?>
	<div id="stats"><?php echo plog_tr('This gallery contains') ?> <?php echo plogger_stats_count_total_collections() ?>, <?php echo plogger_stats_count_total_albums() ?>, <?php echo plogger_stats_count_total_pictures() ?>, <?php echo plog_tr('and') ?> <?php echo plogger_stats_count_total_comments() ?>.</div>

<?php } ?>
	<div id="link-back"><?php echo plogger_link_back(); ?></div>

</div><!-- /plog-wrapper -->
