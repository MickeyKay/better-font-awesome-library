/**
 * Better Font Awesome Library admin JS.
 *
 * @since       1.0.3
 *
 * @package     Better Font Awesome Library
 */

( function( $ ) {

    $( document ).ready( function() {

    	$( '.bfa-iconpicker' )
    		.iconpicker({
	    		placement: 'bottomLeft',
	    		hideOnSelect: true,
	    		animation: false,
	    		selectedCustomClass: 'selected',
	    		icons: bfa_vars.fa_icons,
	    		fullClassFormatter: function( val ) {
	    			return bfa_vars.fa_prefix + ' ' + bfa_vars.fa_prefix + '-' + val;
	    		},
	    	})
    		.on( 'click', function( e ) {
    			$( this ).find( '.iconpicker-search').focus();
    		})
	    	.on( 'iconpickerSelect', function( e ) {
	    		wp.media.editor.insert( icon_shortcode( e.iconpickerItem.context.title.replace( '.', '' ) ) );
	    	});

    });

    function icon_shortcode( icon ) {
        return '[icon name="' + icon + '" class="" unprefixed_class=""]';
    }

} )( jQuery );
