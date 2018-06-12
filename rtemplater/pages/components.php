<div class="text-center p-5 m-5">
	<a href="@$pathToWebRoot" class="btn btn-primary btn-secondary btn-success" style="font-size:26px; padding: 20px 40px;">HOME</a>
</div>

<?php
	$components = $this->getResourceList( 'component' );
	foreach( $components as $component ){
		$name = $component['name'];
		$url = $component['url'];
		$component = $this->component( $component['name'] );
?>

<a id="@$name" href="@$url" class="component-name">@$name</a>
<div class="component-code"><textarea>@$component</textarea></div>
<div>@$component</div>

<?php } ?>
<script>
document.addEventListener( "DOMContentLoaded", function(){
	$( '.component-code textarea' ).each(function(){
		$( this ).css( 'height', this.scrollHeight + 10 );
	});
});
</script>
<style>
	.component-name {
		display: block;
		background: #231803;
		padding: 10px 20px;
		font-size: 28px;
		box-shadow: -10px 0 10px #2d220d;
		text-align: center;
		position: -webkit-sticky;
		position: sticky;
		top: -1px;
		margin-top: 100px;
		color: #f9cb9a;
	}
		.component-name:hover {
			text-decoration: none;
			opacity: 0.9;
			color: orange;
		}

	.component-code {
		position: relative;
	}
		.component-code:after {
			content: 'CODE';
			position: absolute;
			top: 0;
			right: 0;
			bottom: 0;
			background: rgba( 255, 165, 0, 0.6 );
			padding: 42px 8px 5px 8px;
			font-weight: bold;
			pointer-events: none;
		}
		.component-code:hover:after {
			display: none;
		}
		.component-code textarea {
			color: white;
			display: block;
			width: 100%;
			padding: 20px 5px 20px 30px;
			padding-bottom: 3px;
			font-size: 20px;
			border: 0;
			height: auto;
			background: rgba(35, 24, 3, 0.9);
			box-shadow: -10px 0 10px #2d220d;
		}
</style>