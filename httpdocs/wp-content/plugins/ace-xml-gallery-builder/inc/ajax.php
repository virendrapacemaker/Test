<?php
/** 
 * This file contains all ajax actions
 * 
 * @package Ace Gallery
 * @subpackage Ajax
 * @author Marcel Brinkkemper
 * @copyright 2010-2012 Christopher
 * @access public
 * @since 1.1.0
 * 
 */
 
/**
 * bind ajax calls to functions 
 */

add_action( 'wp_ajax_nopriv_ace_swfupload', 'ace_swfupload' );  
add_action( 'wp_ajax_ace_swfupload', 'ace_swfupload' ); 
add_action( 'wp_ajax_ace_admin_list_folders', 'ace_admin_list_folders' );
add_action( 'wp_ajax_ace_admin_contextmenu', 'ace_admin_contextmenu' );
add_action( 'wp_ajax_ace_folder_subcount', 'ace_folder_subcount' );
add_action( 'wp_ajax_nopriv_ace_folder_subcount', 'ace_folder_subcount' );
add_action( 'wp_ajax_ace_insert_folder_shortcode', 'ace_insert_folder_shortcode' );
add_action( 'wp_ajax_ace_upload_showfolder', 'ace_upload_showfolder' );
add_action( 'wp_ajax_ace_insert_image_shortcode', 'ace_insert_image_shortcode' );
add_action( 'wp_ajax_ace_folder_newfolder', 'ace_folder_newfolder' );
add_action( 'wp_ajax_ace_clear_thumbs', 'ace_clear_thumbs' );
add_action( 'wp_ajax_ace_add_user', 'ace_add_user' );
add_action( 'wp_ajax_ace_remove_user', 'ace_remove_user' );
add_action( 'wp_ajax_ace_set_viewer_level', 'ace_set_viewer_level' );
add_action( 'wp_ajax_ace_add_fauthor', 'ace_add_fauthor' );
add_action( 'wp_ajax_ace_remove_fauthor', 'ace_remove_fauthor' );
add_action( 'wp_ajax_ace_rebuild_cache', 'ace_rebuild_cache' );
add_action( 'wp_ajax_ace_next_dirs', 'ace_next_dirs' );
add_action( 'wp_ajax_nopriv_ace_next_dirs', 'ace_next_dirs' );
add_action( 'wp_ajax_ace_next_thumbs', 'ace_next_thumbs' );
add_action( 'wp_ajax_nopriv_ace_next_thumbs', 'ace_next_thumbs' );
add_action( 'wp_ajax_ace_truncate_table', 'ace_truncate_table' );
add_action( 'wp_ajax_ace_rebuild_database', 'ace_rebuild_database' );
add_action( 'wp_ajax_ace_image_request', 'ace_image_request' );
add_action( 'wp_ajax_nopriv_ace_image_request', 'ace_image_request' );
add_action( 'wp_ajax_ace_refresh_folder', 'ace_refresh_folder' );
add_action( 'wp_ajax_ace_media', 'ace_media' );

/**
 * ace_admin_list_folders()
 * Displays an unordered list of folders and subfolders
 * 
 * @since 1.1.0
 * @return void
 */
function ace_admin_list_folders() {
  global $ace_gallery;
  if ( isset( $_POST['folder'] ) ) {
    $folder = new AceFolder( urldecode( $_POST['folder'] ) );
    if ( $folder->valid() ) {
      $folder->list_folders( 'hidden', 'dirname', 'admin' );
      die();
    }
  }
  echo ' ';
  die();
}

/**
 * ace_admin_contextmenu()
 * Displays a context menu for folder to move or copy an image into
 * 
 * @since 1.1.0
 * @return void
 */
function ace_admin_contextmenu() {
  global $ace_gallery;
  $result =' ';
  if ( isset( $_POST['folder'] ) ) {
    $manage = new AceFolder( urldecode( $_POST['folder'] ) );    
    $folders = $ace_gallery->folders( 'subfolders', 'hidden' );
    $count = 0;
    if ( 0 < count( $folders ) ) {
      foreach ( $folders as $folder ) {
        if ( $folder->curdir != $manage->curdir ) {
          $result .= sprintf('<li class="folderpng"><a href="#%s">%s</a></li>',
            urlencode( $folder->curdir ), 
            htmlentities( $folder->curdir ) );
          $count++;  
        } 
      }   
    }
  }
  echo ( 0 < $count ) ? $result : 'none';
  die();
}

