<?php
/*
Plugin Name: ACE XML Gallery Builder
Plugin URI: http://askchrisedwards.com/
Description: This plugin generates an XML file for multiple posts from your Wordpress blog. You can use the generated XML files to populate photo gallery software, iPhone applications, etc.
Date: 2012 June
Author: Christopher
Author URI: http://askchrisedwards.com
Version: 1.1.12
License: GNU GPLv2
Text Domain: ace-xml-gallery-builder/languages
*/

/**
 * Main Ace Gallery Script
 * 
 * @package Ace Gallery
 * @version 1.1
 * @author Marcel Brinkkemper (ace@brimosoft.nl)
 * @copyright 2004 Nicholas Bruun Jespersen
 * @copyright 2005-2006 Valerio Chiodino
 * @copyright 2008-2012 Marcel Brinkkemper 
 * @license GNU GPL
 * @link http://askchrisedwards.com/ace/gallery/
 * 
 */

/**
 * AceGallery
 * Ace Gallery core class
 * 
 * @package Ace Gallery
 * @author Marcel Brinkkemper
 * @copyright 2010 Christopher
 * @version 1.1.11
 * @access public
 * @since 0.16.0
 */
 
class AceGallery {
  
  /**
   * The url to this plugin 
   * @var string
   */
  var $plugin_url;
  
  /**
   * The main directory of this plugin
   * @var string
   */
  var $plugin_dir;
  
  /**
   * The path to this file
   * @var string   */
  var $plugin_file;
  
  /**
   * Basename of this file
   * @var string
   */
  var $plugin_basename; 
  
  /**
   * Array holding all the ace-xml-gallery-builder options
   * @var array 
   */
  var $options;
  
  /**
   * The url the the page holding the main gallery
   * @var string
   */
  var $address;
  
  /** The path to the directory where the gallery resides on the server
   * @var string
  var $root;
  
  /**
   * Object that performs all comment related functions
   * @var class AceCommentor
   */
  var $commentor;
  
  /**
   * The gallery directory that is currently displayed
   * @var string
   */
  var $currentdir;
  
  /**
   * The requested folder or image
   * When permaling are not enabled, the url is http://myblog.org/page/?file=folder/image.jpg
   * When permalinks are enabled, the url is    http://myblog.org/page/folder/image.jpg
   * @var string
   */ 
  var $file;
  
  /**
   * @since 1.1.0
   * array to hold user defined fields
   */
  var $extra_fields = array();
  
  /** 
   * Table to store id -> file relationship
   * file = path relative to gallery root
   * 
   * @since 1.1.0
   * @var string
   */
  var $table;
      
