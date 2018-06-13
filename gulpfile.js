var
	gulp = require('gulp'),
	sass = require('gulp-sass'),
	minifyCSS = require('gulp-csso'),
	concat = require('gulp-concat'),
	sourcemaps = require('gulp-sourcemaps'),
	autoprefixer = require('gulp-autoprefixer'),
	rename = require('gulp-rename'),
	uglify = require('gulp-uglify'),
	fs = require('fs'),

	deleteFolderRecursive = function( path ){
		if ( fs.existsSync(path) ){
			fs.readdirSync(path).forEach(function(file, index){
				var curPath = path + "/" + file;
				if( fs.lstatSync(curPath).isDirectory() ){ // recurse
					deleteFolderRecursive( curPath );
				}
				else { // delete file
					fs.unlinkSync(curPath);
				}
			});
			fs.rmdirSync( path );
		}
	},

	errorToHtml = function( error ){
		var style = 'position: fixed; z-index: 999999;top: 0;left: 0;right: 0;font-weight: bold;font-size: 20px;line-height: 38px;padding: 30px 5px 30px 50px;margin: 0;background: #ffdada;border-bottom: 1px solid #f19898;box-shadow: 0 5px 40px #652424;';
		return '<pre style="' + style + '">' + ( error || 'Error!' ) + '</pre>';
	},

	errorLog = {
		html: true,
		css: false
	};

////////////////////////////////////////////////////////////////////////////////////////////////////
gulp.task( 'css-dev', function(){
	fs.writeFile( './files/build-dev/error-log.html', '', (e)=>{} );
	return gulp.src( './files/scss/builder.scss' )
		.pipe(sourcemaps.init())
		.pipe(sass().on('error', function( err ){
			if( errorLog.html )
				fs.writeFile( './files/build-dev/error-log.html', errorToHtml( err.message ), (e)=>{} );

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