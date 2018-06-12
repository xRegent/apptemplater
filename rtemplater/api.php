<?php
/*
	Structure:
	/components/componentName.php
	/$tpl/pages/$page.php
	
	@phpFuncOrTplFunc( "value", [ 'arg1'=>"value" ] )
	@component( "name" )
	@page( "name" )
	@$textVariableName( "Dafault value" )

*/


Class rTemplater {

	private $version = '0.0.2';

	function __construct( $options = [] ){
		foreach( $this->defaultOptions as $optionName => $optionValue ){
			if( isset( $_GET[ $optionName ] ) )
				$this->{$optionName} = $_GET[ $optionName ];
			else if( isset( $options[ $optionName ] ) )
				$this->{$optionName} = $options[ $optionName ];
			else
				$this->{$optionName} = $optionValue;
		}

		//if( !$this->section )
		//	$this->section = 'rtemplater';
		//var_dump($this);

		$this->customOptions = $options;
	}

	private $chunks = [];
	private $defaultOptions = [
		'projectName'=> '',
		'section'=> '',
		'sections'=> [],
		'sectionTemplate'=> false,
		'page'=> '',
		'data'=> [],
		'dev'=> true,
		'pathToPages'=> 'pages',
		'pathToComponents'=> 'component',
		'pathToWebRoot'=> '/',
		'pathToWebPage'=> 'page',
		'pathToWebComponent'=> 'component',
		'pathToWebComponents'=> 'component/',
		'alias'=> 'tpl',
		'errorLog'=> ''
	];
	private $transformCustomExpr = '/\{\{(.+)\}\}/';
	private $transformVarExpr = '/@\$([a-zA-Z0-9_]+)(\([^\)]*\))?/';
	private $transformFnExpr = '/@(([a-zA-Z0-9_]+)\((\'[^\']*\'|"[^"]*"|[^\)])*\))/';

	public function file( $file ){
		$text = '';
		error_reporting( E_ALL ^ E_NOTICE ^ E_WARNING );
		$text = file_get_contents( $this->pathToFile( $file ) );
		error_reporting( E_ALL );
		return $text;
	}

	public function renderContent(){
		$content = '';

		if( $this->section && $this->sectionTemplate )
			$content .= $this->render( $this->section . '/template.php' );

		else if( $this->page )
			$content .= $this->renderPage( $this->page );

		if( $this->errorLog )
			$content .= $this->file( $this->errorLog );

		return $content;
	}

	public function render( $file, $args = [] ){
		$text = $this->file( $file );

		if( !$text )
			return " --- File \"<strong>$file</strong>\" not found! --- ";

		return $this->renderHTML( $text, $args );
	}

	public function renderHTML( $text, $args = [] ){		
		$text = $this->transform( $text );
		
		if( is_string( $args ) )
			$args = [ 'content' => $args ];

		ob_start();
		extract( $args );
		eval( ' ?>' . $text . '<?php ' );
		$text = ob_get_contents();
		ob_end_clean();

		if( preg_match( $this->transformFnExpr, $text ) )
			$text = $this->transform( $text );

		return $text;
	}

	public function transform( $text ){
		if( $this->alias )
			$text = str_replace(
				'$' . $this->alias, '$this',
				$text
			);

		$text = preg_replace_callback(
			$this->transformCustomExpr,
			function( $matches ){
				return '<?php echo ' . $matches[ 1 ] . ';?>';
			},
			$text
		);

		$text = preg_replace_callback(
			$this->transformVarExpr,
			function( $matches ){
				if( isset( $this->{$matches[ 1 ]} ) )
					return $this->{$matches[ 1 ]};
				else
					return '<?php echo ' . 
						'isset( $' . $matches[ 1 ] . ' ) ? $' . $matches[ 1 ] . ' : ' .
						( isset( $matches[ 2 ] ) ? $matches[ 2 ] : '""' ) .
					';?>';
			},
			$text
		);

		$text = preg_replace_callback(
			$this->transformFnExpr,
			function( $matches ){
				$isClassMethod = method_exists( $this, $matches[ 2 ] );
				return '<?php echo '
					. ( $isClassMethod ? '$this->' : '' )
					. $matches[ 1 ]
					. ';?>';
			},
			$text
		);

		//var_dump( '--------------------------------------------------------------'  );
		//var_dump( 'PREG REPLACE result: ' . $text );

		return $text;
	}

	public function component( $name, $args = [] ){
		return $this->render( $this->pathToComponents . '/' . $name . '.php', $args );
	}

	public function tplComponent( $name, $args = [] ){
		return $this->render(
			$this->section . '/' . $this->pathToComponents . '/' . $name . '.php',
			$args
		);
	}

	public function renderPage( $name = '', $args = [] ){
		return $this->render(
			( $this->section ? $this->section . '/' : '' ) . $this->pathToPages . '/' . ( $name ? $name : $this->page ) . '.php',
			$args
		);
	}

	public function pathToFile( $file ){
		return $_SERVER[ 'DOCUMENT_ROOT' ] . '/' . $file;
	}

	public function chunk( $name, $data = null ){
		if( $data != null )
			$this->chunks[ $name ] = $data;
		else if( isset( $this->chunks[ $name ] ) )
			return $this->chunks[ $name ];

		return '';
	}

	public function getFileResources( $path ){
		$files = glob( $this->pathToFile( $path . "/[^_]*.php" ) );
		$names = [];
		foreach( $files as $file ){
			preg_match( "/(.+\/)?(.+).php/", $file, $matches );
			$names[] = $matches[ 2 ];
		}
		return $names;
	}

	public function getResourceList( $type = '', $section = null ){
		if( $section === null )
			$section = $this->section;

		if( $type == 'component' )
			$names = $this->getFileResources( $this->pathToComponents );
		if( $type == 'page' )
			$names = $this->getFileResources( ( $section ? $section . '/' : '' ) . $this->pathToPages );

		if( !isset( $names ) )
			return [];

		$arr = [];
		foreach( $names as $name ){
			$arr[] = [
				'name'=> $name,
				'title'=> strtoupper( str_replace( '_', ' ', $name ) ),
				'url'=>  $this->url( $name, $type, $section )
			];
		}

		return $arr;
	}

	public function url( $name = '', $type = 'page', $section = null ){
		if( $section === null )
			$section = $this->section;

		$url = '';

		if( $type == 'component' )
			$url = $this->pathToWebRoot . $this->pathToWebComponent . '/' . $name;
		if( $type == 'page' )
			$url = $this->pathToWebRoot . ( $section ? $section : $this->pathToWebPage ) . '/' . $name;

		return $url;
	}

	public function pageTitle( $page = null, $section = null ){
		if( $page === null )
			$page = $this->page;
		if( $section === null )
			$section = $this->section;

		if( $section == 'rtemplater' && $page == 'component' && isset( $_GET[ 'component' ] ) )
			$page = $_GET[ 'component' ];

		$title = $this->projectName;
		$title .= $section && $section != 'rtemplater' ? ( $title ? ' ' : '' ) . strtoupper( $section ) : '';
		$title .= ( $title ? ': ' : '' ) . str_replace( '_', ' ', $page );

		return $title;
	}

	public static $link;

}

function app( $name ){
	global $tpl;
	return isset( $tpl->customOptions[ $name ] ) ? $tpl->customOptions[ $name ] : '';
}