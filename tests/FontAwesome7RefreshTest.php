<?php

require_once __DIR__ . '/BfalTestCase.php';

class FontAwesome7RefreshTest extends BfalTestCase {
	public function test_successful_new_candidate_returns_complete_record_without_bfal_persistence() {
		$assets = $this->get_asset_bodies();
		$this->install_successful_responses( '7.3.2', $assets );
		$library = Better_Font_Awesome_Library::get_instance();

		$result = $library->refresh_release_data();

		$this->assertIsArray( $result );
		$this->assertSame( 2, $result['schema_version'] );
		$this->assertSame( '7.x', $result['channel'] );
		$this->assertSame( 'free', $result['edition'] );
		$this->assertSame( 'api', $result['source'] );
		$this->assertSame( '7.3.2', $result['release']['version'] );
		$this->assertSame(
			'sha512-' . base64_encode( hash( 'sha512', $assets['css/all.min.css'], true ) ),
			$result['release']['srisByLicense']['free'][0]['value']
		);
		$this->assertSame( 18, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transients'] );
		$this->assertSame( $result, $library->get_release_record() );
		$this->assertSame( $result['release']['srisByLicense']['free'], $library->get_release_assets() );
		$this->assertSame( '7.3.2', $library->get_version() );
		$this->assertSame(
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.2/css/all.min.css',
			$library->get_stylesheet_url()
		);
		$tag = '<link rel="stylesheet" id="bfa-font-awesome-css" href="remote.css" />';
		$this->assertStringContainsString(
			'integrity="' . $result['release']['srisByLicense']['free'][0]['value'] . '"',
			apply_filters( 'style_loader_tag', $tag, 'bfa-font-awesome' )
		);
	}

	public function test_already_current_record_stops_after_metadata_discovery() {
		$GLOBALS['bfa_test_http_callback'] = function ( $method, $url ) {
			$this->assertSame( 'POST', $method );
			$this->assertSame( Better_Font_Awesome_Library::FONT_AWESOME_API_BASE_URL, $url );
			return $this->http_response( 200, $this->metadata_body( '7.3.1' ) );
		};
		$library = Better_Font_Awesome_Library::get_instance();
		$this->assertSame(
			'https://example.test/plugin/inc/font-awesome-7-fallback/css/all.min.css',
			$library->get_stylesheet_url()
		);

		$result = $library->refresh_release_data();

		$this->assertSame( '7.3.1', $result['release']['version'] );
		$this->assertSame( 'fallback', $result['source'] );
		$this->assertSame( 1, $GLOBALS['bfa_test_http_calls'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
		$this->assertSame(
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css',
			$library->get_stylesheet_url()
		);
		$this->assertSame( $result['release']['srisByLicense']['free'], $library->get_release_assets() );
	}

	public function test_worker_enforces_exact_request_and_resource_budget_contract() {
		$this->install_successful_responses( '7.3.2', $this->get_asset_bodies() );
		Better_Font_Awesome_Library::get_instance()->refresh_release_data();

		$this->assertSame( 18, Better_Font_Awesome_Release_Data_V2_Refresher::MAX_REQUESTS );
		$this->assertSame( 4 * 1024 * 1024, Better_Font_Awesome_Release_Data_V2_Refresher::MAX_TOTAL_BYTES );
		$this->assertSame( 30, Better_Font_Awesome_Release_Data_V2_Refresher::MAX_TOTAL_SECONDS );
		$this->assertCount( 18, $GLOBALS['bfa_test_http_requests'] );
		foreach ( $GLOBALS['bfa_test_http_requests'] as $request ) {
			$this->assertTrue( $request['args']['sslverify'] );
			$this->assertSame( 0, $request['args']['redirection'] );
			$this->assertTrue( $request['args']['reject_unsafe_urls'] );
			$this->assertTrue( $request['args']['blocking'] );
			$this->assertLessThanOrEqual( 5, $request['args']['timeout'] );
			$this->assertLessThanOrEqual( Better_Font_Awesome_Release_Data_V2_Refresher::MAX_TOTAL_BYTES, $request['args']['limit_response_size'] );
		}
	}

	public function test_malformed_metadata_retains_the_bundled_current_record() {
		$GLOBALS['bfa_test_http_response'] = $this->http_response( 200, '{invalid' );
		$library = Better_Font_Awesome_Library::get_instance();

		$result = $library->refresh_release_data();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'bfa_v2_invalid_json', $result->get_error_code() );
		$this->assertSame( '7.3.1', $library->get_version() );
		$this->assertSame( 'fallback', $library->get_release_record()['source'] );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_provider_disagreement_rejects_the_complete_candidate() {
		$assets = $this->get_asset_bodies();
		$this->install_successful_responses(
			'7.3.2',
			$assets,
			function ( $method, $url, $body, $path ) {
				if ( false !== strpos( $url, 'cdn.jsdelivr.net' ) && 'css/all.min.css' === $path ) {
					return $body . 'different';
				}
				return $body;
			}
		);
		$library = Better_Font_Awesome_Library::get_instance();

		$result = $library->refresh_release_data();

		$this->assertSame( 'bfa_v2_provider_disagreement', $result->get_error_code() );
		$this->assertSame( '7.3.1', $library->get_version() );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_invalid_css_font_reference_rejects_the_candidate() {
		$assets = $this->get_asset_bodies();
		$assets['css/all.min.css'] = str_replace( 'fa-v4compatibility.woff2', 'fa-evil.woff2', $assets['css/all.min.css'] );
		$this->install_successful_responses( '7.3.2', $assets );

		$result = Better_Font_Awesome_Library::get_instance()->refresh_release_data();

		$this->assertSame( 'bfa_v2_font_reference_invalid', $result->get_error_code() );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_redirected_asset_is_a_publication_lag_failure() {
		$assets = $this->get_asset_bodies();
		$this->install_successful_responses(
			'7.3.2',
			$assets,
			function ( $method, $url, $body, $path ) {
				if ( false !== strpos( $url, 'cdnjs.cloudflare.com' ) && 'css/v4-shims.min.css' === $path ) {
					return $this->http_response( 302, '' );
				}
				return $body;
			}
		);

		$result = Better_Font_Awesome_Library::get_instance()->refresh_release_data();

		$this->assertSame( 'bfa_v2_publication_lag', $result->get_error_code() );
		$this->assertSame( 0, $GLOBALS['bfa_test_last_http_request']['args']['redirection'] );
	}

	public function test_missing_required_asset_rejects_the_candidate() {
		$assets = $this->get_asset_bodies();
		$this->install_successful_responses(
			'7.3.2',
			$assets,
			function ( $method, $url, $body, $path ) {
				if ( false !== strpos( $url, 'cdnjs.cloudflare.com' ) && 'webfonts/fa-solid-900.woff2' === $path ) {
					return $this->http_response( 404, '' );
				}
				return $body;
			}
		);

		$result = Better_Font_Awesome_Library::get_instance()->refresh_release_data();

		$this->assertSame( 'bfa_v2_publication_lag', $result->get_error_code() );
		$this->assertSame( '7.3.1', Better_Font_Awesome_Library::get_instance()->get_version() );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_timeout_is_sanitized_and_recovery_succeeds_on_the_next_attempt() {
		$GLOBALS['bfa_test_http_response'] = new WP_Error( 'http_request_failed', '<script>secret timeout</script>' );
		$library = Better_Font_Awesome_Library::get_instance();

		$failed = $library->refresh_release_data();
		$this->assertSame( 'bfa_v2_transport_error', $failed->get_error_code() );
		$this->assertStringNotContainsString( 'secret', $failed->get_error_message() );

		$this->install_successful_responses( '7.3.2', $this->get_asset_bodies() );
		$recovered = $library->refresh_release_data();

		$this->assertSame( '7.3.2', $recovered['release']['version'] );
		$this->assertSame( '', $library->get_error( 'api' ) );
		$this->assertSame( array(), $GLOBALS['bfa_test_transient_writes'] );
	}

	public function test_metadata_response_limit_fails_closed() {
		$GLOBALS['bfa_test_http_response'] = $this->http_response(
			200,
			str_repeat( 'x', Better_Font_Awesome_Release_Data_V2_Refresher::MAX_METADATA_BYTES )
		);

		$result = Better_Font_Awesome_Library::get_instance()->refresh_release_data();

		$this->assertSame( 'bfa_v2_response_too_large', $result->get_error_code() );
		$this->assertSame( '7.3.1', Better_Font_Awesome_Library::get_instance()->get_version() );
	}

	public function test_exact_npm_version_publication_lag_stops_before_asset_downloads() {
		$GLOBALS['bfa_test_http_callback'] = function ( $method, $url ) {
			if ( 'POST' === $method ) {
				return $this->http_response( 200, $this->metadata_body( '7.3.2' ) );
			}
			$this->assertStringContainsString( 'registry.npmjs.org', $url );
			return $this->http_response( 404, '' );
		};

		$result = Better_Font_Awesome_Library::get_instance()->refresh_release_data();

		$this->assertSame( 'bfa_v2_publication_lag', $result->get_error_code() );
		$this->assertSame( 2, $GLOBALS['bfa_test_http_calls'] );
	}

	private function install_successful_responses( $version, $assets, $mutate = null ) {
		$GLOBALS['bfa_test_http_callback'] = function ( $method, $url, $args ) use ( $version, $assets, $mutate ) {
			if ( 'POST' === $method ) {
				$this->assertSame( Better_Font_Awesome_Library::FONT_AWESOME_API_BASE_URL, $url );
				$query = json_decode( $args['body'], true );
				$this->assertStringContainsString( 'release(version: "7.x")', $query['query'] );
				$this->assertStringContainsString( 'icons(license: "free")', $query['query'] );
				return $this->http_response( 200, $this->metadata_body( $version ) );
			}

			if ( false !== strpos( $url, 'registry.npmjs.org' ) ) {
				return $this->http_response(
					200,
					json_encode(
						array(
							'name'    => '@fortawesome/fontawesome-free',
							'version' => $version,
							'license' => '(CC-BY-4.0 AND OFL-1.1 AND MIT)',
						)
					)
				);
			}

			foreach ( $assets as $path => $body ) {
				if ( substr( $url, -strlen( $path ) ) !== $path ) {
					continue;
				}

				$value = is_callable( $mutate ) ? call_user_func( $mutate, $method, $url, $body, $path ) : $body;
				if ( is_array( $value ) || is_wp_error( $value ) ) {
					return $value;
				}
				return $this->http_response( 200, $value );
			}

			return new WP_Error( 'unexpected_http', 'Unexpected HTTP request.' );
		};
	}

	private function metadata_body( $version ) {
		$fixture            = json_decode( file_get_contents( __DIR__ . '/fixtures/font-awesome-7.3.1.json' ), true );
		$release            = $fixture['data']['release'];
		$release['version'] = $version;
		unset( $release['srisByLicense'] );

		return json_encode( array( 'data' => array( 'release' => $release ) ) );
	}

	private function get_asset_bodies() {
		return array(
			'css/all.min.css'                   => '.all{src:url(../webfonts/fa-brands-400.woff2)}.regular{src:url(../webfonts/fa-regular-400.woff2)}.solid{src:url(../webfonts/fa-solid-900.woff2)}.v4{src:url(../webfonts/fa-v4compatibility.woff2)}',
			'css/v4-font-face.min.css'          => '.v4{src:url(../webfonts/fa-v4compatibility.woff2)}',
			'css/v4-shims.min.css'              => '.shim{display:inline-block}',
			'css/v5-font-face.min.css'          => '.v5{src:url(../webfonts/fa-solid-900.woff2)}',
			'webfonts/fa-brands-400.woff2'      => 'brands-font',
			'webfonts/fa-regular-400.woff2'     => 'regular-font',
			'webfonts/fa-solid-900.woff2'       => 'solid-font',
			'webfonts/fa-v4compatibility.woff2' => 'v4-font',
		);
	}

	private function http_response( $status, $body ) {
		return array(
			'response' => array(
				'code'    => $status,
				'message' => 'Mock response',
			),
			'body' => $body,
		);
	}
}
