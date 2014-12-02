<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class for creating views for Item instances.
 * For the Item this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 */
class ItemView extends EntityView {

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @see EntityView::__construct
	 *
	 * @param FingerprintView $fingerprintView
	 * @param ClaimsView $claimsView
	 * @param Language $language
	 * @param string[] $siteLinkGroups
	 */
	public function __construct(
		FingerprintView $fingerprintView,
		ClaimsView $claimsView,
		Language $language,
		array $siteLinkGroups
	) {
		parent::__construct( $fingerprintView, $claimsView, $language );

		$this->siteLinkGroups = $siteLinkGroups;
	}

	/**
	 * @see EntityView::getMainHtml
	 */
	protected function getMainHtml( EntityRevision $entityRevision, array $entityInfo,
		$editable = true
	) {
		$item = $entityRevision->getEntity();

		if ( !( $item instanceof Item ) ) {
			throw new InvalidArgumentException( '$entityRevision must contain an Item.' );
		}

		$html = parent::getMainHtml( $entityRevision, $entityInfo, $editable );
		$html .= $this->claimsView->getHtml(
			$item->getStatements()->toArray(),
			$entityInfo
		);

		return $html;
	}

	/**
	 * @see EntityView::getSideHtml
	 */
	protected function getSideHtml( EntityRevision $entityRevision, $editable = true ) {
		$item = $entityRevision->getEntity();
		return $this->getHtmlForSiteLinks( $item, $editable );
	}

	/**
	 * @see EntityView::getTocSections
	 */
	protected function getTocSections() {
		$array = parent::getTocSections();
		$array['claims'] = 'wikibase-statements';
		foreach ( $this->siteLinkGroups as $group ) {
			$id = htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES );
			$array[$id] = 'wikibase-sitelinks-' . $group;
		}
		return $array;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @since 0.1
	 *
	 * @param Item $item the entity to render
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	protected function getHtmlForSiteLinks( Item $item, $editable = true ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		// FIXME: Inject this
		$siteLinksView = new SiteLinksView(
			$wikibaseRepo->getSiteStore()->getSites(),
			new SectionEditLinkGenerator(),
			$wikibaseRepo->getEntityLookup(),
			$this->language->getCode()
		);

		$itemId = $item->getId();

		return $siteLinksView->getHtml(
			$item->getSiteLinks(),
			$itemId,
			$this->siteLinkGroups,
			$editable
		);
	}

}