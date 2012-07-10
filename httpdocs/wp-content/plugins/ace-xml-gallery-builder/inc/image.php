<?php
/**
 * AceImage
 * This class holds all functions and variables to handle images
 * 
 * @package Ace Gallery
 * @author Marcel Brinkkemper
 * @copyright 2010-2012 Christopher
 * @version 1.0
 * @access public
 */
class AceImage {
  
  /**
   * The filename.ext
   * @var string
   */
  var $image; 
  
  /**
   * The title
   * @var string
   */
  var $title;
  
  /**
   * The description
   * @var string
   */
  var $description;
  
  /**
   * The -unique- id
   * Image identifier for comments
   * @var int
   */
  var $id;
  
  /**
   * The index number to sort manually
   * @var int
   */ 
  var $index;
  
  /**
   * The date/time stored with the image
   * the system dat at the time the image has been found in the gallery
   * @var int
   */
  var $datetime;
  
  /**
   * The folder object holding the image
   * @var AceFolder
   */
  var $folder;
  
  /**
   * Resized image resource
   * @var resource
   */
  var $resized;
  
  /**
   * @since 1.1.0
   * array to hold user defined fields
   */
  var $extra_fields = array(); 
    
  
 /**
   * AceImage::__construct()
   * Creates a AceImage object belonging to folder $parent
   * 
   * @param mixed $parent
   * @return void
   */
  function __construct( $parent ) {
    global $ace_gallery;
    $this->image = $this->title = $this->description = $this->id = $this->index = '';
    $this->folder = $parent;
    $fields = $ace_gallery->get_fields( 'image' );
    if ( false !== $fields ) {
      foreach( $fields as $field ) {
        $this->extra_fields[$field['name']] = '';
      }
    }       
  }
  
  /**
   * AceImage::valid()
   * Checks if the image exists in the AceFolder directory
   * @return bool
   */
  function valid() {
    global $ace_gallery;
    return file_exists( $ace_gallery->root . $this->folder->curdir . $this->image );
  }
  
  
  /**
   * AceImage::src()
   * Returns the src attribute for the image element
   * @example <img src="<?php echo $image->src(); ?>" alt="" />
   * @return string
   */
  function src() {
    global $ace_gallery;
    if ( ( '' == $this->image ) || ! isset( $ace_gallery ) || ! $this->valid() ) {
      return false;
    }  
    return $ace_gallery->address . ace_nice_link( $this->folder->curdir . $this->image );
  }
  
  /**
   * AceImage::alt()
   * Returns the alt attribute for the image element
   * @example <img src="<?php echo $image->src(); ?>" alt="<?php echo $image->alt(); ?>" />
   * @return string
   */
  function alt() {
    return esc_attr__( 'image' ) . ' ' . sanitize_title( $this->image ); 
  }
  
  /**
   * AceImage::html_id()
   * Returns an id for use in a html element
   * @example <img id="<?php echo $image->html_id()); ?>" src="<?php echo $image->src(); ?>" alt="" />
   * @return string
   */
  function html_id() {
    return $this->form_name();
  }
  
  /**
   * AceImage::uri()
   * The address for the image in Ace Gallery
   * This can be a slide view page
   * @param string $widget
   * @return string
   */
  function uri( $widget = 'none' ) {
    global $ace_gallery;
    if ( ( '' == $this->image ) || ! isset( $ace_gallery ) ) {
      return false;
    }
    return  $this->folder->uri( $widget ) . ace_nice_link( $this->image );     
  }
  
  /**
   * AceImage::loc()
   * location of the image in the file system
   * @return string 
   */
  function loc() {
    global $ace_gallery;
    if ( ( '' == $this->image ) || ! isset( $ace_gallery ) ) {
      return false;
    }
    $loc = $ace_gallery->root . $this->folder->curdir . $this->image;
    return ( file_exists( $loc ) ) ? $loc : false;
  }
  
  /**
   * AceImage::original()
   * location of the original uploaded image
   * @since 1.1.0
   * @return string
   */
  function original(){
    global $ace_gallery;
    if ( ( '' == $this->image ) || ! isset( $ace_gallery ) ) {
      return false;
    }
    return $ace_gallery->root . $this->folder->curdir . $this->image;
  }
  
