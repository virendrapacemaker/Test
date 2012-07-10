/*
  images loader for ace-xml-gallery-builder
  copyright 2010 Marcel Brinkkemper
*/

var ace_loading = true;

function ace_js_loadNext() {
  if (jQuery('img.ace_ajax').length) {
    var loadImg = jQuery('.ace_ajax:first');
    if ( loadImg ) {
      loadImg.removeClass('ace_ajax');
      var loadSrc = aceimg.ajaxurl + loadImg.attr('src').split('?')[1];
      loadImg.attr('src', loadSrc );
    }
  } else {	
    if ( ace_loading ) {
    ace_loading = false;
      if(typeof(ace_slideshow) !== 'undefined') {
        if ( jQuery('.ace_slideshow').length ) {  
          ace_js_slideshow();
        }
      }
    }
  }
}

function ace_js_loadFirst() {
	if ( jQuery('img.ace_ajax').length) {
		jQuery('img.ace_ajax').each( function(){
			jQuery(this).load(function(){
				ace_js_loadNext();
			});
		});
	} 	
}

jQuery(window).ready(function() {
  ace_js_loadFirst();  
}) ;

jQuery(window).load(function() {
  ace_js_loadNext();
});