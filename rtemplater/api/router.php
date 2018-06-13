<?php
	include( 'class.php' );

	$options = json_decode(
		file_get_contents(
			$_SERVER[ 'DOCUMENT_ROOT' ] . '/' .
			( isset( $_GET[ 'options' ] ) ? $_GET[ 'options' ] : 'package.json' )
		),
		true
	);

	$RT = ${rTemplater::$ALIAS} = new rTemplater( $options ? $options : [] );

	echo $RT->renderApp();
?>