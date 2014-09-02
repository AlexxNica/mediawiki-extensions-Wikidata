<?php

namespace Wikibase;

use ContextSource;
use Html;
use IContextSource;
use InvalidArgumentException;
use ParserOutput;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\View\SnakHtmlGenerator;
use Wikibase\Repo\View\TextInjector;

/**
 * Base class for creating views for all different kinds of Wikibase\Entity.
 * For the Wikibase\Entity this basically is what the Parser is for WikitextContent.
 *
 * @todo  We might want to re-design this at a later point, designing this as a more generic and encapsulated rendering
 *        of DataValue instances instead of having functions here for generating different parts of the HTML. Right now
 *        these functions require an EntityRevision while a DataValue (if it were implemented) should be sufficient.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class EntityView extends ContextSource {

	/**
	 * @var EntityInfoBuilderFactory
	 */
	protected $entityInfoBuilderFactory;

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $dataTypeLookup;

	/**
	 * @var SectionEditLinkGenerator
	 */
	protected $sectionEditLinkGenerator;

	/**
	 * @var TextInjector
	 */
	protected $textInjector;

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	protected $configBuilder;

	/**
	 * @var ClaimsView
	 */
	protected $claimsView;

	/**
	 * @var FingerprintView
	 */
	protected $fingerprintView;

	/**
	 * Maps entity types to the corresponding entity view.
	 * FIXME: remove this stuff, big OCP violation
	 *
	 * @since 0.2
	 *
	 * @var string[]
	 */
	public static $typeMap = array(
		Item::ENTITY_TYPE => '\Wikibase\ItemView',
		Property::ENTITY_TYPE => '\Wikibase\PropertyView',

		// TODO: Query::ENTITY_TYPE
		'query' => '\Wikibase\QueryView',
	);

	/**
	 * @since 0.1
	 *
	 * @param IContextSource|null $context
	 * @param SnakFormatter $snakFormatter
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityInfoBuilderFactory $entityInfoBuilderFactory
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SerializationOptions $options
	 * @param ParserOutputJsConfigBuilder $configBuilder
	 *
	 * @todo: move the $editable flag here, instead of passing it around everywhere
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		EntityTitleLookup $entityTitleLookup,
		SerializationOptions $options,
		ParserOutputJsConfigBuilder $configBuilder
	) {
		if ( $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
				&& $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_WIDGET ) {
			throw new InvalidArgumentException( '$snakFormatter is expected to return text/html, not '
					. $snakFormatter->getFormat() );
		}

		$this->setContext( $context );
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->options = $options;
		$this->configBuilder = $configBuilder;

		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
		$this->textInjector = new TextInjector();

		// @todo inject in constructor
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$entityTitleLookup
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$entityTitleLookup
		);

		$this->claimsView =  new ClaimsView(
			$entityInfoBuilderFactory,
			$entityTitleLookup,
			$this->sectionEditLinkGenerator,
			$claimHtmlGenerator,
			$this->getLanguage()->getCode()
		);

		$this->fingerprintView = new FingerprintView(
			$this->sectionEditLinkGenerator,
			$this->getLanguage()->getCode()
		);
	}

	/**
	 * Resets the placeholders managed by this view
	 */
	public function resetPlaceholders() {
		$this->textInjector = new TextInjector();
	}

	/**
	 * Returns the placeholder map build while generating HTML.
	 * The map returned here may be used with TextInjector.
	 *
	 * @return array[] string -> array
	 */
	public function getPlaceholders() {
		return $this->textInjector->getMarkers();
	}

	/**
	 * Builds and returns the HTML representing a whole WikibaseEntity.
	 *
	 * @note: The HTML returned by this method may contain placeholders. Such placeholders can be
	 * expanded with the help of TextInjector::inject() calling back to
	 * EntityViewPlaceholderExpander::getExtraUserLanguages()
	 * @note: In order to keep the list of placeholders small, this calls resetPlaceholders().
	 *
	 * @since 0.1
	 *
	 * @param EntityRevision $entityRevision the entity to render
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string HTML
	 */
	public function getHtml( EntityRevision $entityRevision, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$this->resetPlaceholders();

		//NOTE: even though $editable is unused at the moment, we will need it for the JS-less editing model.

		$lang = $this->getLanguage();
		$entityId = $entityRevision->getEntity()->getId() ?: 'new'; // if id is not set, use 'new' suffix for css classes
		$html = '';

		$html .= wfTemplate( 'wikibase-entityview',
			$entityRevision->getEntity()->getType(),
			$entityId,
			$lang->getCode(),
			$lang->getDir(),
			$this->getInnerHtml( $entityRevision, $editable )
		);

		// Show loading spinner as long as JavaScript is initialising.
		// The fastest way to show it is placing the script right after the corresponding HTML.
		// Remove it after a while in any case (e.g. some resources might not have been loaded
		// silently, so JavaScript is not initialising).
		// Additionally attaching to window.error would only make sense before any other
		// JavaScript is parsed.
		$html .= Html::inlineScript( '
if ( $ ) {
	$( ".wikibase-entityview" ).addClass( "loading" ).after( function() {
		var $div = $( "<div/>" ).addClass( "wb-entity-spinner mw-small-spinner" );
		$div.css( "top", $div.height() + "px" );
		$div.css(
			"' . ( $lang->isRTL() ? 'right' : 'left' ) . '",
			( ( $( this ).width() - $div.width() ) / 2 | 0 ) + "px"
		);
		return $div;
	} );
	window.setTimeout( function() {
		$( ".wikibase-entityview" ).removeClass( "loading" );
		$( ".wb-entity-spinner" ).remove();
	}, 7000 );
}
' );

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Builds and returns the inner HTML for representing a whole WikibaseEntity. The difference to getHtml() is that
	 * this does not group all the HTMl within one parent node as one entity.
	 *
	 * @param EntityRevision $entityRevision
	 * @param bool $editable
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function getInnerHtml( EntityRevision $entityRevision, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$entity = $entityRevision->getEntity();

		$html = '';
		$html .= $this->getHtmlForFingerprint( $entity, $editable );
		$html .= $this->getHtmlForToc();
		$html .= $this->getHtmlForTermBox( $entityRevision, $editable );

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Builds and returns the HTML for the entity's fingerprint.
	 *
	 * @param Entity $entity
	 * @param bool $editable
	 * @return string
	 */
	protected function getHtmlForFingerprint( Entity $entity, $editable = true ) {
		return $this->fingerprintView->getHtml( $entity->getFingerprint(), $entity->getId(), $editable );
	}

	/**
	 * Builds and returns the HTML for the toc.
	 *
	 * @return string
	 */
	public function getHtmlForToc() {
		$tocContent = '';
		$tocSections = $this->getTocSections();

		if ( count( $tocSections ) < 2 ) {
			// Including the marker for the termbox toc entry, there is fewer
			// 3 sections. MediaWiki core doesn't show a TOC unless there are
			// at least 3 sections, so we shouldn't either.
			return '';
		}

		// Placeholder for the TOC entry for the term box (which may or may not be used for a given user).
		// EntityViewPlaceholderExpander must know about the 'termbox-toc' name.
		$tocContent .= $this->textInjector->newMarker( 'termbox-toc' );

		$i = 1;

		foreach ( $tocSections as $id => $message ) {
			$tocContent .= wfTemplate( 'wb-entity-toc-section',
				$i++,
				$id,
				wfMessage( $message )->text()
			);
		}

		$toc = wfTemplate( 'wb-entity-toc',
			wfMessage( 'toc' )->text(),
			$tocContent
		);

		return $toc;
	}

	/**
	 * Returns the sections that should displayed in the toc.
	 *
	 * @return string[] array( link target => system message key )
	 */
	protected function getTocSections() {
		return array();
	}

	/**
	 * @param EntityRevision $entityRevision
	 * @param bool $editable
	 *
	 * @return string
	 */
	protected function getHtmlForTermBox( EntityRevision $entityRevision, $editable = true ) {
		if ( $entityRevision->getEntity()->getId() ) {
			// Placeholder for a termbox for the present item.
			// EntityViewPlaceholderExpander must know about the parameters used here.
			return $this->textInjector->newMarker(
				'termbox',
				$entityRevision->getEntity()->getId()->getSerialization(),
				$entityRevision->getRevision()
			);
		}

		return '';
	}

	/**
	 * Renders an entity into an ParserOutput object
	 *
	 * @since 0.1
	 *
	 * @param EntityRevision $entityRevision the entity to analyze/render
	 * @param bool $editable whether to make the page's content editable
	 * @param bool $generateHtml whether to generate HTML. Set to false if only interested in meta-info. default: true.
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( EntityRevision $entityRevision, $editable = true,
		$generateHtml = true
	) {
		wfProfileIn( __METHOD__ );

		// fresh parser output with entity markup
		$pout = new ParserOutput();

		$entity =  $entityRevision->getEntity();
		$isExperimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;

		$configVars = $this->configBuilder->build( $entity, $this->options, $isExperimental );
		$pout->addJsConfigVars( $configVars );

		$allSnaks = $entityRevision->getEntity()->getAllSnaks();

		// treat referenced entities as page links ------
		$entitiesFinder = new ReferencedEntitiesFinder();
		$usedEntityIds = $entitiesFinder->findSnakLinks( $allSnaks );

		foreach ( $usedEntityIds as $entityId ) {
			$pout->addLink( $this->entityTitleLookup->getTitleForId( $entityId ) );
		}

		// treat URL values as external links ------
		$urlFinder = new ReferencedUrlFinder( $this->dataTypeLookup );
		$usedUrls = $urlFinder->findSnakLinks( $allSnaks );

		foreach ( $usedUrls as $url ) {
			$pout->addExternalLink( $url );
		}

		if ( $generateHtml ) {
			$html = $this->getHtml( $entityRevision, $editable );
			$pout->setText( $html );
			$pout->setExtensionData( 'wikibase-view-chunks', $this->getPlaceholders() );
		}

		if ( $entity instanceof Item ) {
			$this->addBadgesToParserOutput( $pout, $entity->getSiteLinkList() );
		}

		//@todo: record sitelinks as iwlinks
		//@todo: record CommonsMedia values as imagelinks

		// make css available for JavaScript-less browsers
		$pout->addModuleStyles( array(
			'wikibase.common',
			'wikibase.toc',
			'jquery.ui.core',
			'jquery.wikibase.statementview',
			'jquery.wikibase.toolbar',
		) );

		// make sure required client sided resources will be loaded:
		$pout->addModules( 'wikibase.ui.entityViewInit' );

		//FIXME: some places, like Special:NewItem, don't want to override the page title.
		//	 But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//	 So, for now, we leave it to the caller to override the display title, if desired.
		// set the display title
		//$pout->setTitleText( $entity>getLabel( $langCode ) );

		wfProfileOut( __METHOD__ );
		return $pout;
	}

	private function addBadgesToParserOutput( ParserOutput $pout, SiteLinkList $siteLinkList ) {
		foreach ( $siteLinkList as $siteLink ) {
			foreach ( $siteLink->getBadges() as $badge ) {
				$pout->addLink( $this->entityTitleLookup->getTitleForID( $badge ) );
			}
		}
	}

}
