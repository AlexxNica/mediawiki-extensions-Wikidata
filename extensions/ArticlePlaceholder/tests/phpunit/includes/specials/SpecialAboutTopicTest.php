<?php

namespace ArticlePlaceholder\Tests\Specials;

use ArticlePlaceholder\AboutTopicRenderer;
use ArticlePlaceholder\Specials\SpecialAboutTopic;
use DerivativeContext;
use Language;
use MediaWikiTestCase;
use RequestContext;
use OutputPage;
use SpecialPage;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers ArticlePlaceholder\Specials\SpecialAboutTopic
 *
 * @group ArticlePlaceholder
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 * @author Lucie-Aimée Kaffee
 */
class SpecialAboutTopicTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( [
			'wgContLang' => Language::factory( 'qqx' )
		] );
	}

	public function testNewFromGlobalState() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$siteGroup = $settings->getSetting( 'siteGroup' );
		$settings->setSetting( 'siteGroup', 'wikipedia' );

		$this->assertInstanceOf(
			SpecialAboutTopic::class,
			SpecialAboutTopic::newFromGlobalState()
		);

		$settings->setSetting( 'siteGroup', $siteGroup );
	}

	public function provideSearchEngineIndexed() {
		return [
			[ true ],
			[ false ],
			[ 'Q123' ]
		];
	}

	/**
	 * @dataProvider provideSearchEngineIndexed
	 */
	public function testHTML( $searchEngineIndexed ) {
		$output = $this->getInstanceOutput( '', $searchEngineIndexed );
		$this->assertSame( '(articleplaceholder-abouttopic)', $output->getPageTitle() );

		$html = $output->getHTML();
		$this->assertContains( 'id=\'ap-abouttopic-form1\'', $html );
		$this->assertContains( 'id=\'ap-abouttopic-entityid\'', $html );
		$this->assertContains( '(articleplaceholder-abouttopic-intro)', $html );
		$this->assertContains( '(articleplaceholder-abouttopic-entityid)', $html );
		$this->assertContains( '(articleplaceholder-abouttopic-submit)', $html );
	}

	public function testRedirect() {
		$redirect = $this->getInstanceOutput( 'Q1234' )->getRedirect();

		$this->assertSame( Title::newFromText( 'Beer' )->getLinkURL(), $redirect );
	}

	/**
	 * @param string $itemIdSerialization
	 *
	 * @return OutputPage
	 */
	private function getInstanceOutput( $itemIdSerialization, $searchEngineIndexed = true ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$title = SpecialPage::getTitleFor( 'AboutTopic' );
		$context->setTitle( $title );
		$outputPage = new OutputPage( $context );

		// initial robot policy should be like the one gotten from the SpecialPage
		$outputPage->setRobotPolicy( 'noindex,nofollow' );

		$context->setOutput( $outputPage );

		$instance = new SpecialAboutTopic(
			$this->getMockBuilder( AboutTopicRenderer::class )->disableOriginalConstructor()->getMock(),
			$this->getEntityIdParser(),
			$this->getSiteLinkLookup(),
			new TitleFactory(),
			'enwiki',
			$this->getEntityLookup(),
			$searchEngineIndexed
		);
		$instance->setContext( $context );

		$instance->execute( $itemIdSerialization );
		return $instance->getOutput();
	}

	private function getSiteLinkLookup() {
		$siteLikLookup = $this->getMockBuilder( SiteLinkLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$siteLikLookup->expects( $this->any() )
			->method( 'getLinks' )
			->with( [ 1234 ], [ 'enwiki' ] )
			->will( $this->returnValue( [ [ 'enwiki', 'Beer', 1234 ] ] ) );

		return $siteLikLookup;
	}

	private function getEntityIdParser() {
		$siteLikLookup = $this->getMockBuilder( EntityIdParser::class )
			->disableOriginalConstructor()
			->getMock();

		$siteLikLookup->expects( $this->any() )
			->method( 'parse' )
			->with( 'Q1234' )
			->will( $this->returnValue( new ItemId( 'Q1234' ) ) );

		return $siteLikLookup;
	}

	private function getEntityLookup() {
		$item = new Item( new ItemId( 'Q1234' ) );
		$item->setLabel( 'en', 'Beer' );
		$item->setDescription( 'en', 'yummy beverage' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Beer' );

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		return $entityLookup;
	}

	public function provideRobotPolicy() {
		return [
			[
				true,
				true
			],
			[
				false,
				false
			],
			[
				'Q1',
				false
			],
			[
				'Q2000',
				true
			]
		];
	}

	/**
	 * @dataProvider provideRobotPolicy
	 */
	public function testRobotPolicy( $searchEngineIndexed, $expected ) {
		$output = $this->getInstanceOutput( 'Q1234', $searchEngineIndexed );
		$metatags = $output->getHeadLinksArray();

		if ( $expected === true ) {
			$this->assertArrayNotHasKey( 'meta-robots', $metatags );
		} else {
			$this->assertArrayHasKey( 'meta-robots', $metatags );
			$this->assertContains( 'noindex,nofollow', $metatags['meta-robots'] );
		}
	}

}
