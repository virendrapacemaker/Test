var $ = jQuery.noConflict();

var ace_prevWidth;
var ace_prevHeight;

function ace_popUp( element ) {  
  ace_prevWidth = $(element).width();
  ace_prevHeight = $(element).height();     
  if ( ! $(element).hasClass('popup') ) { 
    $(element).css({'z-index':'100'});
    var t = setTimeout(function() {
      $(element).css({'position':'absolute','top':-(ace_prevHeight+5)});
      $(element).stop().animate({'font-size':'90%', 'line-height':'1em'}, 200, function() {
        $(element).addClass('popup');  
      });          
    }, 200 );
    $(this).data('timeout', t);      
  }  
}

function ace_popDown( element ) {  
  if( $(element).hasClass('popup')) { 
    clearTimeout($(this).data('timeout'));
    $(element).stop().animate({'font-size':'0', 'line-height':'0' }, 200, function() {
      $(element).removeClass('popup');
      $(element).css(({'top':'0','position':'relative', 'z-index':'0'}));  
    });               
  } else {
    clearTimeout($(this).data('timeout'));
  }
}

$(document).ready(function() {  
  $('.ace_thumb').hover(      
    function() {
      ace_popUp($(this));
    },
    function () {
      ace_popDown($(this));
    }
  );
});