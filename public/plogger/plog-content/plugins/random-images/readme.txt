Plogger Random Images
-----------------------

About
------

This script allows you to include a specified number of random thumbnails from your Plogger gallery on your website, linking you directly to the large image of each of the thumbs.

Installation/Set-up
------------------

Upload the /random-images/ folder to your Plogger /plog-content/plugins/ folder.

To include random images in your website page, click "Use this plugin" and copy the php include shown. Place the php include in the page where you want to display the thumbnails.

The default number of images to display is 5. If you need to change the number of images, edit random-images.php and change the result for the variable $plog_random_images_amount to indicate the number of images you want displayed.

If you would like to display random images from a specific collection, edit random-images.php and make the following changes:
	- Comment out line 48 by placing two forward slashes (//) at the beginning of the line.
	- Remove the two forward slashes (//) from the beginning of line 51 and change "X" to the ID of the desired collection.

Additional notes
---------------

There is a css class assigned to the thumbnails (class="random-images-thumbnail") that is used to style the thumbs on the page where they are displayed.

You can add the following code to your website's stylesheet, if you would like to style the thumbnails, changing the colors, padding and border to match your website's theme.

.random-images-thumbnail {
	background-color: #eee;
	border: 2px solid #999;
	padding: 5px;
}

You can also style the caption underneath the thumbnails with the following:

.random-images-caption {
	text-align: center;
	font-size: 11px;
	margin-top: 5px;
	margin-bottom: 0;
}

License
-------

This plugin is released under the released under the GNU General Public License (GPL) license.
