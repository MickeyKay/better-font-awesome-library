<?php

require_once __DIR__ . '/BfalTestCase.php';

class KitCssDeliveryTest extends BfalTestCase {
	private const URL = 'https://kit.fontawesome.com/0123456789.css';
	private const OTHER_URL = 'https://kit.fontawesome.com/abcdef0123.css';
	private const HANDLE = 'bfa-font-awesome-kit';

	private function kit( $args = array() ) {
		return Better_Font_Awesome_Library::get_instance( array_merge( array(
			'release_channel' => '7.x',
			'asset_delivery' => 'kit-css',
			'kit_css_url' => self::URL,
			'include_v4_shim' => true,
		), $args ) );
	}

	/** @dataProvider valid_urls */
	public function test_official_embed_structure_is_preserved_verbatim( $url ) {
		$library = $this->kit( array( 'kit_css_url' => $url ) );
		$this->assertSame( 'kit-css', $library->get_asset_delivery() );
		$this->assertSame( $url, $library->get_stylesheet_url() );
		$this->assertSame( $url, apply_filters( 'mce_css', '' ) );
		$this->assertSame( array(), $library->get_errors() );
		$this->assert_no_network_or_refresh();
	}

	public static function valid_urls() {
		// Syntactic identifiers only; these URLs are never requested by tests.
		return array( array( self::URL ), array( self::OTHER_URL ), array( 'https://kit.fontawesome.com/KIT_ID.css' ), array( 'https://kit.fontawesome.com/synthetic-kit.css' ) );
	}

