<?php

namespace Wikibase\Repo\Specials;

use Html;
use Language;
use Status;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Page for creating new Wikibase entities.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialNewEntity extends SpecialWikibaseRepoPage {

	/**
	 * Contains pieces of the sub-page name of this special page if a subpage was called.
	 * E.g. array( 'a', 'b' ) in case of 'Special:NewEntity/a/b'
	 * @var string[]|null
	 */
	protected $parts = null;

	/**
	 * @var string|null
	 */
	private $label = null;

	/**
	 * @var string|null
	 */
	private $description = null;

	/**
	 * @var Language
	 */
	private $contentLanguage = null;

	/**
	 * @var string
	 */
	private $rightsUrl;

	/**
	 * @var string
	 */
	private $rightsText;

	/**
	 * @param string $name Name of the special page, as seen in links and URLs.
	 * @param string $restriction User right required, 'createpage' per default.
	 *
	 * @since 0.1
	 */
	public function __construct( $name, $restriction = 'createpage' ) {
		parent::__construct( $name, $restriction );

		// TODO: find a way to inject this
		$this->summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();

		$this->rightsUrl = $settings->getSetting( 'dataRightsUrl' );
		$this->rightsText = $settings->getSetting( 'dataRightsText' );
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.1
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->checkPermissions();
		$this->checkBlocked();
		$this->checkReadOnly();

		$this->parts = ( $subPage === '' ? array() : explode( '/', $subPage ) );
		$this->prepareArguments();

		$out = $this->getOutput();

		$uiLanguageCode = $this->getLanguage()->getCode();

		if ( $this->getRequest()->wasPosted()
			&& $this->getUser()->matchEditToken( $this->getRequest()->getVal( 'token' ) )
		) {
			if ( $this->hasSufficientArguments() ) {
				$entity = $this->createEntity();

				$status = $this->modifyEntity( $entity );

				if ( $status->isGood() ) {
					$summary = new Summary( 'wbeditentity', 'create' );
					$summary->setLanguage( $uiLanguageCode );
					$summary->addAutoSummaryArgs( $this->label, $this->description );

					$status = $this->saveEntity(
						$entity,
						$summary,
						$this->getRequest()->getVal( 'token' ),
						EDIT_NEW
					);

					$out = $this->getOutput();

					if ( !$status->isOK() ) {
						$out->addHTML( '<div class="error">' );
						$out->addWikiText( $status->getWikiText() );
						$out->addHTML( '</div>' );
					} elseif ( $entity !== null ) {
						$title = $this->getEntityTitle( $entity->getId() );
						$entityUrl = $title->getFullUrl();
						$this->getOutput()->redirect( $entityUrl );
					}
				} else {
					$out->addHTML( '<div class="error">' );
					$out->addHTML( $status->getHTML() );
					$out->addHTML( '</div>' );
				}
			}
		}

		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		foreach ( $this->getWarnings() as $warning ) {
			$out->addHTML( Html::element( 'div', array( 'class' => 'warning' ), $warning ) );
		}

		$this->createForm( $this->getLegend(), $this->additionalFormElements() );
	}

	/**
	 * Tries to extract argument values from web request or of the page's sub-page parts
	 *
	 * @since 0.1
	 */
	protected function prepareArguments() {
		$this->label = $this->getRequest()->getVal(
			'label',
			isset( $this->parts[0] ) ? $this->parts[0] : ''
		);
		$this->description = $this->getRequest()->getVal(
			'description',
			isset( $this->parts[1] ) ? $this->parts[1] : ''
		);
		$aliases = $this->getRequest()->getVal( 'aliases' );
		$this->aliases = ( $aliases === null ? array() : explode( '|', $aliases ) );
		$this->contentLanguage = Language::factory( $this->getRequest()->getVal(
			'lang',
			$this->getLanguage()->getCode()
		) );
	}

	/**
	 * Checks whether required arguments are set sufficiently
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	protected function hasSufficientArguments() {
		return $this->stringNormalizer->trimWhitespace( $this->label ) !== ''
			|| $this->stringNormalizer->trimWhitespace( $this->description ) !== ''
			|| implode( '', array_map(
					array( $this->stringNormalizer, 'trimWhitespace' ),
					$this->aliases
				) ) !== '';
	}

	/**
	 * @since 0.1
	 *
	 * @return Entity Created entity of correct subtype
	 */
	abstract protected function createEntity();

	/**
	 * Attempt to modify entity
	 *
	 * @since 0.1
	 *
	 * @param Entity &$entity
	 *
	 * @return Status
	 */
	protected function modifyEntity( Entity &$entity ) {
		$contentLanguageCode = $this->contentLanguage->getCode();
		if ( $this->label !== '' ) {
			$entity->setLabel( $contentLanguageCode, $this->label );
		}
		if ( $this->description !== '' ) {
			$entity->setDescription( $contentLanguageCode, $this->description );
		}
		if ( count( $this->aliases ) !== 0 ) {
			$entity->setAliases( $contentLanguageCode, $this->aliases );
		}
		return \Status::newGood();
	}

	/**
	 * Build additional formelements
	 *
	 * @since 0.1
	 *
	 * @return string Formatted HTML for inclusion in the form
	 */
	protected function additionalFormElements() {
		$langCode = $this->contentLanguage->getCode();
		$langName = Language::fetchLanguageName( $langCode );
		$langDir = $this->contentLanguage->getDir();
		return Html::hidden(
			'lang',
			$langCode
		)
		. Html::element(
			'label',
			array(
				'for' => 'wb-newentity-label',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-newentity-label' )->text()
		)
		. Html::input(
			'label',
			$this->label ?: '',
			'text',
			array(
				'id' => 'wb-newentity-label',
				'class' => 'wb-input',
				'lang' => $langCode,
				'dir' => $langDir,
				'placeholder' => $this->msg(
					'wikibase-label-edit-placeholder-language-aware',
					$langName
				)->text(),
			)
		)
		. Html::element(
			'label',
			array(
				'for' => 'wb-newentity-description',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-newentity-description' )->text()
		)
		. Html::input(
			'description',
			$this->description ?: '',
			'text',
			array(
				'id' => 'wb-newentity-description',
				'class' => 'wb-input',
				'lang' => $langCode,
				'dir' => $langDir,
				'placeholder' => $this->msg(
					'wikibase-description-edit-placeholder-language-aware',
					$langName
				)->text(),
			)
		). Html::element(
			'label',
			array(
				'for' => 'wb-newentity-aliases',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-newentity-aliases' )->text()
		)
		. Html::input(
			'aliases',
			$this->aliases ? implode( '|', $this->aliases ) : '',
			'text',
			array(
				'id' => 'wb-newentity-aliases',
				'class' => 'wb-input',
				'lang' => $langCode,
				'dir' => $langDir,
				'placeholder' => $this->msg(
					'wikibase-aliases-edit-placeholder-language-aware',
					$langName
				)->text(),
			)
		);
	}

	/**
	 * Building the HTML form for creating a new item.
	 *
	 * @param string|null $legend initial value for the label input box
	 * @param string $additionalHtml initial value for the description input box
	 */
	private function createForm( $legend = null, $additionalHtml = '' ) {
		$this->addCopyrightText();

		$this->getOutput()->addHTML(
				Html::openElement(
					'form',
					array(
						'method' => 'post',
						'action' => $this->getPageTitle()->getFullUrl(),
						'name' => 'newentity',
						'id' => 'mw-newentity-form1',
						'class' => 'wb-form'
					)
				)
				. Html::openElement(
					'fieldset',
					array( 'class' => 'wb-fieldset' )
				)
				. Html::element(
					'legend',
					array( 'class' => 'wb-legend' ),
					$legend
				)
				. Html::hidden(
					'token',
					$this->getUser()->getEditToken()
				)
				. $additionalHtml
				. Html::input(
					'submit',
					$this->msg( 'wikibase-newentity-submit' )->text(),
					'submit',
					array(
						'id' => 'wb-newentity-submit',
						'class' => 'wb-button'
					)
				)
				. Html::closeElement( 'fieldset' )
				. Html::closeElement( 'form' )
		);
	}

	/**
	 * @todo could factor this out into a special page form builder and renderer
	 */
	private function addCopyrightText() {
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$this->rightsUrl,
			$this->rightsText
		);

		$html = $copyrightView->getHtml( $this->getLanguage(), 'wikibase-newentity-submit' );

		$this->getOutput()->addHTML( $html );
	}

	/**
	 * @since 0.1
	 *
	 * @return string Legend for the fieldset
	 */
	abstract protected function getLegend();

	/**
	 * Returns any warnings.
	 *
	 * @since 0.4
	 *
	 * @return string[] Warnings that should be presented to the user
	 */
	abstract protected function getWarnings();

}