  function __construct() { 
    global $wpdb;
    
    $this->file = $this->currentdir = '';
    
    $this->plugin_url = path_join( WP_PLUGIN_URL, plugin_basename( dirname( __file__ ) ) ); 
    $this->plugin_dir = path_join( WP_PLUGIN_DIR, plugin_basename( dirname( __file__ ) ) );    
		$this->plugin_file =  __FILE__ ;
		$this->plugin_basename = plugin_basename( $this->plugin_file );      
    load_plugin_textdomain( 'ace-xml-gallery-builder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    add_action( 'init', array( &$this, 'set_gallery_prev') );
    add_action( 'init', array( &$this, 'fields_action' ) );
    add_action( 'init', array( &$this, 'add_roles') );
    add_action( 'init', array( &$this, 'init' ) );
    
    add_action( 'wp_head', array( &$this, 'pluggable' ) );
		    
    $this->init();   
    
    // initiate the commentor only after the main gallery has loaded
    add_action( 'ace_ready', array( &$this, 'init_commentor' ) );
		
		if ( 'TRUE' == $this->get_option( 'preread' ) ) {			
			add_filter( 'ace_image_found', array( &$this, 'ace_image_found' ) );
		}   
  }
  
  /**
   * AceGallery::_resolve_href()
   * Resolves a relative url
   * 
   * @param string $base
   * @param string $href
   * @return string resolved url
   */
  function _resolve_href ( $base, $href ) {   
    if ( !$href ) { 
        return $base; 
    }  
    $href = str_replace( '\\', '/', $href );
    $rel_parsed = parse_url($href); 
    if (array_key_exists('scheme', $rel_parsed)) { 
      return $href; 
    }  
    $base_parsed = parse_url("$base "); 
    if (!array_key_exists('path', $base_parsed)) { 
      $base_parsed = parse_url("$base/ "); 
    }  
    if ($href{0} === "/") { 
      $path = $href; 
    } else { 
      $path = str_replace( '\\', '/', dirname($base_parsed['path']) ) . "/$href"; 
    }  
    $path = preg_replace('~/\./~', '/', $path); 
    $parts = array(); 
    foreach ( explode('/', preg_replace('~/+~', '/', $path)) as $part ) 
      if ($part === "..") { 
        array_pop($parts); 
      } elseif ($part!="") { 
        $parts[] = $part; 
    } 
    return ( (array_key_exists('scheme', $base_parsed) ) ? $base_parsed['scheme'] . '://' . $base_parsed['host'] : "" ) . "/" . implode("/", $parts); 
  } 
  
  /**
   * AceGallery::init()
   * initialize options and table
   * 
   * @return void
   */
  function init() {
    $options = get_option ( 'ace-xml-gallery-builder' );
		if ( false === $options ) { // options not in the wpdb, probably new install
      $options = $this->defaults();
			add_option( 'ace-xml-gallery-builder' , $options );  //set options to default
      $this->options = get_option ( 'ace-xml-gallery-builder' );  
      $veryold = get_option( 'ace_gallery_folder' ); // maybe a very old version of ace gallery was installed
      if ( false === $veryold ) // no options, new activation, no need to upgrade.
        $this->update_option('gallery_secure', ACE_SECURE_VERSION ); 
    } else {      
      $this->options = $this->check_safety( $options );
    }      
    $this->set_ace_table();
    $this->check_upgrade();
    // now we can set variables depending on options  
    $gallery_folder = $this->get_option( 'gallery_folder' );
    $root = str_replace( array('\\', '/'), DIRECTORY_SEPARATOR, trailingslashit( $this->get_absolute_path( ABSPATH . $gallery_folder ) ) );
    if ( ! $this->is_dangerous( $root ) ) {
    	$this->root = $root;
    	$this->address = trailingslashit( $this->_resolve_href( trailingslashit( get_option( 'siteurl' ) ), $gallery_folder ) ) ;	
    } else {
    	$this->root = $this->_default_dir();
    	$this->address = $this->_default_address();
    }
    do_action( 'ace_init' );
	}
	
	/**
	 * AceGallery::pluggable()
	 * Loads the plugable script after other plugins have loaded
	 * 
	 * @since 1.1.0
	 * @return void
	 */
	function pluggable() {
		include_once( $this->plugin_dir . '/inc/pluggable.php' );
	}  
  
  /**
   * AceAdmin::_dangerous()
   * 
   * @return array containg directories in which the gallery should not be.
   */
  function _dangerous() {
    return array(
      'wp-admin',
      'wp-includes',
      'wp-content' . DIRECTORY_SEPARATOR . 'themes',
      'wp-content' . DIRECTORY_SEPARATOR . 'plugins',
      'wp-content' . DIRECTORY_SEPARATOR . 'languages'
    );
  }
  
  /**
   * AceGallery::is_dangerous()
   * Check if the directory chosen for the gallery could break wordpress
   * 
   * @param mixed $directory
   * @return
   */
  function is_dangerous( $directory ) {
  	if ( '/' == trailingslashit( $directory ) ) 
			return true;
		$dangerous = $this->_dangerous();		
		$directory = str_replace( array('/', '\\'), DIRECTORY_SEPARATOR, $directory );
		foreach ( $dangerous as $path ) {
			$notok = strpos( $directory, $path );
			if ( false !== $notok )
				return true;				
		}
		return false;
  }
  
  /**
   * AceGallery::check_safety()
   * 
   * @param array $options
   * @return bool
   */
  function check_safety( $options ) {
    if ( $this->is_dangerous( $options['gallery_folder'] ) ) {	
   		$options['gallery_folder'] = $this->_default_dir();
      set_transient( 'ace_dangerous_path', true, 10 ); // set transient to remind user has set a dangerous path
  	} 	
    return $options;
  }   
  
  /**
   * AceGallery::valid()
   * Check if the gallery root directory is set, if it exists, and if it's not on a dangerous path
   * @return
   */
  function valid() { 	  
    return isset( $this->root ) && file_exists( $this->root ) && ! $this->is_dangerous( $this->get_option('gallery_folder') );
  }
  
  /**
   * AceGallery::get_option()
   * retrieves an option from the options array
   * 
   * @param string $option
   * @return mixed option value or false on fail
   * 
   */
  function get_option( $option ) {
		if ( isset ( $this->options[$option] ) )
			return $this->options[$option];
		else
			return false;
	}
  
  /**
   * AceGallery::change_option()
   * Changes an option but does not save it
   * 
   * @param mixed $option
   * @param mixed $value
   * @return void
   */
  function change_option( $option, $value ) {
    $this->options[$option] = $value;
  }
  
  /**
   * AceGallery::update_option()
   * Changes and saves an option
   * 
   * @param mixed $option
   * @param mixed $value
   * @return void
   */
  function update_option( $option, $value ) {
    $this->change_option( $option, $value );
    update_option( 'ace-xml-gallery-builder', $this->options );
  }
  
  /**
   * AceGallery::store_options()
   * Saves the options to the WP DB
   * 
   * @return void
   */
  function store_options() {    
    update_option( 'ace-xml-gallery-builder', $this->options );
  }
  
	/**
	 * AceGallery::get_absolute_path()
	 * 
	 * @param mixed $path containg ../ or ./
	 * @return absolute path
	 */
	function get_absolute_path( $path ) {
		$path = str_replace( array('/', '\\'), DIRECTORY_SEPARATOR, $path );
		$parts = array_filter( explode( DIRECTORY_SEPARATOR, $path ), 'strlen' );
		$absolutes = array();
		foreach ( $parts as $part ) {
			if ( '.' == $part ) continue;
			if ( '..' == $part ) {
				array_pop( $absolutes );
			} else {
				$absolutes[] = $part;
			}
		}
		$absolute_path = implode( DIRECTORY_SEPARATOR, $absolutes );
		if ( $path[0] ==  DIRECTORY_SEPARATOR ) // implode does not restore leading slash
			$absolute_path = DIRECTORY_SEPARATOR . $absolute_path;
		if ( $path[1] ==  DIRECTORY_SEPARATOR ) // double slash when using UNC path 
			$absolute_path = DIRECTORY_SEPARATOR . $absolute_path;
		return $absolute_path;			
	}
  
 	/**
 	 * AceGallery::get_relative_path()
 	 * 
 	 * @since 1.1.0 
	 * @param mixed $from
 	 * @param mixed $to
 	 * @return string relative path
 	 */
 	function get_relative_path( $from, $to ) {
    $from = explode( DIRECTORY_SEPARATOR,  str_replace( array('/', '\\'), DIRECTORY_SEPARATOR, $from ) );
    $to = explode( DIRECTORY_SEPARATOR, str_replace( array('/', '\\'), DIRECTORY_SEPARATOR, $to ) );
    $rel_path = $to;
    foreach ( $from as $depth => $dir ) {
      if( $dir === $to[$depth] ) {
	      array_shift( $rel_path );
       } else {
	      $remaining = count( $from ) - $depth;
	      if( 1 < $remaining ) {
          $pad_length = ( count( $rel_path ) + $remaining - 1 ) * -1;
          $rel_path = array_pad( $rel_path, $pad_length, '..' );
          break;
	      } 
      }
    }
    return implode( DIRECTORY_SEPARATOR, $rel_path );
}
  
  /**
   * AceGallery::_default_dir()
   * Sets the default gallery directory
   * 
   * @since 1.1
   * @uses apply_filters()
   * @return string;
   */
  function _default_dir() {    
    $uploads = wp_upload_dir();
    $abspath = str_replace( array('/', '\\'), DIRECTORY_SEPARATOR, ABSPATH );
    $basedir = str_replace( array('/', '\\'), DIRECTORY_SEPARATOR, $uploads['basedir'] );
    $relative = $this->get_relative_path( $abspath, $basedir ) . DIRECTORY_SEPARATOR;
    return apply_filters( 'ace_gallery_directory', $relative . 'ace-gallery' . DIRECTORY_SEPARATOR );
  }
	
	/**
	 * AceGallery::_default_address()
	 * Sets the default gallery address
	 * 
	 * since 1.1.9
	 * @uses apply_filters()
	 * @return string;
	 */
	function _default_address() {		
		$path = str_replace( '\\', '/', $this->_default_dir() );
		return $this->_resolve_href( trailingslashit( get_option( 'siteurl') ), $path );
	}	
  
  /**
   * AceGallery::default_editor_capability()
   * The default capability for users to be assigned the ace editor role
   * 
   * @since 1.1.9
   * @uses  apply_filters()
   * @return string
   */
  function default_editor_capability() {
  	return apply_filters( 'ace_editor_capability', 'edit_posts' );
  }
      
	/**
	 * AceGallery::defaults()
   * Sets all options to defaults
   * This will also disable commenting
   * Comments will be uncoupled from images and folders
   * Don't call this for live galleries
   *
   * Options used: 
   * 'new_install'  :  only used at first install to reset settings page
   * 'gallery_folder'  :  the gallery folder, relative to ABSPATH
   * 'gallery_prev'  :  the uri to the main gallery
   * 'gallery_id'  :  the ID for the page holding the [ace_gallery]  shortcode] 
   * 'excluded_folders' :  folder names that do not get listed in the gallery
   *    'cgi-bin', 'thumbs', 'slides', '_vti_cnf', '.svn'
   * 'sort_alphabetically'  :  thumbnail sort options: 
   *    TRUE = name ascending, DTRUE = name descending, 
   *    TITLE = title ascending, DTITLE = title descending, 
   *    FALSE = date ascending, DFALSE = date descending, 
   *    MANUAL = manually  
   * 'pictwidth'  :  width of slide
   * 'pictheight'  :  height of slide
   * 'thumbwidth'  :  width of thumbnail
   * 'thumbheight'  :  height of thumbnail
   * 'thumbs_page'  :  thumbnails per page
   * 'folders_page'  :  folders per page
   * 'folders_columns' :  folders per row
   * 'thumbs_columns' :  thumbnails per row
   * 'thumb_folder'  :  directory to cache thumbnails
   * 'slide_folder'  :  directory to cache slides
   * 'folder_image'  :  what to show per folder: 
   *    icon = folder icon, random_image = random form (sub)folder, none= text only 
   * 'enable_cache'  :  thumbnail cache enable
   * 'enable_slides_cache'  :  slide cache enable
   * 'use_cropping'  :  crop thumbnail image to squares
   * 'use_breadcrumb'  :  Breadcrumb links Enable/disable
   * 'resample_quality'  :  jpeg resample quality
   * 'enable_titles'  :  show image title instead of file name
   * 'use_folder_titles'  :  show folder title instead of file name
   * 'allow_comments'  :  enable comments
   * 'enable_exif'  :  other exif options removed as of 0.16.0
   * 'fileupload_allowedtypes'  :  allowed file types for upload
   *   'jpg jpeg gif png',                 
   * 'manager_roles' : WordPress roles allowed to manage ace gallery 
   *   'administrator'
   * 'enable_mwp_support'  :  enable microsoft upload wizard
   * 'wizard_user'  :  wizard user name
   * 'wizard_password'  :  wizard password
   * 'image_indexing'  :  reset image index number
   * 'link_to_gallery'  :  FALSE: slides and folders show in shortcode post
   * 'titles_length'  :  max length of title: -1 = no limit
   * 'enable_slide_show'  :  enable jQuery slideshow
   * 'gallery_secure'  :  version of last update for options or database
   * 'use_permalinks'  :  since 0.16.0 - use permalinks for the gallery
   * 'on_thumb_click'  :  since 0.16.0 - nothing, fullimg, slide, lightslide, thickslide, lightbox, thickbox
   * 'on_slide_click'  :  since 0.16.0 - nothing, fullimg, lightbox, thickbox
   * 'count_subfolders'  :  since 0.16.0 - none, include, separate, nothing
   * 'random_subfolder'  :  since 0.16.0 - random folder image from subfolder
   * 'style_css'  :  since 0.16.0 - stylesheet filename
   * 'listed_as'  :  since 0.16.0 - display in thumbs view "10 photos"
   * 'show_credits'  :  since 0.16.0 - show powered by Ace Gallery or not
   * 'flash_upload'  :  since 1.0.0  - use flash uploader
   * 'append_search'  :  since 1.0.0  - append ace gallery in wordpress search results
   * 'slide_show_duration'  :  since 1.0.0  - slideshow duration in seconds
   * 'async_cache'  :  since 1.0.0  - load thumbs after the galler has loaded in the browser
   * 'thumb_description'  :  since 1.0.2  - show description in thumbnail view
   * 'memory_ok'  :  since 1.0.16 - wether to skip the memory check
   * 'theme_javascript'  :  since 1.1.0  - whether to load javascript with theme
   * 'viewer_level'  :  since 1.1.0  - minimum level to view the gallery
   * 'sort_folders'  :  since 1.1.0  - folders may be sorted differently from images 
   * 'table_layout'  :  since 1.1.0  - use <table> element to lay out the gallery
   * 'rel_canonical' :  since 1.1.0  - insert canonical link in the page header 
	 * 'preread'       :  since 1.1.0  - read fields for newly add images 
	 * 'ajax_pagination'  since 1.1.1  - refresh image and folder pages by ajax ( stops most lightbox func )
	 * 
	 * @return array
	 */
	function defaults () {
	  global $wpdb;
    $results = get_pages();
    $page_ID = 0;
    if ( ! empty( $results ) ) {
      foreach ( $results as $page ) {
        if ( ( $page->post_status == 'publish' ) && ($page->post_type == 'page' ) ) {
          $content = $page->post_content;
          $pos = strpos( $content, '[ace_gallery' ); 
          if ( $pos !== false ) {
            $page_ID = $page->ID;
          }
        }
      }
      $permalink = get_option( 'permalink_structure' );
      $gallery_temp_uri = ( strlen( $permalink ) != 0 ) ? get_option( 'home' ) . '/' . get_page_uri( $page_ID ) . '/' : get_option( 'home' ) . "?page_id=" . $page_ID;                        
    } else {
      $gallery_temp_uri = '';
    }
    
    $gallery_folder = $this->_default_dir();

		$defaults = array (
      'new_install' => 'TRUE',
      'gallery_folder' => $gallery_folder,
      'gallery_prev' => $gallery_temp_uri,
      'gallery_id' => $page_ID,
      'excluded_folders'=>array('cgi-bin', 'thumbs', 'slides', '_vti_cnf', '.svn' ),
      'sort_alphabetically' => 'TRUE',
      'pictwidth' => 640,
      'pictheight' => 480,
      'thumbwidth' => 160,
      'thumbheight' => 120,
      'thumbs_page' => 16,
      'folders_page' => 0,
      'folders_columns'=> 0,
      'thumbs_columns'=> 0,
      'thumb_folder' => 'thumbs/',
      'slide_folder' => 'slides/',
      'folder_image' => 'icon',
      'enable_cache' => 'TRUE',
      'enable_slides_cache' => 'TRUE',
      'use_cropping' => 'FALSE',
	  'use_breadcrumb' => 'FALSE',
      'resample_quality' => '85',
      'enable_titles' => 'TRUE',
      'use_folder_titles' => 'TRUE',
      'allow_comments' => 'FALSE',
      'enable_exif' => 'FALSE',
      'fileupload_allowedtypes' => 'jpg jpeg gif png', 
      'manager_roles'=> array( 'administrator' ), 
      'enable_mwp_support' => 'FALSE',
      'wizard_user' => 'test',
      'wizard_password' => 'secret',
      'image_indexing' => '0',
      'link_to_gallery' => 'FALSE',
      'titles_length' => '-1',
      'enable_slide_show' => 'TRUE',
      'gallery_secure' => '0.0.0',
      'use_permalinks' => 'FALSE',
      'on_thumb_click' => 'slide',
      'on_slide_click' => 'fullimg',
      'count_subfolders' => 'none',
      'random_subfolder' => 'FALSE',
      'style_css' => 'ace-style.css',
      'listed_as' => 'photos',
      'show_credits' => 'FALSE',
      'flash_upload' => 'TRUE',
      'append_search' => 'FALSE',
      'slide_show_duration' => '5',
      'async_cache' => 'TRUE',
      'thumb_description' => 'FALSE',
      'memory_ok' => 'FALSE',
      'theme_javascript' => 'FALSE',
      'viewer_level' => 'everyone',
      'sort_folders' => 'TRUE',
      'table_layout' => 'FALSE',
			'rel_canonical' => 'FALSE',
			'preread' => 'FALSE',
			'ajax_pagination' => 'FALSE' 
    );
    return $defaults;
  }
  
  /**
   * AceGallery::valid_dir()
   * Checks if the directory is valid to open as AceFolder
   * 
   * @param string $adir
   * @return bool
   */
  function valid_dir( $adir ) {
    if ( ! is_dir( $adir ) ) 
      return false; 
    $forbidden = $this->get_option( 'excluded_folders' );
    $forbidden = ( false !== $forbidden ) ? $forbidden : array();
    $forbidden[] = '..';
    $forbidden[] = '.';
    return ! in_array( basename( $adir ), $forbidden );
  }
  
  /**
   * AceGallery::_build_folders_array()
   * Builds an array containing all gallery folders
   * 
   * @param string $root relative path to folder. If none given all folders will be returned
   * @return array
   */
  function _build_folders_array( $root = '' ) {  	
    $folders = array();
    if ( ! isset( $this->root ) )
    	return;
    $root = ( $root == '' ) ? $this->root : $root;
    if ( $this->is_dangerous( $root ) || ! file_exists( $root ) )
    	return;    	
    if ( $dir_handler = @opendir( $root ) ) {
      while ( false !== ( $afile = readdir( $dir_handler ) ) ) {
        if ( $this->valid_dir( $root . $afile ) ) {
          $folders[] = $root . $afile;
          $folders = array_merge( $folders, $this->_build_folders_array( $root . trailingslashit( $afile ) ) );
        } else {
          continue;
        }
      }
      @closedir( $dir_handler );
      return $folders;
    } 
  }
     
  /**
   * AceGallery::_move_option()
   * Moves a pre 0.16.0 option to the option array
   * Called by AceGallery::check_upgrade()
   * don't call this function directly'
   * 
   * @param mixed $option
   * @return void
   */
  function _move_option( $option ) {
    if ( ( $option == 'new_install' ) || ( $option == 'gallery_prev' ) ) return; // new_install is not in previous versions; gallery_prev is needed to set id at wordpress init
    $value = get_option( 'ace_' . $option );
    $this->change_option( $option, $value );
    delete_option( 'ace_' . $option );
  }  
  
  /**
   * AceGallery::_move_comments()
   * Move comments from proprietary table wp_ace_comments2image to wp_commentmeta
   * If insert in wp_commentmeta fails, a transient will be set and user can try later
   * 
   * @since 1.1.0
   * @return void
   */
  function move_comments() {
    global $wpdb;
    $acetable = $wpdb->prefix . 'ace_comments2image'; 
    if ( $wpdb->get_var("SHOW TABLES LIKE '$acetable'") == $acetable ) {
      $select = "SELECT * FROM $acetable";
      $query = $wpdb->prepare( $select );
      $results = $wpdb->get_results( $query, ARRAY_A );
      $inserted = true;
      if ( ( false !== $results ) && ( 0 < count( $results ) ) ) {
        $insert = "INSERT INTO $wpdb->commentmeta( comment_id, meta_key, meta_value ) VALUES";
        foreach ( $results as $result ) {
          $commentid = $result['comment_ID'];
          $imgid = $result['img_ID'];
          $insert .= "($commentid, 'ace', $imgid),";
        }
        $insert = trim( $insert, ',' );
        $query = $wpdb->prepare( $insert );
        $ready = $wpdb->query( $query );
        $inserted = ( false !== $ready );            
      }
      if ( $inserted ) {        
        $wpdb->query( "DROP TABLE $acetable" );
        delete_transient( 'ace_not_inserted' ); // remove tranbsient if this was second try  
      }
      else 
        set_transient( 'ace_not_inserted', 'true', 60*60*24*7 ); // set transient for a week in case user doesn't notice 
    }
    
  }
    
  /**
   * AceGallery::check_upgrade()
   * Checks if Ace Gallery DB or oiptions should be upgraded
   * @return void
   */
  function check_upgrade() {
        
    $gallery_secure = $this->get_option( 'gallery_secure' );
    if ( version_compare( $gallery_secure, ACE_SECURE_VERSION, '<' ) ) { 
      $old_secure = get_option( 'ace_gallery_secure' );
      $old_install = get_option( 'ace_gallery_folder' );
      
      $newinstall = ! $old_install && ( $gallery_secure == '0.0.0' );
      
      if ( ( $gallery_secure == '0.0.0' ) && $old_secure )  { //upgrading from a pre-0.15.0 version
        $gallery_secure = $old_secure;
      }
      
      if ( version_compare($gallery_secure, "0.10.4.4", '<' ) ) {
          
        $folders = $this->_build_folders_array();
        for ( $i = 0; $i < count( $folders ); $i++ ) {
            $folder = $folders[$i];
            $stat = stat( dirname( $folder ) );
            $fperms = $stat['mode'] & 0000666;
            $dperms = $stat['mode'] & 0000777;
            $titles_file = $folder . "/images.xml";
            if ( file_exists( $titles_file ) ) {
              @chmod( $titles_file, $fperms );
            }
            $thumbs_dir = trailingslashit( $folder  ) . $this->get_option( 'thumb_folder' );
            if ( is_dir( $thumbs_dir ) ) {
              @chmod($thumbs_dir, $dperms);
            }
            $slides_dir = trailingslashit( $folder ) . $this->get_option('slide_folder');
            if ( is_dir( $slides_dir ) ) {
              @chmod( $slides_dir, $dperms );
            }
        }
      }
      
      if ( version_compare($gallery_secure, '0.15', '<' ) && $old_install ) {
        
        foreach( $this->options as $key => $value ) { 
          $this->_move_option( $key );          
        }        
        add_action( 'init', 'prev_to_id', 1 );
        delete_option( 'ace_gallery_uri' ); // delete obsolete options ( if any )
        delete_option( 'ace_buffer_size' );
        delete_option( 'ace_force_lb_support' );    
        delete_option( 'ace_force_tb_support' );
        delete_option( 'ace_fileupload_minlevel ' );
        $this->change_option( 'new_install', 'FALSE' );
      }
      
      if ( version_compare( $gallery_secure, '0.16', '<') ) {            
        $this->update_option( 'use_permalinks', 'FALSE' );
        $this->update_option( 'style_css', 'ace-style.css' );        
        $this->update_option( 'listed_as', 'photos' ); 
        if ( 'TRUE' == $this->get_option( 'force_popup_plugin' ) ) {
          $this->update_option( 'on_thumb_click', 'fullimg' );
        } else {          
          $this->update_option( 'on_thumb_click', 'slide' );
        }
        if ( 'TRUE' == $this->get_option( 'enable_lb_thumbs_support' ) ) {
          $this->update_option( 'on_thumb_click', 'lightbox' ); 
        }
        if ( 'TRUE' == $this->get_option( 'enable_tb_thumbs_support' ) ) {
          $this->update_option( 'on_thumb_click', 'thickbox' ); 
        }       
        if ( 'TRUE' == $this->get_option( 'disable_full_size' ) ) {
          $this->update_option( 'on_slide_click', 'nothing' );
        } else {
          $this->update_option( 'on_slide_click', 'fullimg' );
        }
        if ( 'TRUE' == $this->get_option( 'use_slides_popup' ) ) {          
          $this->update_option( 'on_slide_click', 'popup' );
        }
        if ( 'TRUE' == $this->get_option( 'enable_lb_slides_support' ) ) {                    
          $this->update_option( 'on_slide_click', 'lightbox' );
        }
        if ( 'TRUE' == $this->get_option( 'enable_tb_slides_support' ) ) {                    
          $this->update_option( 'on_slide_click', 'thickbox' );
        }
      }
       
      if ( version_compare( $gallery_secure, '1.1', '<') ) {
        $this->update_option( 'sort_folders', $this->get_option( 'sort_alphabetically') );
        $this->update_option( 'table_layout', 'TRUE' ); 
				$this->update_option( 'preread', 'FALSE' );       
        $this->move_comments();
        $this->set_ace_table();
        $this->add_roles();
      }
                     
      $this->update_option("gallery_secure", ACE_SECURE_VERSION);
    }    
  }
  
  /**
   * AceGallery::prev_to_html_id()
   * translates the gallery page URI to a page ID
   * this function is called only in the update from a pre-0.15.0 version
   * 
   * @return void
   */
  function prev_to_id() {
    $value =  get_option( 'ace_gallery_prev' );
    $ID = ace_get_page_id( $value );
    $this->update_option( 'gallery_id', $ID );
    delete_option( 'ace_gallery_prev' );
  }
  
  /**
   * AceGallery::get_page_id()
   * Retrieves the ID of the page holding the gallery
   * 
   * @return
   */
  function get_page_id( $full_uri ) {  
  	$page_ID = '';
  	$permalink = get_option('permalink_structure');
  	if (strlen( $permalink ) != 0){
  		$site_url = get_option('home');
  		if ( strpos( $permalink, 'index.php' ) > 0 ) {
  			$site_url .= '/index.php';
  		}
  		$page_path = rtrim( $full_uri, '?&amp;' );		
  		if ( strpos( $full_uri, '?') > 0 ) {
  			$page_path = substr( $full_uri, 0, strpos($full_uri, '?') );
  		}
  		$page_path = substr( $page_path, strlen( $site_url ) );
  		$page = get_page_by_path( $page_path );
  		$page_ID = $page->ID;
  	} else {
  		$has_get = strpos( $full_uri, '?' );
  		if ( ! $has_get === false ) {		
  			$parts = explode( '?', $full_uri );
  			$gets = explode( '&', $parts[1] );		
  			foreach ( $gets as $get ) {
  				$is_page = substr( $get, 0, 7 ) == 'page_id';
  				if ( ! $is_page === false ) {
  					$page_ID = substr( $get, 8 );
  				}
  			}
  		}
  			
  	}
  	return $page_ID;
  }
  
  /**
   * AceGallery::level_cap() 
   * returns capability needed to browse the Ace Gallery and Folders
   * 
   * @internal
   * @since 1.1.0
   * @param string $role
   * @return string
   */
  function level_cap( $role = 'administrator' ) {
    if ( is_admin() && current_user_can( 'edit_ace_fields' ) ) return 'read'; // assigned capabilities overrides viewer level: assign lowest denominator
    switch ( $role ) {
      case 'subscriber': return 'read';
      case 'contributor': return 'edit_posts';
      case 'author': return 'upload_files';
      case 'editor': return 'moderate_comments';
      case 'administrator': return 'manage_options';
      default: return 'read';
    }
  }
  
  /**
   * AceGallery::capabilities()
   * Returns a set of capabilities per role
   * 
   * @return
   */
  function capabilities( $type = 'none') {
    switch ( $type ) {
      case 'manager' :
        return array( 
          'ace_manager' => true,
          'ace_editor' => true,
          'ace_author' => true,
          'manage_ace_files' => true,
          'create_ace_folder' => true,
          'upload_ace_file' => true,
          'edit_ace_fields' => true
          );
        break;
      case 'editor' : 
        return array( 
          'ace_editor' => true,
          'ace_author' => true,
          'create_ace_folder' => true,
          'upload_ace_file' => true,
          'edit_ace_fields' => true 
          );
        break;
      case 'author' :
        return array( 
          'ace_author' => true,
          'edit_ace_fields' => true
        );
        break;
      default:
      break;  
    }    
  }
  
  
  /**
   * AceGallery::_update_roles()
   * Applies new Ace Gallery roles according to pre 1.1 roles
   * 
   * @since 1.1
   * @return void
   */
  function _update_roles() {
    global $wp_roles;
    // at the time we do the update, pluggable has not been loaded yet
    require_once( ABSPATH . 'wp-includes/pluggable.php' );
    $gallery_secure = $this->get_option( 'gallery_secure' );
    if ( version_compare( $gallery_secure, '1.1', '<' ) ) { 
      foreach( $wp_roles->role_names as $role => $name ) { // clean up capabilities from wordpress roles
    		$arole = $wp_roles->get_role( $role );		
    		if ( $arole->has_cap( 'manage_ace_files' ) ) {
    		  $arole->remove_cap( 'manage_ace_files' );
    		}
      }    
      // move roles to user capabilities, old managers will become editors, wp admins will become new managers
      $roles = $this->get_option( 'manager_roles' ); 
      if ( $roles ) { // roles have been read
        $blogusers = ace_get_users_of_blog();    
        foreach( $blogusers as $user ) { // check if users have one or more roles and add role ace_editor
          if( ! $user->has_cap( 'ace_manager' ) && ! $user->has_cap( 'ace_editor' ) ) {
            if ( $user->has_cap( 'administrator' ) ) {
              $user->add_role( 'ace_manager' );
            } else {
              foreach( $roles as $role ) {
                if( $user->has_cap( $role ) ) {
                  $user->add_role( 'ace_editor' );
                  break 1;
                }                
              }
            }
          }
        }
      }
      $this->update_option( 'gallery_secure', ACE_SECURE_VERSION );
    } 
  }
  
  
  /**
   * AceGallery::add_roles()
   * Adds Ace Gallery specific roles
   * 
   * @since 1.1.0
   * @return void
   */
  function add_roles() {
    add_role( 'ace_manager', __( 'Ace Gallery Administrator', 'ace-xml-gallery-builder' ), $this->capabilities( 'manager' ) );
    add_role( 'ace_editor', __( 'Ace Gallery Editor', 'ace-xml-gallery-builder' ), $this->capabilities( 'editor' ) );
    add_role( 'ace_author', __( 'Ace Gallery Author', 'ace-xml-gallery-builder' ), $this->capabilities( 'author' ) );
    add_action( 'init', array( &$this, '_update_roles' ) );
  }
  
  /**
   * AceGallery::user_can_browse()
   * Checks if the current user is allowed to browse the gallery
   * 
   * @since 1.1.0
   * @uses WP current_user_can()
   * @return bool
   */
  function user_can_browse() {  	
  	if ( current_user_can( 'manage_options' ) )
  		return true;
		$level = $this->get_option( 'viewer_level' );
		if ( ! isset( $level ) || false === $level )
			$level = 'everyone';			        
    return ( 'everyone' == $level ) ? true : current_user_can( $this->level_cap( $level ) );
  }
  
  /**
   * AceGallery::access_check()
   * Check if user is allowed to view gallery/folder
   * 
   * @since 1.1.0
   * @param AceFolder $folder
   * @param bool $show_error
   * @return bool
   * @todo redundance AceGallery::user_can_browse()
   */
  function access_check( $folder = null, $show_error = true ) {
  	$viewer_level = $this->get_option( 'viewer_level' );
		$viewer_level = ( $viewer_level == '' ) ? 'everyone' : $viewer_level; 	
  	$can_access = ( null != $folder ) ? $folder->user_can( 'viewer' ) : current_user_can( $this->level_cap( $viewer_level ) ) || ( $viewer_level == 'everyone' );
  	if ( !$can_access && $show_error ) {
			if ( ! is_user_logged_in() ) {
        $message = esc_html__( 'Please log in or register to view the gallery.', 'ace-xml-gallery-builder'); 
      } else {
        $message = esc_html__( 'Sorry, you are not allowed to view this item. The owner of this gallery has set access restrictions.', 'ace-xml-gallery-builder' );  
      }
      printf( '<p>%s</p>', $message );
		}	     
		return $can_access;
  }
  
  /**
   * AceGallery::set_gallery_prev()
   * Sets the url for the main gallery page
   * @return void
   */
  function set_gallery_prev() {
    $apage = get_page( $this->get_option( 'gallery_id' ) );  
    $this->update_option( 'gallery_prev', get_page_link( $apage->ID  ) );      
    unset($apage);  
  }
  
  /**
   * AceGallery::is_gallery()
   * Returns true if the gallery page is displayed
   * is always false when AceFrontend is not loaded
   * 
   * @return bool
   */
  function is_gallery() {
    return false;
  }
  
   /**
   * AceGallery::uri()
   * Returns the uri for the current gallery display
   * This doesnt't have to be the gallery page
   * Folders can be displayed in posts or other pages
   * 
   * @return
   */
  function uri( $widget = 'none' ) {
    $is_home = false;
    if ( is_admin() && ! defined( 'ACE_FRONTEND' ) ) { 
      return $this->get_option( 'gallery_prev' );
    }
    $is_home = ( function_exists( 'is_home' ) ) ?  is_home() : false; 
    $is_front_page = ( function_exists( 'is_front_page' ) ) ?  is_front_page() : false;  
    if ( ( 'TRUE' == $this->get_option( 'link_to_gallery' ) ) || $this->is_gallery() || ( 'widget' == $widget ) || $is_home || $is_front_page ) {
      return $this->get_option( 'gallery_prev' );
    }
		$protocol = 'http' . ( ( isset( $_SERVER['HTTPS'] ) && ( 'on' == strtolower( $_SERVER['HTTPS'] ) ) ) ? 's' : '' ) . '://';
		$server = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
		$gallery_uri = $protocol . $server . $_SERVER['REQUEST_URI'];
    $gallery_uri = remove_query_arg( array( 'file', 'ace_show', 'ace_comment', 'ace_paged', 'pagei', 'ace_offset', 'ace_diroffset', 'doing_wp_cron' ), $gallery_uri );		 
    return $gallery_uri;    	  
  }
  
  /**
   * AceGallery::_compare_f()
   * Used by usort to compare folders
   * 
   * @param mixed $f1
   * @param mixed $f2
   * @acces private
   * @todo make private as soon as WP quits support for PHP4
   * @return
   */
  function _compare_f( $f1, $f2 ) { 
    global $ace_gallery;
    $how = $ace_gallery->get_option( 'sort_folders' );
    $id = '';
    $comp1 = $comp2 = '0'; // prevent notices when option 'sort_folders' has not been set.
    switch ( $how ) {
      case 'TRUE' :
      case 'DTRUE' : 
        $comp1 = $f1->curdir;
        $comp2 = $f2->curdir;
        $id = 'curdir';
        break;
      case 'TITLE' :
      case 'DTITLE' : 
        $comp1 = $f1->title();
        $comp2 = $f2->title();
        $id = 'title';
        break;
      case 'FALSE' :
      case 'DFALSE' : 
        $comp1 = $f1->datetime;
        $comp2 = $f2->datetime;
        break;
      case 'MANUAL' :  
        $comp1 = $f1->order;
        $comp2 = $f2->order;
        break;  
    }       
    if ( $id == 'curdir' || $id == 'title' ) {
      $comp1 = strtolower( $comp1 );      
      $comp2 = strtolower( $comp2 );
    }        
    if ( $comp1 == $comp2 ) {
      return 0;
    }    
    $result = ( $comp1 < $comp2 ) ? -1 : 1;
    return ( 'D' == $how[0] ) ? -$result : $result;
  }  
  
  /**
   * AceGallery::_subfolders()
   * Adds subfolders of $folder to the $folderlist
   * 
   * @param array $folderlist
   * @param AceFolder $folder
   * @param bool $show include hidden folders or not
   * @return
   */
  function _subfolders( $folderlist, $folder, $show ) {
    $subfolders = $folder->subfolders();
    if ( 0 < count( $subfolders ) ) {
      foreach ( $subfolders as $subfolder ) {          
        if ( ( ( 'hidden' ==  $show ) && ( 'hidden' == $subfolder->visibility ) ) || ( 'visible' == $subfolder->visibility ) ) {
          $folderlist[] = $subfolder;
          $folderlist = $this->_subfolders( $folderlist, $subfolder, $show );
        }
      }
    }
    return $folderlist;
  }
  
  /**
   * AceGallery::folders()
   * returns an array containing AceFolder objects
   * private folders are treated like visible
   * 
   * @param string $sub: either 'root' (include only gallery root folders) or 'subfolders' (include all subfolders) 
   * @param string $show: either 'hidden' (include hidden folders) or 'visible' (include visible folders only)
   * @return
   */
  function folders( $sub = 'root', $show = 'hidden' ) {
    $folderlist = array();        
    $list = array();	         
    if ( ! isset( $this->root ) )
    	return;    	
    if ( ! file_exists( $this->root ) || $this->is_dangerous( $this->root ) )
      return;	  
		if ( $dir_content = @opendir( $this->root ) ) {
			while ( false !== ( $subdir = readdir( $dir_content ) ) ) {	
				if ( $this->valid_dir( trailingslashit( $this->root ) . $subdir ) ) { 
				$folder = new AceFolder( $subdir );          
          if ( false != $folder ) {    	
            $folder->open(); 				    							           
            if ( ( ( 'hidden' ==  $show ) && ( 'hidden' == $folder->visibility ) )|| ( 'hidden' != $folder->visibility ) ) {
              $list[] = $folder;
            } 
          }          
        }
			}      			        
		  @closedir( $dir_content );
			if ( count( $list ) ) {
				
				if ( false !== has_filter( 'ace_sort_folders' ) )
					apply_filters( 'ace_sort_folders', $list );
				else
    			usort( $list, array( &$this, '_compare_f' ) );
    			
		    foreach( $list as $folder ) {
		      $folderlist[] = $folder;
		      if ( 'subfolders' == $sub ) {
		        $folderlist = $this->_subfolders( $folderlist, $folder, $show );
		      }
		    }
			}	
    }	     
    return $folderlist;
  }
  
  /**
   * AceGallery::random_image()
   * Gets a random image from a folder
   * 
   * @param string $folder
   * @param string $sub if 'subfolders', subfolders are included in the selection
   * @param integer $count
   * @return array
   */
  function random_image( $folder = '', $sub = 'subfolders', $count = 1, $what = 'thumbs' ) {
    if ( '' == $folder ) $sub = 'subfolders';
    $images_list = array();
    $counted = 0;
    if ( '' == $folder ) {
      $folderlist = $this->folders( 'subfolders', 'visible' );
      while ( ( $counted < $count ) && ( 0 < count( $folderlist ) ) ) {
        $folder_key = array_rand( $folderlist );
        $folder = $folderlist[$folder_key];
        $random_images = $folder->random_image( 'subfolders', $count - $counted, $what );
        if ( false != $random_images ) { // no images have been read
          $counted += count( $random_images );   
          if ( 0 < count( $random_images ) ) {
            foreach( $random_images as $image ) {
              $images_list[] = $image;
              if ( $count == count( $images_list ) )
                break;
            }
          }
        }
        unset( $folderlist[$folder_key] );
      } 
    } else {
      $ifolder = new AceFrontendFolder( $folder );
      if ( $ifolder->valid() ) {
        $images_list = $ifolder->random_image( $sub, $count, $what );
      }
    }    
    return $images_list;
  } 
  
  /**
   * AceGallery::is_image()
   * Test if $filvar points to an image
   * 
   * @param string $filevar path of file relative to gallery root
   * @return bool
   */
  function is_image( $filevar ) { 
    if ( '' == $filevar ) {
      return false;
    }
  	$filevar = $filevar;
  	$afile = $this->root . $filevar;
    $abase = basename( $afile );
    return ( file_exists( $afile ) && ! is_dir( $afile ) && ( 0 < preg_match( "/^.*\.(jpg|gif|png|jpeg)$/i", $afile ) ) && ( '.' != $abase[0] ) );
  }
  
  /**
   * AceGallery::is_folder()
   * Test if $filvar points to a folder
   * 
   * @param string $filevar
   * @return
   */
  function is_folder( $filevar ) { 
    if ( '' == $filevar ) {
      return false;
    }
    $filevar = urldecode( $filevar );
    $folder = new AceFolder( $filevar );
    $valid = $folder->valid();
    unset( $folder );
    return $valid;
  }
  
  /**
   * AceGallery::localize_loader()
   * Adds strings for loader javascript
   * @return
   */
  function localize_loader() {
     return array( 
      'ajaxurl' => admin_url( 'admin-ajax.php?' )
     );
  }
  
  /**
   * AceGallery::search_in_xml()
   * Searches for a string in all images.xml files 
   * 
   * @param string $search
   * @return array directories relative to gallery root 
   */
  function search_in_xml( $search='' ) {
  	if ( '' == $search )
  		return false;
  	if ( ! file_exists( $this->root ) || $this->is_dangerous( $this->root ) )
      return;
		$results = array();	
		$dirs = $this->_build_folders_array();
		if ( 0 < count( $dirs ) ) {
			foreach( $dirs as $dir ) {
				$titles_xml = $dir . '/images.xml';
				if ( file_exists( $titles_xml ) ) {		
					$handle = fopen( $titles_xml, 'r' );					
					$found = false;
					if ( $handle ) {					
						while ( ! feof( $handle ) ) {
		  				$buffer = fgets( $handle );
		  				if ( strripos( $buffer, $search ) !== false ) {
		      			$found = true;
		      			break; // stop reading if found
							}
						}
						fclose($handle);
						if ( $found )
							$results[] = substr( $dir, strlen( $this->root ) -1 );
					}
				}                       
			}       
  	}
		return $results;					  	  		
  }
  
 
  /**
   * AceGallery::get_file_by_id()
   * Finds all folders or images with a particular ID. returns an array holding the relative path to the gallery root
   * @param int $img_ID can also be string but should be number
   * @return array
   */
  function get_file_by_id( $img_ID ) {
    $imgID = (int) $img_ID;
    
    $results = $this->_db_get_file( $imgID );
    if ( false !== $results )
      return ( '/' == $results[0] ) ? false : $results;
		$results = array();	      
    $search = ">$imgID<";
    $paths = $this->search_in_xml( $search );
    if ( 0 < count( $paths ) ) {
    	foreach( $paths as $path ) {
    		$folder = new AceFolder( $path );
    		$folder->open();
    		$folder->load();
    		if ( $imgID == $folder->id )
					$results[] = $folder->curdir;
				if ( 0 < count( $folder->list ) ) {
					foreach( $folder->list as $image ) {
						if ( $imgID == $image->id ) {
							$results[] = $folder->curdir . $image->image;
							break;	// stop searching, id is unique per folder
						} 
					}
				}	
				unset( $folder ); 
    	}
    }  
    return $results;
  } 
  
  /**
   * AceGallery::_reserved_fields()
   * Returns an array with fieldnames that are cannot be regestered as new fields
   * The filter allows plugin builders to declare their fields as reserved.
   * 
   * @return array
   * @since 1.1.0
   */
  function _reserved_fields() {
    $reserved = array('data', 
                      'folder', 
                      'fdescription', 
                      'order', 
                      'visibility', 
                      'id', 
                      'folderdate', 
                      'role', 
                      'filename', 
                      'title', 
                      'description', 
                      'image', 
                      'index', 
                      'imagedate', 
                      'photo',
                      'editor',
                      'authors', 
                      'viewer_level' 
                      );
    return apply_filters('ace_reserved_fields', $reserved );
  }
  
  
  /**
   * AceGallery::add_field()
   * Adds an extrafield for folders or images
   * 
   * @param string $field_name should be lowercase 
   * @param string $display_name used in displaying the extra name in Admin
   * @param string $target either 'folder' or 'image'
   * @param bool $can_edit if true, an extra input field will be added in manager
   * @return void
   */
  function add_field( $field_name, $display_name = '', $target = 'image', $can_edit = false,$order='') {
    $newname = strtolower( $field_name );
    $sanitized_name = preg_replace('/[^0-9a-z\_\-]/','', $newname);
    if ( $field_name != $sanitized_name ) {
      return false;
    }
    // reserved names
    $reserved = $this->_reserved_fields();
    if ( in_array( $field_name, $reserved ) ) {
      return false;
    }
    if ( ( 'image' != $target ) && ( 'folder' != $target ) ) {
      return false;
    }
    $new_field = $this->get_field( $field_name, $target );
    $new_field = ( false !== $new_field ) ? $new_field : array();
    $new_field['name'] = $field_name;
    $new_field['display'] = ( '' == $display_name ) ? ucfirst( $field_name ) : $display_name;
    $new_field['target'] = $target;
    $new_field['edit'] = $can_edit;
	$new_field['order'] = $order;
    $this->extra_fields[] = $new_field;
    return true;
  } 
  
  /**
   * AceGallery::get_field()
   * 
   * @param string $field_name
   * @param string $target
   * @return array
   * @since 1.1.0
   * 
   */
  function get_field( $field_name, $target ) {
    $result = false;
    if ( 0 < count( $this->extra_fields ) ) {
      $i = 0;
      foreach( $this->extra_fields as $extra_field ) {
        if ( ( $field_name == $extra_field['name'] ) && ( $target == $extra_field['target'] ) ) {
          $result = $extra_field;
          break;
        }
      }
    }
    return $result;
  }
  
  /**
   * AceGallery::get_fields()
   * Gets all extra fields for folder or image
   * @param string $target
   * @return array
   * @since 1.1.0
   */
  function get_fields( $target ) {
    if ( ( 'image' != $target ) && ( 'folder' != $target ) ) {
      return false;
    }
    $result = array();
    if ( 0 < count( $this->extra_fields ) ) {
      foreach( $this->extra_fields as $extra_field ) {
        if ( $target == $extra_field['target'] ) {
          $result[] = $extra_field;
        }
      }
    }
    return ( 0 < count( $result) ) ? $result : false;
  }
  
  /**
   * AceGallery::has_field()
   * Check if a field is already registered
   * @since 1.1.0
   * 
   * @param mixed $field
   * @return
   */
  function has_field( $field ) {
    $result = false;
    if ( 0 < count( $this->extra_fields ) ) {
      foreach( $this->extra_fields as $extra_field ) {
        if ( $field == $extra_field['name'] ) {
          $result =  true;
          break;
        }
      }
    }
    return $result;
  }
  
  /**
   * AceGallery::fields_action()
   * Creates a hook for plugin builders to do something after Ace Gallery has loaded
   * e.g. Add folder or image fields
   * @since 1.1.0
   * @return void
   */
  function fields_action() {
    do_action( 'ace_ready' ); // global $ace_gallery is now available.
  }
  
  /**
   * AceGallery::themes_dir()
   * returns the path to the directory where the ace gallery themes reside
   * use filter 'ace_themes_dir' to change this
   * path has no trailing slash
   * @since 1.1.0
   * @return string
   */
  function themes_dir() {
    return apply_filters( 'ace_themes_dir', $this->plugin_dir . '/themes' );
  }
  
  /**
   * AceGallery::themes_url()
   * returns the url to the directory where the ace gallery themes reside
   * use filter 'ace_themes_url' to change this
   * url has no trailing slash
   * @since 1.1.0
   * @return string
   */
  function themes_url() {
    return apply_filters( 'ace_theme_url', $this->plugin_url . '/themes' );
  }
  
  /**
   * AceGallery::pagination()
   * Display pagination for admin and frontend
   * 
   * @since 1.1.0
   * @param string $screen
   * @param mixed $items
   * @return string;
   */
  function pagination( $screen, $items, $current_url = '' ) {
    if ( '' == $screen )
      return;
    $perpage = 20;
    $form = 'page_form';     
    $query_var = 'ace_paged';
    $caninput = true;
    switch ( $screen ) {
      case 'images' :  
        $perpage = $this->get_option( 'thumbs_page');
        if ( $perpage == 0 ) return;      
        $query_var = 'ace_pagei';
        global $ace_pagei;
        $paged = $ace_pagei; 
        break;
      case 'aimages' :
        $perpage = 20;
        $form = 'sort_images_form';
        break; 
      case 'cimages' :
        $perpage = 10;        
        $form = 'ifilter';
        $query_var = 'ace_pagei';
        global $ace_pagei;
        $caninput = false;
        break;      
      case 'folders' : 
        $perpage = $this->get_option( 'folders_page' );  
        if ( $perpage == 0 ) return; 
        global $ace_paged;
        $paged = $ace_paged;
        break;
      case 'afolders' :
        $form = 'sort_gallery_form'; 
        break; 
      case 'cfolders' :
        $perpage = 10;        
        $form = 'filter';
        $caninput = false;
        break;     
      case 'comments' :
        $form = 'ace-comments';
        break;  
    }    
    $total_pages = ceil( count( $items ) / $perpage ); 
    if ( $total_pages < 2 )
			return;
      
    $total_items = count( $items );  
    if ( isset ( $paged ) ) {
      $current = max( 1, $paged );
    } else {      
      $current = isset( $_REQUEST[$query_var] ) ? absint( $_REQUEST[$query_var] ) : 0;	
    	$current = min( max( 1, $current ), $total_pages );
    }
    $start = ( $current - 1 ) * $perpage + 1;
    $end = min( $total_items, $current * $perpage);
        
    $output = '<span class="displaying-num">' . sprintf( esc_html__('Displaying %s-%s of', 'ace-xml-gallery-builder'), $start, $end ) . ' ' . number_format_i18n( $total_items ) . '</span>';
		if ( '' == $current_url )
      $current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$page_links = array();
    if ( 1 != $current ) {
  		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
  			'first-page',
  			esc_attr__( 'Go to the first page', 'ace-xml-gallery-builder'  ),
  			remove_query_arg( $query_var, $current_url ),
  			'&laquo;&laquo;'
  		);
  
  		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
  			'prev-page',
  			esc_attr__( 'Go to the previous page', 'ace-xml-gallery-builder'  ),
  			add_query_arg( $query_var, max( 1, $current-1 ), $current_url ),
  			'&laquo;'
  		);
    }
       
