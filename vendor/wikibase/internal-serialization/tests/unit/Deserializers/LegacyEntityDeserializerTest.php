<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Tests\Integration\Wikibase\InternalSerialization\TestFactoryBuilder;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyEntityDeserializer
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyEntityDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$this->deserializer = TestFactoryBuilder::newLegacyDeserializerFactory( $this )->newEntityDeserializer();
	}

	public function testGivenPropertySerialization_propertyIsReturned() {
		$serialization = array(
			'entity' => 'P42',
			'datatype' => 'foo',
		);

		$deserialized = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Property', $deserialized );
	}

	public function testGivenItemSerialization_itemIsReturned() {
		$serialization = array(
			'entity' => 'Q42',
		);

		$deserialized = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Item', $deserialized );
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_exceptionIsThrown( $serialization ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->deserializer->deserialize( $serialization );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( 5 ),
			array( array( 'entity' => 'P42', 'datatype' => null ) ),
		);
	}

}
