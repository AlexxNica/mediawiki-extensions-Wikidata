<?php

namespace Wikibase\Repo\Specials;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Summary;

/**
 * Special page for setting the description of a Wikibase entity.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetDescription extends SpecialModifyTerm {

	/**
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetDescription' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialSetEntity::getPostedValue()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'description' );
	}

	/**
	 * @see SpecialSetEntity::getValue()
	 *
	 * @since 0.4
	 *
	 * @param Fingerprint $fingerprint
	 * @param string $languageCode
	 *
	 * @return string
	 */
	protected function getValue( Fingerprint $fingerprint, $languageCode ) {
		return $fingerprint->hasDescription( $languageCode ) ?
			$fingerprint->getDescription( $languageCode )->getText()
			: '';
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( EntityDocument $entity, $languageCode, $value ) {
		$value = $value === '' ? null : $value;
		$summary = new Summary( 'wbsetdescription' );

		if ( $value === null ) {
			$changeOp = $this->termChangeOpFactory->newRemoveDescriptionOp( $languageCode );
		} else {
			$changeOp = $this->termChangeOpFactory->newSetDescriptionOp( $languageCode, $value );
		}

		$this->applyChangeOp( $changeOp, $entity, $summary );

		return $summary;
	}

}