/**
 * ace_folder_subcount()
 * Display the number of images in subfolders
 * 
 * @since 1.1.0
 * @return void
 */
function ace_folder_subcount() {
  global $ace_gallery;
  $result = ' ';  
  if ( isset( $_POST['folder'] ) ) {
    $subcount = $allcount = 0;
    if ( '' != $_POST['folder'] ) { // get # of images in subfolders of folder
      $folder = new AceFolder( urldecode( $_POST['folder'] ) );
      $count = (int)$folder->count();
      $allcount = (int)$folder->count( 'subfolders' );
      $subcount = $allcount - $count;
      $allcount = ( 'separate' == $ace_gallery->get_option( 'count_subfolders' ) || 'none' == $ace_gallery->get_option( 'count_subfolders' )  ) ? $count : $allcount;
    } else { // get # of images in gallery
      $folders = $ace_gallery->folders( 'root', 'hidden' );
      for ( $i = 0; $i != count( $folders ); $i++ ) {
  		  $folder = $folders[$i];
  			$subcount += $folder->count( 'subfolders' );
  		} 
    }     
    if ( ! isset( $_POST['allcount'] ) ) {          
      if ( 0 < $subcount ) {
        $result .= sprintf( esc_html__( '%s in folders', 'ace-xml-gallery-builder' ), strval( $subcount ) );
      } else {
        if ( '' == $_POST['folder'] )
          $result .= sprintf( esc_html__( '%s in folders', 'ace-xml-gallery-builder' ), strval( $subcount ) ); 
      }        
    } else {
      $result .= sprintf( '%s %s', $allcount, $ace_gallery->get_option( 'listed_as' ) );
    }
  }
  echo $result;
  die();
}

/**
 * ace_insert_folder_shortcode()
 * Insert a folder shortcode in a post
 * 
 * @since 1.1.0
 * @return void
 */
function ace_insert_folder_shortcode() {
  global $ace_gallery;
  if ( isset( $_POST['folder'] ) ) {
    $file = urldecode( $_POST['folder'] );    
    $folder = new AceFolder( $file ); 
    if ( $folder->valid() ) { 
      require_once ( $ace_gallery->plugin_dir . '/inc/uploadtab.php' );
      $uploadtab = new AceUploadTab();
      $uploadtab->insert_folder_shortcode( $folder );
    }  
  }
  die();
}

/**
 * ace_upload_showfolder()
 * Show folder contents in upload tabs
 * 
 * @since 1.1.0
 * @return void
 */
function ace_upload_showfolder() {
  global $ace_gallery;
  if ( isset( $_POST['folder'] ) ) {        
    $current_url = $_POST['current_url'];
    $query = substr( $current_url, strpos( $current_url, '?' ) + 1 ); 
    wp_parse_str( $query, $qs );
    $_REQUEST['post_id'] = isset( $qs['post_id'] ) ? $qs['post_id'] : 0; 
    $file = urldecode( $_POST['folder'] );    
    $folder = new AceFolder( $file ); 
    if ( $folder->valid() ) { 
      require_once ( $ace_gallery->plugin_dir . '/inc/uploadtab.php' );
      $uploadtab = new AceUploadTab();
      $uploadtab->show_folder( $folder, $current_url );
    }  
  }
  die();
}

/**
 * ace_insert_image_shortcode()
 * Insert an image shortcode in a post
 * 
 * @since 1.1.0
 * @return void
 */
function ace_insert_image_shortcode() {
  global $ace_gallery;
  if ( isset( $_POST['image'] ) ) {
    $file = urldecode( $_POST['image'] );    
    $folder = new AceFolder( dirname( $file ) );     
    if ( $folder->valid() ) { 
      $image = $folder->single_image( basename( $file ), 'thumbs' );    
      require_once ( $ace_gallery->plugin_dir . '/inc/uploadtab.php' );
      $uploadtab = new AceUploadTab();
      $uploadtab->insert_image_shortcode( $image );
    }  
  }
  die();
}

/**
 * ace_folder_newfolder()
 * Insert a new folder in the gallery
 * 
 * @since 1.1.0
 * @return void
 */
