
(function(jQuery){jQuery.extend({progressBar:new function(){this.defaults={steps:20,stepDuration:20,max:100,showText:true,textFormat:'percentage',width:120,height:12,callback:null,boxImage:'images/progressbar.gif',barImage:{0:'images/progressbg_red.gif',30:'images/progressbg_orange.gif',70:'images/progressbg_green.gif'},running_value:0,value:0,image:null};this.construct=function(arg1,arg2){var argvalue=null;var argconfig=null;if(arg1!=null){if(!isNaN(arg1)){argvalue=arg1;if(arg2!=null){argconfig=arg2;}}else{argconfig=arg1;}}
return this.each(function(child){var pb=this;var config=this.config;if(argvalue!=null&&this.bar!=null&&this.config!=null){this.config.value=parseInt(argvalue)
if(argconfig!=null)
pb.config=jQuery.extend(this.config,argconfig);config=pb.config;}else{var jQuerythis=jQuery(this);var config=jQuery.extend({},jQuery.progressBar.defaults,argconfig);config.id=jQuerythis.attr('id')?jQuerythis.attr('id'):Math.ceil(Math.random()*100000);if(argvalue==null)
argvalue=jQuerythis.html().replace("%","")
config.value=parseInt(argvalue);config.running_value=0;config.image=getBarImage(config);var numeric=['steps','stepDuration','max','width','height','running_value','value'];for(var i=0;i<numeric.length;i++)
config[numeric[i]]=parseInt(config[numeric[i]]);jQuerythis.html("");var bar=document.createElement('img');var text=document.createElement('span');var jQuerybar=jQuery(bar);var jQuerytext=jQuery(text);pb.bar=jQuerybar;jQuerybar.attr('id',config.id+"_pbImage");jQuerytext.attr('id',config.id+"_pbText");jQuerytext.html(getText(config));jQuerybar.attr('title',getText(config));jQuerybar.attr('alt',getText(config));jQuerybar.attr('src',config.boxImage);jQuerybar.attr('width',config.width);jQuerybar.css("width",config.width+"px");jQuerybar.css("height",config.height+"px");jQuerybar.css("background-image","url("+config.image+")");jQuerybar.css("background-position",((config.width*-1))+'px 50%');jQuerybar.css("padding","0");jQuerybar.css("margin","0");jQuerythis.append(jQuerybar);jQuerythis.append(jQuerytext);}
function getPercentage(config){return config.running_value*100/config.max;}
function getBarImage(config){var image=config.barImage;if(typeof(config.barImage)=='object'){for(var i in config.barImage){if(config.running_value>=parseInt(i)){image=config.barImage[i];}else{break;}}}
return image;}
function getText(config){if(config.showText){if(config.textFormat=='percentage'){return" "+Math.round(config.running_value)+"%";}else if(config.textFormat=='fraction'){return" "+config.running_value+'/'+config.max;}}}
config.increment=Math.round((config.value-config.running_value)/config.steps);if(config.increment<0)
config.increment*=-1;if(config.increment<1)
config.increment=1;var t=setInterval(function(){var pixels=config.width/100;if(config.running_value>config.value){if(config.running_value-config.increment<config.value){config.running_value=config.value;}else{config.running_value-=config.increment;}}
else if(config.running_value<config.value){if(config.running_value+config.increment>config.value){config.running_value=config.value;}else{config.running_value+=config.increment;}}
if(config.running_value==config.value)
clearInterval(t);var jQuerybar=jQuery("#"+config.id+"_pbImage");var jQuerytext=jQuery("#"+config.id+"_pbText");var image=getBarImage(config);if(image!=config.image){jQuerybar.css("background-image","url("+image+")");config.image=image;}
jQuerybar.css("background-position",(((config.width*-1))+(getPercentage(config)*pixels))+'px 50%');jQuerybar.attr('title',getText(config));jQuerytext.html(getText(config));if(config.callback!=null&&typeof(config.callback)=='function')
config.callback(config);pb.config=config;},config.stepDuration);});};}});jQuery.fn.extend({progressBar:jQuery.progressBar.construct});})(jQuery);