	public function test_kit_assets_are_exact_and_independent_of_free_metadata() {
		$library = $this->kit( array( 'load_tinymce_plugin' => false, 'release_data_provider' => function () { $this->fail( 'Asset registration must not read Free metadata.' ); } ) );
		$this->assertSame( 'kit-css', $library->get_asset_delivery() );
		$this->assertSame( '7.x', $library->get_release_channel() );
		$this->assertSame( self::URL, $library->get_stylesheet_url() );
		$this->assertSame( '', $library->get_stylesheet_url_v4_shim() );
		$library->register_font_awesome_css();
		$library->register_font_awesome_css();
		do_action( 'wp_enqueue_scripts' );
		do_action( 'admin_enqueue_scripts' );
		$library->register_v4_shim_inline_css();
		$this->assertSame( array( self::HANDLE ), array_keys( $GLOBALS['bfa_test_registered_styles'] ) );
		$this->assertSame( array( self::HANDLE ), array_keys( $GLOBALS['bfa_test_enqueued_styles'] ) );
		$this->assertSame( array( 'src' => self::URL, 'dependencies' => array(), 'version' => null ), $GLOBALS['bfa_test_registered_styles'][ self::HANDLE ] );
		$this->assertSame( array( self::HANDLE ), $GLOBALS['bfa_test_style_registration_calls'] );
		$this->assertSame( array(), get_editor_stylesheets() );
		$this->assertSame( array(), $GLOBALS['bfa_test_inline_styles'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_reads'] );
		$this->assert_no_network_or_refresh();
	}

	public function test_admin_picker_initialization_does_not_load_free_font_assets() {
		$this->kit();
		do_action( 'admin_enqueue_scripts' );
		$this->assertSame( array( self::HANDLE ), array_keys( $GLOBALS['bfa_test_registered_styles'] ) );
		$this->assertEqualsCanonicalizing( array( self::HANDLE, 'bfa-admin', 'fontawesome-iconpicker' ), array_keys( $GLOBALS['bfa_test_enqueued_styles'] ) );
		$this->assertSame( array(), get_editor_stylesheets() );
		$this->assertSame( array(), $GLOBALS['bfa_test_inline_styles'] );
		$this->assert_no_network_or_refresh();
	}

	public function test_cors_targets_only_the_exact_kit_stylesheet_without_free_integrity() {
		$library = $this->kit();
		$target = '<link id="bfa-font-awesome-kit-css" rel="stylesheet" href="' . self::URL . '" crossorigin="use-credentials">';
		$other = '<link id="theme-css" rel="stylesheet" href="theme.css">';
		$expected = $other . str_replace( 'use-credentials', 'anonymous', $target ) . $other;
		$actual = apply_filters( 'style_loader_tag', $other . $target . $other, self::HANDLE );
		$this->assertSame( $expected, $actual );
		$this->assertSame( $actual, apply_filters( 'style_loader_tag', $actual, self::HANDLE ) );
		$this->assertStringNotContainsString( 'integrity', $actual );
		foreach ( array(
			str_replace( self::URL, self::OTHER_URL, $target ),
			str_replace( self::URL, 'https://untrusted.test/kit.css', $target ),
			str_replace( 'stylesheet', 'preload', $target ),
			str_replace( 'kit-css', 'kit-css-copy', $target ),
			'<link rel="stylesheet" href="' . self::URL . '">',
			'not a link',
		) as $untargeted ) {
			$this->assertSame( $untargeted, apply_filters( 'style_loader_tag', $untargeted, self::HANDLE ) );
		}
		$this->assertSame( $target, apply_filters( 'style_loader_tag', $target, 'theme' ) );
		$free = '<link id="bfa-font-awesome-css" rel="stylesheet" href="free.css">';
		$this->assertSame( $free, apply_filters( 'style_loader_tag', $free, 'bfa-font-awesome' ) );
		$this->assert_no_network_or_refresh();
	}

	public function test_tinymce_preserves_theme_styles_and_does_not_duplicate_kit_or_callbacks() {
		$GLOBALS['bfa_test_editor_styles'] = array( 'theme-editor.css' );
		$library = $this->kit();
		$library->add_editor_styles();
		$library->load();
		$library->load();
		$this->assertCount( 1, $GLOBALS['bfa_test_filter_callbacks']['mce_css'] );
		$this->assertCount( 1, $GLOBALS['bfa_test_filter_callbacks']['style_loader_tag'] );
		$this->assertSame( array( 'theme-editor.css' ), get_editor_stylesheets() );
		$this->assertSame( self::URL, apply_filters( 'mce_css', '' ) );
		$this->assertSame( self::URL, apply_filters( 'mce_css', ' , ' ) );
		$expected = 'theme.css,other.css,' . self::URL;
		$this->assertSame( $expected, apply_filters( 'mce_css', 'theme.css,other.css' ) );
		$this->assertSame( $expected, apply_filters( 'mce_css', $expected ) );
		$present = 'theme.css, ' . self::URL . ' ,other.css';
		$this->assertSame( $present, apply_filters( 'mce_css', $present ) );
		$this->assert_no_network_or_refresh();
	}

	/** @dataProvider owner_modes */
	public function test_first_caller_mode_and_url_survive_later_callers_filters_and_load( $mode, $later_mode ) {
		$library = $this->kit( array( 'asset_delivery' => $mode ) );
		$initial_url = $library->get_stylesheet_url();
		$this->assertSame( $library, $this->kit( array( 'asset_delivery' => $later_mode, 'kit_css_url' => self::OTHER_URL ) ) );
		add_filter( 'bfa_init_args', function ( $args ) use ( $later_mode ) {
			$args['asset_delivery'] = $later_mode;
			$args['kit_css_url'] = self::OTHER_URL;
			$args['release_channel'] = '5.x';
			return $args;
		} );
		$library->load();
		$library->load();
		$this->assertSame( $mode, $library->get_asset_delivery() );
		$this->assertSame( '7.x', $library->get_release_channel() );
		$this->assertSame( $initial_url, $library->get_stylesheet_url() );
		if ( 'kit-css' === $mode ) {
			$this->assertSame( self::URL, apply_filters( 'mce_css', '' ) );
		} else {
			$this->assertSame( '', apply_filters( 'mce_css', '' ) );
		}
	}

	public static function owner_modes() {
		return array( array( 'kit-css', 'kit-css' ), array( 'kit-css', 'automatic' ), array( 'kit-css', 'bundled-local' ), array( 'automatic', 'kit-css' ), array( 'bundled-local', 'kit-css' ) );
	}

	public function test_existing_first_call_filter_can_select_kit() {
		add_filter( 'bfa_init_args', function ( $args ) { $args['asset_delivery'] = 'kit-css'; $args['kit_css_url'] = self::URL; return $args; } );
		$library = Better_Font_Awesome_Library::get_instance();
		$this->assertSame( 'kit-css', $library->get_asset_delivery() );
		$this->assertSame( self::URL, $library->get_stylesheet_url() );
	}

	/** @dataProvider invalid_urls */
	public function test_invalid_url_fails_closed_without_leaking_input_or_later_repair( $url ) {
		$library = $this->kit( array( 'kit_css_url' => $url ) );
		$this->assertSame( '', $library->get_asset_delivery() );
		$this->assertSame( 'bfa_kit_css_url_invalid', $library->get_error( 'delivery' )->get_error_code() );
		$this->assertSame( 'Kit CSS asset delivery requires an official HTTPS CSS-only Kit embed URL.', $library->get_error( 'delivery' )->get_error_message() );
		$this->assertSame( $library, $this->kit() );
		add_filter( 'bfa_init_args', function ( $args ) { $args['kit_css_url'] = self::URL; return $args; } );
		$library->load();
		$this->assert_closed( $library );
	}

	public static function invalid_urls() {
		return array_map( function ( $url ) { return array( $url ); }, array(
			'', null, false, 1, array(), new stdClass(),
			'http://kit.fontawesome.com/0123456789.css', '//kit.fontawesome.com/0123456789.css',
			'javascript:alert(1)', 'file:///tmp/kit.css', 'data:text/css,body{}',
			'https://untrusted.test/0123456789.css', 'https://kit.fontawesome.com.untrusted.test/0123456789.css',
			'https://kit.fontawesome.com@untrusted.test/0123456789.css', 'https://user:secret@kit.fontawesome.com/0123456789.css',
			'https://kit.fontawesome.com:443/0123456789.css', 'https://KIT.FONTAWESOME.COM/0123456789.css',
			'https://kit.fontawesome.com/0123456789.js', 'https://kit.fontawesome.com/0123456789.css?token=secret',
			'https://kit.fontawesome.com/0123456789.css#fragment', 'https://kit.fontawesome.com/../0123456789.css',
			'https://kit.fontawesome.com/%30%31%32%33%34%35%36%37%38%39.css', 'https://kit.fontawesome.com/0123456789.css/extra',
			'https://kit.fontawesome.com/.css', 'https://kit.fontawesome.com/a/b.css',
			'https://kit.fontawesome.com/kit,id.css', "https://kit.fontawesome.com/0123456789.css\n", ' ' . self::URL,
			'https://ka-p.fontawesome.com/0123456789.css', 'https://kit.fontawesome.com\\@untrusted.test/0123456789.css',
		) );
	}

	/** @dataProvider invalid_channels */
	public function test_kit_requires_font_awesome_7( $channel, $process, $code ) {
		$library = $this->kit( array( 'release_channel' => $channel ) );
		$this->assertSame( '', $library->get_asset_delivery() );
		$this->assertSame( $code, $library->get_error( $process )->get_error_code() );
		$this->assert_closed( $library );
	}

	public static function invalid_channels() {
		return array( array( '5.x', 'delivery', 'bfa_asset_delivery_channel_unsupported' ), array( '8.x', 'channel', 'bfa_channel_unsupported' ) );
	}

	/** @dataProvider local_metadata_sources */
	public function test_metadata_stays_free_and_local_without_refresh_or_mutation( $source ) {
		$record = json_decode( file_get_contents( __DIR__ . '/../inc/font-awesome-7-fallback/metadata.json' ), true );
		$args = array( 'release_data_refresh_callback' => function () { $this->fail( 'Kit mode must not request Free discovery.' ); } );
		if ( 'provider' === $source ) { $args['release_data_provider'] = function () use ( $record ) { return $record; }; }
		if ( 'transient' === $source ) { $GLOBALS['bfa_test_transients']['bfa-release-data'] = $record; }
		$before = $GLOBALS['bfa_test_transients'];
		$library = $this->kit( $args );
		$this->assertSame( $record, $library->get_release_record() );
		$this->assertSame( $record['release']['version'], $library->get_version() );
		$this->assertSame( $record['release']['srisByLicense']['free'], $library->get_release_assets() );
		$this->assertNotContains( 'thin', array_column( $library->get_icons(), 'style' ) );
		$this->assertNotContains( 'light', array_column( $library->get_icons(), 'style' ) );
		$library->enqueue_admin_scripts();
		$library->render_shortcode( array( 'name' => 'poc-icon', 'style' => 'thin' ) );
		$library->request_release_data_refresh();
		$error = $library->refresh_release_data();
		$this->assertSame( 'bfa_refresh_disabled', $error->get_error_code() );
		$this->assertSame( $before, $GLOBALS['bfa_test_transients'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
		$this->assertSame( array(), $library->get_errors() );
		$this->assert_no_network_or_refresh();
	}

	public static function local_metadata_sources() { return array( array( 'fallback' ), array( 'provider' ), array( 'transient' ) ); }

	public function test_missing_free_fallback_does_not_replace_kit_assets_or_schedule_recovery() {
		$GLOBALS['bfa_test_plugin_dir_path'] = __DIR__ . '/missing-bundle/';
		$library = $this->kit();
		$this->assertSame( '', $library->get_version() );
		$this->assertSame( self::URL, $library->get_stylesheet_url() );
		$this->assertSame( 'fat fa-poc-icon', $library->get_icon_base_class( 'poc-icon', 'thin' ) );
		$library->register_font_awesome_css();
		$this->assertSame( array( self::HANDLE ), array_keys( $GLOBALS['bfa_test_registered_styles'] ) );
		$this->assertSame( 'bfa_refresh_disabled', $library->refresh_release_data()->get_error_code() );
		$this->assert_no_network_or_refresh();
	}

	/** @dataProvider prefixes */
	public function test_thin_and_existing_style_mappings( $style, $prefix ) {
		$library = $this->kit();
		$this->assertSame( $prefix . ' fa-poc-icon', $library->get_icon_base_class( 'poc-icon', $style ) );
		$this->assertStringContainsString( $prefix . ' fa-poc-icon', $library->render_shortcode( array( 'name' => 'poc-icon', 'style' => $style ) ) );
	}

	public static function prefixes() {
		return array( array( 'thin', 'fat' ), array( 'light', 'fal' ), array( 'regular', 'far' ), array( 'solid', 'fas' ), array( 'brands', 'fab' ), array( 'unknown', 'fa' ), array( '', 'fa' ) );
	}

	private function assert_closed( $library ) {
		$this->assertSame( '', $library->get_stylesheet_url() );
		$this->assertSame( '', $library->get_stylesheet_url_v4_shim() );
		$this->assertSame( $library->get_error( 'channel' ) ?: $library->get_error( 'delivery' ), $library->refresh_release_data() );
		$library->register_font_awesome_css();
		$library->register_v4_shim_inline_css();
		$library->add_editor_styles();
		$library->request_release_data_refresh();
		$this->assertSame( array(), $GLOBALS['bfa_test_registered_styles'] );
		$this->assertSame( array(), get_editor_stylesheets() );
		$this->assertSame( '', apply_filters( 'mce_css', '' ) );
		$this->assertSame( array(), $GLOBALS['bfa_test_inline_styles'] );
		$this->assert_no_network_or_refresh();
	}

	private function assert_no_network_or_refresh() {
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertNotContains( 'bfa_release_data_refresh_requested', array_column( $GLOBALS['bfa_test_did_actions'], 'tag' ) );
	}
}
