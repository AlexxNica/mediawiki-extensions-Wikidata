<?php

namespace WikibaseQuality\ConstraintReport\ConstraintCheck\Helper;

/**
 * Class for helper functions for the connection checkers.
 *
 * @package WikibaseQuality\ConstraintReport\ConstraintCheck\Helper
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ConnectionCheckerHelper {

	/**
	 * Checks if there is a statement with a claim using the given property.
	 *
	 * @param StatementList $statementList
	 * @param string $propertyIdSerialization
	 *
	 * @return boolean
	 */
	public function hasProperty( $statementList, $propertyIdSerialization ) {
		foreach ( $statementList as $statement ) {
			if ( $statement->getPropertyId()->getSerialization() === $propertyIdSerialization ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if there is a statement with a claim using the given property and having one of the given items as its value.
	 *
	 * @param StatementList $statementList
	 * @param string $propertyIdSerialization
	 * @param string|array $itemIdSerializationOrArray
	 *
	 * @return boolean
	 */
	public function hasClaim( $statementList, $propertyIdSerialization, $itemIdSerializationOrArray ) {
		foreach ( $statementList as $statement ) {
			if ( $statement->getPropertyId()->getSerialization() === $propertyIdSerialization ) {
				if ( is_string( $itemIdSerializationOrArray ) ) { // string
					$itemIdSerializationArray = array ( $itemIdSerializationOrArray );
				} else { // array
					$itemIdSerializationArray = $itemIdSerializationOrArray;
				}
				if ( $this->arrayHasClaim( $statement, $itemIdSerializationArray ) ) {
					return true;
				}
			}
		}
		return false;
	}

	private function arrayHasClaim( $statement, $itemIdSerializationArray ) {
		$mainSnak = $statement->getMainSnak();
		if ( $mainSnak->getType() !== 'value' || $mainSnak->getDataValue()->getType() !== 'wikibase-entityid' ) {
			return false;
		}

		foreach ( $itemIdSerializationArray as $itemIdSerialization ) {
			if ( $mainSnak->getDataValue()->getEntityId()->getSerialization() === $itemIdSerialization ) {
				return true;
			}
		}
		return false;
	}
}