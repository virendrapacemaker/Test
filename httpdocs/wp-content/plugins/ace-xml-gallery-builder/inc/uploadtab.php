<?php
/**
 * AceUploadTab
 * All Wordpress Media Upload Tab functions to insert LAzyest Gallery shortcodes into a post
 * 
 * @package Ace Gallery  
 * @author Marcel Brinkkemper
 * @copyright 2010-2012 Christopher
 * @since 1.1.0
 * @access public
 */
class AceUploadTab {
  
  /**
   * AceUploadTab::_folder_row()
   * Display a row of folder information
   * 
   * @param AceFolder $folder
   * @param bool $single
   * @internal
   * @return void
   */
  function _folder_row( $folder, $single = false ) {
    global $ace_gallery;
    $icon = $folder->icon();
    $count = $folder->count();
    $ace_ajax =  ( strpos( $icon['icon'], '?' ) ) ? ' ace_ajax' : ''; 
    ?>
    <div id="media-item-<?php echo $folder->id; ?>" class="media-item child-of-0 preloaded">  
      <img class="pinkynail toggle<?php echo $ace_ajax; ?>" alt="" src="<?php echo $icon['icon'] ?>" style="margin-top:3px; display:block;" />  
      <?php if ( ! $single ) { ?>
      <div id="ace_actions_<?php echo $folder->id; ?>" class="folder-actions">    
        <img alt="" id="ajax-loading_<?php echo $folder->id; ?>" class="ajax-loading" src="<?php echo admin_url('images/wpspin_light.gif'); ?>" />  
        <a rel="<?php echo urlencode( $folder->curdir ); ?>" class="ace_upload-insertfolder" title="<?php esc_attr_e( 'Insert folder shortcode', 'ace-xml-gallery-builder'); ?>" id="ace_if_<?php echo $folder->id; ?>" href="#"><?php echo esc_html__( 'Insert', 'ace-xml-gallery-builder' ); ?></a>
        <a rel="<?php echo urlencode( $folder->curdir ); ?>" class="ace_upload-showfolder" title="<?php esc_attr_e( 'Select an image', 'ace-xml-gallery-builder' ); ?>" id="ace_sf_<?php echo $folder->id; ?>" href="#"><?php echo esc_html_e( 'Show', 'ace-xml-gallery-builder' ); ?></a>        
      </div>        
      <?php } 
        $back_url = remove_query_arg( array( 'ace_paged', 'ace_pagei', 'folder' ), $_SERVER['REQUEST_URI'] );
      ?>        
      <div id="ace_back_<?php echo $folder->id; ?>" class="folder-actions" <?php if ( ! $single ) echo 'style="display:none;"'; ?>">    
        <a title="<?php esc_html_e( 'Show all folders', 'ace-xml-gallery-builder'); ?>" href="<?php echo $back_url ?>"><?php esc_html_e( 'Back', 'ace-xml-gallery-builder' ); ?></a>                
      </div>  
      <div class="filename"><span class="title"><code><?php echo htmlentities( $folder->curdir ); ?></code> <?php echo $folder->title(); ?> (<?php echo $count; ?> <?php echo $ace_gallery->get_option( 'listed_as' ) ?>)</span></div>      
    </div>
    <?php   
  }
  
  /**
   * AceUploadTab::_image_row()
   * Display a row of image information
   * 
   * @param AceThumb $image
   * @return void
   */
  function _image_row( $image ) {
    $back_url = $this->action_url() . '&folder=' . $image->folder->id;
    $image_src = $image->src();
		$ace_ajax =  ( strpos( $image_src, '?' ) ) ? ' ace_ajax' : '';       
    ?>
    <div id="media-item-<?php echo $image->id; ?>" class="media-item child-of-0 preloaded ace-item">  
      <img id="pinky_<?php echo $image->id; ?>" class="pinkynail toggle<?php echo $ace_ajax; ?>" alt="" src="<?php echo $image_src; ?>" style="margin-top:3px; display:block;" />  
      <div id="ace_actions_<?php echo $image->id; ?>" class="folder-actions">    
        <img alt="" id="ajax-loading_<?php echo $image->id; ?>" class="ajax-loading" src="<?php echo admin_url( 'images/wpspin_light.gif' ); ?>" />  
        <a rel="<?php echo urlencode( $image->folder->curdir . $image->image ) ?>" id="ace_ii_<?php echo $image->id ?>" class="ace_upload-insertimage" title="<?php esc_html_e( 'Insert image shortcode', 'ace-xml-gallery-builder'); ?>" id="ace_if_<?php echo $image->id; ?>" href="#"><?php esc_html_e( 'Insert', 'ace-xml-gallery-builder' ); ?></a>                
      </div>
      <div id="ace_back_<?php echo $image->id; ?>" class="folder-actions" style="display:none;">    
        <a title="<?php esc_html_e( 'Show all images in this folder', 'ace-xml-gallery-builder'); ?>" href="<?php echo $back_url ?>"><?php esc_html_e( 'Back', 'ace-xml-gallery-builder' ); ?></a>                
      </div>         
      <div class="filename"><span class="title"><code><?php echo htmlentities( $image->image ); ?></code> <?php echo $image->title(); ?></span></div>      
    </div>
    <?php  
  }
  
