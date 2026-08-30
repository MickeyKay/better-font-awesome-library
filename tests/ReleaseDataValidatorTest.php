<?php

use PHPUnit\Framework\TestCase;

class ReleaseDataValidatorTest extends TestCase {
	public function test_valid_release_is_preserved_in_a_versioned_record() {
		$release = require __DIR__ . '/fixtures/valid-release.php';
		$result  = Better_Font_Awesome_Release_Data_Validator::validate_release( $release, 'provider' );

		$this->assertTrue( $result['valid'] );
		$this->assertNull( $result['error'] );
		$this->assertSame( 1, $result['record']['schema_version'] );
		$this->assertSame( '5.x', $result['record']['channel'] );
		$this->assertSame( 'free', $result['record']['edition'] );
		$this->assertSame( 'provider', $result['record']['source'] );
		$this->assertSame( $release, $result['record']['release'] );
	}

	public function test_valid_declared_record_is_preserved() {
		$release = require __DIR__ . '/fixtures/valid-release.php';
		$record  = Better_Font_Awesome_Release_Data_Validator::validate_release( $release, 'api' )['record'];
		$result  = Better_Font_Awesome_Release_Data_Validator::validate_record( $record );

		$this->assertTrue( $result['valid'] );
		$this->assertNull( $result['error'] );
		$this->assertSame( $record, $result['record'] );
	}

	/**
	 * @dataProvider invalid_record_provider
	 */
	public function test_invalid_declared_records_are_rejected( $mutator, $expected_code ) {
		$release = require __DIR__ . '/fixtures/valid-release.php';
		$record  = Better_Font_Awesome_Release_Data_Validator::validate_release( $release, 'api' )['record'];
		$record  = call_user_func( $mutator, $record );
		$result  = Better_Font_Awesome_Release_Data_Validator::validate_record( $record );

		$this->assertFalse( $result['valid'] );
		$this->assertSame( $expected_code, $result['error']['code'] );
	}

	public function invalid_record_provider() {
		return array(
			'missing schema version' => array(
				function ( $record ) {
					unset( $record['schema_version'] );
					return $record;
				},
				'bfa_record_schema_invalid',
			),
			'unsupported schema version' => array(
				function ( $record ) {
					$record['schema_version'] = 999;
					return $record;
				},
				'bfa_record_schema_unsupported',
			),
			'unsupported channel' => array(
				function ( $record ) {
					$record['channel'] = '7.x';
					return $record;
				},
				'bfa_record_channel_unsupported',
			),
			'unsupported edition' => array(
				function ( $record ) {
					$record['edition'] = 'pro';
					return $record;
				},
				'bfa_record_edition_unsupported',
			),
			'invalid source' => array(
				function ( $record ) {
					$record['source'] = 'external';
					return $record;
				},
				'bfa_record_source_invalid',
			),
			'missing release' => array(
				function ( $record ) {
					unset( $record['release'] );
					return $record;
				},
				'bfa_record_release_invalid',
			),
		);
	}

	public function test_bundled_fallback_is_valid() {
		$json   = file_get_contents( dirname( __DIR__ ) . '/inc/fallback-release-data.json' );
		$result = Better_Font_Awesome_Release_Data_Validator::parse_fallback_json( $json );

		$this->assertTrue( $result['valid'], isset( $result['error']['message'] ) ? $result['error']['message'] : '' );
		$this->assertSame( '5.14.0', $result['record']['release']['version'] );
		$this->assertSame( 'fallback', $result['record']['source'] );
	}

