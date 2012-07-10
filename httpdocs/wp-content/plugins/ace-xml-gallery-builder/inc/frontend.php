<?php
/**
 * AceFrontend class
 * This class contains all functions and actions required for Ace Gallery to work on the frontend of WordPress
 * 
 * @version 1.1.4
 * @package Ace Gallery  
 * @author Marcel Brinkkemper
 * @copyright 2010-2012 Christopher
 * @since 0.16.0
 * 
 */
 
class AceFrontend extends AceGallery {
  
  /**
   * Keeps track of number of slideshows
   * 
   * @var int
   */
  var $slideshows;
  
  /** keeps track of the number of folder thumbnail tables
   * 
   * @var int
   * @since 1.1.0
   */
  var $dirshows;
  
  /** keeps track of the number of image thumbnail tables
   * 
   * @var int
   * @since 1.1.0
   */
  var $thumbshows;
  
  /** 
   * The ID of the comment to show for a folder or image
   * 
   * @var int
   */
  var $comment;
  
  /**
   * The slideshow request
   * 
   * @var string
   */
   var $slideshow;
  
  /**
   * Holds virtual root when given in the gallery shortcode.
   * 
   * @since 1.0.3
   * @var string
   */
  var $virtual_root;
  
  function __construct() {    
    AceGallery::__construct();
    
    $this->slideshows = $this->dirshows = $this->thumbshows = 0;    
    $this->slideshow = $this->comment = '';
    
    // actions
    add_action( 'wp_head', array( &$this, 'css_rules'), 1 );
    add_action( 'wp_head', array( &$this, 'styles' ), 2 );
    add_action( 'wp_head', array( &$this, 'scripts' ), 1);
    if ( 'TRUE' == $this->get_option( 'rel_canonical' ) ) {
			remove_action( 'wp_head', 'rel_canonical' );
			add_action('wp_head', array( &$this, 'rel_canonical' ) );
		}		
		$structure = get_option( 'permalink_structure' );    
    if ( ( 0 < strlen( $structure ) ) && ( 0 == strpos( $structure, 'index.php' ) ) && ( 'TRUE' == $this->get_option( 'use_permalinks' ) ) ) {
      add_action( 'generate_rewrite_rules', array( &$this, 'rewrite_rules' ) );
      add_action( 'init', array( &$this, 'flush_rules' ), 100 );
    }
    add_action( 'admin_bar_menu', array( &$this, 'admin_bar_menu' ), 100 );
		add_action( 'after_setup_theme', array( &$this, 'setup_theme' ) ); 
	      
		// filters        
    add_filter('query_vars', array( &$this, 'query_vars' ) );  
		    
    // shortcodes
    add_shortcode( 'ace_folder', array( &$this, 'folder_code' ) );
    add_shortcode( 'ace_gallery', array( &$this, 'gallery_code' ) );
    add_shortcode( 'ace_image', array( &$this, 'image_code' ) );
    add_shortcode( 'ace_slideshow', array( &$this, 'slideshow_code' ) ); 
  }
  
  /**
   * AceFrontend::rewrite_rules()
   * 
   * @param mixed $rules
   * @return void
   */
  function rewrite_rules( $rules ) {
    global $wp_rewrite;
    if ( 0 == strlen( $this->get_option( 'gallery_prev'  ) ) ) return;
    $pageid = $this->get_option( 'gallery_id' );
    $sitelen = strlen( get_option( 'home' ) ) + 1;    
    $page_path = untrailingslashit( substr( $this->get_option( 'gallery_prev' ), $sitelen ) );
    $new_rules = array( "$page_path/(.+)" => "index.php?pagename=$page_path&file=" . $wp_rewrite->preg_index( 1 ) );   
    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
  }
  
  /**
   * AceFrontend::query_vars()
   * 
   * @param mixed $vars
   * @return
   */
  function query_vars( $vars ) { 
    $vars[] = 'file';
    $vars[] = 'ace_comment';
    $vars[] = 'ace_show';
    $vars[] = 'ace_paged';
    $vars[] = 'ace_pagei';
    return $vars;
  }
  
  /**
   * AceFrontend::flush_rules()
   * 
   * @return void
   */
  function flush_rules() {	
    global $wp_rewrite;
   	$wp_rewrite->flush_rules( false );    
  }
  
  /**
   * AceFrontend::css_rules()
   * Sets widths and heights
   * Basic width formatting for the list items
   * Calculation based on Folder Columns and Thumbnail Columns Settings
   * When setting = 0 (automatic), width is 10px wider than thumbnail width
   * 
   * Is outputted earliest in wp_head so stylesheet will overwrite
   * 
   * @since 1.1.0
   * @uses apply_filters
   * @return void
   */
  function css_rules() {
    $padding = apply_filters( 'ace_item_padding', 6 );
    $width = (int) $this->get_option( 'thumbwidth') + $padding;
    $imgwidth = '';
    if ( 0 == (int)$this->get_option( 'folders_columns' ) )
    	$fwidth = strval( $width ).'px';
    else {
    	$fwidth = strval( floor( 100 / (int) $this->get_option( 'folders_columns' ) ) -1 ).'%';
    	$imgwidth = '100%';
    }	
		if  ( 0 == (int)$this->get_option( 'thumbs_columns' ) ) 
			$iwidth =  strval( $width ).'px';
		else {
			$iwidth = strval( floor( 100 / (int) $this->get_option( 'thumbs_columns' ) ) -1 ).'%';
			$imgwidth = '100%';	
		}	
    printf ( "\n<style type='text/css'>li.acef-item{width:%s;} li.acei-item{width:%s}</style>\n", $fwidth, $iwidth );
    if ( '' != $imgwidth )    	
    	printf ( "\n<style type='text/css'>li.acef-item img{max-width:%s;} li.acei-item img{max-width:%s}</style>\n", $imgwidth, $imgwidth );
  }  
  
  /**
   * AceFrontend::styles()
   * Enqueues all stylessheet needed for frontend
   * 
   * @return void
   */
  function styles() {
    $c = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'dev.css' : 'css'; 
    $styles = array();     
    $stylesheet =  $this->get_option( 'style_css' );  // get theme stylesheet
    if ( ( '' != $stylesheet ) && ( 'no_style' != $stylesheet ) ) { // don't add stylesheet when blog theme is used
      $theme_file = trailingslashit( $this->themes_dir() ) . $stylesheet;
      if ( file_exists( $theme_file ) ) {
        $styles[] = trailingslashit( $this->themes_url() ) . $stylesheet;  
      }   
    } 
    // add ace native stylesheets
    if ( 'TRUE' == $this->get_option( 'enable_slide_show') )
      $styles[] = $this->plugin_url . "/css/_slideshow.$c";  
    $styles[] = $this->plugin_url . "/css/_ajax.$c";
    $i = 0;
    if ( 0 < count( $styles) ) {
      foreach( $styles as $style_css ) {
        if ( '' != $style_css ) {
          $style_name = 'ace-style_' . $i;
          wp_register_style( $style_name, $style_css );
          wp_enqueue_style( $style_name ); 
          $i++;    
        }
      }
    }
  }
  
  /**
   * AceFrontend::scripts()
   * 
   * @return void
   */
  function scripts() {   
    $j = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'dev.js' : 'js';     
    wp_enqueue_script( 'ace_gallery', $this->plugin_url . "/js/ace-xml-gallery-builder.$j", array( 'jquery' ), '1.1', true );
    wp_localize_script( 'ace_gallery', 'ace_ajax', $this->localize_ace() );
    if ( 'TRUE' == $this->get_option( 'enable_exif' ) ) {
      wp_enqueue_script( 'jquery' );
    }
    if ( 'TRUE' == $this->get_option( 'enable_slide_show' ) ) {    
      wp_enqueue_script( 'ace_slideshow', $this->plugin_url . "/js/ace-slideshow.$j", array( 'jquery' ), '1.1', true );
      wp_localize_script( 'ace_slideshow', 'aceshow', $this->localize_show() );
    }
    if ( 'TRUE' == $this->get_option( 'async_cache' ) ) {
      wp_enqueue_script( 'ace_loader', $this->plugin_url . "/js/ace-loader.$j", array( 'jquery' ), '1.1', true );
      wp_localize_script( 'ace_loader', 'aceimg', $this->localize_loader() );
    }
    if ( 'TRUE' == $this->get_option( 'theme_javascript' ) ) {
      $theme_file = str_replace( '.css', '.js', $this->get_option( 'style_css' ) );
      $theme_path = trailingslashit( $this->themes_dir() ) . $theme_file;
      if ( file_exists( $theme_path ) ) {          
        $theme_script = trailingslashit( $this->themes_url() ) . $theme_file;
        wp_enqueue_script( 'ace_theme_script', $theme_script, array( 'jquery' ) , '1.1', true );  
      }
    }
  }
  
  /**
   * AceFrontend::localize_ace()
   * Strings for ace gallery frontend javascript
   *
   * @return array
   */
  function localize_ace() {
    return array(
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
      'searchfor' => __('Searching for comment...', 'ace-xml-gallery-builder' ),
      'pleasewait' => __('Please wait while Ace Gallery searches for ', 'ace-xml-gallery-builder' ),
			'pagination' => ( 'TRUE' == $this->get_option( 'ajax_pagination' ) ) ? 'ajax' : 'default'      
    ); 
  }
  
  
  /**
   * AceFrontend::localize_show()
   * Variables for slideshow javascript
   * 
   * @return array
   */  
  function localize_show() {
    $option = $this->get_option('slide_show_duration');
    $duration = ( $option != '' ) ? (int)$option : 5; 
    return array(
      'titlequeue' => $duration * 400,
      'titleopcty' => $duration * 400,
      'slideview' => $duration * 200, 
      'duration' => $duration * 1000     
    );
  }
  
  /**
   * AceFrontend::setup_theme()
   * Set up filters and action depending on active theme
   * 
   * @since 1.1.9
   * @return void
   */
  function setup_theme() {
		// fix for genesis and catalyst framework
		$priority = ( function_exists( 'genesis' ) ) || ( function_exists( 'catalyst_activate' ) ) ? 6 : 50; 
    add_filter('wp_title', array( &$this, 'wp_title' ), $priority, 3 );    
  }
     
   /**
   * AceFrontend::validate_dir()
   * Checks $this->file for valid gallery directory or image
   * If false, $this->file will be shortended to try one level up
   * 
	 * @since 1.0.40 
   * @return bool
   */
  function validate_dir() {
  	$valid = true;			
		if ( '' != $this->file ) {	 			 	  	
			$dotdot = strstr( $this->file, '..' );
			$valid = false === $dotdot;	     
			$valid = file_exists( $this->root . $this->file );
	    // if filevar does not validate, try to jump one level up
	    if ( ! $valid ) {
	    	$strarr = explode( '/', $this->file );
				while ( ! $valid  && ( count( $strarr ) != 0 ) )  {
					unset( $strarr[count( $strarr ) - 1] );
					$this->file = implode( '/', $strarr );
					$valid = $this->validate_dir();					
				} 	
	    }				    
      if ( is_dir( $this->root . $this->file ) ) {     
        $folder = new AceFolder( $this->file );
        $valid = $folder->valid(); 
        unset( $folder );
      } else {
      	$valid = ( 0 != preg_match( "/.*\.(jpg|gif|png|jpeg)/i", $this->file ) ); 
      }
    }  
  	return $valid;
  }
  
  /**
   * AceFrontend::file_decode()
   * Decodes the file query 
   * 
   * @return string;
   */
  function file_decode() {
  	global $file;
  	$this_file = '';
  	if ( isset( $file ) ) {
 			$this_file = rawurldecode( $file );      
    } else {
      if ( isset( $_GET['file'] ) ) {   
        $this_file = rawurldecode( $_GET['file'] ); 
      } 
    }       		
		$this_file = utf8_decode( stripslashes( $this_file ) );
		return $this_file;
  }
     
  /**
   * AceFrontend::valid()
   * Sets the path to folder or image from query var 'file''
   * Sets query var 'cpage' if comment-page is found in 'file;'
   * Handles other Ace Gallery query vars for commenting and slideshow
   * 
   * @param string $filevar : path to check if it is a valid gallery folder or image
   * @return bool 
   */
  function valid() { 
  	global $ace_comment, $ace_show, $wp_query, $ace_paged, $acepagei; // will be set by wordpress query_vars
		 		     
		$this->file = $this->file_decode();
    $comment_pos = strpos( $this->file, 'comment-page-' );
    if ( $comment_pos !== false ) {
      $comment_page = substr( $this->file, $comment_pos + 13 );
      set_query_var( 'cpage', $comment_page );
      $this->file = substr( $this->file, 0, $comment_pos );
    }   
		$feed_pos = strpos( $this->file, 'feed' );
		if ( $feed_pos !== false ) {
			set_query_var( 'feed', 'comments-rss2' );
		}
		          
    if ( isset( $ace_comment ) ) {      
       $this->comment = $ace_comment;
    } else {  
     if ( isset( $_GET['ace_comment'] ) ) {
       $this->comment = $_GET['ace_comment'];
     }
    }   
    if ( isset( $ace_show ) ) {
      $this->slideshow = $ace_show;
    } else {     
      if ( isset( $_GET['ace_show'] ) ) {
        $this->slideshow = $_GET['ace_show'];
      } 
    }
    
    if ( ! isset( $ace_paged ) ) {
    	if ( isset( $_REQUEST['ace_paged'] ) ) {
    		$ace_paged = absint( $_REQUEST['ace_paged'] );
    	}
		}
		
		if ( ! isset( $ace_pagei ) ) {
    	if ( isset( $_REQUEST['ace_pagei'] ) ) {
    		$ace_pagei = absint( $_REQUEST['ace_pagei'] );
    	}
		}
    
    // for compatibility sake: redirect ofsset queries
    if ( isset( $_REQUEST['ace_offset'] ) ) {
    	$offset = absint( $_REQUEST['ace_offset'] );
    	$ace_pagei = $offset / $this->get_option( 'thumbs_page' ) + 1;
    }
		if ( isset( $_REQUEST['ace_diroffset'] ) ) {
    	$offset = absint( $_REQUEST['ace_diroffset'] );
    	$ace_paged = $offset / $this->get_option( 'folders_page' ) + 1;
    }
      
    // validate dir    
    if ( ! $this->validate_dir() )
    	return false;
   	
    $path = pathinfo( $this->file );
    $this->currentdir = ( is_dir( $this->root . $this->file ) ) ? ltrim( $this->file, '/' ) :  trailingslashit( ltrim( $path['dirname'], '/' ) );
    return true;
  }
   
  /**
   * AceFrontend::is_gallery()
   * Checks if the current page is the gallery page.
   * Should also run before wordpress is-page() exists
   * 
   * @return bool
   */
  function is_gallery() {
  	$protocol = 'http' . ( ( isset( $_SERVER['HTTPS'] ) && ( 'on' == strtolower( $_SERVER['HTTPS'] ) ) ) ? 's' : '') . '://';
  	$server = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
  	$current_uri = $protocol . $server . $_SERVER['REQUEST_URI'];
    if ( ( $this->get_option( 'use_permalinks' ) == 'TRUE' ) && ( strlen( get_option( 'permalink_structure' ) ) > 0 ) ) {
      $gal = strpos( $current_uri, $this->get_option( 'gallery_prev' ) );
      return ( false === $gal ) ? false : true;
    } else {
      if ( function_exists( 'is_page' ) ) {
        return is_page( $this->get_option( 'gallery_id' ) );
      } else {  
        return false;
      }
    }	
  }
  
  /**
   * AceFrontend::folder_code()
   * Returns html code for a folder shortcode
   * 
   * @example [ace_folder folder="afolder/" count="10" cols="3" paging="true"] 
   * @param mixed $atts
   * @return string
   */
  function folder_code( $atts ) {
    global $ace_show;
    extract( shortcode_atts( array( 'folder' => '', 'count' => $this->get_option( 'thumbs_page' ), 'cols' => $this->get_option( 'thumbs_columns' ), 'paging' => 'true' ), $atts ) );
    $folder = trailingslashit( ltrim ( utf8_decode( $folder), '/') );
    $folder = html_entity_decode( $folder );
    $this->valid();
    $folder_code = sprintf( __( 'Ace Gallery cannot access %s', 'ace-xml-gallery-builder'), ace_html( $folder ) );     
    $paging = strtolower( $paging );     
    if ( isset( $ace_show ) ) {
      $this->slideshow = $ace_show;
    } else {     
      if ( isset( $_GET['ace_show'] ) ) {
        $this->slideshow = $_GET['ace_show'];
      } 
    } 
    $the_folder = new AceFrontendFolder( $folder );
    $this->valid(); // set $this->file     
    if ( $the_folder->valid() ) {      
      if ( ( 'true' == $this->slideshow ) && ( $this->file == $folder ) ) {       
        ob_start();
        $the_folder->slideshow();
      } else {
        $the_folder->open();
        ob_start();
      ?>
  		<div class="ace_gallery">
      <?php  
        if ( isset( $this->file ) && $this->is_image( $this->file ) && ( $this->currentdir == $the_folder->curdir ) ) {        
            $the_folder->show_slide( basename( $this->file ) );
          } else {          
            $the_folder->show_thumbs( $count, $cols, $paging );
          }
        ?>
      </div>        
      <?php
      $virtualroot = isset( $this->virtual_root ) ? urlencode( $this->virtual_root ): '';
			echo "\n<script type='text/javascript'>var ace_virtual = { root: '$virtualroot' };</script>\n"; 
      }
      
    $folder_code = ob_get_contents();
    ob_end_clean();
    }
    unset( $the_folder );
    return $folder_code;
  }
  
  /**
   * AceFrontend::set_root()
   * Sets a different root for a gallery shortcut
   * use [ace_gallery root="folder"]
   * The new root is a subfolder of the gallery 
   * Private folders cannot be set as root folder
   * 
   * @since 1.0.3
   * @param string $root
   * @return bool
   */
  function set_root( $root ) {
  	global  $current_user;
  	
		$new_root = str_replace( "\\", "/", $this->get_absolute_path( path_join( $this->root, $root ) ) );
		if ( ! file_exists( $new_root ) )
			return false;
			
  	get_currentuserinfo();
  	$folder = new AceFolder( $root );
  	if ( false !== $folder ) {
  		$folder->open();
  		$this->change_option( 'viewer_level', $folder->viewer_level );
  		if ( ( 'private' == $folder->visibility ) && ( $current_user->ID != $folder->editor ) )
				return false; 	    
	    $this->root = trailingslashit( $new_root );
	    $this->address = trailingslashit( path_join( $this->address, $root ) );
	    $this->virtual_root = trailingslashit( $root );
	    return true;				
    } 
    return false;
  }
  
  /**
   * AceFrontend::gallery_code()
   * Returns the html code for the gallery shortcode
   * 
   * @example [ace_gallery root="afolder/"]
   * @param mixed $atts
   * @return string
   */
  function gallery_code( $atts ) {
    extract( shortcode_atts ( array( 'ace_gallery' => '', 'root' => ''), $atts) );
    if ( '' != $root ) {
      $this->set_root( utf8_decode( $root ) );
    }
    ob_start(); 
    $show = ( ! isset( $this->file ) ) ? true : $this->valid();       
    if ( $show === false ) {
      ?>
      <div class="error">
        <p><strong><?php esc_html_e( 'Something went wrong initializing Ace Gallery.', 'ace-xml-gallery-builder' ); ?></strong></p>
        <p><?php esc_html_e( 'Maybe the folder or the image you are looking for does not exist','ace-xml-gallery-builder' ); ?></p>
        <p>
      <?php                 
        if ( current_user_can( 'manage_options' ) ) {
        	/* translators 1: <a href="">, 2: </a> */
          echo sprintf( esc_html__( 'Please check your %1ssettings%2s  or contact the author of this page'),
						sprintf( '<a href="%s">', admin_url( 'admin.php?page=ace-xml-gallery-builder' ) ),
						'</a>' 
					);
        } else {          
          esc_html_e( 'Please contact the author of this page.', 'ace-xml-gallery-builder' );  
        }
      ?>      
        </p>
      </div>
      <?php 
    } else {    
      $this->show();  			                
    } 
    $new_content = ob_get_contents();
    ob_end_clean();
    return $new_content;
  }
  
  /**
   * AceFrontend::image_code()
   * Returns the html code for an image in a post 
   * 
   * @todo add WordPress classes
   * @param mixed $atts
   * @return string
   */
  function image_code( $atts ) {
    
    extract( shortcode_atts ( array( 'folder' => '', 'image' => '', 'align' => '', 'width' => '', 'height' => '', 'title' => '', 'display' => 'thumb'), $atts));    
    $folder = trailingslashit( utf8_decode( $folder ) );   
    $image = utf8_decode( $image ); 
    $image_code = '<p class="error">' . sprintf( __( 'Ace Gallery cannot access %s', 'ace-xml-gallery-builder' ), ace_html( $folder ) ) . '</p>';
    if ( is_readable( $this->root . $folder ) && ( '/' != $folder ) ) {      
      $ifolder = new AceFolder( $folder );
      if ( false !== $ifolder ) {
	      $the_image = $ifolder->single_image( $image, $display . 's' );
	      if ( ! $the_image ) { 
	        $image_code = '<p class="error">' . sprintf( __( 'Ace Gallery cannot find %s', 'ace-xml-gallery-builder' ), ace_html( $image ) ) . '</p>';
	      } else {  
	      	$width = intval( $width );
	      	$height = intval( $height );
		      $img_location = $the_image->loc(); 	      	
	    		list( $iwidth, $iheight ) = @getimagesize( $img_location );
	    		if ( 0 == intval( $iheight ) ) 
						$iheight = $this->get_option( 'pictheight' );
	    		if ( 0 == intval( $iwidth ) ) 
						$iwidth = $this->get_option( 'pictwidth' );
	    		
	    		// set width of containing div;
	    		$div_width = ( 0 < $width  ) ? $width : $iwidth;
	    		$div_width = $div_width + 10;
	    		
	    		// only one attribute is set
	    		if ( ( 0 < $height && 0 == $width ) || ( 0 == $height && 0 < $width ) ) {
	    			if ( 0 < $height )
	    				$width = round( $height / $iheight * $iwidth );
	    			else  		    			
	    				$height = round( $width / $iwidth * $iheight );
					}
	        $img_src = $the_image->src();        
	        $img_link = $the_image->on_click( 'widget' );
	        $attr_width = 'width:' . strval( $div_width ) . ';';
	        if ( 'image' == $display ) {
						$img_src = $the_image->src();
	        }
	        $image_link = ''; 
	        unset( $ifolder );	 
					
					// set style for left, right or centered        
	        $image_code = '<div class="ace_image ' . $display . '" style="';
	        if ( 'left' == $align ) {
	            $image_code .= 'float:left;';
	        } elseif ( 'right' == $align ) {
	            $image_code .= 'float:right;';
	        } elseif ( 'center' == $align ) {
	            $image_code .= 'margin-left:auto;margin-right:auto;';
	        }  
	        $image_code .= $attr_width;
	        $image_code .= '"><div style="text-align:center">';
	        $wcode = ( 0 < $width ) ? $width : $iwidth;
	        $hcode = ( 0 < $height ) ? $height : $iheight;
	        $rel = ( '' != $img_link['rel'] ) ? ' rel="' . $img_link['rel'] . '"' : '';
	        if ( 'image' != $display )
	        	$image_link = '<a href="' . $img_link['href'] . '" class="' . $img_link['class'] . $rel . ' title="' . $the_image->title() . '" >';
	        $image_link .= '<img src="' . $img_src . '" alt="' . $the_image->title() . '" width="' . $wcode . '" height="' . $hcode . '" />';
					if ( 'image' != $display )
						$image_link .= '</a>';        
	        $image_code .= $image_link;
	        $image_code .= '</div><div class="title">';
	        if ( ( '' == $title ) && ( 'TRUE' == $this->get_option( 'enable_titles' ) ) ) {
	          $title = $the_image->title();
	        }
	        if ($title != '') {
	          $image_code .= ace_html( $title );
	        }
	        $image_code .= '</div></div>';
	      }
	      unset( $the_image );
			}
    }   
  	return $image_code;
  }
 
  /**
   * AceFrontend::slideshow_code()
   * Returns html code for a show of slides from a folder
   * If no folder is given, a random folder will be selected 
   * 
   * @example [ace_slideshow folder="afolder" display="slide"]
   * @param mixed $atts
   * @return string
   */
  function slideshow_code($atts) {
    extract( shortcode_atts( array( 'folder' => '', 'display' => 'slide' ), $atts ) );
    $where = ace_nice_link( $folder );
    $goodfolder = false;
    if ( '' ==  $folder )  {
      $where = __( 'the Gallery', 'ace-xml-gallery-builder' );
      $folders = $this->folders( 'subfolders', 'visible' );
      while ( ! $goodfolder && ( 0 < count( $folders ) ) ) {
        $key = array_rand( $folders );
        $sfolder = $folders[$key];
        if ( 0 != $sfolder->count() ) {
          $goodfolder = true;
          $folder = $sfolder->curdir;
        }
        unset( $folders[$key] );             
      }       
    }   
    $slideshow_code = esc_html( sprintf( __( 'Ace Gallery cannot find images in %s', 'ace-xml-gallery-builder' ), $where ) ); 
    if ( ! $goodfolder ) {      
      $folder = trailingslashit( ltrim( utf8_decode( $folder ), '/' ) );      
      $folder = html_entity_decode( $folder );
    }    
    $sfolder = new AceFrontendFolder( $folder );  
    if ( $sfolder->valid() ) {
        ob_start();
        echo "<div class='ace_gallery'>\n";
        $sfolder->slideshow( $display );
        echo "</div>\n";
        $slideshow_code = ob_get_contents();
        ob_end_clean();
    }
    return $slideshow_code;
  }

  /**
   * AceFrontend::get_folder()
   * 
   * @return class AceFrontendFolder
   */
  function get_folder() {
    return new AceFrontEndFolder( $this->currentdir );
  }
  
  /**
   * AceFrontend::_is_dir()
   * Checks if the requested file is a directory
   * 
   * @internal
   * @return bool
   */
  function _is_dir() {
    return is_dir( $this->root . $this->file );
  }
  
  /**
   * AceFrontend::do_slide()
   * Output a slide page, can be changed by a filter
   * 
   * @param AceFrontendFolder $folder
   * @param string $filevar
   * @return void
   */
  function do_slide( $folder, $filevar ) { 
  	ob_start();
  	$folder->show_slide( $filevar );
  	$do_slide = ob_get_contents();
    ob_end_clean();  	 
    $do_slide = apply_filters( 'ace_do_slide', $do_slide, $folder, $filevar );
    echo $do_slide;
  }
  
  /**
   * AceFrontend::show()
   * Build the html code for the full gallery
   * 
   * @param integer $count number of folders
   * @param integer $cols number of folder columns
   * @param bool $dirs
   * @return
   */
  function show( $count = -1, $cols = -1 ) { // builds main gallery page
   
    if ( 'TRUE' == $this->get_option( 'allow_comments' ) && ( '' == $this->comment ) && ( '' == $this->file ) ) { 
      echo "<script type=\"text/javascript\">aceGallery=true;</script>\n";
    } 
		 
    $folder = null;       
    if ( '' != $this->file ) {      
      $folder = $this->get_folder();
      if ( ! $folder->valid() ) {  
        unset( $folder );
        return false;
      } else {        
        $folder->open();
        $folder->load( 'thumbs' );
      }
    }
    
		if ( ! $this->access_check( $folder )  ) 
			return;
  	    
    echo "<div class='ace_gallery'>\n";
   	
		$virtualroot = isset( $this->virtual_root ) ? urlencode( $this->virtual_root ): '';
		echo "\n<script type='text/javascript'>var ace_virtual = { root: '$virtualroot' };</script>\n"; 
    
		$this->create_navigation();
                         
    $dcount = ( -1 == $count ) ? $this->get_option( 'folders_page' ) : $count;
    $dcols = ( -1 == $cols ) ?  $this->get_option( 'folders_columns' ) : $cols;
    if ( isset( $folder ) )
    	echo apply_filters( 'ace_folder_header', $folder->folder_header() );
    if ( isset( $folder ) && ( 'true' == $this->slideshow ) ) {
      $folder->slideshow('slide');
    } else { 
      if ( ! $this->is_image( $this->file ) ) { 
        $this->show_dirs( $folder, $dcount, $dcols ); // show (sub)folders
      }   
      if ( isset( $folder ) ) {                      // not on the gallery root
        if (  $this->_is_dir() ) { // it is a folder; show thumbnails
          $count = ( -1 == $count ) ? $this->get_option( 'thumbs_page' ) : $count;
          $cols = ( -1 == $cols ) ?  $this->get_option( 'thumbs_columns' ) : $cols;
          $folder->show_thumbs( $count, $cols ); // show thumbs
        } else  { 
        	$this->do_slide( $folder, basename( $this->file ) );
        }
      }
    }
    unset( $folder );
    $this->credits_div();
   
    echo "</div>\n";
  }
  	
	/**
	 * AceFrontend::admin_bar_menu()
	 * Show edit links for gallery in WordPress admin bar
	 * 
	 * @since 1.1
	 * @return void
	 */
	function admin_bar_menu() {
		global $wp_admin_bar;
		if ( ! $this->is_gallery() )
			return;
		if ( ( $this->get_option( 'new_install' ) != 'TRUE' ) && $this->valid() && current_user_can( 'edit_ace_fields' ) ) {
			$wp_admin_bar->add_menu( array( 'id' => 'ace-menu', 'title' => __( 'Ace', 'ace-xml-gallery-builder' ), 'href' => '#' ) );
			$wp_admin_bar->add_menu( array( 'parent' => 'ace-menu', 'id' => 'ace-xml-gallery-builder-manage',  'title' => __( 'Manage Gallery', 'ace-xml-gallery-builder' ), 'href' => admin_url( 'admin.php?page=ace-filemanager') ) );
			if ( current_user_can( 'edit_ace_fields' ) && ! is_search() && ( $this->is_folder( $this->file ) || $this->is_image( $this->file ) ) ) {
				$wp_admin_bar->add_menu( array( 'parent' => 'ace-xml-gallery-builder-manage', 'title' => __( 'Edit Folder', 'ace-xml-gallery-builder' ), 'href' => admin_url( 'admin.php?page=ace-filemanager&amp;folder=' . urlencode( $this->currentdir ) ) ) );
			}
			if ( current_user_can( 'edit_ace_fields' ) && ! is_search() && $this->is_image( $this->file ) ) {
				$folder = new AceFolder( $this->currentdir );
				$filename = basename( $this->file );
				$image = $folder->single_image( $filename );
				$wp_admin_bar->add_menu( array( 'parent' => 'ace-xml-gallery-builder-manage', 'id' => 'ace-xml-gallery-builder-edit-image', 'title' => __( 'Edit Image', 'ace-xml-gallery-builder' ), 'href' => admin_url( 'admin.php?page=ace-filemanager&amp;folder=' . urlencode( $this->currentdir ) . '#' . $image->form_name() ) ) );				
			}
			if ( current_user_can( 'edit_posts' ) && ( 'TRUE' == $this->get_option( 'allow_comments' ) ) ) {
				$wp_admin_bar->add_menu( array( 'parent' => 'ace-menu', 'id' => 'ace-xml-gallery-builder-edit-comments', 'title' => __( 'Comments', 'ace-xml-gallery-builder' ), 'href' => admin_url( 'admin.php?page=ace-filemanager&edit=comments&amp;file=' . ace_nice_link( $this->file ) ) ) );
			}		
			if ( current_user_can( 'edit_themes') )
				$wp_admin_bar->add_menu( array( 'parent' => 'ace-menu', 'id' => 'ace-xml-gallery-builder-edit-themes', 'title' => __( 'Themes', 'ace-xml-gallery-builder' ), 'href' => admin_url( 'admin.php?page=ace-themesmanager' ) ) );						
    	if ( current_user_can( 'manage_options' ) )
				$wp_admin_bar->add_menu( array( 'parent' => 'ace-menu', 'id' => 'ace-xml-gallery-builder-settings', 'title' => __( 'Gallery Settings', 'ace-xml-gallery-builder' ), 'href' => admin_url( 'admin.php?page=ace-xml-gallery-builder') ) );			
		}	
	}	
  
  /**
   * AceFrontend::credits_div()
   * outputs the 'powered by' credits line
   * 
   * @since 1.1.0
   * @return void
   */
  function credits_div() {
    if ( 'TRUE' != $this->get_option( 'show_credits' ) ) 
      return;
    $credits_div = '<div class="ace_powered"><div class="acepow">';
    $credits_div .= sprintf( __( 'Powered by <a href="%s">Ace Gallery %s</a> Copyright &copy; 2008-%s <a href="%s">%s</a>', 'ace-xml-gallery-builder' ),
      'http://wordpress.org/extend/plugins/ace-xml-gallery-builder/',
      ace_version(),
      date( 'Y' ),
      'http://askchrisedwards.com/',
      'Christopher'
    );  
    $credits_div .= "</div></div>\n";
    echo $credits_div;
  }  
  
  /**
   * AceFrontend::_sep()
   * Filtered separator used in 'now viewing' breadcrumbs
   * 
   * @since 1.1.0
   * @uses apply_filters()
   * @return string
   */
  function _sep() {
    return apply_filters( 'ace_separator', '&raquo;' );
  }
  
  /**
   * AceFrontend::create_navigation()
   * Show the navigation breadcrumb trail
   * 
   * @uses apply_filters()
   * @uses get_bloginfo()
   * @uses get_the_title()
   * @uses trailingslashit()
   * @return void
   */
  function create_navigation() {
  	global $post; 
	global $ace_gallery;
	/*echo "<pre>";
	print_r($ace_gallery);
	echo $ace_gallery->get_option( 'use_breadcrumb' );*/
    //if ( 'TRUE' == $ace_gallery->get_option( 'use_cropping' ) ) 	
    $nav = explode( '/', untrailingslashit( $this->currentdir ) );
    $path = pathinfo( $this->file );
    $current = '';
    $now_viewing = apply_filters( 'ace_now_viewing', __( ' Now viewing: ', 'ace-xml-gallery-builder' ) );
    $sep = $this->_sep();
    $navigator = sprintf( '<div class="top_navigator">%s <a href="%s">%s</a> <span class="raquo">%s</span> <a href="%s">%s</a>',
      $now_viewing,
      get_bloginfo( 'url' ), 
      get_bloginfo( 'name' ),
      $sep,
      $this->uri(),
      get_the_title( $post->ID )
    );    
    if ( $nav[0] != '' ) {
	    foreach ( $nav as $n ) {
	      $current .= trailingslashit( $n );
	      $folder = new AceFrontendFolder( $current );
	      $folder->open();
	      $navigator .= sprintf( ' <span class="raquo">%s</span> <a href="%s">%s</a> ',
	        $sep,
	        $folder->uri(),
	        $folder->title()
	      );
	      unset( $folder );
	    }
		}
    if ( ! is_dir( $this->root . $this->file ) ) {
      $folder = new AceFolder( $this->currentdir );
      if ( $folder->valid() ) {
        $image = $folder->single_image( $path['basename'] );        
        $navigator .= sprintf ( ' <span class="raquo">%s</span> <a href="%s">%s</a>',
          $sep, 
          $image->uri(), 
          $image->title()
        );    
      }
      unset( $folder );
    }
    $navigator .= "</div>\n";
	if ( 'TRUE' == $ace_gallery->get_option( 'use_breadcrumb' ) ){
    echo apply_filters( 'ace_navigator', $navigator );
	}
  }

  /**
   * AceFrontend::show_dirs()
   * Show the folders view
   * 
   * @param AceFolder $folder 
   * @param integer $perpage number of folders per page
   * @param integer $columns number of columns
   * @return void
   */
  function show_dirs( $folder = null, $perpage = 0, $columns = 1 ) {
    global $ace_paged, $current_user;
    
    if ( ! $this->access_check( $folder ) ) 
			return;	
    $columns =  ( 'TRUE' == $this->get_option( 'table_layout') ) ? max( 1, $columns ) : max( 0, $columns );
    $perpage = max( 0, $perpage );
    
    $folders = ( null != $folder ) ? $folder->subfolders( 'visible' ) : $this->folders( 'root', 'visible' );
    if ( 0 == count( $folders ) ) 
      return;		     
    $foldervalue = ( null != $folder ) ? urlencode( $folder->curdir ) : '';
    $start = 1;      
    $end = count( $folders );        
    $query_var = 'ace_paged';
    $ace_paged = isset( $ace_paged ) ? (int)$ace_paged : isset( $_REQUEST[$query_var] ) ? absint( $_REQUEST[$query_var] ) : null;
    printf( '<div class="folders"><!-- Ace Gallery %s -->%s', ace_version(), "\n" );
		if ( 0 < $perpage) {    
      $total_pages = ceil( count( $folders ) / $perpage );
      if ( isset ( $ace_paged ) ) {
        $current = max( 1, $ace_paged);
      } else {      
        $current = isset( $_REQUEST[$query_var] ) ? absint( $_REQUEST[$query_var] ) : 0;	
        $current = min( max( 1, $current ), $total_pages );
      }
      $start = ( $current - 1 ) * $perpage + 1;
      $this->dirshows++;
      $current_user = get_currentuserinfo();
      $end = min( count( $folders ), $current * $perpage);
      if ( ( $perpage < count( $folders ) ) && ( $perpage != 0 ) ) { 
      	$ajax_nonce = wp_create_nonce( 'show_dirs' );
        printf( '<form name="folders_page_%s" action="%s" method="post">', $this->dirshows, $this->uri() );
        printf( '<input type="hidden" name="current" value="%s" />', $current );
        printf( '<input type="hidden" name="last_page" value="%s" />', ceil( count( $folders ) / $perpage ) );
        printf( '<input type="hidden" name="folder" value="%s" />', $foldervalue );  
        printf( '<input type="hidden" name="virtual" value="%s" />', urlencode( $this->virtual_root ) );
        printf( '<input type="hidden" name="perpage" value="%s" />', $perpage );
        printf( '<input type="hidden" name="columns" value="%s" />', $columns );
				printf( '<input type="hidden" name="ajax_nonce" value="%s" />', $ajax_nonce );        
        printf( '<input type="hidden" name="request_uri" value="%s" />', remove_query_arg( 'ace_paged', $_SERVER['REQUEST_URI'] ) ); 
				printf( '<input type="hidden" name="user_id" value="%s" />', $current_user->ID );       
      } 		
  	}           
    // this is where we the actually echo the folders 
    echo ( 'TRUE' == $this->get_option( 'table_layout') ) ? $this->dir_table( $folders, $start, $end, $columns ) : $this->dir_view( $folders, $start, $end );    

    if ( ( $perpage < count( $folders ) ) && ( $perpage != 0 ) ) {
      printf ( '<div class="folder_pagination">%s<br style="clear:both;" /></div></form>', $this->pagination( 'folders', $folders ) );            
    } 
    echo "</div>";
  }
  
  /**
   * AceFrontend::_above_folders()
   * Filtered title above the folders listing
   * 
   * @since 1.1.0
   * @return string
   */
  function _above_folders() {
    return apply_filters( 'ace_above_folders', __(  'Folders', 'ace-xml-gallery-builder' ) );
  }
  
  /**
   * AceFrontend::_cabinet()
   * Filtered cabinet image
   * 
   * @since 1.1.0
   * @return string <img /> element
   */
  function _cabinet() {
    return apply_filters( 'ace_cabinet', sprintf( '<img class="ace_folders_img icon" src="%s" alt="folders" />', $this->plugin_url . '/images/folders.png' ) );
  }
  
  /**
   * AceFrontend::dir_table()
   * returns html of folders table
   * 
   * @since 1.1.0
   * @deprecated after 1.1.0 this function will no longer be updated
   * @uses apply_filters()
   * @param array $folders
   * @param integer $start
   * @param integer $end
   * @param integer $columns
   * @return string 
   */
  function dir_table( $folders, $start = 1, $end = 1, $columns = 1 ) {    
    if ( ( 0 == count( $folders ) ) ) 
      return '';
    
    $dir_table = '';
    $col_count = 0;
    $columns = ( $columns == 0 ) ? 1 : $columns;    
    $show_image = 'none' != $this->get_option( 'folder_image' ); 
    $div_width = ( ! $show_image ) ? 0 : (int)$this->get_option( 'thumbwidth' );
            
    $dir_table = '<table class="dir_view"><tbody>';
    //$dir_table .= sprintf( '<tr><td colspan="%d" class="folder">%s %s</td></tr><tr>',
      //$columns, 
      //$this->_cabinet(),
      //esc_html( $this->_above_folders() )
    //);
    
    $i = $start -1;
    while ( $i < $end ) {                  
      $folderi = $folders[$i];         
      if ( 'hidden' != $folderi->visibility ) { // do not insert a table cell when folder is hidden
      $dir_table .= sprintf( '<td><div class="ace_thumb" style="min-width:%spx">%s%s%s%s</div></td>',
        $div_width,
        $folderi->icon_div(),
        $folderi->title_div(),
        $folderi->description_div(),
        apply_filters( 'ace_frontend_folder', '', $folderi )
      );
      $col_count++;
      if ( ( $col_count / $columns ) == intval( $col_count / $columns ) ) {
        $dir_table .= '</tr>';
        if ( $i+1 < $end ) { 
          $dir_table .='<tr>';
        }
      }            
      $i++;
      } // not hidden
    } //while
    
    if ( ( $col_count / $columns ) != intval( $col_count / $columns ) ) { // pad table with empty cells
      while ( ( $col_count / $columns ) != intval( $col_count / $columns )  ) {
        $dir_table .= '<td></td>';
        $col_count++;
      }
    $dir_table .= '</tr>';
    } 
    $dir_table .= '</tbody></table><br />';
    return $dir_table;
  }
  
  /**
   * AceFrontend::dir_view()
   * returns html of folder list
   * 
   * @since 1.1.0
   * @param array $folders
   * @param int $start
   * @param int $end
   * @return string html of folders listing
   */
  function dir_view(  $folders, $start, $end ) {
    if ( ( 0 == count( $folders ) ) ) 
      return '';
      
    $dir_view = '<div class="dir_view">';
   // $dir_view .= sprintf( '<div class="folder">%s %s</div>', 
    //  $this->_cabinet(),
    //  $this->_above_folders() 
    //);
    $dir_view .= '<ul class="acef-list">';
    $i = $start - 1;
    while ( $i < $end ) {
      $folderi = $folders[$i];
      if ( 'hidden' != $folderi->visibility ) { // do not insert a list tiem when folder is hidden
        $dir_view .= sprintf( '<li class="acef-item"><div class="ace_thumb">%s%s%s%s</div></li>',
        $folderi->icon_div(),
        $folderi->title_div(),
        $folderi->description_div(),
        apply_filters( 'ace_frontend_folder', '', $folderi ) );                  
        $i++;
      }
    }
    $dir_view .= '</ul>';
    
    $dir_view .= '</div>';
    return $dir_view;
  }

  /**
   * AceFrontend::wp_title()
   * Returns the WordPress title for the gallery instead of the page title
   * @link http://askchrisedwards.com/2010/12/27/ace-xml-gallery-builder-and-seo-plugins/
   * @param string $title The title as compiled by WordPress
   * @param string $sep How to separate the various items within the page title.
   * @param string $seplocation Direction to display title.
   * @return string
   */
  function wp_title( $title, $sep, $seplocation ) { 
    global $ace_pagei, $ace_paged;		 
    $prefix = " $sep ";
  	if ( $this->is_gallery() && $this->valid() && ( '' != $this->currentdir ) ) {
  		$tdirs = untrailingslashit( $this->currentdir );
  		$dirs = explode( '/', $tdirs );  		
  		$tfile = '';
  		$title_array = array();
  		
  		foreach( $dirs as $dir ) {
  			$tfile .= trailingslashit( $dir );
  			if ( is_dir( $this->root . $tfile ) ) {
				  $folder = new AceFrontendFolder( $tfile );
          if ( is_object( $folder ) ) {               
            $folder->open();
      			$title_array[] = $folder->title();                      
			    }                 
  			} 
  		} 			   		
  		if ( $this->is_image( $this->file ) ) {
  		  if ( is_object( $folder )) {  		   
    		  $folder->load( 'images' );
          $pathinfo = pathinfo( $this->file );
    			$image = $folder->single_image( $pathinfo['basename'] ); 
  		  }
  			if ( is_object( $image) ) $title_array[] = $image->title();
  		}
      
      if ( !empty($title) )        
  			if ( 'right' == $seplocation ) {
  		  $title_array = array_reverse( $title_array );
        $title = implode( " $sep ", $title_array ) . $prefix . $title;
		  } else {
		    $title = $title . $prefix . implode( " $sep ", $title_array );
		  }  				
  	}
  	
  	// append gallery paging also for galleries in posts
		$imagepage ='';	
		if ( isset( $ace_pagei ) ) {
			$page = intval( $ace_pagei );
			if ( $page > 1 ) { 				
				$imagepage = sprintf( __( '%s page %d', 'ace-xml-gallery-builder' ), ucfirst( $this->get_option( 'listed_as' ) ), $page ) ;  			
				$title_array[] = $page;
			}
		}		
		$folderpage = '';
		if ( isset( $ace_paged ) ) {
			$page = intval( $ace_paged );
			if ( $page > 0 ) {				
				$folderpage = sprintf( __( 'Index %d', 'ace-xml-gallery-builder' ), $page ) ;
			}
		}
		
		if ( '' != $imagepage ) 
			$title = $imagepage . $prefix . $title;
		if ( '' != $folderpage ) 
			$title = $folderpage . $prefix . $title;
							
  	return $title;
  }
	
	/**
	 * AceFrontend::rel_canonical()
	 * Echoes the canonical link in the page header
	 * 
	 * @link http://askchrisedwards.com/2011/09/05/canonical-urls-revisited/
	 * @since 1.1
	 * @return void
	 */
	function rel_canonical() {
	  global $wp_the_query, $ace_pagei, $ace_paged;
		// this is copied from original wordpress code   
		if ( !is_singular() )
			return;
		if ( !$id = $wp_the_query->get_queried_object_id() )
			return;
		$link = get_permalink( $id );
  	// check if this is the actual gallery page
  	if ( $this->is_gallery() ) {
  		// validate and clean up the gallery query 
	  	$this->valid();
	  	// the base link is our gallery page link as given in ace gallery settings
	  	$link = trailingslashit( $this->get_option( 'gallery_prev' ) );
	  	// check if we are displaying a folder or image
			if ( isset( $this->file ) ) {
				// compile canonical link 
				if ( 'TRUE' != $this->get_option('use_permalinks') ) {			
					$link = add_query_arg( 'file', $this->file, $link );	
				} else {
					$link .= $this->file;
				}			
				$link = trailingslashit( $link );		
			}
		} 
		
		// pages should also be indexed
		if ( !isset( $ace_paged ) )
			$ace_paged = ( isset( $_REQUEST['ace_paged'] ) ) ? intval( $_REQUEST['ace_paged'] ) : 0;
		else 
			$ace_paged = intval( $ace_paged );	
		if ( !isset( $ace_pagei ) )
			$ace_pagei = ( isset( $_REQUEST['ace_pagei'] ) ) ? intval( $_REQUEST['ace_pagei'] ) : 0;
		else 
			$ace_pagei = intval( $ace_pagei );				 
		
		if ( $ace_pagei > 1 )
			$link = add_query_arg( 'ace_pagei', $ace_pagei, $link );
		if ( $ace_paged > 1 )
		  $link = add_query_arg( 'ace_paged', $ace_paged, $link );
		  
		echo "<link rel='canonical' href='$link' />\n";
	} 
  
} // AceFrontend;


/**
 * AceFrontendFolder
 * Holds all folder functions for Frontend
 * 
 * @package Ace Gallery
 * @subpackage Frontend
 * @author Marcel Brinkkemper
 * @copyright 2010-2011 Christopher
 * @since 0.16.0
 * @access public
 */
class AceFrontendFolder extends AceFolder {
  
  /**
   * AceFrontendFolder::load()
   * Loads images without the folder icon image
   * 
   * @param string $what
   * @see AceFolder::load()
   * @return void
   */
  function load( $what = 'images' ) {
    global $ace_gallery;
    AceFolder::load( $what );    
    if (  ( 'icon' == $ace_gallery->get_option( 'folder_image' ) ) && ( 0 < count( $this->list ) ) ) {
      foreach( $this->list as $key_i=>$image  ) {
        if ( $this->is_folder_icon( $image->image ) ) {          
          unset( $this->list[$key_i] );
          $this->list = array_values( $this->list ); 
        }
      }
    } 
  }
  
  /**
   * AceFrontendFolder::do_slideshow()
   * 
   * @since 1.1.0
	 * @param string $display What to display in the slide show. Can be either 'slide', 'thumb', or 'image'
   * @param AceFrontendFolder $folder
   * @return string html for slideshow
   */
  function do_slideshow( $display='slide' ) {
  	global $ace_gallery;
  	  	
  	$slideshow = '<div class="ace_loading">Loading...</div>';
    $i= 0;     
    while ( $i < count( $this->list ) ) {        
      $slide = $this->list[$i];     
      $img_link = $slide->on_click();
     	$rel = ( '' != $img_link['rel'] ) ? ' rel="' . $img_link['rel'] . '"' : '';   	
      $img_src = $slide->src();        
      $atitle = '';
      $adescription = '';      
      if ( '' != $slide->title ) {
        $atitle = '<h3>' . htmlspecialchars( strip_tags( $slide->title), ENT_QUOTES ) . '</h3>'; 
      } 
      if ( '' != $slide->description ) {
        $adescription = '<p>' .  htmlspecialchars( strip_tags( $slide->description ), ENT_QUOTES )  . '</p>';
      }   
      $slideshow .= '<a id="' . $img_link['id'] . '_' . $ace_gallery->nr_shows . '" href="' . $img_link['href'] . '" class="' . $img_link['class'] . '"' . $rel . '>';
      $slideshow .=  '<img src="' . $img_src . '" title="' . $slide->title(). '" alt="' . $slide->alt(). '" rel="'.  $atitle . $adescription . '" /></a>';
      $i++;
    }  		
    $style = ( 'thumb' == $display ) ? ' style = "height:0px"' : '';
    $slideshow .=  '<div class="sstitle"' . $style . '></div>';
    
    return $slideshow;
  }
  
   
  /**
   * AceFrontendFolder::slideshow()
   * 
   * @param string $display. What to display in the slide show. Can be either 'slide', 'thumb', or 'image'
   * @return void
   */
  function slideshow( $display='slide' ) {		
  	global $ace_gallery;
  	
  	if ( ! $ace_gallery->access_check( $this ) ) 
  		return;
  
    if ( '' == $ace_gallery->get_option( 'enable_slide_show' ) ) return;
    
    
  	if ( ( 'image' == $display ) && ( $ace_gallery->get_option( 'disable_full_size' ) == "TRUE" ) ) {
      $display = 'slide';
  	}
  	 				
		$this->load( $display . 's' );
		
		if ( 0 == count( $this->list ) )
			return;
		if ( 1 == count( $this->list ) ) {
			$image = $this->list[0];
			$this->show_slide( $image->image );
			return;	
		}			
				
		$show_class = ( $display == 'thumb' )? 'ace_sideshow' : ''; 
		?>
		<div class="ace_slideshow <?php echo $show_class; ?>" id="ace_slideshow_<?php $ace_gallery->nr_shows++; echo $ace_gallery->nr_shows; ?>" >
			<?php echo apply_filters( 'ace_slideshow', $this->do_slideshow( $display ), $this  ); ?>	
    </div>
    <?php      
  }
  
  /**
   * AceFrontendFolder::thumb_image()
   * 
   * @since 1.1.0
   * @param AceThumb $image
   * @return string code for thumbnail image
   */
  function thumb_image( $image ) {  	
    global $post, $ace_gallery; 
    $onclick = $image->on_click();	
		$rel = ( '' != $onclick['rel'] ) ? 'rel="' . $onclick['rel'] . '"' : '';   	
    $class= 'thumb';
    if ( 'TRUE' != $ace_gallery->get_option( 'enable_cache' )  || 
			( ( 'TRUE' == $ace_gallery->get_option( 'async_cache' ) ) 
				&& ! file_exists( $image->loc() ) ) ) {
			$class .= ' ace_ajax';	
		}	
		$postid = is_object ( $post ) ? $post->ID : $ace_gallery->get_option( 'gallery_id' ); 
    return sprintf( '<div class="ace_thumb_image" style="margin-left: -8px;margin-bottom: -10px;"><a id="%s_%s" href="%s" class="%s" %s title="%s" ><img class="%s" src="%s" alt="%s" /></a></div>',          
      $onclick['id'],
      $postid,
      $onclick['href'],
      $onclick['class'],
			$rel,
      $onclick['title'],
      $class,
      $image->src(),
      $image->alt()  
    );    
  }
  
  /**
   * AceFrontendFolder::thumb_title()
   * 
   * @uses apply_filters
   * @since 1.1.0
   * @param AceThumb $image
   * @return string html code of title
   */
  function thumb_title( $image ) {
    global $ace_gallery;    				  
    $thumb_title = '<div class="ace_thumb_title">';
    $title = $image->title(); 
		$max_length = (int) $ace_gallery->get_option( 'titles_length' );
		if ( '0' != $ace_gallery->get_option( 'titles_length' ) )  {
			if ( strlen( $title ) > $max_length ) {
			  strip_tags( $title );
				$title = substr( $title, 0, $max_length - 1 ) . '&hellip;';
			}	
		}
    $thumb_title .= sprintf( '<span title="%s" >%s</span>',
      $image->title(),
      ace_html( $title ) 
    );  		
    $thumb_title .= '</div><hr style="color:#ccc;height:1px;margin: 0 5px 0 0;">';
	  
    if ( ( 'TRUE' == $ace_gallery->get_option( 'thumb_description' ) ) ) {
    	if ( '' != $image->description )
	      $thumb_title .= sprintf( '<div class="thumb_description" style="font-size:12px;margin-top:10px;"><p>%s...</p></div>',
	        ace_html( substr($image->description(),0,100) )
	      );
      $thumb_title .= apply_filters( 'ace_thumb_description', '', $image );
    }
    return $thumb_title;  
  }
  
  /**
   * AceFrontendFolder::folder_header()
   * the header above the thumbnails
   * 
   * @since 1.1.0
   * @uses apply_filters()
   * @return string html code of header
   */
  function folder_header() {
    $thumbs_folder_header =  sprintf( '<div class="folder_title"><h3>%s</h3></div>%s',
      apply_filters( 'ace_folder_title', ace_html( $this->title() ), $this ),
      ( '' != $this->description ) ? sprintf( '<div class="folder_description"><p>%s</p></div>', ace_html( $this->description() ) ) : ''
    );
    return apply_filters( 'ace_thumbs_folder_header', $thumbs_folder_header, $this );
  }
  
  /**
   * AceFrontendFolder::thumbs_view()
   * returns html of folder thumbnails list
   * 
   * @since 1.1.0
   * @param integer $start
   * @param integer $end
   * @return string html code of the thumbnails list
   */
  function thumbs_view( $start, $end ) {
    global $ace_gallery;
			
    $do_title = ( '-1' != $ace_gallery->get_option( 'titles_length' ) );
    
    $folder_class = sanitize_title( $this->curdir );
    
    $thumbs_view = "<div class='ace_thumb_view $folder_class'>\n";
    
    if ( 0 < count( $this->list ) ) {
      $thumbs_view .= "\n<ul class='acei-list'>\n";	
  		for ( $i = $start - 1; $i < $end; $i++ ) { // main cycle        
        $image = $this->list[$i];
        $thumbs_view .= '<li class="acei-item"><div class="ace_thumb">';      
        $thumbs_view .= $this->thumb_image( $image );          
    		if ( $do_title )				  
          $thumbs_view .= $this->thumb_title( $image );        			
        $thumbs_view .= apply_filters( 'ace_frontend_thumbnail', '', $image );
        $thumbs_view .= "</div></li>\n";
      }                      
      $thumbs_view .= '</ul>';
    }
    $thumbs_view .= "</div>\n";    
    return $thumbs_view;
  }
  
  /**
   * AceFrontendFolder::thumbs_table()
   * returns html of folder thumbnails table
   * 
   * @since 1.1.0
   * @deprecated after 1.1.0, this function will no longer be updated
   * @param integer $start
   * @param integer $end
   * @param integer $columns
   * @return string html code of the thumbnails table
   */
  function thumbs_table( $start, $end, $columns=1 ) {    	
    global $ace_gallery;
    
    $columns = ( 0 == $columns ) ? 1 : $columns;    
		$col_count = 0;    
		$do_title = ( '-1' != $ace_gallery->get_option( 'titles_length' ) );
    
    $thumbs_table = "<table class='ace_thumb_view'>\n<tbody>\n";
    if ( 0 < count( $this->list ) ) {	
			for ( $i = $start - 1; $i < $end; $i++ ) { // main cycle        
        $image = $this->list[$i];
        
        $thumbs_table .= '<td><div class="ace_thumb">';        
        $thumbs_table .= $this->thumb_image( $image );          
				if ( $do_title )				  
          $thumbs_table .= $this->thumb_title( $image );   
        			
        $thumbs_table .= apply_filters( 'ace_frontend_thumbnail', '', $image );                           
        $thumbs_table .= '</div></td>';
        
				$col_count++;
        if ( ( $col_count / $columns ) == intval( $col_count / $columns ) ) {
          $thumbs_table .= '</tr>';
          if ( $i+1 < $end ) {
            $thumbs_table .= "\n<tr>";
          }
        }				
		  } // main cycle
    }	
    if ( ( $col_count / $columns ) != intval( $col_count / $columns )  ) {   
      while ( ( $col_count / $columns ) != intval( $col_count / $columns )  ) {
        $thumbs_table .= '<td></td>';
        $col_count++;
      }
      $thumbs_table .= "</tr>\n";
    }
    $thumbs_table .= "</tbody>\n</table>\n";
    return $thumbs_table;
  }
  
  /**
   * AceFrontendFolder::empty_link()
   * echo a link to an image
   * 
   * @since 1.1.0
   * @uses esc_attr() 
   * @param AceThumb $image
   * @return void
   */
  function empty_link( $image ) { 
  	$onclick = $image->on_click();  
  	$rel = ( '' != $onclick['rel'] ) ? 'rel="' . $onclick['rel'] . '"' : '';   	
    printf ( '<a id="%s" href="%s" class="%s" %s title="%s"></a>',
      $onclick['id'],
      $onclick['href'],
      $onclick['class'],
      $rel,
      esc_attr( $image->title() )
    );       
  } 
  
  /**
   * AceFrontendFolder::show_thumbs()
   * Display thumnails of images in the folder
   * 
   * @uses user_logged_in()
   * @uses current_user_can()
   * @param integer $perpage = number of thumbnails to show
   * @param integer $columns = number of columns in the image table
   * @param string $paging = show images over more pages if number of images is larger than $perpage
   * @return void
   */
  function show_thumbs( $perpage = 0, $columns = 1, $paging = 'true' ) {		
    global $ace_gallery, $ace_pagei, $post;
    if ( ! $ace_gallery->access_check( $this ) ) 
			return;
			        
    $foldervalue = urlencode( $this->curdir );
        
    $thumbs_page = $ace_gallery->get_option( 'thumbs_page' );
		if ( $perpage !=  $thumbs_page ) {
			$ace_gallery->change_option( 'thumbs_page', $perpage );
 		}
 		
    if ( ! isset( $this->list )  ) {
      $this->load( 'thumbs' );
    }
    $start = 1;      
    $end = count( $this->list );
		?> 
		<div class="thumb_images">
		<?php  
    if ( 0 < $perpage) {    
      $total_pages = ceil( count( $this->list ) / $perpage ); 
      $query_var = 'ace_pagei';
      if ( isset ( $ace_pagei ) ) {
        $current = max( 1, $ace_pagei);
      } else {      
        $current = isset( $_REQUEST[$query_var] ) ? absint( $_REQUEST[$query_var] ) : 0;	
        $current = min( max( 1, $current ), $total_pages );
      }
      $start = ( $current - 1 ) * $perpage + 1;
      $end = min( count( $this->list ), $current * $perpage);
      if ( ( $paging == true ) && ( $perpage < count( $this->list ) ) && ( $perpage != 0 ) ) {
      	$ajax_nonce = wp_create_nonce( 'show_thumbs' );
        printf( '<form name="thumbs_page" action="%s" method="post">', $this->uri() );                 
        printf( '<input type="hidden" name="current" value="%s" />', $current );
        printf( '<input type="hidden" name="last_page" value="%s" />', ceil( count( $this->list ) / $perpage ) );
        printf( '<input type="hidden" name="folder" value="%s" />', $foldervalue ); 
        printf( '<input type="hidden" name="virtual" value="%s" />', urlencode( $ace_gallery->virtual_root ) ); 
        printf( '<input type="hidden" name="perpage" value="%s" />', $perpage );
        printf( '<input type="hidden" name="columns" value="%s" />', $columns );      
				printf( '<input type="hidden" name="post_id" value="%s" />', $post->ID );
				printf( '<input type="hidden" name="ajax_nonce" value="%s" />', $ajax_nonce );  
        printf( '<input type="hidden" name="request_uri" value="%s" />', remove_query_arg( 'ace_pagei', $_SERVER['REQUEST_URI'] ) );
      }    
    }		  		
		if( 0 < count( $this->list ) ) {		
			$i = 1;   
			if ( in_array( $ace_gallery->get_option( 'on_thumb_click' ), array( 'lightslide', 'thickslide', 'lightbox', 'thickbox' ) ) ) {
	      echo '<div style="display:none">';  
				while ( $i < $start ) { // pre cycle of anchor links for slideshow plugins
	        $image = $this->list[$i-1];    ace_db($i-1,'i');		    
	        $this->empty_link( $image );
					$i++;
			
				}
	      echo "</div>\n";
			}
      echo "<div class='ace_thumb_view'>\n";            
      // this is where we actually echo the thumbnail images
      echo ( 'TRUE' == $ace_gallery->get_option( 'table_layout') ) ? $this->thumbs_table( $start, $end, $columns ) : $this->thumbs_view( $start, $end );      
			echo "</div>\n";
			
			$i = $end;    
			if ( in_array( $ace_gallery->get_option( 'on_thumb_click' ), array( 'lightslide', 'thickslide', 'lightbox', 'thickbox' ) ) ) {   
	      echo '<div style="display:none">';  
	      while ($i < count( $this->list ) ) { // post cycle of anchor links for slideshow plugins
	        $image = $this->list[$i];        
	        $this->empty_link( $image );
					$i++;
				}			
	    	echo "</div>\n";
    	}
		} // count images
		if ( 1 < count( $this->list ) ) {	 
	    ?>  
			<div class="buttons">
	    <?php
	    if ( ( 'TRUE' == $ace_gallery->get_option( 'enable_slide_show' ) )  && $ace_gallery->is_gallery() ) {
					?>
			    <a href="<?php echo add_query_arg( 'ace_show', 'true', $this->uri() ); ?>" class="ace_slideshow_button"><?php echo __( 'Slide Show', 'ace-xml-gallery-builder' ); ?></a>
			    <?php      
				}	
			?>	
			</div>       
	    <div class="image_pagination"> <?php      
			if ( ( $paging == true ) && ( $perpage < count( $this->list ) ) && ( $perpage != 0 ) ) { 
 	                    				
	    	echo $ace_gallery->pagination( 'images', $this->list );
	    } 
			?>  	   			 
				<br style="clear:both;" />	   			
	    </div>
	    <?php
	    if ( ( $paging == true ) && ( $perpage < count( $this->list ) ) && ( $perpage != 0 ) )
	    	echo "</form>\n";
    }
    ?>    
    </div>
    <?php		
    $ace_gallery->change_option( 'thumbs_page', $thumbs_page );    
  }
  
  /**
   * AceFrontendFolder::show_slide()
   * Show a single image in slide view
   * 
   * @param string $filename file name of the image to show 
   * @return void
   */
  function show_slide( $filename ) {
    global $ace_gallery, $post; 
    
  	if ( ! $ace_gallery->access_check( $this ) ) 
  		return;

    $this->load('slides'); 		          
		for ( $i = 0; $i < count( $this->list ); $i++ ) {
		  $image = $this->list[$i];        
			if( $image->image == $filename ) {          
				if( 0 == $i ) {  				  
					$previous = end( $this->list );
				} else {
					$previous = $this->list[$i-1];
				}  			 
				if( ( $i + 1 ) == count( $this->list ) ) {
					$next = $this->list[0];
				} else {
					$next = $this->list[$i+1];
				}
				break;
			}
		};
    if ( ! isset($previous) || ! isset($next) ) { 
      esc_html_e( 'Something went wrong displaying the slide', 'ace-xml-gallery-builder' );
      return;
    }
     	 
    ?>    		
    <div class="ace_image">
    <?php
		if ( in_array( $ace_gallery->get_option( 'on_slide_click' ), array( 'lightbox', 'thickbox' ) ) ) { // add links for lightbox
			$j = 0;
			while ( $j < $i ) {
        $dummy = $this->list[$j];
        $onclick = $dummy->on_click();
       	$rel = ( '' != $onclick['rel'] ) ? 'rel="' . $onclick['rel'] . '"' : '';   	
      ?>
        <a id="<?php echo $onclick['id']; ?>" class="ace_dummy <?php echo $onclick['class'] ?>" title="<?php echo $onclick['title']; ?>"  href="<?php echo $onclick['href']; ?>" <?php echo $rel; ?>></a>
      <?php          
				$j++;
			}
		}
		
    $onclick = $image->on_click();
   	$rel = ( '' != $onclick['rel'] ) ? 'rel="' . $onclick['rel'] . '"' : '';   	
    ?>
      <a id="<?php echo $onclick['id'] . $post->ID ?>" class="slide <?php echo $onclick['class']; ?>" href="<?php echo $onclick['href'] ?>" title="<?php echo $onclick['title']; ?>" <?php echo $rel; ?>>
        <img class="slide" id="<?php echo $image->html_id(); ?>" src="<?php echo $image->src(); ?>" alt="<?php echo $image->alt(); ?>" />
      </a>         
    <?php
		
		if ( in_array( $ace_gallery->get_option( 'on_slide_click' ), array( 'lightbox', 'thickbox' ) ) ) { // add links for lightbox
			$j = $i + 1;
			while ( $j < count( $this->list ) ) {
        $dummy = $this->list[$j];
        $onclick = $dummy->on_click();
       	$rel = ( '' != $onclick['rel'] ) ? 'rel="' . $onclick['rel'] . '"' : '';   	
      ?>
        <a id="<?php echo $onclick['id']; ?>" class="ace_dummy <?php echo $onclick['class'] ?>" title="<?php echo $onclick['title']; ?>"  href="<?php echo $onclick['href']; ?>" <?php echo $rel; ?>></a>
      <?php          
				$j++;
			}
		}  	      	
		?>  
		  <div class="title"><?php echo ace_html( $image->title() ); ?>&nbsp;</div>
    <?php  
    if ( '' != $image->description ) {       
    ?>  
      <div class="description"><?php echo ace_html( $image->description() ) ; ?>&nbsp;</div>
    <?php          
    }   
    do_action('ace_frontend_slide', $image );
    ?> </div> <!-- ace image --> <?php
    if ( 1 < count( $this->list ) ){
  		?>  		
      <div class="ace_navigator" style="width:95%">
      <?php if ( ( 'TRUE' == $ace_gallery->get_option( 'enable_slide_show' ) )  && $ace_gallery->is_gallery() ) : ?>
      <a href="<?php echo add_query_arg( 'ace_show', 'true', $this->uri() ); ?>" class="ace_slideshow_button"><?php echo __( 'Slide Show', 'ace-xml-gallery-builder' ); ?></a>
		  <?php
		  endif;
      $page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page',
			esc_attr__( 'Go to the previous slide', 'ace-xml-gallery-builder'  ),
			esc_url( $previous->uri() ),
			'&laquo;'
		  );      
  		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
  			'next-page',
  			esc_attr__( 'Go to the next slide', 'ace-xml-gallery-builder'  ),
  			esc_url( $next->uri() ),
  			'&raquo;'
  		);
      $output = join( "\n", $page_links );

