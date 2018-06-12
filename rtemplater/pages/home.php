<div class="text-center p-5 m-5">
	<a href="@$pathToWebRoot@$pathToWebComponents" class="btn btn-primary btn-secondary btn-success -btn-main">COMPONENTS</a>
</div>

<div class="container">
	<div class="row"><?php

	$sections = $this->sections;
	if( count( $this->getFileResources( $this->pathToPages ) ) )
		$sections[] = '';

	$grid = 12 / count( $sections );
	$grid = $grid < 4 ? 4 : $grid;

	foreach( $sections as $section ){
		$pages = $this->getResourceList( 'page', $section );
		if( count( $pages ) ){
			echo '<div class="col-md-' . $grid . ' text-center">';
			echo '<div class="p-3 -section-name">' . strtoupper( $section ? $section : 'pages' ) . '</div>';
			foreach( $pages as $page ){
				$rgb = rand( 0, 150 ) . ', ' . rand( 0, 150 ) . ', ' . rand( 0, 150 );
				echo "\n" . '<a href="' . $page['url'] . '" class="btn btn-primary -btn-page" style="'
					. 'background-color: rgb( ' . $rgb . ' );'
					. 'border-color: rgba( ' . $rgb . ', 1.5 );'
					. '">' . $page['title'] . "</a>\n";
			}
			echo '</div>';
		}
	}

	?></div>
</div>

<style>
	.-section-name {
		font-size: 24px;
	}

	.-btn-main {
		font-size:26px;
		padding: 20px 40px;
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