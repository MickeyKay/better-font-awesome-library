<?php

use PHPUnit\Framework\TestCase;

class ReleaseChannelTest extends TestCase {
	public function test_internal_channel_foundation_recognizes_only_approved_majors() {
		$this->assertTrue( Better_Font_Awesome_Release_Channel::is_supported( '5.x' ) );
		$this->assertTrue( Better_Font_Awesome_Release_Channel::is_supported( '7.x' ) );
		$this->assertFalse( Better_Font_Awesome_Release_Channel::is_supported( '6.x' ) );
		$this->assertSame( 1, Better_Font_Awesome_Release_Channel::get_schema_version( '5.x' ) );
		$this->assertSame( 2, Better_Font_Awesome_Release_Channel::get_schema_version( '7.x' ) );
		$this->assertNull( Better_Font_Awesome_Release_Channel::get_schema_version( '6.x' ) );
	}
}
