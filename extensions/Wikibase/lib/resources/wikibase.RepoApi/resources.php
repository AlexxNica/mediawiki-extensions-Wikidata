<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match(
		'+' . preg_quote( DIRECTORY_SEPARATOR, '+' ) . '((?:vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR, '+' ) . '.*)$+',
		__DIR__,
		$remoteExtPathParts
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . DIRECTORY_SEPARATOR . $remoteExtPathParts[1],
	);

	$modules = array(

		'wikibase.api.RepoApi' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.RepoApi.js',
			),
			'dependencies' => array(
				'json',
				'wikibase.api.__namespace',
			),
		),

		'wikibase.api.RepoApiError' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.RepoApiError.js',
			),
			'messages' => array(
				'wikibase-error-unexpected',
				'wikibase-error-save-generic',
				'wikibase-error-remove-generic',
				'wikibase-error-save-timeout',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-no-external-page',
				'wikibase-error-ui-edit-conflict',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.api.__namespace',
			),
		),

	);

	return $modules;
} );