  /**
   * AceUploadTab::show_gallery()
   * Display all folders in the gallery
   * 
   * @return void
   */
  function show_gallery() {
    global $ace_gallery;
    $folders = $ace_gallery->folders( 'subfolders', 'visible' );
    if ( 0 == count( $folders ) ) {
      ?><h3 class="media-title"><?php esc_html_e( 'Ace Gallery is empty', 'ace-xml-gallery-builder' ); ?></h3></form><?php
    }    
    $pagination = $ace_gallery->pagination( 'cfolders',  $folders );  
    $perpage  = 10;            
    $total_pages = ceil( count( $folders ) / $perpage ); 
    $query_var = 'ace_paged';
    if ( isset ( $paged ) ) {
      $current = $paged;
    } else {      
      $current = isset( $_REQUEST[$query_var] ) ? absint( $_REQUEST[$query_var] ) : 0;	
  	$current = min( max( 1, $current ), $total_pages );
    }
    $start = ( $current - 1 ) * $perpage + 1;
    $end = min( count( $folders ), $current * $perpage);
    ?>
      <form id="filter" method="post" action="">
      <h3 class="media-title"><?php esc_html_e( 'Add media from Ace Gallery', 'ace-xml-gallery-builder' ); ?></h3>         
      <div class="tablenav"><?php echo $pagination ?></div>
      </form>      
      <form enctype="multipart/form-data" method="post" action="<?php echo $this->action_url(); ?>" class="media-upload-form validate" id="acegallery-form">
      <?php wp_nonce_field( 'media_upload_acegallery' ); ?>
      <div id="media-items">
      <?php      
      for ( $i = $start -1; $i != $end; $i++ ) { 
        $folder = $folders[$i]; 
        $this->_folder_row( $folder );              
      }    
      ?>
      </div>
    </form>
    <?php    
  }
  
  /**
   * AceUploadTab::show_folder()
   * Display all image rows
   * 
   * @param AceFolder $folder
   * @param string $current_url
   * @return void
   */
  function show_folder( $folder, $current_url = '' ) {
    global $ace_gallery;
    if ( ! $folder->valid() ) {
      echo sprintf( '<div class="media-item"><div class="filename"><span class="title">%s</span</div></div>', __( 'Error opening folder', 'ace-xml-gallery-builder' ) );
      return;
    } 
    $folder->open();
    $folder ->load( 'thumbs' );
    if ( 0 == count( $folder->list ) ) {
      echo sprintf( '<div class="media-item"><div class="filename"><span class="title">%s</span</div></div>', __( 'This folder is empty', 'ace-xml-gallery-builder' ) );
      return;
    }
    $pagination = $ace_gallery->pagination( 'cimages',  $folder->list, $current_url );
    $perpage  = 10;            
    $total_pages = ceil( count( $folder->list ) / $perpage ); 
    $query_var = 'ace_pagei';
    if ( isset ( $pagei ) ) {
      $current = $pagei;
    } else {      
      $current = isset( $_REQUEST[$query_var] ) ? absint( $_REQUEST[$query_var] ) : 0;	
  	$current = min( max( 1, $current ), $total_pages );
    }
    $start = ( $current - 1 ) * $perpage + 1;
    $end = min( count( $folder->list ), $current * $perpage);
    ?>
    <div class="media-item" id="ace_folder_nav">
      <form id="ifilter" method="post" action="">         
        <div class="tablenav"><?php echo $pagination ?></div>
        <input type="hidden" name="current_url" value="<?php echo $current_url; ?>" />
      </form>
    </div>
    <?php
    for ( $i = $start -1; $i != $end; $i++ ) { 
      $image = $folder->list[$i]; 
      $this->_image_row( $image );              
    }    
  }
  
