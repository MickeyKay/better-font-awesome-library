<?php

require_once __DIR__ . '/BfalTestCase.php';

class ReleaseDataRuntimeTest extends BfalTestCase {
	public function test_missing_data_returns_fallback_without_transport_and_requests_refresh() {
		$requests = array();
		$library  = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_refresh_callback' => function ( $channel, $instance ) use ( &$requests ) {
					$requests[] = array( $channel, $instance );
				},
			)
		);

		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertCount( 1, $requests );
		$this->assertSame( '5.x', $requests[0][0] );
		$this->assertSame( $library, $requests[0][1] );
		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
	}

	public function test_normal_request_entrypoints_never_perform_transport() {
		$library = Better_Font_Awesome_Library::get_instance();

		$library->load();
		$library->get_version();
		$library->get_stylesheet_url();
		$library->get_stylesheet_url_v4_shim();
		$library->get_icons();
		$library->get_release_icons();
		$library->get_release_assets();
		$library->register_font_awesome_css();
		$library->add_editor_styles();
		$library->render_shortcode( array( 'name' => 'flag' ) );

		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_local_provider_data_takes_precedence_without_refresh() {
		$release       = $this->get_valid_release();
		$provider_calls = 0;
		$refresh_calls  = 0;
		$library        = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider' => function () use ( $release, &$provider_calls ) {
					++$provider_calls;
					return $release;
				},
				'release_data_refresh_callback' => function () use ( &$refresh_calls ) {
					++$refresh_calls;
				},
			)
		);

		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 'provider', $library->get_release_record()['source'] );
		$this->assertSame( 1, $provider_calls );
		$this->assertSame( 0, $refresh_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_post_construction_registration_claims_empty_slots_and_reopens_fallback() {
		$library = Better_Font_Awesome_Library::get_instance();
		$this->assertSame( '5.14.0', $library->get_version() );

		$release        = $this->get_valid_release();
		$provider_calls = 0;
		$refresh_calls  = 0;
		$provider       = function () use ( $release, &$provider_calls ) {
			++$provider_calls;
			return $release;
		};
		$refresh        = function () use ( &$refresh_calls ) {
			++$refresh_calls;
		};

		$result = $library->register_release_data_collaborators(
			array(
				'release_data_provider'         => $provider,
				'release_data_refresh_callback' => $refresh,
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 'provider', $library->get_release_record()['source'] );
		$this->assertSame( 1, $provider_calls );
		$this->assertSame( 0, $refresh_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_post_construction_refresh_callback_handles_a_new_fallback_request() {
		$library = Better_Font_Awesome_Library::get_instance();
		$this->assertSame( '5.14.0', $library->get_version() );
		$requests = array();

		$result = $library->register_release_data_collaborators(
			array(
				'release_data_refresh_callback' => function ( $channel, $instance ) use ( &$requests ) {
					$requests[] = array( $channel, $instance );
				},
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertCount( 1, $requests );
		$this->assertSame( '5.x', $requests[0][0] );
		$this->assertSame( $library, $requests[0][1] );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_claimed_refresh_callback_is_available_after_a_prior_request() {
		$this->prime_valid_transient();
		$library = Better_Font_Awesome_Library::get_instance();
		$this->assertSame( '5.15.4', $library->get_version() );
		$library->request_release_data_refresh();
		$requests = array();

		$result = $library->register_release_data_collaborators(
			array(
				'release_data_refresh_callback' => function ( $channel, $instance ) use ( &$requests ) {
					$requests[] = array( $channel, $instance );
				},
			)
		);
		$this->assertCount( 0, $requests );
		$library->request_release_data_refresh();

		$this->assertTrue( $result );
		$this->assertCount( 1, $requests );
		$this->assertSame( '5.x', $requests[0][0] );
		$this->assertSame( $library, $requests[0][1] );
		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 'transient', $library->get_release_record()['source'] );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_equivalent_collaborator_registration_is_idempotent() {
		$library        = Better_Font_Awesome_Library::get_instance();
		$release        = $this->get_valid_release();
		$provider_calls = 0;
		$provider       = function () use ( $release, &$provider_calls ) {
			++$provider_calls;
			return $release;
		};
		$refresh        = function () {};
		$collaborators  = array(
			'release_data_provider'         => $provider,
			'release_data_refresh_callback' => $refresh,
		);

		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertTrue( $library->register_release_data_collaborators( $collaborators ) );
		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertTrue( $library->register_release_data_collaborators( $collaborators ) );
		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 1, $provider_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_conflicting_registration_preserves_deliberate_earlier_collaborators() {
		$release             = $this->get_valid_release();
		$owner_provider_calls = 0;
		$owner_refresh_calls  = 0;
		$new_provider_calls   = 0;
		$new_refresh_calls    = 0;
		$owner_provider       = function () use ( $release, &$owner_provider_calls ) {
			++$owner_provider_calls;
			return $release;
		};
		$owner_refresh        = function () use ( &$owner_refresh_calls ) {
			++$owner_refresh_calls;
		};
		$library              = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider'         => $owner_provider,
				'release_data_refresh_callback' => $owner_refresh,
			)
		);

		$this->assertSame( '5.15.4', $library->get_version() );
		$result = $library->register_release_data_collaborators(
			array(
				'release_data_provider' => function () use ( &$new_provider_calls ) {
					++$new_provider_calls;
					return array();
				},
				'release_data_refresh_callback' => function () use ( &$new_refresh_calls ) {
					++$new_refresh_calls;
				},
			)
		);
		$library->request_release_data_refresh();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'bfa_release_data_provider_conflict', $result->get_error_code() );
		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 1, $owner_provider_calls );
		$this->assertSame( 1, $owner_refresh_calls );
		$this->assertSame( 0, $new_provider_calls );
		$this->assertSame( 0, $new_refresh_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_provider_conflict_does_not_claim_empty_callback_or_reopen_fallback() {
		$owner_provider_calls = 0;
		$new_provider_calls   = 0;
		$new_refresh_calls    = 0;
		$owner_provider       = function () use ( &$owner_provider_calls ) {
			++$owner_provider_calls;
			return array();
		};
		$library              = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider' => $owner_provider,
			)
		);

		$this->assertSame( '5.14.0', $library->get_version() );
		$result = $library->register_release_data_collaborators(
			array(
				'release_data_provider' => function () use ( &$new_provider_calls ) {
					++$new_provider_calls;
					return array();
				},
				'release_data_refresh_callback' => function () use ( &$new_refresh_calls ) {
					++$new_refresh_calls;
				},
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'bfa_release_data_provider_conflict', $result->get_error_code() );
		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
		$this->assertSame( 1, $owner_provider_calls );
		$this->assertSame( 0, $new_provider_calls );
		$this->assertSame( 0, $new_refresh_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_callback_conflict_does_not_claim_empty_provider_slot() {
		$owner_refresh_calls = 0;
		$new_provider_calls  = 0;
		$new_refresh_calls   = 0;
		$owner_refresh       = function () use ( &$owner_refresh_calls ) {
			++$owner_refresh_calls;
		};
		$library             = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_refresh_callback' => $owner_refresh,
			)
		);

		$this->assertSame( '5.14.0', $library->get_version() );
		$result = $library->register_release_data_collaborators(
			array(
				'release_data_provider' => function () use ( &$new_provider_calls ) {
					++$new_provider_calls;
					return array();
				},
				'release_data_refresh_callback' => function () use ( &$new_refresh_calls ) {
					++$new_refresh_calls;
				},
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'bfa_release_data_refresh_callback_conflict', $result->get_error_code() );
		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertSame( 1, $owner_refresh_calls );
		$this->assertSame( 0, $new_provider_calls );
		$this->assertSame( 0, $new_refresh_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_null_removal_attempts_preserve_installed_collaborators() {
		$release             = $this->get_valid_release();
		$owner_provider_calls = 0;
		$owner_refresh_calls  = 0;
		$owner_provider       = function () use ( $release, &$owner_provider_calls ) {
			++$owner_provider_calls;
			return $release;
		};
		$owner_refresh        = function () use ( &$owner_refresh_calls ) {
			++$owner_refresh_calls;
		};
		$library              = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider'         => $owner_provider,
				'release_data_refresh_callback' => $owner_refresh,
			)
		);

		$provider_result = $library->register_release_data_collaborators( array( 'release_data_provider' => null ) );
		$callback_result = $library->register_release_data_collaborators( array( 'release_data_refresh_callback' => null ) );
		$this->assertSame( '5.15.4', $library->get_version() );
		$library->request_release_data_refresh();

		$this->assertInstanceOf( WP_Error::class, $provider_result );
		$this->assertSame( 'bfa_release_data_provider_invalid', $provider_result->get_error_code() );
		$this->assertInstanceOf( WP_Error::class, $callback_result );
		$this->assertSame( 'bfa_release_data_refresh_callback_invalid', $callback_result->get_error_code() );
		$this->assertSame( 1, $owner_provider_calls );
		$this->assertSame( 1, $owner_refresh_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	/**
	 * @dataProvider invalid_collaborator_provider
	 */
	public function test_invalid_collaborators_are_rejected_without_changing_fallback_state( $collaborators, $expected_code ) {
		$library = Better_Font_Awesome_Library::get_instance();
		$this->assertSame( '5.14.0', $library->get_version() );

		$result = $library->register_release_data_collaborators( $collaborators );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $expected_code, $result->get_error_code() );
		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function invalid_collaborator_provider() {
		return array(
			'non-array input'  => array( 'provider', 'bfa_release_data_collaborators_invalid' ),
			'empty array'      => array( array(), 'bfa_release_data_collaborators_invalid' ),
			'unknown key'      => array( array( 'unknown' => null ), 'bfa_release_data_collaborators_invalid' ),
			'invalid provider' => array( array( 'release_data_provider' => 'not_callable' ), 'bfa_release_data_provider_invalid' ),
			'invalid callback' => array( array( 'release_data_refresh_callback' => 'not_callable' ), 'bfa_release_data_refresh_callback_invalid' ),
			'null provider'    => array( array( 'release_data_provider' => null ), 'bfa_release_data_provider_invalid' ),
			'null callback'    => array( array( 'release_data_refresh_callback' => null ), 'bfa_release_data_refresh_callback_invalid' ),
		);
	}

	public function test_invalid_registration_is_atomic() {
		$library        = Better_Font_Awesome_Library::get_instance();
		$provider_calls = 0;
		$provider       = function () use ( &$provider_calls ) {
			++$provider_calls;
			return array();
		};

		$result = $library->register_release_data_collaborators(
			array(
				'release_data_provider'         => $provider,
				'release_data_refresh_callback' => 'not_callable',
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'bfa_release_data_refresh_callback_invalid', $result->get_error_code() );
		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertSame( 0, $provider_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_later_get_instance_arguments_do_not_register_collaborators() {
		$library        = Better_Font_Awesome_Library::get_instance();
		$release        = $this->get_valid_release();
		$provider_calls = 0;
		$refresh_calls  = 0;

		$second = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider' => function () use ( $release, &$provider_calls ) {
					++$provider_calls;
					return $release;
				},
				'release_data_refresh_callback' => function () use ( &$refresh_calls ) {
					++$refresh_calls;
				},
			)
		);

		$this->assertSame( $library, $second );
		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertSame( 0, $provider_calls );
		$this->assertSame( 0, $refresh_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_registration_does_not_discard_valid_non_fallback_data() {
		$this->prime_valid_transient();
		$library        = Better_Font_Awesome_Library::get_instance();
		$provider_calls = 0;
		$replacement    = $this->get_valid_release();
		$replacement['version'] = '5.15.3';

		$this->assertSame( '5.15.4', $library->get_version() );
		$result = $library->register_release_data_collaborators(
			array(
				'release_data_provider' => function () use ( $replacement, &$provider_calls ) {
					++$provider_calls;
					return $replacement;
				},
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 'transient', $library->get_release_record()['source'] );
		$this->assertSame( 0, $provider_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_provider_accepts_a_validated_release_record() {
		$result  = Better_Font_Awesome_Release_Data_Validator::validate_release( $this->get_valid_release(), 'api' );
		$library = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider' => function () use ( $result ) {
					return $result['record'];
				},
			)
		);

		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 'api', $library->get_release_record()['source'] );
	}

	/**
	 * @dataProvider invalid_provider_record_provider
	 */
	public function test_provider_rejects_invalid_declared_record_fields( $field, $value, $expected_code ) {
		$record           = Better_Font_Awesome_Release_Data_Validator::validate_release( $this->get_valid_release(), 'api' )['record'];
		$record[ $field ] = $value;
		$library          = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider' => function () use ( $record ) {
					return $record;
				},
			)
		);

		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
		$this->assertInstanceOf( WP_Error::class, $library->get_error( 'provider' ) );
		$this->assertSame( $expected_code, $library->get_error( 'provider' )->get_error_code() );
	}

	public function invalid_provider_record_provider() {
		$invalid_release            = $this->get_valid_release();
		$invalid_release['version'] = '7.0.0';

		return array(
			'schema version' => array( 'schema_version', 999, 'bfa_record_schema_unsupported' ),
			'channel'        => array( 'channel', '7.x', 'bfa_record_channel_unsupported' ),
			'edition'        => array( 'edition', 'pro', 'bfa_record_edition_unsupported' ),
			'source'         => array( 'source', 'external', 'bfa_record_source_invalid' ),
			'release'        => array( 'release', $invalid_release, 'bfa_version_unsupported' ),
		);
	}

	public function test_valid_transient_is_used_without_refresh() {
		$this->prime_valid_transient();
		$refresh_calls = 0;
		$library       = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_refresh_callback' => function () use ( &$refresh_calls ) {
					++$refresh_calls;
				},
			)
		);

		$this->assertSame( '5.15.4', $library->get_version() );
		$this->assertSame( 'transient', $library->get_release_record()['source'] );
		$this->assertSame( 0, $refresh_calls );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_invalid_transient_is_rejected_and_fallback_is_used() {
		$GLOBALS['bfa_test_transients']['bfa-release-data'] = array( 'version' => 'not-valid' );
		$library = Better_Font_Awesome_Library::get_instance();

		$this->assertSame( '5.14.0', $library->get_version() );
		$this->assertInstanceOf( WP_Error::class, $library->get_error( 'cache' ) );
		$this->assertSame( 'bfa_version_invalid', $library->get_error( 'cache' )->get_error_code() );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_invalid_fallback_fails_closed_without_warnings_or_transport() {
		$file = tempnam( sys_get_temp_dir(), 'bfal-invalid-' );
		file_put_contents( $file, '{invalid' );
		add_filter(
			'bfa_fallback_release_data_path',
			function () use ( $file ) {
				return $file;
			}
		);

		try {
			$library = Better_Font_Awesome_Library::get_instance();
			$this->assertSame( '', $library->get_version() );
			$this->assertSame( '', $library->get_stylesheet_url() );
			$this->assertSame( '', $library->get_stylesheet_url_v4_shim() );
			$this->assertSame( array(), $library->get_icons() );
			$this->assertSame( array(), $library->get_release_icons() );
			$this->assertSame( array(), $library->get_release_assets() );
			$this->assertInstanceOf( WP_Error::class, $library->get_error( 'fallback' ) );
			$this->assertSame( 'bfa_invalid_json', $library->get_error( 'fallback' )->get_error_code() );
			$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
		} finally {
			unlink( $file );
		}
	}

	public function test_refresh_request_action_is_emitted_only_once() {
		$library = Better_Font_Awesome_Library::get_instance();
		$library->get_version();
		$library->get_icons();
		$library->request_release_data_refresh();

		$requests = array_filter(
			$GLOBALS['bfa_test_did_actions'],
			function ( $action ) {
				return 'bfa_release_data_refresh_requested' === $action['tag'];
			}
		);
		$this->assertCount( 1, $requests );
		$request = reset( $requests );
		$this->assertSame( '5.x', $request['arguments'][0] );
		$this->assertSame( $library, $request['arguments'][1] );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_successful_explicit_refresh_validates_then_persists_atomically() {
		$this->prime_valid_transient();
		$refreshed            = $this->get_valid_release();
		$refreshed['version'] = '5.15.3';
		$GLOBALS['bfa_test_http_response'] = $this->http_response(
			200,
			json_encode( array( 'data' => array( 'release' => $refreshed ) ) )
		);
		add_filter(
			'bfa_release_data_transient_expiration',
			function () {
				return 123;
			}
		);

		$library = Better_Font_Awesome_Library::get_instance();
		$result  = $library->refresh_release_data();

		$this->assertSame( $refreshed, $result );
		$this->assertSame( $refreshed, $GLOBALS['bfa_test_transients']['bfa-release-data'] );
		$this->assertSame( 123, $GLOBALS['bfa_test_transient_writes'][0]['expiration'] );
		$this->assertSame( '5.15.3', $library->get_version() );
		$this->assertSame( 'api', $library->get_release_record()['source'] );
		$this->assertSame( 1, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_transport_filters_cannot_disable_tls_or_resource_bounds() {
		$this->prime_valid_transient();
		add_filter(
			'bfa_wp_remote_get_args',
			function ( $args ) {
				$args['sslverify']           = false;
				$args['timeout']             = 99;
				$args['limit_response_size'] = PHP_INT_MAX;
				$args['redirection']         = 9;
				$args['reject_unsafe_urls']  = false;
				$args['blocking']            = false;
				return $args;
			}
		);
		$GLOBALS['bfa_test_http_response'] = $this->http_response(
			200,
			json_encode( array( 'data' => array( 'release' => $this->get_valid_release() ) ) )
		);

		Better_Font_Awesome_Library::get_instance()->refresh_release_data();
		$args = $GLOBALS['bfa_test_last_http_request']['args'];

		$this->assertTrue( $args['sslverify'] );
		$this->assertSame( 5, $args['timeout'] );
		$this->assertSame( Better_Font_Awesome_Release_Data_Validator::MAX_RESPONSE_BYTES, $args['limit_response_size'] );
		$this->assertSame( 0, $args['redirection'] );
		$this->assertTrue( $args['reject_unsafe_urls'] );
		$this->assertTrue( $args['blocking'] );
		$body = json_decode( $args['body'], true );
		$this->assertStringContainsString( 'release(version: "5.x")', $body['query'] );
	}

	/**
	 * @dataProvider transport_failure_provider
	 */
	public function test_transport_failures_are_sanitized_and_retain_prior_data( $code ) {
		$this->prime_valid_transient();
		$prior = $GLOBALS['bfa_test_transients']['bfa-release-data'];
		$GLOBALS['bfa_test_http_response'] = new WP_Error( $code, '<script>token=secret headers=private</script>' );
		$library = Better_Font_Awesome_Library::get_instance();

		$result = $library->refresh_release_data();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'bfa_transport_error', $result->get_error_code() );
		$this->assertSame( $prior, $GLOBALS['bfa_test_transients']['bfa-release-data'] );
		$this->assertStringNotContainsString( 'secret', $result->get_error_message() );
		$this->assertStringNotContainsString( '<script>', $result->get_error_message() );
	}

	public function transport_failure_provider() {
		return array(
			'timeout'    => array( 'http_request_failed_timeout' ),
			'DNS'        => array( 'http_request_failed_dns' ),
			'TLS'        => array( 'http_request_failed_ssl' ),
			'connection' => array( 'http_request_failed_connection' ),
		);
	}

	/**
	 * @dataProvider unsuccessful_status_provider
	 */
	public function test_non_2xx_statuses_are_failures_and_retain_prior_data( $status ) {
		$this->prime_valid_transient();
		$prior = $GLOBALS['bfa_test_transients']['bfa-release-data'];
		$GLOBALS['bfa_test_http_response'] = $this->http_response( $status, '<script>secret raw body</script>' );
		$result = Better_Font_Awesome_Library::get_instance()->refresh_release_data();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'bfa_http_error', $result->get_error_code() );
		$this->assertSame( $prior, $GLOBALS['bfa_test_transients']['bfa-release-data'] );
		$this->assertStringNotContainsString( 'secret', $result->get_error_message() );
	}

	public function unsuccessful_status_provider() {
		return array(
			'403' => array( 403 ),
			'429' => array( 429 ),
			'500' => array( 500 ),
			'unexpected status' => array( 418 ),
		);
	}

	/**
	 * @dataProvider malformed_refresh_provider
	 */
	public function test_malformed_refreshes_never_replace_prior_data( $body, $expected_code ) {
		$this->prime_valid_transient();
		$prior = $GLOBALS['bfa_test_transients']['bfa-release-data'];
		$GLOBALS['bfa_test_http_response'] = $this->http_response( 200, $body );
		$result = Better_Font_Awesome_Library::get_instance()->refresh_release_data();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $expected_code, $result->get_error_code() );
		$this->assertSame( $prior, $GLOBALS['bfa_test_transients']['bfa-release-data'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function malformed_refresh_provider() {
		$release = require __DIR__ . '/fixtures/valid-release.php';
		$empty_icons = $release;
		$empty_icons['icons'] = array();
		$unknown_style = $release;
		$unknown_style['icons'][0]['styles'][] = 'sharp';
		$bad_asset = $release;
		$bad_asset['srisByLicense']['free'][0]['path'] = '../all.css';

		return array(
			'GraphQL errors' => array( '{"errors":[{"message":"raw secret"}]}', 'bfa_graphql_error' ),
			'empty body' => array( '', 'bfa_response_empty' ),
			'oversized body' => array( str_repeat( 'x', Better_Font_Awesome_Release_Data_Validator::MAX_RESPONSE_BYTES + 1 ), 'bfa_response_too_large' ),
			'invalid JSON' => array( '{invalid', 'bfa_invalid_json' ),
			'missing data' => array( '{}', 'bfa_schema_missing_data' ),
			'missing release' => array( '{"data":{}}', 'bfa_schema_missing_release' ),
			'empty icons' => array( json_encode( array( 'data' => array( 'release' => $empty_icons ) ) ), 'bfa_icons_empty' ),
			'unknown style' => array( json_encode( array( 'data' => array( 'release' => $unknown_style ) ) ), 'bfa_style_unknown' ),
			'invalid asset' => array( json_encode( array( 'data' => array( 'release' => $bad_asset ) ) ), 'bfa_asset_path_invalid' ),
		);
	}

	public function test_persistence_failure_retains_in_memory_and_persisted_prior_data() {
		$this->prime_valid_transient();
		$prior                 = $GLOBALS['bfa_test_transients']['bfa-release-data'];
		$refreshed             = $this->get_valid_release();
		$refreshed['version']  = '5.15.3';
		$GLOBALS['bfa_test_set_transient_result'] = false;
		$GLOBALS['bfa_test_http_response'] = $this->http_response(
			200,
			json_encode( array( 'data' => array( 'release' => $refreshed ) ) )
		);
		$library = Better_Font_Awesome_Library::get_instance();
		$result  = $library->refresh_release_data();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'bfa_cache_write_failed', $result->get_error_code() );
		$this->assertSame( $prior, $GLOBALS['bfa_test_transients']['bfa-release-data'] );
		$this->assertSame( '5.15.4', $library->get_version() );
	}

	public function test_successful_refresh_recovers_after_a_prior_failure() {
		$this->prime_valid_transient();
		$GLOBALS['bfa_test_http_response'] = $this->http_response( 500, 'raw failure' );
		$library = Better_Font_Awesome_Library::get_instance();

		$this->assertInstanceOf( WP_Error::class, $library->refresh_release_data() );
		$this->assertInstanceOf( WP_Error::class, $library->get_error( 'api' ) );

		$refreshed            = $this->get_valid_release();
		$refreshed['version'] = '5.15.3';
		$GLOBALS['bfa_test_http_response'] = $this->http_response(
			200,
			json_encode( array( 'data' => array( 'release' => $refreshed ) ) )
		);

		$this->assertSame( $refreshed, $library->refresh_release_data() );
		$this->assertSame( '', $library->get_error( 'api' ) );
		$this->assertSame( '5.15.3', $library->get_version() );
	}

	public function test_unsupported_filtered_channel_fails_before_transport() {
		$this->prime_valid_transient();
		add_filter(
			'bfa_font_awesome_release_channel',
			function () {
				return '6.x';
			}
		);
		$result = Better_Font_Awesome_Library::get_instance()->refresh_release_data();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'bfa_channel_unsupported', $result->get_error_code() );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	public function test_provider_failures_and_notices_do_not_expose_raw_details() {
		$library = Better_Font_Awesome_Library::get_instance(
			array(
				'release_data_provider' => function () {
					return new WP_Error( 'secret_code', '<script>token=secret headers=private</script>' );
				},
			)
		);

		ob_start();
		$library->do_admin_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'bfa_provider_error', $output );
		$this->assertStringNotContainsString( 'token=secret', $output );
		$this->assertStringNotContainsString( 'headers=private', $output );
		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertAppliedFilterArgumentCount( 'bfa_show_errors', 1 );
	}

	public function test_data_source_filters_keep_their_established_argument_counts() {
		Better_Font_Awesome_Library::get_instance();
		$this->assertAppliedFilterArgumentCount( 'bfa_fallback_release_data_path', 1 );

		$this->prime_valid_transient();
		$GLOBALS['bfa_test_http_response'] = $this->http_response(
			200,
			json_encode( array( 'data' => array( 'release' => $this->get_valid_release() ) ) )
		);
		Better_Font_Awesome_Library::get_instance()->refresh_release_data();
		$this->assertAppliedFilterArgumentCount( 'bfa_release_data_transient_expiration', 1 );
		$this->assertAppliedFilterArgumentCount( 'bfa_font_awesome_release_channel', 1 );
	}

	private function http_response( $status, $body ) {
		return array(
			'response' => array(
				'code'    => $status,
				'message' => 'Raw upstream response message',
			),
			'body'     => $body,
		);
	}

	private function assertAppliedFilterArgumentCount( $tag, $count ) {
		foreach ( $GLOBALS['bfa_test_applied_filters'] as $filter ) {
			if ( $tag === $filter['tag'] && $count === $filter['arg_count'] ) {
				$this->addToAssertionCount( 1 );
				return;
			}
		}

		$this->fail( $tag . ' was not applied with ' . $count . ' argument(s).' );
	}
}
