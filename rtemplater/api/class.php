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

		if( $this->pathToRootFolder === null )
			$this->pathToRootFolder = $_SERVER[ 'DOCUMENT_ROOT' ] . '/';

		$this->levels = $this->levelsInfo( $this->levelList );
		$this->levelList = $this->levels->list;

		rTemplater::$ALIAS = $this->alias;



		//echo '<pre>$GET  - '; print_r( $_GET ); echo '</pre>';
		//echo '<pre>LEVEL - '; print_r( $this->levels ); echo '</pre>';
		//var_dump($this);
	}

	private $_args          = [];
	private $levelsCache    = [];
	private $defaultOptions = [
		'transformCustomExpr'     => '/\{\{(.+)\}\}/',
		'transformVarExpr'        => '/@\$([\w->]+)(\([^\)]*\))?/',
		'transformFnExpr'         => '/@(([\w->]+)?\((\'[^\']*\'|"[^"]*"|[^\)])*\))/',

		'alias'                   => 'RT',
		'name'                    => '',
		'levelList'               => [],
		'rootLevels'              => [],

		'pathToRootFolder'        => null,
		'pathToComponentFolder'   => 'component',

		'pathToWebRoot'           => '/',
		'pathToWebComponent'      => 'component/',

		'templateFileName'        => '__template.php',
		'fileExtension'           => '.php',

		'chunks'                  => [],

		'appLog'                  => [],
		'printLog'                => false,

		'errorLog'                => [],
		'printErrorLog'           => true,

		'printFileLog'            => ''
	];


	public function path( $file ){
		return $this->pathToRootFolder . '/' . $file;
	}
	public function file( $file ){
		$this->log( "file: $file" );

		$content = null;

		if( $this->isFile( $file ) ){
			//error_reporting( E_ALL ^ E_NOTICE ^ E_WARNING );
			$content = file_get_contents( $this->path( $file ) );
			//error_reporting( E_ALL );
		}
		else {
			$this->errorLog[] = 'FILE Not Found: ' . $file;
			
		}

		return $content;
	}
	public function isFile( $file ){
		$this->log( "isFile: $file" );
		return file_exists( $this->path( $file ) );
	}

	public function renderApp(){
		$this->log( "renderApp" );

		//var_dump($this);

		return $this->renderLevel() . $this->renderLogs();
	}

	public function renderLevel( $levelList = null ){

		if( $levelList === null )
			$levelList = $this->levelList;		

		$levels = $this->levelsInfo( $levelList );

		if( $levels->error )
			return renderError( $levels->error );

		$level = $levels->current;

		if( $level->error )
			return renderError( $level->error );

		if( $this->levelList == $levels->list ){
			$this->levels = $levels;
			$this->level = $level;
		}

		//var_dump( $levels );
		//var_dump( $level );

		if( $level->canTemplate ){
			$this->log( "renderLevel($level->deep): template: $level->templatePath" );

			$level->canTemplate = false;
			return $this->render( $level->templatePath );
		}
		else if( $level->isLastLevel ){

			if( $levels->isBrowse )
				return $this->generate( 'browse-folder', $level->path );

			else if( $level->isPageExists ){
				$this->log( "renderLevel($level->deep): page: $level->pagePath" );

				return $this->render( $level->pagePath );
			}
			else
				return $this->renderError( "404 - PAGE Not Found: $level->path" );
		}
		else if( $level->isNextTemplateExists || $level->isNextPageExists ){

			if( $level->isNextTemplateExists )
				$this->log( "renderLevel($level->deep): detect next template: $level->nextTemplatePath" );

			else if( $level->isNextPageExists )
				$this->log( "renderLevel($level->deep): detect next page: $level->nextPagePath" );

			$levels->deep++;
			return $this->renderLevel( $levels->list );
		}

		else if( $levels->count - $levels->deep <= 2 && !$level->isNextPageExists )
			return $this->renderError( "404 - PAGE Not Found: $level->nextPath" );

		else
			return $this->renderError( "404 - Not Found logic in path: $level->path" );
	}

	public function levelsInfo( $levelList ){
		if( is_string( $levelList ) )
			$levelList = explode( '/', $this->levelList );

		$formatedList = [];
		foreach( $levelList as $level )
			$formatedList[] = str_replace( '/', '', $level );

		$levelList = $formatedList;

		$lastLevelIndex = count( $levelList ) - 1;
		$isBrowseLastLevel = $levelList[ $lastLevelIndex ] == '';
		if( $isBrowseLastLevel )
			unset( $levelList[ $lastLevelIndex ] );

		$levelsSlug = implode( '/', $levelList );

		$this->log( "levelsInfo: $levelsSlug" );

		if( isset( $this->levelsCache[ $levelsSlug ] ) )
			$levels = $this->levelsCache[ $levelsSlug ];

		else {
			$levels            = new stdClass();
			$levels->list      = $levelList;
			$levels->slug      = $levelsSlug;
			$levels->count     = count( $levelList );
			$levels->first     = $levelList[ 0 ];
			$levels->last      = $levelList[ $levels->count - 1 ];
			$levels->isBrowse  = $isBrowseLastLevel;
			$levels->eq        = [];
			$levels->deep      = 0;
			$levels->error     = '';

			if( $levels->count < 1 )
				$levels->error = "Number of levels is less than 1";

			$this->levelsCache[ $levelsSlug ] = $levels;
		}


		if( isset( $levels->eq[ $levels->deep ] ) )
			$level = $levels->eq[ $levels->deep ];

		else {
			$level = new stdClass();
			$level->deep = $deep = $levels->deep;
			$level->error = '';

			if( $deep > $levels->count )
				$level->error = "Deep $deep to much";

			if( $levels->error )
				$level->error = $levels->error;

			if( $level->error )
				return $level;

			$level->levelsSlug              = $levels->slug;
			$level->name                    = $levels->list[ $deep ];
			$level->path                    = join( array_slice( $levels->list, 0, $deep + 1 ), '/' );

			$level->pagePath                = $level->path . $this->fileExtension;
			$level->isPageExists            = $this->isFile( $level->pagePath );
			
			$level->templatePath            = preg_replace( '/(\/)?[^\/]+$/', "$1" . $this->templateFileName, $level->pagePath );
			$level->isTemplateExists        = $this->isFile( $level->templatePath );

			$level->prev                    = $deep > 0 && isset( $levels->list[ $deep - 1 ] ) ? $levels->list[ $deep - 1 ] : '';
			$level->next                    = isset( $levels->list[ $deep + 1 ] ) ? $levels->list[ $deep + 1 ] : '';
			$level->nextPath                = $level->next ? $level->path . '/' . $level->next : '';

			$level->nextTemplatePath        = $level->path . '/' . $this->templateFileName;
			$level->isNextTemplateExists    = $this->isFile( $level->nextTemplatePath );

			$level->nextPagePath            = $level->next ? $level->path . '/' . $level->next . $this->fileExtension : '';
			$level->isNextPageExists        = $level->next ? $this->isFile( $level->nextPagePath ) : false;

			$level->isLastLevel             = $deep == $levels->count - 1;
			$level->canTemplate             = $level->isTemplateExists;

			$levels->eq[ $levels->deep ] = $level;
		}

		$levels->current = $level;

		return $levels;
	}

	public function renderPage( $path = null, $args = [] ){
		$path = ( $path === null ? $this->level->path : $path );
		$this->log( "renderPage: $path" );
		return $this->render( $path . $this->fileExtension, $args );
	}

	public function render( $file, $args = [] ){
		$this->log( "render: $file" );
		return $this->renderHTML( $this->file( $file ), $args );
	}

	public function renderHTML( $text, $args = [] ){
		//$this->log( "renderHTML: " . str_replace( "\n", '', substr( $text, 0, 30 ) ) );
		$this->log( "renderHTML" );

		$text = $this->transformToPHP( $text );
		
		if( is_string( $args ) )
			$args = [ 'content' => $args ];

		ob_start();
		global ${$this->alias};
		$this->_args = $args;
		extract( $args );
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

	public function renderError( $args = [] ){
		$this->log( "renderError" );

		if( is_string( $args ) )
			$args = [ 'message'=>$args ];

		return $this->renderPage( 'rtemplater/browse', $args );
	}

	public function renderLogs(){
		$this->log( "renderLogs" );

		$content = '';
		$popUpErrors = $this->printErrorLog ? $this->errorLog : [];

		if( $this->printFileLog && $this->isFile( $this->printFileLog ) )
			$popUpErrors[] = $this->file( $this->printFileLog );

		if( count( $popUpErrors ) )
			$content .= '<pre style="position: fixed; z-index: 999999;top: 0;left: 0;right: 0;font-weight: bold;font-size: 20px;line-height: 38px;padding: 30px 5px 30px 50px;margin: 0;background: #ffdada;border-bottom: 1px solid #f19898;box-shadow: 0 5px 40px #652424;">' .
				join( "\n----------\n", $popUpErrors )
				. '</pre>';

		if( $this->printLog && count( $this->appLog ) ){
			$content .= "<pre style='
				font-weight: bold;
				font-size: 20px;
				line-height: 38px;
				padding: 0 5px 0 50px;
				margin: 40px 0 0 0;
				background: #daffff;
				border-top: 1px solid #c3cff9;
				box-shadow: 0 5px 40px #6ee8db;'>";

			ob_start();
			print_r( $this->appLog );
			$content .= preg_replace( '/\n\s*|^Array\n\(|\)$/', "\n", ob_get_contents() );
			ob_end_clean();

			$content .= '</pre>';
		}

		return $content;
	}

	public function transformToPHP( $text ){
		$this->log( "transformToPHP" );

		//echo "transformToPHP: $text" ;

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
					$result = $this->{$matches[ 1 ]};
				else
					$result = '<?php echo ' . 
						'isset( $' . $matches[ 1 ] . ' ) ? $' . $matches[ 1 ] . ' : ' .
						( isset( $matches[ 2 ] ) ? $matches[ 2 ] : '""' ) .
					';?>';

				//var_dump($result,$matches);

				return $result;
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
		$this->log( "renderComponent: $name" );
		return $this->render( $this->pathToComponentFolder . '/' . $name . $this->fileExtension, $args );
	}

	public function addError( $err ){
		$this->log( "addError: $err" );
		$this->errorLog[] = $err;
	}

	public function log( ...$args ){
		if( count( $args ) == 1 )
			$this->appLog[] = $args[ 0 ];
		else
			$this->appLog[] = $args;
	}

	public function chunk( $name, $data = null ){
		$this->log( "chunk: $name" );

		if( $data != null )
			$this->chunks[ $name ] = $data;
		else if( isset( $this->chunks[ $name ] ) )
			return $this->chunks[ $name ];

		return '';
	}

	public function scanFolder( $path = '' ){
		$this->log( "scanFolder: $path" );

		$directory = $path == '' ? '' : $path . '/';
		$files = glob( $this->path( $directory . '[^_]*.php' ) );
		$folders = array_filter( glob( $this->path( $directory .'[^_]*' ) ), 'is_dir' );

		$resources = [];
		foreach( $files as $file ){
			preg_match( "/(.+\/)?(.+)" . $this->fileExtension . "/", $file, $matches );
			$name = $matches[ 2 ];
			$resources[] = [
				'name'   => $name,
				'type'   => 'page',
				'title'  => strtoupper( str_replace( '_', ' ', $name ) ),
				'path'   => $directory . $name . $this->fileExtension,
				'url'    => $this->pathToWebRoot . $directory . $name
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
						'path'   => $folderPath,
						'url'    => $this->pathToWebRoot . $directory . $name . '/'
					];
					$resources = array_merge( $resources, $folderResources );
				}
			}
		}

		return $resources;
	}

	public function args( $key, $args = null ){
		$args = $args === null ? $this->_args : $args;
		return isset( $args[ $key ] ) ? $args[ $key ] : ( isset( $_GET[ $key ] ) ? $_GET[ $key ] : '' );
	}

	public function url( $name = '', $type = 'page', $section = null ){
		return '';
	}


	public function generate( $type = '', ...$args ){
		$this->log( "generate: $type" );

		$content = '';

		switch( $type ){

			case 'scripts':
				$dev = $this->options[ 'debug' ];
				$scripts = $this->options[ 'scripts' ][ $dev ? 'dev' : 'production' ];
				$content = PHP_EOL;
				foreach( $scripts as $link ){
					$content .= "\t" .
						'<script src="' .
						$link .
						( $dev && !preg_match( '/^\w+:\/\//', $link ) ? '?v=' . $this->generate( 'timestamp' ) : '' ) .
						'"></script>' .
						PHP_EOL;
				}
				break;

			case 'styles':
				$dev = $this->options[ 'debug' ];
				$styles = $this->options[ 'styles' ][ $dev ? 'dev' : 'production' ];
				$content = PHP_EOL;
				foreach( $styles as $link ){
					$content .= "\t" .
						'<link rel="stylesheet" href="' .
						$link .
						( $dev && !preg_match( '/^\w+:\/\//', $link ) ? '?v=' . $this->generate( 'timestamp' ) : '' ) .
						'" />' .
						PHP_EOL;
				}
				break;

			case 'timestamp':
				$content = number_format( microtime(true) * 1000, 0, '.', '' );
				break;

			case 'folder-slug':
				$content = implode( $this->levelList, '-' );
				break;

			case 'browse-folder':
				$path = isset( $args[ 0 ] ) ? $args[ 0 ] : '';
				$content = $this->renderPage( 'rtemplater/browse', [ 'path'=>$path ] );
				break;

			case 'page-title':
				$content = strtoupper( $this->name );
				if( $this->levels->last != 'browse' ){

					if( $this->levels->first && $this->levels->first != 'rtemplater' )
						$content = ( $content ? $content . ' ' : '' ) . strtoupper( $this->levels->first );

					if( $this->levels->last && $this->levels->last != $this->levels->first )
						$content = ( $content ? $content . ': ' : '' ) . $this->levels->last;
				}
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