  /**
   * AceUploadTab::action_url()
   * The tab url
   * @return string
   */
  function action_url() {    
  	$post_id = $_REQUEST['post_id'];
  	$media_upload = admin_url( 'media-upload.php' );
		$action_url = add_query_arg( array( 'post_id' => $post_id, 'tab' => 'acegallery', 'type' => 'image' ), $media_upload ); 
    return $action_url;
  }
  
  /**
   * AceUploadTab::display_folder()
   * Display all folder information and image rows
   * 
   * @param mixed $folder
   * @return
   */
  function display_folder( $folder ) {
    $single = true;    
    $current_url = remove_query_arg( array('ace_pagei', 'ace_paged' ), $_SERVER['REQUEST_URI'] );
    ?>
    <form enctype="multipart/form-data" method="post" action="<?php echo $this->action_url(); ?>" class="media-upload-form validate" id="acegallery-form">
      <?php wp_nonce_field( 'media_upload_acegallery' ); ?>
      <h3 class="media-title"><?php esc_html_e( 'Add media from Ace Gallery', 'ace-xml-gallery-builder' ); ?></h3>
      <div id="media-items">
        <?php 
        $this->_folder_row( $folder, $single ); 
        $this->show_folder( $folder, $current_url );
        ?>
      </div>
    </form>
    <?php
  }
  
  /**
   * AceUploadTab::display()
   * Main display function for media upload tab
   * 
   * @return
   */
  function display() {
    global $ace_gallery;    
    media_upload_header();
    if ( isset( $_REQUEST['folder'] ) ) {      
      $file = $ace_gallery->get_file_by_id( $_REQUEST['folder'] );    
      $folder = new AceFolder( $file[0] ); 
      if ( $folder->valid() ) 
        $this->display_folder( $folder ); 
    } else {
      $this->show_gallery();
    }
  }
  
