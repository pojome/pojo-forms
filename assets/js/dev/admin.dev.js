/*!
 * @author: Pojo Team
 */
/* global jQuery */

;( function( $, undefined ) {
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
			var self = this;

			$( document ).on( 'pojo_metabox_repeater_new_item', '.atmb-repeater-row', function() {
				var id = self._getNewFieldID();
				$( this )
					.find( 'input.atmb-field-hidden.atmb-field_id' )
					.val( id );
				
				$( this )
					.find( 'div.atmb-field-row.atmb-shortcode input.atmb-field-text' )
					.val( self._getFieldShortcode( id ) );
				
				$( this )
					.find( 'div.atmb-field-row.atmb-type select' )
					.trigger( 'change' );
			} )
			
				.on( 'change', '#pojo-forms-form li.atmb-repeater-row div.atmb-field-row.atmb-type select', function() {
					self._onChangeFieldType( self, $( this ) );
				} );
			
			$( 'li.atmb-repeater-row div.atmb-field-row.atmb-type select', '#pojo-forms-form' ).trigger( 'change' );
		},
		
		_onChangeFieldType: function( self, $element ) {
			var showFieldsPerElements = {
					defaults: [],
					textarea: [ 'textarea_rows' ],
					number: [ 'number_min', 'number_max' ],
					checkbox: [ 'inline', 'choices', 'default_value' ],
					radio: [ 'inline', 'choices' ],
					dropdown: [ 'choices', 'multiple', 'first_unselectable_item' ],
					file: [ 'file_types', 'file_sizes' ]
				},
				hideFields = [ 'textarea_rows', 'default_value', 'inline', 'choices', 'multiple', 'first_unselectable_item', 'number_min', 'number_max', 'file_types', 'file_sizes' ];
			
			var $wrapper = $element.closest( 'li.atmb-repeater-row' );
			
			self._hideOrDisplayFields( $wrapper, hideFields, 'hide' );
			
			if ( undefined === showFieldsPerElements[ $element.val() ] ) {
				return;
			}

			self._hideOrDisplayFields( $wrapper, showFieldsPerElements[ $element.val() ], 'display' );
		},
		
		_hideOrDisplayFields: function( $wrapper, list_fields, action ) {
			var $currentField;
			$.each( list_fields, function( index, field_name ) {
				$currentField = $wrapper.find( 'div.atmb-field-row.atmb-' + field_name );
				
				if ( 'display' === action ) {
					$currentField.fadeIn( 'fast' );
				} else {
					$currentField.hide();
				}
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
