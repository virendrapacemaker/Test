<?php 
global $ace_gallery, $file,$wpdb,$wp;
require_once( '../../../../wp-load.php' );
require_once( '../ace-fields.php' );
//$ace_fields = new AceFields();
$options = get_option( 'ace-fields' );
//print_r($options);
//print_r(serialize($options));
$new_options=array();
for($i=0;$i<=count($options);$i++)
{
  if($i!=($_POST['id']-1)){
   //echo $i;
   $new_options[]=$options[$i];
  }
   
}
//array_pop($new_options);
//print_r($new_options) ;
      
     
  
update_option( 'ace-fields', serialize($new_options));
//print_r($_POST);

?>