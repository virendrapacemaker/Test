<?php

/**
 * ace_deprecated_image_request()
 * Used to create images on the fly
 * 
 * As of Ace Gallery version 1.1.0 this file should no longer be called directly
 * only for backward compatibility for hard coded links
 * 
 * @deprecated use ajax call ace_image_request 
 * @return void
 */
function ace_deprecated_image_request() {
	require_once( dirname( __FILE__ ) . '/inc/frontend.php' );
  global $ace_gallery;
  
  $ace_gallery = new AceFrontend();
  
  if ( ! isset( $ace_gallery ) )
  	die('-1');
 
  $memok = $ace_gallery->valid();
  $thumb = isset( $_GET[ 'thumb' ] );
  
	$path = pathinfo( $ace_gallery->file );
  $folder = new AceFrontendFolder( $path[ 'dirname'] );
  $image = ( $thumb ) ? new AceThumb( $folder ) : new AceSlide( $folder );
  $image->image = $path[ 'basename' ];
  if( $thumb ) {
		$height = $ace_gallery->get_option( 'thumbheight' );
		$width = $ace_gallery->get_option( 'thumbwidth' );
	}
	else {
		$height = $ace_gallery->get_option( 'pictheight' );
		$width = $ace_gallery->get_option( 'pictwidth' );
	}
  $cache = ( ( $thumb && ( 'TRUE' == $ace_gallery->get_option( 'enable_cache' ) ) ) || ( ! $thumb && ( 'TRUE' == $ace_gallery->get_option( 'enable_slides_cache' ) ) ) );
  if ( $memok ) {
	  if ( $cache ) {
	    $memok = $image->cache();
	  } else {		
	    $memok = $image->resize( $width, $height ); 
	  }
  }
  if ( ! $memok ) {      	
    $alert = imagecreatefrompng( $ace_gallery->plugin_dir . '/images/file_alert.png' );
    header( 'Content-type: image/png' );
    imagepng( $alert );
    imagedestroy( $alert );
  } else {    
    if ( is_resource( $image->resized ) ) {
      switch( strtolower( $path[ 'extension' ] ) ) {   
    		case 'jpeg':
    		case 'jpg':
    			header( 'Content-type: image/jpeg' );
    		  imagejpeg( $image->resized );
    			break;
    		case 'gif':
    			header( 'Content-type: image/gif' );
        	imagegif( $image->resized );
    			break;
    		case 'png':
    			header( 'Content-type: image/png' );
        	imagepng( $image->resized );
    			break;
    		default:
    			break;
    	}      	
      imagedestroy( $image->resized );
    }
  }
}


$root = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );

if ( file_exists( $root . '/wp-load.php' ) ) {
    require_once( $root . '/wp-load.php' );
} 

ace_deprecated_image_request();

?>