    if ( $caninput ) {     
  		$html_current_page = sprintf( "%s <input class='current-page' title='%s' type='text' name='%s' value='%s' size='%d' />",
        esc_html__('Page', 'ace-xml-gallery-builder' ),
  			esc_attr__( 'Current page', 'ace-xml-gallery-builder'  ),
  			esc_attr( $query_var ),
  			number_format_i18n( $current ),
  			strlen( $total_pages )
  		);
    } else {
      $html_current_page = sprintf( '%s <a name="current-page" title="%s" class="prev-page">%s</a>',
        esc_html__('Page', 'ace-xml-gallery-builder' ),
  			esc_attr__( 'Current page', 'ace-xml-gallery-builder'  ),
        number_format_i18n( $current )
      );
    }
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = sprintf( esc_html__( '%s of %s', 'ace-xml-gallery-builder' ), $html_current_page, $html_total_pages );  
    if ( $total_pages != $current ) {
  		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
  			'next-page',
  			esc_attr__( 'Go to the next page', 'ace-xml-gallery-builder'  ),
  			add_query_arg( $query_var, min( $total_pages, $current+1 ), $current_url ),
  			'&raquo;'
  		);
  
  		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
  			'last-page',
  			esc_attr__( 'Go to the last page', 'ace-xml-gallery-builder'  ),
  			add_query_arg( $query_var, $total_pages, $current_url ),
  			'&raquo;&raquo;'
  		);
    }

		$output .= join( " ", $page_links );

		$pagination = "<div class='tablenav-pages'>$output</div>";

		return $pagination;
  } 
  
  /**
   * AceGallery::set_ace_table()
   * Insert a new table in the WordPress database to store id -> file relationships
   * 
   * @since 1.1.0
   * @return bool
   */
  function set_ace_table() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'acefiles';
    if ( $wpdb->get_var("SHOW TABLES LIKE '$this->table'") != $this->table ) {
      $sql = "CREATE TABLE " . $this->table . " (
  			img_ID bigint(20) NOT NULL default '0',
  			file varchar(512) NOT NULL default '/',
  			KEY  (img_ID)
  		); ";
      require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
    }
    return true;
  }
  
  /**
   * AceGallery::_db_get_file()
   * get file by id from wpdb
   * 
   * @since 1.1.0
   * @param integer $imgID
   * @return array()
   */
  function _db_get_file( $imgID = 0 ) {
    global $wpdb;
    if ( 0 == $imgID )
      return array( 0 => '/' );
    if ( $wpdb->get_var("SHOW TABLES LIKE '$this->table'") != $this->table )
      return false;
    
    $select = "SELECT file FROM " . $this->table . " WHERE img_ID = " . $imgID;
    $query = $wpdb->prepare( $select );
    $results = $wpdb->get_results( $query, ARRAY_A );
    if ( 0 == count( $results ) ) 
      return false;    
    $files = array();  
    foreach( $results as $result )
      $files[] = stripslashes( rawurldecode( $result['file'] ) );
    return $files;  
  }
  
  /**
   * AceGallery::_db_set_file()
   * store id -> file combinations in database
   * 
   * @since 1.1.0
   * @param integer $imgID
   * @param mixed $files
   * @return void
   */
  function _db_set_file( $imgID = 0, $files = array() ) {
    global $wpdb;
    if ( ( 0 == $imgID ) || ( 0 == count( $files ) ) ) return;
    foreach ( $files as $file ) {
      $file = rawurlencode( $file );      
      $insert = "INSERT INTO $this->table (img_ID, file) VALUES ( $imgID, '$file' )";  
      $wpdb->query( $insert );      
    }
  }
  
  /**
   * AceGallery::init_commentor()
   * Initiates the commentor;
   * @return void
   */
  function init_commentor() {  
    if ( 'TRUE' == $this->get_option( 'allow_comments' ) ) { 
      require_once( $this->plugin_dir . '/inc/comments.php');
      $this->commentor = new AceCommentor();  
    }    
  }
  
	/**
	 * AceGallery::ace_image_found()
	 * Reads and fills title, description and datetime fileds for newly add images
	 * 
	 * @since 1.1.0
	 * @param AceImage $image
	 * @return AceImage
	 */
	function ace_image_found( $image ) {
		
		// include image.php when not in admin
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		if ( ! function_exists( 'wp_read_image_metadata' ) ) 
			return;
		
		$file = trailingslashit( $this->root ) . trailingslashit( $image->folder->curdir ) . $image->image;
		// read metadata from image		
		$metadata = wp_read_image_metadata( $file );
		if ( false !== $metadata ) {
			// metadata title is derived from exif or iptc Title
			if ( isset( $metadata['title'] ) )
			$image->title = $metadata['title']; 
			// metadata title is derived from exif Comment or iptc Description
			if ( isset( $metadata['title'] ) )
			$image->description = $metadata['title'];	
			// use creation timestamp instead of upload date/time
			if ( isset( $metadata['created_timestamp'] ) )
			$image->datetime = $metadata['created_timestamp'];
			// add filter for users to add more fields
			apply_filters( 'ace_preread', $image, $metadata );
		}	
		if ( 0 == intval($image->datetime) )
			$image->datetime = filemtime( $image->original() );
		return( $image );				
	}
       
} // AceGallery class

