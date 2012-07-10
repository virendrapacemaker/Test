<?php
 
/**
 * ace_version()
 * Get Ace Gallery Current version from plugin file
 * @return string
 * 
 */
function ace_version() {
  require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
  global $ace_gallery;
  $plugin_data = get_plugin_data( $ace_gallery->plugin_file );
  return $plugin_data['Version'];
}  

/**
 * Last version where options or database settings have changed
 */
define('ACE_SECURE_VERSION', '1.1');

?>