function ace_folder_newfolder() {
  global $ace_gallery;
  
  $nonce = $_POST['_wpnonce'];
  $from_gallery = wp_verify_nonce( $nonce, 'ace_manage_gallery' );
  $from_folder =  wp_verify_nonce( $nonce, 'ace_manage_folder' );
  if ( ! ( $from_gallery || $from_folder ) )
  	die();  		
  if ( isset( $_POST['create_new_folder'] ) ) {    
    if ( isset( $_POST['folder'] ) ) {
      $id = $_POST['folder'];
      $newname = $_POST['new_folder_name'];
      if ( '0' != $id ) { 
      	$file = trailingslashit( utf8_decode( stripslashes( rawurldecode( $_POST['directory'] ) ) ) );
        $_POST['folder'] = $_POST['directory'];
        include_once( $ace_gallery->plugin_dir . '/inc/manager.php' );
        $parentfolder = new AceAdminFolder( $file );
        if ( $parentfolder->valid() ) {
          $parentfolder->open();
          $foldername = $parentfolder->curdir . $newname;    
        }
      } else {
        unset( $_POST['folder'] );
        $foldername = $newname;
      } 
    }  
    $message = sprintf( 'Folder %s cannot be opened.', htmlentities( $newname ) );
    trailingslashit( $newname );               
    $result = $ace_gallery->new_gallery_folder( $foldername );
    
    if ( true === $result ) {
      $i = 0;
      $found = false;
      $folders = ( '0' != $id ) ? $parentfolder->subfolders() : $ace_gallery->folders( 'root', 'hidden' );  
      while ( ! $found || $i > count( $folders ) ) { // find pagination information
        $folder = $folders[$i];
        $found = $newname; $folder->dirname();
        $i++; 
      }      
      if ( $found ) {
        $page = ceil( 20 / $i );
        $_POST['ace_paged'] = $page; // set pagination request
        $action = ( '0' != $id ) ? $parentfolder->foldersbox() : $ace_gallery->foldersbox( $folders );
      } else {
        $ace_gallery->message = $message;
        $ace_gallery->success = false;
      	$ace_gallery->options_message();
      }  
    } else {      
      $ace_gallery->message = $result;
      $ace_gallery->success = false;
      $ace_gallery->options_message();
    }
  }
  die();
}  

/**
 * ace_clear_thumbs()
 * Delete cache
 * 
 * @since 1.1.0
 * @return void
 */
function ace_clear_thumbs() {
  global $ace_gallery;
  if ( isset( $_POST['folder'] ) ) {
    $file = $file = trailingslashit( utf8_decode( stripslashes( rawurldecode( $_POST['directory'] ) ) ) );
    $_POST['folder'] = $_POST['directory'];    
    include_once( $ace_gallery->plugin_dir . '/inc/manager.php' );    
    $folder = new AceAdminFolder( $file );    
    if ( $folder->valid() ) {
      // $folder->delete_file();  
    } 
  }
  die();
}

/**
 * ace_add_user()
 * Add a user with role to the gallery
 * 
 * @since 1.1.0
 * @return
 */
function ace_add_user() {
  global $ace_gallery;
  $ace_gallery->add_user( $_POST['id'], $_POST['type'] );
  echo 'true';
  die();
}

/**
 * ace_remove_user()
 * Remove a user with role from the gallery
 * 
 * @since 1.1.0
 * @return void
 */
function ace_remove_user() {
  global $ace_gallery;
  $ace_gallery->remove_user( $_POST['id'], $_POST['type'] );
  echo 'true';
  die();
}

/**
 * ace_set_viewer_level()
 * Remove a user with role from the gallery
 * 
 * @since 1.1.0
 * @return void
 */
function ace_set_viewer_level() {
  global $ace_gallery;
  $ace_gallery->set_viewer_level();
  echo 'true';
  die();
}

/**
 * ace_media_upload()
 * Show ace-xml-gallery-builder upload window
 * 
 * @return void
 * @since 1.1.3
 */
function ace_media() {
	global $ace_gallery; 
	
	check_ajax_referer();
	
	$folder = isset( $_REQUEST['folder' ] ) ? utf8_decode( stripslashes( rawurldecode( $_GET['folder'] ) ) ) : '';	
	
	if ( '' == $folder )
		die( __( 'Cannot upload images to no folder', 'ace-xml-gallery-builder' ) );
		
	require_once( $ace_gallery->plugin_dir . '/inc/manager.php' );
	$ace_admin_folder = new AceAdminFolder( $folder );
	$ace_admin_folder->open();
		
	if (  'TRUE' == $ace_gallery->get_option( 'flash_upload' ) )  {
		$j = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'dev.js' : 'js';
	 	wp_register_script( 'ace_swfupload-handlers', $ace_gallery->plugin_url . "/js/ace-swf-handler.$j", array('jquery'), '1.1', false );
	  wp_enqueue_script('swfupload-all');
	  wp_enqueue_script( 'ace_swfupload-handlers' );
	  wp_localize_script( 'ace_swfupload-handlers', 'ace_swfuploadL10n', $ace_gallery->localize_swf() );
	  $c = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'dev.css' : 'ccs';
	    wp_register_style( 'ace_admin_style', $ace_gallery->plugin_url . "/css/_admin.$c" );
	  $ace_gallery->update_option( 'flash_upload', 'TRUE' );
	}  
	if ( isset($_GET['flash'] ) ) {     
	  if ( '0' == $_GET['flash'] ) { 
	    $ace_gallery->update_option( 'flash_upload', 'FALSE' );
	  }
	}
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
	<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<meta http-equiv="X-UA-Compatible" content="IE=8" />
	<title><?php bloginfo('name') ?> &rsaquo; <?php esc_html_e('Uploads'); ?> &#8212; <?php esc_html_e('Ace Gallery', 'ace-xml-gallery-builder'); ?></title>
	<?php
	wp_enqueue_style( 'global' );
	wp_enqueue_style( 'wp-admin' );
	wp_enqueue_style( 'colors' );
	wp_enqueue_style( 'media' );
	wp_enqueue_style( 'ie' );
	wp_enqueue_style( 'ace_admin_style' );
	?>
	<script type="text/javascript">
	//<![CDATA[
	addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
	var userSettings = {'url':'<?php echo SITECOOKIEPATH; ?>','uid':'<?php if ( ! isset($current_user) ) $current_user = wp_get_current_user(); echo $current_user->ID; ?>','time':'<?php echo time(); ?>'};
	var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>', pagenow = 'media-upload-popup', adminpage = 'media-upload-popup',
	isRtl = <?php echo (int) is_rtl(); ?>;
	//]]>
	</script>
	<?php
	do_action('admin_print_styles-media-upload-popup');
	do_action('admin_print_styles');
	do_action('admin_print_scripts');
	do_action('admin_head-media-upload-popup');
	do_action('admin_head');
	
	?>
	</head>
	<body id="media-upload" class="no-js">
	<script type="text/javascript">
	//<![CDATA[
	(function(){
	var c = document.body.className;
	c = c.replace(/no-js/, 'js');
	document.body.className = c;
	})();
	
	
	//]]>
	</script>
	<div id="media-upload-header">
		<ul id="sidemenu">
		<li id="tab-type"><a href="#" class="current"><?php esc_html_e( 'From Computer', 'ace-xml-gallery-builder' ); ?></a></li>	
		</ul>
	</div>
	<?php if ( isset( $_REQUEST['html-upload'] ) ) $ace_admin_folder->uploadfiles(); ?>
	<?php if ( $ace_admin_folder->user_can( 'editor' ) ) $ace_admin_folder->uploadbox(); ?>
	<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>	
	</body>
	</html>
<?php
	die();
}

/**
 * ace_add_fauthor()
 * Add an autor to a folder
 * 
 * @since 1.1.0
 * @return void
 */
function ace_add_fauthor() {
  global $ace_gallery;  
  if ( isset( $_POST['folder'] ) ) {
    include_once( $ace_gallery->plugin_dir . '/inc/manager.php' );
    $folder =  new AceAdminFolder( $_POST['folder'] );
    if ( $folder->valid() ) {
      $folder->open();
      $result = $folder->set_author( $_POST['id'] );
      if ( $result ) {
        $folder->change();
        echo 'true';
        die();
      }
    }    
  }
  echo 'false';
  die();
}

/**
 * ace_remove_fauthor()
 * Remove an autor from a folder
 * 
 * @since 1.1.0
 * @return void
 */
function ace_remove_fauthor() {
  global $ace_gallery;  
  if ( isset( $_POST['folder'] ) ) {
    include_once( $ace_gallery->plugin_dir . '/inc/manager.php' );
    $folder =  new AceAdminFolder( $_POST['folder'] );
    if ( $folder->valid() ) {
      $folder->open();
      $result = $folder->unset_author( $_POST['id'] );
      if ( $result ) {
        $folder->change();
        echo 'true';
        die();
      }
    }    
  }
  echo 'false';
  die();
}

/**
 * ace_rebuild_cache()
 * Rebuilds the cache for 1 folder and returns the number of folders to go.
 * 
 * @since 1.1.0
 * @return void
 */
function ace_rebuild_cache() {
  global $ace_gallery;
  if ( isset( $_POST['folder'] ) ) {
    $count = $ace_gallery->rebuild_cache( $_POST['folder'], $_POST['image'] );    
    echo $count['folder'].','.$count['image'];
  }
  die();
}


