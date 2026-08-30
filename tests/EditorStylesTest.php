<?php

require_once __DIR__ . '/BfalTestCase.php';

class EditorStylesTest extends BfalTestCase {
	public function test_automatic_admin_enqueue_skips_block_editor_screens() {
		$this->get_instance( array( 'include_v4_shim' => true ) );
		$GLOBALS['bfa_test_is_block_editor'] = true;

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertArrayNotHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayNotHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}

	public function test_direct_registration_remains_available_on_block_editor_screens() {
		$library = $this->get_instance( array( 'include_v4_shim' => true ) );
		$GLOBALS['bfa_test_is_block_editor'] = true;

		$library->register_font_awesome_css();

		$this->assertArrayHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}

	public function test_normal_admin_screen_retains_automatic_font_awesome_loading() {
		$this->get_instance( array( 'include_v4_shim' => true ) );

		do_action( 'admin_enqueue_scripts', 'plugins.php' );

		$this->assertArrayHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}

	public function test_missing_current_screen_fails_open_without_errors() {
		$this->get_instance( array( 'include_v4_shim' => true ) );
		$GLOBALS['bfa_test_has_current_screen'] = false;

		do_action( 'admin_enqueue_scripts', 'plugins.php' );

		$this->assertArrayHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}

	public function test_frontend_automatic_loading_remains_unchanged() {
		$this->get_instance( array( 'include_v4_shim' => true ) );
		$GLOBALS['bfa_test_is_block_editor'] = true;

		do_action( 'wp_enqueue_scripts' );

		$this->assertArrayHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}

	public function test_v4_shim_disabled_registers_only_the_main_stylesheet() {
		$library = $this->get_instance( array( 'include_v4_shim' => false ) );

		$library->register_font_awesome_css();

		$this->assertArrayHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayNotHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}

	public function test_editor_styles_preserve_existing_entries_and_wordpress_deduplication() {
		$GLOBALS['bfa_test_editor_styles'][] = 'theme-editor.css';
		$library = $this->get_instance( array( 'include_v4_shim' => true ) );

		$library->add_editor_styles();
		$library->add_editor_styles();

		$this->assertSame(
			array(
				'theme-editor.css',
				'https://use.fontawesome.com/releases/v5.15.4/css/all.css',
				'https://use.fontawesome.com/releases/v5.15.4/css/v4-shims.css',
			),
			get_editor_stylesheets()
		);
	}

	public function test_automatic_admin_callback_keeps_established_priority_and_is_removable() {
		$library = $this->get_instance( array( 'include_v4_shim' => true ) );

		$this->assertSame( 15, has_action( 'admin_enqueue_scripts', array( $library, 'register_font_awesome_css' ) ) );
		$this->assertTrue( remove_action( 'admin_enqueue_scripts', array( $library, 'register_font_awesome_css' ), 15 ) );
		do_action( 'admin_enqueue_scripts', 'plugins.php' );

		$this->assertArrayNotHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayNotHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}
}
