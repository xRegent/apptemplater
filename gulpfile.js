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
	fs = require('fs'),

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

	process = function( element ){
		//console.log( 'Gulp Process:', element );

		let file;

		if( element.clearFolder )
			deleteFiles( fixPath( element.folder ), true );

		if( element.src )
			file = gulp.src( fixPath( element.src ) );

		if( element.sourcemaps )
			file = file.pipe( sourcemaps.init() );

		if( element.sass ){
			let _sass = sass();

			if( element.log || element.cssLog )
				_sass = _sass.on( 'error', function( err ){
					
					if( element.log ){
						let
							path = fixPath( element.log ),
							dir = path.replace( /\/[^\/]*$/, '' );

						if( !fs.existsSync( dir ) )
							fs.mkdirSync( dir );

						fs.writeFile( path, err.message, (e)=>{} );
					}

					if( element.cssLog ){
						let
							path = fixPath( element.cssLog ),
							dir = path.replace( /\/[^\/]*$/, '' );

						if( !fs.existsSync( dir ) )
							fs.mkdirSync( dir );

						fs.writeFile( path, "/*\n" + err.message + "\n*/", (e)=>{} );
					}

					sass.logError.call( this, err );
				});

			file = file.pipe( _sass );
		}

		if( element.autoprefixer )
			file = file.pipe(autoprefixer( element.autoprefixer ));

		if( element.rename )
			file = file.pipe(rename( element.rename ));

		if( element.minifyCSS )
			file = file.pipe(minifyCSS( element.minifyCSS ));

		if( element.uglify )
			file = file.pipe(uglify());

		if( element.sourcemaps )
			file = file.pipe( sourcemaps.write() );

		if( element.folder )
			file = file.pipe(gulp.dest( fixPath( element.folder ) ));

		return file;
	};

Object.keys( options.tasks ).forEach(function( taskName ){
	let task = options.tasks[ taskName ];
	gulp.task( taskName, function(){
		console.log( '|   Task: ' + taskName + '. Dubug: ' + !!app.debug );

		return task.map(function( element ){
			if( typeof element == 'string' )
				return gulp.start( devOrProd( element ) );

			else if( typeof element == 'object' ){
				if( !( 'folder' in element ) )
					element.folder = options.folder;

				return process( element );
			}
		});
	});
});

if( options.watch ){
	let watchFn = function(){
		Object.keys( options.watch ).forEach(function( fileName ){
			let
				tasks = options.watch[ fileName ].map(function( taskName ){
					taskName = devOrProd( taskName );
					if(
						( app.debug && ~taskName.indexOf( 'production' ) ) ||
						( !app.debug && ~taskName.indexOf( 'dev' ) )
					)
						return '';

					gulp.start( taskName );
					return taskName;
				}),
				watcher = gulp.watch( fixPath( fileName ), tasks );

			watcher.on( 'change', function( event ){
				console.log( '|   ' + event.type + ': ' + event.path );
			});
		});
	};

	gulp.task( 'watch', watchFn );
	gulp.task( package.name, watchFn );
}
	


return;
////////////////////////////////////////////////////////////////////////////////////////////////////
gulp.task( 'css-dev', function(){
	fs.writeFile( './files/build-dev/error-log.html', '', (e)=>{} );
	return gulp.src( './files/scss/builder.scss' )
		.pipe(sourcemaps.init())
		.pipe(sass().on('error', function( err ){
			if( errorLog.html )
				fs.writeFile( './files/build-dev/error-log.html', err.message, (e)=>{} );

			if( errorLog.css )
				fs.writeFile( './files/build-dev/main.css', "/*\n" + err.message + "\n*/", (e)=>{} );

			sass.logError.call( this, err );
		}))
		.pipe(autoprefixer({
			browsers: [ 'last 2 versions' ],
			cascade: false
		}))
		.pipe(rename({
			basename: "main"
		}))
		.pipe(sourcemaps.write( '.', {
			mapFile: function( mapFilePath ){
				return mapFilePath.replace( '.css', '' );
			}
		}))
		.pipe(gulp.dest('./files/build-dev'));
});
////////////////////////////////////////////////////////////////////////////////////////////////////
gulp.task( 'css', [ 'css-dev' ], function(){
	return gulp.src( './files/build-dev/main.css' )
		.pipe(minifyCSS())
		.pipe(rename({
			basename: "main.min"
		}))
		.pipe(gulp.dest('./files/build'));
});
////////////////////////////////////////////////////////////////////////////////////////////////////
gulp.task( 'js-dev', function(){
	return gulp.src( './files/js/main.js' )
		.pipe(gulp.dest('./files/build-dev'));
});
////////////////////////////////////////////////////////////////////////////////////////////////////
gulp.task( 'js', [ 'js-dev' ], function(){
	return gulp.src( './files/build-dev/main.js' )
		.pipe(uglify())
		.pipe(rename({
			basename: "main.min"
		}))
		.pipe(gulp.dest('./files/build'));
});
////////////////////////////////////////////////////////////////////////////////////////////////////
gulp.task( 'apptemplater', [ 'css-dev' ], function(){
	var watcher = gulp.watch( './files/scss/*.scss', [ 'css-dev' ] );
	watcher.on( 'change', function( event ){
		console.log( event.type + ': ' + event.path );
	});
});
////////////////////////////////////////////////////////////////////////////////////////////////////
gulp.task( 'default', [ 'css', 'js' ] );
////////////////////////////////////////////////////////////////////////////////////////////////////
gulp.task( 'production', [ 'default' ], function(){
	deleteFolderRecursive( './files/build-dev' );
});
////////////////////////////////////////////////////////////////////////////////////////////////////