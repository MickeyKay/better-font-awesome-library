<?php

require_once __DIR__ . '/BfalTestCase.php';

class EditorStylesTest extends BfalTestCase {
	public function test_automatic_admin_enqueue_remains_active_on_block_editor_screens() {
		$this->get_instance( array( 'include_v4_shim' => true ) );
		$GLOBALS['bfa_test_is_block_editor'] = true;

		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertArrayHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
	}

	public function test_font_awesome_stylesheet_tags_use_anonymous_cors() {
		$this->get_instance();
		$main = '<link rel="stylesheet" id="bfa-font-awesome-css" href="https://use.fontawesome.com/all.css" media="all" />';
		$shim = '<link rel="stylesheet" id="bfa-font-awesome-v4-shim-css" href="https://use.fontawesome.com/v4-shims.css" media="all" />';

		$this->assertSame( 1, substr_count( apply_filters( 'style_loader_tag', $main, 'bfa-font-awesome' ), 'crossorigin="anonymous"' ) );
		$this->assertSame( 1, substr_count( apply_filters( 'style_loader_tag', $shim, 'bfa-font-awesome-v4-shim' ), 'crossorigin="anonymous"' ) );
	}

	public function test_unrelated_stylesheet_tag_is_byte_for_byte_unchanged() {
		$this->get_instance();
		$tag = "<link data-example='one' rel=\"stylesheet\" href=\"theme.css\" />\n";

		$this->assertSame( $tag, apply_filters( 'style_loader_tag', $tag, 'theme-style' ) );
	}

	public function test_preceding_preload_link_is_unchanged_and_main_stylesheet_is_targeted() {
		$this->get_instance();
		$preload = '<link rel="preload" href="https://optimizer.test/font.css" as="font">' . "\n";
		$main    = '<link rel="stylesheet" id="bfa-font-awesome-css" href="https://use.fontawesome.com/all.css">';

		$this->assertSame(
			$preload . '<link rel="stylesheet" id="bfa-font-awesome-css" href="https://use.fontawesome.com/all.css" crossorigin="anonymous">',
			apply_filters( 'style_loader_tag', $preload . $main, 'bfa-font-awesome' )
		);
	}

	public function test_preceding_stylesheet_link_is_unchanged_and_main_stylesheet_is_targeted() {
		$this->get_instance();
		$theme = '<link data-optimizer="kept" rel="stylesheet" id="theme-css" href="theme.css">' . "\n";
		$main  = '<link rel="stylesheet" id="bfa-font-awesome-css" href="https://use.fontawesome.com/all.css">';

		$this->assertSame(
			$theme . '<link rel="stylesheet" id="bfa-font-awesome-css" href="https://use.fontawesome.com/all.css" crossorigin="anonymous">',
			apply_filters( 'style_loader_tag', $theme . $main, 'bfa-font-awesome' )
		);
	}

	public function test_following_link_is_unchanged_when_main_stylesheet_is_targeted() {
		$this->get_instance();
		$main      = '<link rel="stylesheet" id="bfa-font-awesome-css" href="https://use.fontawesome.com/all.css">';
		$following = "\n" . '<link rel="stylesheet" id="theme-css" href="theme.css" data-after="kept">';

		$this->assertSame(
			'<link rel="stylesheet" id="bfa-font-awesome-css" href="https://use.fontawesome.com/all.css" crossorigin="anonymous">' . $following,
			apply_filters( 'style_loader_tag', $main . $following, 'bfa-font-awesome' )
		);
	}

	public function test_only_exact_expected_id_is_changed_among_multiple_links() {
		$this->get_instance();
		$input = '<link rel="preload" id="optimizer-preload" href="preload.css">' . "\n"
			. '<link rel="stylesheet" id="bfa-font-awesome-css-copy" crossorigin="use-credentials" href="copy.css">' . "\n"
			. '<link rel="stylesheet" id="bfa-font-awesome-css" crossorigin="use-credentials" href="all.css">' . "\n"
			. '<link rel="stylesheet" id="theme-css" href="theme.css">';
		$expected = '<link rel="preload" id="optimizer-preload" href="preload.css">' . "\n"
			. '<link rel="stylesheet" id="bfa-font-awesome-css-copy" crossorigin="use-credentials" href="copy.css">' . "\n"
			. '<link rel="stylesheet" id="bfa-font-awesome-css" crossorigin="anonymous" href="all.css">' . "\n"
			. '<link rel="stylesheet" id="theme-css" href="theme.css">';

		$this->assertSame( $expected, apply_filters( 'style_loader_tag', $input, 'bfa-font-awesome' ) );
	}

	public function test_allowed_handle_without_expected_id_returns_complete_markup_unchanged() {
		$this->get_instance();
		$input = '<link rel="preload" id="optimizer-preload" href="preload.css">' . "\n"
			. '<link rel="stylesheet" id="different-css" href="all.css">' . "\n"
			. '<link rel="stylesheet" id="theme-css" href="theme.css">';

		$this->assertSame( $input, apply_filters( 'style_loader_tag', $input, 'bfa-font-awesome' ) );
		$this->assertSame( $input, apply_filters( 'style_loader_tag', $input, 'bfa-font-awesome-v4-shim' ) );
	}

	public function test_existing_anonymous_cors_attribute_is_idempotent() {
		$this->get_instance();
		$tag  = '<link rel="stylesheet" id="bfa-font-awesome-css" crossorigin="anonymous" href="all.css" />';
		$once = apply_filters( 'style_loader_tag', $tag, 'bfa-font-awesome' );

		$this->assertSame( 1, substr_count( $once, 'crossorigin="anonymous"' ) );
		$this->assertSame( $once, apply_filters( 'style_loader_tag', $once, 'bfa-font-awesome' ) );
	}

	public function test_other_cors_value_is_normalized_without_changing_other_attributes() {
		$this->get_instance();
		$tag = '<link data-before="kept" id="bfa-font-awesome-css" crossorigin="use-credentials" rel="stylesheet" href="all.css" data-after="also-kept">';

		$this->assertSame(
			'<link data-before="kept" id="bfa-font-awesome-css" crossorigin="anonymous" rel="stylesheet" href="all.css" data-after="also-kept">',
			apply_filters( 'style_loader_tag', $tag, 'bfa-font-awesome' )
		);
	}

	public function test_v4_shim_exact_target_normalizes_and_remains_idempotent() {
		$this->get_instance();
		$tag  = '<link id="bfa-font-awesome-v4-shim-css" rel="stylesheet" crossorigin="use-credentials" href="v4-shims.css">';
		$once = apply_filters( 'style_loader_tag', $tag, 'bfa-font-awesome-v4-shim' );

		$this->assertSame(
			'<link id="bfa-font-awesome-v4-shim-css" rel="stylesheet" crossorigin="anonymous" href="v4-shims.css">',
			$once
		);
		$this->assertSame( $once, apply_filters( 'style_loader_tag', $once, 'bfa-font-awesome-v4-shim' ) );
	}

	/**
	 * @dataProvider unexpected_stylesheet_markup_provider
	 */
	public function test_unexpected_markup_fails_safely( $markup ) {
		$this->get_instance();

		$this->assertSame( $markup, apply_filters( 'style_loader_tag', $markup, 'bfa-font-awesome' ) );
	}

	public function unexpected_stylesheet_markup_provider() {
		return array(
			'empty'          => array( '' ),
			'plain text'     => array( 'not a link tag' ),
			'incomplete tag' => array( '<link rel="stylesheet"' ),
			'other tag'      => array( '<style>.fa { display: block; }</style>' ),
		);
	}

	public function test_cors_filter_is_registered_once_at_priority_ten_for_two_arguments() {
		$this->get_instance();

		$this->assertTrue( has_filter( 'style_loader_tag' ) );
		$this->assertCount( 1, $GLOBALS['bfa_test_filter_callbacks']['style_loader_tag'] );
		$this->assertSame( 10, $GLOBALS['bfa_test_filter_callbacks']['style_loader_tag'][0]['priority'] );
		$this->assertSame( 2, $GLOBALS['bfa_test_filter_callbacks']['style_loader_tag'][0]['accepted_args'] );
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
