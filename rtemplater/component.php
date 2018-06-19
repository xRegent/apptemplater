<?php
	
	$components = $this->scanFolder( $this->pathToComponentFolder );
	$singleComponent = $this->args( 'component' );
	$singleComponentData = null;
	$content = '';

	if( $singleComponent ){

		foreach( $components as $component ){
			if( $component['name'] == $singleComponent )
				$singleComponentData = $component;

			$content .= '<a id="'.$component['name'].'" href="'.$component['url'].'" class="btn btn-primary btn-success">'.$component['name'].'</a>';
		}

		if( !$singleComponentData ){
			$this->addError( "Component \"$singleComponent\" Not Found!" );
			$content = $content ? '<br><br><br><br><br>All Components:<br><br>' . $content : '';
		}
	}

	if( $singleComponent ){
		if( $singleComponentData ){
			$componentName = $component['name'];
			$componentUrl = $component['url'];
			$componentHTML = $this->render( $component['path'] );

			echo '<div>' . $componentHTML . '</div>';
			echo '<div class="component-code"><textarea>' . $componentHTML . '</textarea></div>';
		}

		else if( $content )
			echo '<div class="container -component-not-found">' . $content . '</div>';
	}

	else
		foreach( $components as $component ){
			$componentName = $component['name'];
			$componentUrl = $component['url'];
			$componentHTML = $this->render( $component['path'] );

			if( $component['type'] == 'page' ){
				echo '<a id="'.$componentName.'" href="'.$componentUrl.'" class="component-name">'.$componentName.'</a>';
				echo '<div class="component-code"><textarea>'.$componentHTML.'</textarea></div>';
				echo '<div>'.$componentHTML.'</div>';
			}
		}
?>

<script>
document.addEventListener( "DOMContentLoaded", function(){
	$( '.component-code textarea' ).each(function(){
		$( this ).css( 'height', this.scrollHeight + 10 );
	});
});
</script>

@renderPage( 'rtemplater/__styles' )