  /**
   * AceImage::title()
   * Returns a title.
   * Either the title from the xml file or the filename
   * When 'show titles' is enabled, underscores will be replaced by spaces
   * @return string
   */
  function title() {
    global $ace_gallery;
    $title = ( '' != $this->title ) ? $this->title : str_replace( '_', ' ', htmlentities( substr( $this->image, 0, -4 ) ) );
    $title =  ( 'TRUE' == $ace_gallery->get_option( 'enable_titles' ) ) ? $title : htmlentities( $this->image ); 
    return apply_filters( 'ace_image_title', $title, $this );
  }
  
  /**
   * AceImage::description()
   * Retruns the discription after applying a filter
   * @return string
   */
  function description() {
    return apply_filters( 'ace_image_description', $this->description, $this );
  }
  
  /**
   * AceImage::title()
   * 
   * @return
   */
  /*function title() {
    global $ace_gallery;
    $title = $this->title();
    $title =  strip_tags( ace_html( $title ) );    
    return apply_filters( 'ace_image_title', $title, $this );
  }*/
  
  /**
   * AceImage::form_name()
   * 
   * @return
   */
  function form_name() {
    if ( '' == $this->image ) {
      return false;
    }
    return sanitize_title( $this->image );
  }
  
  /**
   * AceImage::on_click()
   * 
   * @param string $widget
   * @return
   */
  function on_click( $widget='none' ) {
    global $ace_gallery;
    return array( 'href' => $ace_gallery->address . ace_nice_link( $this->folder->curdir . $this->image ) , 'class' => 'ace', 'rel' => '', 'title' => $this->title(), 'id' => sanitize_title( $this->image ) );  
  }
  
  /**
   * AceImage::write_xml()
   * 
   * @param mixed $handle
   * @return void
   */
  function write_xml( $handle ) {
    global $ace_gallery;
	//$images=new AceImageTable();
    //$image = $images->items[$i];
    if ( ! isset( $handle ) ) {    
      return false;      
    }    
		fwrite( $handle, "\t<photo>\n" );
		fwrite( $handle, "\t\t<filename><![CDATA[" . utf8_encode( htmlentities( $this->image ) ) . "]]></filename>\n" );
		fwrite( $handle, "\t\t<title><![CDATA[" . utf8_encode( htmlentities( $this->title ) ) . "]]></title>\n" );    
		fwrite( $handle, "\t\t<description><![CDATA[" . utf8_encode( htmlentities( $this->description ) ) . "]]></description>\n" );
		fwrite( $handle, "\t\t<image>" . $this->id . "</image>\n" );
		//fwrite( $handle, "\t\t<imagepath><![CDATA[" . utf8_encode( htmlentities($ace_gallery->address.$ace_gallery->root . $this->folder->curdir . $this->image ) ) . "]]></imagepath>\n" );
		fwrite( $handle, "\t\t<imagepath><![CDATA[" . utf8_encode( htmlentities($ace_gallery->address. $this->folder->curdir . $this->image ) ) . "]]></imagepath>\n" );
		//fwrite( $handle, "\t\t<index>". $this->index . "</index>\n" );
    fwrite( $handle, "\t\t<imagedate>" . $this->datetime . "</imagedate>\n" );  
    if ( 0 < count( $this->extra_fields ) ) {
      foreach( $this->extra_fields as $key=>$field ) {
        fwrite( $handle, "\t\t<$key><![CDATA[" . utf8_encode( htmlentities( $field ) ) . "]]></$key>\n" );  
      }
    }
		fwrite( $handle, "\t</photo>\n" );
  }
  
  /**
   * AceImage::newsize()
   * resizes or crops the image
   * 
   * @since 1.1.0
   * @param int $width
   * @param int $height
   * @return bool resize or crop success
   */
  function newsize( $width, $height ) {
    global $ace_gallery;
    if ( 'TRUE' == $ace_gallery->get_option( 'use_cropping' ) ) {
      $size = ( $width  < $height  ) ? $width :  $height; 
      if ( false === $this->crop( $size ) ) {
        return false;
      }             
    } else {
      if  ( false === $this->resize( $width, $height ) ) {
        return false;
      }
    }
    return true;
  }
  
