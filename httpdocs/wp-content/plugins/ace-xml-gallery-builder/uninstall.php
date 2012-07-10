<?php
/**
 * Uninstalls the ace-galery options when an uninstall has been requested 
 * from the WordPress admin
 *
 * @package ace-xml-gallery-builder
 * @subpackage uninstall
 * @since 0.15.0
 */

/* check if the uninstaller is called from wordpress plugin admin page */
if( ! defined( 'ABSPATH' ) || ! defined ( 'WP_UNINSTALL_PLUGIN' ) )
	exit();


include_once( 'ace-xml-gallery-builder.php' );
/**
 * AceGalleryUninstaller
 * 
 * @package Ace Gallery  
 * @author Marcel Brinkkemper
 * @copyright (c) 2011 Christopher
 * @version 1.1.0
 * @since 1.1.0
 * @access public
 */
class AceGalleryUninstaller extends AceAdmin {
	
	/**
	 * AceGalleryUninstaller::__construct()
	 * 
	 * @return void
	 */
	function __construct() {
		AceAdmin::__construct();
	}	
	
	/**
	 * AceGalleryUninstaller::uninstall()
	 * The uninstall procedure
	 * wp_die s with an error message if one of the uninstall functions fails
	 * 
	 * @since 1.1.0
	 * @uses wp_die()
	 * @return void
	 */
	function uninstall() {
		$error_message = '';
		$error_message .= $this->uninstall_cache();
		$error_message .= $this->uninstall_titles();
		$error_message .= $this->uninstall_comment_meta();
		$error_message .= $this->uninstall_ace_table();	
		$this->uninstall_roles();	
		if ( '' != $error_message ) {
			$error_message = __( 'Ace Gallery could not uninstall. One or more items could not be removed.', 'ace-xml-gallery-builder' ) . '<br />' . $error_message;
			wp_die( $error_message );
		}
		// all data has been removed, delete the options
		delete_option( 'ace-xml-gallery-builder' );
		delete_option( 'ace-fields' );
		delete_option( 'widget_ace_list_folders' );
		delete_option( 'widget_ace_random_image' );
		delete_option( 'widget_ace_slide_show' );
	}
	
	/**
	 * AceGalleryUninstaller::uninstall_cache()
	 * Remove thumbs and slides directories
	 * 
	 * @since 1.1.0
	 * @return string '' if successful
	 */
	function uninstall_cache() {
		return ( $this->clear_cache() ) ? '' : __( ' Could not clear your thumbnails and/or slides cache', 'ace-xml-gallery-builder' ) . '<br />';
	}
	
	/**
	 * AceGalleryUninstaller::uninstall_titles()
	 * Remove all images.xml
	 * 
	 * @since 1.1.0
	 * @return string '' if successful
	 */
	function uninstall_titles() {
		$success = true;
		$folderlist = $this->folders();
		if ( 0 != count( $folderlist ) ) { 
	 		foreach ( $folderlist as $folder ) {
	 			$titles = $this->root . $folder->curdir . 'images.xml';
      	if ( file_exists( $titles ) ) {
        	if ( @unlink( $titles ) == false )
          	$success =  false;
      	}
 	  	}
  	}  	
  	return $success ? '' : __( 'Could not remove folder and image data in images.xml', 'ace-xml-gallery-builder' ) . '<br />'; 
	}
	
	/**
	 * AceGalleryUninstaller::uninstall_comment_meta()
	 * Remove comment meta for gallery comments
	 * 
	 * @since 1.1.0
	 * @return string '' if successful
	 */
	function uninstall_comment_meta() {
		global $wpdb;
		$success = true;
		$query = "SELECT COUNT(*) AS cnt FROM $wpdb->commentmeta WHERE meta_key = 'ace'";
		$results = $wpdb->get_results( $query );
    $cnt = ( ! empty( $results ) ) ? ( $results[0]->cnt ) : 0;
		if ( 0 != $cnt ) {	      
	 		$query = "DELETE FROM $wpdb->commentmeta WHERE meta_key = 'ace'";
	    $success = $wpdb->query( $query, ARRAY_A );
    }
    return ( false !== $success ) ? '' : sprintf( __( 'Could not remove comment meta from %s', 'ace-xml-gallery-builder' ), $wpdb->commentmeta ) . '<br />';
	}
	
	/**
	 * AceGalleryUninstaller::uninstall_ace_table()
	 * Remove the ace id -> file table
	 * 
	 * @since 1.1.0
	 * @return string '' if successful
	 */
	function uninstall_ace_table() {
		global $wpdb;
		$success = true;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$this->table'") == $this->table ) {
			$success = $wpdb->query( "DROP TABLE $this->table" ); 
		}	
		return ( false !== $success ) ? '' : sprintf( __( 'Could not remove table %s from the WordPress database', 'ace-xml-gallery-builder' ), $this->table ) . '<br />';
	}
	
	function uninstall_roles() {
    require_once( ABSPATH . 'wp-includes/pluggable.php' );
    $blogusers = ace_get_users_of_blog();    
    foreach( $blogusers as $user ) { // check if users have one or more roles and add role ace_editor
      if ( $user->has_cap( 'ace_manager' ) )
				$user->remove_role( 'ace_manager' );
			if ( $user->has_cap( 'ace_editor' ) )
				$user->remove_role( 'ace_editor' );
			if ( $user->has_cap( 'ace_author' ) )
				$user->remove_role( 'ace_author' );		 
    }
    remove_role( 'ace_manager' );
		remove_role( 'ace_editor' );
		remove_role( 'ace_author' );	
  }
	
} // AceGalleryUninstaller
	
$ace_uninstaller = new AceGalleryUninstaller();
$ace_uninstaller->uninstall();
unset( $ace_uninstaller );

/* Done, Sorry you had to uninstall Ace Gallery */
?>