<?php

// autoload_psr4.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'Wikimedia\\Purtle\\Tests\\' => array($baseDir . '/extensions/Wikibase/purtle/tests/phpunit'),
    'Wikimedia\\Purtle\\' => array($baseDir . '/extensions/Wikibase/purtle/src'),
    'Wikidata\\' => array($baseDir . '/src'),
    'Wikibase\\View\\' => array($baseDir . '/extensions/Wikibase/view/src'),
    'Wikibase\\InternalSerialization\\' => array($vendorDir . '/wikibase/internal-serialization/src'),
    'Wikibase\\DataModel\\' => array($vendorDir . '/wikibase/data-model/src', $vendorDir . '/wikibase/data-model-serialization/src'),
    'WikibaseQuality\\Tests\\' => array($baseDir . '/extensions/Quality/tests/phpunit'),
    'WikibaseQuality\\Specials\\' => array($baseDir . '/extensions/Quality/specials'),
    'WikibaseQuality\\Api\\' => array($baseDir . '/extensions/Quality/api'),
    'WikibaseQuality\\' => array($baseDir . '/extensions/Quality/includes'),
    'ValueValidators\\' => array($vendorDir . '/data-values/validators/src'),
    'Serializers\\' => array($vendorDir . '/serialization/serialization/src/Serializers'),
    'PropertySuggester\\' => array($baseDir . '/extensions/PropertySuggester/src/PropertySuggester'),
    'Diff\\' => array($vendorDir . '/diff/diff/src'),
    'Deserializers\\' => array($vendorDir . '/serialization/serialization/src/Deserializers'),
    'DataValues\\Serializers\\' => array($vendorDir . '/data-values/serialization/src/Serializers'),
    'DataValues\\Geo\\' => array($vendorDir . '/data-values/geo/src'),
    'DataValues\\Deserializers\\' => array($vendorDir . '/data-values/serialization/src/Deserializers'),
    'DataTypes\\' => array($vendorDir . '/data-values/data-types/src'),
);
