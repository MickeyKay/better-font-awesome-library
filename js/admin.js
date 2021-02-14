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

		$( 'body' ).on( 'mousedown', '.bfa-iconpicker', function(e) { // Use mousedown even to allow for triggering click later without infinite looping.

			e.preventDefault();

			$( this ).not( ' .initialized' )
				.addClass( 'initialized' )
				.iconpicker({
					placement: 'bottomLeft',
					hideOnSelect: true,
					animation: false,
					selectedCustomClass: 'selected',
					icons: icons,
					fullClassFormatter: function( icon_title ) {
						var classes = [];
						var icon = get_icon_by_title( icon_title );

						return icon.base_class;
					},
				})
				.find( '.iconpicker-item' ).each( function() {
					var $item = $( this );
					var title = $item.attr( 'title' ).replace( '.', '' );

					$item.attr( 'title', title );
				});

			$( this ).trigger( 'click' );

		})
		.on( 'click', '.bfa-iconpicker', function(e) {
			e.preventDefault(); // Prevent scrolling to top.
			$( this ).find( '.iconpicker-search' ).focus();
		});

		// Set up icon insertion functionality.
		$( '.bfa-iconpicker' ).on( 'iconpickerSelected', function( e ) {
      var icon_title = e.iconpickerItem.title.replace( '.', '' );
			var icon = get_icon_by_title( icon_title );
			wp.media.editor.insert( icon_shortcode( icon ) );
		});
	});

} )( jQuery );
