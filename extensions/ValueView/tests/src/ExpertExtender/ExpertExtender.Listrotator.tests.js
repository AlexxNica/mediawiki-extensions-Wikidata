/**
 * @licence GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Listrotator' );

	testExpertExtenderExtension.all(
		ExpertExtender.Listrotator,
		function() {
			return new ExpertExtender.Listrotator( '', [ { value: 'value', label: 'label' } ] );
		}
	);

	QUnit.test( 'supports custom values', function( assert ) {
		assert.expect( 1 );
		var getUpstreamValue = function() {
			return {
				custom: true,
				value: 'custom value',
				label: 'label for custom value'
			};
		};
		var $extender = $( '<div />' );

		var listrotator = new ExpertExtender.Listrotator(
			'',
			[ { value: 'fixed value', label: 'label for fixed value' } ],
			null,
			getUpstreamValue
		);

		listrotator.init( $extender );
		listrotator.draw();

		assert.equal( listrotator.getValue(), 'custom value' );
	} );

	QUnit.asyncTest( 'supports switching away from custom values', function( assert ) {
		assert.expect( 2 );
		var onValueChange = sinon.spy();
		var upstreamValue = {
			custom: true,
			value: 'custom value',
			label: 'label for custom value'
		};
		var getUpstreamValue = function() {
			return upstreamValue;
		};
		var $extender = $( '<div />' );

		var listrotator = new ExpertExtender.Listrotator(
			'',
			[ { value: 'fixed value', label: 'label for fixed value' } ],
			onValueChange,
			getUpstreamValue
		);

		listrotator.init( $extender );
		listrotator.draw();
		listrotator.rotator._setValue( 'fixed value' );

		setTimeout( function() {
			sinon.assert.calledOnce( onValueChange );
			assert.equal( listrotator.getValue(), 'fixed value' );

			QUnit.start();
		}, 200 );

	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit
);