  /**
   * AceImage::resize()
   * 
   * @param int $width  Maximum Width to resize the image
   * @param int $height Maximum height to resize the image
   * @return bool resize success
   */
  function resize( $width, $height ) {
    global $ace_gallery;
    if ( false === $this->loc() ) {
      return false;
    }
    if ( ! $this->memory_ok() ) {
      return false;
    }
    $img_location = $this->original();
    list( $o_width, $o_height, $o_type ) = @getimagesize( $img_location );
    
    $img = wp_load_image( $img_location );
    if ( !is_resource( $img ) ) {
    	trigger_error( $img, E_USER_WARNING );
    	return false;
		}
    
    $xratio = $width / $o_width;
    $yratio = $height / $o_height;
  	if ( ( $xratio >= 1 )  && ( $yratio >= 1 ) ) { 
  	  $nwidth = $o_width;
  	  $nheight = $o_height;
  	} elseif ( ( $xratio * $o_height ) < $height ) {
  		$nheight = floor( $xratio * $o_height );
  		$nwidth = $width;
  	} else {
  		$nwidth = floor( $yratio * $o_width );
  		$nheight = $height;
  	}
  	
    $resized = wp_imagecreatetruecolor( $nwidth, $nheight );  	  	 	
    imagecopyresampled( $resized, $img, 0, 0, 0, 0, $nwidth, $nheight, $o_width, $o_height );
    
    // convert from full colors to index colors, like original PNG.
    if ( IMAGETYPE_PNG == $o_type && function_exists( 'imageistruecolor' ) && ! imageistruecolor( $img ) )
      imagetruecolortopalette( $resized, false, imagecolorstotal( $img ) );
    
    unset( $img );    
    $resized = apply_filters( 'ace_imageresized', $resized, $width, $height, $this );
    $this->resized = $resized;               
    return true; 
  }
  
  /**
   * AceImage::crop()
   * 
   * @param mixed $size Width and Height of the square cropped image
   * @return bool success or failure
   * @todo merge with AceImage::resize()
   */
  function crop( $size ) {
    if ( false === $this->loc() ) {
      return false;
    }
    if ( ! $this->memory_ok() ) {
      return false;
    }
    $img_location = $this->original();
    list( $o_width, $o_height, $o_type ) = @getimagesize( $img_location );
    
    $img = wp_load_image( $img_location );
    if ( !is_resource( $img ) ) {
    	trigger_error( $img, E_USER_WARNING );
    	return false;
		}    
    
    if ( $o_width > $o_height )  { // landscape image
      $out_width = $out_height = $o_height;
      $out_left = round( ( $o_width - $o_height ) / 2 );
      $out_top = 0;
    } else { // portrait image
      $out_top = 0;
      $out_width = $out_height = $o_width;
      $out_left = 0;
    }  
        
    $cropped = wp_imagecreatetruecolor( $size, $size );
		imagecopyresampled( $cropped, $img, 0, 0, $out_left, $out_top, $size, $size, $out_width, $out_height );
		
		// convert from full colors to index colors, like original PNG.
		if ( IMAGETYPE_PNG == $o_type && function_exists( 'imageistruecolor' ) && ! imageistruecolor( $img ) )
			imagetruecolortopalette( $cropped, false, imagecolorstotal( $img ) );
		
    unset( $img );    
    $cropped = apply_filters( 'ace_image_cropped', $cropped, $size, $this );
    $this->resized = $cropped;  
    return true;  
  }
  
  /**
   * AceImage::memory_ok()
   * Checks if the amount of memory needed to store an image is available
   * 
   * @since 1.0
   * @return bool
   */
  function memory_ok() {
    global $ace_gallery;
    if ( 'TRUE' == $ace_gallery->get_option( 'memory_ok' ) ) {
      @ini_set('memory_limit', '256M' );
      return true; 
    }      
    $image_info = getimagesize( $ace_gallery->root . $this->folder->curdir . $this->image );
    $bits = ( isset( $image_info['bits'] ) ) ? $image_info['bits'] : 8;
    $channels = ( isset( $image_info['channels'] ) ) ? $image_info['channels'] : 3;
    $memory_needed = round( ( $image_info[0] * $image_info[1] * $bits * $channels / 8 + pow(2, 16) ) * 2 );
    return apply_filters( 'ace_memory_ok', ( memory_get_usage() + $memory_needed < (integer)ini_get('memory_limit') * pow( 1024, 2 ) ) );
  }
  
