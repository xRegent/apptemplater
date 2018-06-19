////////////////////////////////////////////////////////////////////////////////////////////////////

var
	package = require('./package.json'),
	app = package.app,
	options = app.build,

	gulp = require('gulp'),
	sass = require('gulp-sass'),
	minifyCSS = require('gulp-csso'),
	concat = require('gulp-concat'),
	sourcemaps = require('gulp-sourcemaps'),
	autoprefixer = require('gulp-autoprefixer'),
	rename = require('gulp-rename'),
	uglify = require('gulp-uglify'),
	//jsonSass = require('json-sass'),
	through = require('through2'),
	jsToSassString = require('json-sass/lib/jsToSassString'),

	fs = require('fs'),
	mkdirp = require('mkdirp'),
	getDirName = require('path').dirname,

	stream,// =  fs.createReadStream( new Buffer('buffer') ),

	fixPath = function( path ){
		return path.replace( /^\//, '' );
	},

	devOrProd = function( name ){
		return name == "dev/production" ? ( app.debug ? 'dev' : 'production' ) : name;
	},

	deleteFiles = function( path, saveFolder ){
		if( fs.existsSync( path ) ){
			fs.readdirSync( path ).forEach(function( file, index ){
				var curPath = path + "/" + file;
				if( fs.lstatSync( curPath ).isDirectory() )
					deleteFiles( curPath );

				else
					fs.unlinkSync( curPath );
			});

			if( !saveFolder )
				fs.rmdirSync( path );
		}
	},

	writeFile = function( path, contents, cb=(e)=>{} ){
		mkdirp( getDirName(path), function (err){
			if(err)
				return cb(err);

			fs.writeFile( path, contents, cb );
		});
	},

	deleteFile = function( path, contents, cb=(e)=>{} ){
		mkdirp( getDirName(path), function (err){
			if(err)
				return cb(err);

			if( fs.existsSync( path ) )
				fs.unlinkSync( path, contents, cb );
		});
	},

	process = function( element, cb ){
		//console.log( 'Gulp Process:', element );


		let
			file,
			log_path = fixPath( element.log || '' ),
			//log_dir = log_path.replace( /\/[^\/]*$/, '' ),
			
			jsLog_path = fixPath( element.jsLog || '' ),
			//jsLog_dir = jsLog_path.replace( /\/[^\/]*$/, '' ),

			cssLog_path = fixPath( element.cssLog || '' ),
			//cssLog_dir = cssLog_path.replace( /\/[^\/]*$/, '' ),

			stopProcess = function(){
				console.log( '--- STOP GULP PROCESS ---' );
			};
		
		if( log_path )
			deleteFile( log_path );
		
		if( jsLog_path )
			deleteFile( jsLog_path );
		
		if( cssLog_path )
			deleteFile( cssLog_path );

		if( element.clearFolder )
			deleteFiles( fixPath( element.folder ), true );

		if( element.fs_src )
			file = fs.createReadStream( fixPath( element.fs_src ) );

		if( element.src )
			file = gulp.src( fixPath( element.src ) );

		stream = file;

		console.log( '| Gulp PROCESS fn: ', ( element.fs_src || element.src ) );

		if( element.sourcemaps )
			stream = stream.pipe( sourcemaps.init() );

		if( element.sass ){
			let _sass = sass();

			if( element.log || element.cssLog ){

				_sass = _sass.on( 'error', function( err ){
					
					if( element.log )
						writeFile( log_path, err.message );

					if( element.cssLog )
						writeFile( cssLog_path, "/*\n" + err.message + "\n*/" );

					sass.logError.call( this, err );
				});
			}

			stream = stream.pipe( _sass );
		}

		if( element.autoprefixer )
			stream = stream.pipe(autoprefixer( element.autoprefixer ));

		if( element.jsonSass )
			stream = stream.pipe(through(function( chunk, enc, callback ){
				try{
					let
						defaultOptions = {
							prefix: '',
							suffix: ';'
						}
						options = typeof element.jsonSass == 'object' ? element.jsonSass : defaultOptions,
						jsValue = JSON.parse( chunk ),
						sassString = jsToSassString( jsValue );
					
					sassString = ( options.prefix || defaultOptions.prefix ) +
						sassString +
						( options.suffix || defaultOptions.suffix );

					this.push( sassString );
					callback();
				}
				catch( err ){
					//console.log( 'ERROR in JSON-TO-SASS: ', err );

					var message = err.message + "\r\n" +
						JSON.stringify( element )
							.replace( /,/g, ",\r\n" )
							.replace( /\}/g, "\r\n}" )
							.replace( /\{/g, "{\r\n" );

					console.log( 'ERROR in JSON-TO-SASS: ', message );

					if( element.log )
						writeFile( log_path, message );

					if( element.jsLog )
						writeFile( jsLog_path, 'alert(`' + message + '`);' );

					//this.emit('error' , err);
					//this.emit('end');

					this.emit('end');
				}
			}));

		if( element.rename )
			stream = stream.pipe(rename( element.rename ));

		if( element.minifyCSS )
			stream = stream.pipe(minifyCSS( element.minifyCSS ));

		if( element.uglify )
			stream = stream.pipe(uglify());

		if( element.sourcemaps )
			stream = stream.pipe( sourcemaps.write() );

		if( element.fs_result )
			stream = stream.pipe(through(function( chunk, enc, callback ){
				writeFile( fixPath( element.fs_result ), chunk );
				callback();
			}));

		else if( element.folder )
			stream = stream.pipe(gulp.dest( fixPath( element.folder ) ));

		return stream;
	};
////////////////////////////////////////////////////////////////////////////////////////////////////

Object.keys( options.tasks ).forEach(function( taskName ){
	let task = options.tasks[ taskName ];
	
	gulp.task( taskName, function( cb ){
		console.log( '|   Task: ' + taskName + '. Dubug: ' + !!app.debug );

		task.forEach(function( element ){

			if( typeof element == 'string' )
				gulp.start( devOrProd( element ) );

			else if( typeof element == 'object' ){
				if( !( 'folder' in element ) )
					element.folder = options.folder;

				process( element );
			}
		});

		cb();
	});
});

////////////////////////////////////////////////////////////////////////////////////////////////////

if( options.watch ){
	let watchFn = function( cb ){
		Object.keys( options.watch ).forEach(function( fileName ){
			let
				tasks = options.watch[ fileName ].map(function( taskName ){
					taskName = devOrProd( taskName );
					if(
						( app.debug && ~taskName.indexOf( 'production' ) ) ||
						( !app.debug && ~taskName.indexOf( 'dev' ) )
					)
						return '';

					//gulp.start( taskName );
					return taskName;
				}),
				path = fixPath( fileName ),
				watcher = gulp.watch( path, tasks );

			console.log( '| Gulp start watch:', path );

			gulp.start( tasks );

			watcher.on( 'change', function( event ){
				console.log( '|   ' + event.type + ': ' + event.path + ' | Start:', tasks );
			});
		});

		cb();
	};

	gulp.task( 'watch', watchFn );
	gulp.task( package.name, watchFn );
}