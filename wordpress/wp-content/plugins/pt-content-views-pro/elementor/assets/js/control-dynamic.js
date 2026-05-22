( function ( $ ) {
	$( document ).on( 'contentviews_dynamic_init', function ( event, object ) {
		var ID = '#elementor-control-default-' + object.data._cid;
		setTimeout( function () {

			var isLFHas = $( ID ).closest( '.elementor-control' ).hasClass( 'elementor-control-hasLF' );
			if ( isLFHas ) {
				var obj = checkLiveFilter_Elementor( object );
				var whichVal = Object.keys( obj ).length ? JSON.stringify( obj ) : '';
				$( ID ).val( whichVal );
				$( ID ).trigger( 'input' );
			}

		}, 100 ); /* wait for control to be added to dom */
	} );

	// Same as checkLiveFilter() in block js
	var checkLiveFilter_Elementor = function ( object ) {
		var obj = { };

		function get_order() {
			let lf1 = object.relatedAtts['lfSortOpts'];
			if ( Array.isArray( lf1 ) && lf1.length ) {
				obj['_orderby'] = 1;
			}
		}

		function get_search() {
			if ( object.relatedAtts['searchLfEnable'] ) {
				obj['_search'] = 1;
			}
		}

		function get_fields() {
			// get fields in elementor repeater		
			[ 'filterCtf', 'sortCtf' ].map( ( akey ) => {
				let keyVal = object.relatedAtts[akey];
				if ( typeof keyVal === 'object' && keyVal.models ) {
					keyVal.models.map( ( item ) => {
						if ( typeof item.attributes === 'object' && item.attributes.lfenable ) {
							obj[akey === 'sortCtf' ? '_orderby' : item.attributes.key] = 1;
						}
					} );
				}
			} );
		}


		function get_taxonomy() {
			Object.keys( contentviews_dynamic_localize.taxo_list ).map( ( taxo ) => {
				if ( object.relatedAtts[taxo + '__LfEnable'] ) {
					obj['tx_' + taxo] = 1;
				}
			} );
		}

		// rearrange in order (search > taxonomy > custom field > sort) to match default output
		get_search();
		get_taxonomy();
		get_fields();
		get_order();

		return obj;
	};

}( jQuery ) );
