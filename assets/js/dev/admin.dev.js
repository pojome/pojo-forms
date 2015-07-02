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
				var id = $self._getNewFieldID();
				$( this )
					.find( 'input.atmb-field-hidden.atmb-field_id' )
					.val( id );
				
				$( this )
					.find( 'div.atmb-field-row.atmb-shortcode input.atmb-field-text' )
					.val( $self._getFieldShortcode( id ) );
			} );
		},
		
		_getNewFieldID: function() {
			return this.lastRepeaterFieldID++;
		},
		
		_getFieldShortcode: function( id ) {
			return '[form-field-' + id + ']';
		},
		
		_storeLastRepeaterID: function() {
			var $self = this;
			
			var $allRepeaterIDs = $( '#pojo-forms-form' ).find( 'input.atmb-field-hidden.atmb-field_id' );
			
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
					currentID = $self._getNewFieldID();
					$( this ).val( currentID );
				}
				
				$( this )
					.closest( 'div.atmb-button-collapse' )
					.find( 'div.atmb-field-row.atmb-shortcode input.atmb-field-text' )
					.val( $self._getFieldShortcode( currentID ) );
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
