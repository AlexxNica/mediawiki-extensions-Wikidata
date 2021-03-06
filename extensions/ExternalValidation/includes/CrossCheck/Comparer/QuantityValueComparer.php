<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\QuantityValue;
use InvalidArgumentException;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class QuantityValueComparer implements DataValueComparer {

	/**
	 * @see DataValueComparer::compare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 *
	 * @throws InvalidArgumentException
	 * @return string One of the ComparisonResult::STATUS_... constants.
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		if ( !$this->canCompare( $value, $comparativeValue ) ) {
			throw new InvalidArgumentException( 'Given values can not be compared using this comparer.' );
		}

		/**
		 * @var QuantityValue $value
		 * @var QuantityValue $comparativeValue
		 */

		if ( $comparativeValue->getLowerBound()->compare( $value->getUpperBound() ) <= 0 &&
			$comparativeValue->getUpperBound()->compare( $value->getLowerBound() ) >= 0
		) {
			return ComparisonResult::STATUS_MATCH;
		}

		return ComparisonResult::STATUS_MISMATCH;
	}

	/**
	 * @see DataValueComparer::canCompare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue ) {
		return $value instanceof QuantityValue && $comparativeValue instanceof QuantityValue;
	}

}
