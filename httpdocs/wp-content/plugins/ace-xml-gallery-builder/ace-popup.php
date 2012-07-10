<?php

	// Don't remove this lines:
	require_once('../../../wp-blog-header.php');
	global $ace_gallery;
	$folder = isset( $_GET['folder'] ) ? stripslashes( rawurldecode( $_GET['folder'] ) ) : '';
	$image = isset(  $_GET['image'] ) ? stripslashes( rawurldecode( $_GET['image'] ) ) : '';
	
	$file = $ace_gallery->address.$folder.$image;
	$test = $ace_gallery->root.$folder.$image;
	if ( ! file_exists( $test ) || ( $image == '' ) || ( $folder == '' ) ) { 
		die( 'Cheating huh?' );
	} else {

?>

<! DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

	<head>

		<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
		<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" />

		<title><?php echo esc_html( $image ) ?></title>
		
		<style type="text/css">
			body {
				text-align:center;
				margin:0;
				padding:0;
			}
			img {
				border:none;
			}
		</style>
		<script type="text/javascript">
		function WinWidth()	{
			if (window.innerWidth!=window.undefined) return window.innerWidth; 
			if (document.compatMode=='CSS1Compat') return document.documentElement.clientWidth; 
			if (document.body) return document.body.clientWidth; 
			return window.undefined; 
		}
		
		function WinHeight() {
			if (window.innerHeight!=window.undefined) return window.innerHeight; 
			if (document.compatMode=='CSS1Compat') return document.documentElement.clientHeight; 
			if (document.body) return document.body.clientHeight; 
			return window.undefined; 
		}
		
		function FitPic() { 
			iWidth=WinWidth();
			iHeight=WinHeight();
			iWidth = document.images[0].width - iWidth; 
			iHeight = document.images[0].height - iHeight; 
			window.resizeBy((iWidth), (iHeight))
			self.focus(); 
		} 

		</script>
	</head>

	<body onload="FitPic()">
		<a href="javascript:self.close()" title="<?php esc_attr_e( 'Click to close', 'ace-gallery' ); ?>">
			<img src="<?php echo  $file ; ?>" alt="" />
		</a>
	</body>
</html>

<?php
}

?>