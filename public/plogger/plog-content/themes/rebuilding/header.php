<div id="containerOuter">
	    <div id="container">
		<img src="/images/painter.jpg" alt="House painter on ladder." style="float:left;" />
		<img src="/images/big-logo.png" alt="Rebuilding Together Portland logo." style="margin-right: 155px; margin-top: 5px;display:block;float:right;" />
		<div style="clear:both;font-size:0em;">&nbsp;</div>
		<div id="nav">
		    <a href="/">Home</a>
		    <a href="/about-us">About Us</a>
		    <a href="/volunteer">Volunteer</a>
				<a href="/house-captains">House Captains</a>
		    <a href="/homeowners">Homeowners</a>
		    <a href="/non-profits">Non-Profits</a>
		    <a href="/board-of-directors">Board of Directors</a>
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
	