  /**
   * AceImage::set_extra_field()
   * 
   * set the value for an extra field
   * this should be a string
   * 
   * @param string $index
   * @param string $value
   * @return void
   * @since 1.1.0
   */
  function set_extra_field( $index, $value='' ) {
    $this->extra_fields[$index] = $value;
  }
  
  /**
   * AceImage::get_extra_field()
   * 
   * @param string $index
   * @return string
   * @since 1.1.0
   */
  function get_extra_field( $index ) {
    $value = false;
    if ( isset($this->extra_fields[$index] ) ) {
      $value = $this->extra_fields[$index];
    }
    return $value;
  }
  
} // AceImage

/**
 * AceSlide
 * 
 * @package Ace Gallery   
 * @author Marcel Brinkkemper
 * @copyright 2010 Christopher
 * @version 1.0
 * @access public
 */
class AceSlide extends AceImage {  
  
  /**
   * AceSlide::src()
   * 
   * @return string
   */
  function src() {
    global $ace_gallery;
    if ( ( ! $this->valid() ) || ! isset( $ace_gallery )  ) {
      return false;
    }     
    $slidefile = $ace_gallery->root . $this->folder->curdir . $ace_gallery->get_option( 'slide_folder' ) . $this->image;
    if ( 'TRUE' == $ace_gallery->get_option( 'enable_slides_cache' ) ) {
      if ( ! file_exists( $slidefile ) ) {
        $this->cache();
      }
      if ( file_exists( $slidefile ) ) { // a slide has been cached
        return  $ace_gallery->address . ace_nice_link( $this->folder->curdir . $ace_gallery->get_option( 'slide_folder' ) . $this->image );
      } else { // a slide could not be cached, probably a memory error
        return $ace_gallery->plugin_url . '/images/file_alert.png';
      }
      
    }		   
  	return admin_url( 'admin-ajax.php' ) . '?action=ace_image_request&amp;file='. ace_nice_link( $this->folder->realdir() . $this->image ) ;						
  }
      
  /**
   * AceSlide::html_id()
   * 
   * @return
   */
  function html_id() {
    return 'ace_slide_' . AceImage::html_id();
  }
  
  /**
   * AceSlide::uri()
   * 
   * @param string $widget
   * @return
   */
  function uri( $widget = 'none' ) {
    global $ace_gallery;
    if ( ( false === $this->loc() ) || ! isset( $ace_gallery )  ) {
      return false;
    }
    return $this->folder->uri( $widget ) . ace_nice_link( $this->image );
  }
  
  /**
   * AceSlide::cache()
   * Creates a slide in the slides cache.
   * 
   * @return bool success or failure
   */
  function cache() {
    global $ace_gallery;
    if ( isset( $ace_gallery ) && ( $this->image != '' ) ) {
      if ( 'TRUE' == $ace_gallery->get_option( 'enable_slides_cache' ) ) {
        $slide_dir = $ace_gallery->root . $this->folder->curdir . $ace_gallery->get_option( 'slide_folder' );
		    if ( ! file_exists( $this->loc() ) ) {
		      if ( false === $this->resize( $ace_gallery->get_option( 'pictwidth' ), $ace_gallery->get_option( 'pictheight' ) ) ) {
		        return false;  
		      }
          if ( ! file_exists( $slide_dir ) ) {
            $res = wp_mkdir_p( $slide_dir );
            if ( ! $res ) {
              return false;
            }
          }
          if ( is_writable( $slide_dir ) ) {
            $path = pathinfo( $this->image );         
            if ( is_resource( $this->resized ) ) {
          		switch ( strtolower( $path['extension'] ) ) {
          	  	case 'jpeg':
          	  	case 'jpg':
          	    	imagejpeg( $this->resized, $slide_dir . $this->image, $ace_gallery->get_option( 'resample_quality' ) );
          	    	break;
          	  	case 'gif':
          	    	imagegif( $this->resized, $slide_dir . $this->image );
          	    	break;
          	  	case 'png':
          	    	imagepng( $this->resized, $slide_dir . $this->image );
          	   	 break;
          		}
            }
          }
          if ( file_exists( $slide_dir . $this->image ) ) {
            $stat = stat( dirname( $slide_dir ) );            
            $perms = $stat['mode'] & 0000666;
            @chmod( $slide_dir . $this->image, $perms );
            return true;
          } else {            
            return false;
          }  
        } else {
          return true;
        }
      }
    }
  }
  
