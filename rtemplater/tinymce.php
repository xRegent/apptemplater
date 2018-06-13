<textarea style="display:block;width:100%;height:300px;" id="TINY_MCE">
<?php
	$components = $this->getResourceList( 'component' );
	foreach( $components as $component ){
		echo '<div>' . $this->component( $component['name'] ) . '</div>' . "\n\n<br>\n\n";
	}
?>
</textarea>
<?php $tpl->chunk( 'post-scripts', "
<script src='https://cdnjs.cloudflare.com/ajax/libs/tinymce/3.5.8/tiny_mce.js'></script>
<script>
$(function(){
	$( '#TINY_MCE' ).height( $( window ).height() - 100 );;
	tinymce.init({
		selector: '#TINY_MCE',
		content_css : '/files/build-dev/main.css?' + new Date().getTime(),
	});
});
</script>
" );?>