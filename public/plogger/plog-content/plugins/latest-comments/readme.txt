Plogger Latest Comments
------------------------

About
------

This script allows you to include a specified number of the latest comments from your Plogger gallery on your website.

Installation/Set-up
------------------

Upload the /latest-comments/ folder to your Plogger /plog-content/plugins/ folder.

To include the latest comments in your website page, click "Use this plugin" and copy the php include shown. Place the php include in the page where you want to display the comments.

The default number of comments to display is 5. If you need to change the number of comments, edit latest-comments.php and change the result for the variable $plog_lc_amount to indicate the number of comments you want displayed.

Currently, the comments are truncated at 100 characters. If you would like to remove the truncation, edit latest-comments.php and change the result for $plog_lc_comment_trim to 0.

Additional notes
---------------

The latest comments are output as a list (<ul> <li>comment</li> </ul). There is a css class assigned to both the comments <ul> tag and <li> tag (class="latest-comments") that can be used to style the comments on the page where they are displayed.

You can add the following code to your website's stylesheet, if you would like to style your comments list, changing the colors, padding, etc. to match your website's theme.

ul.latest-comments {
	background-color: #f3f3f3;
	border: 1px solid #999;
	margin-top: 5px;
	padding: 10px 10px 0 10px;
	-moz-border-radius: 5px;
	-khtml-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}

li.latest-comments {
	list-style-type: none;
	list-style-position: inside;
	margin-bottom: 15px;
}

License
-------

This plugin is released under the released under the GNU General Public License (GPL) license.
