( function ( $ ) {
	$( document ).on( 'contentviews_sortable_init', function ( event, obj ) {
		var ID = '#elementor-control-default-' + obj.data._cid;
		setTimeout( function () {
			var IDSelect2 = $( ID ).select2( {
				dropdownCssClass: 'contentviews-select2-dropdown',
				initSelection: function (element, callback) {
                    if (!obj.multiple) {
                        callback({id: '', text: ''});
                    }else{
						callback({id: '', text: ''});
					}
					var ids = [];
                    if(!Array.isArray(obj.currentID) && obj.currentID != ''){
						 ids = [obj.currentID];
					}else if(Array.isArray(obj.currentID)){
						 ids = obj.currentID.filter(function (el) {
							return el != null;
						})
					}

					var isLFArrange = $( ID ).closest( '.elementor-control' ).hasClass( 'elementor-control-lfArrange' );
					var isFieldPosition = $( ID ).closest( '.elementor-control' ).hasClass( 'elementor-control-fieldsPosition' );
					if ( isLFArrange || isFieldPosition ) {
						// always recheck for enabled options, to update this list (when change post type, toggle live filters)
						var currentFields = isLFArrange ? get_enabled_livefilters() : get_enabled_fields();
						// remove options that not enabled anymore
						ids = ids.filter( ( field ) => currentFields.includes( field ) );
						// add options that newly enabled
						currentFields.forEach( function ( field ) {
							if ( !ids.includes( field ) ) {
								ids.push( field );
							}
						} );
					}

                    if ( ids.length > 0 ) {
						let eaelSelect2Options = '';
                        ids.forEach( function ( item, index ) {
							const key = item;
							const value = isFieldPosition ? fieldArr[item] : ( isLFArrange ? get_lf_text( item ) : item );
							eaelSelect2Options += `<option selected="selected" value="${key}">${value}</option>`;
						} )

						element.append( eaelSelect2Options );

						// before drag and drop, value is not submitted yet.
						// to submit: $( ID ).trigger( "change" );
                    }
                }
			} );
			
			
			//Manual Sorting : Select2 drag and drop : starts
            setTimeout(function (){
                IDSelect2.next().children().children().children().sortable({
                    containment: 'parent',
                    stop: function(event, ui) {
                        ui.item.parent().children('[title]').each(function() {
                            var title = $(this).attr('title');
                            var original = $('option:contains(' + title + ')', IDSelect2).first();
                            original.detach();
                            IDSelect2.append(original)
                        });
                        IDSelect2.change();
                    }
                });

                $(ID).on("select2:select", function(evt) {
                    var element = evt.params.data.element;
                    var $element = $(element);

                    $element.detach();
                    $(this).append($element);
                    $(this).trigger("change");
                });
            },200);
            //Manual Sorting : Select2 drag and drop : ends


		}, 100 );

	} );

	/*--- FOR FIELDS POSITION ---*/
	// Reload when toggle fields
	var switcher_selector = "#elementor-controls > .elementor-control-separator1 ~ [class*='elementor-control-show']";
	var fieldArr = contentviews_sortable_localize.fields_list;
	var reload_enabled_fields = function () {
		if ( parent.document ) {
			parent.document.addEventListener( "mousedown", function ( e ) {
				if ( e.target.matches( switcher_selector + ' *' ) ) {
					var $wrapper = $( e.target ).closest( '#elementor-controls' );
					var $fieldPosition = $( '.elementor-control-fieldsPosition select', $wrapper );

					var get_option = function ( value1 ) {
						return $fieldPosition.find( 'option[value="' + value1 + '"]' ).first();
					};

					var $clickElement = $( e.target ).closest( '.elementor-control-type-switcher' );
					// wait so checkbox is updated
					setTimeout( function () {
						var $input = $clickElement.find( 'input[type="checkbox"]' );
						var clickValue = $input.attr( 'data-setting' );

						if ( clickValue === 'showTaxonomy' ) {
							return;
						}

						var $option = $fieldPosition.find( 'option[value="' + clickValue + '"]' ).first();
						if ( $input.is( ':checked' ) ) {
							if ( $option.length === 0 ) {
								var newOption = new Option( fieldArr[clickValue], clickValue, true, true );

								let this_idx = Object.keys( fieldArr ).indexOf( clickValue ),
									prev_field = Object.keys( fieldArr )[this_idx - 1],
									next_field = Object.keys( fieldArr )[this_idx + 1],
									$prev_option = prev_field ? get_option( prev_field ) : null,
									$next_option = next_field ? get_option( next_field ) : null;

								if ( this_idx === 0 ) {
									$fieldPosition.prepend( newOption ).trigger( 'change' );
								}
								else if ( $prev_option !== null && $prev_option.length > 0 ) {
									$prev_option.after( newOption );
									$fieldPosition.trigger( 'change' );
								}
								else if ( $next_option !== null && $next_option.length > 0 ) {
									$next_option.before( newOption );
									$fieldPosition.trigger( 'change' );
								} else {
									$fieldPosition.append( newOption ).trigger( 'change' );
								}
							}
						} else {
							if ( $option.length > 0 ) {
								$option.detach();
								$fieldPosition.trigger( 'change' );
							}
						}
					}, 1000 );
				}
			} );
		}
	};
	reload_enabled_fields();

	// Get enabled fields
	var get_enabled_fields = function () {
		var enabledFields = [ ];
		$( switcher_selector ).each( function () {
			if ( !$( this ).hasClass( 'elementor-hidden-control' ) && !$( this ).hasClass( 'elementor-control-showTaxonomy' ) ) {
				let $input = $( this ).find( 'input[type="checkbox"]' );
				if ( $input.is( ':checked' ) ) {
					enabledFields.push( $input.attr( 'data-setting' ) );
				}
			}
		} );
		return enabledFields;
	};


	/*--- FOR HAS LF ---*/
	function get_enabled_livefilters() {
		var lfInput = try_parse_json( $( '#elementor-controls .elementor-control-hasLF input' ).val() );
		return ( typeof lfInput === 'object' ) ? Object.keys( lfInput ) : [ ];
	}

	function try_parse_json( str ) {
		try {
			return JSON.parse( str );
		} catch ( e ) {
			return false;
		}
	}

	// same as getLFOptions() in block js
	function get_lf_text( key ) {
		var lfText = '';

		const arr = contentviews_dynamic_localize.lftext_arr;
		if ( arr[key] ) {
			lfText = arr[key];
		} else if ( key.includes( 'tx_' ) ) {
			let taxo = key.replace( 'tx_', '' );
			lfText = contentviews_dynamic_localize.taxo_list[taxo] || taxo;
		} else {
			lfText = key;
		}

		return lfText;
	}

}( jQuery ) );
