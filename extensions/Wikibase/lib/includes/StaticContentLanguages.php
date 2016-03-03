<?php

namespace Wikibase\Lib;

/**
 * Provide languages supported as content languages based on a list
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class StaticContentLanguages implements ContentLanguages {

	/**
	 * @var string[] Array of language codes
	 */
	private $languageCodes = null;

	/**
	 * @param string[] $languageCodes
	 */
	public function __construct( array $languageCodes ) {
		$this->languageCodes = $languageCodes;
	}

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages() {
		return $this->languageCodes;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return bool
	 */
	public function hasLanguage( $languageCode ) {
		return in_array( $languageCode, $this->languageCodes );
	}

}
