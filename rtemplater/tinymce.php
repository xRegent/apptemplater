<textarea style="display:block;width:100%;height:300px;" id="TINY_MCE">
<?php
	$components = $this->scanFolder( $this->pathToComponentFolder );
	$additionalStyles = app('styles.WYSIWYG');
	$v = $this->args('v');
	if( !$v )
		$v = '3.5.8';

	$tinymce_filename = 'tinymce.min.js';

	if( substr( $v, 0, 1 ) == '3' )
		$tinymce_filename = 'tiny_mce.js';

	foreach( $components as $component ){
		if( $component['type'] == 'page' )
			echo '<div>' . $this->renderComponent( $component['name'] ) . '</div>' . "\n\n<br>\n\n";
	}
?>
</textarea>

<?php $this->chunk( 'post-scripts', "
<script src='https://cdnjs.cloudflare.com/ajax/libs/tinymce/$v/$tinymce_filename'></script>
<script>
$(function(){
	$( '#TINY_MCE' ).height( $( window ).height() - 100 );
	tinymce.init({
		selector: '#TINY_MCE',
		" . ( $additionalStyles ? "content_css : '" . $additionalStyles . '?' . $this->generate('timestamp') . "'" : '' ) . "
	});
});
</script>
" );?>