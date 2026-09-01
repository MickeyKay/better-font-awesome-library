<?php

use PHPUnit\Framework\TestCase;

class ReleaseDataV2AdapterTest extends TestCase {
	public function test_family_aware_catalog_is_deterministic() {
		$record  = $this->get_record();
		$catalog = Better_Font_Awesome_Release_Data_V2_Adapter::get_catalog( $record );

		$this->assertSame(
			array(
				array( 'id' => 'address-book', 'label' => 'Address Book', 'family' => 'classic', 'style' => 'regular' ),
				array( 'id' => 'address-book', 'label' => 'Address Book', 'family' => 'classic', 'style' => 'solid' ),
				array( 'id' => 'github', 'label' => 'GitHub', 'family' => 'classic', 'style' => 'brands' ),
				array( 'id' => 'xmark', 'label' => 'Xmark', 'family' => 'classic', 'style' => 'solid' ),
			),
			$catalog
		);
	}

	public function test_canonical_name_precedes_alias_resolution() {
		$record = $this->get_record();

		$this->assertSame( 'xmark', Better_Font_Awesome_Release_Data_V2_Adapter::resolve_name( $record, 'xmark' ) );
		$this->assertSame( 'xmark', Better_Font_Awesome_Release_Data_V2_Adapter::resolve_name( $record, 'close' ) );
		$this->assertSame( 'address-book', Better_Font_Awesome_Release_Data_V2_Adapter::resolve_name( $record, 'contact-book' ) );
		$this->assertNull( Better_Font_Awesome_Release_Data_V2_Adapter::resolve_name( $record, 'not-an-icon' ) );
	}

	private function get_record() {
		$fixture = file_get_contents( __DIR__ . '/fixtures/font-awesome-7.3.1.json' );
		return Better_Font_Awesome_Release_Data_V2_Validator::parse_fixture_json( $fixture )['record'];
	}
}
