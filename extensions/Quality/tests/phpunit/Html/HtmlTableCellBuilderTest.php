<?php


namespace WikibaseQuality\ExternalValidation\Tests\Html;

use WikibaseQuality\Html\HtmlTableCellBuilder;


/**
 * @covers WikibaseQuality\Html\HtmlTableCellBuilder
 *
 * @group WikibaseQuality
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class HtmlTableCellBuilderTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider constructDataProvider
	 */
	public function testConstruct( $content, $attributes, $expectedException = null ) {
		$this->setExpectedException( $expectedException );
		$cell = new HtmlTableCellBuilder( $content, $attributes );

		$this->assertEquals( $content, $cell->getContent() );
		$this->assertEquals( $attributes, $cell->getAttributes() );
	}

	/**
	 * Test cases for testConstruct
	 *
	 * @return array
	 */
	public function constructDataProvider() {
		return array(
			array(
				'foobar',
				array()
			),
			array(
				'foobar',
				array(
					'rowspan' => 2,
					'colspan' => 2
				)
			),
			array(
				42,
				array(),
				'InvalidArgumentException'
			)
		);
	}

	/**
	 * @dataProvider toHtmlDataProvider
	 */
	public function testToHtml( $content, $attributes, $expectedHtml ) {
		$cell = new HtmlTableCellBuilder( $content, $attributes );
		$actualHtml = $cell->toHtml();

		$this->assertEquals( $expectedHtml, $actualHtml );
	}

	/**
	 * Test cases for testToHtml
	 *
	 * @return array
	 */
	public function toHtmlDataProvider() {
		return array(
			array(
				'foobar',
				array(),
				'<td>foobar</td>'
			),
			array(
				'foobar',
				array(
					'rowspan' => 2,
					'colspan' => 3
				),
				'<td rowspan="2" colspan="3">foobar</td>'
			),
			array(
				'foobar',
				array(
					'foo' => 'bar'
				),
				'<td foo="bar">foobar</td>'
			)
		);
	}
}