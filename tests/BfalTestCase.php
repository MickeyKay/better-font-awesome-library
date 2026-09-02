<?php

use PHPUnit\Framework\TestCase;

abstract class BfalTestCase extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		bfa_test_reset_wordpress_state();
		$this->reset_singleton();
	}

	protected function tearDown(): void {
		$this->reset_singleton();
		parent::tearDown();
	}

	protected function get_valid_release() {
		return require __DIR__ . '/fixtures/valid-release.php';
	}

	protected function prime_valid_transient() {
		$GLOBALS['bfa_test_transients']['bfa-release-data'] = $this->get_valid_release();
	}

	protected function get_instance( $args = array() ) {
		$this->prime_valid_transient();
		$args = array_merge( array( 'release_channel' => '5.x' ), $args );
		return Better_Font_Awesome_Library::get_instance( $args );
	}

	private function reset_singleton() {
		$property = new ReflectionProperty( Better_Font_Awesome_Library::class, 'instance' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}
		$property->setValue( null, null );
	}
}
