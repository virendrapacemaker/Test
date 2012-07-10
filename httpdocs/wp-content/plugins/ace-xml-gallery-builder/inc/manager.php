<?php

/**
 * AceAdminFolder
 * Thanks to:
 * Denis Howlett <feedback@isocra.com> http://www.isocra.com/ for Table drag and Drop
 * Stuart Langridge, http://www.kryogenix.org/days/2007/04/07/sorttable-v2-making-your-tables-even-more-sortable for Table Sorting script
 * Cory S.N. LaViska, a Beautiful Site http://abeautifulsite.net/ for Context menu script
 * 
 * @package Ace-Gallery  
 * @author Marcel Brinkkemper (ace@brimosoft.nl)
 * @copyright 2008-2012 Christopher
 * @since 0.16.0
 * @access public
 */
class AceAdminFolder extends AceFolder {
  
  /**
   * Holds the number of images in this folder to prevent re-reading of the directory
   * @var int
   * @since 1.1.3.1
   */
  var $thiscount;
  
  /**
   * AceAdminFolder::__construct()
   * 
   * @param mixed $path
   * @return void
   */
  function __construct( $path ) {
    global $ace_gallery;
    AceFolder::__construct( $path );
    $this->thiscount = $this->count();
  }
  
  function do_actions() {
  	global $ace_gallery;
    if( isset( $_REQUEST['update_folder'] ) || isset( $_REQUEST['update_folder-s'] ) ) { 
      $this->save_edits();
    }
    if ( isset( $_REQUEST['sort_gallery_structure'] ) || isset( $_REQUEST['sort_gallery_structure-s'] ) ) {
	    $this->save_changed_folders();		
    }
    if ( isset( $_REQUEST['file_to_delete'] ) ) {
      $this->delete_file();
    }   
    if ( isset( $_REQUEST['create_new_folder'] ) ) {
     	if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'ace_manage_folder' ) ) {
    		$thisname = $this->curdir . $_REQUEST['new_folder_name'];
    		$_POST['folder'] = urlencode( $this->curdir );
    		$ace_gallery->new_gallery_folder( $thisname );
			}
    }
    if ( isset( $_REQUEST['move_to'] ) ) {
      $this->copy_image( 'move' );
    }      
    if ( isset( $_REQUEST['copy_to'] ) ) {
      $this->copy_image( 'copy' );
    }
    if ( isset( $_REQUEST['add-author'] ) ) {
      $this->add_author();
    }
    if ( isset( $_REQUEST['remove-author'] ) ) {
      $this->remove_author();
    }
  }
  
  
  /**
   * AceAdminFolder::manage()
   * Show the Edit Ace Gallery Folder screen
   * 
   * @return void
   */
  function manage() {  
    global $ace_gallery, $wp_version;  
    $this->open(); // read folder fields from images.xml  
    $this->do_actions();
    $action_url = admin_url( 'admin.php' ) . '?page=ace-filemanager&amp;folder=' . ace_nice_link( $this->curdir ); 
    ?>    
    <div class="wrap">
      <?php screen_icon( $this->visibility ); ?>
      <h2><?php esc_html_e( 'Edit Ace Gallery Folder ', 'ace-xml-gallery-builder' ); ?></h2>
			<?php $ace_gallery->options_message() ?> 
      <div id="ajax-div"></div>    
      <?php if ( version_compare( $wp_version, '3.4', '<' ) ) : ?> 
      <div id="poststuff" class="metabox-holder has-right-sidebar">
      <?php else : ?>       
      <div id="poststuff" class="metabox-holder">
      <?php endif; ?>
        <form name="sort_images_form" id="sort_images_form" method="post" action="<?php echo $action_url ?>">      
        <?php if ( function_exists( 'wp_nonce_field' ) ) wp_nonce_field( 'ace_manage_folder' ); ?>        
        <input id="manage_folder" type="hidden" name="manage_folder" value="<?php echo $this->form_name(); ?>" /> 
        <input id="directory" type="hidden" name="directory" value="<?php echo ace_nice_link( $this->curdir ); ?>"/>        
        <input id="folder_id" type="hidden" name="folder" value="<?php echo $this->id; ?>" />         
  		  <input type="hidden" name="imagesbox" value="<?php echo sanitize_title( $this->curdir ); ?>" />				
       	<?php if ( version_compare( $wp_version, '3.4', '<' ) ) : ?> 
						<?php $this->sidebar() ?>         		
          	<div id="post-body">  
         	<?php else : ?>
						<div id="post-body" class="metabox-holder columns-2">		              	
         		<?php $this->sidebar() ?>
         	<?php endif; ?>
          <div id="post-body-content">
            <?php $this->titlediv(); ?>
            <br /><br class='clear' /> 
            <?php if ( $this->user_can(  'editor' ) ) $this->editor_toolbar(); ?>
            <?php $this->descriptiondiv(); ?>
            <div id="imagesdiv">
              <?php $this->imagesbox(); ?>
            </div>
            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
			 <?php $this->foldersbox(); ?>
          	</div>
          </div> 					
        </div>   
        </form>    
      </div>
    </div>
    <?php    
  }
  
  function editor_toolbar() {
  	global $ace_gallery;
  	$action_url = add_query_arg( array( 'action' => 'ace_media', 'folder' => ace_nice_link( $this->curdir ), 'width' =>640,'height'=>300, 'TB_iframe' => 1 ), wp_nonce_url(  'admin-ajax.php' ) );
  	?>
  	<div id="editor-toolbar" class="wp-editor-tools">
	  	<div class="hide-if-no-js wp-media-buttons" id="wp-media-buttons"><?php echo esc_html__( 'Upload Image(s)', 'ace-xml-gallery-builder' ); ?>
				<a title="<?php esc_html_e( 'Add an Image', 'ace-xml-gallery-builder' ) ?>" class="thickbox add_image" id="content-add_image" href="<?php echo $action_url ?>">
					<img onclick="return false;" alt="<?php esc_html_e( 'Add Image(s)', 'ace-xml-gallery-builder' ) ?>" src="<?php echo admin_url( 'images/media-button-image.gif' ); ?>" />
				</a>
			</div>
		</div>
		<?php
  }
  
 	/**
	 * Display a view switcher
   * Copied from WordPress 3.1.0
	 *
	 * @since 1.1.0
	 */
	function view_switcher( $current_mode ) {
		$modes = array(
			'list'    => esc_html__( 'List View', 'ace-xml-gallery-builder' ),
			'excerpt' => esc_html__( 'Edit View', 'ace-xml-gallery-builder' )
		);
    ?> <div id="ace-view-switch" class="view-switch"><?php
		foreach ( $modes as $mode => $title ) {
			$class = ( $current_mode == $mode ) ? 'class="current"' : '';
      echo sprintf ( '<a href="%s" id="view-link-%s" %s><img id="view-switch-%s" src="%s" width="20" height="20" title="%s" alt="%s" /></a>',
        esc_url( add_query_arg( 'mode', $mode, $_SERVER['REQUEST_URI'] ) ), 
        $mode, 
        $class, 
        $mode, 
        esc_url( includes_url( 'images/blank.gif' ) ), 
        $title, 
        $title 
      ) . "\n";
		}
		?></div><?php
	}
  
  /**
   * AceAdminFolder::sidebar()
   * Build the sidebar
   * 
   * @since 1.1.0
   * @return void
   */
  function sidebar() {
  	global $wp_version;
    ?>
	    <?php if ( version_compare( $wp_version, '3.4', '<' ) ) : ?> 
	    <div id="side-info-column" class="inner-sidebar">
	    <?php else : ?>
	    <div id="postbox-container-1" class="postbox-container inner-sidebar">
	    <?php endif; ?>
      <div id="side-sortables" class="meta-box-sortables ui-sortable">      
      <?php
      $this->submitbox();
      $this->newfolderbox();
      $this->editorbox();
      $this->viewerbox();
      $this->extrabox();
      ?>
      </div>
    </div>
    <?php
  }
  
  /**
   * AceAdminFolder::submitbox()
   * Box with folder information and main submit button
   * 
   * @since 1.1.0
   * @return void
   */
  function submitbox() {
    switch ( $this->visibility ) {
      case 'visible': $visibility = esc_html__( 'Visible', 'ace-xml-gallery-builder' ); break;      
      case 'hidden': $visibility = esc_html__( 'Hidden', 'ace-xml-gallery-builder' ); break;
      case 'private': $visibility = esc_html__( 'Private', 'ace-xml-gallery-builder' ); break;
    }
    $datef = __( 'M j, Y @ G:i' );
    $stamp = __('Created on: <b>%1$s</b>');
    $date = date_i18n( $datef, $this->datetime );
    $disabled = $this->user_can( 'editor' ) ? '' : 'disabled="disabled"';
    $notprivate = ( -1 == $this->editor ) ? 'display:none;' : 'display:block;';
     
    ?>     
    <div id="submitdiv" class="postbox">
      <h3 class="hndle"><span><?php esc_html_e( 'Folder', 'ace-xml-gallery-builder') ?></span></h3>
      <div class="inside">
        <div id="submitpost" class="submitbox">
          <div id="minor-publishing-actions">           
            <div id="preview-action">
              <a class="preview button" href="<?php echo $this->uri(); ?>" target="_blank"><?php esc_html_e( 'View this Folder','ace-xml-gallery-builder' ); ?></a>
              <br />
            </div>
            <div class="clear"></div>
            <div class="misc-pub-section "></div>
          </div>
          <div id="misc-publishing-actions">            
            <div id="path" class="misc-pub-section ">            
              <?php esc_html_e( 'Path', 'ace-xml-gallery-builder' ); ?>:
              <?php $this->breadcrumbtrail()  ?> 
            </div>
            <div id="visibility" class="misc-pub-section ">
              <?php esc_html_e( 'Visibility', 'ace-xml-gallery-builder' ); ?>:
              <span id="post-visibility-display"><?php echo $visibility ?></span>                        
              <div id="post-visibility-select">
                <p class="visibility fvisible"><input type="radio" <?php checked( 'visible', $this->visibility ); ?> value="visible" id="visibility-radio-public" name="visibility" <?php echo $disabled ?> />
                <label class="selectit" for="visibility-radio-public"><?php esc_html_e( 'Visible', 'ace-xml-gallery-builder' ); ?></label></p>                          
                <p style="<?php echo $notprivate; ?>" class="visibility fprivate"><input type="radio" <?php checked( 'private', $this->visibility ); ?> value="private" id="visibility-radio-private" name="visibility" <?php echo $disabled ?> />
                <label class="selectit" for="visibility-radio-private"><?php esc_html_e( 'Private', 'ace-xml-gallery-builder' ); ?></label></p>                         
                <p class="visibility fhidden"><input type="radio" <?php checked( 'hidden', $this->visibility ); ?> value="hidden" id="visibility-radio-hidden" name="visibility" <?php echo $disabled ?> />
                <label class="selectit" for="visibility-radio-hidden"><?php esc_html_e( 'Hidden', 'ace-xml-gallery-builder' ); ?></label></p>                         
              </div>            
            </div>
            <div class="misc-pub-section curtime misc-pub-section-last">
              <span id="timestamp"><?php printf($stamp, $date); ?></span>
            </div>
          </div>
          <?php                  
          if ( $this->user_can( 'editor' ) ) {
            $delete_url = wp_nonce_url( sprintf( 'admin.php?page=ace-filemanager&amp;delete_folder=%s', ace_nice_link( $this->curdir ) ), 'ace_delete_folder' );                         
          ?>
          <div id="major-publishing-actions">
            <div id="delete-action">
              <a onclick="if ( confirm('<?php _e( 'You are about to delete this folder and all contents and subfolders', 'ace-xml-gallery-builder'); ?> \'<?php esc_html_e( $this->form_name() ); ?>\'\n  \'<?php _e( 'Cancel', 'ace-xml-gallery-builder'); ?>\'<?php _e(' to stop, '); ?> \'OK\'<?php _e(' to delete.'); ?>') ) { return true;}return false;" class="submitdelete deletion" href="<?php echo $delete_url; ?>"><?php _e( 'Delete folder','ace-xml-gallery-builder' ) ?></a>
            </div>
            <div id="publishing-action">
              <img alt="" id="ajax-loading" class="ajax-loading" src="<?php echo admin_url( 'images/wpspin_light.gif' ); ?>" style="visibility: hidden;" />
              <input type="submit" class="button button-highlighted button-primary" name="update_folder" value="<?php esc_html_e('Update Folder', 'ace-xml-gallery-builder' ); ?>" />
            </div>
            <div class="clear"></div>
          </div>                  
          <?php 
          }
          ?>                    		
        </div>
      </div>
    </div>
    <?php
  }  
  
  /**
   * AceAdminFolder::newfolderbox()
   * Show the box to enter a new subfolder
   * 
   * @since 1.1.0
   * @return void
   */
   function newfolderbox() {
    global $ace_gallery;
    $ace_gallery->newfolderbox( $this );
  }
  
  /**
   * AceAdminFolder::editorbox()
   * Box with Author and ditor information
   * 
   * @since 1.1.0
   * @return void
   */
  function editorbox() {
    global$wp_roles;
    $blogusers = ace_get_users_of_blog(); 
    $aoptions = $foptions = $authors = array(); 
    $editor = ( $this->editor == 0 ) ? $current_user->ID : $this->editor;
    $acnt = 0;        
    $selected = ( -1 == $this->editor ) ? 'selected="selected"' : '';
    $editor = esc_html__( 'Not selected', 'ace-xml-gallery-builder' );
    $foptions[] = sprintf( '<option value="-1" %s>%s </option>', 
      $selected,      
      $editor
    ); 
    foreach( $blogusers as $user ) {
      $selected = '';
      if ( $user->ID == $this->editor ) {
        $selected = 'selected="selected"';
        $editor = $user->user_nicename;
      }
      $optionval = sprintf( '<option value="%s" %s>%s </option>', 
        $user->ID, 
        $selected,
        $user->user_nicename
      );
      if ( ! $user->has_cap( 'manage_ace_files' ) ) {
        $foptions[] = $optionval;             
        if ( in_array( $user->ID, $this->authors ) && ( $user->ID != $this->editor ) ) { // user has folder author capabilities       
          $aoptions['has'][] = $optionval;
          if ( 6 > $acnt )
            $authors[] = $user->user_nicename;
          if ( 6 == $acnt )
            $authors[] .= '&hellip;';
        } else {
          if ( $user->ID != $this->editor )
            $aoptions['not'][] = $optionval;
        }
      }
    }
    
    $addremove = esc_html__( 'Add / Remove', 'ace-xml-gallery-builder' );
    $add = esc_html__( 'Add' , 'ace-xml-gallery-builder' ) . ' &raquo;';
    $remove = '&laquo; ' . esc_html__( 'Remove', 'ace-xml-gallery-builder' );
    $users = esc_html__( 'Users', 'ace-xml-gallery-builder' );
    $authorstyle = ( isset( $_REQUEST['edit'] ) && ( $_REQUEST['edit'] == 'authors') ) ? 'display:block;' : 'display:none;';
    $authorclass =  ( isset( $_REQUEST['edit'] ) && ( $_REQUEST['edit'] == 'authors') ) ? 'ace' : 'hide-if-ajax';
    ?>
    <div class="postbox" id="editordiv">
      <h3 class="hndle"><span><?php esc_html_e('Users', 'ace-xml-gallery-builder' ); ?></span></h3>
      <div class="inside">
        <div id="ace-editor" class="misc-pub-section">
          <label for="editor"><?php esc_html_e('Editor', 'ace-xml-gallery-builder' ); ?>
          <?php if ( current_user_can( 'manage_ace_files' ) || current_user_can( 'manage_options' ) ) : ?>        
          <select id="editor" name="folder_editor">
            <?php echo implode( $foptions ); ?>
          </select></label>
          <?php else : ?>
          <p><?php echo $editor; ?></p></label>
          <input type="hidden" name="folder_editor" value="<?php echo $this->editor; ?>" />
          <?php endif; ?>
        </div>
        <div id="ace-authors" class="misc-pub-section misc-pub-section-last">
          <p><?php esc_html_e( 'Authors', 'ace-xml-gallery-builder' ); ?>: <span id="list-authors" class="users-list"><?php echo implode( ', ', $authors ); ?></span></p>
          <?php if ( $this->user_can( 'editor') ) : ?>
          <p class="hide-if-no-ajax"><a id="add-remove-author" class="button-secondary" href="<?php echo add_query_arg( 'edit', 'authors') ?>"><?php echo $addremove; ?></a></p>           
          <div id="edit_authors" class="<?php echo $authorclass; ?>" style="<?php echo $authorstyle; ?>">
            <div id="not-author" class="has_role">
              <p><strong><?php echo $users ?></strong></p>
              <select class="multiple" id="not-authors" name="not-authors[]" multiple="multiple" size="5">
                <?php if ( isset( $aoptions['not'] ) ) { echo implode( $aoptions['not'] ); }  ?>                             
              </select>
              <p class="authorbutton"><input class="button-secondary" id="add-fauthor" name="add-author" type="submit" value="<?php echo $add ?>" /> <img alt="" id="author-ajax-loading" src="images/wpspin_light.gif" class="ajax-loading" /></p>      
            </div>
            <div id="is-author" class="has_role">
              <p><strong><?php esc_html_e( 'Authors', 'ace-xml-gallery-builder' ); ?></strong></p>
              <select class="multiple" id="is-authors" name="is-authors[]" multiple="multiple" size="5">
                <?php if ( isset( $aoptions['has'] ) ) { echo implode( $aoptions['has'] ); }  ?>                             
              </select>      
              <p class="authorbutton"><input class="button-secondary" id="remove-fauthor" name="remove-author" type="submit" value="<?php echo $remove ?>" /></p>
            </div>
            <div class="clear"></div>   
          </div>
          <?php endif; ?>                     
        </div>
      </div>
    </div>
    <?php
  }
  
  /**
   * AceAdminFolder::extrabox()
   * Show box to edit extra fields
   * 
   * @since 1.1.0 
   * @return void 
   */
  function extrabox() {
    if ( ! $this->user_can( 'editor' )  ) return;
    global $ace_gallery;        
    $folderfields = $ace_gallery->get_fields( 'folder' ); 
    if ( ( false !== $folderfields ) && ( 0 < count( $folderfields) ) ) { // show extra fields if admin enabled
      $edit = false;
      foreach ( $folderfields as $field ) {
        if ( $field['edit'] ) {
          $edit = true;
          break;
        }
      }
      if ( $edit ) {
      ?>
      <div id="folder_extrafields" class="postbox">
        <div class="handlediv"><br /></div><h3 class='hndle'><span><?php esc_html_e( 'Extra Fields', 'ace-xml-gallery-builder' ); ?></span></h3>
        <div class="inside">
        <?php foreach ( $folderfields as $field ) {
          if ( $field['edit'] ) { ?>
          <label for="<?php echo $field['name']; ?>"><?php echo $field['display'] ?></label><input name="<?php echo $field['name']; ?>" type="text" size="32" value="<?php echo ( isset( $this->extra_fields[$field['name']] ) ) ? htmlspecialchars( stripslashes( $this->extra_fields[$field['name']] ), ENT_QUOTES )  : ''; ?>" />
        <?php } } ?>                
        </div>
      </div>
      <?php  
    } }
  }
  

  /**
   * AceAdminFolder::viewerbox()
   * Show box to select viewer level
   * 
   * @since 1.1.0
   * @return void
   */
  function viewerbox() {
    if ( ! $this->user_can( 'editor' ) ) // only editors or higher can set viewer level
      return;
    if ( '' == $this->viewer_level )
      $this->viewer_level = 'everyone';    
		      
		$notlogin = esc_attr__('Viewer does not have to log on to your blog.', 'ace-xml-gallery-builder' );   
    ?> 
    <div class="postbox" id="viewerdiv">
      <h3 class="hndle"><span><?php esc_html_e( 'Viewers', 'ace-xml-gallery-builder' ); ?></span></h3>
      <div class="inside" id="check_roles">        
        <div class="misc-pub-section misc-pub-section-last" id="roles_div">
        <p><strong><?php esc_html_e( 'Minimum level to view this folder', 'ace-xml-gallery-builder' ); ?></strong><br /></p>
          <label><input type="radio" name="viewer_level" value="editor" <?php checked( 'editor', $this->viewer_level ); ?> /> <?php esc_html_e( 'Editor' ) ?></label><br />
          <label><input type="radio" name="viewer_level" value="author" <?php checked( 'author', $this->viewer_level ); ?> /> <?php esc_html_e( 'Author' ) ?></label><br />          
          <label><input type="radio" name="viewer_level" value="contributor" <?php checked( 'contributor', $this->viewer_level ); ?> /> <?php _e( 'Contributor' ) ?></label><br />          
          <label><input type="radio" name="viewer_level" value="subscriber" <?php checked( 'subscriber', $this->viewer_level ); ?> /> <?php esc_html_e( 'Subscriber' ) ?></label><br />
          <label title="<?php echo $notlogin ?>"><input title="<?php echo $notlogin ?>" type="radio" name="viewer_level" value="everyone" <?php checked( 'everyone', $this->viewer_level ); ?> /> <?php  esc_html_e( 'All visitors' ) ?></label><br /><br />           
        </p>
        </div>
      </div>
    </div>
    <?php
  }
  
  /**
   * AceAdminFolder::breadcrumbtrail()
   * Show the folder path breadcrumbtrail to navigate back
   * 
   * @since 1.1.0 
   * @return void
   */
  function breadcrumbtrail() {    
    ?> 
    <a href="admin.php?page=ace-filemanager" title="<?php esc_attr_e( 'Go back to Manage Gallery Structure', 'ace-xml-gallery-builder' ); ?>" ><?php esc_html_e( 'Gallery','ace-xml-gallery-builder' ); ?></a> / 
    <?php
    $levels = explode('/', untrailingslashit( $this->curdir ) );
    $tlevel = '';
    for ( $i = 0; $i < count( $levels ) - 1; $i++ ) {
    	$tlevel .= trailingslashit( $levels[$i] );    
    	echo '<a title="' . esc_attr__( 'Manage level up', 'ace-xml-gallery-builder') . '" href="admin.php?page=ace-filemanager&amp;folder=' . ace_nice_link( $tlevel ) . '" >' . htmlentities( $levels[$i] ) . '</a> / ';
    }
    $thisdir = htmlentities( $levels[$i] );
    echo "<strong>$thisdir</strong>\n";
  }
  
  /**
   * AceAdminFolder::titlediv()
   * div holding the title = title
   * 
   * @since 1.1.0 
   * @return void
   */
  function titlediv() {
    $titlelabel = sprintf( esc_html__( 'Enter title for folder "%s" here', 'ace-xml-gallery-builder' ), htmlentities( $this->dirname() ) );
    $style= ( 0 < strlen( $this->title ) ) ? 'visibility:hidden;' : 'visibility:visible;';
    $title = ace_esc_title( $this->title );
    ?>
    <div id="titlediv" style="margin:0px">
      <div id="titlewrap">
        <label for="title" id="title-prompt-text" style="<?php echo $style; ?>" class="hide-if-no-js"><?php echo $titlelabel; ?></label>
        <input type="text" id="title" class="ace" name="folder_title" autocomplete="off" value="<?php echo $title; ?>" size="80"  />
      </div>
	  
      <div class="inside">
        <div id="edit-slug-box">        
          <strong>Permalink: </strong><span id=""><?php echo $this->uri(); ?></span>
          <span id="view-post-btn"><a class="button" href="<?php echo $this->uri(); ?>" target="_blank"><?php esc_html_e( 'View this Folder','ace-xml-gallery-builder' ); ?></a></span>
        </div>
		<div id="edit-slug-box1" style="margin-top:10px;padding-left:10px;color: #666666;">           <?php global $ace_gallery;  
		  if(isset($_REQUEST['directory'])&&$_REQUEST['directory']!=''){
		  $dir=$_REQUEST['directory'];
		  }else{
		  $dir=$_REQUEST['folder'];
		  }    
    $gallery_folder = network_site_url( '/' ).str_replace( DIRECTORY_SEPARATOR,'/' , $ace_gallery->get_option( 'gallery_folder' ) ).$dir.'images.xml';?>
          <strong>XML Path: </strong><a href="<?php  echo $gallery_folder ;  ?>" target="_blank"><?php  echo $gallery_folder ; ?></a>
         </div>
      </div>
    </div>
    <?php
  }
  
  /**
   * AceAdminFolder::descriptiondiv()
   * div holding the description
   * 
   * @since 1.1.0 
   * @return void
   */
  function descriptiondiv() {
    $explain = esc_html__( 'You may use these HTML tags and attributes: <a href=""> <strong> <em> <ul> <li> <br />', 'ace-xml-gallery-builder' );		    
		$description = ace_esc_description( $this->description );    
    ?>
    <div id="folder_description" class="postbox">
      <div class="handlediv"><br /></div><h3 class='hndle'><span><?php esc_html_e( 'Description', 'ace-xml-gallery-builder' ); ?></span></h3>
      <div class="inside">
        <label class="screen-reader-text" for="fdescription"><?php esc_html_e( 'Description', 'ace-xml-gallery-builder' ); ?></label>
        <textarea name="fdescription" id="fdescription"><?php echo $description ?></textarea>
        <p><?php echo $explain; ?></p>
      </div>
    </div>
    <?php
  }
  
  /**
   * AceAdminFolder::imagesbox()
   * The manage images box
   * 
   * @since 1.1.0
   * @return void
   */
  function imagesbox() {
  	global $ace_gallery;
    $paged = ( isset($_REQUEST['ace_paged'] ) ) ? (int) $_REQUEST['ace_paged'] : 1;
    $list = 'list';
    $list_style = 'listview';
    if ( isset( $_REQUEST['mode'] ) ) {
    	//$list_style = ( 'excerpt' == $_REQUEST['mode'] ) ? 'editview' : 'listview';
		$list_style = ( 'excerpt' == $_REQUEST['mode'] ) ? 'editview' : 'listview';
			$list = $_REQUEST['mode']; 	
    }		  
    $this->load( 'thumbs' );  
    $ace_gallery->sortit['images'] = ( 'MANUAL' == $ace_gallery->get_option( 'sort_alphabetically' ) ) && $this->can_save();
    $pagination = $ace_gallery->pagination( 'aimages', $this->list );
    ?><ul id="ace_context" title="<?php echo urlencode( $this->curdir ); ?>" class="contextMenu"><li><!-- will be loaded by ajax --></li></ul>
      <div id="imagesbox" class="postbox">
        <div class="inside">
          <div class="tablenav">
            <?php if ( ! $ace_gallery->sortit['images'] && ( 20 < $this->thiscount ) ) echo $pagination; ?>
            <?php $this->view_switcher( $list ); ?>
          </div>
        </div>
        <div id="sortimages" class="<?php //echo $list_style ?>view">        
        <?php 
        $imagetable = new AceImageTable( $this->list );
        $imagetable->page( 'ace_paged' );
        $imagetable->display();      
        unset( $imagetable );
        ?>
        </div>
      <?php if ( ! $ace_gallery->sortit['images'] && ( 20 < $this->thiscount ) ) {	?>
        <div class="inside">
          <div class="tablenav">
            <?php echo $pagination ?>
          </div>
        </div>
      <?php      
  		}         
    	if ( ( 10 < $this->thiscount ) && $this->can_save() && $this->user_can( 'editor' ) )  { // add an extra update button if more than 10 images
    	?>       
        <div id="below_images" class="inside" style="padding:0px;">      
          <div id="second-publishing-actions">
            <div id="publishing-second">
              <input type="submit" class="button button-highlighted button-primary" name="update_folder-s" value="<?php esc_html_e( 'Update Folder','ace-xml-gallery-builder' ); ?>" />
            </div>
            <div class="clear"></div>
          </div>
        </div>
      <?php	} ?>
      </div>
      <?php   
  }
  
  /**
   * AceAdminFolder::foldersbox()
   * Show the manage subfolders box
   *  
   * @return void
   */
  function foldersbox() {
    global $ace_gallery;
  	$folders = $this->subfolders();  
    $hidden = ( 0 == count( $folders ) ) ? 'style="display: none;"' : '';
  	$can_save = $this->can_save();
  	for ( $i = 0; $i != count( $folders ); $i++ ) {
  	  $subfolder = $folders[$i];
  		if ( ! $subfolder->can_save() ) {
  		  $can_save = false;
        break;
      }
  	}      
    $buttontext = $ace_gallery->sortit['folders'] ? esc_html__(  'Save Gallery order', 'ace-xml-gallery-builder' ) : esc_html__( 'Save changes', 'ace-xml-gallery-builder' );     
    $pagination = $ace_gallery->pagination( 'afolders', $folders );
    $folder_table = new AceFolderTable( $folders );
    ?>
    <div id="foldersdiv">
      <div id="folderbox" class="postbox" <?php echo $hidden ?>>                         
        <input type="hidden" name="sort_folders" value="<?php echo $this->form_name() ?>" />        
          <div class="inside">
            <div class="tablenav">                    
              <?php if ( ! $ace_gallery->sortit['folders'] && ( 20 < count( $folders ) ) ) { ?>
              <?php echo $pagination ?>
              <?php	} ?>      
              <?php if ( $can_save ) { ?>        
              <input class="button-secondary" name="sort_gallery_structure-s" type="submit" value="<?php echo $buttontext ?>" />
              <?php } ?>          
            </div>
          </div>           
          <br class="clear" />
          <?php $folder_table->display(); ?>
      		<?php if ( count( $folders ) > 10 ) { ?>
          <div class="inside">
            <div class="tablenav">                    
              <?php if ( ! $ace_gallery->sortit['folders'] && ( 20 < count( $folders ) ) ) { ?>
                <?php echo $pagination ?>
              <?php	} ?>              
              <input class="button-secondary" name="sort_gallery_structure-s" type="submit" value="<?php echo $buttontext ?>" />          
            </div>
          </div>
          <br class="clear" />
          <?php	} ?>          
      </div>
    </div>
	
    <?php 
    unset( $folders );
    return true;
  }
  
  /**
   * AceAdminFolder::uploadbox()
   * Show the box with the uploader(s)
   * 
   * @return void
   */
  
  function uploadbox() {      
  	global $ace_gallery;
  	
		// get and set flash uploader preferences 	
    $flash = ( 'TRUE' == $ace_gallery->get_option( 'flash_upload' ) || ( isset( $_REQUEST['flash'] ) && $_REQUEST['flash'] == 1 ) );     
    if ( false !== stripos($_SERVER['HTTP_USER_AGENT'], 'mac') && apache_mod_loaded('mod_security') )
		  $flash = false; 
  	$ace_gallery->update_option( 'flash_upload', $flash ? 'TRUE' : 'FALSE' );
  	
  	// urls and text based on flash uploader
  	$action_url = add_query_arg( array( 'action' => 'ace_media', 'folder' => ace_nice_link( $this->curdir ), 'width' => 640, 'TB_iframe' => 1 ), wp_nonce_url(  'admin-ajax.php' ) );
  	$action_url = add_query_arg( array( 'flash' => $flash ? 1 : 0 ), $action_url );
  	$switch_url = add_query_arg( array( 'flash' => $flash ? 0 : 1 ), $action_url );
    $flashbypass = sprintf( __( 'You are using the Flash uploader. Problems? Try the <a href="%s">Browser uploader</a> instead', 'ace-xml-gallery-builder' ), $switch_url );
    $htmlbypass = sprintf( __( 'You are using the Browser uploader. Use the <a href="%s">Flash uploader</a> instead', 'ace-xml-gallery-builder' ), $switch_url );
  	
  	$flashstyle = $flash ? 'display:block' : 'display:none';
  	$htmlstyle =  $flash ? 'display:none' : 'display:block';$upload_size_unit = $max_upload_size =  wp_max_upload_size();
    
    // text for allowed types
  	$filetypes = '*.' . str_replace( ' ', ';*.', strtolower( $ace_gallery->get_option( 'fileupload_allowedtypes' ) ) );
    $allowed_types = explode(' ', trim( strtolower( $ace_gallery->get_option( 'fileupload_allowedtypes' ) ) ) );    
    foreach ($allowed_types as $type) {
  		$type_tags[] = "<code>$type</code>";
  	}
		// text for maximum file uplaod			
		$sizes = array( 'KB', 'MB', 'GB' );
		for ( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ )
			$upload_size_unit /= 1024;
		if ( $u < 0 ) {
			$upload_size_unit = 0;
			$u = 0;
		} else {
			$upload_size_unit = (int) $upload_size_unit;
		}
  	$i = implode(', ', $type_tags);
  	$show_image = '';	
  	if ( isset( $_POST['newname'] ) || ( $ace_gallery->success && isset( $ace_gallery->message ) ) ) {
  		$uploaded = isset( $_POST['newname'] ) ? $_POST['newname'] : $ace_gallery->message; 
  		$show_image = sprintf( '<div id="media-item-ace" class="media-item">
				<div class="progress"><div class="bar" style="width:100%%">100%%</div></div>
				<div id="media-upload-error-ace"></div>
				<div class="filename new"><span class="title">%s</span></div>
			</div>' , $uploaded );	
  		unset( $ace_gallery->message );			  		
  	}
  	if ( $flash ) { 
			$user = wp_get_current_user();
			$uid = (int) $user->ID;
  		$ajax_nonce = wp_create_nonce();
	    ?>
		
	<style type="text/css">
	     .button1 { text-align: center; font-weight: bold;  }
	</style>
	    <script type="text/javascript">
	      //<! [CDATA[
	      var folder_id = <?php echo $this->id ?>;
	      var swfu;
	      SWFUpload.onload = function() {
	        var settings = {
	        	button_text: '<span class="button"><?php esc_html_e(''); ?></span>',
	        	button_text_style: '.button {text-align: center; font-weight: bold; font-family:"Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif; color:#cccccc;}',
	        	button_height: "28",
	        	button_width: "100",
	        	button_text_top_padding: "4px",
	        	//button_image_url: '<?php //echo includes_url('images/upload.png'); ?>',
				button_image_url: '<?php echo '../wp-content/plugins/ace-xml-gallery-builder/inc/';?>images/select6.png',
	        	button_placeholder_id: "flash-browse-button",
	        	upload_url : "<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>",
	        	flash_url : "<?php echo includes_url('js/swfupload/swfupload.swf'); ?>",
	        	file_post_name: "async-upload",
	        	file_types: "*.jpg;*.jpeg;*.gif;*.png",
	          file_types_description : "<?php esc_html_e( 'Image files', 'ace-xml-gallery-builder' ); ?>",
	        	post_params : {
	        		"_ajax_nonce" : "<?php echo $ajax_nonce; ?>",      			
	        		"short" : "1",
	            "file" : "<?php echo ace_nice_link( $this->curdir ); ?>",
	            "action" : "ace_swfupload",
           		"uid" : <?php echo $uid; ?>
	        	},
	        	file_size_limit : "<?php echo wp_max_upload_size(); ?>b",
	        	file_dialog_start_handler : ace_fileDialogStart,
	        	file_queued_handler : ace_fileQueued,
	        	upload_start_handler : ace_uploadStart,
	        	upload_progress_handler : ace_uploadProgress,
	        	upload_error_handler : ace_uploadError,
	        	upload_success_handler : ace_uploadSuccess,
	        	upload_complete_handler : ace_uploadComplete,
	        	file_queue_error_handler : ace_fileQueueError,
	        	file_dialog_complete_handler : ace_fileDialogComplete,
	        	swfupload_pre_load_handler: ace_swfuploadPreLoad,
	        	swfupload_load_failed_handler: ace_swfuploadLoadFailed,
	        	custom_settings : {
	        		degraded_element_id : "html_upload-ui", // id of the element displayed when swfupload is unavailable
	        		swfupload_element_id : "flash-upload-ui" // id of the element displayed when swfupload is available
	        	},
	        	debug: false
	        };
	        swfu = new SWFUpload(settings);
	      };
	      //]]>
	    </script>
	  	<?php	
  	} 		
  	?>
	
  	<?php if ( $ace_gallery->success || isset( $_POST['newname'] ) ) : ?>
	      
		 <form action="<?php echo esc_attr( $action_url ); ?>"  method="post" enctype="multipart/form-data">				            
      <h3 class="media-title"><?php esc_html_e( 'Add images from your computer', 'ace-xml-gallery-builder' ); ?></h3>
      <div class="inside">    
        <div id="media-upload-notice">
          <?php if ( isset( $errors['upload_notice'] ) ) { echo $errors['upload_notice']; } ?>
        </div>
        <div id="media-upload-error"> 
          <?php if (isset($errors['upload_error']) && is_wp_error($errors['upload_error'])) echo $errors['upload_error']->get_error_message(); ?>        	
        </div>
        <?php $ace_gallery->options_message() ?>
      
        <div id="flash-upload-ui" class="hide-if-no-js" style="<?php   echo $flashstyle; ?>">
          <div>
            <?php esc_html_e( 'Choose files to upload', 'ace-xml-gallery-builder' ); ?>
            <div id="flash-browse-button"></div>
            <span><input id="cancel-upload" disabled="disabled" onclick="cancelUpload()" type="button" value="<?php esc_attr_e('Cancel Upload', 'ace-xml-gallery-builder'); ?>" class="button" /></span>            
          </div>
					<p class="media-upload-size"><?php printf ( esc_html__( 'Maximum upload file size: %d%s', 'ace-xml-gallery-builder' ), $upload_size_unit, $sizes[$u] ); ?></p>
					<p class="upload-flash-bypass"><?php echo $flashbypass ?></p>            
          <p class="howto"><?php esc_html_e( 'After a file has been uploaded, you can return to edit the Folder .', 'ace-xml-gallery-builder' ); ?></p>
          
        </div>               
        <div id="html_upload-ui" style="<?php echo $htmlstyle ?>">              
          <a name="html_upload-ui"></a>         
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo wp_max_upload_size(); ?>" />
            <p id="html-upload-wrap">
              <input type="file" name="html_upload" id="html_upload" /><input type="submit" name="html-upload" class="button" value="<?php _e( 'Upload', 'ace-xml-gallery-builder' ); ?>" />
            </p>                      
            <input type="hidden" name="upload_folder" value="<?php echo htmlentities( $this->curdir ); ?>" /> 
						<p class="media-upload-size"><?php printf ( esc_html__( 'Maximum upload file size: %d%s', 'ace-xml-gallery-builder' ), $upload_size_unit, $sizes[$u] ); ?></p>                                      
            <p class="upload-html-bypass"><?php echo $htmlbypass; ?></p>
        </div>
		
        <div id="media-items" class="ace"><?php echo $show_image; ?></div> 
		<?php //if(!empty($show_image)) { ?>
		<div style="padding-top:10px;"><a href="#" class="button"  onclick="javascript:window.parent.document.location.reload();"> Ok</a></div>
		<?php //}?>     
      </div>               
		</form> 
		<?php else : ?>
		<form>
			<h3 class="media-title"><?php esc_html_e( 'Upload failed', 'ace-xml-gallery-builder' ); ?></h3>
			<div class="inside">
				<?php $ace_gallery->options_message() ?>
				<div id="html_upload-ui">
				<p><a class="button" href="<?php echo $action_url; ?>"><?php esc_html_e( 'Try another file', 'ace-xml-gallery-builder' ); ?></a></p>
				</div>
			</div>
		</form>	
		<?php endif; ?>		 
    <?php 
  }
  
  /**
   * AceAdminFolder::_check_save()
   * Display a message if a user cannot save changes
   * 
   * @return string
   */
  function _check_save() {
    if ( ! $this->can_save() ) {
      return esc_html__( "It doesn't look like you have sufficient permissions to save changes for folder %s", 'ace-xml-gallery-builder' );
    }
    return '';
  }
  
  /**
   * AceAdminFolder::_check_allowed()
   * Check if the uploaded files are allowed filetypes
   *  
   * @param string $imgtype
   * @return string
   */
  function _check_allowed( $imgtype ) {
    global $ace_gallery;
  	$allowed_types = explode(' ', trim( strtolower( $ace_gallery->get_option( 'fileupload_allowedtypes' ) ) ) );  
    if ( ! in_array( $imgtype, $allowed_types ) ) {
		  return esc_html__( "File %s of type %s is not allowed", 'ace-xml-gallery-builder' );
    }
    return '';
  }
  
  /**
   * AceAdminFolder::_check_image()
   * Check if an uploaded file is a valid image file 
   * 
   * @since 1.1.9
   * @uses esc_html__
   * @param string $imagefile
   * @return string
   */
  function _check_image( $imagefile ) {
  	if ( ! getimagesize( $imagefile ) ) {
  		return esc_html__( "%s is not a valid image file" , 'ace-xml-gallery-builder' );
  	}
  	return '';
  }
  
  /**
   * AceAdminFolder::uploadfiles()
   * Perform upload for html uploader
   * 
   * @return void
   */
  function uploadfiles() {
    global $ace_gallery;	
    
    if (! wp_verify_nonce( $_REQUEST['_wpnonce'] ) ) 
			die( esc_html__( 'Security check', 'ace-xml-gallery-builder') );
    
    // check if folder is writable
    $cansave = $this->_check_save();
    if ( '' != $cansave ) {
      $ace_gallery->message = sprintf( $cansave , htmlentities( $this->curdir ) );
      return;
    } 
    
    $newname = basename( ( isset( $_POST['newname'] ) ) ? $_POST['newname'] : '' );
    $image_name = ( strlen( $newname ) ) ? $newname : basename( $_FILES['html_upload']['name'] );
    if ( '' == $image_name ) {
      $ace_gallery->message = esc_html__( 'Please enter an image file name', 'ace-xml-gallery-builder' );
      $ace_gallery->success = false;
      return;
    } 
    
		$image_name = stripslashes( utf8_decode( $image_name ) );
		$image_size = isset( $_POST['newsize'] ) ? intval($_POST['newsize']) : intval($_FILES['html_upload']['size']);
		$image_type = ( strlen( $newname ) ) ? $_POST['newtype'] : $_FILES['html_upload']['type'];
		$path = pathinfo( $image_name );
		$imgtype = strtolower( $path['extension'] );
		
		// check if the filename extension is allowed
    $is_allowed = $this->_check_allowed( $imgtype );
  	if ( '' != $is_allowed )  {
		  $ace_gallery->message = sprintf( $is_allowed, $image_name, $image_type );
      $ace_gallery->success = false;
      return;      
    }
				    
		$pathtofile = trailingslashit( $ace_gallery->root . $this->curdir ) . $image_name;
    $html_upload = ( strlen( $newname ) ) ? $_POST['html_upload'] : $_FILES['html_upload']['tmp_name'];
    $duplicate_file = false;
    if ( file_exists( $pathtofile ) ) { 
    	$checkname = $pathtofile;
			$path = pathinfo( $pathtofile );   	
		  $duplicate_file = true;
			$i = 1;
		  while ( file_exists( $checkname ) ) {		  	
		  	$tryname = $path['filename'] . "_$i";				
      	$checkname = trailingslashit( $ace_gallery->root . $this->curdir ) . $tryname . '.' . $path['extension'];				
      	$i++; 
		  }		   
    	$pathtofile = $checkname;
    } 
		$moved = move_uploaded_file( $html_upload, $pathtofile ); // move uploaded file to folder
    
    if ( ! $moved ) {
		  $moved = copy( $html_upload, $pathtofile ); // copy duplicate (numbered) name file to new name
		}
    
		if ( ! $moved ) {
      $ace_gallery->message = sprintf( esc_html__( "Couldn't upload your file to %s.", 'ace-xml-gallery-builder' ), $pathtofile );
      $ace_gallery->success = false;
      return;
		} else {
			$stat = stat( dirname( $pathtofile ));
			$perms = $stat['mode'] & 0000666;
			@chmod($pathtofile, $perms);
			@unlink($html_upload);
		}		
    	    
    // check if the file is a valid image
    $check_image = $this->_check_image( $pathtofile );
    if ( '' != $check_image ) {
    	@unlink( $pathtofile );
    	$ace_gallery->message = sprintf( $check_image, htmlentities( $image_name ) );
      $ace_gallery->success = false;
    	return;
    }  
    
		if ( $duplicate_file ) {
			$ace_gallery->success = false;
			$action_url = add_query_arg( array( 'action' => 'ace_media', 'folder' => ace_nice_link( $this->curdir ), 'width' => 640, 'TB_iframe' => 1 ), wp_nonce_url(  'admin-ajax.php' ) );						
		  ?>			
			<div id="message" class="error">
        <p>
        <?php printf( esc_html__( 'The filename "%s" already exists', 'ace-xml-gallery-builder' ), $image_name ); ?>
        </p>
      </div> 
      <form action="<?php echo esc_attr( $action_url ); ?>" method="post" enctype="multipart/form-data">
				<h3><?php esc_html_e( 'Duplicate File?', 'ace-xml-gallery-builder' ); ?></h3>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo wp_max_upload_size(); ?>" />
        <input type="hidden" name="newtype" value="<?php echo $image_type; ?>" />
        <input type="hidden" name="newsize" value="<?php echo $image_size; ?>" />
        <input type="hidden" name="html_upload" value="<?php echo ace_nice_link( $pathtofile ) ?>" />
        <label><?php  esc_html_e( 'Alternate name:' ) ?><input type="text" name="newname" size="30" class="uploadform" value="<?php echo basename( $pathtofile ); ?> " /></label>
        <p class="submit">
          <input style="width:128px" type="submit" name="submit" value="<?php esc_html_e( 'Rename', 'ace-xml-gallery-builder'); ?>" class="button-secondary" />
        </p>
      </form>
      <?php
			return;
		}
		$ace_gallery->success = true;
    $ace_gallery->message = esc_html( $image_name );
    
		return;  	
  }
  
  /**
   * AceAdminFolder::swfuploadfiles()
   * Perform upload for swf uploader 
   * @return int 0 if success;  string error message if fails
   */
  function swfuploadfiles() {
    global $ace_gallery;
    
  	$allowed_types = explode(' ', trim( strtolower( $ace_gallery->get_option( 'fileupload_allowedtypes' ) ) ) );  
    
    // check if this folder is writable
    $cansave = $this->_check_save();
    if ( '' != $cansave ) {
      return  sprintf( $cansave , $this->curdir );
    } 
    
    $async_upload = $_FILES['async-upload']['tmp_name'];
    
    $image_name = basename( $_FILES['async-upload']['name'] );
		$image_name = stripslashes( utf8_decode( $image_name ) );
		$image_size = intval( $_FILES['async-upload']['size']);
		$path = pathinfo( $image_name );
		$image_type = strtolower( $path['extension'] );

		// check if the filename extension is allowed
    $is_allowed = $this->_check_allowed( $image_type );
  	if ( '' != $is_allowed )  {
  		return '<div class="media-upload-error original">' . sprintf( $is_allowed, htmlentities($image_name), $image_type ) . '</div>';
    }	     
       
		$pathtofile = trailingslashit( $ace_gallery->root . $this->curdir ) . $image_name;
		$i = 1;
    $checkname = $pathtofile;
		$path = pathinfo( $pathtofile );
    $duplicate_file = false;
		while ( file_exists( $checkname ) ) {
		  $duplicate_file = true;
		  $filename = $path['filename'] . '_' . $i;
      $checkname = trailingslashit( $ace_gallery->root . $this->curdir ) . $filename . '.' . $path['extension'];
      $i++;
		}    
    $pathtofile = $checkname;
		$moved = move_uploaded_file( $async_upload, $pathtofile ); 
    
    if ( ! $moved ) {
		  $moved = copy( $async_upload, $pathtofile ); 
		}
    
		if ( ! $moved ) {
      $message = sprintf( esc_html__( "Couldn't upload your file to %s.", 'ace-xml-gallery-builder' ), $pathtofile );
      return '<div class="media-upload-error original">'.$message.'</div>';
		} else { 
			$stat = stat( dirname( $pathtofile ) );
			$perms = $stat['mode'] & 0000666;
			@chmod( $pathtofile, $perms );
			@unlink( $async_upload ); 
			
			// check if the file is a valid iamge
    	$check_image = $this->_check_image( $pathtofile );
    	if ( '' != $check_image ) {
    		@unlink( $pathtofile );
    		return '<div class="media-upload-error original">' . sprintf( $check_image, htmlentities($image_name) ) . '</div>';
    	}     
		}
    return '0';
  }
  
  
  /**
   * AceAdminFolder::change_extra_fields()
   * 
   * @since 1.1.0
   * @param string $for either 'folder' or 'string'
   * @param AceImage $object
   * @return array of extra fields and values
   */
  function change_extra_fields( $for, $object = null ) {
    global $ace_gallery; 
    $fields = $ace_gallery->get_fields( $for );
		$extra_fields = array(); 
    if ( ( false !== $fields ) && ( 0 < count( $fields) ) ) {
      foreach ( $fields as $field ) {      	
      	$fieldname = $field['name'];
      	$postname = ( $for == 'folder') ? $field['name'] : $object->form_name() . '_' . $field['name'];
        $extra_fields[$fieldname] = ( isset( $_POST[$postname] ) ) ? $_POST[$postname] : $object->extra_fields[$fieldname];         
      }
    } 
    return $extra_fields;
  }
  
  /**
   * AceAdminFolder::change()
   * Save all fields, overrides AceFolder::change()
   * 
   * @since 1.1.0
   * @return bool
   * @todo form names
   */
  function change() {
  	
    AceFolder::change();
    
    if ( ! defined( 'DOING_AJAX' ) && ( 0 < count( $_POST ) ) ) {
      $this->title = $_POST['folder_title'];
      $description = nl2br( $_POST['fdescription'] );
    	$this->description = $description;  
      // some fields are only displayed to authorized users
      $this->visibility = isset( $_POST['visibility'] )  ? $_POST['visibility'] : $this->visibility;
      $this->editor = isset( $_POST['folder_editor'] ) ? $_POST['folder_editor'] : $this->editor; 
      $this->viewer_level = isset( $_POST['viewer_level'] ) ? $_POST['viewer_level'] : $this->viewer_level; 
      $this->extra_fields =  $this->change_extra_fields( 'folder' );
      $this->load();
    	for ( $i = 0; $i != count( $this->list ); $i++ ) {
    		// prepare the strings to be written
        $image = $this->list[$i];
    		$form_value = $image->form_name();
        if ( isset( $_POST[$form_value] ) ) { // only change images currently displayed
          $image->title = $_POST[$form_value];
					$description = nl2br( $_POST["desc_" . $form_value] );
          $image->description = $description; 
          $image->index = $_POST["index_" . $form_value];
          $image->extra_fields = $this->change_extra_fields( 'image', $image );
        }
    	}  		       
      return $this->save();
    } 
  }
  
  /**
   * AceAdminFolder::save_edits()
   * Save changes in the folder and images fields
   * 
   * @since 0.16.0
   * @return void
   */
  function save_edits() {
    global $ace_gallery;
  	$nonce=$_REQUEST['_wpnonce'];    
    if (!  wp_verify_nonce( $nonce, 'ace_manage_folder' ) ) { 
			$ace_gallery->message = __( 'You are not allowed to change Ace Gallery folders', 'ace-xml-gallery-builder' );
			$ace_gallery->success = false;
			return;
		}
    $success = $this->change();
    $ace_gallery->message = ( $success ) ? __( 'Folder saved. Continue editing below', 'ace-xml-gallery-builder' ) : __( 'Could not save Changes. Please check your file permissions', 'ace-xml-gallery-builder' );
    $ace_gallery->success = $success;    
  }
  
  /**
   * AceAdminFolder::save_changed_folders()
   * Sort subfolders after the  Save changes button has been clicked
   * 
   * @since 1.0
   * @return void
   */
  function save_changed_folders() {
  	global $ace_gallery;
  	$nonce=$_REQUEST['_wpnonce'];
    if (!  wp_verify_nonce( $nonce, 'ace_manage_folder' ) ) {
    	$ace_gallery->message = __( 'You are not allowed to change Ace Gallery sub folders', 'ace-xml-gallery-builder' );
			$ace_gallery->success = false;
			return;
    }  
    $success =  false;
    $subfolders = $this->subfolders( 'hidden' );
    for ( $i = 0; $i != count( $subfolders ); $i++ ) {    	
      $folder = $subfolders[$i];  		
      $name = $folder->form_name();
      if ( isset( $_POST['index'][$name] ) ) { // only change folders currently displayed 
    		$folder->order = $_POST['index'][$name];
    		$success = $folder->change();
        if ( ! $success ) {
          break;
        }
      }
  	}
    $ace_gallery->message = ( $success ) ? __( 'Subfolders changed. Continue editing below', 'ace-xml-gallery-builder' ) : __( 'Ace Gallery could not save your folders.', 'ace-xml-gallery-builder' );     
    $ace_gallery->success = $success;
  }
  
  /**
   * AceAdminFolder::hascopy()
   * Check if an image has a copy in another folder
   * 
   * @since 1.0 
   * @param mixed $filevar
   * @return bool
   */
  function hascopy( $filevar ) {
    global $ace_gallery;
    $filename = stripslashes( urldecode( $filevar ) );
    $image = $this->single_image( $filename );
    $imagefiles =  $ace_gallery->get_file_by_id( $image->id );
    return ( 1 < count( $imagefiles ) );  
  }
  
  /**
   * AceAdminFolder::delete_file()
   * Deletes a file or cache from the folder
   *  
   * @return void
   */
  function delete_file() {
    global $ace_gallery;    
    $delete_this = $_REQUEST['file_to_delete'];
    $cache = ( isset( $_REQUEST['cache'] ) ) ? $_REQUEST['cache'] : '';
    $waste_it = $ace_gallery->root . $this->curdir . $delete_this;
    $message = __( 'Nothing to delete', 'ace-xml-gallery-builder' );
    if ( file_exists( $waste_it ) ) {
      if ( ! is_dir( $waste_it ) ) { // delete an image
        if ( ! $this->hascopy( $delete_this ) ) {
          if ( isset( $ace_gallery->commentor ) ) {
            $ace_gallery->commentor->remove_comments( $this->curdir . $delete_this ); 
          }
        }
        $success = @unlink(  $waste_it );// also delete cache
        $waste_cache = $ace_gallery->root . $this->curdir . $ace_gallery->get_option( 'thumb_folder' ) . $delete_this;
        if ( file_exists( $waste_cache ) ) {
        	@unlink( $waste_cache );
        }
        $waste_cache = $ace_gallery->root . $this->curdir . $ace_gallery->get_option( 'slide_folder' ) . $delete_this;
        if ( file_exists( $waste_cache ) ) {
        	@unlink( $waste_cache );
        }
        $message = ( $success ) ? sprintf( __( ' Image %s deleted successfully', 'ace-xml-gallery-builder' ), $delete_this ) : sprintf( __( ' Cannot delete %s, please check your server permissions', 'ace-xml-gallery-builder' ), $delete_this );    		
    	} else { // delete cache
         if ( '' != $cache ) {
          $success = $ace_gallery->clear_directory( $waste_it );
          $message = ( $success ) ? sprintf( __( ' %s cache deleted successfully', 'ace-xml-gallery-builder' ), $delete_this ) : sprintf( __( ' Cannot delete %s cache, maybe it has already been deleted or have wrong permissions',  'ace-xml-gallery-builder' ), $delete_this ); 
          }
   		}
   	} else { // file does not exist 
      if ( '' != $cache ) { // but cache should be deleted
        $success = true;
        $message = sprintf( __( '%s cache deleted successfully', 'ace-xml-gallery-builder' ), $delete_this );
      } else { // file not found. deleted in other session?
        $success = false;
        $message = sprintf( __( '%s does not exist', 'ace-xml-gallery-builder' ), $delete_this );
      } 
    } 
    $ace_gallery->message = $message;
    $ace_gallery->success = $success;
  }
  
  /**
   * AceAdminFolder::copy_image()
   * Copy or move an image to another folder
   * 
   * @return void
   * @since 1.0
   * 
   */
  function copy_image( $action = 'copy' ) {
    global $ace_gallery;
    $success = false; 
    $get = ( $action == 'copy' ) ? $_REQUEST['copy_to'] : $_REQUEST['move_to'];
    $folderfile = urldecode( $get ) ;
    $imagefile =  urldecode( $_REQUEST['image'] );
    if ( ( '' == $folderfile ) || ( '' == $imagefile) ) { 
      $message = sprintf( __( 'Cannot find image or folder, please <a href="%s">reload</a> this folder', 'ace-xml-gallery-builder' ), admin_url( 'admin.php' ) . '?page=ace-filemanager&folder=' .ace_nice_link( $this->curdir ) );
    } else {
      $to_folderobj = new AceFolder( $folderfile );
      $to_folderobj->open();   
      $to_folderobj->load();
      if ( ! $to_folderobj->user_can(  'editor' ) ) {
        $message = sprintf ( esc_html__( 'You have insufficient permissions to copy to folder %s', 'ace-xml-gallery-builder' ),  htmlentities( $folderfile ) ); 
      } else {
        $to_folder = $ace_gallery->root . $folderfile;
        $from_image = $ace_gallery->root . $imagefile;
        $from_folderobj = new AceFolder( dirname( $imagefile ) );
        $from_imageobj = $from_folderobj->single_image( basename( $from_image) );
        $to_image = $to_folder . basename( $from_image );
        if ( file_exists( $to_image ) ) {
          $message = sprintf( esc_html__( 'Cannot copy, %s already exists in %s', 'ace-xml-gallery-builder' ),  htmlentities( basename( $from_image) ),  htmlentities( $folderfile ) );      
        } else {        
          if ( ! @copy( $from_image, $to_image ) ) {     
            $message = esc_html__( 'Cannot copy, Something went wrong copying your image. Please check your server permissions', 'ace-xml-gallery-builder' );
          } else {                 
            if ( 'move' == $action )  {
              $success = @unlink( $from_image );
              if ( ! $success ) {
                $message = esc_html__('Cannot move, image is copied instead', 'ace-xml-gallery-builder' );
              }
            } else {
              $success = true;
            }
            if ( $success ) {        
              $from_imageobj->folder = $to_folderobj;            
              $to_folderobj->list[] = $from_imageobj;
              $to_folderobj->save();
              $copymove = ( $action == 'copy' ) ? 'copied' : 'moved';
              $folderlink = '<a href="' . admin_url( 'admin.php' ) . '?page=ace-filemanager&folder=' . urlencode( $folderfile ) . '#' . $from_imageobj->form_name() . '">' . htmlentities( $folderfile ) . '</a>'; 
              $message = sprintf( esc_html__( '%s successfully %s to %s', 'ace-xml-gallery-builder' ), htmlentities( basename( $from_image) ), $copymove,  $folderlink);
            }
          }
        } 
      }
    }    
    $ace_gallery->message = $message;
    $ace_gallery->success = $success;    
  }
  
  function set_author( $user_id ) {
    $nonce=$_REQUEST['_wpnonce'];    
    if (!  wp_verify_nonce( $nonce, 'ace_manage_folder' ) ) die( esc_html__( 'You are not allowed to change Ace Gallery folders', 'ace-xml-gallery-builder' ) ); 
    if ( ! in_array( $user_id, $this->authors ) )
      $this->authors[] = $user_id; 
    else 
      return false;
    return true;   
  }
  
  function unset_author( $user_id ) {
    $nonce=$_REQUEST['_wpnonce'];    
    if (!  wp_verify_nonce( $nonce, 'ace_manage_folder' ) ) wp_die( esc_html__( 'You are not allowed to change Ace Gallery folders', 'ace-xml-gallery-builder' ) );
    $key = array_search( $user_id, $this->authors );
    if ( false !== $key )
      unset( $this->authors[$key] );
    else 
      return false;
    return true;
  }
  
   
  /**
   * AceAdminFolder::add_author()
   * Add an author to the folder authors list
   * 
   * @since 1.1.0
   * @return void
   */
  function add_author() {
    global $ace_gallery;
    if ( ! isset( $_POST['not-authors'] ) ) return;
    $users = $_POST['not-authors'];
    foreach( $users as $user_id ) {
      $this->set_author( $user_id );
    }           
    $ace_gallery->success = $this->change();
    $ace_gallery->message = $ace_gallery->success ? __( 'Succesfully added author(s)', 'ace-xml-gallery-builder' ) : __( 'Could not add Author(s)', 'ace-xml-gallery-builder' ); 
    $_REQUEST['edit'] = 'authors'; 
  }
  
  /**
   * AceAdminFolder::remove_author()
   * Remove an author from the folder author list
   * 
   * @since 1.1.0
   * @return void
   */
  function remove_author() {
    global $ace_gallery;
    if ( ! isset( $_POST['is-authors'] ) ) return;
    $users = $_POST['is-authors'];
    foreach( $users as $user_id ) {
      $this->unset_author( $user_id );
    }
    $ace_gallery->success = $this->change();
    $ace_gallery->message = $ace_gallery->success ? __( 'Succesfully removed author(s)', 'ace-xml-gallery-builder' ) : __( 'Could not remove Author(s)', 'ace-xml-gallery-builder' ); 
    $_REQUEST['edit'] = 'authors';
  }
    
} // AceAdminFolder
?>