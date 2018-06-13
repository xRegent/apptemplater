<?php
	include( 'api.php' );

	$options = [];
	if( isset( $_GET[ 'options' ] ) )
		$options = json_decode( file_get_contents( $_SERVER[ 'DOCUMENT_ROOT' ] . '/' . $_GET[ 'options' ] ), true );

	$tpl = new rTemplater( $options );

	echo $tpl->render( 'template.php' );
?>