<?php include('theme_functions.php'); ?>
<!--Output highest level container division-->
<div id="plog-wrapper">
	<table id="header-table">
		<tr>
			<td><?php echo generate_header(); ?></td>
			<td id="jump-search-container">
				<?php echo generate_jump_menu(); ?>
				<br />
				<?php echo generate_search_box(); ?>
			</td>
		</tr>
	</table><!-- /header-table -->
<?php echo plogger_download_selected_form_start(); ?>

	<div id="main-container">
		<div id="breadcrumbs">
			<table id="breadcrumb-table">
				<tr>
					<td><?php echo generate_breadcrumb(); ?></td>
					<td class="align-right"><?php echo plogger_download_selected_button(); ?><?php echo plogger_print_button(); ?></td>
				</tr>
			</table><!-- /breadcrumb-table -->
		</div><!-- /breadcrumbs -->
