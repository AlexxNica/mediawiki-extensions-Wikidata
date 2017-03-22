<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpSiteLink;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * @covers Wikibase\ChangeOp\ChangeOpSiteLink
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Michał Łazowik
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ChangeOpSiteLinkTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$argLists = array();

		$argLists[] = array( 'enwiki', 1234 );
		$argLists[] = array( 1234, 'Berlin' );
		$argLists[] = array( 'plwiki', 'Warszawa', array( 'FA', 'GA' ) );
		$argLists[] = array( 'plwiki', 'Warszawa', array( new ItemId( 'Q42' ), 'FA' ) );
		$argLists[] = array( 'plwiki', 'Warszawa', array( new PropertyId( 'P42' ) ) );

		return $argLists;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testConstructorWithInvalidArguments( $siteId, $linkPage, array $badges = null ) {
		new ChangeOpSiteLink( $siteId, $linkPage, $badges );
	}

	public function changeOpSiteLinkProvider() {
		$deSiteLink = new SiteLink( 'dewiki', 'Berlin' );
		$enSiteLink = new SiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q149' ) ) );
		$plSiteLink = new SiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ) ) );

		$existingSiteLinks = array(
			$deSiteLink,
			$plSiteLink
		);

		$args = array();

		// adding sitelink with badges
		$args[] = array(
			$existingSiteLinks,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q149' ) ) ),
			array_merge( $existingSiteLinks, array( $enSiteLink ) )
		);

		// deleting sitelink
		$args[] = array(
			$existingSiteLinks,
			new ChangeOpSiteLink( 'dewiki', null ),
			array( $plSiteLink )
		);

		// setting badges on existing sitelink
		$args[] = array(
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) ),
			array(
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) )
			)
		);

		// changing sitelink without modifying badges
		$args[] = array(
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', 'Test' ),
			array(
				$deSiteLink,
				new SiteLink( 'plwiki', 'Test', array( new ItemId( 'Q42' ) ) )
			)
		);

		// change badges without modifying title
		$args[] = array(
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', null, array( new ItemId( 'Q149' ) ) ),
			array(
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q149' ) ) )
			)
		);

		// add duplicate badges
		$args[] = array(
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', null, array( new ItemId( 'q42' ), new ItemId( 'Q149' ), new ItemId( 'Q42' ) ) ),
			array(
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) )
			)
		);

		return $args;
	}

	/**
	 * @dataProvider changeOpSiteLinkProvider
	 */
	public function testApply( array $existingSiteLinks, ChangeOpSiteLink $changeOpSiteLink, array $expectedSiteLinks ) {
		$item = new Item();
		$item->setSiteLinkList( new SiteLinkList( $existingSiteLinks ) );

		$changeOpSiteLink->apply( $item );

		$this->assertEquals(
			$expectedSiteLinks,
			array_values( $item->getSiteLinkList()->toArray() )
		);
	}

	public function testGivenNoSitelinkOnSiteAndBadgeChangeRequested_validateReturnsError() {
		$item = new Item();
		$item->setSiteLinkList( new SiteLinkList( [ new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ) ] ) ] ) );

		$changeOp = new ChangeOpSiteLink( 'enwiki', null, [ new ItemId( 'Q149' ) ] );

		$result = $changeOp->validate( $item );

		$this->assertFalse( $result->isValid() );
		$this->assertEquals( 'no-such-sitelink', $result->getErrors()[0]->getCode() );
	}

	public function summaryTestProvider() {
		$sitelinks = array(
			new SiteLink( 'dewiki', 'Berlin' ),
			new SiteLink( 'ruwiki', 'Берлин', array( new ItemId( 'Q42' ) ) )
		);

		$cases = array();
		$badge = new ItemId( 'Q149' );

		// Add sitelink without badges
		$cases['add-sitelink-without-badges'] = array(
			'add',
			array( 'Berlin' ),
			$sitelinks,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', array() )
		);

		// Add sitelink with badges
		$cases['add-sitelink-with-badges'] = array(
			'add-both',
			array( 'Berlin', array( $badge ) ),
			$sitelinks,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', array( $badge ) )
		);

		// Set page name only for existing sitelink
		$cases['set-pagename-existing-sitelink'] = array(
			'set',
			array( 'London' ),
			$sitelinks,
			new ChangeOpSiteLink( 'ruwiki', 'London' )
		);

		// Add badge to existing sitelink
		$cases['add-badges-to-existing-sitelink'] = array(
			'set-badges',
			array( array( $badge ) ),
			$sitelinks,
			new ChangeOpSiteLink( 'dewiki', null, array( $badge ) )
		);

		// Set page name and badges for existing sitelink
		$cases['set-pagename-badges-existing-sitelink'] = array(
			'set-both',
			array( 'London', array( $badge ) ),
			$sitelinks,
			new ChangeOpSiteLink( 'dewiki', 'London', array( $badge ) ),
		);

		// Changes badges for existing sitelink
		$cases['change-badges-for-existing-sitelink'] = array(
			'set-badges',
			array( array( $badge ) ),
			$sitelinks,
			new ChangeOpSiteLink( 'ruwiki', null, array( $badge ) )
		);

		return $cases;
	}

	/**
	 * @dataProvider summaryTestProvider
	 */
	public function testApplySummary(
		$expectedAction,
		array $expectedArguments,
		array $sitelinks,
		ChangeOpSiteLink $changeOpSiteLink
	) {
		$item = new Item();
		$item->setSiteLinkList( new SiteLinkList( $sitelinks ) );

		$summary = new Summary();
		$changeOpSiteLink->apply( $item, $summary );

		$this->assertSame(
			$expectedAction,
			$summary->getActionName()
		);

		$this->assertEquals(
			$expectedArguments,
			$summary->getAutoSummaryArgs()
		);
	}

}
