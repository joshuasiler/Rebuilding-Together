	</div><!-- /main-container -->
<?php echo plogger_download_selected_form_end(); ?>

	<div id="pagination">
		<table id="pagination-table">
			<tr>
				<td><?php echo plogger_slideshow_link(); ?></td>
				<td><?php echo plogger_pagination_control(); ?></td>
				<td id="sortby-container"><?php echo plogger_sort_control(); ?></td>
				<td id="rss-tag-container"><?php echo plogger_rss_feed_button(); ?></td>
			</tr>
		</table><!-- /pagination-table -->
	</div><!-- /pagination -->

<?php if ($GLOBALS['plogger_level'] == 'collections') { // Display gallery stats only if at collection level ?>
	<div id="stats"><?php echo plog_tr('This gallery contains') ?> <?php echo plogger_stats_count_total_collections() ?>, <?php echo plogger_stats_count_total_albums() ?>, <?php echo plogger_stats_count_total_pictures() ?>, <?php echo plog_tr('and') ?> <?php echo plogger_stats_count_total_comments() ?>.</div>

<?php } ?>
	<div id="link-back"><?php echo plogger_link_back(); ?></div>

</div><!-- /plog-wrapper -->
	</div>
		<div id="footer">
		    Rebuilding Together Portland, &copy; 2010
		</div>
	    </div>
	</div>
	<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-12635422-1");
pageTracker._trackPageview();
} catch(err) {}</script>

  </body>
</html>
