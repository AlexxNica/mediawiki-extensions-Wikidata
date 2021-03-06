<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Title;

/**
 * Tests for pages that are not connected to any Item.
 *
 * @covers Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseLibraryNoLinkedEntityTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLibraryNoLinkedEntityTests';

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'LuaWikibaseLibraryNoLinkedEntityTests' => __DIR__ . '/LuaWikibaseLibraryNoLinkedEntityTests.lua',
		);
	}

	/**
	 * @return Title
	 */
	protected function getTestTitle() {
		return Title::newFromText( 'WikibaseClientDataAccessTest-NotLinkedWithAnyEntity' );
	}

}
