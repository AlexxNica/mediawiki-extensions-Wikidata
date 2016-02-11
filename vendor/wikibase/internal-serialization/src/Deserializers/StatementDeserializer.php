<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Statement\Statement;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class StatementDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $legacyDeserializer;

	/**
	 * @var DispatchableDeserializer
	 */
	private $currentDeserializer;

	public function __construct(
		Deserializer $legacyDeserializer,
		DispatchableDeserializer $currentDeserializer
	) {
		$this->legacyDeserializer = $legacyDeserializer;
		$this->currentDeserializer = $currentDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @return Statement
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Claim serialization must be an array' );
		}

		if ( $this->currentDeserializer->isDeserializerFor( $serialization ) ) {
			return $this->currentDeserializer->deserialize( $serialization );
		} elseif ( $this->isLegacySerialization( $serialization ) ) {
			return $this->legacyDeserializer->deserialize( $serialization );
		} else {
			return $this->fromUnknownSerialization( $serialization );
		}
	}

	private function isLegacySerialization( array $serialization ) {
		// This element is called 'mainsnak' in the current serialization.
		return array_key_exists( 'm', $serialization );
	}

	private function fromUnknownSerialization( array $serialization ) {
		try {
			return $this->currentDeserializer->deserialize( $serialization );
		} catch ( DeserializationException $currentEx ) {
			try {
				return $this->legacyDeserializer->deserialize( $serialization );
			} catch ( DeserializationException $legacyEx ) {
				throw new DeserializationException(
					'The provided claim serialization is neither legacy ('
					. $legacyEx->getMessage() . ') nor current ('
					. $currentEx->getMessage() . ')'
				);
			}
		}
	}

}