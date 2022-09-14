/**
 * Better Font Awesome Library admin JS.
 *
 * @since       1.0.3
 *
 * @package     Better Font Awesome Library
 */

( function( $ ) {

	var icons = Object.values( bfa_vars.fa_icons );

	function get_icon_by_title( title ) {
		return icons.find( function( icon ) {
			return icon.title == title;
		});
	}

	function icon_shortcode( icon ) {
		var icon_style_string = icon.style ? ' style="' + icon.style + '"' : '';
		return '[icon name="' + icon.slug + '"' + icon_style_string + ' class="" unprefixed_class=""]';
	}

	$( function() {

        // We initialize on click instead of document.ready to ensure
        // that BFA triggers still work when dynamically loaded later
        // (e.g. repeatable fields/editors).
        $( 'body' ).on( 'click', '.bfa-iconpicker', function(e) {

            var $iconPicker = $( this );

            // Initialize if not already initialized.
            $iconPicker.not( ' .initialized' )
                .addClass( 'initialized' )
        		.iconpicker({
        			placement: 'bottomLeft',
        			hideOnSelect: true,
        			icons: icons,
        			fullClassFormatter: function( icon_title ) {
        				var classes = [];
        				var icon = get_icon_by_title( icon_title );

        				return icon.base_class;
        			},
        		})

                // Clean up human-readable icon names.
        		.find( '.iconpicker-item' ).each( function() {
        			var $item = $( this );
        			var title = $item.attr( 'title' ).replace( '.', '' );

        			$item.attr( 'title', title );
        		});

                // Place cursor focus on the search input.
                .on( 'iconpickerShown', function( e ) {
        			$iconPicker.find( '.iconpicker-search' ).trigger( 'focus');
        		});

        		// Handle inserting selected icon into editor.
        		.on( 'iconpickerSelected', function( e ) {
        			var icon_title = e.iconpickerItem.title.replace( '.', '' );
        			var icon = get_icon_by_title( icon_title );
        			wp.media.editor.insert( icon_shortcode( icon ) );
        		});
        });
	});

} )( jQuery );
