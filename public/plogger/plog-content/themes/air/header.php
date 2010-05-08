<?php include('theme_functions.php'); ?>
<!--Output highest level container division-->
<div id="plog-wrapper">

	<div id="header">
		<?php echo generate_header(); ?>

		<div id="search-container">
				<?php echo generate_search_box(); ?>
		</div><!-- /search-container -->

		<div id="breadcrumbs">
			<div id="slideshow">
				<?php echo plogger_slideshow_link(); ?>
<?php echo plogger_print_button(); ?>

			</div><!-- /slideshow -->
			<?php echo generate_breadcrumb('Home', ' | '); ?>

		</div><!-- /breadcrumbs -->

	</div><!-- /header -->
	<?php echo plogger_download_selected_form_start(); ?>