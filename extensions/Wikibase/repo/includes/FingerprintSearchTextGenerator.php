<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class FingerprintSearchTextGenerator {

	/**
	 * @param Fingerprint $fingerprint
	 *
	 * @return string
	 */
	public function generate( Fingerprint $fingerprint ) {
		$text = implode( "\n", $fingerprint->getLabels()->toTextArray() );

		$text .= "\n" . implode( "\n", $fingerprint->getDescriptions()->toTextArray() );

		/** @var AliasGroup $aliasGroup */
		foreach ( $fingerprint->getAliasGroups() as $aliasGroup ) {
			$text .= "\n" . implode( "\n", $aliasGroup->getAliases() );
		}

		return trim( $text, "\n" );
	}

}
