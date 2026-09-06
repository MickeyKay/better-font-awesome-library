<?php

require_once __DIR__ . '/BfalTestCase.php';

class AssetDeliveryTest extends BfalTestCase {
	/** @dataProvider automatic_modes */
	public function test_automatic_preserves_provider_selection( $mode, $channel ) {
		$record = '7.x' === $channel ? $this->bundle() : $this->get_valid_release();
		$args = array(
			'release_channel' => $channel,
			'release_data_provider' => function () use ( $record ) { return $record; },
		);
		if ( null !== $mode ) {
			$args['asset_delivery'] = $mode;
		}
		$library = Better_Font_Awesome_Library::get_instance( $args );
		$this->assertSame( 'automatic', $library->get_asset_delivery() );
		$this->assertSame( $channel, $library->get_release_channel() );
		$this->assertSame(
			'7.x' === $channel ? 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css' : 'https://use.fontawesome.com/releases/v5.15.4/css/all.css',
			$library->get_stylesheet_url()
		);
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public static function automatic_modes() {
		return array( array( null, '7.x' ), array( 'automatic', '7.x' ), array( null, '5.x' ), array( 'automatic', '5.x' ) );
	}

	/** @dataProvider ignored_candidates */
	public function test_local_ignores_provider_and_transient_without_reading_or_mutating_them( $candidate ) {
		$record = $this->candidate( $candidate );
		$GLOBALS['bfa_test_transients']['bfa-release-data'] = $record;
		$before = $GLOBALS['bfa_test_transients'];
		$library = Better_Font_Awesome_Library::get_instance(
			array(
				'asset_delivery' => 'bundled-local',
				'release_data_provider' => function () use ( $record ) {
					$this->fail( 'Local delivery must not invoke a provider.' );
					return $record;
				},
				'release_data_refresh_callback' => function () { $this->fail( 'Local delivery must not schedule refresh.' ); },
			)
		);
		$this->assertSame( $this->bundle(), $library->get_release_record() );
		$this->assertSame( 'bundled-local', $library->get_asset_delivery() );
		$this->assertSame( '7.x', $library->get_release_channel() );
		$this->assertSame( $this->bundle()['release']['version'], $library->get_version() );
		$this->assertSame( array(), $library->get_errors() );
		$library->load();
		$library->get_icons();
		$library->get_release_icons();
		$library->register_font_awesome_css();
		$library->enqueue_admin_scripts();
		$library->render_shortcode( array( 'name' => 'close' ) );
		$library->request_release_data_refresh();
		$this->assertSame( 'bfa_refresh_disabled', $library->refresh_release_data()->get_error_code() );
		$this->assertSame( $this->bundle(), $library->get_release_record() );
		$this->assertSame( $before, $GLOBALS['bfa_test_transients'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_reads'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
		$this->assertSame( array(), $library->get_errors(), 'Disabled refresh is a result, not an admin diagnostic.' );
		$this->assert_no_refresh_work();
	}

	public static function ignored_candidates() {
		return array_map( function ( $name ) { return array( $name ); }, array( 'empty', 'matching', 'newer', 'wrong-channel', 'malformed' ) );
	}

	public function test_local_catalog_picker_styles_and_editor_share_the_bundle() {
		$library = Better_Font_Awesome_Library::get_instance( array( 'asset_delivery' => 'bundled-local', 'include_v4_shim' => true ) );
		$library->register_font_awesome_css();
		$record = $this->bundle();
		$this->assertSame( $record['release']['srisByLicense']['free'], $library->get_release_assets() );
		$this->assertSame( array_column( $record['release']['icons'], 'id' ), array_column( $library->get_release_icons(), 'id' ) );
		$expected_picker = array();
		foreach ( $record['release']['icons'] as $icon ) {
			foreach ( $icon['familyStylesByLicense']['free'] as $style ) {
				$expected_picker[] = $icon['id'] . ':' . $style['style'];
			}
		}
		$actual_picker = array_map( function ( $icon ) { return $icon['slug'] . ':' . $icon['style']; }, $library->get_icons() );
		sort( $expected_picker );
		sort( $actual_picker );
		$this->assertSame( $expected_picker, $actual_picker );

		$root = 'https://example.test/plugin/inc/font-awesome-7-fallback/';
		$this->assertSame( $root . 'css/all.min.css', $library->get_stylesheet_url() );
		$this->assertSame( $root . 'css/v4-shims.min.css', $library->get_stylesheet_url_v4_shim() );
		$expected = array(
			'bfa-font-awesome' => 'css/all.min.css',
			'bfa-font-awesome-v5-compat' => 'css/v5-font-face.min.css',
			'bfa-font-awesome-v4-font-face' => 'css/v4-font-face.min.css',
			'bfa-font-awesome-v4-shim' => 'css/v4-shims.min.css',
		);
		$this->assertSame( array_keys( $expected ), array_keys( $GLOBALS['bfa_test_registered_styles'] ) );
		foreach ( $expected as $handle => $path ) {
			$this->assertSame( $root . $path, $GLOBALS['bfa_test_registered_styles'][ $handle ]['src'] );
			$tag = apply_filters( 'style_loader_tag', '<link id="' . $handle . '-css" rel="stylesheet" href="' . $root . $path . '" />', $handle );
			$this->assertStringContainsString( 'integrity="sha512-', $tag );
			$this->assertStringContainsString( 'crossorigin="anonymous"', $tag );
		}
		$this->assertSame( array_map( function ( $path ) use ( $root ) { return $root . $path; }, array_values( $expected ) ), get_editor_stylesheets() );
		$this->assertSame( array(), $GLOBALS['bfa_test_inline_styles'] );
		$library->request_release_data_refresh();
		$this->assert_no_refresh_work();
	}

	/** @dataProvider owner_modes */
	public function test_first_caller_owns_mode_and_channel_even_after_filter_changes_and_reload( $mode, $channel, $later_mode, $later_channel ) {
		$library = Better_Font_Awesome_Library::get_instance( array( 'asset_delivery' => $mode, 'release_channel' => $channel ) );
		$record = $library->get_release_record();
		$later = Better_Font_Awesome_Library::get_instance( array( 'asset_delivery' => $later_mode, 'release_channel' => $later_channel ) );
		add_filter( 'bfa_init_args', function ( $args ) use ( $later_mode, $later_channel ) {
			$args['asset_delivery'] = $later_mode;
			$args['release_channel'] = $later_channel;
			return $args;
		} );
		add_filter( 'bfa_font_awesome_release_channel', function () use ( $later_channel ) { return $later_channel; } );
		$library->load();
		$this->assertSame( $library, $later );
		$this->assertSame( $mode, $library->get_asset_delivery() );
		$this->assertSame( $channel, $library->get_release_channel() );
		$this->assertSame( $record, $library->get_release_record() );
	}

	public static function owner_modes() {
		return array(
			array( 'bundled-local', '7.x', 'automatic', '5.x' ),
			array( 'automatic', '7.x', 'bundled-local', '5.x' ),
			array( 'automatic', '5.x', 'bundled-local', '7.x' ),
		);
	}

	/** @dataProvider invalid_configurations */
	public function test_invalid_configuration_reports_no_mode_and_cannot_be_replaced( $mode, $channel, $process, $code ) {
		$library = Better_Font_Awesome_Library::get_instance( array( 'asset_delivery' => $mode, 'release_channel' => $channel ) );
		$this->assertSame( '', $library->get_asset_delivery() );
		$this->assertSame( 'unsupported' === $channel ? '' : $channel, $library->get_release_channel() );
		$this->assertSame( $code, $library->get_error( $process )->get_error_code() );
		$this->assertSame( $library->get_error( $process ), $library->refresh_release_data() );
		$this->assertSame( $library, Better_Font_Awesome_Library::get_instance( array( 'asset_delivery' => 'automatic', 'release_channel' => '7.x' ) ) );
		add_filter( 'bfa_init_args', function ( $args ) {
			$args['asset_delivery'] = 'automatic';
			$args['release_channel'] = '7.x';
			return $args;
		} );
		$library->load();
		$this->assertSame( '', $library->get_asset_delivery() );
		$this->assertSame( 'unsupported' === $channel ? '' : $channel, $library->get_release_channel() );
		$this->assertSame( $library->get_error( $process ), $library->refresh_release_data() );
		if ( 'delivery' === $process ) {
			$library->register_v4_shim_inline_css();
		}
		$this->assert_closed( $library );
	}

	public static function invalid_configurations() {
		return array(
			array( 'remote-secret', '7.x', 'delivery', 'bfa_asset_delivery_unsupported' ),
			array( null, '7.x', 'delivery', 'bfa_asset_delivery_unsupported' ),
			array( array(), '7.x', 'delivery', 'bfa_asset_delivery_unsupported' ),
			array( false, '7.x', 'delivery', 'bfa_asset_delivery_unsupported' ),
			array( 'bundled-local', '5.x', 'delivery', 'bfa_asset_delivery_channel_unsupported' ),
			array( 'automatic', 'unsupported', 'channel', 'bfa_channel_unsupported' ),
			array( 'bundled-local', 'unsupported', 'channel', 'bfa_channel_unsupported' ),
		);
	}

	public function test_first_caller_init_filter_can_select_local_delivery() {
		add_filter( 'bfa_init_args', function ( $args ) { $args['asset_delivery'] = 'bundled-local'; return $args; } );
		$library = Better_Font_Awesome_Library::get_instance();
		$this->assertSame( 'bundled-local', $library->get_asset_delivery() );
		$this->assertSame( $this->bundle(), $library->get_release_record() );
		$this->assert_no_refresh_work();
	}

	/** @dataProvider bundle_failures */
	public function test_broken_bundle_never_uses_remote_candidates( $path, $replacement ) {
		$root = sys_get_temp_dir() . '/bfal-local-' . uniqid() . '/';
		$bundle_root = $root . Better_Font_Awesome_Library::FONT_AWESOME_7_FALLBACK_PATH;
		mkdir( $bundle_root . 'css', 0700, true );
		mkdir( $bundle_root . 'webfonts', 0700, true );
		$files = array_merge( array( 'metadata.json' ), array_column( $this->bundle()['release']['srisByLicense']['free'], 'path' ), array(
			'webfonts/fa-brands-400.woff2', 'webfonts/fa-regular-400.woff2', 'webfonts/fa-solid-900.woff2', 'webfonts/fa-v4compatibility.woff2',
		) );
		try {
			foreach ( $files as $file ) {
				copy( dirname( __DIR__ ) . '/' . Better_Font_Awesome_Library::FONT_AWESOME_7_FALLBACK_PATH . $file, $bundle_root . $file );
			}
			if ( null === $replacement ) {
				unlink( $bundle_root . $path );
			} else {
				file_put_contents( $bundle_root . $path, $replacement );
			}
			$GLOBALS['bfa_test_plugin_dir_path'] = $root;
			$candidate = $this->candidate( 'newer' );
			$GLOBALS['bfa_test_transients']['bfa-release-data'] = $candidate;
			$library = Better_Font_Awesome_Library::get_instance( array(
				'asset_delivery' => 'bundled-local', 'include_v4_shim' => true,
				'release_data_provider' => function () use ( $candidate ) { $this->fail( 'Provider called for broken bundle.' ); return $candidate; },
				'release_data_refresh_callback' => function () { $this->fail( 'Refresh requested for broken bundle.' ); },
			) );
			$this->assertInstanceOf( WP_Error::class, $library->get_error( 'fallback' ) );
			$this->assertSame( 'bundled-local', $library->get_asset_delivery(), 'A bundle failure does not invalidate the configured delivery mode.' );
			if ( 'metadata.json' !== $path ) {
				$this->assertSame( 'bfa_bundled_asset_unavailable', $library->get_error( 'fallback' )->get_error_code() );
			}
			$this->assertSame( 'bfa_refresh_disabled', $library->refresh_release_data()->get_error_code() );
			$library->register_v4_shim_inline_css();
			$this->assert_closed( $library );
			$this->assertSame( $candidate, $GLOBALS['bfa_test_transients']['bfa-release-data'] );
		} finally {
			$GLOBALS['bfa_test_plugin_dir_path'] = null;
			foreach ( $files as $file ) {
				if ( file_exists( $bundle_root . $file ) ) { unlink( $bundle_root . $file ); }
			}
			rmdir( $bundle_root . 'css' );
			rmdir( $bundle_root . 'webfonts' );
			rmdir( $bundle_root );
			rmdir( $root . 'inc' );
			rmdir( $root );
		}
	}

	public static function bundle_failures() {
		return array(
			array( 'metadata.json', null ), array( 'metadata.json', '{invalid' ),
			array( 'css/all.min.css', null ), array( 'css/v5-font-face.min.css', '' ),
			array( 'css/v4-font-face.min.css', null ), array( 'css/v4-shims.min.css', null ),
			array( 'webfonts/fa-brands-400.woff2', null ), array( 'webfonts/fa-regular-400.woff2', null ),
			array( 'webfonts/fa-solid-900.woff2', null ), array( 'webfonts/fa-v4compatibility.woff2', null ),
		);
	}

	private function bundle() {
		return json_decode( file_get_contents( dirname( __DIR__ ) . '/inc/font-awesome-7-fallback/metadata.json' ), true );
	}

	private function candidate( $name ) {
		if ( 'empty' === $name ) { return array(); }
		if ( 'wrong-channel' === $name ) { return $this->get_valid_release(); }
		if ( 'malformed' === $name ) { return array( 'version' => 'invalid' ); }
		$record = $this->bundle();
		if ( 'newer' === $name ) {
			$record['release']['version'] = '7.99.0';
			$record['release']['icons'] = array( $record['release']['icons'][0] );
		}
		return $record;
	}

	private function assert_closed( $library ) {
		$this->assertSame( '', $library->get_version() );
		$this->assertSame( '', $library->get_stylesheet_url() );
		$this->assertSame( '', $library->get_stylesheet_url_v4_shim() );
		$this->assertSame( array(), $library->get_release_record() );
		$this->assertSame( array(), $library->get_release_icons() );
		$this->assertSame( array(), $library->get_icons() );
		$this->assertSame( array(), $library->get_release_assets() );
		$library->register_font_awesome_css();
		$library->request_release_data_refresh();
		$this->assertSame( array(), $GLOBALS['bfa_test_registered_styles'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_inline_styles'] );
		$this->assertSame( array(), get_editor_stylesheets() );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_reads'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
		$this->assert_no_refresh_work();
	}

	private function assert_no_refresh_work() {
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertNotContains( 'bfa_release_data_refresh_requested', array_column( $GLOBALS['bfa_test_did_actions'], 'tag' ) );
	}
}
