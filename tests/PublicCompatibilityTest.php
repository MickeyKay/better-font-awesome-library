<?php

require_once __DIR__ . '/BfalTestCase.php';

class PublicCompatibilityTest extends BfalTestCase {
	public function test_public_constants_are_preserved() {
		$this->assertSame( 'bfa', Better_Font_Awesome_Library::SLUG );
		$this->assertSame( '2.1.0', Better_Font_Awesome_Library::VERSION );
		$this->assertSame( 'https://api.fontawesome.com', Better_Font_Awesome_Library::FONT_AWESOME_API_BASE_URL );
		$this->assertSame( 'https://use.fontawesome.com/releases', Better_Font_Awesome_Library::FONT_AWESOME_CDN_BASE_URL );
		$this->assertSame( 'inc/fallback-release-data.json', Better_Font_Awesome_Library::FALLBACK_RELEASE_DATA_PATH );
		$this->assertSame( 'fa', Better_Font_Awesome_Library::ICON_PREFIX );
		$this->assertSame( DAY_IN_SECONDS, Better_Font_Awesome_Library::TRANSIENT_EXPIRATION );
	}

	public function test_public_method_signatures_are_preserved() {
		$methods = array(
			'get_instance'                    => array( 1, 1 ),
			'load'                            => array( 0, 0 ),
			'setup_root_url'                  => array( 0, 0 ),
			'remove_font_awesome_css'          => array( 0, 0 ),
			'remove_icon_shortcode'            => array( 0, 0 ),
			'add_icon_shortcode'               => array( 0, 0 ),
			'sanitize_shortcode_name_att'      => array( 1, 0 ),
			'sanitize_shortcode_class_att'     => array( 1, 0 ),
			'render_shortcode'                 => array( 1, 0 ),
			'get_icon_base_class'              => array( 2, 1 ),
			'register_font_awesome_css'        => array( 0, 0 ),
			'register_v4_shim_inline_css'      => array( 0, 0 ),
			'add_editor_styles'                => array( 0, 0 ),
			'enqueue_admin_scripts'            => array( 0, 0 ),
			'add_insert_shortcode_button'      => array( 0, 0 ),
			'do_admin_notice'                  => array( 0, 0 ),
			'get_error'                        => array( 1, 0 ),
			'get_version'                      => array( 0, 0 ),
			'get_stylesheet_url'               => array( 0, 0 ),
			'get_stylesheet_url_v4_shim'       => array( 0, 0 ),
			'get_icons'                        => array( 0, 0 ),
			'get_release_icons'                => array( 0, 0 ),
			'get_release_assets'               => array( 0, 0 ),
			'get_prefix'                       => array( 0, 0 ),
			'get_transient_expiration'         => array( 0, 0 ),
			'get_errors'                       => array( 0, 0 ),
		);

		foreach ( $methods as $method_name => $counts ) {
			$method = new ReflectionMethod( Better_Font_Awesome_Library::class, $method_name );
			$this->assertTrue( $method->isPublic(), $method_name . ' must remain public.' );
			$this->assertSame( $counts[0], $method->getNumberOfParameters(), $method_name . ' parameter count changed.' );
			$this->assertSame( $counts[1], $method->getNumberOfParameters() - $method->getNumberOfRequiredParameters(), $method_name . ' optional parameter count changed.' );
		}
	}

