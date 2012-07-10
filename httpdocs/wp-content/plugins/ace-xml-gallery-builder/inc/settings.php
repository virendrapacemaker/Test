<?php  
/** 
 * This file contains the Ace Gallery Settings screen
 * 
 * @since 1.1.0
 */ 
 
class AceSettings {
  
  var $new_install;
  var $installstyle;
  var $pages;
  var $pagecount;
  var $other_page;

  function __construct() {
    global $ace_gallery;
	//print_r($ace_gallery);
    if ( ! current_user_can( 'manage_options' ) ) {      
      wp_die( esc_html__( 'You do not have permission to change these settings.', 'ace-xml-gallery-builder' ) );
    }
   	$this->pages = get_pages( 'post_type=page&post_status=publish&hierarchical=0');
   	$this->pagecount = count( $this->pages );
		$this->other_page = false;  	
  }
  
  function do_actions() {
    global $ace_gallery;
    if ( ! isset( $_GET['updated'] ) && ! isset( $_GET['settings-updated'] ) ) { // only take other actions when update button has not been clicked       
      if ( isset( $_GET['xp_wiz'] ) ) {
          $ace_gallery->wizard_form(); 
					$this->other_page = true; 
      }	else {
        if ( isset( $_GET['create_folder'] ) ) {
          $ace_gallery->create_gallery_folder( $_GET['create_folder'] );  
        } 
        if ( isset( $_GET['insert_shortcode'] ) ) {
          $ace_gallery->insert_shortcode( $_GET['insert_shortcode'] );  
        }
        if ( isset( $_GET['reset_options'] ) ) {
          $defaults = $ace_gallery->defaults();
          $ace_gallery->options = &$defaults;
          $ace_gallery->store_options();
          wp_redirect( admin_url( 'admin.php?page=ace-xml-gallery-builder' ) );
          exit;
        }
        if ( isset( $_GET['clear_cache'] ) ) {
          $ace_gallery->admin_clear_cache();
        } else {
      		do_action( 'ace-xml-gallery-builder-settings_actions' );
        	if ( has_action( 'ace-xml-gallery-builder-settings_pages' ) ) {        		
      			do_action( 'ace-xml-gallery-builder-settings_pages', $this );
					}
        }   
      }   
    }  
    $dangerous = get_transient( 'ace_dangerous_path' ); 
    if ( false !== $dangerous ) {    
      $ace_gallery->message = $dangerous ? __( 'You cannot set your Gallery Folder in a WordPress directory. Folder set to default' ) : '';  
      $ace_gallery->success = false;
      delete_transient( 'ace_dangerous_path' );      
    }  
  }
  
  function display() {
    global $ace_gallery, $wp_version;
    $this->do_actions();   
    if ( $this->other_page )
			return; 	 	          	
   	$this->new_install = ( $ace_gallery->get_option( 'new_install' ) == 'TRUE' ) || 
			! $ace_gallery->valid() || 
				( 0 == $this->pagecount ) || 
					! file_exists( $ace_gallery->get_absolute_path( ABSPATH . $ace_gallery->get_option('gallery_folder') ) );			   	
		$this->installstyle = $this->new_install ? 'new_install' : '';    
    ?>
    <div class="wrap">
      <?php screen_icon( 'folders' ); ?>
      <h2><?php esc_html_e( 'Ace Gallery Settings', 'ace-xml-gallery-builder' ); ?></h2>
      <?php $ace_gallery->options_message(); ?>  
      <div id="ajax-div"></div>    
      <?php if ( version_compare( $wp_version, '3.4', '<' ) ) : ?> 
      <div id="poststuff" class="metabox-holder has-right-sidebar">
      <?php else : ?> 
      <div id="poststuff" class="metabox-holder">
      <?php endif; ?>
	 
        <form method="post" action="options.php">      
  		    <?php settings_fields( 'ace-xml-gallery-builder' ); ?>
          <input type="hidden" id="ace_settings" name="ace_settings" value="<?php echo wp_create_nonce( 'settings' ) ?>" />
          <?php if ( version_compare( $wp_version, '3.4', '<' ) ) : ?> 
						<?php $this->sidebar() ?>         		
          	<div id="post-body">
         	<?php else : ?>
						<div id="post-body" class="metabox-holder columns-2">		              	
         		<?php $this->sidebar() ?>
         	<?php endif; ?>
					 <div id="post-body-content">
              <?php $this->main_options(); ?>
	            <?php $this->thumbnail_options(); ?>
	            <?php $this->slide_options(); ?>
	            <?php $this->title_options(); ?>
	            <?php $this->upload_options(); ?>
	            <?php $this->advanced_options(); ?>
	            <?php if ( ! $this->new_install ) : ?>
            		<?php do_action( 'ace_settings_main' );?>
	            <?php endif; ?>
              <?php if ( 0 < $this->pagecount ) : ?>
              <div class="submit">
                <input class="button-primary" type="submit" name="ace-xml-gallery-builder[update_options]" value="<?php	esc_html_e( 'Save Changes', 'ace-xml-gallery-builder' );	?>" />
              </div>      
              <?php endif; ?>     
            </div>
          </div>        
         </form>
	    
      </div>
    </div>
    <?php
  }
  
  function sidebar() {
    global $ace_gallery, $wp_version;
    ?>
    <?php if ( version_compare( $wp_version, '3.4', '<' ) ) : ?> 
    <div id="side-info-column" class="inner-sidebar <?php echo $this->installstyle; ?>">
    <?php else : ?>
    <div id="postbox-container-1" class="postbox-container <?php echo $this->installstyle; ?>">
    <?php endif; ?>
      <div id="side-sortables" class="meta-box-sortables ui-sortable">
        <?php $this->aboutbox(); ?>
        <?php $this->utilities(); ?>        
        <?php do_action( 'ace_settings_sidebar' );?>
      </div>
    </div>
    <?php
  }
  
