<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Reference;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceDeserializer implements DispatchableDeserializer {

	/**
	 * @var Deserializer
	 */
	private $snaksDeserializer;

	/**
	 * @param Deserializer $snaksDeserializer
	 */
	public function __construct( Deserializer $snaksDeserializer ) {
		$this->snaksDeserializer = $snaksDeserializer;
	}

	/**
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return bool
	 */
	public function isDeserializerFor( $serialization ) {
		return $this->isValidSerialization( $serialization );
	}

	private function isValidSerialization( $serialization ) {
		return is_array( $serialization ) && array_key_exists( 'snaks', $serialization );
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param mixed $serialization
	 *
	 * @return object
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		$reference = $this->getDeserialized( $serialization );

		return $reference;
	}

	private function getDeserialized( array $serialization ) {
		return new Reference(
			$this->deserializeSnaks( $serialization )
		);
	}

	private function deserializeSnaks( array $serialization ) {
		$snaks = $this->snaksDeserializer->deserialize( $serialization['snaks'] );

		if( array_key_exists( 'snaks-order', $serialization ) ) {
			$this->assertSnaksOrderIsArray( $serialization );

			$snaks->orderByProperty( $serialization['snaks-order'] );
		}

		return $snaks;
	}

	private function assertCanDeserialize( $serialization ) {
		if ( !$this->isValidSerialization( $serialization ) ) {
			throw new DeserializationException( 'The serialization is invalid!' );
		}
	}

	private function assertSnaksOrderIsArray( array $serialization ) {
		if ( !is_array( $serialization['snaks-order'] ) ) {
			throw new InvalidAttributeException(
				'snaks-order',
				$serialization['snaks-order'],
				"snaks-order attribute is not a valid array"
			);
		}
	}

}
