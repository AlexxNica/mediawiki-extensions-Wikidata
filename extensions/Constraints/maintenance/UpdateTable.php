<?php

namespace WikibaseQuality\ConstraintReport\Maintenance;

use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ConstraintReport\Constraint;
use WikibaseQuality\ConstraintReport\ConstraintReportFactory;

if( !class_exists( '\Maintenance' ) ) {
	$basePath = getenv( "MW_INSTALL_PATH" ) !== false ? getenv( "MW_INSTALL_PATH" ) : __DIR__ . "/../../..";
	require_once $basePath . "/maintenance/Maintenance.php";
}


/**
 * Class UpdateTable
 *
 * @package WikibaseQuality\ConstraintReport\Maintenance
 *
 * Fills constraint table with constraints given in a csv file passed to this.
 * Should be done once a week to keep constraint table up to date.
 * csv-file is generated by https://github.com/WikidataQuality/ConstraintsFromTemplates/blob/master/csvScriptBuilder.py
 */
class UpdateTable extends \Maintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = "Reads csv file and writes its contents into constraints table";
		$this->addOption( 'csv-file', 'csv file that contains constraints parsed from the property talk pages.', true, true );
		$this->setBatchSize( 1000 );
	}

	public function execute(){
		$csvFile = fopen( $this->getOption( 'csv-file' ), 'rb' );
		if( !$csvFile ) {
			exit( "Error while opening csv-file" );
		}

		$constraintRepo = ConstraintReportFactory::getDefaultInstance()->getConstraintRepository();
		$constraintRepo->deleteAll( $this->mBatchSize );
		$this->insertValues( $constraintRepo, $csvFile );
		fclose( $csvFile );
	}

	private function insertValues( $constraintRepo, $csvFile ) {

		$i = 0;
		$db = wfGetDB( DB_MASTER );
		$accumulator = array();
		while ( true ) {
			$data = fgetcsv( $csvFile );
			if ( $data === false || ++$i % $this->mBatchSize === 0 ) {
				$constraintRepo->insertBatch( $accumulator );

				$db->commit( __METHOD__, 'flush' );
				wfWaitForSlaves();

				if ( !$this->isQuiet() ) {
					$this->output( "\r\033[K" );
					$this->output( "$i rows inserted" );
				}

				$accumulator = array();

				if ( $data === false ) {
					break;
				}
			}

			$constraintParameters = (array) json_decode( $data[3] );
			$propertyId = new PropertyId( 'P' . $data[1] );
			$accumulator[] = new Constraint( $data[0], $propertyId, $data[2], $constraintParameters );
		}

	}
}

// @codeCoverageIgnoreStart
$maintClass = 'WikibaseQuality\ConstraintReport\Maintenance\UpdateTable';
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd