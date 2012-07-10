<?php
/*
Plugin Name: Ace Gallery Extra Fields
Plugin URI: http://askchrisedwards.com/ace/gallery/
Description: Add your extra fields to Folders and Images in Ace Gallery 
Author: Christopher
Author URI: http://askchrisedwards.com
Version: 1.1.12
Date: 2012 June
License: GNU GPLv2
Text Domain: ace-xml-gallery-builder/languages
*/

/**
 * AceFields
 * This Plugin adds functionality to Ace Gallery to add user defined fields
 * - Adds box to Ace Gallery setting to edit field  names and types
 * - Adds simple filters and actions to display the fields 
 * 
 * @package Ace Gallery
 * @author Marcel Brinkkemper
 * @copyright 2011-2012 Christopher
 * @version 1.1.0
 * @access public
 */
class AceFields {
  
  var $fields;
    
  /**
   * AceFields::__construct()
   * 
   * @since 1.1.0
   * @uses add_action()
   * @uses add_filter()
   * @uses get_option()
   * @return void
   */
  function __construct() {
    // core actions
    add_action( 'ace_ready', array( &$this, 'ready' ) );  
    add_action( 'ace_settings_main', array( &$this, 'settings_main' ) );
    
    // admin filters
    add_filter( 'ace_update_options', array( &$this, 'update_options') );
    
    // frontend filters and actions
    add_filter( 'ace_thumbs_folder_header', array( &$this, 'folder_header' ), 5, 2 );
    add_filter( 'ace_after_folder_description', array( &$this, 'folder_description' ), 5, 2 );
    add_filter( 'ace_thumb_description', array( &$this, 'thumb_description' ), 5, 2 );
    add_action( 'ace_frontend_slide', array( &$this, 'frontend_slide' ), 5, 2 );
    
    $options = get_option( 'ace-fields' );
	//print_r($options);
    $this->fields = ( false !== $options ) ? $options : array();
    
  }
  
  /**
   * AceFields::ready()
   * Adds fields to Ace Gallery
   * 
   * @since 1.1.0
   * @return void
   */
  function ready() {
  global $ace_gallery;
   //echo "<pre>";
  // print_r($ace_gallery);
  //print_r($this->fields);
    if ( 0 < count( $this->fields) ) {
      foreach( $this->fields as $field ) {
        ace_add_extrafield( $field['name'], $field['display'], $field['target'], $field['edit'],$field['order'] ); 
      }
    }
  }
  
  /**
   * AceFields::fields_rows()
   * Adds rows to edit fields in the Settings page
   * 
   * @since 1.1.0
   * @return void
   */
  function fields_rows() {
    $tdname = '<td><input id="name_%d" name=ace-xml-gallery-builder[extra][%d][name] type="text" value="%s" size="16"  /></td>';
    $tddisplay = '<td><input id="display_%d" name="ace-xml-gallery-builder[extra][%d][display]" type="text" value="%s" size="32"  /></td>';
    $tdtarget = '<td><select id="target_%d" name="ace-xml-gallery-builder[extra][%d][target]"><option value="image"%s>%s</option><option value="folder"%s>%s</option></select></td>';
    $tdedit = '<td><input type="checkbox" id="edit_%d" name="ace-xml-gallery-builder[extra][%d][edit]" %s /></td>';
	$tdorder = '<td><input id="order_%d" name=ace-xml-gallery-builder[extra][%d][order] type="text" value="%s" size="3"  /></td>';
	$tddelete = '<td><input type="button" id="delete_%d" name="ace-xml-gallery-builder[extra][%d][delete]" %s value="Delete" onclick="deletefeilds(this.id);"/></td>';
     
    if ( 0 < count( $this->fields ) ) {
	//if (count( $this->fields) >6 ) {
      for( $i = 1; $i <= count( $this->fields ); $i++ ) {
        $field = $this->fields[$i-1]; 
        if ( '' == $field['name'] )
          continue;
        $row = '<tr id="tr_'.$i.'">';
        $row .= sprintf( $tdname, $i, $i, esc_attr( $field['name'] ) ); 
        $row .= sprintf( $tddisplay, $i, $i, esc_attr( $field['display'] ) ); 
        $row .= sprintf( $tdtarget, $i, $i,
          ( 'image' == $field['target'] ) ? ' selected="selected"' : '',
          esc_html__( 'Image', 'ace-xml-gallery-builder' ),
          ( 'folder' == $field['target'] ) ? ' selected="selected"' : '',
          esc_html__( 'Folder', 'ace-xml-gallery-builder' )
        );
        $row .= sprintf( $tdedit, $i, $i, $field['edit'] ? 'checked="checked"' : '' );
		$row .= sprintf( $tdorder, $i, $i, esc_attr( $field['order'] ) );
		$row .= sprintf( $tddelete, $i, $i, esc_attr( $field['delete'] ) );
        $row .= '</tr>';
		
        echo $row;
      }
    }else{
	     $row= '<tr><td colspan="6" align="center">There is no Field.Click Add New Button.</td></tr>';
		 echo $row;
	} 
	/*$i = 0;
    $row = '<tr>';
    $row .= sprintf( $tdname, $i, $i, '' ); 
    $row .= sprintf( $tddisplay, $i, $i, '' );
    $row .= sprintf( $tdtarget, $i, $i, '',
      esc_html__( 'Image', 'ace-xml-gallery-builder' ), '',
      esc_html__( 'Folder', 'ace-xml-gallery-builder' )
    );
    $row .= sprintf( $tdedit, $i, $i, '' );
	$row .= sprintf( $tdorder, $i, $i, '' );
	$row .= sprintf( '<td></td>', $i, $i, '' );
    $row .= '</tr>'; 
    echo $row;*/  
  }                          
  
  /**
   * AceFields::settings_main()
   * Add extra box with filed edits to the settings page
   * 
   * @since 1.1.0
   * @return void
   */
  function settings_main() {
    global $ace_gallery;
    ?>
	<script type="text/javascript">
	function addnew1(){
			//alert('click');
			jQuery('#ace_extra_fields_table').append('<tr><td><input type="text" size="16" value="" name="ace-xml-gallery-builder[extra][0][name]" id="name_0"></td><td><input type="text" size="32" value="" name="ace-xml-gallery-builder[extra][0][display]" id="display_0"></td><td><select name="ace-xml-gallery-builder[extra][0][target]" id="target_0"><option value="image">Image</option><option value="folder">Folder</option></select></td><td><input type="checkbox" name="ace-xml-gallery-builder[extra][0][edit]" id="edit_0"></td><td><input type="text" size="3" value="" name="ace-xml-gallery-builder[extra][0][order]" id="order_0"></td><td></td></tr>');

	}
	</script>
    <div id="ace_extra_field_options" class="postbox">
      <h3 class="hndle"><span><?php esc_html_e( 'Extra Fields', 'ace-xml-gallery-builder' ) ?></span></h3>
      <div class="inside" style="height:10px;">
               <div style="float:left;width:420px;"><p><?php esc_html_e( 'Enter your own fields to be stored along with your Folders or Images.', 'ace-xml-gallery-builder' ); ?></p>
			   </div>
		       <div class="submit" style="float:right;width:82px;padding:0px;">
                <!--<input type="submit" value="Add New" name="ace-xml-gallery-builder[update_options]" class="button-primary">-->
				<input type="button" value="Add New" id="addnew" name="" class="button-primary" onclick="addnew1();">
              </div>
			  <div style="clear:both;">&nbsp;</div>
      </div>
      <table id="ace_extra_fields_table" class="widefat">
        <thead>
          <tr>
            <th scope="col"><?php esc_html_e( 'Name', 'ace-xml-gallery-builder' ) ?></th>
            <th scope="col"><?php esc_html_e( 'Display Name', 'ace-xml-gallery-builder' ) ?></th>
            <th scope="col"><?php esc_html_e( 'Type', 'ace-xml-gallery-builder' ) ?></th>
            <th scope="col"><?php esc_html_e( 'Editable', 'ace-xml-gallery-builder' ) ?></th>
			<th scope="col"><?php esc_html_e( 'Order', 'ace-xml-gallery-builder' ) ?></th>
			<th scope="col"><?php esc_html_e( 'Delete', 'ace-xml-gallery-builder' ) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $this->fields_rows(); ?>                              								
        </tbody>
      </table>
    </div>
    <?php            
  }
  
  
  /** 
   * AceFields::update_options()
   * Filter the options from the settings page and save the fields
   * 
   * @since 1.1.0
   * @uses esc_attr()
   * @uses update_option()
   * @param array $options
   * @return array
   */
  function update_options( $options ) {  
    // print($options);
    if ( isset( $options['extra'] ) ) {      
      $this->fields = array();
      foreach( $options['extra'] as $field ) {  
        if ( '' == $field['name'] )
          continue;
        $temp = esc_attr( $field['name'] ); 
        $field['name'] = esc_attr( strtolower( str_replace( ' ', '', $field['name'] ) ) );
        $field['display'] = ( isset( $field['display']) && ( '' != $field['display'] ) ) ? strip_tags( $field['display'] ) : ucfirst( $temp );
        $field['edit'] = ( isset( $field['edit'] ) ) ? true : false;
		$field['order'] = ( isset( $field['order'] ) ) ? $field['order'] :$field['order'];
		$field['delete'] = ( isset( $field['delete'] ) ) ? $field['delete'] : $field['delete'];
        $this->fields[] = $field;
		//print_r($field);
      }
	  //print_r($this->fields);
      update_option( 'ace-fields', $this->fields );
      unset( $options['extra'] ); // don't save them with ace-xml-gallery-builder options
    }
	//print_r($options);
    return $options;
  }
  