	public function test_bundled_fallback_checksum_matches() {
		$json     = file_get_contents( dirname( __DIR__ ) . '/inc/fallback-release-data.json' );
		$checksum = file_get_contents( dirname( __DIR__ ) . '/inc/fallback-release-data.sha256' );
		$expected = substr( trim( $checksum ), 0, 64 );

		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $expected );
		$this->assertSame( $expected, hash( 'sha256', $json ) );
	}

	public function test_valid_api_payload_is_parsed() {
		$release = require __DIR__ . '/fixtures/valid-release.php';
		$result  = Better_Font_Awesome_Release_Data_Validator::parse_api_response(
			json_encode( array( 'data' => array( 'release' => $release ) ) )
		);

		$this->assertTrue( $result['valid'] );
		$this->assertSame( 'api', $result['record']['source'] );
		$this->assertSame( $release, $result['record']['release'] );
	}

	/**
	 * @dataProvider invalid_payload_provider
	 */
	public function test_invalid_api_payloads_are_rejected( $body, $expected_code ) {
		$result = Better_Font_Awesome_Release_Data_Validator::parse_api_response( $body );

		$this->assertFalse( $result['valid'] );
		$this->assertSame( $expected_code, $result['error']['code'] );
		$this->assertLessThanOrEqual( 160, strlen( $result['error']['message'] ) );
	}

	public function invalid_payload_provider() {
		return array(
			'empty body'       => array( '', 'bfa_response_empty' ),
			'oversized body'   => array( str_repeat( 'x', Better_Font_Awesome_Release_Data_Validator::MAX_RESPONSE_BYTES + 1 ), 'bfa_response_too_large' ),
			'invalid json'     => array( '{invalid', 'bfa_invalid_json' ),
			'json scalar'      => array( 'true', 'bfa_invalid_json' ),
			'GraphQL errors'   => array( '{"errors":[{"message":"upstream raw detail"}]}', 'bfa_graphql_error' ),
			'missing data'     => array( '{}', 'bfa_schema_missing_data' ),
			'missing release'  => array( '{"data":{}}', 'bfa_schema_missing_release' ),
			'null release'     => array( '{"data":{"release":null}}', 'bfa_release_invalid' ),
		);
	}

	/**
	 * @dataProvider invalid_release_provider
	 */
	public function test_invalid_release_shapes_are_rejected( $mutator, $expected_code ) {
		$release = require __DIR__ . '/fixtures/valid-release.php';
		$release = call_user_func( $mutator, $release );
		$result  = Better_Font_Awesome_Release_Data_Validator::validate_release( $release );

		$this->assertFalse( $result['valid'] );
		$this->assertSame( $expected_code, $result['error']['code'] );
	}

	public function invalid_release_provider() {
		return array(
			'missing version' => array(
				function ( $release ) {
					unset( $release['version'] );
					return $release;
				},
				'bfa_version_invalid',
			),
			'invalid semantic version' => array(
				function ( $release ) {
					$release['version'] = 'latest';
					return $release;
				},
				'bfa_version_invalid',
			),
			'unsupported major' => array(
				function ( $release ) {
					$release['version'] = '6.0.0';
					return $release;
				},
				'bfa_version_unsupported',
			),
			'empty icons' => array(
				function ( $release ) {
					$release['icons'] = array();
					return $release;
				},
				'bfa_icons_empty',
			),
			'malformed icon' => array(
				function ( $release ) {
					unset( $release['icons'][0]['label'] );
					return $release;
				},
				'bfa_icon_invalid',
			),
			'malformed membership' => array(
				function ( $release ) {
					$release['icons'][0]['membership']['free'] = 'solid';
					return $release;
				},
				'bfa_membership_invalid',
			),
			'unknown style' => array(
				function ( $release ) {
					$release['icons'][0]['styles'][] = 'sharp';
					return $release;
				},
				'bfa_style_unknown',
			),
			'empty Free icon set' => array(
				function ( $release ) {
					foreach ( $release['icons'] as &$icon ) {
						$icon['membership']['free'] = array();
					}
					return $release;
				},
				'bfa_free_icons_empty',
			),
			'missing assets' => array(
				function ( $release ) {
					$release['srisByLicense']['free'] = array();
					return $release;
				},
				'bfa_assets_invalid',
			),
			'invalid asset path' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['path'] = '../all.css';
					return $release;
				},
				'bfa_asset_path_invalid',
			),
			'invalid integrity' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['value'] = '<script>raw</script>';
					return $release;
				},
				'bfa_asset_integrity_invalid',
			),
			'empty integrity digest' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['value'] = 'sha384-=';
					return $release;
				},
				'bfa_asset_integrity_invalid',
			),
			'invalid integrity padding' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['value'] = 'sha384-' . base64_encode( str_repeat( 'a', 48 ) ) . '=';
					return $release;
				},
				'bfa_asset_integrity_invalid',
			),
			'noncanonical integrity encoding' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['value'] = 'sha256-' . rtrim( base64_encode( str_repeat( 'a', 32 ) ), '=' );
					return $release;
				},
				'bfa_asset_integrity_invalid',
			),
			'invalid integrity digest length' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['value'] = 'sha384-' . base64_encode( str_repeat( 'a', 47 ) );
					return $release;
				},
				'bfa_asset_integrity_invalid',
			),
			'integrity algorithm length mismatch' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['value'] = 'sha256-' . base64_encode( str_repeat( 'a', 48 ) );
					return $release;
				},
				'bfa_asset_integrity_invalid',
			),
			'missing main stylesheet' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['path'] = 'css/solid.css';
					return $release;
				},
				'bfa_asset_missing',
			),
		);
	}

	/**
	 * @dataProvider valid_integrity_provider
	 */
	public function test_supported_integrity_algorithms_require_exact_digest_lengths( $algorithm, $digest_length ) {
		$release = require __DIR__ . '/fixtures/valid-release.php';
		$value   = $algorithm . '-' . base64_encode( str_repeat( 'a', $digest_length ) );

		foreach ( $release['srisByLicense']['free'] as &$asset ) {
			$asset['value'] = $value;
		}
		unset( $asset );

		$result = Better_Font_Awesome_Release_Data_Validator::validate_release( $release );
		$this->assertTrue( $result['valid'] );
	}

	public function valid_integrity_provider() {
		return array(
			'SHA-256' => array( 'sha256', 32 ),
			'SHA-384' => array( 'sha384', 48 ),
			'SHA-512' => array( 'sha512', 64 ),
		);
	}

	public function test_diagnostics_do_not_include_upstream_content() {
		$secret = '<script>token=secret headers=private</script>';
		$result = Better_Font_Awesome_Release_Data_Validator::parse_api_response(
			json_encode( array( 'errors' => array( array( 'message' => $secret ) ) ) )
		);

		$this->assertFalse( $result['valid'] );
		$this->assertStringNotContainsString( 'secret', $result['error']['message'] );
		$this->assertStringNotContainsString( 'headers', $result['error']['message'] );
		$this->assertStringNotContainsString( '<script>', $result['error']['message'] );
	}
}