  /**
   * AceUploadTab::insert_image_shortcode()
   * Form to insert an image shortcode
   * 
   * @param AceThumb $image
   * @return void
   */
  function insert_image_shortcode( $image ) {
    if ( ! $image->valid() ) {
      esc_html_e( 'Error retrieving image', 'ace-xml-gallery-builder' );
      return;        
    }
    $onclick = $image->on_click( 'widget' );
    $onclickhref = $onclick['href'];
    $thumburl = $image->src();
    $filename = $image->image;
    list($width, $height, $type, $attr) = getimagesize( $image->original() );
    $date = $image->datetime; 
    $media_dims = "$width&nbsp;x&nbsp;$height";
    $title = htmlspecialchars( stripslashes( $image->title ), ENT_QUOTES );
    ?>    
    <input type="hidden" name="ace_folder" value="<?php echo urlencode( $image->folder->curdir ) ?>" />
    <input type="hidden" name="ace_image" value="<?php echo urlencode( $image->image ) ?>" />
    <table class="describe">
  		<thead class="media-item-info" id="media-head-<?php echo $image->id; ?>">
    		<tr valign="top">
    			<td class="A1B1" id="thumbnail-head-<?php echo $image->id; ?>">
    		  	<p><a href="<?php echo $onclickhref; ?>" target="_blank"><img class="thumbnail" src="<?php echo $thumburl; ?>" alt="" style="margin-top: 3px" /></a></p>		
    			</td>
    			<td>
      			<p><strong><?php esc_html_e( 'File name:', 'ace-xml-gallery-builder' ); ?></strong> <?php esc_html_e( $filename ); ?></p>
      			<p><strong><?php esc_html_e( 'File type:', 'ace-xml-gallery-builder' ); ?></strong> <?php echo image_type_to_mime_type($type); ?></p>
      		  <p><strong><?php esc_html_e( 'Date:', 'ace-xml-gallery-builder' ); ?></strong> <?php echo date( get_option( "date_format" ), $date ); ?></p>
      		  <p><strong><?php esc_html_e( 'Dimensions:', 'ace-xml-gallery-builder' ); ?></strong> <?php echo $media_dims; ?></p>
    		  </td>
        </tr>
        <tr class="post_title form-required">
          <th valign="top" class="label" scope="row">
            <label for="short_code_title">
              <span class="alignleft"><?php esc_html_e( 'Title', 'ace-xml-gallery-builder' ); ?></span><br class="clear" />
            </label>
          </th>
          <td class="field">
            <input type="text" value="<?php echo $title; ?>" name="short_code_title" id="short_code_title[<?php echo $image->id; ?>]" class="text" />
          </td>        
        </tr> 
        <tr class="align">
          <th valign="top" class="label" scope="row">
            <label for="image_align[<?php echo $image->id; ?>]"><span class="alignleft"><?php esc_html_e( 'Alignment', 'ace-xml-gallery-builder' ); ?></span><br class="clear" /></label>
          </th>
          <td class="field">
            <input type="radio" value="" id="image-align-none-<?php echo $image->id; ?>" name="image_align" /><label class="align image-align-none-label" for="image-align-none"><?php esc_html_e( 'None', 'ace-xml-gallery-builder') ?></label>
            <input type="radio" checked="checked" value="left" id="image-align-left" name="image_align" /><label class="align image-align-left-label" for="image-align-left"><?php esc_html_e( 'Left', 'ace-xml-gallery-builder') ?></label>
            <input type="radio" value="center" id="image-align-center" name="image_align" /><label class="align image-align-center-label" for="image-align-center"><?php esc_html_e( 'Center', 'ace-xml-gallery-builder') ?></label>
            <input type="radio" value="right" id="image-align-right" name="image_align" /><label class="align image-align-right-label" for="image-align-right-<?php echo $image->id; ?>"><?php esc_html_e( 'Right', 'ace-xml-gallery-builder') ?></label>
          </td>
		    </tr> 
        <tr class="image-size">    
          <th valign="top" class="label" scope="row">
            <label for="image-size">
              <span class="alignleft"><?php esc_html_e( 'Size', 'ace-xml-gallery-builder' ); ?></span><br class="clear" />
            </label>
          </th>
          <td class="field">
            <div class="image-size-item">
              <input type="radio" checked="checked" value="thumb" id="image-size-thumbnail" name="image-size" />
                <label for="image-size-thumbnail"><?php esc_html_e( 'Thumbnail', 'ace-xml-gallery-builder' ); ?></label>
            </div>            
            <div class="image-size-item">
              <input type="radio" value="slide" id="image-size-slide" name="image-size" />
                <label for="image-size-slide"><?php esc_html_e( 'Slide', 'ace-xml-gallery-builder' ); ?></label>
            </div>
            <div class="image-size-item">
              <input type="radio" value="image" id="image-size-full" name="image-size" />
                <label for="image-size-full"><?php esc_html_e( 'Full Size', 'ace-xml-gallery-builder' ); ?></label>
            </div>
          </td>
		    </tr>
        <tr class="submit">
          <td></td>
          <td>
            <input class="button" type="submit" name="image_short" title="<?php esc_html_e( 'Insert a Ace Gallery shortcode', 'ace-xml-gallery-builder' ); ?>" value="<?php echo __( 'Insert as shortcode', 'ace-xml-gallery-builder' ) ?>" />
          </td>
        </tr>
  		</thead>
  		<tbody>
      </tbody>
    </table>
    <?php
  }
  
