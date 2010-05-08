<?php include('theme_functions.php'); ?>
<!--Output highest level container division-->
<div id="plog-wrapper">

	<div id="header">
		<div><?php echo generate_header(); ?></div>
			<div id="jump-search-container">
				<?php echo generate_search_box(); ?>
			</div><!-- /jump-search-container -->
	</div><!-- /header -->
	
	<div id="main-container">
<?php echo plogger_download_selected_form_start(); ?>
		<div id="breadcrumbs">
			<div id="download-selected"><?php echo plogger_print_button().plogger_download_selected_button(); ?></div>
			<?php echo generate_breadcrumb(); ?>

		</div><!-- end breadcrumbs -->
