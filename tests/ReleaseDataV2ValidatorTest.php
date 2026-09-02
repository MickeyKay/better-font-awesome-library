<?php

use PHPUnit\Framework\TestCase;

class ReleaseDataV2ValidatorTest extends TestCase {
	public function test_recorded_font_awesome_7_fixture_is_valid() {
		$result = Better_Font_Awesome_Release_Data_V2_Validator::parse_fixture_json( $this->get_fixture_json() );

		$this->assertTrue( $result['valid'] );
		$this->assertSame( 2, $result['record']['schema_version'] );
		$this->assertSame( '7.x', $result['record']['channel'] );
		$this->assertSame( 'free', $result['record']['edition'] );
		$this->assertSame( 'fixture', $result['record']['source'] );
		$this->assertSame( '7.3.1', $result['record']['release']['version'] );
	}

	public function test_recorded_font_awesome_5_fixture_remains_valid_schema_1_data() {
		$fixture = json_decode( file_get_contents( __DIR__ . '/fixtures/font-awesome-5.15.4.json' ), true );
		$result  = Better_Font_Awesome_Release_Data_Validator::validate_release( $fixture, 'provider' );

		$this->assertTrue( $result['valid'] );
		$this->assertSame( 1, $result['record']['schema_version'] );
		$this->assertSame( '5.x', $result['record']['channel'] );
		$this->assertSame( $fixture, $result['record']['release'] );
	}

	public function test_declared_schema_2_record_is_validated_without_rewriting_release_data() {
		$release = $this->get_release();
		$record  = Better_Font_Awesome_Release_Data_V2_Validator::validate_release( $release, 'fallback' )['record'];
		$result  = Better_Font_Awesome_Release_Data_V2_Validator::validate_record( $record );

		$this->assertTrue( $result['valid'] );
		$this->assertSame( $record, $result['record'] );
		$this->assertSame( $release, $result['record']['release'] );
	}

	/**
	 * @dataProvider invalid_release_provider
	 */
	public function test_malformed_and_adversarial_releases_are_rejected( $mutator, $expected_code ) {
		$release = call_user_func( $mutator, $this->get_release() );
		$result  = Better_Font_Awesome_Release_Data_V2_Validator::validate_release( $release );

		$this->assertFalse( $result['valid'] );
		$this->assertSame( $expected_code, $result['error']['code'] );
	}

	public function invalid_release_provider() {
		return array(
			'version with final line feed' => array(
				function ( $release ) {
					$release['version'] .= "\n";
					return $release;
				},
				'bfa_v2_version_invalid',
			),
			'canonical ID with final line feed' => array(
				function ( $release ) {
					$release['icons'][0]['id'] .= "\n";
					return $release;
				},
				'bfa_v2_icon_invalid',
			),
			'alias with final line feed' => array(
				function ( $release ) {
					$release['icons'][0]['aliases']['names'][0] .= "\n";
					return $release;
				},
				'bfa_v2_alias_invalid',
			),
			'SRI with final line feed' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['value'] .= "\n";
					return $release;
				},
				'bfa_v2_asset_integrity_invalid',
			),
			'partial icon' => array(
				function ( $release ) {
					unset( $release['icons'][0]['label'] );
					return $release;
				},
				'bfa_v2_icon_invalid',
			),
			'malformed aliases' => array(
				function ( $release ) {
					$release['icons'][0]['aliases']['names'] = 'contact-book';
					return $release;
				},
				'bfa_v2_alias_invalid',
			),
			'duplicate canonical ID' => array(
				function ( $release ) {
					$release['icons'][] = $release['icons'][0];
					return $release;
				},
				'bfa_v2_icon_duplicate',
			),
			'alias collision' => array(
				function ( $release ) {
					$release['icons'][1]['aliases']['names'][] = 'contact-book';
					return $release;
				},
				'bfa_v2_alias_collision',
			),
			'alias collides with canonical ID' => array(
				function ( $release ) {
					$release['icons'][0]['aliases']['names'][] = 'github';
					return $release;
				},
				'bfa_v2_alias_collision',
			),
			'unknown family' => array(
				function ( $release ) {
					$release['icons'][0]['familyStylesByLicense']['free'][0]['family'] = 'sharp';
					return $release;
				},
				'bfa_v2_family_unknown',
			),
			'unknown style' => array(
				function ( $release ) {
					$release['icons'][0]['familyStylesByLicense']['free'][0]['style'] = 'light';
					return $release;
				},
				'bfa_v2_style_unknown',
			),
			'cross-license icon data' => array(
				function ( $release ) {
					$release['icons'][0]['familyStylesByLicense']['pro'] = array(
						array( 'family' => 'classic', 'style' => 'light' ),
					);
					return $release;
				},
				'bfa_v2_edition_leakage',
			),
			'cross-license asset data' => array(
				function ( $release ) {
					$release['srisByLicense']['pro'] = $release['srisByLicense']['free'];
					return $release;
				},
				'bfa_v2_edition_leakage',
			),
			'invalid asset path' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['path'] = '../all.min.css';
					return $release;
				},
				'bfa_v2_asset_path_invalid',
			),
			'duplicate asset path' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][] = $release['srisByLicense']['free'][0];
					return $release;
				},
				'bfa_v2_asset_path_duplicate',
			),
			'invalid integrity' => array(
				function ( $release ) {
					$release['srisByLicense']['free'][0]['value'] = 'sha512-not-base64';
					return $release;
				},
				'bfa_v2_asset_integrity_invalid',
			),
			'missing required asset' => array(
				function ( $release ) {
					array_pop( $release['srisByLicense']['free'] );
					return $release;
				},
				'bfa_v2_asset_missing',
			),
			'unsupported release identity' => array(
				function ( $release ) {
					$release['version'] = '6.7.2';
					return $release;
				},
				'bfa_v2_version_unsupported',
			),
		);
	}

	public function test_oversized_fixture_is_rejected_before_decoding() {
		$json   = str_repeat( 'x', Better_Font_Awesome_Release_Data_V2_Validator::MAX_RESPONSE_BYTES + 1 );
		$result = Better_Font_Awesome_Release_Data_V2_Validator::parse_fixture_json( $json );

		$this->assertFalse( $result['valid'] );
		$this->assertSame( 'bfa_v2_response_too_large', $result['error']['code'] );
	}

	private function get_fixture_json() {
		return file_get_contents( __DIR__ . '/fixtures/font-awesome-7.3.1.json' );
	}

	private function get_release() {
		return json_decode( $this->get_fixture_json(), true )['data']['release'];
	}
}