/**
 * @global $ace_gallery
 * var holding the displaying Gallery object
 * Please test isset($ace_gallery) before interacting with the gallery
 */
global $ace_gallery;

$ace_plugin_dir = dirname ( __FILE__ );
 
require( $ace_plugin_dir . '/inc/version.php' );   
require( $ace_plugin_dir . '/inc/image.php' );    
require( $ace_plugin_dir . '/inc/folder.php' ); 
require( $ace_plugin_dir . '/inc/functions.php' );
require( $ace_plugin_dir . '/inc/widgets.php' );
if ( defined( 'DOING_AJAX') )
  require_once( $ace_plugin_dir . '/inc/ajax.php' );  


if ( is_admin() ) {  
  require_once( $ace_plugin_dir . '/inc/tables.php' );
	require_once( $ace_plugin_dir . '/inc/admin.php' );
  $ace_gallery = new AceAdmin(); 
} else {
  require_once( $ace_plugin_dir . '/inc/frontend.php' );
  if ( isset( $_REQUEST['s'] ) ) {
    require_once( $ace_plugin_dir . '/inc/search.php' );
  }
  $ace_options = get_option( 'ace-xml-gallery-builder' );
  $do_search = array_key_exists( 'append_search', $ace_options ) && ( 'TRUE' == $ace_options['append_search'] );  
  $ace_gallery = ( ! isset( $_REQUEST['s'] ) || ! $do_search ) ?  new AceFrontend() : new AceSearchFrontend();
}
?>