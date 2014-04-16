<?php

namespace Wikibase\content;

use Status;
use Wikibase\DataModel\Entity\Entity;

/**
 * Validator interface used for pre-save validation in EntityContent.
 * This is essentially a vehicle to introduce knowledge about global state into
 * the context of the save operation, so global constraints can be enforced.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityValidator {

	/**
	 * Validate an entity before saving.
	 * This is intended for enforcing "hard" global constraints.
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @return Status
	 */
	public function validateEntity( Entity $entity );

}