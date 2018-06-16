<?php	
	$error = isset( $_GET['error'] ) ? $_GET['error'] : ( isset( $args['error'] ) ? $args['error'] : '' );
	$errorPath = isset( $_GET['errorPath'] ) ? $_GET['errorPath'] : ( isset( $args['errorPath'] ) ? $args['errorPath'] : '' );
	$path = isset( $_GET['path'] ) ? $_GET['path'] : ( isset( $args['path'] ) ? $args['path'] : '' );

	if( $error && $errorPath || $error == '404' ){
		echo '<div class="-level-error">';
			if( $error == '404' ){

				if( $errorPath )
					echo "Not Found: <strong>$errorPath</strong>";
				else
					echo "<strong>404</strong> - Page Not Found";
			}
			if( $error == '403' )
				echo "403 Error: <strong>$errorPath</strong>";
		echo '</div>';
	}

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

<style>
	.-level-error {
		font-size: 20px;
		line-height: 38px;
		padding: 30px 5px 30px 50px;
		background: #ffdada;
		border-bottom: 1px solid #f19898;
		box-shadow: 0 5px 40px #652424;
		text-align: center;
		margin: 0;
		margin-bottom: 60px;
	}
	.-level-block {
		padding: 15px;
		margin: 10px 0;
		border-left: 2px solid green;
		text-align: left;
	}
		.-level-link {
			display: inline-block;
			padding: 15px;
			margin: -15px 0px 10px -15px;
			border: 2px solid green;
			background: rgba(0, 128, 0, 0.08);
			border-left: none;
			font-size: 20px;
			text-decoration: none;
			color: #000;
			font-weight: bold;
		}
			.-level-link:hover {
				text-decoration: none;
				background: rgba(0, 0, 128, 0.2);
			}
		.-level-separator {
			display: block;
			margin: 30px 0;
		}

	.-text-red {
		color: #b30202;
	}

	.-btn-main {
		font-size:26px;
		padding: 20px 40px;
	}

	.-btn-main-mini {
		font-size: 18px;
		padding: 10px 40px;
		width: 200px;
	}
	.-btn-page {
		font-size: 20px;
		margin: 8px;
		border-width: 0;
	}
		.-btn-page:hover {
			background-color: black !important;
		}
</style>