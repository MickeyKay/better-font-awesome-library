<?php
/**
 * Mixed Block Editor and wp_editor() fixture for browser regression testing.
 *
 * Define BFAL_BROWSER_FIXTURE_EDITOR_COUNT before loading this file to render
 * multiple traditional editors. Define BFAL_BROWSER_FIXTURE_DYNAMIC as true to
 * include a client-initialized editor and picker trigger.
 *
 * @package Better_Font_Awesome_Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bfal_fixture_editor_count = defined( 'BFAL_BROWSER_FIXTURE_EDITOR_COUNT' )
	? max( 1, (int) BFAL_BROWSER_FIXTURE_EDITOR_COUNT )
	: 1;

add_action(
	'add_meta_boxes_post',
	static function () use ( $bfal_fixture_editor_count ) {
		for ( $index = 1; $index <= $bfal_fixture_editor_count; ++$index ) {
			$editor_id = 'bfal_fixture_editor_' . $index;
			add_meta_box(
				'bfal-wp-editor-fixture-' . $index,
				'BFAL traditional editor fixture ' . $index,
				static function () use ( $editor_id, $index ) {
					wp_editor(
						'Traditional editor fixture content ' . $index . '.',
						$editor_id,
						array(
							'media_buttons' => true,
							'textarea_rows' => 4,
						)
					);
				},
				'post',
				'normal',
				'default'
			);
		}

		if ( defined( 'BFAL_BROWSER_FIXTURE_DYNAMIC' ) && BFAL_BROWSER_FIXTURE_DYNAMIC ) {
			add_meta_box(
				'bfal-dynamic-wp-editor-fixture',
				'BFAL dynamic editor fixture',
				static function () {
					?>
					<button type="button" class="button" id="bfal-add-dynamic-editor">Initialize dynamic editor</button>
					<div id="bfal-dynamic-editor-host" hidden>
						<div id="wp-bfal_dynamic_editor-media-buttons" class="wp-media-buttons">
							<?php do_action( 'media_buttons', 'bfal_dynamic_editor' ); ?>
						</div>
						<textarea id="bfal_dynamic_editor">Dynamic editor fixture content.</textarea>
					</div>
					<script>
						document.getElementById( 'bfal-add-dynamic-editor' ).addEventListener( 'click', function() {
							var host = document.getElementById( 'bfal-dynamic-editor-host' );
							host.hidden = false;
							wp.editor.initialize( 'bfal_dynamic_editor', {
								tinymce: true,
								quicktags: true
							} );
							this.disabled = true;
						} );
					</script>
					<?php
				},
				'post',
				'normal',
				'default'
			);
		}
	}
);

if ( defined( 'BFAL_BROWSER_FIXTURE_DYNAMIC' ) && BFAL_BROWSER_FIXTURE_DYNAMIC ) {
	add_action( 'admin_enqueue_scripts', 'wp_enqueue_editor' );
}
