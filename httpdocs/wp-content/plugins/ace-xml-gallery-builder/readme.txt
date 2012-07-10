=== Ace Gallery ===
Contributors: macbrink
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1257529
Tags: photo,album,picture,ace,image,gallery,easy,thumbs,slides,exif,popup,folders,lightbox,widget,ftp-upload,schortcode,comment,image,gallery
Tested up to: 3.4
Requires at least: 3.2
Stable tag: 1.1.12

Maybe the easiest Gallery plugin for WordPress

== Description ==

Create a photo gallery from your existing photo directories 

This gallery basically needs just two settings: Your image directory and your gallery page. Ace Gallery automatically creates a photo gallery with folders, sub folders, thumbnail pages and slide shows.

If you want more, the gallery offers a multitude of options by featuring a smart back end management site. You can sort photos through folders and add titles, comments and descriptions with minimal effort. If you are tired of uploading photos through the WordPress server, this plug-in will make it a breeze with their FTP auto-indexing integration.

Read the user guide on the [Ace Gallery website](http://askchrisedwards.com/ace/gallery/user-guide/)

For more information about version 1.1.12, read the [release notes](http://askchrisedwards.com/2012/06/13/ace-xml-gallery-builder-1-1-12/)

Read all about the [version 1.1 series](http://askchrisedwards.com/tag/1-1/)

= Ace Gallery features: =

* Fully integrated in WordPress with Settings and Management pages
* Unlimited number of images in unlimited number of nested folders
* Automatic thumbnail and slide creation
* Add Titles, Descriptions and Custom fields to Folders and Images
* Comment on Images and Folders
* [Shortcodes](http://askchrisedwards.com/ace/gallery/user-guide/shortcodes/) for Galleries, Folders, Slideshows and Images
* Arrange your folders and images by date, alphabetically or manually
* Widgets for Random Images, Slide Show, and Folder List
* WordPress Post Edit Screen upload button integration
* Translations for Dutch, Russian and German
* Expandable by [plugins](http://askchrisedwards.com/ace/)

= Add on plugins: =

* Get an alternative way to show your Slides with the [Ace Slides plugin](http://wordpress.org/extend/plugins/ace-slides/)
* Add more widgets with the [Ace Widgets plugin](http://wordpress.org/extend/plugins/ace-widgets/)
* Backup your Gallery with the [Ace Backup plugin](http://wordpress.org/extend/plugins/ace-backup/)
* Add Beautiful Stacked Gallery effects with the [Ace Stack plugin](http://wordpress.org/extend/plugins/ace-stack/)
* Watermark your images with the [Ace Watermark plugin](http://wordpress.org/extend/plugins/ace-watermark/)

For latest information please check [the blog on the author web site](http://askchrisedwards.com/posts/)

For help please check [the plugin web site](http://askchrisedwards.com/ace/gallery/)

== Upgrade Notice ==

= 1.1.12 =
* Version 1.1.12 is ready for WordPress 3.4

== Changelog ==

= 1.1.12 =
* Bug Fix: Correctly display Admin screens
* Bug Fix: Hide Ace Gallery menu after restting options

= 1.1.11 =
* Bug fix: Manual sort does not work after upload
* Bug fix: Navigator invisible for Slide View
* Changed: use folder name as class for thumbs view element
* Bug fix: Admin screens lay out for WordPress 3.4

= 1.1.10.1 =
* Bug fix: Folder image count reset to 0

= 1.1.10 =
* Changed: Styling of [ace_image] shortcode by height, width and class
* Bug fix: Empty class attribute in folder image count
* Changed: Add extra fields in folder thumbnails view
* Bug fix: Incorrect comments edit url on manager screen
* Changed: removed function check_users() to improve performance 
* Bug fix: backslash in images url on Windows-based servers
* Bug fix: Upload failed on Internet Explorer 9
* Changed: Refresh folder display after each upload
* Bug fix: Refresh folder for empty folders
* Changed: Use file date when image date value is empty
* Bug fix: Prevent division by zero in image shortcode

= 1.1.9.1 =
* Bug fix: Image file validation

= 1.1.9 =
* Bug fix: Gallery user role management
* Changed: Do not show manage link on plugins page without valid settings
* Bug fix: Save Gallery path using DIRECTORY_SEPARATOR
* Bug fix: Resolve gallery address when DIRECTORY_SEPARATOR  is \
* Bug fix: Resolve gallery address for WordPress in subdirectory
* Bug fix: Decode url and utf8 encoded folder names in widgets
* Changed: AJAX image request memory and headers
* Changed: Use browser cache for AJAX generated slides and thumbnails
* Bug fix: Genesis framework Doctitle
* Changed: Extra validation for image uploads
* Bug Fix: Fatal error: Call to undefined function get_users() at install

= 1.1.8.1 =
* Bug fix: Permalinks in rewrite rules

= 1.1.8 =
* Bug fix: Directory not found for UNC path
* Bug fix: Escaping url could delete %20 spaces in folders
* Bug fix: Rewrite rules
* Security fix: Potential security risk in Admin for invalid folder names

= 1.1.7.1 =
* Bug fix: Incorrectly compacted javascript blocked image description editing

= 1.1.7 =
* Bug fix: div element does not close when folder has only one image
* Bug fix: Number of comments of posts in search were incorrect
* Bug fix: New subfolder got misplaced on duplicate IDs
* Bug fix: Warning on cropping image/png files

= 1.1.6 =
* Bug Fix: Display of full size images in thumbnail view
* Changed: Cropping the top for portrait oriented images
* Bug Fix: Empty rel tag for lightbox links stops prev - next functionality
* Bug Fix: No permalinks for links in gallery shortcode in posts
* Bug Fix: Locked folders when viewer level has not yet been set

= 1.1.5 =
* Bug Fix: Display of utf8 encoded folders names in widgets
* Bug fix: Error on accept renamed duplicate file names
* Added: Filter in image manager for image specific actions 

= 1.1.4 =
* Bug Fix: Cannot open utf8 encoded folder names
* Bug Fix: Undefined variable in comments.php
* Bug Fix: Cannot insert shortcode in page

= 1.1.3.3 =
* Added: Javascript reset of Lightbox plugins on refresh
* Bug Fix: Incorrect usage of onclick['title']
* Changed: Styling of upload progress bar for WordPress 3.3 

= 1.1.3.2 =
* Bug Fix: Browser uploader returns 'Security check on file upload
* Changed: small improvement for canonical url

= 1.1.3.1 =
* Bug Fix: Browser uploader returns -1 on fille upload

= 1.1.3 =
* Bug Fix: Insert an incomplete shortcode
* Changed: Use admin-ajax.php instead of media-upload.php for image uploader
* Bug Fix: Refresh folder after uploading image(s)
* Bug fix: jQuery conflict on pages where slideshow does not display
* Changed: Adjust width of thumbnail images to column settings

= 1.1.2.1 =
* Bug Fix: Incorrect fields setting for comments
* Bug fix for backlink in shortcodes with root option

= 1.1.1.1 =
* Bug Fix: Manually sorted images did not save sort order.

= 1.1.1 =
* Bug Fix: Overlapping Folders table in Admin
* Bug Fix: Fatal Error on non-existing image in shortcode
* Bug Fix: Create Folder in manually sorted folders
* Bug Fix: Drag and drop sorting
* Bug Fix: Sorting Folders and Images separately
* Bug Fix: Sorting Titles by clicking on table header
* Changed: Translatable strings
* Added: Filters and Actions for plugin developers

= 1.1 =
* Added: Pluggable filters and actions
* Added: User defined fields for folders and images
* Added: Themes for Ace Gallery
* Added: User authorization for upload, editing, and gallery and folder management
* Added: New pagination for Gallery and Admin
* Added: Ajax in frontend and backend
* Added: Upload tab integration
* Changed: Table layout is deprecated
* Improved: Comments loading time

Full changelog on the [Ace Gallery website](http://askchrisedwards.com/ace/gallery/developing/)

== Installation ==

1. Install the plugin by using your Admin Plugin page
2. Activate the plugin
3. Go to Settings -> Ace Gallery
4. Enter your Pictures directory and your Gallery page
5. Save Changes

For detailed installation instructions please go to [the plugin installation site](http://askchrisedwards.com/ace/installation/)

== Screenshots == 

= Version 1.1 screen shots =
For screenshots please visit the [Version 1.1 Gallery](http://askchrisedwards.com/gallery/version_1.1/)

== Frequently Asked Questions ==

Please check the [plugin web site](http://askchrisedwards.com/ace/gallery/frequently-asked-questions/) for Frequently Asked Questions

== Uninstall ==

1. Deactivate plugin from your WordPress Admin Plugins page
2. Delete the plugin from your WordPress Admin Plugins page
3. The thumbs and slides folders in your gallery will be removed automatically, along with the images.xml files. All comments will be reset to the gallery page. You cannot undo comments after uninstall. 

== License ==

* Copyright (c) 2004 - Nicholas Bruun Jespersen
* Copyright (c) 2005 - 2006 Valerio Chiodino
* Copyright (c) 2008-2011 - Marcel Brinkkemper 

* jQuery Context Menu,  Copyright (c) 2008 A Beautiful Site, LLC.
* TableDnD plug-in for JQuery,  Copyright (c) Denis Howlett
* jQuery Context Menu Plugin,  Copyright (c) Cory S.N. LaViska
* jQuery Progress Bar plugin,  Copyright (c) 2008 Gary Teo

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA