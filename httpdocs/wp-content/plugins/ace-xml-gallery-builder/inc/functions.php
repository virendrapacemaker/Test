<?php
/**
 * Template tags and functions for Ace Gallery
 * 
 * @package Ace-Gallery  
 * @author Marcel Brinkkemper
 * @copyright 2008-2012 Marcel Brinkkemper
 */
 
 
/**
 * ace_list_folders()
 * 
 * @param string $title
 * @return
 */
function ace_list_folders( $title ) {
  global $ace_gallery;
  if ( ! isset( $ace_gallery ) ) {
    return false;
  }
  $disp =  ( 'TRUE' == $ace_gallery->get_option( 'use_folder_titles' ) ) ? 'title' : 'dirname';
  if ( $title != '' ) {
    ?>
    <h2><?php echo $title; ?></h2>
    <?php
  }
  $folders = $ace_gallery->folders( 'root', 'visible' );
  if ( 0 < count( $folders ) ) {
    ?><ul><?php
    foreach( $folders as $folder ) {
      $folder->open();
      ?>
      <li><a href="<?php echo $folder->uri( 'widget' ); ?>" title="<?php echo $folder->title(); ?>"><?php echo ace_html( $folder->title() ); ?></a>
      <?php $folder->list_folders( 'visible', $disp, 'widget' ); ?>
      </li>
      <?php 
    }
    ?></ul><?php
  }
}
 
 /**
 * ace_random_image() template tag to display random image
 * 
 * @param string $title
 * @param string $count
 * @param string $folder
 * @param bool $sub
 * @return
 */ 
function ace_random_image( $title, $count='1', $folder='', $sub=true ) {
  global $ace_gallery;
  if ( ! isset( $ace_gallery ) ) {
    return false;
  }
  if ( '' == $folder ) $sub = true;
  $substr = $sub ? 'subfolders' : 'root';
  $count = intval( $count ); 
  $image_list = $ace_gallery->random_image( $folder, $substr, $count );
  if ( $title != '' ) {
    ?>
    <h2><?php echo $title; ?></h2>
    <?php
  }
  ?>
    <div class="ace_sidebox">
  <?php 
  foreach ( $image_list as $thumb ) {
    $onclick = $thumb->on_click( 'widget' );
   	$rel = ( '' != $onclick['rel'] ) ? 'rel="' . $onclick['rel'] . '"' : '';   	
		$class= 'thumb';  		  
  	if ( ! file_exists( $thumb->loc() ) ) 
  		$class .= ' ace_ajax';
    ?>
    <p class="ace_thumb_image">
      <a href="<?php echo $onclick['href']; ?>" title="<?php echo $thumb->title() ?>" <?php echo $rel; ?> class="<?php echo $onclick['class'] ?>"><img class="<?php echo $class; ?>" src="<?php echo $thumb->src(); ?>" alt="<?php echo $thumb->alt(); ?>" /></a>
    </p>
    <?php
  }
  ?>
    </div>
  <?php
}


/**
 * ace_random_slideshow() slide show of random images (as a widget) in sidebar
 * 
 * @param string $title
 * @param string $count
 * @param string $display
 * @param string $folder
 * @param bool $sub
 * @return
 */
function ace_random_slideshow($title, $count='2', $display='5', $folder='', $sub=true ) {
   global $ace_gallery;   
  if ( ! isset( $ace_gallery ) ) return false;  	
  if ( '' == $ace_gallery->get_option('enable_slide_show') ) return false;
  if ( '' == $folder ) $sub = true;
  $substr = $sub ? 'subfolders' : 'root'; 
  $image_list = $ace_gallery->random_image( $folder, $substr, intval( $count ) );
  
  $min_width = $ace_gallery->get_option( 'thumbwidth') . 'px';
  $min_height = $ace_gallery->get_option( 'thumbheight') . 'px';
  if ( $title != '' ) {
    ?>
    <h2><?php echo $title; ?></h2>
    <?php
  }
  ?>
    <div class="ace_slideshow" id="ace_sideshow_<?php $ace_gallery->slideshows++; echo $ace_gallery->slideshows; ?>" style="min-width:<?php echo $min_width; ?>; min-height:<?php echo $min_height; ?>;"> 
      <div class="ace_loading"><?php esc_html_e( 'Loading...', 'ace-xml-gallery-builder' ); ?></div> 
  <?php
  foreach ( $image_list as $thumb ) {
    $onclick = $thumb->on_click( 'widget' );
		$class= 'thumb'; 
		$rel = ( '' != $onclick['rel'] ) ? 'rel="' . $onclick['rel'] . '"' : '';   	
  	if ( ! file_exists( $thumb->loc() ) ) 
  		$class .= ' ace_ajax';
    ?>
    <a id="<?php echo $onclick['id'] . '_' .$ace_gallery->slideshows ?>" href="<?php echo $onclick['href']; ?>" title="<?php echo $thumb->title() ?>" <?php echo $rel; ?> class="<?php echo $onclick['class'] ?>"><img class="<?php echo $class; ?>" src="<?php echo $thumb->src(); ?>" alt="<?php echo $thumb->alt(); ?>" /></a>
    <?php
  }
  ?></div><?php
}

/**
 * ace_nice_link()
 * encodes the URL but leaves slashes for nicer link in the gallery
 * 
 * @param string $alink 
 * @return string
 */
function ace_nice_link( $alink ) {
	return str_replace( '%2F', '/', rawurlencode( utf8_encode( $alink ) ) );
}

/**
 * ace_html()
 * This function makes sure titles and descriptions will be displayed with only the allowed html elements
 * Users should be albe to use html entities in the title withou double encoding
 * Anchors should not be encoded
 * 
 * @param mixed $astring
 * @return void
 */
function ace_html( $astring ) {
  if ( $astring == '' ) return $astring;
  $astring  = esc_html( stripslashes( $astring ) ); 
  /* if an anchor is found, just convert all <, >, and quotes. Mind! this will fail if you use quotes or <, > in your text */
  if ( 0 != preg_match( "|&lt;a|", $astring ) ) {
    $astring = str_replace( "&lt;", "<", $astring );
    $astring = str_replace( "&gt;", ">", $astring );
    $astring = str_replace( "&quot;", "\"", $astring );
  } else {  /* else just replace the allowed html tags */    
    $astring = str_replace( "&lt;strong&gt;", "<strong>", $astring );
    $astring = str_replace( "&lt;/strong&gt;", "</strong>", $astring );
    $astring = str_replace( "&lt;br /&gt;", "<br />", $astring );
    $astring = str_replace( "&lt;em&gt;", "<em>", $astring );
    $astring = str_replace( "&lt;/em&gt;", "</em>", $astring );
    $astring = str_replace( "&lt;ul&gt;", "<ul>", $astring );
    $astring = str_replace( "&lt;/ul&gt;", "</ul>", $astring );
    $astring = str_replace( "&lt;ul&gt;", "<ul>", $astring );
    $astring = str_replace( "&lt;/ul&gt;", "</ul>", $astring ); 
  }
  return $astring;
}

/**
 * ace_esc_description()
 * prepares string value for editing in description textarea
 * 
 * @since 1.1.0
 * @param string $text
 * @return string
 */
function ace_esc_description( $text ) {
	$safe_text = esc_textarea( preg_replace('`<br(?: /)?>([\\n\\r])`', '$1', stripslashes( $text ) ) );
	return apply_filters( 'ace_esc_description', $safe_text, $text );
}

/**
 * ace_esc_title()
 * prepares string value for editing in title input
 * 
 * @since 1.1.0
 * @param mixed $text
 * @return
 */
function ace_esc_title( $text ) {
	$safe_text = htmlspecialchars( stripslashes( $text ), ENT_QUOTES );
	return apply_filters( 'ace_esc_title', $safe_text, $text );
}

/**
 * ace_add_extrafield()
 * Template tag to add an extra field to images or folders
 * should be called after Ace Gallery has initialized
 * example: 
 * 
 * add_action( 'ace_ready', 'myfunction' );
 * 
 * function myfunction {
 *   ace_add_extrafield( 'myfield', 'My Field', 'image', true,'1' );
 * }
 * 
 * @param string $field_name
 * @param string $display_name
 * @param string $target
 * @param bool $can_edit
 * @since 1.1.0
 * @return
 */