	/**
   * AceSlide::loc()
   * location of the image in the file system
   * @return string 
   */
  function loc() {
    global $ace_gallery;
    return $ace_gallery->root . $this->folder->curdir . $ace_gallery->get_option( 'slide_folder' ) . $this->image;
  }
  
  
  /**
   * AceSlide::on_click()
   * 
   * @param string $widget
   * @return
   */
  function on_click( $widget = 'none' ) {
    global $ace_gallery;  
    $onclick = AceImage::on_click();
    $onclick['id'] = 'ace_thumb_onclick_' .  $onclick['id'];   
    $slide = new AceSlide( $this->folder );
    $slide->image = $this->image; 
    switch ( $ace_gallery->get_option( 'on_slide_click' ) ) {
      case 'nothing' : 
        $onclick['href'] = '#';
        break;
      case 'fullimg' :
        break;
      case 'lightbox' :
        $onclick['rel'] = 'lightbox[' . $this->folder->form_name() . ']';        
        break;
      case 'thickbox' :
        $onclick['class'] = 'thickbox';
        $onclick['rel'] = $this->folder->form_name();
        break;
      case 'popup' :
        $onclick['href'] = "javascript:void(window.open('" . $ace_gallery->plugin_url . "/ace-popup.php?image=" . $this->image . "&amp;folder=" . ace_nice_link( $this->folder->curdir ) . "','','resizable=no,location=no,menubar=no,scrollbars=no,status=no,toolbar=no,fullscreen=no,dependent=yes,width=" . $ace_gallery->get_option( 'pictwidth' ) . ",height=" . $ace_gallery->get_option( 'pictheight' ) . ",left=100,top=100'))";
        break;
    }    
    unset( $slide );
    $onclick = apply_filters( 'ace_slide_onclick', $onclick, $this );
    return $onclick;
  }
  
} // AceSlide

/**
 * AceThumb
 * 
 * @package  Ace Gallery  
 * @author Marcel Brinkkemper
 * @copyright 2010 Christopher
 * @version 1.0
 * @access public
 */
class AceThumb extends AceImage {
   
  /**
   * AceThumb::__construct()
   * 
   * @param mixed $parent
   * @return
   */
  function __construct( $parent ) {
    AceImage::__construct( $parent );    
  }  
  
  /**
   * AceThumb::src()
   * 
   * @return
   */
  function src() {
    global $ace_gallery;
    if ( ! $this->valid() || ! isset( $ace_gallery )  ) {
      return false;
    }  
    $thumbfile = $ace_gallery->root . $this->folder->curdir . $ace_gallery->get_option( 'thumb_folder' ) . $this->image;
    if ( 'TRUE' == $ace_gallery->get_option( 'enable_cache' ) ) {   
      if ( ! file_exists( $thumbfile ) && ( 'TRUE' != $ace_gallery->get_option( 'async_cache' ) ) ) {
        $this->cache();
      }
      if ( file_exists( $thumbfile ) ) { 
        return  $ace_gallery->address . ace_nice_link( $this->folder->curdir . $ace_gallery->get_option( 'thumb_folder' ) . $this->image );
      } else {
        if ( 'TRUE' == $ace_gallery->get_option( 'async_cache' ) ) {
          return $ace_gallery->plugin_url . '/images/ajax-img.gif?action=ace_image_request&amp;file=' . ace_nice_link( $this->folder->realdir() . $this->image ) . '&amp;thumb=1';	
        } else {
          return $ace_gallery->plugin_url . '/images/file_alert.png';
        }
      }
      
    }				
  	return admin_url( 'admin-ajax.php' ) . '?action=ace_image_request&amp;file=' . ace_nice_link( $this->folder->realdir() . $this->image ) . '&amp;thumb=1';						
  }
        
