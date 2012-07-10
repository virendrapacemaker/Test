<?php

/**
 * Sidebar Widgets for Ace Gallery
 * 
 * @package Ace-Gallery  
 * @author Marcel Brinkkemper
 * @copyright 2008-2012 Christopher
 * @todo cast into classes
 */

add_action( 'widgets_init', 'ace_ace_widgets' );

function ace_ace_widgets() {

  if ( !  function_exists('wp_register_sidebar_widget') )
    return;

  function ace_widget_list_folders( $args ) {
    extract( $args );
    $options = get_option( 'widget_ace_list_folders' );
    $title = $options['title'];
    if ( empty($title) )
      $title = esc_html__( 'ACE Folders', 'ace-xml-gallery-builder' );
    echo $before_widget . $before_title . $title . $after_title;
    ace_list_folders( '' );
    echo $after_widget;
  }

  function ace_widget_list_folders_control() {
    $options = $newoptions = get_option( 'widget_ace_list_folders' );
    if ( isset( $_POST["ace_list_folders-submit"] ) ) {
      $newoptions['title'] = strip_tags( stripslashes($_POST["ace_list_folders-title"]) );
    }
    if ( $options != $newoptions ) {
      $options = $newoptions;
      update_option( 'widget_ace_list_folders', $options );
    }
    $title = esc_attr( $options['title'] );

?>
			<p><label for="ace_list_folders-title"><?php

    esc_html_e( 'Title:' );

?> <input class="widefat" id="ace_list_folders-title" name="ace_list_folders-title" type="text" value="<?php

    echo $title;

?>" /></label></p>
			<input type="hidden" id="ace_list_folders-submit" name="ace_list_folders-submit" value="1" />
<?php

  }

  function ace_widget_random_image( $args ) {
    global $ace_gallery;    
    extract( $args );
    $options = get_option( 'widget_ace_random_image' );
    $title = $options['title'];
    $count = $options['count'];
    $folder = utf8_decode( stripslashes( rawurldecode( $options['folder'] ) ) );
    $sub = isset( $options['subfolders'] );
    if ( empty($count) )
      $count = '1';
    if ( empty($title) )
      $title = __( 'ACE Random Image' );
    echo $before_widget . $before_title . $title . $after_title;
    ace_random_image( '', $count, $folder, $sub );
    echo $after_widget;
  }

  function ace_widget_random_image_control() {
    global $ace_gallery;
    $options = get_option( 'widget_ace_random_image' );
    if ( isset( $_POST["ace_random_image-submit"] ) ) {
      $options['title'] = isset( $_POST["ace_random_image-title"] ) ? strip_tags( stripslashes( $_POST["ace_random_image-title"] ) ) : '';
      $options['count'] = isset( $_POST['ace_random_image-count'] ) ? $_POST['ace_random_image-count'] : 1;
      $options['folder'] = isset( $_POST['ace_random_image-folder'] ) ? $_POST['ace_random_image-folder'] : '';
      $options['subfolders'] = isset(  $_POST['ace_random_image-sub'] ) ? $_POST['ace_random_image-sub']: '';
      if ( $options['folder'] == '' )
        $options['subfolders'] = 'on';
      update_option( 'widget_ace_random_image', $options );
    }

    $title = esc_attr( $options['title'] );
    $count = $options['count'];
    if ( $count == '' )
      $count = '1';
    $folder = $options['folder'];
    $sub = $options['subfolders'] ? 'checked="checked"' : '';

    $dirlist = $ace_gallery->folders( 'subfolders', 'visible' );

?>
			<p><label for="ace_random_image-title"><?php

    esc_html_e( 'Title:', 'ace-xml-gallery-builder' );

?> <input class="widefat" id="ace_random_image-title" name="ace_random_image-title" type="text" value="<?php

    echo $title;

?>" /></label></p>
			<p><label for="ace_random_image-count"><?php

    esc_html_e( 'Number of Images:', 'ace-xml-gallery-builder' );

?> <input class="widefat" id="ace_random_image-count" name="ace_random_image-count" type="text" size="2" value="<?php

    echo $count;

?>" /></label></p>
			<p><label for="ace_random_image-folder"><?php

    esc_html_e( 'Folder', 'ace-xml-gallery-builder' );

?> <select id="ace_random_image-folder" name="ace_random_image-folder">
				<option value="" <?php

    if ( $folder == '' )
      echo 'selected="selected"';

?> ><?php

    esc_html_e( '(all)', 'ace-xml-gallery-builder' );

?></option>
				<?php

    foreach ( $dirlist as $dir ) {
      echo '<option value="' . ace_nice_link( $dir->curdir ) . '"';
      if ( $folder == $dir->curdir )
        echo 'selected="selected"';
      echo ' >' . htmlentities( $dir->curdir ) . '</option>';
    }

?>
			</select></label></p>
			<p><label for="ace_random_image-sub"><?php

    esc_html_e( 'Include Sub Folders', 'ace-xml-gallery-builder' );

?> <input type="checkbox" id="ace_random_image-sub" name="ace_random_image-sub" <?php

    echo $sub;

?> /></label></p>
			<input type="hidden" id="ace_random_image-submit" name="ace_random_image-submit" value="1" />
<?php
  
  }

  function ace_widget_slide_show( $args ) {
    global $ace_gallery;
    extract( $args );
    if ( '' == $ace_gallery->get_option('enable_slide_show') ) return false;
    $options = get_option( 'widget_ace_slide_show' );
    $title = $options['title'];
    $count = $options['count'];
    $display = $options['display'];
    $folder = utf8_decode( stripslashes( rawurldecode( $options['folder'] ) ) );
    $sub = $options['subfolders'];
    if ( empty($count) )
      $count = '1';
    if ( empty($title) )
      $title = __( 'ACE Slide Show' );
    echo $before_widget . $before_title . $title . $after_title;
    ace_random_slideshow( '', $count, $display, $folder, $sub == 'on' );
    echo $after_widget;
  }

  function ace_widget_slide_show_control() {
    global $ace_gallery;
    if ( '' == $ace_gallery->get_option('enable_slide_show') ) return false;
    $options = get_option( 'widget_ace_slide_show' );
    if ( isset($_POST["ace_slide_show-submit"]) ) {
      $options['title'] = isset( $_POST["ace_slide_show-title"] ) ? strip_tags( stripslashes($_POST["ace_slide_show-title"]) ) : '';
      $options['count'] = isset( $_POST['ace_slide_show-count'] ) ? $_POST['ace_slide_show-count'] : 2;
      $options['display'] = isset( $_POST['ace_slide_show-time'] ) ? $_POST['ace_slide_show-time'] : 5;
      $options['folder'] = isset( $_POST['ace_slide_show-folder'] ) ? $_POST['ace_slide_show-folder'] : '';
      $options['subfolders'] = isset( $_POST['ace_slide_show-sub'] ) ? $_POST['ace_slide_show-sub'] : '';
      if ( $options['folder'] == '' )
        $options['subfolders'] = 'on';
      update_option( 'widget_ace_slide_show', $options );
    }

    $title = esc_attr( $options['title'] );
    $count = $options['count'];
    $display = $options['display'];
    if ( $count == '' )
      $count = '2';
    if ( $display == '' )
      $display = '5';
    $folder = $options['folder'];
    $sub = $options['subfolders'] ? 'checked="checked"' : '';
    $dirlist = $ace_gallery->folders( 'subfolders', 'visible' );

?>
			<p><label for="ace_slide_show-title"><?php

    esc_html_e( 'Title:' );

?> <input class="widefat" id="ace_slide_show-title" name="ace_slide_show-title" type="text" value="<?php

    echo $title;

?>" /></label></p>
			<p><label for="ace_slide_show-count"><?php

    esc_html_e( 'Number of Images:' );

?> <input class="widefat" id="ace_slide_show-count" name="ace_slide_show-count" type="text" size="2" value="<?php

    echo $count;

?>" /></label></p>
			<input class="widefat" id="ace_slide_show-time" name="ace_slide_show-time" type="hidden" size="2" value="<?php

    echo $display;

?>" /></label></p>	
			<p><label for="ace_slide_show-folder"><?php

    esc_html_e( 'Folder', 'ace-xml-gallery-builder' );

?> <select id="ace_slide_show-folder" name="ace_slide_show-folder">
				<option value="" <?php

    if ( $folder == '' )
      echo 'selected="selected"';

?> ><?php

    esc_html_e( '(all)', 'ace-xml-gallery-builder' );

?></option>
				<?php

    foreach ( $dirlist as $dir ) {
      echo '<option value="' . ace_nice_link( $dir->curdir ) . '"';
      if ( $folder == $dir->curdir )
        echo 'selected="selected"';
      echo ' >' . htmlentities( $dir->curdir ) . '</option>';
    }

?>
			</select></label></p>
			<p><label for="ace_slide_show-sub"><?php

    esc_html_e( 'Include Sub Folders', 'ace-xml-gallery-builder' );

?> <input type="checkbox" id="ace_slide_show-sub" name="ace_slide_show-sub" <?php

    echo $sub;

?> /></label></p>
			
			<input type="hidden" id="ace_slide_show-submit" name="ace_slide_show-submit" value="1" />
<?php

  }
global $ace_gallery;
  $widget_ops = array( 'classname' => 'ace_list_folders', 'description' => __("A list of all your Ace Gallery Folders",
    'ace-xml-gallery-builder') );
  wp_register_sidebar_widget( 'ace-list-folders', __('ACE List Folders'),
    'ace_widget_list_folders', $widget_ops );
  wp_register_widget_control( 'ace-list-folders', __('ACE List Folders'),
    'ace_widget_list_folders_control' );

  $widget_ops = array( 'classname' => 'ace_random_image', 'description' => __("Random Images from your Ace Gallery",
    'ace-xml-gallery-builder' ) );
  wp_register_sidebar_widget( 'ace-random-image', __('ACE Random Image'),
    'ace_widget_random_image', $widget_ops );
  wp_register_widget_control( 'ace-random-image', __('ACE Random Image', 'ace-xml-gallery-builder'),
    'ace_widget_random_image_control' );

  if ( 'TRUE' == $ace_gallery->get_option('enable_slide_show') ) {
  $widget_ops = array( 'classname' => 'ace-slide-show', 'description' => __("Slide Show of Thumbnails from your Ace Gallery",
    'ace-xml-gallery-builder') );
  wp_register_sidebar_widget( 'ace-slide-show', __('ACE Slide Show'),
    'ace_widget_slide_show', $widget_ops );
  wp_register_widget_control( 'ace-slide-show', __('ACE Slide Show', 'ace-xml-gallery-builder'),
    'ace_widget_slide_show_control' );
  }

}

?>