/**
 * ace_next_dirs()
 * Shows next page of folders
 * 
 * @since 1.1.0
 * @return void
 */
function ace_next_dirs() {
  global $ace_gallery;
	if ( 'TRUE' != $ace_gallery->get_option( 'external_request' ) ) {
		check_ajax_referer( 'show_dirs', 'ajax_nonce' );
	}	
	wp_set_current_user( $_POST['user_id'] );
  $ace_plugin_dir = $ace_gallery->plugin_dir;
	define( 'ACE_FRONTEND', true ); 
  $_SERVER['REQUEST_URI'] = $_POST['request_uri'];
  require_once( $ace_plugin_dir . '/inc/frontend.php' );
  $ace_gallery = new AceFrontend(); 
	if ( '' != $_POST['virtual'] )
		$ace_gallery->set_root( urldecode( $_POST['virtual'] ) );
  $path = urldecode( $_POST['folder'] );  
  $ace_pagei = isset( $_POST['ace_paged'] ) ? $_POST['ace_paged'] : 1; 
  $folder = ( $path != '') ? $folder = new AceFolder( $path ) : null;
  if ( !is_null( $folder ) )
		$folder->open();  
 	$ace_gallery->show_dirs( $folder, (int)$_POST['perpage'], (int)$_POST['columns']  );
  die();
}

/**
 * ace_next_thumbs()
 * Shows next page of image thumbnails
 * 
 * @since 1.1.0
 * @return void
 */
function ace_next_thumbs() {
	global $ace_gallery, $post;
	if ( 'TRUE' != $ace_gallery->get_option( 'external_request' ) ) {
		check_ajax_referer( 'show_thumbs', 'ajax_nonce' );
	}
  $ace_plugin_dir = $ace_gallery->plugin_dir;
	define( 'ACE_FRONTEND', true );	
  $_SERVER['REQUEST_URI'] = $_POST['request_uri'];
  require_once( $ace_plugin_dir . '/inc/frontend.php' );
  $ace_gallery = new AceFrontend();   
	if ( '' != $_POST['virtual'] )
		$ace_gallery->set_root( urldecode( $_POST['virtual'] ) );	
  $start = 1; 
  $ace_pagei = isset( $_POST['ace_pagei'] ) ? $_POST['ace_pagei'] : 1; 
  if ( isset( $_POST['folder'] ) ){ 
    $path = urldecode( $_POST['folder'] );
    $folder = new AceFrontendFolder( $path );
		$folder->load( 'thumbs' );
		$post = get_post( intval( $_POST['post_id'] ) );
    $folder->show_thumbs( (int)$_POST['perpage'], (int)$_POST['columns'], true );   
  } else {
    echo -1;    
  }  
  die();
}

/**
 * ace_truncate_table()
 * empty the wp_acefiles table
 * 
 * @since 1.1.0
 * @return void
 */
function ace_truncate_table() {
  global $ace_gallery;
  echo $ace_gallery->truncate_table() ? '1' : '0';
  die();
}


/**
 * ace_rebuild_database()
 * Rebuilds the table for 1 folder and returns the number of folders to go.
 * @since 1.1.0
 * @return void
 */
function ace_rebuild_database() {
  global $ace_gallery;
  if ( isset( $_POST['folder'] ) ) {
    $count = $ace_gallery->rebuild_database( $_POST['folder'] );
    echo $count;
  } 
  die();
}

/**
 * ace_image_request()
 * Used to asynchronously create images
 * If resizing fails, an error image /images/file_alert.png will be returned
 * Sends header 304 Not Modified if image in browser cache 
 * 
 * @since 1.1.0
 * @return void
 */
