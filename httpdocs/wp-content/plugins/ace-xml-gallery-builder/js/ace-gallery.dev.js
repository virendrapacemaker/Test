/* Scripts for Ace Gallery Frontend */
/* Copyright (c) Christopher http://askchrisedwards.com */
/* By Marcel Brinkkemper */


function ace_doCounts() { 
  
  /* count images in folders table */
  if ( jQuery('.ace_folder_allcount').length ) {
    jQuery('.ace_folder_allcount').each( function() {
      var theID = jQuery(this).attr('id');
      var thisFolder = ace_virtual.root + jQuery(this).attr('title');
      var data = {
        action: 'ace_folder_subcount',
        allcount: 'true',
        folder: thisFolder
      };                 
      jQuery.post( ace_ajax.ajaxurl, data, function(response) {        
        theID = '#' + theID;                 
        jQuery(theID).html(response);
      });    
    });
  }
    
  /* count subfolders in folders table */  
  if ( jQuery('.ace_folder_subcount').length ) {
    jQuery('.ace_folder_subcount').each( function() {
      var theID = jQuery(this).attr('id');
      var thisFolder = ace_virtual.root + jQuery(this).attr('title');
      var data = {
        action: 'ace_folder_subcount',
        folder: thisFolder
      };              
      jQuery.post( ace_ajax.ajaxurl, data, function(response) {        
        theID = '#' + theID;                 
        jQuery(theID).html(response);
      });    
    });
  }
}

