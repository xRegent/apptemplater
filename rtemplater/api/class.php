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



		//echo '<pre>$GET  - '; print_r( $_GET ); echo '</pre>';
		//echo '<pre>LEVEL - '; print_r( $this->levels ); echo '</pre>';
		//var_dump($this);
	}

	private $chunks = [];
	private $errors = [];
	private $transformCustomExpr = '/\{\{(.+)\}\}/';
	private $transformVarExpr = '/@\$([a-zA-Z0-9_]+)(\([^\)]*\))?/';
	private $transformFnExpr = '/@(([a-zA-Z0-9_]+)?\((\'[^\']*\'|"[^"]*"|[^\)])*\))/';
	private $defaultOptions = [
		'projectTitle'        => '',
		'levels'              => [],
		'rootLevels'          => [],
		'browseLevel'         => '',
		'currentLevelDeep'    => 0,

		'alias'               => 'RT',
		'sections'            => [],
		'pathToRoot'          => null,
		'pathToWebRoot'       => '/',
		'pathToLevel'         => './',
		'pathToWebLevel'      => '',
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
		$content = null;

		if( $this->isFile( $file ) ){
			//error_reporting( E_ALL ^ E_NOTICE ^ E_WARNING );
			$content = file_get_contents( $this->path( $file ) );
			//error_reporting( E_ALL );
		}
		else {
			$this->errors[] = 'FILE Not Found: ' . $file;
			
		}

		return $content;
	}
	public function isFile( $file ){
		return file_exists( $this->path( $file ) );
	}

	public function renderApp(){
		$appContent = $this->render( $this->templateFileName );

		if( $this->logFile ){
			$errors = $this->file( $this->logFile );

			if( $errors )
				$this->errors[] = $this->file( $this->logFile );
		}

		if( $this->log && count( $this->errors ) )
			$appContent .= '<pre style="position: fixed; z-index: 999999;top: 0;left: 0;right: 0;font-weight: bold;font-size: 20px;line-height: 38px;padding: 30px 5px 30px 50px;margin: 0;background: #ffdada;border-bottom: 1px solid #f19898;box-shadow: 0 5px 40px #652424;">' .
				join( "\n----------\n", $this->errors )
				. '</pre>';

		//var_dump($this);

		return $appContent;
	}

	public function renderLevel( $deep = null ){
		if( $deep === null ){
			$this->currentLevelDeep++;
			$deep = $this->currentLevelDeep;

			//var_dump( "---deep: $deep" );
		}
		$level = $this->levelInfo( $deep );
		$this->level = $level;

		//var_dump( $level );

		if( $level->name == $this->lastLevel ){
			if( $this->browseLevel )
				return $this->generate( 'browse-folder', $level->path );

			else if( $level->isPageExists )
				return $this->render( $level->pagePath );

			else{
				return $this->renderPage( 'rtemplater/browse', [ 'error'=> 404, 'errorPath'=>$level->pagePath ]);
			}
		}

		else if( $level->isNextTemplateExists )
			return $this->render( $level->nextTemplatePath );

		else
			return $this->renderPage( 'rtemplater/browse', [ 'error'=> 403, 'errorPath'=>$level->pagePath ]);

		return null;
	}

	public function levelInfo( $deep ){
		$level                          = new stdClass();
		$level->deep                    = $deep;
		$level->name                    = $this->levels[ $level->deep - 1 ];
		$level->path                    = join( array_slice( $this->levels, 0, $level->deep ), '/' );
		$level->pagePath                = $level->path . $this->fileExtension;
		$level->isPageExists            = $this->isFile( $level->pagePath );
		$level->nextTemplatePath        = $level->path . '/' . $this->templateFileName;
		$level->isNextTemplateExists    = $this->isFile( $level->nextTemplatePath );
		return $level;
	}

	public function renderPage( $path = null, $args = [] ){
		return $this->render( ( $path === null ? $this->level->path : $path ) . $this->fileExtension, $args );
	}

	public function render( $file, $args = [] ){
		return $this->renderHTML( $this->file( $file ), $args );
	}

	public function renderHTML( $text, $args = [] ){		
		$text = $this->transformToPHP( $text );
		
		if( is_string( $args ) )
			$args = [ 'content' => $args ];

		ob_start();
		extract( $args );
		global ${$this->alias};
		eval( ' ?>' . $text . '<?php ' );
		$text = ob_get_contents();
		ob_end_clean();

		if(
			preg_match( $this->transformFnExpr, $text ) ||
			preg_match( $this->transformCustomExpr, $text ) ||
			preg_match( $this->transformVarExpr, $text )
		)
			$text = $this->renderHTML( $text, $args );

		return $text;
	}

	public function transformToPHP( $text ){
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
				if( !$matches[ 2 ] ){
					$matches[ 2 ] = 'renderComponent';
					$matches[ 1 ] = $matches[ 2 ] . $matches[ 1 ];
				}

				$isClassMethod = method_exists( $this, $matches[ 2 ] );
				return '<?php echo '
					. ( $isClassMethod ? '$this->' : '' )
					. $matches[ 1 ]
					. ';?>';
			},
			$text
		);

		//var_dump( '--------------------------------------------------------------'  );
		//var_dump( 'transformToPHP result: ' . $text );

		return $text;
	}

	public function renderComponent( $name, $args = [] ){
		return $this->render( $this->pathToComponent . $name . $this->fileExtension, $args );
	}

	public function chunk( $name, $data = null ){
		if( $data != null )
			$this->chunks[ $name ] = $data;
		else if( isset( $this->chunks[ $name ] ) )
			return $this->chunks[ $name ];

		return '';
	}

	public function scanFolder( $path = '' ){
		$directory = $path == '' ? '' : $path . '/';
		$files = glob( $this->path( $directory . '[^_]*.php' ) );
		$folders = array_filter( glob( $this->path( $directory .'[^_]*' ) ), 'is_dir' );

		$resources = [];
		foreach( $files as $file ){
			preg_match( "/(.+\/)?(.+).php/", $file, $matches );
			$name = $matches[ 2 ];
			$resources[] = [
				'name'   => $name,
				'type'   => 'page',
				'title'  => strtoupper( str_replace( '_', ' ', $name ) ),
				'path'   => $this->pathToLevel . $directory . $name . $this->fileExtension,
				'url'    => $this->pathToWebRoot . $this->pathToWebLevel . $directory . $name
			];
		}

		foreach( $folders as $folder ){
			preg_match( "/(.+\/)?(.+)/", $folder, $matches );
			$name = $matches[ 2 ];
			$folderPath = $directory . $name;
			$folderAccess = $path == '' ? in_array( $name, $this->rootLevels ) : true;

			if( $folderAccess ) {
				$folderResources = $this->scanFolder( $folderPath );

				if( count( $folderResources ) ){
					$resources[] = [
						'name'   => $name,
						'type'   => 'folder',
						'title'  => strtoupper( str_replace( '_', ' ', $name ) ),
						'path'   => $this->pathToLevel . $folderPath,
						'url'    => $this->pathToWebRoot . $this->pathToWebLevel . $directory . $name . '/'
					];
					$resources = array_merge( $resources, $folderResources );
				}
			}
		}

		return $resources;
	}

	public function url( $name = '', $type = 'page', $section = null ){
		return '';
	}


	public function generate( $type = '', ...$args ){
		$content = '';

		switch( $type ){

			case 'browse-folder':
				$path = isset( $args[ 0 ] ) ? $args[ 0 ] : '';
				$content = $this->renderPage( 'rtemplater/browse', [ '_PATH'=>$path ] );
				break;

			case 'random-rgb-color':
				$content = [ rand( 0, 150 ), rand( 0, 150 ), rand( 0, 150 ) ];
				break;

			case 'random-bg-color':
				$content = 'background-color: rgb( ' . implode( ', ', $this->generate( 'random-rgb-color' ) ) . ' );';
				break;
		}


		return $content;
	}

}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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