function ace_image_request() {	
  global $ace_gallery;
  $this_file = '';
  if ( isset( $_GET['file'] ) ) {   
    $this_file = utf8_decode( stripslashes( rawurldecode( $_GET['file'] ) ) ); 
  }   
    
	$original_file = $ace_gallery-> root . $this_file; 
  if ( ( '' == $this_file ) || ! file_exists( $original_file ) ) {
  	header('HTTP/1.1 404 Not Found');	
		esc_html_e( 'Illegal image request', 'ace-xml-gallery-builder' );
		die();	
	}
	
	$path = pathinfo( $this_file ); 
	 
	$thumb = isset( $_REQUEST['thumb'] ) && ( $_REQUEST['thumb'] == 1 );
	$cache = ( ( $thumb && ( 'TRUE' == $ace_gallery->get_option( 'enable_cache' ) ) ) || ( ! $thumb && ( 'TRUE' == $ace_gallery->get_option( 'enable_slides_cache' ) ) ) );
	
	$cache_dir = $thumb ? $ace_gallery->get_option( 'thumb_folder' ) : $ace_gallery->get_option( 'slide_folder' );
	$cached_file = $ace_gallery-> root . trailingslashit( $path['dirname'] ) . $cache_dir . $path['basename'];
	
	// send 304 response if file has not been changed
	if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
		if ( $cache && is_file( $cached_file ) ) { 
			if ( strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) == filemtime( $cached_file ) )  {
	  		@header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', filemtime( $cached_file ) ) . ' GMT', true, 304 );
	  		die();
			}	
		}		
		if ( ! $cache ) {
			if ( strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) == filemtime( $original_file ) ) {
	  		@header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', filemtime( $original_file ) ) . ' GMT', true, 304 );
	  		die();				
			}
		}		
	}
	 
	switch( strtolower( $path[ 'extension' ] ) ) {   
		case 'jpeg':
		case 'jpg':
			header( 'Content-type: image/jpeg' );
			break;
		case 'gif':
			@header( 'Content-type: image/gif' );
			break;
		case 'png':
			@header( 'Content-type: image/png' );
			break;
  }
	
	// pass through file if cached file already exists
	if ( is_file( $cached_file ) ) {
		@header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', filemtime( $cached_file ) ) . ' GMT');
		@readfile( $cached_file );
		die();
	} 
	  
  $folder = new AceFolder( $path['dirname'] );
  $image = ( $thumb ) ? new AceThumb( $folder ) : new AceSlide( $folder );
  $image->image = $path['basename'];
  if( $thumb ) {
		$height = $ace_gallery->get_option( 'thumbheight' );
		$width = $ace_gallery->get_option( 'thumbwidth' );
	}
	else {
		$height = $ace_gallery->get_option( 'pictheight' );
		$width = $ace_gallery->get_option( 'pictwidth' );
	}
  
  if ( $cache ) { 
    $memok = $image->cache();
    @header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', filemtime( $cached_file ) ) . ' GMT');
  } else {		
    $memok = $image->newsize( $width, $height );     
    @header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', filemtime( $original_file ) ) . ' GMT');
  } 
  if ( ! $memok ) {    
    $resized = imagecreatefrompng( $ace_gallery->plugin_dir . '/images/file_alert.png' ); 
  } else {
  	$resized = &$image->resized;
  }	
  if ( is_resource( $resized ) ) {
    switch( strtolower( $path[ 'extension' ] ) ) {   
  		case 'jpeg':
  		case 'jpg':
  		  imagejpeg( $resized );
  			break;
  		case 'gif':
      	imagegif( $resized );
  			break;
  		case 'png':
      	imagepng( $resized );
  			break;
  		default:
  			break;
  	}  
    imagedestroy( $resized );
  }  	
  die();
}

/**
 * ace_refresh_folder()
 * refresh image table in folder edit screen when upload thickbox closes
 * @since 1.1.0
 * @return void
 */
function ace_refresh_folder() {
	global $ace_paged, $ace_gallery;
	if ( isset( $_POST['folder'] ) ) {
		$ace_paged = isset( $_POST['ace_paged'] ) ? $_POST['ace_paged'] : 1;		
		$path = utf8_decode( rawurldecode( $_POST['folder'] ) );
		$folder = new AceFolder( $path );
		$folder->open();
		$folder->load( 'thumbs' );		
    $imagetable = new AceImageTable( $folder->list );
    $imagetable->page( 'ace_paged' );
    $imagetable->display();   
	}
	die();
}

/**
 * ace_swfupload()
 * 
 * used in async upload by flash uploader
 * @param string $path
 * @return string
 * @since 1.0
 */
function ace_swfupload() {
  global $ace_gallery, $file;
  require_once( dirname( __FILE__ ) . '/manager.php' );
	header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
	wp_set_current_user( $_REQUEST['uid'] );
	check_ajax_referer();
	// set the gallery folder
	$file = stripslashes( utf8_decode( rawurldecode( $_POST['file'] ) ) );
	if ( $file == '' ) {		
		esc_html_e( 'No folder to store the image' , 'ace-xml-gallery-builder' );
	}	 
  $folder = new AceAdminFolder( $file );
  
  $message = $folder->swfuploadfiles();
  unset( $folder );
  echo $message;
  die();
}	
  
?>