<div id="containerOuter">
	    <div id="container">
		<img src="/images/painter.jpg" alt="House painter on ladder." style="float:left;" />
		<img src="/images/big-logo.png" alt="Rebuilding Together Portland logo." style="margin-right: 155px; margin-top: 5px;display:block;float:right;" />
		<div style="clear:both;font-size:0em;">&nbsp;</div>
		<div id="nav">
		    <a href="/">Home</a>
		    <a href="/r/about_page">About Us</a>
		    <a href="/contacts/new">Volunteer</a>
				<a href="/r/house_captains">House Captains</a>
		    <a href=/r/homeowners_page>Homeowners</a>
		    <a href="/r/nonprofits_page">Non-Profits</a>
		    <a href="/r/board_page">Board of Directors</a>
		    <a href="/plogger/index.php">Project Photos</a>
		</div>
		<div id="content">
		    
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
	