jQuery(window).ready(function(){     
  
  ace_doCounts();
  
   /* pagination events */
  if ( ace_ajax.pagination == 'ajax' ) {

	  /* prev next for folder thumbnails */
	  jQuery('.folder_pagination a').live( 'click', function() {
	    var folderForm = jQuery(this).closest('form');
	    var folderDiv = jQuery(this).closest('.folders');
	    var galleryDiv = jQuery(this).closest('.ace_gallery');
	    var current = parseInt( jQuery("input[name='current']", folderForm ).val(), 10 );
	    var paged;  		   
	    switch (jQuery(this).attr('class')) {
	     case 'first-page' : paged = 0; break;
	     case 'prev-page'  : paged = current - 1; break;
	     case 'next-page'  : paged = current + 1; break;
	     case 'last-page'  : paged = parseInt( jQuery("input[name='last_page']", folderForm ).val(), 10 );
	    }         
	    
	    data = {
	      action: 'ace_next_dirs',
	      folder: jQuery("input[name='folder']", folderForm ).val(),			 
				user_id: jQuery("input[name='user_id']", folderForm ).val(),      
				virtual: jQuery("input[name='virtual']", folderForm ).val(),           
	      perpage: jQuery("input[name='perpage']", folderForm ).val(),    
	      columns: jQuery("input[name='columns']", folderForm ).val(),  
	      ajax_nonce: jQuery("input[name='ajax_nonce']", folderForm ).val(),
	      request_uri: jQuery("input[name='request_uri']", folderForm ).val(),
	      ace_paged:  paged
	    }
	    jQuery.post( ace_ajax.ajaxurl, data, function(response) {
				folderDiv.replaceWith( response ) 
				if(typeof ace_js_loadFirst == 'function') {    
	  			ace_js_loadFirst();
	  		}      
	   	 	ace_doCounts();						   
				if(typeof ace_js_loadNext == 'function') {    
	  			ace_js_loadNext();
	  		} 
	  		jQuery(window).trigger('ace_refresh');
	  	});
	    return false;
	  });
	  
	  /* prev next for image thumbnails */
	  jQuery('.image_pagination a').live( 'click', function() {
	    var imageForm = jQuery(this).closest('form');
	    var thumbDiv = jQuery(this).closest('.thumb_images');
	    var galleryDiv = jQuery(this).closest('.ace_gallery');
	    var current = parseInt( jQuery("input[name='current']", imageForm ).val(), 10 );
	    var paged;     
	    switch (jQuery(this).attr('class')) {
	     case 'first-page' : paged = 0; break;
	     case 'prev-page'  : paged = current - 1; break;
	     case 'next-page'  : paged = current + 1; break;
	     case 'last-page'  : paged = parseInt( jQuery("input[name='last_page']", imageForm ).val(), 10 );
	    }        	  
	    data = {
	      action: 'ace_next_thumbs',
	      folder: ace_virtual.root + jQuery("input[name='folder']", imageForm ).val(),      
	      perpage: jQuery("input[name='perpage']", imageForm ).val(),    
	      columns: jQuery("input[name='columns']", imageForm ).val(),  
	      post_id: jQuery("input[name='post_id']", imageForm ).val(),
	      ajax_nonce: jQuery("input[name='ajax_nonce']", imageForm ).val(),
	      request_uri: jQuery("input[name='request_uri']", imageForm ).val(), 
	    	virtual: ace_virtual.root,      
	      ace_pagei:  paged
	    }			
	    jQuery.post( ace_ajax.ajaxurl, data, function(response) {
				thumbDiv.replaceWith( response );
				if(typeof ace_js_loadFirst == 'function') {  
	  			ace_js_loadFirst();     
	  		}      	   					   
				if(typeof ace_js_loadNext == 'function') {    
	  			ace_js_loadNext();
	  		}	 
	  		jQuery(window).trigger('ace_refresh');
	    });
	    return false;
	  });
	   
	  jQuery("input[name='ace_paged']").live( 'keypress', function(e) {
	    var c = e.which ? e.which : e.keyCode;
	    if (c == 13) {    	
	      e.preventDefault();
				var folderForm = jQuery(this).closest('form');
			  var folderDiv = jQuery(this).closest('.folders');
			  var galleryDiv = jQuery(this).closest('.ace_gallery');
			  var current = parseInt( jQuery( "input[name='current']", folderForm ).val(), 10 );
			  var newPage = parseInt( jQuery(this).val(), 10 );	  		  
			  if ( newPage != current ) {  	
			    var lastPage = jQuery( "input[name='last_page']", folderForm ).val();
			    if ( newPage < 1 ) {
			      newPage = 1;        
			      jQuery(this).val('1');
			    }
			    if ( newPage > lastPage ) {
			      newPage = lastPage;
			      jQuery(this).val(lastPage);  
			    }   
			    data = {
			      action: 'ace_next_dirs',
			      folder: jQuery("input[name='folder']", folderForm ).val(),			 
						user_id: jQuery("input[name='user_id']", folderForm ).val(),      
						virtual: jQuery("input[name='virtual']", folderForm ).val(),     
			      perpage: jQuery("input[name='perpage']", folderForm ).val(),    
			      columns: jQuery("input[name='columns']", folderForm ).val(),  
			      request_uri: jQuery("input[name='request_uri']", folderForm ).val(),
			    	ajax_nonce: jQuery("input[name='ajax_nonce']", folderForm ).val(),
			      ace_paged:  newPage
			    } 
					
			    jQuery.post( ace_ajax.ajaxurl, data, function(response) {
		  			folderDiv.replaceWith( response )  				
						if(typeof ace_js_loadFirst == 'function') {    
	      			ace_js_loadFirst();
	      		} 			       
	       	 	ace_doCounts();						   
						if(typeof ace_js_loadNext == 'function') {    
	      			ace_js_loadNext();
						}						
	  				jQuery(window).trigger('ace_refresh');				
			  	});     
			  }
	      return false;
	    }
	  });
	  
	  jQuery("input[name='ace_pagei']").live( 'keypress', function(e) {
	    var c = e.which ? e.which : e.keyCode;
	    if (c == 13) {
	      e.preventDefault();
			  var imageForm = jQuery(this).closest('form');
			  var thumbDiv = jQuery(this).closest('.thumb_images');
			  var galleryDiv = jQuery(this).closest('.ace_gallery');
			  var current = parseInt( jQuery( "input[name='current']", imageForm ).val(), 10 );
			  var newPage = parseInt( jQuery(this).val(), 10 );  
			  if ( newPage != current ) {  	
			    var lastPage = jQuery( "input[name='last_page']", imageForm ).val();  
			    if ( newPage < 1 ) {
			      newPage = 1;        
			      jQuery(this).val('1');
			    }
			    if ( newPage > lastPage ) {
			      newPage = lastPage;
			      jQuery(this).val(lastPage);  
			    }
			    data = {
			      action: 'ace_next_thumbs',
			      folder: ace_virtual.root + jQuery("input[name='folder']", imageForm ).val(),      
			      perpage: jQuery("input[name='perpage']", imageForm ).val(),    
			      columns: jQuery("input[name='columns']", imageForm ).val(),  
			      request_uri: jQuery("input[name='request_uri']", imageForm ).val(),
			    	ajax_nonce: jQuery("input[name='ajax_nonce']", imageForm ).val(),    	
			    	post_id: jQuery("input[name='post_id']", imageForm ).val(),
			    	virtual: ace_virtual.root, 
			      ace_pagei:  newPage
			    }	    
					jQuery.post( ace_ajax.ajaxurl, data, function(response) {
						thumbDiv.replaceWith( response );
						if(typeof ace_js_loadFirst == 'function') {  
			  			ace_js_loadFirst();     
			  		}      	   					   
						if(typeof ace_js_loadNext == 'function') {    
			  			ace_js_loadNext();
			  		}	  			  		
	  				jQuery(window).trigger('ace_refresh');
	    		});  
			  }
	      return false;
	    }
	  });
	  
	jQuery(window).bind('ace_refresh', function(){
		if((typeof Shadowbox=='object')&&(typeof Shadowbox.setup=='function'))
			Shadowbox.setup();
		if(jQuery().fancybox)
			jQuery('a.ace').attr('rel', 'folder').fancybox();
	});
  
  } 
  /* end pagination event */ 
  
  
});  