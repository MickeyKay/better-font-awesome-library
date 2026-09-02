<?php

require_once __DIR__ . '/BfalTestCase.php';

class FontAwesome7RuntimeTest extends BfalTestCase {
	public function test_implicit_default_and_explicit_seven_selection_are_identical() {
		$implicit = Better_Font_Awesome_Library::get_instance();
		$first    = $this->runtime_snapshot( $implicit );

		$this->reset_library_singleton();
		bfa_test_reset_wordpress_state();
		$explicit = Better_Font_Awesome_Library::get_instance( array( 'release_channel' => '7.x' ) );

		$this->assertSame( $first, $this->runtime_snapshot( $explicit ) );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_default_seven_uses_packaged_fallback_immediately_without_http() {
		$library = Better_Font_Awesome_Library::get_instance( array( 'include_v4_shim' => true ) );

		$this->assertSame( '7.x', $library->get_release_channel() );
		$this->assertSame( '7.3.1', $library->get_version() );
		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
		$this->assertSame(
			'https://example.test/plugin/inc/font-awesome-7-fallback/css/all.min.css',
			$library->get_stylesheet_url()
		);
		$this->assertSame(
			'https://example.test/plugin/inc/font-awesome-7-fallback/css/v4-shims.min.css',
			$library->get_stylesheet_url_v4_shim()
		);
		$this->assertCount( 4, $library->get_release_assets() );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_first_caller_channel_is_immutable_across_later_callers_and_loads() {
		$filter_calls = 0;
		add_filter(
			'bfa_font_awesome_release_channel',
			function ( $channel ) use ( &$filter_calls ) {
				++$filter_calls;
				return 1 === $filter_calls ? $channel : '5.x';
			}
		);

		$first  = Better_Font_Awesome_Library::get_instance();
		$second = Better_Font_Awesome_Library::get_instance( array( 'release_channel' => '5.x' ) );
		$first->load();

		$this->assertSame( $first, $second );
		$this->assertSame( '7.x', $first->get_release_channel() );
		$this->assertSame( '7.3.1', $first->get_version() );
		$this->assertSame( 1, $filter_calls );
	}

	public function test_explicit_five_first_caller_cannot_be_overridden_by_later_seven_caller() {
		$this->prime_valid_transient();
		$first  = Better_Font_Awesome_Library::get_instance( array( 'release_channel' => '5.x' ) );
		$second = Better_Font_Awesome_Library::get_instance( array( 'release_channel' => '7.x' ) );

		$this->assertSame( $first, $second );
		$this->assertSame( '5.x', $second->get_release_channel() );
		$this->assertSame( '5.15.4', $second->get_version() );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_explicit_five_preserves_established_runtime_behavior() {
		$this->prime_valid_transient();
		$library = Better_Font_Awesome_Library::get_instance( array( 'release_channel' => '5.x' ) );

		$this->assertSame( '5.x', $library->get_release_channel() );
		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 'https://use.fontawesome.com/releases/v5.15.4/css/all.css', $library->get_stylesheet_url() );
		$this->assertSame( 'https://use.fontawesome.com/releases/v5.15.4/css/v4-shims.css', $library->get_stylesheet_url_v4_shim() );
		$this->assertSame(
			'<i class="fas fa-moon fa-2x fa-spin my-custom-class " ></i>',
			$library->render_shortcode(
				array(
					'name'             => 'moon',
					'style'            => 'solid',
					'class'            => '2x spin',
					'unprefixed_class' => 'my-custom-class',
				)
			)
		);
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_wrong_channel_provider_cannot_mix_metadata_and_assets() {
		$fa5 = $this->get_valid_release();
		$library = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider' => function () use ( $fa5 ) {
					return $fa5;
				},
			)
		);

		$this->assertSame( '7.3.1', $library->get_version() );
		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
		$this->assertStringContainsString( '/font-awesome-7-fallback/', $library->get_stylesheet_url() );
		$this->assertSame( 'bfa_v2_version_unsupported', $library->get_error( 'provider' )->get_error_code() );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_provider_fallback_provenance_does_not_select_packaged_assets() {
		$record = $this->get_schema_two_record( '7.4.0', 'provider-record' );
		$library = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider' => function () use ( $record ) {
					return $record;
				},
			)
		);
		$integrity = $record['release']['srisByLicense']['free'][0]['value'];
		$tag       = '<link rel="stylesheet" id="bfa-font-awesome-css" href="remote.css" />';

		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
		$this->assertSame( '7.4.0', $library->get_version() );
		$this->assertSame(
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.4.0/css/all.min.css',
			$library->get_stylesheet_url()
		);
		$this->assertSame( $record['release']['srisByLicense']['free'], $library->get_release_assets() );
		$this->assertStringContainsString( 'integrity="' . $integrity . '"', apply_filters( 'style_loader_tag', $tag, 'bfa-font-awesome' ) );
		$this->assertStringNotContainsString( '/font-awesome-7-fallback/', $library->get_stylesheet_url() );
	}

	public function test_transient_fallback_provenance_does_not_select_packaged_assets() {
		$record = $this->get_schema_two_record( '7.4.1', 'transient-record' );
		$GLOBALS['bfa_test_transients']['bfa-release-data'] = $record;

		$library = Better_Font_Awesome_Library::get_instance();

		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
		$this->assertSame( '7.4.1', $library->get_version() );
		$this->assertSame(
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.4.1/css/all.min.css',
			$library->get_stylesheet_url()
		);
		$this->assertSame( $record['release']['srisByLicense']['free'], $library->get_release_assets() );
		$this->assertStringNotContainsString( '/font-awesome-7-fallback/', $library->get_stylesheet_url() );
	}

	public function test_explicit_five_rejects_a_schema_two_provider_record() {
		$record = Better_Font_Awesome_Release_Data_V2_Validator::parse_record_json(
			file_get_contents( dirname( __DIR__ ) . '/inc/font-awesome-7-fallback/metadata.json' )
		)['record'];
		$library = Better_Font_Awesome_Library::get_instance(
			array(
				'release_channel'       => '5.x',
				'release_data_provider' => function () use ( $record ) {
					return $record;
				},
			)
		);

		$this->assertSame( '5.x', $library->get_release_channel() );
		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
		$this->assertSame( 'bfa_record_schema_unsupported', $library->get_error( 'provider' )->get_error_code() );
		$this->assertSame( 'https://use.fontawesome.com/releases/v5.14.0/css/all.css', $library->get_stylesheet_url() );
	}

	public function test_schema_two_icons_adapt_to_the_established_public_shape_and_aliases() {
		$library = Better_Font_Awesome_Library::get_instance();
		$icons   = $library->get_release_icons();
		$address = null;
		foreach ( $icons as $icon ) {
			if ( 'address-book' === $icon['id'] ) {
				$address = $icon;
				break;
			}
		}

		$this->assertSame( array( 'id', 'label', 'membership', 'styles' ), array_keys( $address ) );
		$this->assertSame( array( 'regular', 'solid' ), $address['membership']['free'] );
		$this->assertSame( array( 'regular', 'solid' ), $address['styles'] );
		$github = null;
		foreach ( $icons as $icon ) {
			if ( 'github' === $icon['id'] ) {
				$github = $icon;
				break;
			}
		}
		$this->assertSame( array( 'brands' ), $github['membership']['free'] );

		$picker = null;
		foreach ( $library->get_icons() as $icon ) {
			if ( 'address-book' === $icon['slug'] && 'solid' === $icon['style'] ) {
				$picker = $icon;
				break;
			}
		}

		$this->assertSame( array( 'title', 'slug', 'style', 'base_class', 'searchTerms' ), array_keys( $picker ) );
		$this->assertSame( 'fas fa-address-book', $picker['base_class'] );
		$this->assertStringContainsString( 'contact-book', $picker['searchTerms'] );
		$this->assertSame(
			'<i class="fas fa-address-book " ></i>',
			$library->render_shortcode( array( 'name' => 'contact-book', 'style' => 'solid' ) )
		);
	}

	public function test_seven_registers_only_required_runtime_and_compatibility_styles() {
		$library = Better_Font_Awesome_Library::get_instance( array( 'include_v4_shim' => true ) );
		$library->register_font_awesome_css();

		$this->assertSame(
			array(
				'bfa-font-awesome',
				'bfa-font-awesome-v5-compat',
				'bfa-font-awesome-v4-font-face',
				'bfa-font-awesome-v4-shim',
			),
			array_keys( $GLOBALS['bfa_test_registered_styles'] )
		);
		$this->assertStringEndsWith( '/css/v5-font-face.min.css', $GLOBALS['bfa_test_registered_styles']['bfa-font-awesome-v5-compat']['src'] );
		$this->assertStringEndsWith( '/css/v4-font-face.min.css', $GLOBALS['bfa_test_registered_styles']['bfa-font-awesome-v4-font-face']['src'] );
		$this->assertArrayNotHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_inline_styles'] );

		$this->assertSame(
			array(
				'https://example.test/plugin/inc/font-awesome-7-fallback/css/all.min.css',
				'https://example.test/plugin/inc/font-awesome-7-fallback/css/v5-font-face.min.css',
				'https://example.test/plugin/inc/font-awesome-7-fallback/css/v4-font-face.min.css',
				'https://example.test/plugin/inc/font-awesome-7-fallback/css/v4-shims.min.css',
			),
			get_editor_stylesheets()
		);
	}

	public function test_seven_stylesheet_tags_use_matching_sri_and_anonymous_cors() {
		$library   = Better_Font_Awesome_Library::get_instance();
		$integrity = $library->get_release_assets()[0]['value'];
		$tag       = '<link rel="stylesheet" id="bfa-font-awesome-css" href="local.css" />';
		$filtered  = apply_filters( 'style_loader_tag', $tag, 'bfa-font-awesome' );

		$this->assertStringContainsString( 'crossorigin="anonymous"', $filtered );
		$this->assertStringContainsString( 'integrity="' . $integrity . '"', $filtered );
	}

	public function test_every_ordinary_seven_entrypoint_performs_zero_http() {
		$library = Better_Font_Awesome_Library::get_instance( array( 'include_v4_shim' => true ) );

		$library->load();
		$library->get_version();
		$library->get_stylesheet_url();
		$library->get_stylesheet_url_v4_shim();
		$library->get_icons();
		$library->get_release_icons();
		$library->get_release_assets();
		$library->register_font_awesome_css();
		$library->add_editor_styles();
		$library->enqueue_admin_scripts();
		$library->render_shortcode( array( 'name' => 'close' ) );

		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	private function runtime_snapshot( $library ) {
		return array(
			'channel' => $library->get_release_channel(),
			'version' => $library->get_version(),
			'source'  => $library->get_release_record()['source'],
			'main'    => $library->get_stylesheet_url(),
			'v4'      => $library->get_stylesheet_url_v4_shim(),
			'assets'  => $library->get_release_assets(),
		);
	}

	private function get_schema_two_record( $version, $seed ) {
		$record = Better_Font_Awesome_Release_Data_V2_Validator::parse_record_json(
			file_get_contents( dirname( __DIR__ ) . '/inc/font-awesome-7-fallback/metadata.json' )
		)['record'];
		$record['release']['version'] = $version;
		foreach ( $record['release']['srisByLicense']['free'] as &$asset ) {
			$asset['value'] = 'sha512-' . base64_encode( hash( 'sha512', $seed . ':' . $asset['path'], true ) );
		}
		unset( $asset );

		return $record;
	}

	private function reset_library_singleton() {
		$property = new ReflectionProperty( Better_Font_Awesome_Library::class, 'instance' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}
		$property->setValue( null, null );
	}
}