  /**
   * AceSettings::main_options()
   * 
   * @return void
   */
  function main_options() {  
    global $ace_gallery;       
    $gallery_folder = str_replace( array('/', '\\'), DIRECTORY_SEPARATOR, $ace_gallery->get_option( 'gallery_folder' ) );   
    $createfolder_url = add_query_arg( 'create_folder', $gallery_folder, admin_url( 'options-general.php?page=ace-xml-gallery-builder' ) );
    $createpage_url = admin_url( 'page-new.php' );
    $poptions = array();
    if ( 0 < $this->pagecount )  {
      foreach( $this->pages as $apage ) {
        $selected = ( $apage->ID == $ace_gallery->get_option( 'gallery_id' ) ) ? 'selected="selected"' : '';
        $poptions[] = sprintf( '<option value="%s" %s>%s</option>', $apage->ID, $selected, esc_attr( $apage->post_title ) );        
      }
    } 
    if ( $this->new_install ) { 
      $poptions[] = sprintf( '<option value="-1">%s</option>', esc_attr__( 'Create a New Page', 'ace-xml-gallery-builder' ) );
    }
    ?>
    <script type="text/javascript">
    /* <! [CDATA[ */
    pageURLs=new Array();                  
    pageCodes=new Array();
    <?php
    if ( 0 < $this->pagecount )  {      
      $script = '';
      foreach( $this->pages as $apage ) { 
      	$content = $apage->post_content;
				if(	! $content )
					continue;
        $is_gallery = ( strpos( $content, '[ace_gallery' ) );        
        $str = ( false !== $is_gallery ) ? 'true' : 'false';
        $script .= sprintf( "pageURLs['%s']='%s';\n",
          $apage->ID,
          trailingslashit( get_page_link( $apage->ID ) ) 
        );
        $script .= sprintf( "pageCodes['%s']=%s;\n",
           $apage->ID,
           $str
        );
      }
      echo $script;
    }
    ?>
    /* //]]> */
    </script>
    <?php                              
    $page_id = $ace_gallery->get_option( 'gallery_id' );
    if (  '' != $page_id ) {                      
      $apage = get_page( $page_id );
      if ( ! $apage || ( 'publish' != $apage->post_status ) ) { // page should be published
        $page_id = '';
      }
    }                        
    if (  '' == $page_id ) { // page id is not set, just pick first in line
      $apage = $this->pages[0];
      $page_id = $apage->ID;
      $ace_gallery->change_option( 'gallery_prev', trailingslashit( get_page_link( $apage->ID ) ) );                        
    }                                                         
    $apage = get_page( $page_id ); 
    $is_gallery = strpos( $apage->post_content, '[ace_gallery' ); 
    $astyle = ( $is_gallery === false ) ? 'display:block' : 'display:none';    
    ?>    
    <div id="ace_main_options" class="postbox">
      <h3 class="hndle"><span><?php esc_html_e( 'Main Gallery Options', 'ace-xml-gallery-builder'); ?></span></h3>
        <?php if ( $this->new_install ) : ?>
      <div class="inside"> 
        <div class="update below-h2">
          <h1><?php esc_html_e( 'Welcome to Ace Gallery', 'ace-xml-gallery-builder' ); ?></h1>
          <p><?php esc_html_e( 'Before you can enjoy all the features of Ace Gallery, please enter the folder to store your images, and your blog page where you show your Gallery', 'ace-xml-gallery-builder' ); ?></p>
        </div>           
      </div>      
      <?php endif; ?>                
      <table id="ace_main_options_table" class="widefat">
        <tbody>
          <tr>
            <th scope="row"><label for="gallery_folder"><?php esc_html_e( 'Your Gallery Folder', 'ace-xml-gallery-builder' ); ?></label></th>
            <td>
              <input name="ace-xml-gallery-builder[gallery_folder]" id="gallery_folder" value="<?php echo $gallery_folder ?>" size="60" class="code" type="text" /> <br />
							<p><?php esc_html_e( 'Relative to the WordPress installation folder', 'ace-xml-gallery-builder') ?></p>												            
              <?php
								$gallery_path = $ace_gallery->get_absolute_path( ABSPATH . $gallery_folder );
								if ( ! file_exists( $gallery_path ) ) : 
							?>
              <div class="error below-h2">
              	<p><strong><?php esc_html_e( 'WARNING', 'ace-xml-gallery-builder' ); ?></strong> <?php esc_html_e( 'The specified gallery folder does not exist', 'ace-xml-gallery-builder' ); ?>:
                <code><?php $gallery_folder; ?></code></p>
                <p><a href="<?php echo $createfolder_url; ?>"><?php esc_html_e( 'Let Ace Gallery create this folder for me.', 'ace-xml-gallery-builder' ); ?></a></p>
              </div>
              <?php endif; ?>
              <?php if ( $this->new_install ) : ?>
              	<input type="hidden" name="ace-xml-gallery-builder[new_install]" value="TRUE" />
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="gallery_id"><?php esc_html_e( 'Your Gallery Page', 'ace-xml-gallery-builder' ); ?></label></th>            
            <td >                  
              <?php if ( 0 == $this->pagecount ) { ?>                    
              <a href="<?php echo $createpage_url ?>"><?php esc_html_e( 'Your blog has no pages yet, please create a page for your gallery', 'ace-xml-gallery-builder' ); ?></a>  
              <?php } else { ?>
              <select class="postform" name="ace-xml-gallery-builder[gallery_id]" id="gallery_id" onchange="ace_page_change()" >
                <?php echo implode( $poptions ); ?>                    
              </select><br />                
              <p><?php esc_html_e( 'The exact address where your main gallery is browsable:', 'ace-xml-gallery-builder' ); ?></p>
              <p id="gallery_prev_p"><?php echo $ace_gallery->get_option( 'gallery_prev' ); ?></p>
              <div id="ace_insertcode" class="error below-h2" style="<?php echo $astyle; ?>">
                <p><strong style='color:#ff0000;'><?php esc_html_e( 'WARNING', 'ace-xml-gallery-builder' ); ?></strong>: <?php sprintf( esc_html__( 'The Ace Gallery shortcode %s cannot be found on this page.', 'ace-xml-gallery-builder'), '<code>[ace_gallery]</code>' );?></p>
                <p><a id="a_insert_shortcode" href="admin.php?page=ace-xml-gallery-builder&amp;insert_shortcode=<?php echo $apage->ID ?>">Let Ace Gallery insert the shortcode for me</a></p>
              </div>    
              <input type="hidden" name="ace-xml-gallery-builder[gallery_prev]" id="gallery_prev" value="<?php echo $ace_gallery->get_option( 'gallery_prev' ); ?>" />
              <input type="hidden" name="ace-xml-gallery-builder[new_install]" id="new_install" value="FALSE" />
              <?php } ?> 
              <br class="clear" />
            </td>
          </tr>
        </tbody>
      </table>   
    </div>
    <?php    
    unset( $apage );
    unset( $this->pages );
  } // AceSettings::main_options()
  
  
  /**
   * AceSettings::thumbnail_options()
   * 
   * @return void
   */
  function thumbnail_options() {
    global $ace_gallery;  
    ?>
    <div id="ace_thumbnail_options" class="postbox <?php echo $this->installstyle; ?>" >
    <h3 class="hndle"><span><?php esc_html_e( 'Thumbnail View Options' , 'ace-xml-gallery-builder' ); ?></span></h3>
    <table id="ace_thumbnail_options_table" class="widefat">
      <tbody>
        <tr>
          <th scope="row"><label for="thumbwidth"><?php esc_html_e( 'Maximum Thumbnail Width' , 'ace-xml-gallery-builder' ); ?></label></th>
          <td><input name="ace-xml-gallery-builder[thumbwidth]" id="thumbwidth" value="<?php echo $ace_gallery->get_option( 'thumbwidth' ); ?>" size="10" class="code" type="text" /> pixels</td>
        </tr>
        <tr>
          <th scope="row"><label for="thumbheight"><?php esc_html_e( 'Maximum Thumbnail Height', 'ace-xml-gallery-builder' ); ?></label></th>
          <td><input name="ace-xml-gallery-builder[thumbheight]" id="thumbheight" value="<?php echo $ace_gallery->get_option( 'thumbheight' ); ?>" size="10" class="code" type="text" /> pixels</td>
        </tr>								
        <tr>
          <th scope="row"><label for="thumbspage"><?php esc_html_e( 'Thumbnails per Page', 'ace-xml-gallery-builder' ); ?></label></th>
          <td><input name="ace-xml-gallery-builder[thumbs_page]" id="thumbs_page" value="<?php echo $ace_gallery->get_option( 'thumbs_page' ); ?>" size="5" class="code" type="text" /><br />
          <p><?php esc_html_e( 'Set to 0 to disable pagination.', 'ace-xml-gallery-builder' ); ?></p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="folders_page"><?php esc_html_e( 'Folders per Page', 'ace-xml-gallery-builder' ); ?></label></th>
          <td><input name="ace-xml-gallery-builder[folders_page]" id="folders_page" value="<?php echo $ace_gallery->get_option( 'folders_page' ); ?>" size="5" class="code" type="text" /><br />
          <p><?php esc_html_e( 'Set to 0 to disable pagination.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="count_subfolders"><?php esc_html_e( 'Count Images', 'ace-xml-gallery-builder'); ?></label></th>
          <td>
            <select id="count_subfolders" name="ace-xml-gallery-builder[count_subfolders]">
              <option value="none" <?php selected( 'none', $ace_gallery->get_option( 'count_subfolders' ) ); ?>><?php esc_attr_e( 'Show number of images in folder only', 'ace-xml-gallery-builder' ) ?></option>                          
              <option value="include" <?php selected( 'include', $ace_gallery->get_option( 'count_subfolders' ) ); ?>><?php esc_attr_e( 'Show number of images in folder including subfolders', 'ace-xml-gallery-builder' ); ?></option>                           
              <option value="separate" <?php selected( 'separate', $ace_gallery->get_option( 'count_subfolders' ) ); ?>><?php esc_attr_e( 'Show number of images in folder and subfolders separately', 'ace-xml-gallery-builder' ); ?></option>
              <option value="nothing" <?php selected( 'nothing', $ace_gallery->get_option( 'count_subfolders' ) ); ?>><?php esc_attr_e("Don't show number of images in folder", 'ace-xml-gallery-builder' ); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="sort_alphabetically"><?php esc_html_e( 'Sort Images by', 'ace-xml-gallery-builder' ) ?></label></th>
            <td> 
              <select id="sort_alphabetically" name="ace-xml-gallery-builder[sort_alphabetically]">
              <option value="TRUE" <?php selected( 'TRUE' , $ace_gallery->get_option( 'sort_alphabetically' ) ); ?>><?php esc_attr_e('Name, ascending ( A &rarr; Z )', 'ace-xml-gallery-builder' ) ?></option>
              <option value="DTRUE" <?php selected( 'DTRUE' , $ace_gallery->get_option( 'sort_alphabetically' ) ); ?>><?php esc_attr_e('Name, descending ( Z &rarr; A )', 'ace-xml-gallery-builder' ) ?></option>
              <option value="TITLE" <?php selected( 'TITLE' , $ace_gallery->get_option( 'sort_alphabetically' ) ); ?>><?php esc_attr_e('Title, ascending ( A &rarr; Z )', 'ace-xml-gallery-builder' ) ?></option>
              <option value="DTITLE" <?php selected( 'DTITLE' , $ace_gallery->get_option( 'sort_alphabetically' ) ); ?>><?php esc_attr_e('Title, descending ( Z &rarr; A )', 'ace-xml-gallery-builder' ) ?></option>                        
              <option value="DFALSE" <?php selected( 'DFALSE' , $ace_gallery->get_option( 'sort_alphabetically' ) ); ?>><?php esc_attr_e('Date, newest first', 'ace-xml-gallery-builder' ) ?></option>                                  
              <option value="FALSE" <?php selected( 'FALSE' , $ace_gallery->get_option( 'sort_alphabetically' ) ); ?>><?php esc_attr_e('Date, oldest first', 'ace-xml-gallery-builder' ) ?></option>                     
              <option value="MANUAL" <?php selected( 'MANUAL' , $ace_gallery->get_option( 'sort_alphabetically' ) ); ?>><?php esc_attr_e('Manually', 'ace-xml-gallery-builder' ) ?></option>                                                  
            </select>                     
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="sort_folders"><?php esc_html_e( 'Sort Folders by', 'ace-xml-gallery-builder' ) ?></label></th>
            <td> 
              <select id="sort_folders" name="ace-xml-gallery-builder[sort_folders]">
              <option value="TRUE" <?php selected( 'TRUE' , $ace_gallery->get_option( 'sort_folders' ) ); ?>><?php esc_attr_e('Name, ascending ( A &rarr; Z )', 'ace-xml-gallery-builder' ) ?></option>
              <option value="DTRUE" <?php selected( 'DTRUE' , $ace_gallery->get_option( 'sort_folders' ) ); ?>><?php esc_attr_e('Name, descending ( Z &rarr; A )', 'ace-xml-gallery-builder' ) ?></option>
              <option value="TITLE" <?php selected( 'TITLE' , $ace_gallery->get_option( 'sort_folders' ) ); ?>><?php esc_attr_e('Title, ascending ( A &rarr; Z )', 'ace-xml-gallery-builder' ) ?></option>
              <option value="DTITLE" <?php selected( 'DTITLE' , $ace_gallery->get_option( 'sort_folders' ) ); ?>><?php esc_attr_e('Title, descending ( Z &rarr; A )', 'ace-xml-gallery-builder' ) ?></option>                        
              <option value="DFALSE" <?php selected( 'DFALSE' , $ace_gallery->get_option( 'sort_folders' ) ); ?>><?php esc_attr_e('Date, newest first', 'ace-xml-gallery-builder' ) ?></option>                                  
              <option value="FALSE" <?php selected( 'FALSE' , $ace_gallery->get_option( 'sort_folders' ) ); ?>><?php esc_attr_e('Date, oldest first', 'ace-xml-gallery-builder' ) ?></option>                     
              <option value="MANUAL" <?php selected( 'MANUAL' , $ace_gallery->get_option( 'sort_folders' ) ); ?>><?php esc_attr_e('Manually', 'ace-xml-gallery-builder' ) ?></option>                                                  
            </select>                     
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="folders_columns"><?php esc_html_e( 'Folder Columns', 'ace-xml-gallery-builder' ) ?></label></th>
          <td><input name="ace-xml-gallery-builder[folders_columns]" id="folders_columns" value="<?php echo $ace_gallery->get_option( 'folders_columns' ); ?>" size="5" class="code" type="text" />
          	<?php if( 'TRUE' != $ace_gallery->get_option('table_layout') ) echo '<p>' . esc_html__( 'Set to 0 for a maximum fill per row', 'ace-xml-gallery-builder' ) . '</p>'; ?>
					</td>
        </tr>
        <tr>
          <th scope="row"><label for="thumbs_columns"><?php esc_html_e( 'Thumbnail Columns', 'ace-xml-gallery-builder' ) ?></label></th>
          <td><input name="ace-xml-gallery-builder[thumbs_columns]" id="thumbs_columns" value="<?php echo $ace_gallery->get_option( 'thumbs_columns' ); ?>" size="5" class="code" type="text" />
          <?php if( 'TRUE' != $ace_gallery->get_option('table_layout') ) echo '<p>' . esc_html__( 'Set to 0 for a maximum fill per row', 'ace-xml-gallery-builder' ) . '</p>'; ?>
					</td>
        </tr>			
        <tr>
          <th scope="row"><label for="folder_image"><?php esc_html_e( 'Folder Icons', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <select id="folder_image" name="ace-xml-gallery-builder[folder_image]" onchange="ace_random_change()">
              <option value="icon" <?php selected( 'icon', $ace_gallery->get_option( 'folder_image') ); ?>><?php esc_attr_e('Folder icon', 'ace-xml-gallery-builder' ) ?></option>
              <option value="random_image" <?php selected( 'random_image', $ace_gallery->get_option( 'folder_image') )?>><?php esc_attr_e( 'Random image from folder', 'ace-xml-gallery-builder' ) ?></option>                      
              <option value="none" <?php selected( 'none', $ace_gallery->get_option( 'folder_image') ); ?>><?php esc_attr_e( 'None', 'ace-xml-gallery-builder' ) ?></option>
            </select> 
            <div id="random_subfolder_div"<?php if ( 'random_image' != $ace_gallery->get_option( 'folder_image' ) ) echo 'style="display:none;"' ?>>
              <label><input name="ace-xml-gallery-builder[random_subfolder]" type="checkbox" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'random_subfolder' ) ); ?> /> <?php esc_html_e( 'Include images from sub folders', 'ace-xml-gallery-builder' ); ?> </label>
            </div>
           </td>                    
        </tr>                    
        <tr>
          <th scope="row"><?php esc_html_e( 'Caching', 'ace-xml-gallery-builder' ) ?></th>
          <td>
            <label><input type="checkbox" name="ace-xml-gallery-builder[enable_cache]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'enable_cache' ) ); ?> /><?php esc_html_e( ' Enable thumbnail caching', 'ace-xml-gallery-builder' ); ?></label><br />            
            <br /> 
            <label><input type="checkbox" name="ace-xml-gallery-builder[async_cache]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'async_cache' ) ); ?> /><?php ?><?php esc_html_e( ' Create cached thumbnails after page has loaded in the browser', 'ace-xml-gallery-builder' ); ?></label>
                                     
            <br /><label><?php esc_html_e( 'Store cached thumbnails in sub folders named: ', 'ace-xml-gallery-builder' ) ?><input name="ace-xml-gallery-builder[thumb_folder]" id="thumb_folder" value="<?php echo $ace_gallery->get_option('thumb_folder'); ?>" size="25" class="code" type="text" /></label>
            <br />
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e( 'Cropping', 'ace-xml-gallery-builder' ) ?></th>
          <td>
            <label><input type="checkbox" name="ace-xml-gallery-builder[use_cropping]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'use_cropping' ) ); ?> /><?php esc_html_e( ' Enable thumbnail cropping', 'ace-xml-gallery-builder'); ?></label>            
          </td>
        </tr>
		<tr>
          <th scope="row"><?php esc_html_e( 'Breadcrumb', 'ace-xml-gallery-builder' ) ?></th>
          <td>
            <label><input type="checkbox" name="ace-xml-gallery-builder[use_breadcrumb]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'use_breadcrumb' ) ); ?> /><?php esc_html_e( ' Enable Breadcrumb Links', 'ace-xml-gallery-builder'); ?></label>            
          </td>
        </tr>
      
      <tr>
        <th><?php esc_html_e( 'On Click', 'ace-xml-gallery-builder' ); ?></th>
        <td>
          <p><?php esc_html_e( 'Perform the following action when thumbnails are clicked:', 'ace-xml-gallery-builder' ); ?><br /></p>
          <select name="ace-xml-gallery-builder[on_thumb_click]">                    
            <option value="nothing" <?php selected( 'nothing', $ace_gallery->get_option( 'on_thumb_click' ) ); ?>><?php esc_attr_e( 'Nothing', 'ace-xml-gallery-builder' ); ?></option>
            <option value="slide" <?php selected( 'slide', $ace_gallery->get_option( 'on_thumb_click' ) ); ?>><?php esc_attr_e( 'Show slide in slide view', 'ace-xml-gallery-builder' ); ?></option>
            <option value="lightslide" <?php selected( 'lightslide', $ace_gallery->get_option( 'on_thumb_click' ) ); ?>><?php esc_attr_e( 'Show slide in Lightbox', 'ace-xml-gallery-builder' ); ?></option>
            <option value="thickslide" <?php selected( 'thickslide', $ace_gallery->get_option( 'on_thumb_click' ) ); ?>><?php esc_attr_e( 'Show slide in Thickbox', 'ace-xml-gallery-builder' ); ?></option>
            <option value="fullimg" <?php selected( 'fullimg', $ace_gallery->get_option( 'on_thumb_click' ) ); ?>><?php esc_attr_e( 'Show full size image', 'ace-xml-gallery-builder' ); ?></option>                                           
            <option value="lightbox" <?php selected( 'lightbox', $ace_gallery->get_option( 'on_thumb_click' ) ); ?>><?php esc_attr_e( 'Show full size image in Lightbox', 'ace-xml-gallery-builder' ); ?></option>                                           
            <option value="thickbox" <?php selected( 'thickbox', $ace_gallery->get_option( 'on_thumb_click' ) ); ?>><?php esc_attr_e( 'Show full size image in Thickbox', 'ace-xml-gallery-builder' ); ?></option>
          </select>
          <p>                   
    <?php if ( ! $ace_gallery->some_lightbox_plugin() ) {
            esc_html_e( ' (A supported Lightbox plugin was not detected.)', 'ace-xml-gallery-builder' ); 
          } 
    ?>    <br />
    <?php if ( ! $ace_gallery->some_thickbox_plugin() ) { 
            esc_html_e( ' (A supported Thickbox plugin was not detected.)', 'ace-xml-gallery-builder' ); 
          } 
    ?>
          </p>
        </td>
      </tr>
      <?php do_action( 'ace-xml-gallery-builder-settings_thumbnails' ); ?>
      </tbody>
    </table>
  </div>
  <?php    
  } // AceSettings::thumbnail_options()
  
  
  /**
   * AceSettings::slide_options()
   * 
   * @return void
   */
  function slide_options() {
    global $ace_gallery;
    ?>
    <div id="ace_slide_options" class="postbox <?php echo $this->installstyle; ?>">
      <h3 class="hndle"><span><?php esc_html_e( 'Slide View Options', 'ace-xml-gallery-builder' ) ?></span></h3>
      <table id="ace_slide_options_table" class="widefat">
        <tbody>
        <tr>
          <th scope="row"><?php esc_html_e( 'Comments', 'ace-xml-gallery-builder' ) ?></th>
          <td>
            <label><input name="ace-xml-gallery-builder[allow_comments]" type="checkbox" value="TRUE" <?php checked ( 'TRUE', $ace_gallery->get_option( 'allow_comments' ) ); ?>  />
              <?php esc_html_e( 'Enable user comments on slides', 'ace-xml-gallery-builder' );?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="pictwidth"><?php esc_html_e( 'Maximum Slides Width', 'ace-xml-gallery-builder' ) ?></label></th>
          <td><input name="ace-xml-gallery-builder[pictwidth]" id="pictwidth" value="<?php echo $ace_gallery->get_option( 'pictwidth' ); ?>" size="10" class="code" type="text" /> pixels</td>
        </tr>
        <tr>
          <th><label for="pictheight"><?php esc_html_e( 'Maximum Slides Height', 'ace-xml-gallery-builder' ) ?></label></th>
          <td><input name="ace-xml-gallery-builder[pictheight]" id="pictheight" value="<?php echo $ace_gallery->get_option( 'pictheight' ); ?>" size="10" class="code" type="text" /> pixels</td>
        </tr>
        
        <tr>
          <th scope="row"><?php esc_html_e( 'Caching', 'ace-xml-gallery-builder' ) ?></th>
          <td>
            <label><input type="checkbox" name="ace-xml-gallery-builder[enable_slides_cache]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'enable_slides_cache' ) ); ?> /><?php esc_html_e( ' Enable slide caching', 'ace-xml-gallery-builder' ) ?></label><br />
            <label><?php esc_html_e( 'Store cached slides in sub folders named: ', 'ace-xml-gallery-builder' ) ?><input name="ace-xml-gallery-builder[slide_folder]" id="slide_folder" value="<?php echo $ace_gallery->get_option( 'slide_folder' ); ?>" size="25" class="code" type="text" /></label> <br />
          </td>
        </tr>
        <tr>
          <th scope="row"><?php esc_html_e( 'Slide Show', 'ace-xml-gallery-builder' ) ?></th>
          <td>
            <label><input type="checkbox" name="ace-xml-gallery-builder[enable_slide_show]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'enable_slide_show' ) ); ?> /><?php esc_html_e( ' Enable Slide Show', 'ace-xml-gallery-builder' ) ?></label><br />
            <?php if ( '' == $ace_gallery->get_option('slide_show_duration') ) { $ace_gallery->change_option('slide_show_duration', '5'); } ?>
            <label><?php esc_html_e( 'Each slide will show for: ', 'ace-xml-gallery-builder' ); ?><input type="text" name="ace-xml-gallery-builder[slide_show_duration]" size="3" value="<?php echo $ace_gallery->get_option('slide_show_duration') ?>" /><?php esc_html_e( ' seconds', 'ace-xml-gallery-builder' ); ?></label><br />            
          </td>
        </tr>


        <tr>
          <th scope="row"><?php esc_html_e( 'On Click', 'ace-xml-gallery-builder' ) ?></th>
          <td>
            <?php esc_html_e( 'Perform the following action when slides are clicked:', 'ace-xml-gallery-builder' ) ?><br />
            <select name="ace-xml-gallery-builder[on_slide_click]">
              <option value="nothing" <?php selected( 'nothing', $ace_gallery->get_option( 'on_slide_click' ) ) ?>><?php esc_attr_e( 'Nothing', 'ace-xml-gallery-builder' ) ?></option>
              <option value="fullimg" <?php selected( 'fullimg', $ace_gallery->get_option( 'on_slide_click' ) ) ?>><?php esc_attr_e( 'Show full size image', 'ace-xml-gallery-builder' ) ?></option>
              <option value="popup" <?php selected( 'popup', $ace_gallery->get_option( 'on_slide_click' ) ) ?>><?php esc_attr_e( ' Show image in pop-up window', 'ace-xml-gallery-builder' ) ?></option>
              <option value="lightbox" <?php selected( 'lightbox', $ace_gallery->get_option( 'on_slide_click' ) ) ?>><?php esc_attr_e( 'Show image in Lightbox', 'ace-xml-gallery-builder' ) ?></option>
              <option value="thickbox" <?php selected( 'thickbox', $ace_gallery->get_option( 'on_slide_click' ) ) ?>><?php esc_attr_e( 'Shhow image in Thickbox', 'ace-xml-gallery-builder' ) ?></option>
            </select>
            <p>
            <?php if ( ! $ace_gallery->some_lightbox_plugin() ) {
              esc_html_e( '(A supported Lightbox plug-in was not detected.)', 'ace-xml-gallery-builder' ); 
            } 
            ?>
            <br />
            <?php if ( ! $ace_gallery->some_thickbox_plugin() ) { 
                esc_html_e( ' (A supported Thickbox plug-in was not detected.)', 'ace-xml-gallery-builder' ); 
              } 
            ?>
            </p>
          </td>
        </tr>
        <?php if ( function_exists( 'exif_read_data' ) ) : ?>  
        <tr>
          <th scope="row"><?php esc_html_e( 'Exif Data', 'ace-xml-gallery-builder' ); ?></th>
          <td>
            <label>
              <input type="checkbox" name="ace-xml-gallery-builder[enable_exif]" id="enable_exif" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'enable_exif' ) ); ?> />
                <?php esc_html_e( 'Display image Exif data', 'ace-xml-gallery-builder' ); ?>                 
            </label>
          </td>  
        </tr>
        <?php endif; ?>        
      	<?php do_action( 'ace-xml-gallery-builder-settings_slides' ); ?>
        </tbody>
      </table>
    </div>                 
    <?php
  } // AceSettings::slide_options()
  
  /**
   * AceSettings::title_options()
   * 
   * @return void
   */
  function title_options() {
    global $ace_gallery;
    ?>        
    <div id="ace_title_options" class="postbox <?php echo $this->installstyle; ?>">
      <h3 class="hndle"><span><?php esc_html_e( 'Title Options', 'ace-xml-gallery-builder' ) ?></span></h3>
      <table id="ace_title_options_table" class="widefat">
        <tbody>
        <tr>
          <th scope="row" colspan="3" class="th-full">
            <label>
              <input type="checkbox" name="ace-xml-gallery-builder[enable_titles]" value="TRUE" <?php checked ( 'TRUE', $ace_gallery->get_option( 'enable_titles' ) ); ?> />
              <?php esc_html_e( 'Use image titles instead of file names', 'ace-xml-gallery-builder' ) ?>
            </label>
          </th>
        </tr>
        
        <tr>
          <th scope="row" colspan="3" class="th-full">
            <label>
              <input type="checkbox" name="ace-xml-gallery-builder[use_folder_titles]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'use_folder_titles' ) ); ?> />
              <?php esc_html_e( 'Use folder titles instead of folder names', 'ace-xml-gallery-builder' ) ?>
            </label>
          </th>
        </tr>
         
        <tr>
          <th scope="row" colspan="3" class="th-full">
            <label>
              <input type="checkbox" name="ace-xml-gallery-builder[thumb_description]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'thumb_description' ) ); ?> />
              <?php esc_html_e( 'Show descriptions in thumbnail view', 'ace-xml-gallery-builder' ) ?>
            </label>
          </th>
        </tr>
        <tr>
          <th scope="row">
            <label for="titles_length">                
              <?php esc_html_e( 'Length of Titles in Thumbnail View', 'ace-xml-gallery-builder' ) ?>							
          		<input type="text" id="titles_length" name="ace-xml-gallery-builder[titles_length]" size="3" value="<?php echo $ace_gallery->get_option( 'titles_length' ); ?>" /> 
            	<?php esc_html_e( 'characters', 'ace-xml-gallery-builder' ) ?>                
            </label>
          </th>         
          <td>
          	<p><?php esc_html_e( 'Set to -1 to disable titles in thumbnail view.', 'ace-xml-gallery-builder' ); ?></p>
          	<p><?php esc_html_e( 'Set to  0 to disable cropping of titles text in thumbnail view.', 'ace-xml-gallery-builder' ); ?></p>
            <p></p><?php esc_html_e( 'Disable titles will also disable descriptions in thumbnail view.', 'ace-xml-gallery-builder' ); ?></p>
          </td>
        </tr>        
      	<?php do_action( 'ace-xml-gallery-builder-settings_titles' ); ?>
        </tbody>
      </table>
    </div>
    <?php    
  } //AceSettings::title_options()
  
  
  /**
   * AceSettings::upload_options()
   * 
   * @return void
   */
  function upload_options() {
    global $ace_gallery;
    ?>
    <div id="ace_upload_options" class="postbox <?php echo $this->installstyle; ?>">
      <h3 class="hndle"><span><?php esc_html_e( 'Upload Options', 'ace-xml-gallery-builder' ) ?></span></h3>
      <div class="inside">
        <p><?php esc_html_e( 'These settings only affect the Ace Gallery image upload forms, not the standard WordPress uploader.', 'ace-xml-gallery-builder' ); ?></p>
      </div>
      <table id="ace_upload_options_table" class="widefat">
        <tbody>                    
        <tr>
          <th scope="row"><label for="fileupload_allowedtypes"><?php esc_html_e( 'Allowed File Extensions', 'ace-xml-gallery-builder' ) ?></label></th>
          <td><input name="ace-xml-gallery-builder[fileupload_allowedtypes]" type="text" id="fileupload_allowedtypes" value="<?php echo $ace_gallery->get_option( 'fileupload_allowedtypes' ); ?>" size="40" />
            <p><?php printf( esc_html__( 'Recommended: %sjpg jpeg gif png%s. Separate extensions by spaces (" ").', 'ace-xml-gallery-builder' ), '<code>', '</code>' ) ?></p>
          </td>
        </tr>                                								
        <tr>
          <th scope="row"><label for="flash_upload"><?php esc_html_e( 'Enable Flash Uploader', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input type="checkbox" id="flash_upload" name="ace-xml-gallery-builder[flash_upload]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('flash_upload') ); ?> /><br />
            <p><?php esc_html_e( 'Use the Adobe Flash Player to upload multiple images at once.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>		
				<tr>
          <th scope="row"><label for="preread"><?php esc_html_e( 'Auto Read Image Data', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input type="checkbox" id="preread" name="ace-xml-gallery-builder[preread]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('preread') ); ?> /><br />
            <p><?php esc_html_e( 'Read image meta data for newly add images', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>								
        <tr>
          <th scope="row"><label for="enable_mwp_support"><?php esc_html_e( 'Enable Web Publishing Wizard Support', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input type="checkbox" id="enable_mwp_support" name="ace-xml-gallery-builder[enable_mwp_support]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('enable_mwp_support') ); ?> /><br />
            <p><?php esc_html_e( 'Note: This feature is only supported for use with Microsoft Windows XP.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <?php if ( 'TRUE' == $ace_gallery->get_option( 'enable_mwp_support' ) ) { ?>
        <tr id="mwp_support">
          <th scope="row">&nbsp;</th>
          <td>
            <div class="submit">
              <a class="button" href="admin.php?page=ace-xml-gallery-builder&amp;xp_wiz" ><?php esc_html_e( 'Upload Wizard Settings', 'ace-xml-gallery-builder' ) ?></a>
            </div>  
          </td>
        </tr> 
        <?php } ?>         
      	<?php do_action( 'ace-xml-gallery-builder-settings_upload' ); ?>
        </tbody>
      </table>
    </div>
    <?php    
  } // AceSettings::upload_options()
  
  /**
   * AceSettings::advanced_options()
   * 
   * @return void
   */
  function advanced_options() {
    global $ace_gallery;
    
    $add = esc_html__( 'Add', 'ace-xml-gallery-builder' ) . ' &raquo;';
    $remove = '&laquo; ' . esc_html__( 'Remove', 'ace-xml-gallery-builder' );
    $users = esc_html__( 'Users', 'ace-xml-gallery-builder' );    
    $option = '<option value="%1s">%2s</option>';   
    $blogusers = ace_get_users_of_blog();
    foreach( $blogusers as $user ) {
    	$user->get_role_caps();
      if ( ! $user->has_cap( 'manage_options') ) { // Administrators are gallery administators by default. They cannot be removed from this role		        
        $optionval = sprintf( $option, $user->ID, esc_attr( $user->user_nicename ) );
        if ( $user->has_cap( 'ace_manager' ) ) { // user has manager capabilities
          $moptions['has'][] = $optionval;
        } else {
          $moptions['not'][] = $optionval;
        }  
      }
    }    
    ?>
    <div id="ace_advanced_options" class="postbox <?php echo $this->installstyle; ?>">
      <h3 class="hndle"><span><?php esc_html_e( 'Advanced Options', 'ace-xml-gallery-builder' ) ?></span></h3>
      <table id="ace_advanced_options_table" class="widefat">
        <tbody>
        <tr>
          <th scope="col"><?php esc_html_e( 'Ace Gallery Administrators', 'ace-xml-gallery-builder' ) ?></th>
          <td>
          	<div id="ace-managers"> 
                <div id="not-manager" class="has_role">
                  <p><strong><?php echo $users ?></strong></p>
                  <select class="multiple" id="not-managers" name="ace-xml-gallery-builder[not-managers][]" multiple="multiple" size="10">
                    <?php if ( isset( $moptions['not'] ) ) { foreach ( $moptions['not'] as $eoption ) echo $eoption ; }  ?>                             
                  </select>
                  <p class="authorbutton"><input class="button-secondary" id="add-manager" name="ace-xml-gallery-builder[add-manager]" type="submit" value="<?php echo $add ?>" /> <img alt="" id="manager-ajax-loading" src="images/wpspin_light.gif" class="ajax-loading" /></p>      
                </div>
                <div id="is-manager" class="has_role">
                  <p><strong><?php esc_html_e( 'Administrators', 'ace-xml-gallery-builder' ); ?></strong></p>
                  <select class="multiple" id="is-managers" name="ace-xml-gallery-builder[is-managers][]" multiple="multiple" size="10">
                    <?php if ( isset( $moptions['has'] ) ) { foreach ( $moptions['has'] as $eoption ) echo $eoption; }  ?>                             
                  </select>      
                  <p class="authorbutton"><input class="button-secondary" id="remove-manager" name="ace-xml-gallery-builder[remove-manager]" type="submit" value="<?php echo $remove ?>" /></p>
                </div>
              <div class="clear"></div> 
            </div>
            <p><?php esc_html_e( 'Blog Administrators are Ace Gallery Administrators by default', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>      
        <tr>
          <th scope="col"><label for="use_permalinks"><?php esc_html_e( 'Use Permalinks for the Gallery', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input type="checkbox" id="use_permalinks" name="ace-xml-gallery-builder[use_permalinks]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('use_permalinks') ); ?> /><br />
            <p><?php esc_html_e( 'Enable this to show Gallery Folders as subpages of your Gallery Page.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <tr>
          <th scope="col"><label for="rel_canonical"><?php esc_html_e( 'Use Canonical links', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input type="checkbox" id="rel_canonical" name="ace-xml-gallery-builder[rel_canonical]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('rel_canonical') ); ?> /><br />
            <p><?php esc_html_e( 'Enable this to add a canonical link for the gallery in your page header.', 'ace-xml-gallery-builder' ) ?></p>
            <p><?php printf( esc_html__( 'This could interfere with SEO plugins. Please read %smore about canonical links%s', 'ace-xml-gallery-builder' ), '<a href="http://askchrisedwards.com/2011/09/05/canonical-urls-revisited/">', '</a>' ); ?></p> 
          </td>
        </tr>
        <tr>
          <th scope="col"><label for="append_search"><?php esc_html_e( 'Append Gallery to Wordpress search results', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input type="checkbox" id="append_search" name="ace-xml-gallery-builder[append_search]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('append_search') ); ?> /><br />
            <p><?php esc_html_e( 'Enable this to show Gallery Folders and Images in the Wordpress search results.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <tr>
          <th scope="col"><label for="excluded_folders_string"><?php esc_html_e( 'Excluded Folders', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input name="ace-xml-gallery-builder[excluded_folders_string]" id="excluded_folders_string" value="<?php echo implode( ',', $ace_gallery->get_option( 'excluded_folders' ) ); ?>" size="60" class="code" type="text" /> <br />
            <p><?php esc_html_e( 'List folders to exclude from the gallery.  Separate folders with commas (",") while omitting spaces.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <tr>
          <th scope="col"><label for="resample_quality"><?php esc_html_e( 'Image Resampling Quality', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input name="ace-xml-gallery-builder[resample_quality]" id="resample_quality" value="<?php echo $ace_gallery->get_option( 'resample_quality' ); ?>" size="10" class="code" type="text" /><br />
            <p><?php esc_html_e( 'Valid settings range from 0 (low quality) to 100 (best quality).  This setting only applies to JPEG files.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <tr>
          <th scope="col"><label for="link_to_gallery"><?php esc_html_e( 'Shortcode links to Gallery', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input type="checkbox" id="link_to_gallery" name="ace-xml-gallery-builder[link_to_gallery]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('link_to_gallery') ); ?> /><br />
            <p><?php esc_html_e( 'Enable this to jump to the Gallery after a user clicks on an folder shortcode in a post.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <tr>
          <th scope="col"><label for="listed_as"><?php esc_html_e( 'The images in the Gallery should be listed as', 'ace-xml-gallery-builder' ); ?></label></th>
          <td>
            <input name="ace-xml-gallery-builder[listed_as]" id="listed_as" value="<?php echo $ace_gallery->get_option( 'listed_as' ) ?>" size="12" type="text" />
          </td>
        </tr>
        <tr>
          <th scope="col"><label for="show_credits"><?php esc_html_e( 'Credits', 'ace-xml-gallery-builder' ); ?></label></th>
          <td>
            <input type="checkbox" name="ace-xml-gallery-builder[show_credits]" id="show_credits" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option( 'show_credits' ) ); ?> /><br />
            <p><?php esc_html_e( 'Enable this to support Ace Gallery by showing the "Powered by Ace Gallery" banner below your gallery', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <tr>
          <th><label for="memory_ok"><?php esc_html_e( 'Do not check Memory before creating images', 'ace-xml-gallery-builder' ) ?></label></th>
          <td>
            <input type="checkbox" id="memory_ok" name="ace-xml-gallery-builder[memory_ok]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('memory_ok') ); ?> /><br />
            <p><?php esc_html_e( 'Enable this to skip the memory check. Warning, this could crash your gallery.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <tr>
          <th><label for="table_layout"><?php esc_html_e(  'Use <table> element for gallery layout', 'ace-xml-gallery-builder' ); ?></label></th>
          <td>
            <input type="checkbox" id="table_layout" name="ace-xml-gallery-builder[table_layout]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('table_layout') ); ?> /><br />
            <p><?php esc_html_e( 'Enable this to use a <table> element to display the gallery as in previous Ace Gallery versions.', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>
        <tr>
          <th><label for="ajax_pagination"><?php esc_html_e(  'Use AJAX to refresh thumbnail pages', 'ace-xml-gallery-builder' ); ?></label></th>
          <td>
            <input type="checkbox" id="ajax_pagination" name="ace-xml-gallery-builder[ajax_pagination]" value="TRUE" <?php checked( 'TRUE', $ace_gallery->get_option('ajax_pagination') ); ?> /><br />
            <p><?php esc_html_e( 'Enable this to refresh the gallery without refreshing the whole page', 'ace-xml-gallery-builder' ) ?><br />
							 <?php esc_html_e( 'Warning: This will stop lightbox or thickbox on secondary pages', 'ace-xml-gallery-builder' ) ?></p>
          </td>
        </tr>        
      	<?php do_action( 'ace-xml-gallery-builder-settings_advanced' ); ?>
        </tbody>
      </table>
    </div>
    <?php    
  } // AceSettings::advanced_options()
  
  function utilities() {
    global $ace_gallery;
    ?>
    <div id="submitdiv" class="postbox <?php echo $this->installstyle; ?>">    
      <h3 class="hndle"><span><?php esc_html_e( 'Utility functions', 'ace-xml-gallery-builder' ) ?></span></h3>
      <div class="inside">
        <div id="submitpost" class="submitbox">
          <div id="misc-publishing-actions">
            <div class="misc-pub-section">
              <div id="cache-rebuilder" class="hide-if-no-js alignleft">
                <p><a id="rebuild-cache" class="button" href="#" title="<?php esc_html_e( 'Create missing thumbs and slides for your gallery', 'ace-xml-gallery-builder' ) ?>"><?php esc_html_e( 'Fill up Cache', 'ace-xml-gallery-builder' ) ?></a></p>
                <p><span id="cache-bar" class="progressBar ajax-loading"></span></p>
              </div>
              <div id="preview-action">
                <p><a class="button" href="admin.php?page=ace-xml-gallery-builder&amp;clear_cache" title="<?php esc_html_e( 'Clear thumbs and slides from your gallery', 'ace-xml-gallery-builder' ) ?>"><?php esc_html_e( 'Clear Cache', 'ace-xml-gallery-builder' ) ?></a></p>                            
              </div>
              <br class="clear"/>    
            </div>
            <div class="misc-pub-section misc-pub-section-last">
              <p><a id="rebuild-database" class="button" href="#" title="<?php esc_html_e( 'Insert Images paths into the WordPress database', 'ace-xml-gallery-builder' ) ?>"><?php esc_html_e( 'Build Links Database', 'ace-xml-gallery-builder' ) ?></a></p>
              <p><span id="database-bar" class="progressBar ajax-loading"></span></p>
              <p><?php echo __( 'Use this function if you see an increase in loading time for your comments.', 'ace-xml-gallery-builder' ) ?></p>
            </div>        
          </div>          
          <div id="major-publishing-actions">
            <div id="delete-action">
              <a onclick="if ( confirm('<?php echo __( 'You are about to Reset all Options.', 'ace-xml-gallery-builder'); ?>\n  \'<?php echo __('Cancel', 'ace-xml-gallery-builder'); ?>\'<?php echo __(' to stop, '); ?> \'OK\'<?php echo __(' to delete.'); ?>')  { return true;}return false;" class="submitdelete deletion" href="admin.php?page=ace-xml-gallery-builder&amp;reset_options" title="<?php esc_attr_e( 'Reset all options to their default values.', 'ace-xml-gallery-builder' )  ?>"><?php esc_html_e( 'Reset Options', 'ace-xml-gallery-builder' ) ?></a>
            </div> 
            <div id="publishing-action">
              <input class="button-primary" type="submit" name="ace-xml-gallery-builder[update_options-s]" value="<?php	esc_html_e( 'Save Changes', 'ace-xml-gallery-builder' )	?>" />
            </div> 
            <div class="clear"></div>
          </div>
        </div>
      </div>
    </div>
    <?php    
  }
  
  function aboutbox() {
    ?>
    <div id="aboutbox" class="postbox <?php echo $this->installstyle; ?>">
      <h3 class="hndle"><span><?php esc_html_e( 'About Ace Gallery', 'ace-xml-gallery-builder' ); ?></span></h3>
      <div class="inside">
        <div id="version" class="misc-pub-section">               
          <div class="versions">
            <p><span id="ace-version-message"><strong><?php esc_html_e( 'Version', 'ace-xml-gallery-builder' ); ?></strong>: <?php echo  ace_version(); ?></span></p>
          </div>
        </div>
        <div id="links" class="misc-pub-section">
          <p><a class="home" target="_blank" href="http://askchrisedwards.com/ace/gallery/"><?php esc_html_e( 'Plugin Homepage', 'ace-xml-gallery-builder' ); ?></a></p>
          <p><a class="notepad" target="_blank" href="http://askchrisedwards.com/ace/gallery/user-guide/"><?php esc_html_e( 'User Guide', 'ace-xml-gallery-builder' ); ?></a></p>
          <p><a class="popular" target="_blank" href="http://askchrisedwards.com/ace/gallery/frequently-asked-questions/"><?php esc_html_e( 'Frequently Asked Questions', 'ace-xml-gallery-builder' ); ?></a></p>
          <p><a class="add" target="_blank" href="http://askchrisedwards.com/forums/forum/requests/"><?php esc_html_e( 'Suggest a Feature', 'ace-xml-gallery-builder' ); ?></a></p>
          <p><a class="rss" target="_blank" href="http://askchrisedwards.com/category/ace-xml-gallery-builder/feed/"><?php esc_html_e( 'Ace Gallery News', 'ace-xml-gallery-builder' ); ?></a></p>
          <p><a class="user-group" target="_blank" href="http://wordpress.org/tags/ace-xml-gallery-builder?forum_id=10"><?php esc_html_e( 'WordPress Forum', 'ace-xml-gallery-builder' ); ?></a></p>
        </div>        
        <div id="donate" class="misc-pub-section misc-pub-section-last">
          <p style="text-align:center"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=1257529" title="<?php esc_html_e( 'Donate with PayPal', 'ace-xml-gallery-builder' ); ?>"><img src="https://www.paypal.com/en_US/NL/i/btn/btn_donateCC_ACE.gif" alt="" /></a></p>
        </div>
      </div>
    </div>
    <?php
  }
    
} // class AceSettings
 
?>
<?php global $ace_gallery; ?>
<script type="text/javascript">
	      function deletefeilds(id){
		  var r=confirm("Do you want to delete This Field?");
				if (r==true)
				  {
				   var id=id;
		           var resultid=id.split("_");
				   jQuery.post('<?php echo $ace_gallery ->plugin_url. '/inc/ace_delete_extra.php';?>', {id:resultid[1]},
			       function(data) {
				   var resultid=id.split("_");
				   document.getElementById("tr_"+resultid[1]).innerHTML='<td colspan="6">This Field Has been deleted. Please Save All Changes.</td>';
				   });
				 }
			}
			
			//document.getElementById('addnew').click(function(){alert('click');});
</script> 