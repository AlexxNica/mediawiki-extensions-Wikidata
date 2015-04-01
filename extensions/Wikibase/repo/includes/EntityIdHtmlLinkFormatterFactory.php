<?php

namespace Wikibase\Repo;

use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * A factory for generating EntityIdHtmlLinkFormatters.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactory implements EntityIdFormatterFactory {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param EntityTitleLookup $titleLookup
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		EntityTitleLookup $titleLookup,
		LanguageNameLookup $languageNameLookup
	) {
		$this->titleLookup = $titleLookup;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @see EntityIdFormatterFactory::getOutputFormat
	 *
	 * @return string SnakFormatter::FORMAT_HTML
	 */
	public function getOutputFormat() {
		return SnakFormatter::FORMAT_HTML;
	}

	/**
	 * @see EntityIdFormatterFactory::getEntityIdFormater
	 *
	 * @param LabelLookup $labelLookup
	 *
	 * @return EntityIdHtmlLinkFormatter
	 */
	public function getEntityIdFormater( LabelLookup $labelLookup ) {
		return new EntityIdHtmlLinkFormatter(
			$labelLookup,
			$this->titleLookup,
			$this->languageNameLookup
		);
	}

}