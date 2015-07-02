/*!
 * @author: Pojo Team
 */
/* global jQuery */

;( function( $ ) {
	'use strict';

	var Pojo_Forms_Admin_App = {
		lastRepeaterFieldID: 1,
		
		cache: {
			$document: $( document ),
			$window: $( window )
		},

		cacheElements: function() {},

		buildElements: function() {},

		bindEvents: function() {
			var $self = this;

			$( document ).on( 'pojo_metabox_repeater_new_item', '.atmb-repeater-row', function() {
				$( this ).find( '.atmb-field-row.atmb-field_id .atmb-field-text' ).val( $self._getNewFieldID() );
			} );
		},
		
		_getNewFieldID: function() {
			return this.lastRepeaterFieldID++;
		},
		
		_storeLastRepeaterID: function() {
			var $self = this;
			
			var $allRepeaterIDs = $( '.atmb-field-row.atmb-field_id .atmb-field-text' );
			
			// Store the last
			$allRepeaterIDs.each( function() {
				var currentID = $( this ).val();
				if ( '' === currentID ) {
					return;
				}

				if ( $self.lastRepeaterFieldID < currentID ) {
					$self.lastRepeaterFieldID = parseInt( currentID, 0 ) + 1;
				}
			} );

			$allRepeaterIDs.each( function() {
				var currentID = $( this ).val();
				if ( '' === currentID ) {
					$( this ).val( $self._getNewFieldID() );
				}
			} );
		},

		init: function() {
			this.cacheElements();
			this.buildElements();
			this._storeLastRepeaterID();
			this.bindEvents();
		}
	};

	$( document ).ready( function( $ ) {
		Pojo_Forms_Admin_App.init();
	} );

}( jQuery ) );