      echo "<div class='tablenav-pages'>$output</div>"; 
      ?>
        <br style="clear: both;"/>
  		</div> 	      
      <?php
		}
		if( 'TRUE' == $ace_gallery->get_option( 'enable_exif' ) ) {
		  $this->show_exif( $filename );
		}  		
  }
  
    
  /**
   * AceFrontendFolder::_photo_getval()
   * Used by AceFrontendFolder::show_exif()
   * Get a value from the exif value array
   * 
   * @param string $image_info
   * @param array $val_array
   * @return string
   */
  function _photo_getval( $image_info, $val_array ) {
    $info_val = 'Unknown';
    foreach( $val_array as $name=>$val ) {
      if ( $name == $image_info ) {
        $info_val = &$val;
        break;
      }
    }
    return $info_val;
  }
    
  /**
   * AceFrontendFolder::show_exif()
   * Show exif code for a jpeg image
   * 
   * @param string $filename
   * @return void
   */
  function show_exif( $filename ) {
    global $ace_gallery;
    $image = $this->single_image( $filename );    
    $pathinfo = pathinfo( $filename );
        
    $imgtype = array( '', 'GIF', 'JPG', 'PNG', 'SWF', 'PSD', 'BMP', 'TIFF(intel byte order)', 'TIFF(motorola byte order)', 'JPC', 'JP2', 'JPX', 'JB2', 'SWC', 'IFF', 'WBMP', 'XBM');

    $orientation = array('', 'top left side', 'top right side', 'bottom right side', 'bottom left side', 'left side top', 'right side top', 'right side bottom', 'left side bottom');

    $resolution_unit = array('', '', 'inches', 'centimeters');

    $ycbcr_positioning = array('', 'the center of pixel array', 'the datum point');
    
    $exposure_program = array(
      __('Not defined', 'ace-xml-gallery-builder' ),
      __( 'Manual', 'ace-xml-gallery-builder' ), 
      __( 'Normal program', 'ace-xml-gallery-builder' ), 
      __( 'Aperture priority', 'ace-xml-gallery-builder' ), 
      __( 'Shutter priority', 'ace-xml-gallery-builder' ), 
      __( 'Creative program (biased toward depth of field)', 'ace-xml-gallery-builder' ), 
      __( 'Action program (biased toward fast shutter speed)', 'ace-xml-gallery-builder' ), 
      __( 'Portrait mode (for closeup photos with the background out of focus)', 'ace-xml-gallery-builder' ), 
      __( 'Landscape mode (for landscape photos with the background in focus)', 'ace-xml-gallery-builder' )
    );

    
    $metering_mode = array(
      '0' => __( 'Unknown', 'ace-xml-gallery-builder' ),
      '1' => __( 'Average', 'ace-xml-gallery-builder' ),
      '2' => __( 'Center Weighted Average', 'ace-xml-gallery-builder' ),
      '3' => __( 'Spot', 'ace-xml-gallery-builder' ),
      '4' => __( 'MultiSpot', 'ace-xml-gallery-builder' ),
      '5' => __( 'Pattern', 'ace-xml-gallery-builder' ),
      '6' => __( 'Partial', 'ace-xml-gallery-builder' ),
      '255' =>__( 'Other Metering Mode', 'ace-xml-gallery-builder' )
    );
    
    $light_source = array(
      '0' => __( 'unknown', 'ace-xml-gallery-builder' ),
      '1' => __( 'Daylight', 'ace-xml-gallery-builder' ),
      '2' => __( 'Fluorescent', 'ace-xml-gallery-builder' ),
      '3' => __( 'Tungsten (incandescent light)', 'ace-xml-gallery-builder' ),
      '4' => __( 'Flash', 'ace-xml-gallery-builder' ),
      '9' => __( 'Fine weather', 'ace-xml-gallery-builder' ),
      '10' => __( 'Cloudy weather', 'ace-xml-gallery-builder' ),
      '12' => __( 'Daylight fluorescent (D 5700 – 7100K)', 'ace-xml-gallery-builder' ),
      '13' => __( 'Day white fluorescent (N 4600 – 5400K)', 'ace-xml-gallery-builder' ),
      '14' => __( 'Cool white fluorescent (W 3900 – 4500K)', 'ace-xml-gallery-builder' ),
      '15' => __( 'White fluorescent (WW 3200 – 3700K)', 'ace-xml-gallery-builder' ),
      '17' => __( 'Standard light A', 'ace-xml-gallery-builder' ),
      '18' => __( 'Standard light B', 'ace-xml-gallery-builder' ),
      '19' => __( 'Standard light C', 'ace-xml-gallery-builder' ),
      '20' => __( 'D55', 'ace-xml-gallery-builder' ),
      '21' => __( 'D65', 'ace-xml-gallery-builder' ),
      '22' => __( 'D75', 'ace-xml-gallery-builder' ),
      '23' => __( 'D50', 'ace-xml-gallery-builder' ),
      '24' => __( 'ISO studio tungsten', 'ace-xml-gallery-builder' ),
      '255' => __( 'other light source', 'ace-xml-gallery-builder' )
    );
    
    $flash = array(
      '0' => __( 'Flash did not fire.', 'ace-xml-gallery-builder' ),
      '1' => __( 'Flash fired.', 'ace-xml-gallery-builder' ),
      '5' => __( 'Strobe return light not detected.', 'ace-xml-gallery-builder' ),
      '7' => __( 'Strobe return light detected.', 'ace-xml-gallery-builder' ),
      '9' => __( 'Flash fired, compulsory flash mode', 'ace-xml-gallery-builder' ),
      '13' => __( 'Flash fired, compulsory flash mode, return light not detected', 'ace-xml-gallery-builder' ),
      '15' => __( 'Flash fired, compulsory flash mode, return light detected', 'ace-xml-gallery-builder' ),
      '16' => __( 'Flash did not fire, compulsory flash mode', 'ace-xml-gallery-builder' ),
      '24' => __( 'Flash did not fire, auto mode', 'ace-xml-gallery-builder' ),
      '25' => __( 'Flash fired, auto mode', 'ace-xml-gallery-builder' ),
      '29' => __( 'Flash fired, auto mode, return light not detected', 'ace-xml-gallery-builder' ),
      '31' => __( 'Flash fired, auto mode, return light detected', 'ace-xml-gallery-builder' ),
      '32' => __( 'No flash function', 'ace-xml-gallery-builder' ),
      '65' => __( 'Flash fired, red-eye reduction mode', 'ace-xml-gallery-builder' ),
      '69' => __( 'Flash fired, red-eye reduction mode, return light not detected', 'ace-xml-gallery-builder' ),
      '71' => __( 'Flash fired, red-eye reduction mode, return light detected', 'ace-xml-gallery-builder' ),
      '73' => __( 'Flash fired, compulsory flash mode, red-eye reduction mode', 'ace-xml-gallery-builder' ),
      '77' => __( 'Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected', 'ace-xml-gallery-builder' ),
      '79' => __( 'Flash fired, compulsory flash mode, red-eye reduction mode, return light detected', 'ace-xml-gallery-builder' ),
      '89' => __( 'Flash fired, auto mode, red-eye reduction mode', 'ace-xml-gallery-builder' ),
      '93' => __( 'Flash fired, auto mode, return light not detected, red-eye reduction mode', 'ace-xml-gallery-builder' ),
      '95' => __( 'Flash fired, auto mode, return light detected, red-eye reduction mode', 'ace-xml-gallery-builder' )
    );
    
    $exif = @exif_read_data( $ace_gallery->root . $this->curdir . $image->image, 0, true );  
    $img_info = array ();
    if ( isset( $exif['FILE']['FileName'] ) ) 
      $img_info[__('FileName', 'ace-xml-gallery-builder')] = $exif['FILE']['FileName'];  
    if ( isset( $exif['FILE']['FileType'] ) )   
      $img_info[__('FileType', 'ace-xml-gallery-builder')] =  $imgtype[$exif['FILE']['FileType']];
    if ( isset( $exif['FILE']['MimeType'] ) ) 
      $img_info[__('MimeType', 'ace-xml-gallery-builder')] =  $exif['FILE']['MimeType']; 
    if ( isset( $exif['FILE']['FileSize'] ) ) 
      $img_info[__('FileSize', 'ace-xml-gallery-builder')] = ( floor( $exif['FILE']['FileSize'] / 1024 * 10 ) /10 ) . 'KB';
    if ( isset( $exif['FILE']['FileDateTime'] ) )       
      $img_info[__('FileDateTime', 'ace-xml-gallery-builder')] = date( 'Y-m-d  H:i:s', $exif['FILE']['FileDateTime'] );
    if ( isset( $exif['IFD0']['Artist'] ) ) 
      $img_info[__('Artist', 'ace-xml-gallery-builder')] = $exif['IFD0']['Artist']; 
    if ( isset( $exif['IFD0']['Make'] ) )
      $img_info[__('Make', 'ace-xml-gallery-builder')] = $exif['IFD0']['Make']; 
    if ( isset( $exif['IFD0']['Model'] ) )
      $img_info[__('Model', 'ace-xml-gallery-builder')] = $exif['IFD0']['Model']; 
    if ( isset( $exif['IFD0']['DateTime'] ) ) 
      $img_info[__('DateTime', 'ace-xml-gallery-builder')] = $exif['IFD0']['DateTime']; 
    if ( isset( $exif['EXIF']['ExifVersion'] ) ) 
      $img_info[__('ExifVersion', 'ace-xml-gallery-builder')] = $exif['EXIF']['ExifVersion']; 
    if ( isset( $exif['EXIF']['DateTimeOriginal'] ) ) 
      $img_info[__('DateTimeOriginal', 'ace-xml-gallery-builder')] = $exif['EXIF']['DateTimeOriginal']; 
    if ( isset( $exif['EXIF']['DateTimeDigitized'] ) ) 
      $img_info[__('DateTimeDigitized', 'ace-xml-gallery-builder')] = $exif['EXIF']['DateTimeDigitized']; 
    if ( isset( $exif['COMPUTED']['Height'] ) ) 
      $img_info[__('Height', 'ace-xml-gallery-builder')] = $exif['COMPUTED']['Height'] . 'px'; 
    if ( isset( $exif['COMPUTED']['Width'] ) ) 
      $img_info[__('Width', 'ace-xml-gallery-builder')] = $exif['COMPUTED']['Width'] . 'px'; 
    if ( isset( $exif['EXIF']['CompressedBitsPerPixel'] ) ) 
      $img_info[__('CompressedBitsPerPixel', 'ace-xml-gallery-builder')] = $exif['EXIF']['CompressedBitsPerPixel'] .  __( ' Bits/Pixel', 'ace-xml-gallery-builder' );
    $img_info[__('FocusDistance', 'ace-xml-gallery-builder')] = isset( $exif['COMPUTED']['FocusDistance'] ) ? $exif['COMPUTED']['FocusDistance'] . 'm' : NULL;
    $img_info[__('FocalLength', 'ace-xml-gallery-builder')] = isset( $exif['EXIF']['FocalLength'] ) ? $exif['EXIF']['FocalLength'] . 'mm' : NULL; 
    $img_info[__('FocalLengthIn35mmFilm', 'ace-xml-gallery-builder')] = isset( $exif['EXIF']['FocalLengthIn35mmFilm'] ) ? $exif['EXIF']['FocalLengthIn35mmFilm'] . 'mm' : NULL; 
    if ( isset( $exif['EXIF']['ColorSpace'] ) ) 
      $img_info[__('ColorSpace', 'ace-xml-gallery-builder')] = $exif['EXIF']['ColorSpace'] == 1 ? 'sRGB' :  __('Uncalibrated', 'ace-xml-gallery-builder' );
    if ( isset( $exif['IFD0']['ImageDescription'] ) ) 
      $img_info[__('ImageDescription', 'ace-xml-gallery-builder')] = $exif['IFD0']['ImageDescription']; 
    if ( isset( $exif['IFD0']['Orientation'] ) ) 
      $img_info[__('Orientation', 'ace-xml-gallery-builder')] = $orientation[$exif['IFD0']['Orientation']]; 
    if ( isset( $exif['IFD0']['XResolution'] ) ) 
      $img_info[__('XResolution', 'ace-xml-gallery-builder')] = $exif['IFD0']['XResolution'] . $resolution_unit[$exif['IFD0']['ResolutionUnit']]; 
    if ( isset( $exif['IFD0']['YResolution'] ) ) 
      $img_info[__('YResolution', 'ace-xml-gallery-builder')] = $exif['IFD0']['YResolution'] . $resolution_unit[$exif['IFD0']['ResolutionUnit']]; 
    if ( isset( $exif['IFD0']['Software'] ) ) 
      $img_info[__('Software', 'ace-xml-gallery-builder')] = utf8_encode( $exif['IFD0']['Software'] ); 
    if ( isset( $exif['IFD0']['YCbCrPositioning'] ) ) 
      $img_info[__('YCbCrPositioning', 'ace-xml-gallery-builder')] = $ycbcr_positioning[$exif['IFD0']['YCbCrPositioning']]; 
    if ( isset( $exif['IFD0']['Copyright'] ) ) 
      $img_info[__('Copyright', 'ace-xml-gallery-builder')] = $exif['IFD0']['Copyright'];  
    if ( isset( $exif['COMPUTED']['Copyright.Photographer'] ) )
      $img_info[__('Photographer', 'ace-xml-gallery-builder')] = $exif['COMPUTED']['Copyright.Photographer']; 
    if ( isset( $exif['COMPUTED']['Copyright.Editor'] ) ) 
      $img_info[__('Editor', 'ace-xml-gallery-builder')] = $exif['COMPUTED']['Copyright.Editor']; 
    if ( isset( $exif['EXIF']['ExifVersion'] ) ) 
      $img_info[__('ExifVersion', 'ace-xml-gallery-builder')] = $exif['EXIF']['ExifVersion']; 
    if ( isset( $exif['EXIF']['FlashPixVersion'] ) ) 
      $img_info[__('FlashPixVersion', 'ace-xml-gallery-builder')] = __('Ver', 'ace-xml-gallery-builder') . number_format( $exif['EXIF']['FlashPixVersion']/100, 2 );    
    if ( isset( $exif['EXIF']['ApertureValue'] ) ) 
      $img_info[__('ApertureValue', 'ace-xml-gallery-builder')] = $exif['EXIF']['ApertureValue']; 
    if ( isset( $exif['EXIF']['ShutterSpeedValue'] ) ) 
      $img_info[__('ShutterSpeedValue', 'ace-xml-gallery-builder')] = $exif['EXIF']['ShutterSpeedValue']; 
    if ( isset( $exif['COMPUTED']['ApertureFNumber'] ) ) 
      $img_info[__('ApertureFNumber', 'ace-xml-gallery-builder')] = $exif['COMPUTED']['ApertureFNumber']; 
    if ( isset( $exif['EXIF']['MaxApertureValue'] ) ) 
      $img_info[__('MaxApertureValue', 'ace-xml-gallery-builder')] = 'F' . $exif['EXIF']['MaxApertureValue']; 
    if ( isset( $exif['EXIF']['ExposureTime'] ) ) 
      $img_info[__('ExposureTime', 'ace-xml-gallery-builder')] = $exif['EXIF']['ExposureTime']; 
    if ( isset( $exif['EXIF']['FNumber'] ) ) 
      $img_info[__('F-Number', 'ace-xml-gallery-builder')] = $exif['EXIF']['FNumber']; 
    if ( isset( $exif['EXIF']['MeteringMode'] ) ) 
      $img_info[__('MeteringMode', 'ace-xml-gallery-builder')] = $this->_photo_getval( $exif['EXIF']['MeteringMode'], $metering_mode ); 
    if ( isset( $exif['EXIF']['LightSource'] ) ) 
      $img_info[__('LightSource', 'ace-xml-gallery-builder')] = $this->_photo_getval( $exif['EXIF']['LightSource'], $light_source ); 
    if ( isset( $exif['EXIF']['Flash'] ) ) 
      $img_info[__('Flash', 'ace-xml-gallery-builder')] = $this->_photo_getval( $exif['EXIF']['Flash'], $flash ); 
    if ( isset( $exif['EXIF']['ExposureMode'] ) ) 
      $img_info[__('ExposureMode', 'ace-xml-gallery-builder')] = $exif['EXIF']['ExposureMode'] == 1 ? __('Manual exposure', 'ace-xml-gallery-builder' ) : __('Auto exposure', 'ace-xml-gallery-builder' ); 
    if ( isset( $exif['EXIF']['WhiteBalance'] ) ) 
      $img_info[__('WhiteBalance', 'ace-xml-gallery-builder')] = $exif['EXIF']['WhiteBalance'] == 1 ?  __('Manual white balance', 'ace-xml-gallery-builder'  ) :  __('Auto white balance', 'ace-xml-gallery-builder'  ); 
    if ( isset( $exif['EXIF']['ExposureProgram'] ) ) 
      $img_info[__('ExposureProgram', 'ace-xml-gallery-builder')] = $exposure_program[$exif['EXIF']['ExposureProgram']]; 
    if ( isset( $exif['EXIF']['ExposureBiasValue'] ) ) 
      $img_info[__('ExposureBiasValue', 'ace-xml-gallery-builder')] = $exif['EXIF']['ExposureBiasValue'] . __('EV', 'ace-xml-gallery-builder'); 
    if ( isset( $exif['EXIF']['ISOSpeedRatings'] ) ) 
      $img_info[__('ISOSpeedRatings', 'ace-xml-gallery-builder')] = $exif['EXIF']['ISOSpeedRatings']; 
    if ( isset( $exif['EXIF']['ComponentsConfiguration'] ) ) 
      $img_info[__('ComponentsConfiguration', 'ace-xml-gallery-builder')] = bin2hex( $exif['EXIF']['ComponentsConfiguration'] ) == '01020300' ? 'YCbCr' : 'RGB';      
    if ( isset( $exif['COMPUTED']['UserCommentEncoding'] ) ) 
      $img_info[__('UserCommentEncoding', 'ace-xml-gallery-builder')] = $exif['COMPUTED']['UserCommentEncoding']; 
    if ( isset( $exif['COMPUTED']['UserComment'] ) ) 
      $img_info[__('UserComment', 'ace-xml-gallery-builder')] = $exif['COMPUTED']['UserComment'];      
    if ( isset( $exif['EXIF']['ExifImageLength'] ) ) 
      $img_info[__('ExifImageLength', 'ace-xml-gallery-builder')] = $exif['EXIF']['ExifImageLength']; 
    if ( isset( $exif['EXIF']['ExifImageWidth'] ) ) 
      $img_info[__('ExifImageWidth', 'ace-xml-gallery-builder')] = $exif['EXIF']['ExifImageWidth']; 
    if ( isset( $exif['EXIF']['FileSource'] ) ) 
      $img_info[__('FileSource', 'ace-xml-gallery-builder')] = bin2hex( $exif['EXIF']['FileSource'] ) == 0x03 ? 'DSC' : __('unknown', 'ace-xml-gallery-builder'  ) ; 
    if ( isset( $exif['EXIF']['SceneType'] ) ) 
      $img_info[__('SceneType', 'ace-xml-gallery-builder')] = bin2hex( $exif['EXIF']['SceneType'] ) == 0x01 ? __('A directly photographed image', 'ace-xml-gallery-builder'  ) :  __('unknown', 'ace-xml-gallery-builder'  ) ; 
    if ( isset( $exif['COMPUTED']['Thumbnail.FileType'] ) ) 
      $img_info[__('Thumbnail.FileType', 'ace-xml-gallery-builder')] = $exif['COMPUTED']['Thumbnail.FileType']; 
    if ( isset( $exif['COMPUTED']['Thumbnail.MimeType'] ) ) 
      $img_info[__('Thumbnail.MimeType', 'ace-xml-gallery-builder')] = $exif['COMPUTED']['Thumbnail.MimeType'];   
    ?>    
    <div class="imagedata">
      <p class="exifheader"><?php esc_html_e( 'Image data', 'ace-xml-gallery-builder' ); ?></p>
      <?php
      if ( $exif ) {        
      ?>
      <table class="imagedatatable">
        <tbody>
          <tr>
            <th scope="row"><?php esc_html_e( 'Date', 'ace-xml-gallery-builder' ); ?></th>
            <td><?php echo $img_info[__('FileDateTime', 'ace-xml-gallery-builder')]; ?></td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e( 'Height' ); ?></th>
            <td><?php echo $img_info[__('Height', 'ace-xml-gallery-builder')]; ?></td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e( 'Width' ); ?></th>            
            <td><?php echo $img_info[__('Width', 'ace-xml-gallery-builder')]; ?></td>
          </tr>
          <?php if ( isset( $img_info[__('Make', 'ace-xml-gallery-builder')] ) && isset( $img_info[__('Model', 'ace-xml-gallery-builder')]) ) { ?>
          <tr>
            <th scope="row"><?php esc_html_e( 'Camera' ); ?></th>            
            <td><?php echo $img_info[__('Make', 'ace-xml-gallery-builder')] . ' - ' . $img_info[__('Model', 'ace-xml-gallery-builder')]; ?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
      <script type="text/javascript">function showExif(){jQuery('#all_exif').show();}</script>
      <a href="javascript:showExif();"><?php esc_html_e( 'Show all Exif data', 'ace-xml-gallery-builder' ); ?></a>
      <table id="all_exif">
        <tbody>
        <?php 
        foreach( $img_info as $name => $val ) {
          if ( $val ) {
          ?>
          <tr>
            <th scope="row"><?php echo $name; ?></th>
            <td><?php echo $val; ?></td>
          </tr>
          <?php
          }
        }
        ?>
        </tbody>
      </table>   
    <?php
    } else {
      list($width, $height, $type, $attr) = getimagesize( $ace_gallery->root . $this->curdir . $image->image );
    ?> 
      <table class="imagedatatable">
        <tbody>
          <tr>
            <th scope="row"><?php esc_html_e( 'Date', 'ace-xml-gallery-builder' ); ?></th>
            <td><?php echo date( get_option('date_format' ), filemtime( $ace_gallery->root . $this->curdir . $image->image ) ); ?></td>
            <th scope="row"><?php esc_html_e( 'Height' ); ?></th>
            <td><?php echo $height . 'px'; ?></td>
            <th scope="row"><?php esc_html_e( 'Width' ); ?></th>            
            <td><?php echo $width . 'px'; ?></td>
          </tr>
        </tbody>
      </table>   
    <?php
    }
    ?>
  </div>
  <?php  
  }
  
} // AceFrontendFolder
 
?>