function ace_add_extrafield( $field_name, $display_name = '', $target = 'image', $can_edit = false,$order='' ) {
  global $ace_gallery;
  $result = true;
  if ( ! isset( $ace_gallery ) ) {
    $result = false;
  } else {    
    $result = $ace_gallery->add_field( $field_name, $display_name, $target, $can_edit,$order );
  } 
  return $result;
}

/**
 * ace_get_the_title()
 * Returns the title for the currently displaying folder or slide page
 * 
 * @since 1.1.0
 * @return string
 */
function ace_get_the_title() {
  global $ace_gallery;
  
  $page = get_page( $ace_gallery->get_option( 'gallery_id' ) );
  $title = esc_html( $page->post_title );
  unset( $page );
  
  if ( !isset( $ace_gallery ) )
  	return $title;
  $ace_gallery->valid();
		
  if ( $ace_gallery->is_image( $ace_gallery->file ) ) {
    $folder = new AceFolder( dirname( $ace_gallery->file ) );
    $image = $folder->single_image( basename( $ace_gallery->file ) );
    $title = $image->title();
    unset( $image, $folder );
  }
  if ( $ace_gallery->is_folder( $ace_gallery->file ) ) {
    $folder = new AceFolder( $ace_gallery->file );
    $folder->open();
    $title = $folder->title();
    unset( $folder );
  }
  return $title;
}


/**
 * ace_login_required()
 * Checks if login is required to view the current folder or slide page
 * 
 * @since 1.1.0
 * @return bool
 */
function ace_login_required() {
  global $ace_gallery;
  if ( is_user_logged_in() ) return false;
  if ( ! isset( $ace_gallery ) ) return false;
  if ( ! isset( $ace_gallery->file ) ) 
    $ace_gallery->valid();    
  if ( $ace_gallery->is_folder( $ace_gallery->file ) ) {
		$the_folder = new AceFolder( $ace_gallery->file ) ;
  } 
  elseif ( $ace_gallery->is_image( $ace_gallery->file ) ) {
    $the_folder = new AceFolder( dirname( $ace_gallery->file) );    
  } else {
    return false;
  }  
  $login_required = ! $the_folder->user_can( 'viewer' );
  unset( $the_folder );
  return $login_required;  
}

/**
 * ace_level_required()
 * Checks if a (higher) user level is required to view the current folder or slide page
 * @return
 */
function ace_level_required() {
  global $ace_gallery; 
  if ( ! isset( $ace_gallery ) ) return false;
  $ace_gallery->valid();
  if ( $ace_gallery->is_folder( $ace_gallery->file ) ) {
		$the_folder = new AceFolder( $ace_gallery->file ) ;
  } 
  elseif ( $ace_gallery->is_image( $ace_gallery->file ) ) {
    $the_folder = new AceFolder( dirname( $ace_gallery->file) );    
  } else {
    return false;
  }   
  $level_required = ! $ace_gallery->access_check( $the_folder );
  unset( $the_folder );
  return $level_required;  
}

/**
 * ace_get_users_of_blog()
 * Gets all users of blog that have at least contributor rights
 * @since 1.1.0
 * @uses get_users()
 * @uses class WP_User()
 * @uses user_can()
 * @return array
 */
function ace_get_users_of_blog() {
	global $ace_gallery;
  $blog_users = get_users();
  $result = array(); 
  // By default, user should be at least contributor to be selected as editor
  $capability = $ace_gallery->default_editor_capability();
  foreach ( $blog_users as $userdata ) {
    $user = new WP_User( $userdata->ID );
    if ( user_can( $user, $capability ) ) { 
      $result[] = $user;
    } else {   
      unset( $user );
    }
    unset( $userdata );
  }
  unset( $blog_users );
  return $result;
}

/**
 * ace_db()
 * for debugging 
 * works only if WP_DEBUG is defined
 * 
 * @param mixed $var
 * @param string $txt
 * @return void
 */
function ace_db($var,$txt=''){
	if( !	defined(	'WP_DEBUG'	)	)
		return;
	$txt = ( $txt == '' ) ? 'var' : $txt; 	
	printf ("<br /><b>%s</b> = %s<br />\n", $txt, htmlentities( print_r( $var, true ) ) );
}
?>