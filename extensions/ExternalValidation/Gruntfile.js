/* jshint node: true, strict: false */
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-jscs' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );

	grunt.initConfig( {
		jshint: {
			options: {
				jshintrc: true
			},
			all: '.'
		},
		jscs: {
			all: '.'
		},
		banana: {
			options: {
				disallowDuplicateTranslations: false,
				disallowUnusedTranslations: false,
				requireCompleteMessageDocumentation: false
			},
			all: 'i18n/'
		},
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**',
				'!vendor/**'
			]
		}
	} );

	grunt.registerTask( 'test', [ 'jshint', 'jscs', 'jsonlint', 'banana' ] );
};
