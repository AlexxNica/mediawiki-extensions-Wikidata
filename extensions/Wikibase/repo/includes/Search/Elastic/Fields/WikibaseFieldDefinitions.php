<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseFieldDefinitions {

	/**
	 * @return SearchIndexField[] Array key is field name.
	 */
	public function getFields() {
		$fields = array(
			'label_count' => new LabelCountField(),
			'sitelink_count' => new SiteLinkCountField(),
			'statement_count' => new StatementCountField()
		);

		return $fields;
	}

}