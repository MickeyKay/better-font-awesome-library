<?php
/**
 * Pure Font Awesome Free release data parsing and validation.
 *
 * @package Better_Font_Awesome_Library
 */

if ( ! class_exists( 'Better_Font_Awesome_Release_Data_Validator' ) ) :
	class Better_Font_Awesome_Release_Data_Validator {

		/** Version of the internal release record shape. */
		const SCHEMA_VERSION = 1;

		/** Supported Font Awesome release channel. */
		const RELEASE_CHANNEL = '5.x';

		/** Supported Font Awesome edition. */
		const EDITION = 'free';

		/** Maximum accepted encoded response size. */
		const MAX_RESPONSE_BYTES = 2097152;

		/**
		 * Parse and validate a Font Awesome GraphQL response body.
		 *
		 * @param string $body Encoded JSON response body.
		 * @return array Validation result.
		 */
		public static function parse_api_response( $body ) {
			$decoded = self::decode_json( $body );
			if ( ! $decoded['valid'] ) {
				return $decoded;
			}

			$payload = $decoded['data'];
			if ( ! empty( $payload['errors'] ) ) {
				return self::failure( 'bfa_graphql_error', 'The Font Awesome metadata service returned an error.' );
			}

			return self::validate_envelope( $payload, 'api' );
		}

		/**
		 * Parse and validate a bundled fallback JSON document.
		 *
		 * @param string $json Encoded fallback JSON.
		 * @return array Validation result.
		 */
		public static function parse_fallback_json( $json ) {
			$decoded = self::decode_json( $json );
			if ( ! $decoded['valid'] ) {
				return $decoded;
			}

			return self::validate_envelope( $decoded['data'], 'fallback' );
		}

		/**
		 * Validate a release array and wrap it in the internal record shape.
		 *
		 * The release array is returned without normalization so existing public
		 * getter shapes remain compatible with the GraphQL response.
		 *
		 * @param mixed  $release Release data.
		 * @param string $source  Data source identifier.
		 * @return array Validation result.
		 */
		public static function validate_release( $release, $source = 'unknown' ) {
			if ( ! is_array( $release ) ) {
				return self::failure( 'bfa_release_invalid', 'Font Awesome release data must be an array.' );
			}

			if ( ! isset( $release['version'] ) || ! is_string( $release['version'] ) ) {
				return self::failure( 'bfa_version_invalid', 'Font Awesome release data has an invalid version.' );
			}

			$version = $release['version'];
			if ( ! preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/', $version ) ) {
				return self::failure( 'bfa_version_invalid', 'Font Awesome release data has an invalid semantic version.' );
			}

			if ( 0 !== strpos( $version, '5.' ) ) {
				return self::failure( 'bfa_version_unsupported', 'The Font Awesome release is outside the supported 5.x channel.' );
			}

			$icons_result = self::validate_icons( isset( $release['icons'] ) ? $release['icons'] : null );
			if ( ! $icons_result['valid'] ) {
				return $icons_result;
			}

			$assets = isset( $release['srisByLicense']['free'] ) ? $release['srisByLicense']['free'] : null;
			$assets_result = self::validate_assets( $assets );
			if ( ! $assets_result['valid'] ) {
				return $assets_result;
			}

			$allowed_sources = array( 'api', 'fallback', 'provider', 'transient', 'unknown' );
			if ( ! in_array( $source, $allowed_sources, true ) ) {
				$source = 'unknown';
			}

			return array(
				'valid'  => true,
				'record' => array(
					'schema_version' => self::SCHEMA_VERSION,
					'channel'        => self::RELEASE_CHANNEL,
					'edition'        => self::EDITION,
					'source'         => $source,
					'release'        => $release,
				),
				'error'  => null,
			);
		}

		/**
		 * Decode a bounded JSON document.
		 *
		 * @param mixed $json JSON document.
		 * @return array Intermediate parse result.
		 */
		private static function decode_json( $json ) {
			if ( ! is_string( $json ) || '' === trim( $json ) ) {
				return self::failure( 'bfa_response_empty', 'The Font Awesome metadata response was empty.' );
			}

			if ( strlen( $json ) > self::MAX_RESPONSE_BYTES ) {
				return self::failure( 'bfa_response_too_large', 'The Font Awesome metadata response exceeded the size limit.' );
			}

			$decoded = json_decode( $json, true );
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
				return self::failure( 'bfa_invalid_json', 'The Font Awesome metadata response was not valid JSON.' );
			}

			return array(
				'valid' => true,
				'data'  => $decoded,
				'error' => null,
			);
		}

		/**
		 * Validate the common GraphQL envelope shape.
		 *
		 * @param array  $payload Decoded payload.
		 * @param string $source  Source identifier.
		 * @return array Validation result.
		 */
		private static function validate_envelope( $payload, $source ) {
			if ( ! isset( $payload['data'] ) || ! is_array( $payload['data'] ) ) {
				return self::failure( 'bfa_schema_missing_data', 'Font Awesome metadata is missing the data object.' );
			}

			if ( ! array_key_exists( 'release', $payload['data'] ) ) {
				return self::failure( 'bfa_schema_missing_release', 'Font Awesome metadata is missing the release object.' );
			}

			return self::validate_release( $payload['data']['release'], $source );
		}

		/**
		 * Validate icon metadata and Free membership.
		 *
		 * @param mixed $icons Icon rows.
		 * @return array Validation result.
		 */
		private static function validate_icons( $icons ) {
			if ( ! is_array( $icons ) || empty( $icons ) ) {
				return self::failure( 'bfa_icons_empty', 'Font Awesome release data must contain icons.' );
			}

			$known_styles      = array( 'brands', 'solid', 'regular', 'light', 'duotone' );
			$free_styles       = array( 'brands', 'solid', 'regular' );
			$free_icon_count   = 0;

			foreach ( $icons as $icon ) {
				if (
					! is_array( $icon ) ||
					! isset( $icon['id'], $icon['label'], $icon['membership'], $icon['styles'] ) ||
					! is_string( $icon['id'] ) ||
					! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $icon['id'] ) ||
					! is_string( $icon['label'] ) ||
					'' === trim( $icon['label'] ) ||
					! is_array( $icon['styles'] ) ||
					empty( $icon['styles'] )
				) {
					return self::failure( 'bfa_icon_invalid', 'Font Awesome release data contains a malformed icon.' );
				}

				foreach ( $icon['styles'] as $style ) {
					if ( ! is_string( $style ) || ! in_array( $style, $known_styles, true ) ) {
						return self::failure( 'bfa_style_unknown', 'Font Awesome release data contains an unknown style.' );
					}
				}

				if ( ! is_array( $icon['membership'] ) || ! isset( $icon['membership']['free'] ) || ! is_array( $icon['membership']['free'] ) ) {
					return self::failure( 'bfa_membership_invalid', 'Font Awesome release data contains malformed Free membership.' );
				}

				foreach ( $icon['membership']['free'] as $style ) {
					if (
						! is_string( $style ) ||
						! in_array( $style, $free_styles, true ) ||
						! in_array( $style, $icon['styles'], true )
					) {
						return self::failure( 'bfa_membership_invalid', 'Font Awesome release data contains invalid Free style membership.' );
					}
					++$free_icon_count;
				}
			}

			if ( 0 === $free_icon_count ) {
				return self::failure( 'bfa_free_icons_empty', 'Font Awesome release data contains no Free icons.' );
			}

			return array( 'valid' => true, 'error' => null );
		}

		/**
		 * Validate release asset identity and integrity values.
		 *
		 * @param mixed $assets Free asset rows.
		 * @return array Validation result.
		 */
		private static function validate_assets( $assets ) {
			if ( ! is_array( $assets ) || empty( $assets ) ) {
				return self::failure( 'bfa_assets_invalid', 'Font Awesome release data must contain Free assets.' );
			}

			$paths = array();
			foreach ( $assets as $asset ) {
				if ( ! is_array( $asset ) || ! isset( $asset['path'], $asset['value'] ) || ! is_string( $asset['path'] ) || ! is_string( $asset['value'] ) ) {
					return self::failure( 'bfa_assets_invalid', 'Font Awesome release data contains a malformed Free asset.' );
				}

				if ( ! preg_match( '#^(css|js)/[a-z0-9][a-z0-9._-]*\.(css|js)$#', $asset['path'] ) ) {
					return self::failure( 'bfa_asset_path_invalid', 'Font Awesome release data contains an invalid asset path.' );
				}

				if ( isset( $paths[ $asset['path'] ] ) ) {
					return self::failure( 'bfa_asset_path_invalid', 'Font Awesome release data contains a duplicate asset path.' );
				}
				$paths[ $asset['path'] ] = true;

				if ( ! preg_match( '/^sha(256|384|512)-[A-Za-z0-9+\/=]+$/', $asset['value'] ) ) {
					return self::failure( 'bfa_asset_integrity_invalid', 'Font Awesome release data contains an invalid asset integrity value.' );
				}
			}

			foreach ( array( 'css/all.css', 'css/v4-shims.css' ) as $required_path ) {
				if ( ! isset( $paths[ $required_path ] ) ) {
					return self::failure( 'bfa_asset_missing', 'Font Awesome release data is missing a required Free stylesheet.' );
				}
			}

			return array( 'valid' => true, 'error' => null );
		}

		/**
		 * Build a deterministic validation failure.
		 *
		 * @param string $code    Stable error code.
		 * @param string $message Sanitized error summary.
		 * @return array Failure result.
		 */
		private static function failure( $code, $message ) {
			return array(
				'valid'  => false,
				'record' => null,
				'error'  => array(
					'code'    => $code,
					'message' => $message,
				),
			);
		}
	}
endif;