  /**
   * AceThumb::html_id()
   * 
   * @return
   */
  function html_id() {
    return 'ace_thumb_' . AceImage::html_id();
  }
  
  /**
   * AceThumb::cache()
   * 
   * @return
   */
  function cache() {
    global $ace_gallery;
    if ( isset( $ace_gallery ) && ( $this->image != '' ) ) {
      if ( 'TRUE' == $ace_gallery->get_option( 'enable_cache' ) ) {
        $thumb_dir = $ace_gallery->root . $this->folder->curdir . $ace_gallery->get_option( 'thumb_folder' );
		    if ( ! file_exists( $this->loc() ) ) {
		      if  ( false === $this->newsize( $ace_gallery->get_option( 'thumbwidth' ), $ace_gallery->get_option( 'thumbheight' ) ) ) {
            return false;
          }          
          if ( ! file_exists( $thumb_dir ) ) {
            $res = wp_mkdir_p( $thumb_dir, 0777 );
            if ( false === $res ) {		                        
              return false;  
            }
          }
          if ( is_writable( $thumb_dir ) ) {
            $path = pathinfo( $this->image );            
            if ( is_resource( $this->resized ) ) {
          		switch ( strtolower( $path['extension'] ) ) {
          	  	case 'jpeg':
          	  	case 'jpg':
          	    	imagejpeg( $this->resized, $thumb_dir . $this->image, $ace_gallery->get_option( 'resample_quality' ) );
          	    	break;
          	  	case 'gif':
          	    	imagegif( $this->resized, $thumb_dir . $this->image );
          	    	break;
          	  	case 'png':
          	    	imagepng( $this->resized, $thumb_dir . $this->image );
          	   	 break;
          		}
            }
          }
          if ( 'TRUE' != $ace_gallery->get_option( 'async_cache' ) ) { // if async_cache, resized image will be output by admin-ajax.php
            if ( is_resource( $this->resized ) ) imagedestroy( $this->resized );
          }
          if ( file_exists( $thumb_dir . $this->image ) ) {
            $stat = stat( dirname( $thumb_dir ) );
            $perms = $stat['mode'] & 0000666;
            @chmod( $thumb_dir . $this->image, $perms );
            return true;
          } else {		          
            return false;
          }  
        } else {
          return true;
        }
      }
    }
  }
  
  
  
  /**
   * AceThumb::loc()
   * location of the image in the file system
   * @return string 
   */
  function loc() {
    global $ace_gallery;
    return $ace_gallery->root . $this->folder->curdir . $ace_gallery->get_option( 'thumb_folder' ) . $this->image;
  }
  

  /**
   * AceThumb::on_click()
   * 
   * @param string $widget
   * @return
   */
  function on_click( $widget = 'none' ) {
    global $ace_gallery;  
    $onclick = AceImage::on_click( $widget );     
    $onclick['id'] = 'ace_thumb_onclick_' .  $onclick['id'];   
    $slide = new AceSlide( $this->folder );
    $slide->image = $this->image;  
    switch ( $ace_gallery->get_option( 'on_thumb_click' ) ) {
      case 'nothing' : 
        $onclick['href'] = '#';
        break;
      case 'fullimg' :
        break;
      case 'slide' : 
        $onclick['href'] = $slide->uri( $widget );
        break;
      case 'lightslide' :
        $onclick['href'] = $slide->src();
        $onclick['rel'] = 'lightbox[' . $this->folder->form_name() . ']'; 
        break;
      case 'thickslide' : 
        $onclick['href'] = $slide->src(); 
        $onclick['class'] = 'thickbox';
        $onclick['rel'] = $this->folder->form_name();
      case 'lightbox' :
        $onclick['rel'] = 'lightbox[' . $this->folder->form_name() . ']';        
        break;
      case 'thickbox' :
        $onclick['class'] = 'thickbox';
        $onclick['rel'] = $this->folder->form_name();
        break;
    }    
    unset( $slide );
    $onclick = apply_filters( 'ace_thumb_onclick', $onclick, $this );
    return $onclick;
  }
  
} // AceThumb
?>