  /**
   * AceFields::folder_header()
   * Appends the Folder fields to the header above the thumbs
   * 
   * @since 1.1.0
   * @param string $header
   * @param AceFolders $folder
   * @return string
   */
  function folder_header( $header, $folder ) {
    if ( 0 != count( $this->fields ) ) {
      foreach( $this->fields as $field ) {
        if ( 'folder' == $field['target'] ) {
          $header .= sprintf ( '<div class="extra-field %s"><p><span class="name">%s</span> <span class="value">%s</span></p></div>', $field['name'],
            esc_html( $field['display'] ), 
            ace_html( $folder->get_extra_field( $field['name'] ) )
          );
        }
      } 
    }
   return $header;
  }
  
  /**
   * AceFields::folder_description()
   * Appends extra fileds to folder description in thumbnail view
   * 
   * @since 1.1.10
   * @param string $description
   * @param AceFolder $folder
   * @return void
   */
  function folder_description( $after, $folder ) {
  	if ( 0 != count( $this->fields ) ) {
      foreach( $this->fields as $field ) {
        if ( 'folder' == $field['target'] ) {
          $after .= sprintf ( '<div class="extra-field %s"><p><span class="name">%s</span> <span class="value">%s</span></p></div>', $field['name'],
            esc_html( $field['display'] ), 
            ace_html( $folder->get_extra_field( $field['name'] ) )
          );
        }
      } 
    }
   return $after;
  }
  
  /**
   * AceFields::frontend_slide()
   * Appends the fields to the text below the slide
   * 
   * @since 1.1.0
   * @param AceImage $image
   * @return void
   */
  function frontend_slide( $image ) {
    if ( 0 != count( $this->fields ) ) {
      foreach( $this->fields as $field ) {
        if ( 'image' == $field['target'] ) {
          printf ( '<div class="extra-field %s"><p><span class="name">%s</span> <span class="value">%s</span></p></div>', $field['name'],
            esc_html( $field['display'] ), 
            ace_html( $image->get_extra_field( $field['name'] ) )
          );
        }
      } 
    }
  }
  
  /**
   * AceFields::thumb_description()
   * 
   * @param string $description
   * @param AceImage $image
   * @return string
   */
  function thumb_description( $description, $image ) {
  	if ( 0 != count( $this->fields ) ) {
	foreach( $this->fields as $field1 ) {
		 $odrer[]=$field1['order'];
		 }
		 array_multisort($odrer, SORT_DESC, $this->fields);
		 sort($this->fields,true);
	//print_r($this->fields);
      foreach( $this->fields as $field ) {
	  sort($this->fields,true);
        if ( 'image' == $field['target'] ) {
          $description .= sprintf ( '<div class="extra-field %s"><p><span class="name">%s</span> <span class="value">%s</span></p></div>', $field['name'],
            esc_html( $field['display'] ), 
            ace_html( $image->get_extra_field( $field['name'] ) )
          );
        }
      }     
		}
		return $description;
  }
  
} // AceFields

$ace_fields = new AceFields();