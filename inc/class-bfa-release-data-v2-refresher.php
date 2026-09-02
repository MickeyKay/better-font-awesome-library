<?php
/**
 * Bounded Font Awesome 7 Free release refresh worker.
 *
 * @package Better_Font_Awesome_Library
 */

if ( ! class_exists( 'Better_Font_Awesome_Release_Data_V2_Refresher' ) ) :
	/**
	 * Discover and completely validate one Font Awesome 7 candidate.
	 *
	 * @internal
	 */
	class Better_Font_Awesome_Release_Data_V2_Refresher {

		/** Hard request budget for one new-candidate attempt. */
		const MAX_REQUESTS = 18;

		/** Hard aggregate response-body budget. */
		const MAX_TOTAL_BYTES = 4194304;

		/** Hard wall-clock budget in seconds. */
		const MAX_TOTAL_SECONDS = 30;

		/** Maximum timeout for one request. */
		const MAX_REQUEST_SECONDS = 5;

		/** Maximum Font Awesome metadata response size. */
		const MAX_METADATA_BYTES = 2097152;

		/** Maximum exact npm version response size. */
		const MAX_NPM_BYTES = 262144;

		/** Expected upstream package identity. */
		const NPM_PACKAGE = '@fortawesome/fontawesome-free';

		/** Expected upstream package license identity. */
		const NPM_LICENSE = '(CC-BY-4.0 AND OFL-1.1 AND MIT)';

		/** @var array Base WordPress HTTP arguments. */
		private $request_args;

		/** @var int Number of requests made. */
		private $request_count = 0;

		/** @var int Number of response bytes consumed. */
		private $response_bytes = 0;

		/** @var float Attempt start time. */
		private $started_at;

		/**
		 * Perform one bounded refresh attempt.
		 *
		 * @param array $request_args  Base WordPress HTTP arguments.
		 * @param array $current_record Completely validated current schema-2 record.
		 * @return array|WP_Error Complete validated schema-2 record or sanitized error.
		 */
		public static function refresh( $request_args, $current_record ) {
			$worker = new self( $request_args );
			return $worker->run( $current_record );
		}

		/**
		 * Initialize one attempt.
		 *
		 * @param array $request_args Base WordPress HTTP arguments.
		 */
		private function __construct( $request_args ) {
			$this->request_args = is_array( $request_args ) ? $request_args : array();
			$this->started_at   = microtime( true );
		}

		/**
		 * Run discovery and new-candidate validation.
		 *
		 * @param array $current_record Current schema-2 record.
		 * @return array|WP_Error Valid record or error.
		 */
		private function run( $current_record ) {
			$current = Better_Font_Awesome_Release_Data_V2_Validator::validate_record( $current_record );
			if ( ! $current['valid'] ) {
				return $this->failure( 'bfa_v2_current_invalid', 'The current Font Awesome 7 release record was invalid.' );
			}

			$candidate = $this->discover_candidate();
			if ( is_wp_error( $candidate ) ) {
				return $candidate;
			}

			$current_version   = $current['record']['release']['version'];
			$candidate_version = $candidate['version'];
			$comparison        = version_compare( $candidate_version, $current_version );
			if ( 0 === $comparison ) {
				return $current['record'];
			}

			if ( $comparison < 0 ) {
				return $this->failure( 'bfa_v2_version_regression', 'Font Awesome metadata discovery returned an older 7.x release.' );
			}

			$npm = $this->confirm_npm_version( $candidate_version );
			if ( is_wp_error( $npm ) ) {
				return $npm;
			}

			$assets = $this->validate_cross_provider_assets( $candidate_version );
			if ( is_wp_error( $assets ) ) {
				return $assets;
			}

			$candidate['srisByLicense'] = array(
				'free' => $assets['sris'],
			);
			$result = Better_Font_Awesome_Release_Data_V2_Validator::validate_release( $candidate, 'api' );
			if ( ! $result['valid'] ) {
				return $this->validation_failure( $result );
			}

			return $result['record'];
		}

		/**
		 * Discover the latest 7.x Free icon metadata from Font Awesome.
		 *
		 * @return array|WP_Error Candidate release without asset SRI values, or error.
		 */
		private function discover_candidate() {
			$args            = $this->request_args;
			$args['headers'] = array( 'Content-Type' => 'application/json' );
			$args['body']    = wp_json_encode(
				array(
					'query' => 'query { release(version: "7.x") { version icons(license: "free") { id label aliases { names } familyStylesByLicense { free { family style } } } } }',
				)
			);

			$body = $this->request( 'POST', Better_Font_Awesome_Library::FONT_AWESOME_API_BASE_URL, $args, self::MAX_METADATA_BYTES, 'metadata' );
			if ( is_wp_error( $body ) ) {
				return $body;
			}

			$decoded = json_decode( $body, true );
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
				return $this->failure( 'bfa_v2_invalid_json', 'The Font Awesome 7 metadata response was not valid JSON.' );
			}

			if ( ! empty( $decoded['errors'] ) ) {
				return $this->failure( 'bfa_v2_graphql_error', 'The Font Awesome 7 metadata service rejected the release query.' );
			}

			if ( ! isset( $decoded['data']['release'] ) || ! is_array( $decoded['data']['release'] ) ) {
				return $this->failure( 'bfa_v2_schema_invalid', 'Font Awesome 7 metadata was missing the release object.' );
			}

			$release = $decoded['data']['release'];
			if ( ! isset( $release['icons'] ) || ! is_array( $release['icons'] ) ) {
				return $this->failure( 'bfa_v2_icons_empty', 'Font Awesome 7 metadata did not contain Free icons.' );
			}

			foreach ( $release['icons'] as &$icon ) {
				if ( is_array( $icon ) && ! isset( $icon['aliases'] ) ) {
					$icon['aliases'] = array( 'names' => array() );
				}
			}
			unset( $icon );

			$placeholder = $release;
			$placeholder['srisByLicense'] = array(
				'free' => $this->placeholder_sris(),
			);
			$validated = Better_Font_Awesome_Release_Data_V2_Validator::validate_release( $placeholder, 'api' );
			if ( ! $validated['valid'] ) {
				return $this->validation_failure( $validated );
			}

			return array(
				'version' => $release['version'],
				'icons'   => $release['icons'],
			);
		}

		/**
		 * Confirm that the exact official npm package version is published.
		 *
		 * @param string $version Candidate version.
		 * @return true|WP_Error True or error.
		 */
		private function confirm_npm_version( $version ) {
			$url  = 'https://registry.npmjs.org/%40fortawesome%2Ffontawesome-free/' . rawurlencode( $version );
			$body = $this->request( 'GET', $url, $this->request_args, self::MAX_NPM_BYTES, 'publication' );
			if ( is_wp_error( $body ) ) {
				return $body;
			}

			$metadata = json_decode( $body, true );
			if (
				JSON_ERROR_NONE !== json_last_error() ||
				! is_array( $metadata ) ||
				! isset( $metadata['name'], $metadata['version'], $metadata['license'] ) ||
				self::NPM_PACKAGE !== $metadata['name'] ||
				$version !== $metadata['version'] ||
				self::NPM_LICENSE !== $metadata['license']
			) {
				return $this->failure( 'bfa_v2_publication_invalid', 'The exact Font Awesome Free npm package identity was invalid.' );
			}

			return true;
		}

		/**
		 * Validate exact cdnjs assets against the corresponding jsDelivr npm files.
		 *
		 * @param string $version Candidate version.
		 * @return array|WP_Error SRI rows and validated asset bodies, or error.
		 */
		private function validate_cross_provider_assets( $version ) {
			$limits = array(
				'css/all.min.css'                    => 524288,
				'css/v4-font-face.min.css'           => 131072,
				'css/v4-shims.min.css'               => 262144,
				'css/v5-font-face.min.css'           => 131072,
				'webfonts/fa-brands-400.woff2'       => 524288,
				'webfonts/fa-regular-400.woff2'      => 262144,
				'webfonts/fa-solid-900.woff2'        => 524288,
				'webfonts/fa-v4compatibility.woff2'  => 131072,
			);
			$css_bodies = array();
			$sris       = array();

			foreach ( $limits as $path => $limit ) {
				$cdnjs_url = sprintf(
					'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/%s/%s',
					rawurlencode( $version ),
					$path
				);
				$jsdelivr_url = sprintf(
					'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@%s/%s',
					rawurlencode( $version ),
					$path
				);

				$cdnjs = $this->request( 'GET', $cdnjs_url, $this->request_args, $limit, 'publication' );
				if ( is_wp_error( $cdnjs ) ) {
					return $cdnjs;
				}

				$jsdelivr = $this->request( 'GET', $jsdelivr_url, $this->request_args, $limit, 'publication' );
				if ( is_wp_error( $jsdelivr ) ) {
					return $jsdelivr;
				}

				if ( '' === $cdnjs || ! hash_equals( hash( 'sha512', $cdnjs ), hash( 'sha512', $jsdelivr ) ) ) {
					return $this->failure( 'bfa_v2_provider_disagreement', 'The Font Awesome asset providers disagreed on exact candidate bytes.' );
				}

				if ( 0 === strpos( $path, 'css/' ) ) {
					$css_bodies[ $path ] = $cdnjs;
					$sris[] = array(
						'path'  => $path,
						'value' => 'sha512-' . base64_encode( hash( 'sha512', $cdnjs, true ) ),
					);
				}
			}

			$css_result = $this->validate_css_font_references( $css_bodies );
			if ( is_wp_error( $css_result ) ) {
				return $css_result;
			}

			return array( 'sris' => $sris );
		}

		/**
		 * Require CSS to reference only the exact validated WOFF2 inventory.
		 *
		 * @param array $css_bodies CSS keyed by relative path.
		 * @return true|WP_Error True or error.
		 */
		private function validate_css_font_references( $css_bodies ) {
			$required = array(
				'fa-brands-400.woff2',
				'fa-regular-400.woff2',
				'fa-solid-900.woff2',
				'fa-v4compatibility.woff2',
			);
			$found = array();

			foreach ( $css_bodies as $css ) {
				preg_match_all( '#url\(([^)]+)\)#', $css, $matches );
				foreach ( $matches[1] as $url ) {
					$url = trim( $url, " \t\n\r\0\x0B\"'" );
					if ( ! preg_match( '#^\.\./webfonts/(fa-[a-z0-9-]+\.woff2)\z#', $url, $path_match ) || ! in_array( $path_match[1], $required, true ) ) {
						return $this->failure( 'bfa_v2_font_reference_invalid', 'Font Awesome candidate CSS referenced an invalid font asset.' );
					}
					$found[ $path_match[1] ] = true;
				}
			}

			if ( $required !== array_values( array_intersect( $required, array_keys( $found ) ) ) ) {
				return $this->failure( 'bfa_v2_font_missing', 'Font Awesome candidate CSS did not reference every required font asset.' );
			}

			return true;
		}

		/**
		 * Execute one budgeted WordPress HTTP request.
		 *
		 * @param string $method       GET or POST.
		 * @param string $url          Exact allowlisted URL.
		 * @param array  $args         Request arguments.
		 * @param int    $response_max Per-response byte limit.
		 * @param string $failure_type Failure category.
		 * @return string|WP_Error Response body or error.
		 */
		private function request( $method, $url, $args, $response_max, $failure_type ) {
			$elapsed   = microtime( true ) - $this->started_at;
			$remaining = self::MAX_TOTAL_SECONDS - $elapsed;
			if ( $this->request_count >= self::MAX_REQUESTS || $remaining <= 0 ) {
				return $this->failure( 'bfa_v2_budget_exceeded', 'The Font Awesome 7 refresh exceeded its request or time budget.' );
			}

			$remaining_bytes = self::MAX_TOTAL_BYTES - $this->response_bytes;
			if ( $remaining_bytes <= 0 ) {
				return $this->failure( 'bfa_v2_budget_exceeded', 'The Font Awesome 7 refresh exceeded its response byte budget.' );
			}

			$configured_timeout = isset( $args['timeout'] ) ? (float) $args['timeout'] : self::MAX_REQUEST_SECONDS;
			$configured_limit   = isset( $args['limit_response_size'] ) ? (int) $args['limit_response_size'] : $response_max;

			$args['sslverify']           = true;
			$args['redirection']         = 0;
			$args['reject_unsafe_urls']  = true;
			$args['blocking']            = true;
			$args['timeout']             = max( 0.1, min( $configured_timeout, self::MAX_REQUEST_SECONDS, $remaining ) );
			$args['limit_response_size'] = max( 1, min( $configured_limit, (int) $response_max, $remaining_bytes ) );
			++$this->request_count;

			$response = 'POST' === $method ? wp_remote_post( $url, $args ) : wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				return $this->failure( 'bfa_v2_transport_error', 'A required Font Awesome 7 provider could not be reached.' );
			}

			$status = (int) wp_remote_retrieve_response_code( $response );
			if ( $status < 200 || $status >= 300 ) {
				$code = 'publication' === $failure_type ? 'bfa_v2_publication_lag' : 'bfa_v2_http_error';
				return $this->failure( $code, 'A required Font Awesome 7 provider did not publish the candidate successfully.' );
			}

			$body        = wp_remote_retrieve_body( $response );
			$body_length = strlen( $body );
			if ( $body_length >= $args['limit_response_size'] ) {
				return $this->failure( 'bfa_v2_response_too_large', 'A Font Awesome 7 provider response reached its strict size limit.' );
			}

			$this->response_bytes += $body_length;
			if ( $this->response_bytes > self::MAX_TOTAL_BYTES || ( microtime( true ) - $this->started_at ) > self::MAX_TOTAL_SECONDS ) {
				return $this->failure( 'bfa_v2_budget_exceeded', 'The Font Awesome 7 refresh exceeded its overall resource budget.' );
			}

			return $body;
		}

		/**
		 * Build syntactically valid temporary SRI rows for early metadata validation.
		 *
		 * @return array Placeholder SRI rows.
		 */
		private function placeholder_sris() {
			$integrity = 'sha512-' . base64_encode( str_repeat( "\0", 64 ) );
			$rows      = array();
			foreach ( array( 'css/all.min.css', 'css/v4-font-face.min.css', 'css/v4-shims.min.css', 'css/v5-font-face.min.css' ) as $path ) {
				$rows[] = array(
					'path'  => $path,
					'value' => $integrity,
				);
			}
			return $rows;
		}

		/**
		 * Convert a validator failure to a sanitized WordPress error.
		 *
		 * @param array $result Validator result.
		 * @return WP_Error Error.
		 */
		private function validation_failure( $result ) {
			$error = isset( $result['error'] ) && is_array( $result['error'] ) ? $result['error'] : array();
			return $this->failure(
				isset( $error['code'] ) ? $error['code'] : 'bfa_v2_validation_error',
				isset( $error['message'] ) ? $error['message'] : 'Font Awesome 7 candidate validation failed.'
			);
		}

		/**
		 * Create a sanitized worker failure.
		 *
		 * @param string $code    Stable error code.
		 * @param string $message Safe diagnostic.
		 * @return WP_Error Error.
		 */
		private function failure( $code, $message ) {
			return new WP_Error( $code, $message );
		}
	}
endif;
