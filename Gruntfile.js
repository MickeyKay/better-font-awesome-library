/*jslint node: true */
"use strict";

module.exports = function( grunt ) {

	// Load all tasks.
	require('load-grunt-tasks')(grunt, {scope: 'devDependencies'});

	// Grab package as variable for later use/
	var pkg = grunt.file.readJSON( 'package.json' );

	// Project configuration
	grunt.initConfig( {
		pkg: pkg,
		copy: {
			fontawesome: {
				cwd: 'node_modules/',
				src:  [
					'fontawesome-iconpicker/dist/**'
				],
				dest: 'lib/',
				expand: true,
			}
		}
	} );

	grunt.registerTask( 'default', [
		'copy'
	] );

	grunt.util.linefeed = '\n';
};


