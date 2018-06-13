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

	public static $ALIAS = 'RT';
	public static $version = '0.0.5';

	function __construct( $options = [] ){
		$this->options = $options;
		$rt_options = $options;

		if( isset( $options['rtemplater'] ) )
			$rt_options = $options['rtemplater'];

		foreach( $this->defaultOptions as $optionName => $optionValue ){
			if( isset( $_GET[ $optionName ] ) )
				$this->{$optionName} = $_GET[ $optionName ];
			else if( isset( $rt_options[ $optionName ] ) )
				$this->{$optionName} = $rt_options[ $optionName ];
			else
				$this->{$optionName} = $optionValue;
		}

		if( $this->pathToRoot === null )
			$this->pathToRoot = $_SERVER[ 'DOCUMENT_ROOT' ] . '/';

		$formatedLevels = [];
		foreach( $this->levels as $level ){
			if( $level )
				$formatedLevels[] = str_replace( '/', '', $level );
		}
		$this->levels = $formatedLevels;
		$this->levelCount = count( $formatedLevels );
		if( $this->levelCount ){
			$this->firstLevel = $formatedLevels[ 0 ];
			$this->lastLevel = $formatedLevels[ $this->levelCount - 1 ];
		}
		else
			$this->firstLevel = $this->lastLevel = null;

		$this->level = $this->levelInfo( 1 );
		rTemplater::$ALIAS = $this->alias;





		echo '<pre>$GET  - '; print_r( $_GET ); echo '</pre>';
		echo '<pre>LEVEL - '; print_r( $this->levels ); echo '</pre>';
		//var_dump($this);
	}

	private $chunks = [];
	private $errors = [];
	private $transformCustomExpr = '/\{\{(.+)\}\}/';
	private $transformVarExpr = '/@\$([a-zA-Z0-9_]+)(\([^\)]*\))?/';
	private $transformFnExpr = '/@(([a-zA-Z0-9_]+)\((\'[^\']*\'|"[^"]*"|[^\)])*\))/';
	private $defaultOptions = [
		'projectTitle'        => '',
		'levels'              => [],
		'browseLevel'         => '',
		'currentLevelDeep'    => 0,

		'alias'               => 'RT',
		'sections'            => [],
		'pathToRoot'          => null,
		'pathToWebRoot'       => '/',
		'pathToSection'       => './',
		'pathToWebSection'    => '',
		'pathToComponent'     => './component/',
		'pathToWebComponent'  => 'component/',
		'log'                 => true,
		'logFile'             => '',
		'templateFileName'    => '__template.php',
		'fileExtension'       => '.php'
	];


	public function path( $file ){
		return $this->pathToRoot . $file;
	}
	public function file( $file ){
		$text = '';
		error_reporting( E_ALL ^ E_NOTICE ^ E_WARNING );
		$text = file_get_contents( $this->path( $file ) );
		error_reporting( E_ALL );
		return $text;
	}
	public function isFile( $file ){
		return file_exists( $this->path( $file ) );
	}

	public function renderApp(){
		$appContent = $this->render( $this->templateFileName );

		//if( $this->log )
		//	$appContent .= $this->printErrors();

		if( $this->logFile )
			$appContent .= $this->file( $this->logFile );

		return $appContent;
	}

	public function renderLevel( $deep = null ){
		if( $deep === null ){
			$this->currentLevelDeep++;
			$deep = $this->currentLevelDeep;
		}
		$level = $this->levelInfo( $deep );
		$this->level = $level;

		//var_dump( $level );

		if( $level->name == $this->lastLevel ){
			if( $level->isPage ){
				if( $this->browseLevel )
					return 'BROWSE OF LEVEL ' . $level->path;
				else
					return $this->render( $level->pagePath );
			}
			else {
				// 404 Error Page
			}
		}

		else if( $level->isTemplate )
			return $this->render( $level->templatePath );
		else {
			// 404 Error
		}


		//return $this->renderPage();
	}

	public function levelInfo( $deep ){
		$level               = new stdClass();
		$level->deep         = $deep;
		$level->name         = $this->levels[ $level->deep - 1 ];
		$level->path         = join( array_slice( $this->levels, 0, $level->deep ), '/' );
		$level->templatePath = $level->path . '/' . $this->templateFileName;
		$level->isTemplate   = $this->isFile( $level->templatePath );
		$level->pagePath     = $level->path . $this->fileExtension;
		$level->isPage       = $this->isFile( $level->pagePath );
		return $level;
	}

	public function renderPage( $path = null ){
		return $this->render( ( $path === null ? $this->level->path : $path ) . $this->fileExtension );
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

	public function chunk( $name, $data = null ){
		if( $data != null )
			$this->chunks[ $name ] = $data;
		else if( isset( $this->chunks[ $name ] ) )
			return $this->chunks[ $name ];

		return '';
	}

	public function getFileResources( $path ){
		$files = glob( $this->path( $path . "/[^_]*.php" ) );
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

		return 'PAGE TITLE';

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

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function app( $name = null ){
	global ${rTemplater::$ALIAS};
	$RT = ${rTemplater::$ALIAS};

	if( $name === null )
		return $RT->options;
	else {
		$path = explode( ".", $name );
		$value = $RT->options;
		foreach( $path as $level_name ){
			if( isset( $value[ $level_name ] ) )
				$value = $value[ $level_name ];
			else
				return null;
		}

		return $value;
	}
}