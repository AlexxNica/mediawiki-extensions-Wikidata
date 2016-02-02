/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $ ) {
	'use strict';

	/**
	 * Toolbar controller widget
	 *
	 * The toolbar controller initializes and manages toolbar widgets. Toolbar definitions are
	 * registered via the jQuery.toolbarcontroller.definition() method. When initializing the
	 * toolbar controller, the ids of the registered toolbar definitions that the controller shall
	 * initialize are passed as options.
	 * The toolbar controller does not parse the existing DOM structure when being initialized, it
	 * listens to the events registered by a toolbar definition. In order to have the desired
	 * toolbars created by the controller, it needs to be initialized on some parent node of the DOM
	 * structure it should manage toolbars in before the events defined in toolbar definitions are
	 * triggered (e.g. before a widget, a toolbar shall interact with, is created).
	 *
	 * @since 0.4
	 *
	 * @constructor
	 *
	 * @param {Object} [options={}]
	 * @param {Object} [options.toolbars={}]
	 *        Lists of toolbar definition ids indexed by their corresponding toolbar type.
	 */
	$.widget( 'wikibase.toolbarcontroller', {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			toolbars: null
		},

		/**
		 * @see jQuery.Widget._create
		 *
		 * @throws {Error} in case a given toolbar ID is not registered for the toolbar type given.
		 */
		_create: function() {
			var self = this;

			this.options.toolbars = this.options.toolbars || {};

			$.each( this.options.toolbars, function( type, ids ) {
				$.each( ids, function( index, id ) {
					var def = $.wikibase.toolbarcontroller.definition( type, id );

					if ( !def ) {
						throw new Error( 'Missing toolbar controller definition for type "'
							+ type + '" with id "' + id + '"' );
					}

					$.each( def.events, function( eventNames, callback ) {
						self.registerEventHandler( type, id, eventNames, callback );
					} );
				} );
			} );
		},

		/**
		 * Registers an event handler.
		 *
		 * @since 0.5
		 *
		 * @param {string} toolbarType
		 * @param {string} toolbarId
		 * @param {string} eventNames Space-separated string containing the event names to register
		 *                 a callback handler for. It is assumed that this string is uniquely used
		 *                 for a toolbar type with a specific id: For one toolbar, no additional
		 *                 handler should be registered with exactly the same eventNames string.
		 * @param {Function} callback
		 *
		 * @throws {Error} if the callback provided in an event definition is not a function.
		 */
		registerEventHandler: function( toolbarType, toolbarId, eventNames, callback ) {
			if ( !$.isFunction( callback ) ) {
				throw new Error( 'No callback or known default action given for event "'
					+ eventNames + '"' );
			}

			var self = this;
			var def = $.wikibase.toolbarcontroller.definition( toolbarType, toolbarId );

			this.element.on( eventNames, def.selector, function( event ) {
				event.data = event.data || {};
				event.data.toolbar = {
					id: toolbarId,
					type: toolbarType
				};

				callback( event, self );
			} );
		},

		/**
		 * Destroys a toolbar.
		 *
		 * @since 0.5
		 *
		 * @param {jQuery.wikibase.toolbar} toolbar
		 */
		destroyToolbar: function( toolbar ) {
			// Toolbar might have been removed from the DOM already by some other destruction
			// mechanism.
			if ( toolbar ) {
				toolbar.destroy();
				if ( toolbar.option( '$container' ).get( 0 ) !== toolbar.element.get( 0 ) ) {
					toolbar.option( '$container' ).remove();
				}
				toolbar.element.off( '.' + toolbar.widgetName );
			}
		}

	} );
}( jQuery ) );
