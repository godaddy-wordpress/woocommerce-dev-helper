/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	var config = {};

	// Makepot
	config.addtextdomain = {
		addtextdomain: {
			options: {
				updateDomains: true
			},
			files: [{
				expand: true,
				cwd: '',
				src: [ '*.php', '**/*.php', '!node_modules/**' ],
				//dest: 'build/'
			}],
			// files:  {
			// 	// expand: true,
			// 	// cwd: process.cwd(),
			// 	src: [
			// 		'*.php',
			// 		'**/*.php',
			// 		'!node_modules/**'
			// 	]
			// }
		}
	};

	return config;
};
