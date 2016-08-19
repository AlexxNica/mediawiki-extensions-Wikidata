<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use LogicException;
use UsageException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikimedia\Assert\Assert;

/**
 * Helper class for api modules to load entities.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class EntityLoadingHelper {

	/**
	 * @var ApiBase
	 */
	protected $apiModule;

	/**
	 * @var EntityRevisionLookup
	 */
	protected $entityRevisionLookup;

	/**
	 * @var ApiErrorReporter
	 */
	protected $errorReporter;

	/**
	 * @var string See the LATEST_XXX constants defined in EntityRevisionLookup
	 */
	protected $defaultRetrievalMode = EntityRevisionLookup::LATEST_FROM_SLAVE;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var SiteLinkLookup|null
	 */
	private $siteLinkLookup = null;

	/**
	 * @var string
	 */
	private $entityIdParam = 'entity';

	public function __construct(
		ApiBase $apiModule,
		EntityIdParser $idParser,
		EntityRevisionLookup $entityRevisionLookup,
		ApiErrorReporter $errorReporter
	) {
		$this->apiModule = $apiModule;
		$this->idParser = $idParser;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * Returns the name of the request parameter expected to contain the ID of the entity to load.
	 *
	 * @return string
	 */
	public function getEntityIdParam() {
		return $this->entityIdParam;
	}

	/**
	 * Sets the name of the request parameter expected to contain the ID of the entity to load.
	 *
	 * @param string $entityIdParam
	 */
	public function setEntityIdParam( $entityIdParam ) {
		$this->entityIdParam = $entityIdParam;
	}

	/**
	 * @return SiteLinkLookup|null
	 */
	public function getSiteLinkLookup() {
		return $this->siteLinkLookup;
	}

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 */
	public function setSiteLinkLookup( SiteLinkLookup $siteLinkLookup ) {
		$this->siteLinkLookup = $siteLinkLookup;
	}

	/**
	 * @return string
	 */
	public function getDefaultRetrievalMode() {
		return $this->defaultRetrievalMode;
	}

	/**
	 * @param string $defaultRetrievalMode Use the LATEST_XXX constants defined
	 *        in EntityRevisionLookup
	 */
	public function setDefaultRetrievalMode( $defaultRetrievalMode ) {
		Assert::parameterType( 'string', $defaultRetrievalMode, '$defaultRetrievalMode' );
		$this->defaultRetrievalMode = $defaultRetrievalMode;
	}

	/**
	 * Load the entity content of the given revision.
	 *
	 * Will fail by calling dieException() $this->errorReporter if the revision
	 * cannot be found or cannot be loaded.
	 *
	 * @param EntityId $entityId EntityId of the page to load the revision for
	 * @param int|string|null $revId revision to load, or the retrieval mode,
	 *        see the LATEST_XXX constants defined in EntityRevisionLookup.
	 *        If not given, the current revision will be loaded, using the default retrieval mode.
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return EntityRevision|null
	 */
	protected function loadEntityRevision(
		EntityId $entityId,
		$revId = null
	) {
		if ( $revId === null ) {
			$revId = $this->defaultRetrievalMode;
		}

		try {
			$revision = $this->entityRevisionLookup->getEntityRevision( $entityId, $revId );
			return $revision;
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->errorReporter->dieException( $ex, 'unresolved-redirect' );
		} catch ( BadRevisionException $ex ) {
			$this->errorReporter->dieException( $ex, 'nosuchrevid' );
		} catch ( StorageException $ex ) {
			$this->errorReporter->dieException( $ex, 'cant-load-entity-content' );
		}

		throw new LogicException( 'ApiErrorReporter::dieException did not throw a UsageException' );
	}

	/**
	 * @param EntityId|null $entityId ID of the entity to load. If not given, the ID is taken
	 *        from the request parameters. If $entityId is given, it must be consistent with
	 *        the 'baserevid' parameter.
	 * @return EntityDocument
	 */
	public function loadEntity( EntityId $entityId = null ) {
		if ( !$entityId ) {
			$params = $this->apiModule->extractRequestParams();
			$entityId = $this->getEntityIdFromParams( $params );
		}

		if ( !$entityId ) {
			$this->errorReporter->dieError(
				'No entity ID provided',
				'no-entity-id' );
		}

		$entityRevision = $this->loadEntityRevision( $entityId );

		if ( !$entityRevision ) {
			$this->errorReporter->dieError(
				'Entity ' . $entityId->getSerialization() . ' not found',
				'no-such-entity' );
		}

		return $entityRevision->getEntity();
	}

	/**
	 * @param string[] $params
	 *
	 * @return EntityId|null
	 */
	protected function getEntityIdFromParams( array $params ) {
		if ( isset( $params[$this->entityIdParam] ) ) {
			return $this->getEntityIdFromString( $params[$this->entityIdParam] );
		} elseif ( isset( $params['site'] ) && isset( $params['title'] ) ) {
			return $this->getEntityIdFromSiteTitleCombination(
				$params['site'],
				$params['title']
			);
		}

		return null;
	}

	/**
	 * Returns an EntityId object based on the given $id,
	 * or throws a usage exception if the ID is invalid.
	 *
	 * @param string $id
	 *
	 * @throws UsageException
	 * @return EntityId
	 */
	private function getEntityIdFromString( $id ) {
		try {
			return $this->idParser->parse( $id );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-entity-id' );
		}

		return null;
	}

	/**
	 * @param string $site
	 * @param string $title
	 *
	 * @throws UsageException If no such entity is found.
	 * @return EntityId The ID of the entity connected to $title on $site.
	 */
	private function getEntityIdFromSiteTitleCombination( $site, $title ) {
		if ( $this->siteLinkLookup ) {
			// FIXME: Normalization missing, see T47282.
			$itemId = $this->siteLinkLookup->getItemIdForLink( $site, $title );
		} else {
			$itemId = null;
		}

		if ( $itemId === null ) {
			$this->errorReporter->dieError( 'No entity found matching site link ' . $site . ':' . $title,
			                                'no-such-entity-link' );
		}

		return $itemId;
	}

}
