<?php

require_once __DIR__ . '/BfalTestCase.php';

class EditorStylesTest extends BfalTestCase {
	public function test_font_awesome_admin_styles_are_not_enqueued_on_block_editor_screens() {
		$this->get_instance( array( 'include_v4_shim' => true ) );
		$GLOBALS['bfa_test_is_admin']        = true;
		$GLOBALS['bfa_test_is_block_editor'] = true;

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertArrayNotHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayNotHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}

	public function test_frontend_styles_are_unchanged_for_block_editor_requests() {
		$this->get_instance( array( 'include_v4_shim' => true ) );
		$GLOBALS['bfa_test_is_admin']        = false;
		$GLOBALS['bfa_test_is_block_editor'] = true;

		do_action( 'wp_enqueue_scripts' );

		$this->assertArrayHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}

	public function test_font_awesome_editor_styles_are_scoped_to_tinymce() {
		$this->get_instance( array( 'include_v4_shim' => true ) );

		$this->assertSame(
			array(),
			$GLOBALS['bfa_test_editor_styles'],
			'Font Awesome styles must not be registered as global editor styles.'
		);
		$this->assertArrayHasKey( 'mce_css', $GLOBALS['bfa_test_filter_callbacks'] );
		$this->assertSame(
			'wp-content.css,https://use.fontawesome.com/releases/v5.15.4/css/all.css,https://use.fontawesome.com/releases/v5.15.4/css/v4-shims.css',
			apply_filters( 'mce_css', 'wp-content.css' )
		);
		$this->assertSame(
			'https://use.fontawesome.com/releases/v5.15.4/css/all.css,https://use.fontawesome.com/releases/v5.15.4/css/v4-shims.css',
			apply_filters( 'mce_css', '' )
		);
	}
}
