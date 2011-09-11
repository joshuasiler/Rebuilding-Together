
	<div id="footer" class="clearfix">

<?php if (plogger_pagination_control() != '') { ?>
		<div id="pagination">
			<?php echo plogger_pagination_control(5); ?>
		</div><!-- /pagination -->
<?php } ?>
<?php if (plogger_download_selected_button() != '') { ?>
		<div id="download-selected"><?php echo plogger_download_selected_button(); ?></div><!-- /download-selected -->
<?php } ?>
<?php if (generate_jump_menu() != '') { ?>
		<div id="navigation-container">
				<?php echo generate_jump_menu(); ?>
		</div><!-- /navigation-container -->
<?php } ?>

<?php if (plogger_sort_control() != '') { ?>
		<div id="sort-control">
<?php echo plogger_sort_control(); ?>
		</div><!-- /sort-control -->
<?php } ?>

<?php if (plogger_rss_feed_button() != '') { ?>
		<div id="rss-tag-container"><?php echo plogger_rss_feed_button(); ?></div><!-- /rss-tag-container -->
<?php } ?>

		<div id="link-back"><?php echo plogger_link_back(); ?></div>
		<div class="credit"><a title="ardamis.com" href="http://www.ardamis.com/"><?php echo plog_tr('Design by') ?> ardamis.com</a></div><!-- /credit -->

	</div><!-- /footer clearfix -->
<?php echo plogger_download_selected_form_end(); ?>

</div><!-- /plog-wrapper -->