	public function test_singleton_keeps_the_first_calls_configuration() {
		$release               = $this->get_valid_release();
		$first_provider_calls  = 0;
		$first_refresh_calls   = 0;
		$second_provider_calls = 0;
		$second_refresh_calls  = 0;
		$first = $this->get_instance(
			array(
				'load_styles'                  => false,
				'load_shortcode'               => false,
				'release_data_provider'        => function () use ( $release, &$first_provider_calls ) {
					++$first_provider_calls;
					return $release;
				},
				'release_data_refresh_callback' => function () use ( &$first_refresh_calls ) {
					++$first_refresh_calls;
				},
			)
		);
		$action_count = count( $GLOBALS['bfa_test_actions'] );

		$second = Better_Font_Awesome_Library::get_instance(
			array(
				'load_styles'                  => true,
				'load_shortcode'               => true,
				'release_data_provider'        => function () use ( &$second_provider_calls ) {
					++$second_provider_calls;
					return array();
				},
				'release_data_refresh_callback' => function () use ( &$second_refresh_calls ) {
					++$second_refresh_calls;
				},
			)
		);

		$this->assertSame( $first, $second );
		$this->assertSame( $action_count, count( $GLOBALS['bfa_test_actions'] ) );
		$this->assertSame( '5.15.4', $first->get_version() );
		$first->request_release_data_refresh();
		$this->assertSame( 1, $first_provider_calls );
		$this->assertSame( 1, $first_refresh_calls );
		$this->assertSame( 0, $second_provider_calls );
		$this->assertSame( 0, $second_refresh_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_established_return_shapes_and_urls_are_preserved() {
		$library = $this->get_instance();

		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 'https://use.fontawesome.com/releases/v5.15.4/css/all.css', $library->get_stylesheet_url() );
		$this->assertSame( 'https://use.fontawesome.com/releases/v5.15.4/css/v4-shims.css', $library->get_stylesheet_url_v4_shim() );
		$this->assertSame( 'fa', $library->get_prefix() );
		$this->assertSame( DAY_IN_SECONDS, $library->get_transient_expiration() );
		$this->assertSame( $this->get_valid_release()['icons'], $library->get_release_icons() );
		$this->assertSame( $this->get_valid_release()['srisByLicense']['free'], $library->get_release_assets() );

		$icons = $library->get_icons();
		$this->assertCount( 3, $icons );
		$this->assertSame(
			array( 'title', 'slug', 'style', 'base_class', 'searchTerms' ),
			array_keys( $icons[0] )
		);
		$this->assertSame( 'Flag (solid)', $icons[0]['title'] );
		$this->assertSame( 'flag', $icons[0]['slug'] );
		$this->assertSame( 'solid', $icons[0]['style'] );
		$this->assertSame( 'fas fa-flag', $icons[0]['base_class'] );
		$this->assertSame( 'flag', $icons[0]['searchTerms'] );
	}

	public function test_shortcode_markup_is_preserved_byte_for_byte() {
		$library = $this->get_instance();

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
		$this->assertSame(
			'<i class="fa fa-flag " title="A &amp; B" >&nbsp;</i>',
			$library->render_shortcode(
				array(
					'name'  => 'fa-flag',
					'title' => 'A & B',
					'space' => 'true',
				)
			)
		);
	}

	public function test_shortcode_and_icon_filters_keep_their_arguments() {
		add_filter(
			'bfa_icon_class',
			function ( $class, $name ) {
				return $class . ' filtered-' . $name;
			},
			10,
			2
		);
		add_filter(
			'bfa_icon_tag',
			function () {
				return 'span';
			}
		);
		add_filter(
			'bfa_icon',
			function ( $output ) {
				return $output . '<!-- filtered -->';
			}
		);

		$output = $this->get_instance()->render_shortcode( array( 'name' => 'flag' ) );

		$this->assertSame( '<span class="fa fa-flag filtered-flag " ></span><!-- filtered -->', $output );
		$this->assertFilterAppliedWithArgumentCount( 'bfa_icon_class', 2 );
		$this->assertFilterAppliedWithArgumentCount( 'bfa_icon_tag', 1 );
		$this->assertFilterAppliedWithArgumentCount( 'bfa_icon', 1 );
	}

	public function test_icon_array_filters_remain_active_in_order() {
		add_filter(
			'bfa_icon_list',
			function ( $icons ) {
				$icons[0]['legacy_filter'] = true;
				return $icons;
			}
		);
		add_filter(
			'bfa_icon_array',
			function ( $icons ) {
				$icons[0]['modern_filter'] = true;
				return $icons;
			}
		);

		$icons = $this->get_instance()->get_icons();

		$this->assertTrue( $icons[0]['legacy_filter'] );
		$this->assertTrue( $icons[0]['modern_filter'] );
		$this->assertFilterAppliedWithArgumentCount( 'bfa_icon_list', 1 );
		$this->assertFilterAppliedWithArgumentCount( 'bfa_icon_array', 1 );
	}

	public function test_registration_handles_and_v4_shim_behavior_are_preserved() {
		$library = $this->get_instance( array( 'include_v4_shim' => true ) );
		$library->register_font_awesome_css();

		$this->assertArrayHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_registered_styles'] );
		$this->assertArrayHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_registered_styles'] );
		$this->assertSame( '2.1.0', $GLOBALS['bfa_test_registered_styles']['bfa-font-awesome']['version'] );
		$this->assertSame( '2.1.0', $GLOBALS['bfa_test_registered_styles']['bfa-font-awesome-v4-shim']['version'] );
		$this->assertArrayHasKey( 'bfa-font-awesome', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_enqueued_styles'] );
		$this->assertArrayHasKey( 'bfa-font-awesome-v4-shim', $GLOBALS['bfa_test_inline_styles'] );
		$this->assertStringContainsString( '/v5.15.4/webfonts/fa-solid-900.woff2', $GLOBALS['bfa_test_inline_styles']['bfa-font-awesome-v4-shim'] );
	}

	public function test_documented_initialization_and_notice_filters_remain_active() {
		$this->get_instance();
		$this->assertFilterAppliedWithArgumentCount( 'bfa_init_args', 1 );
		$this->assertFilterAppliedWithArgumentCount( 'bfa_wp_remote_get_args', 1 );

		add_filter(
			'bfa_show_errors',
			function () {
				return false;
			}
		);
		$library = $this->get_instance();
		$library->do_admin_notice();
		$this->assertSame( '', $this->getActualOutput() );
	}

	private function assertFilterAppliedWithArgumentCount( $tag, $count ) {
		foreach ( $GLOBALS['bfa_test_applied_filters'] as $filter ) {
			if ( $tag === $filter['tag'] && $count === $filter['arg_count'] ) {
				$this->addToAssertionCount( 1 );
				return;
			}
		}

		$this->fail( $tag . ' was not applied with ' . $count . ' argument(s).' );
	}
}
