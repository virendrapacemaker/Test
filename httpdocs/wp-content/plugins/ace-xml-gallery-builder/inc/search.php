<?php
/**
 * AceSearchFrontend
 * Displays the results for a Wordpress search in the Gallery and appends to results
 * 
 * @package Ace-Gallery  
 * @author Marcel Brinkkemper
 * @copyright 2008-2012 Christopher
 * @version 1.0
 * @since 1.0
 * @access public
 */
class AceSearchFrontend extends AceFrontend {
  
  var $identifier = '__search__';
  
  var $query = '';
  
  var $found = false;
    
  /**
   * AceSearchFrontend::__construct()
   * 
   * @return
   */
  function __construct() {
    AceFrontend::__construct();
    $this->file = $this->identifier;
    $this->query = $_GET['s'];
    add_filter( 'the_posts', array(&$this, 'the_posts' ) );
    add_filter( 'post_link', array(&$this, 'post_link' ), 5, 2 );
  }
  
  /**
   * AceSearchFrontend::get_option()
   * Overrides options folders_page and thumbs_page
   * All results will be displayed on one page
   * 
   * @param mixed $option
   * @return
   */
  function get_option( $option ) {
    if ( 'folders_page' == $option ) return 0;
    if ( 'thumbs_page' == $option ) return 0;  
		if ( 'enable_slide_show' == $option ) return 'FALSE';      
    return AceGallery::get_option( $option );
  }
  
  /**
   * AceSearchFrontend::is_gallery()
   * 
   * @return
   */
  function is_gallery() {
    return true;
  }
  
  /**
   * AceSearchFrontend::the_posts()
   * Add search results to posts array
   * 
   * @param mixed $posts
   * @return
   */
  function the_posts( $posts ) {
    global $wp_query;    
    if ( $wp_query->is_search && ( '' != $this->query ) ) {
      $post = array();
      $post['ID'] = $this->get_option( 'gallery_id' ); 
      $post['post_title'] = $this->get_option( 'listed_as' );
      $post['post_status'] = 'publish';      
      $post['post_author'] = 1;
      $post['post_date'] = date('Y-m-d H:i:s');
      $post['guid'] = $this->get_option( 'gallery_prev');
      $post['post_type'] = 'page';
      $post['comment_status'] = 'closed';
      $result = $this->search_code();  
      $post['post_content'] = $result;       
      $post['post_excerpt'] = $result;
      if ( $this->found ) {   
        $posts[] = (object)$post;
      }
    }    
    return $posts;
  }
  
  /**
   * AceSearchFrontend::post_link()
   * Post link is alway the gallery page
   * 
   * @param mixed $permalink
   * @param mixed $post
   * @return
   */
  function post_link( $permalink, $post ) { 
    if ( $post->ID == $this->get_option( 'gallery_id' ) ) {
      $permalink = $this->get_option( 'gallery_prev');
    }   
    return $permalink;
  }
  
  /**
   * AceSearchFrontend::search_code()
   * Create gallery for search results
   * 
   * @return
   */
  function search_code() {
    ob_start();  
    $this->show();
    $new_content = ob_get_contents();
    ob_end_clean();
    return $new_content;
  }
  
  /**
   * AceSearchFrontend::create_navigation()
   * No navigation for search results
   * 
   * @return
   */
  function create_navigation() {
    return '';   
  }
  
  /**
   * AceSearchFrontend::is_image()
   * 
   * @param mixed $filevar
   * @return
   */
  function is_image( $filevar ) {
    if ( 0 != preg_match( "|$this->identifier|", $filevar )) return false;
    return AceGallery::is_image( $filevar );
  }
  
  /**
   * AceSearchFrontend::get_folder()
   * 
   * @return
   */
  function get_folder() { 
    return new AceSearchFolder( $this->identifier );
  }
  
  /**
   * AceSearchFrontend::_is_dir()
   * 
   * @return
   */
  function _is_dir() {  
    return true;
  }
  
}
 
/**
 * AceSearchFolder
 * Builds the folder with search results
 * 
 */
class AceSearchFolder extends AceFrontendFolder {
  
  /**
   * AceSearchFolder::valid()
   * 
   * @return
   */
  function valid() { 
    return true;
  }
  
  /**
   * AceSearchFolder::can_save()
   * 
   * @return
   */
  function can_save() {
    return false;
  }
  
  /**
   * AceSearchFolder::open()
   * 
   * @return
   */
  function open() {
    return true;
  }
  
  /**
   * AceSearchFolder::dirname()
   * 
   * @return
   */
  function dirname() {
    global $ace_gallery;
    return $ace_gallery->get_option( 'listed_as' );
  }
  
  /**
   * AceSearchFolder::user_can()
   * 
   * @param string $capability
   * @return bool
   */
  function user_can( $capability = '' ) {
  	$user_can = AceFolder::user_can( $capability );
  	return ( $capability == 'viewer' ) ? true : $user_can;
  }
  
  /**
   * AceSearchFolder::load()
   * Search for images
   * 
   * @param string $what
   * @return
   */
  function load( $what = 'thumbs' ) {
    global $ace_gallery;    
    if ( isset( $this->list ) ) {
      $this->_empty_list();
    }
    $this->list = array();
    $found = false; 
    $query = utf8_encode( htmlentities( $ace_gallery->query ) );
    $folderlist = $ace_gallery->search_in_xml( $query );
    if ( 0 == count( $folderlist ) ) 
			return false;
		foreach ( $folderlist as $path ) {
			$folder = new AceFolder( $path );
			$folder->open();
			
			if ( ! $folder->user_can( 'viewer' ) )
				continue;
				
			$folder->load( 'thumbs' );
			if ( 0 < count( $folder->list ) ) {
				foreach( $folder->list as $thumb ) {
					if ( strripos( $thumb->image, $ace_gallery->query ) !== false )
						$this->list[] = $thumb;
					else if ( strripos( $thumb->title, $ace_gallery->query ) !== false )
						$this->list[] = $thumb;
					else if ( strripos( $thumb->description, $ace_gallery->query ) !== false )
						$this->list[] = $thumb;		
				}
			}
		}	 
    if ( 0 < count( $this->list ) ) 
			$ace_gallery->found = true;
    return true;
  }
  
  /**
   * AceSearchFolder::subfolders()
   * Search for folders
   * 
   * @param string $show
   * @return
   */
  function subfolders( $show='visible' ) {
    global $ace_gallery;
    $folderlist = $ace_gallery->folders( 'subfolders', 'visible');
    $flist = array();   
    if ( 0 == count( $folderlist ) ) return false; 
    foreach( $folderlist as $folder ) {
      $folder->open();
			
			if ( ! $folder->user_can( 'viewer' ) )
				continue;
				
      if ( strripos( $folder->dirname(), $ace_gallery->query  ) !== false ) { 
        $flist[] = $folder;    
      } elseif ( strripos( $folder->title, $ace_gallery->query  ) !== false ) {
        $flist[] = $folder;
      } elseif ( strripos( $folder->description, $ace_gallery->query ) !== false ) {
        $flist[] = $folder;
      } 
    }
    if ( 0 != count( $flist ) )  $ace_gallery->found = true;
    return $flist;        
  }
  
  /**
   * AceSearchFolder::uri()
   * 
   * @return
   */
  function uri() {
    return '#';
  }
}

?>