  /**
   * AceUploadTab::insert_folder_shortcode()
   * Form to insert a folder shortcode
   * 
   * @param AceFolder $folder
   * @return void
   */
  function insert_folder_shortcode( $folder ) {  
    if ( ! $folder->valid() ) {
      esc_html_e( 'Error opening folder', 'ace-xml-gallery-builder' );
      return;  
    } 
    $folder->open();
    $count = $folder->count();
    $selectimages = sprintf( '<option value="">%s</option>', esc_html__( 'Default', 'ace-xml-gallery-builder' ) ); 
    for ( $i =1; $i <= $count; $i++ ) {
      $selectimages .= sprintf( '<option value="%s">%s</option>', $i, $i );
    }
    $selectcolumns = sprintf( '<option value="">%s</option>', esc_html__( 'Default', 'ace-xml-gallery-builder' ) ); 
    for ( $i =1; $i <= 10; $i++ ) {
      $selectcolumns .= sprintf( '<option value="%s">%s</option>', $i, $i );
    }   
  ?>  
    <input type="hidden" name="ace_folder" value="<?php echo urlencode( $folder->curdir ); ?>" />
    <div class="media-item">
    <table class="describe" id="ace_st_<?php echo $folder->id; ?>">
      <thead>
        <tr><th colspan="2"><?php esc_html_e( 'Folder shortcode', 'ace-xml-gallery-builder' ); ?></th></tr>            
      </thead>
      <tbody>
        <tr>
          <th class="label" scope="row"><label for="count"><?php esc_html_e( 'Number of images', 'ace-xml-gallery-builder' ); ?></label></th>
          <td><select name="count"><?php echo $selectimages ?></select></td>            
        </tr>
        <tr>          
          <th class="label" scope="row"><label for="column"><?php esc_html_e( 'Number of columns', 'ace-xml-gallery-builder' ); ?></label></th>
          <td><select name="column"><?php echo $selectcolumns ?></select></td>            
        </tr>
        <tr>
          <th class="label" scope="row"><label for="paging"><?php esc_html_e( 'Add pagination', 'ace-xml-gallery-builder' ); ?></label></th>
          <td><input type="checkbox" name="paging" /></td>
        </tr>
        <tr class="submit">
          <td></td>
          <td>
            <input class="button" type="submit" name="folder_short" value="<?php esc_html_e( 'Insert as shortcode', 'ace-xml-gallery-builder' ) ?>" />
            <input class="button" type="submit" name="folder_slide" value="<?php esc_html_e( 'Insert as slide show', 'ace-xml-gallery-builder' ) ?>" />          
          </td>
        </tr>
      </tbody>
      </table>
    </div>
    <?php
  }
  
  /**
   * AceUploadTab::folder_to_editor()
   * 
   * @param string $whathtml what to insert, shortcode or slide show
   * @return void
   */
  function folder_to_editor( $whathtml ) {
    $file = urldecode( $_POST['ace_folder'] );
    $folder = new AceFolder( $file );
    $file = htmlentities( $file );
    unset( $folder );
    switch ( $whathtml ) {
      case 'shortcode' :
        $count = ( isset( $_POST["count"] ) ) ? $_POST["count"] : '';
        $count = ( '' != $count ) ? sprintf( 'count="%s"', $count ) : '';        
        $cols = ( isset( $_POST["column"] ) ) ? $_POST["column"] : '';
        $cols = ( '' != $cols ) ? sprintf( 'cols="%s"', $cols ) : '';    
        $paging = ( isset( $_POST["paging"] ) ) ? 'paging="true"': 'paging="false"';    
        $html = sprintf('[ace_folder folder="%s" %s %s %s]', $file, $count, $cols, $paging );
        break;
      case 'slideshow' :        
        $html = sprintf('[ace_slideshow folder="%s"]', $file );
        break;
    }    
    return media_send_to_editor( $html );
  }
  
  /**
   * AceUploadTab::image_to_editor()
   * 
   * @param mixed $whathtml what to insert, shortcode
   * @return void
   */
  function image_to_editor ( $whathtml ) {
    global $ace_gallery;
    $file = urldecode( $_POST['ace_folder'] );
    $single = urldecode( $_POST['ace_image'] );
    $folder = new AceFolder( $file );
    $folder->open();
    $image = $folder->single_image( $single, $_POST["image-size"] . 's' );
    $onclick = $image->on_click( 'widget' );    
    $title = ( isset( $_POST["short_code_title"] ) ) ? $_POST["short_code_title"] : '';
    $html = '';
    switch ( $whathtml ) {
      case 'shortcode' :
        $title = ( '' != $title ) ? sprintf( 'title="%s"', $title ) : $title;
        $align = ( isset( $_POST["image_align"] ) ) ? sprintf( 'align="%s"', $_POST["image_align"] ) : '';
        $display = sprintf( 'display="%s"', $_POST["image-size"] );
        $html .= sprintf( '[ace_image folder="%s" image="%s" %s %s %s]', $file, $single, $title, $align, $display );
        break; 
    }     
    return media_send_to_editor( $html );
  } 
  
} // AceUploadTab
?>