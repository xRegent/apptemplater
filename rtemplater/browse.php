<?php
	$path = $this->args( 'path' );
	$error = $this->args( 'error' );
	$errorPath = $this->args( 'errorPath' );
	$message = $this->args( 'message' );
	$messages = [];

	if( $this->printErrorLog && count( $this->errorLog ) ){
		$messages = $this->errorLog;
		$this->errorLog = [];
	}

	if( $message )
		$messages[] = $message;

	if( $error ){
		if( $error == '404' )
			$messages[] = "404 - Page Not Found" . ( $errorPath ? ' (' . $errorPath . ')' : '' );
		else
			$messages[] = "Error $error";
	}

	if( count( $messages ) )
		echo '<pre class="-level-error">' . join( "\n----------\n", $messages ) . '</pre>';
?>
<div class="container">

	<div class="row text-center my-5 pb-5">
		<div class="col-md-4 mt-3 text-md-left">
			<a href="@$pathToWebRoot" class="btn btn-primary btn-secondary -btn-main-mini -btn-success">Home</a>
		</div>
		<div class="col-md-4">
			<a href="@$pathToWebRoot@$pathToWebComponent" class="btn btn-primary btn-secondary btn-success -btn-main">COMPONENTS</a>
		</div>
		<div class="col-md-4 mt-3 text-md-right">
			<a href="{{ $this->pathToWebRoot }}dev/tinymce?v=3.5.8" class="btn btn-primary btn-secondary -btn-main-mini -btn-success">Tiny MCE</a>
		</div>
	</div>

<?php

	$resources = $this->scanFolder( $path );
	$content = '';

	foreach( $resources as $item ){
		$itemContent = '';

		if( $content && $item['type'] == 'folder' )
			$itemContent .= '</div><hr class="-level-separator"><div class="-level-block">'.PHP_EOL;

		//if( !$content && $item['type'] == 'page' )
		//	$itemContent .= '<div><a href="." class="-level-link">PAGES</a></div>'.PHP_EOL;


		if( $item['type'] == 'folder' )
			$itemContent .= '<div><a href="' . $item['url'] . '" class="-level-link">' .
				preg_replace( '/\//', '<span class="-text-red">/</span>', preg_replace( '/^\/|\/$/', '', $item['url'] ) ) .
			'</a></div>'.PHP_EOL;

		else if( $item['type'] == 'page' )
				$itemContent .= '<a href="' . $item['url'] . '" class="btn btn-primary -btn-page" style="'
				. $this->generate( 'random-bg-color' )
				. '">' . $item['title'] . '</a>'.PHP_EOL;

		$content .= $itemContent;
	}
	
	echo $content ? '<div class="-level-block">' . PHP_EOL . $content . PHP_EOL . '</div>' : '';
?>	
</div>

@renderPage( 'rtemplater/__styles' )