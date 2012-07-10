/*
 jQuery slideshow for Ace Gallery
 copyright 2009-2010 Marcel Brinkkemper
 version 1.1
 */

function ace_js_slideshow() {
    jQuery('.ace_loading').each( function() {
      jQuery(this).hide();
    });
    jQuery('.ace_slideshow').each( function() { 
      jQuery(this).children('a').css({opacity: 0.0});
      jQuery(this).children('a').css({visibility: 'visible'});
      var maxWidth = 0; 
      var maxHeight = 0;  
      jQuery(this).children('a').each( function(index, object) {
        var imgWidth = parseInt(jQuery(object).find('img:first').width());
        maxWidth = (imgWidth > maxWidth)? imgWidth : maxWidth;
        var imgHeight = parseInt(jQuery(object).find('img:first').height());
        maxHeight = (imgHeight > maxHeight)? imgHeight : maxHeight;
      })
      jQuery(this).css({width:maxWidth+'px', height:maxHeight+'px'});
      var first = jQuery(this).children('a:first');
      var firstImg = first.find('img');     
      first.css({opacity: 1.0}); 
      var leftPad = ( maxWidth - parseInt( jQuery(this).children('a:first').find('img').width() ) ) / 2;
      var bottomPad = ( maxHeight - parseInt( jQuery(this).children('a:first').find('img').height() ) ) / 2;
      var title = firstImg.attr('rel');
      if ( !title ) title = '';
      if ( title == ' ' ) { title = '' };  	
      first.css({opacity: 0.0})
        .addClass('show')
        .animate({opacity: 1.0}, aceshow.slideview )
        .css({left: leftPad+'px', bottom:bottomPad+'px'});  
   	  jQuery(this).children('.sstitle').animate({opacity: 0.0}, { queue:false, duration:0 }).animate({height: '0px'}, { queue:true, duration:aceshow.titlequeue });
      if ( jQuery(this).attr('id').match('ace_slideshow') && ( title.length > 0 )  ) {
      jQuery(this).children('.sstitle').css({ width: firstImg.width(), left: leftPad+'px', bottom:bottomPad+'px' });
    	jQuery(this).children('.sstitle').animate({opacity: 0.7},aceshow.titleopcty ).animate({height: '100px'},aceshow.titlequeue );
    	jQuery(this).children('.sstitle').html(title);      
      } 
    }); 
  	setInterval('ace_js_gallery_show()', aceshow.duration );	
}

function ace_js_gallery_show() {
  jQuery('.ace_slideshow').each( function() {    
    var current = jQuery(this).children('a.show');
  	var next = ((current.next().length) ? ((current.next().hasClass('sstitle'))? jQuery(this).children('a:first') :current.next()) : jQuery(this).children('a:first'));	
  	var nextImg = next.find('img');
    var leftPad = ( parseInt( jQuery(this).width() ) - parseInt( nextImg.width() ) ) / 2;
    var bottomPad = ( parseInt( jQuery(this).height() ) - parseInt( nextImg.height() ) ) / 2;
    var title = nextImg.attr('rel');  
    if ( !title ) title = '';
    if ( title == ' ' ) { title = '' };
  	next.css({opacity: 0.0})
  	.addClass('show')
  	.animate({opacity: 1.0}, aceshow.slideview )
    .css({left: leftPad+'px', bottom:bottomPad+'px'});
  	current.animate({opacity: 0.0}, aceshow.slideview )
  	.removeClass('show');    
   	jQuery(this).children('.sstitle').animate({opacity: 0.0}, { queue:false, duration:0 }).animate({height: '0px'}, { queue:true, duration:aceshow.titlequeue });
    if ( jQuery(this).attr('id').match('ace_slideshow') && ( title.length > 0 ) ) {
      jQuery(this).children('.sstitle').css({ width: nextImg.width(), left: leftPad+'px', bottom:bottomPad+'px' });	
    	jQuery(this).children('.sstitle').animate({opacity: 0.7},aceshow.titleopcty ).animate({height: '100px'},aceshow.titlequeue );
    	jQuery(this).children('.sstitle').html(title);      
    }	
  });
}
var ace_slideshow = true;
var aceCounter = 0;

jQuery(window).load(function() {  
  if(typeof(ace_loading) === 'undefined') {
    if ( jQuery('.ace_slideshow').length ) {  
      ace_js_slideshow();
    }
  }
}) ;
