<?php
/**
 * Pure Font Awesome 7 Free release data parsing and validation.
 *
 * @package Better_Font_Awesome_Library
 */

if ( ! class_exists( 'Better_Font_Awesome_Release_Data_V2_Validator' ) ) :
	/**
	 * Validate the internal family-aware schema used by the 7.x channel.
	 *
	 * @internal
	 */
	class Better_Font_Awesome_Release_Data_V2_Validator {

		/** Version of the family-aware release record shape. */
		const SCHEMA_VERSION = 2;

		/** Release channel represented by this schema. */
		const RELEASE_CHANNEL = '7.x';

		/** Supported Font Awesome edition. */
		const EDITION = 'free';

		/** Maximum accepted encoded release document size. */
		const MAX_RESPONSE_BYTES = 2097152;

		/**
		 * Parse a bounded recorded fixture envelope.
		 *
		 * @param mixed $json Encoded fixture JSON.
		 * @return array Validation result.
		 */
		public static function parse_fixture_json( $json ) {
			$decoded = self::decode_json( $json );
			if ( ! $decoded['valid'] ) {
				return $decoded;
			}

			if (
				! isset( $decoded['data']['data'] ) ||
				! is_array( $decoded['data']['data'] ) ||
				! array_key_exists( 'release', $decoded['data']['data'] )
			) {
				return self::failure( 'bfa_v2_schema_invalid', 'Font Awesome 7 metadata is missing the release object.' );
			}

			return self::validate_release( $decoded['data']['data']['release'], 'fixture' );
		}

		/**
		 * Parse a bounded internal record document.
		 *
		 * @param mixed $json Encoded record JSON.
		 * @return array Validation result.
		 */
		public static function parse_record_json( $json ) {
			$decoded = self::decode_json( $json );
			if ( ! $decoded['valid'] ) {
				return $decoded;
			}

			return self::validate_record( $decoded['data'] );
		}

		/**
		 * Validate a Font Awesome 7 Free release.
		 *
		 * The input release remains unchanged in the resulting record.
		 *
		 * @param mixed  $release Release data.
		 * @param string $source  Internal source identifier.
		 * @return array Validation result.
		 */
		public static function validate_release( $release, $source = 'unknown' ) {
			if ( ! self::is_valid_source( $source ) ) {
				return self::failure( 'bfa_v2_record_source_invalid', 'The Font Awesome 7 release record has an invalid source.' );
			}

			if ( ! is_array( $release ) ) {
				return self::failure( 'bfa_v2_release_invalid', 'Font Awesome 7 release data must be an array.' );
			}

			$encoded = json_encode( $release );
			if ( false === $encoded || strlen( $encoded ) > self::MAX_RESPONSE_BYTES ) {
				return self::failure( 'bfa_v2_response_too_large', 'The Font Awesome 7 metadata exceeded the size limit.' );
			}

			if ( ! isset( $release['version'] ) || ! is_string( $release['version'] ) ) {
				return self::failure( 'bfa_v2_version_invalid', 'Font Awesome 7 release data has an invalid version.' );
			}

			if ( ! preg_match( '/^7\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\z/', $release['version'] ) ) {
				if ( preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\z/', $release['version'] ) ) {
					return self::failure( 'bfa_v2_version_unsupported', 'The Font Awesome release is outside the internal 7.x channel.' );
				}

				return self::failure( 'bfa_v2_version_invalid', 'Font Awesome 7 release data has an invalid semantic version.' );
			}

			$icons_result = self::validate_icons( isset( $release['icons'] ) ? $release['icons'] : null );
			if ( ! $icons_result['valid'] ) {
				return $icons_result;
			}

			if ( ! isset( $release['srisByLicense'] ) || ! is_array( $release['srisByLicense'] ) ) {
				return self::failure( 'bfa_v2_assets_invalid', 'Font Awesome 7 release data must contain Free assets.' );
			}

			if ( self::contains_non_free_key( $release['srisByLicense'] ) ) {
				return self::failure( 'bfa_v2_edition_leakage', 'Font Awesome 7 release data contains assets outside the Free edition.' );
			}

			$assets = isset( $release['srisByLicense']['free'] ) ? $release['srisByLicense']['free'] : null;
			$assets_result = self::validate_assets( $assets );
			if ( ! $assets_result['valid'] ) {
				return $assets_result;
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
		 * Validate a declared family-aware record.
		 *
		 * @param mixed $record Release record.
		 * @return array Validation result.
		 */
		public static function validate_record( $record ) {
			if ( ! is_array( $record ) ) {
				return self::failure( 'bfa_v2_record_invalid', 'The Font Awesome 7 release record must be an array.' );
			}

			if ( ! isset( $record['schema_version'] ) || self::SCHEMA_VERSION !== $record['schema_version'] ) {
				return self::failure( 'bfa_v2_record_schema_invalid', 'The Font Awesome 7 release record has an invalid schema version.' );
			}

			if ( ! isset( $record['channel'] ) || self::RELEASE_CHANNEL !== $record['channel'] ) {
				return self::failure( 'bfa_v2_record_channel_invalid', 'The Font Awesome 7 release record has an invalid channel.' );
			}

			if ( ! isset( $record['edition'] ) || self::EDITION !== $record['edition'] ) {
				return self::failure( 'bfa_v2_record_edition_invalid', 'The Font Awesome 7 release record has an invalid edition.' );
			}

			if ( ! isset( $record['source'] ) || ! self::is_valid_source( $record['source'] ) ) {
				return self::failure( 'bfa_v2_record_source_invalid', 'The Font Awesome 7 release record has an invalid source.' );
			}

			if ( ! array_key_exists( 'release', $record ) ) {
				return self::failure( 'bfa_v2_record_release_invalid', 'The Font Awesome 7 release record is missing release data.' );
			}

			return self::validate_release( $record['release'], $record['source'] );
		}

		/**
		 * Validate canonical icon data, aliases, and Free family/style rows.
		 *
		 * @param mixed $icons Icon rows.
		 * @return array Validation result.
		 */
		private static function validate_icons( $icons ) {
			if ( ! is_array( $icons ) || empty( $icons ) ) {
				return self::failure( 'bfa_v2_icons_empty', 'Font Awesome 7 release data must contain icons.' );
			}

			$canonical_ids = array();
			foreach ( $icons as $icon ) {
				if (
					! is_array( $icon ) ||
					! isset( $icon['id'], $icon['label'] ) ||
					! is_string( $icon['id'] ) ||
					! self::is_valid_name( $icon['id'] ) ||
					! is_string( $icon['label'] ) ||
					'' === trim( $icon['label'] ) ||
					strlen( $icon['label'] ) > 200
				) {
					return self::failure( 'bfa_v2_icon_invalid', 'Font Awesome 7 release data contains a malformed icon.' );
				}

				if ( isset( $canonical_ids[ $icon['id'] ] ) ) {
					return self::failure( 'bfa_v2_icon_duplicate', 'Font Awesome 7 release data contains a duplicate canonical icon ID.' );
				}

				$canonical_ids[ $icon['id'] ] = true;
			}

			$aliases = array();
			foreach ( $icons as $icon ) {
				if (
					! isset( $icon['aliases'] ) ||
					! is_array( $icon['aliases'] ) ||
					! isset( $icon['aliases']['names'] ) ||
					! is_array( $icon['aliases']['names'] )
				) {
					return self::failure( 'bfa_v2_alias_invalid', 'Font Awesome 7 release data contains malformed name aliases.' );
				}

				foreach ( $icon['aliases']['names'] as $alias ) {
					if ( ! is_string( $alias ) || ! self::is_valid_name( $alias ) || $alias === $icon['id'] ) {
						return self::failure( 'bfa_v2_alias_invalid', 'Font Awesome 7 release data contains a malformed name alias.' );
					}

					if ( isset( $canonical_ids[ $alias ] ) || isset( $aliases[ $alias ] ) ) {
						return self::failure( 'bfa_v2_alias_collision', 'Font Awesome 7 release data contains a colliding name alias.' );
					}

					$aliases[ $alias ] = $icon['id'];
				}

				if ( ! isset( $icon['familyStylesByLicense'] ) || ! is_array( $icon['familyStylesByLicense'] ) ) {
					return self::failure( 'bfa_v2_membership_invalid', 'Font Awesome 7 release data contains malformed Free family membership.' );
				}

				if ( self::contains_non_free_key( $icon['familyStylesByLicense'] ) ) {
					return self::failure( 'bfa_v2_edition_leakage', 'Font Awesome 7 release data contains icon membership outside the Free edition.' );
				}

				$memberships = isset( $icon['familyStylesByLicense']['free'] ) ? $icon['familyStylesByLicense']['free'] : null;
				if ( ! is_array( $memberships ) || empty( $memberships ) ) {
					return self::failure( 'bfa_v2_membership_invalid', 'Font Awesome 7 release data contains incomplete Free family membership.' );
				}

				$seen_memberships = array();
				foreach ( $memberships as $membership ) {
					if ( ! is_array( $membership ) || ! isset( $membership['family'], $membership['style'] ) || ! is_string( $membership['family'] ) || ! is_string( $membership['style'] ) ) {
						return self::failure( 'bfa_v2_membership_invalid', 'Font Awesome 7 release data contains malformed Free family membership.' );
					}

					if ( 'classic' !== $membership['family'] ) {
						return self::failure( 'bfa_v2_family_unknown', 'Font Awesome 7 release data contains an unknown Free family.' );
					}

					if ( ! in_array( $membership['style'], array( 'brands', 'regular', 'solid' ), true ) ) {
						return self::failure( 'bfa_v2_style_unknown', 'Font Awesome 7 release data contains an unknown Free style.' );
					}

					$key = $membership['family'] . '/' . $membership['style'];
					if ( isset( $seen_memberships[ $key ] ) ) {
						return self::failure( 'bfa_v2_membership_invalid', 'Font Awesome 7 release data contains duplicate Free family membership.' );
					}
					$seen_memberships[ $key ] = true;
				}
			}

			return array( 'valid' => true, 'error' => null );
		}

		/**
		 * Validate the exact CSS metadata required by the Font Awesome 7 baseline.
		 *
		 * @param mixed $assets Free asset rows.
		 * @return array Validation result.
		 */
		private static function validate_assets( $assets ) {
			if ( ! is_array( $assets ) || empty( $assets ) ) {
				return self::failure( 'bfa_v2_assets_invalid', 'Font Awesome 7 release data must contain Free assets.' );
			}

			$required_paths = array(
				'css/all.min.css',
				'css/v4-font-face.min.css',
				'css/v4-shims.min.css',
				'css/v5-font-face.min.css',
			);
			$paths = array();

			foreach ( $assets as $asset ) {
				if ( ! is_array( $asset ) || ! isset( $asset['path'], $asset['value'] ) || ! is_string( $asset['path'] ) || ! is_string( $asset['value'] ) ) {
					return self::failure( 'bfa_v2_assets_invalid', 'Font Awesome 7 release data contains a malformed Free asset.' );
				}

				if ( ! in_array( $asset['path'], $required_paths, true ) ) {
					return self::failure( 'bfa_v2_asset_path_invalid', 'Font Awesome 7 release data contains an invalid asset path.' );
				}

				if ( isset( $paths[ $asset['path'] ] ) ) {
					return self::failure( 'bfa_v2_asset_path_duplicate', 'Font Awesome 7 release data contains a duplicate asset path.' );
				}

				if ( ! self::is_valid_integrity_value( $asset['value'] ) ) {
					return self::failure( 'bfa_v2_asset_integrity_invalid', 'Font Awesome 7 release data contains an invalid asset integrity value.' );
				}

				$paths[ $asset['path'] ] = true;
			}

			foreach ( $required_paths as $required_path ) {
				if ( ! isset( $paths[ $required_path ] ) ) {
					return self::failure( 'bfa_v2_asset_missing', 'Font Awesome 7 release data is missing a required Free stylesheet.' );
				}
			}

			return array( 'valid' => true, 'error' => null );
		}

		/**
		 * Check canonical SRI syntax and digest length.
		 *
		 * @param string $value Integrity value.
		 * @return bool Whether the value is valid.
		 */
		private static function is_valid_integrity_value( $value ) {
			if ( ! preg_match( '/^(sha256|sha384|sha512)-([A-Za-z0-9+\/]+={0,2})\z/', $value, $matches ) ) {
				return false;
			}

			$decoded = base64_decode( $matches[2], true );
			if ( false === $decoded || base64_encode( $decoded ) !== $matches[2] ) {
				return false;
			}

			$lengths = array(
				'sha256' => 32,
				'sha384' => 48,
				'sha512' => 64,
			);

			return $lengths[ $matches[1] ] === strlen( $decoded );
		}

		/**
		 * Check whether a license-indexed object includes anything but Free.
		 *
		 * @param array $value License-indexed data.
		 * @return bool Whether non-Free data is present.
		 */
		private static function contains_non_free_key( $value ) {
			foreach ( array_keys( $value ) as $key ) {
				if ( 'free' !== $key ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check a canonical icon name or name alias.
		 *
		 * @param string $name Icon name.
		 * @return bool Whether the name is valid.
		 */
		private static function is_valid_name( $name ) {
			return strlen( $name ) <= 128 && 1 === preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*\z/', $name );
		}

		/**
		 * Decode bounded JSON.
		 *
		 * @param mixed $json JSON document.
		 * @return array Intermediate parse result.
		 */
		private static function decode_json( $json ) {
			if ( ! is_string( $json ) || '' === trim( $json ) ) {
				return self::failure( 'bfa_v2_response_empty', 'The Font Awesome 7 metadata response was empty.' );
			}

			if ( strlen( $json ) > self::MAX_RESPONSE_BYTES ) {
				return self::failure( 'bfa_v2_response_too_large', 'The Font Awesome 7 metadata response exceeded the size limit.' );
			}

			$decoded = json_decode( $json, true );
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
				return self::failure( 'bfa_v2_invalid_json', 'The Font Awesome 7 metadata response was not valid JSON.' );
			}

			return array( 'valid' => true, 'data' => $decoded, 'error' => null );
		}

		/**
		 * Check an internal source identifier.
		 *
		 * @param mixed $source Source identifier.
		 * @return bool Whether the source is valid.
		 */
		private static function is_valid_source( $source ) {
			return is_string( $source ) && in_array( $source, array( 'api', 'fallback', 'fixture', 'provider', 'transient', 'unknown' ), true );
		}

		/**
		 * Create a sanitized validation failure.
		 *
		 * @param string $code    Stable error code.
		 * @param string $message Safe diagnostic.
		 * @return array Validation result.
		 */
		private static function failure( $code, $message ) {
			return array(
				'valid' => false,
				'error' => array(
					'code'    => $code,
					'message' => $message,
				),
			);
		}
	}
endif;
