<?php

namespace Wikibase\MediaInfo\Tests\MediaWiki\View;

use InvalidArgumentException;
use Language;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\EntityRevision;
use Wikibase\MediaInfo\DataModel\MediaInfo;
use Wikibase\MediaInfo\DataModel\MediaInfoId;
use Wikibase\MediaInfo\View\MediaInfoView;
use Wikibase\View\EntityTermsView;
use Wikibase\View\EntityView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TextInjector;

/**
 * @covers Wikibase\MediaInfo\View\MediaInfoView
 *
 * @license GPL-2.0+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class MediaInfoViewTest extends PHPUnit_Framework_TestCase {

	private function newStatementSectionsViewMock() {
		return $this->getMockBuilder( StatementSectionsView::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newEntityTermsViewMock() {
		return $this->getMockBuilder( EntityTermsView::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newLanguageDirectionalityLookupMock() {
		$languageDirectionalityLookup = $this->getMock( LanguageDirectionalityLookup::class );
		$languageDirectionalityLookup->method( 'getDirectionality' )
			->willReturn( 'auto' );

		return $languageDirectionalityLookup;
	}

	private function newMediaInfoView(
		$contentLanguageCode = 'en',
		EntityTermsView $entityTermsView = null,
		StatementSectionsView $statementSectionsView = null
	) {
		$templateFactory = TemplateFactory::getDefaultInstance();

		if ( !$entityTermsView ) {
			$entityTermsView = $this->newEntityTermsViewMock();
		}

		if ( !$statementSectionsView ) {
			$statementSectionsView = $this->newStatementSectionsViewMock();
		}

		return new MediaInfoView(
			$templateFactory,
			$entityTermsView,
			$statementSectionsView,
			$this->newLanguageDirectionalityLookupMock(),
			$contentLanguageCode
		);
	}

	private function newEntityRevision( EntityDocument $entity ) {
		$revId = 0;
		$timestamp = wfTimestamp( TS_MW );
		return new EntityRevision( $entity, $revId, $timestamp );
	}

	public function testInstantiate() {
		$view = $this->newMediaInfoView();
		$this->assertInstanceOf( MediaInfoView::class, $view );
		$this->assertInstanceOf( EntityView::class, $view );
	}

	public function testGetHtml_invalidEntityType() {
		$view = $this->newMediaInfoView();

		$entity = $this->getMock( EntityDocument::class );
		$revision = $this->newEntityRevision( $entity );

		$this->setExpectedException( InvalidArgumentException::class );
		$view->getHtml( $revision );
	}

	/**
	 * @dataProvider provideTestGetHtml
	 */
	public function testGetHtml(
		MediaInfo $entity,
		MediaInfoId $entityId = null,
		TermList $descriptions = null,
		$contentLanguageCode = 'en',
		StatementList $statements = null
	) {
		$entityTermsView = $this->newEntityTermsViewMock();
		$entityTermsView->expects( $this->once() )
			->method( 'getHtml' )
			->with(
				$this->callback( function( Fingerprint $fingerprint ) use ( $descriptions ) {
					if ( $descriptions ) {
						return $fingerprint->getDescriptions() === $descriptions;
					} else {
						return $fingerprint->getDescriptions()->isEmpty();
					}
				} ),
				$entityId,
				$this->isType( 'string' ),
				$this->isInstanceOf( TextInjector::class )
			)
			->will( $this->returnValue( 'entityTermsView->getHtml' ) );

		// FIXME Shouldn't be called
		$entityTermsView->expects( $this->once() )
			->method( 'getEntityTermsForLanguageListView' )
			->will( $this->returnValue( 'entityTermsView->getEntityTermsForLanguageListView' ) );

		$statementSectionsView = $this->newStatementSectionsViewMock();
		$statementSectionsView->expects( $this->once() )
			->method( 'getHtml' )
			->with(
				$this->callback( function( StatementList $statementList ) use ( $statements ) {
					return $statements ? $statementList === $statements : $statementList->isEmpty();
				} )
			)
			->will( $this->returnValue( 'statementSectionsView->getHtml' ) );

		$view = $this->newMediaInfoView(
			$contentLanguageCode,
			$entityTermsView,
			$statementSectionsView
		);

		$revision = $this->newEntityRevision( $entity );

		$result = $view->getHtml( $revision );
		$this->assertInternalType( 'string', $result );
		$this->assertContains( 'wb-mediainfo', $result );
		$this->assertContains( 'entityTermsView->getHtml', $result );
	}

	public function provideTestGetHtml() {
		$mediaInfoId = new MediaInfoId( 'M1' );
		$descriptions = new TermList( [ new Term( 'en', 'EN_DESCRIPTION' ) ] );
		$statements = new StatementList( [
			new Statement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) )
		] );

		return [
			[
				new MediaInfo()
			],
			[
				new MediaInfo(
					$mediaInfoId
				),
				$mediaInfoId
			],
			[
				new MediaInfo(
					$mediaInfoId,
					null,
					$descriptions,
					$statements
				),
				$mediaInfoId,
				$descriptions,
				'en',
				$statements
			],
			[
				new MediaInfo(
					$mediaInfoId,
					null,
					$descriptions
				),
				$mediaInfoId,
				$descriptions,
				'lkt'
			],
			[
				new MediaInfo(
					$mediaInfoId,
					null,
					$descriptions,
					$statements
				),
				$mediaInfoId,
				$descriptions,
				'lkt',
				$statements
			],
		];
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGetTitleHtml_invalidEntityType() {
		$view = $this->newMediaInfoView();

		$entity = $this->getMock( EntityDocument::class );
		$revision = $this->newEntityRevision( $entity );

		$view->getTitleHtml( $revision );
	}

	/**
	 * @dataProvider provideTestGetTitleHtml
	 */
	public function testGetTitleHtml(
		MediaInfo $entity,
		TermList $labels = null,
		MediaInfoId $entityId = null,
		$contentLanguageCode = 'en'
	) {
		$entityTermsView = $this->newEntityTermsViewMock();
		$entityTermsView->expects( $this->once() )
			->method( 'getTitleHtml' )
			->with(
				$this->callback( function( Fingerprint $fingerprint ) use ( $labels ) {
					return $labels ? $fingerprint->getLabels() === $labels : $fingerprint->getLabels()->isEmpty();
				} ),
				$entityId
			)
			->will( $this->returnValue( 'entityTermsView->getTitleHtml' ) );

		$view = $this->newMediaInfoView( $contentLanguageCode, $entityTermsView );
		$revision = $this->newEntityRevision( $entity );

		$result = $view->getTitleHtml( $revision );
		$this->assertInternalType( 'string', $result );
		$this->assertEquals( 'entityTermsView->getTitleHtml', $result );
	}

	public function provideTestGetTitleHtml() {
		$mediaInfoId = new MediaInfoId( 'M1' );
		$labels = new TermList( [ new Term( 'en', 'EN_LABEL' ) ] );

		return [
			[
				new MediaInfo()
			],
			[
				new MediaInfo(
					$mediaInfoId
				),
				null,
				$mediaInfoId
			],
			[
				new MediaInfo(
					$mediaInfoId,
					$labels
				),
				$labels,
				$mediaInfoId
			],
			[
				new MediaInfo(
					$mediaInfoId,
					$labels
				),
				$labels,
				$mediaInfoId,
				'lkt'
			],
		];
	}

	public function testPlaceholderIntegration() {
		$entityRevision = $this->newEntityRevision( new MediaInfo( new MediaInfoId( 'M1' ) ) );

		$entityTermsView = $this->newEntityTermsViewMock();
		$entityTermsView->expects( $this->once() )
			->method( 'getHtml' )
			->will( $this->returnCallback(
				function(
					Fingerprint $fingerprint,
					MediaInfoId $entityId,
					$termBoxHtml,
					TextInjector $textInjector
				) {
					return $textInjector->newMarker(
						'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class'
					);
				}
			) );

		$view = $this->newMediaInfoView( 'en', $entityTermsView );
		$view->getHtml( $entityRevision );
		$placeholders = $view->getPlaceholders();

		// FIXME: EntityViewPlaceholderExpander only supports entities with fingerprints
		// Otherwise this would be 2.
		$this->assertEquals( 1, count( $placeholders ) );
	}

}
