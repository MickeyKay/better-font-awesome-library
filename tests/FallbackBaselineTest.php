<?php

use PHPUnit\Framework\TestCase;

class FallbackBaselineTest extends TestCase {
	private $fallback_root;

	protected function setUp(): void {
		$this->fallback_root = dirname( __DIR__ ) . '/inc/font-awesome-7-fallback';
	}

	public function test_fallback_has_exact_minimal_inventory() {
		$expected = array(
			'ATTRIBUTION.md',
			'LICENSE.txt',
			'css/all.min.css',
			'css/v4-font-face.min.css',
			'css/v4-shims.min.css',
			'css/v5-font-face.min.css',
			'metadata.json',
			'provenance.json',
			'provenance.sha256',
			'webfonts/fa-brands-400.woff2',
			'webfonts/fa-regular-400.woff2',
			'webfonts/fa-solid-900.woff2',
			'webfonts/fa-v4compatibility.woff2',
		);

		$this->assertSame( $expected, $this->get_inventory() );
	}

	public function test_fallback_metadata_is_a_valid_inactive_schema_2_record() {
		$result = Better_Font_Awesome_Release_Data_V2_Validator::parse_record_json(
			file_get_contents( $this->fallback_root . '/metadata.json' )
		);

		$this->assertTrue( $result['valid'], isset( $result['error']['message'] ) ? $result['error']['message'] : '' );
		$this->assertSame( '7.3.1', $result['record']['release']['version'] );
		$this->assertSame( 'fallback', $result['record']['source'] );
		$this->assertCount( 1992, $result['record']['release']['icons'] );

		foreach ( $result['record']['release']['icons'] as $icon ) {
			$this->assertSame( array( 'free' ), array_keys( $icon['familyStylesByLicense'] ) );
		}
		$this->assertSame( array( 'free' ), array_keys( $result['record']['release']['srisByLicense'] ) );
	}

	public function test_provenance_checksum_and_per_file_hashes_match() {
		$provenance_json = file_get_contents( $this->fallback_root . '/provenance.json' );
		$checksum        = trim( file_get_contents( $this->fallback_root . '/provenance.sha256' ) );
		$provenance      = json_decode( $provenance_json, true );

		$this->assertSame( hash( 'sha256', $provenance_json ) . '  provenance.json', $checksum );
		$this->assertSame( '@fortawesome/fontawesome-free', $provenance['package']['name'] );
		$this->assertSame( '7.3.1', $provenance['package']['version'] );
		$this->assertSame( '(CC-BY-4.0 AND OFL-1.1 AND MIT)', $provenance['package']['license'] );
		$this->assertSame( 'sha512-wmglKKPDIkgV3aWlZzWECCPoGIkYCulzBwxG9+w7rc5BGapZ6cPMpoPOT8k36J0Ni7PPX6c/rsoMWfS4d1MUMg==', $provenance['package']['integrity'] );
		$this->assertSame( '9d7d7df8f850637e001d24e6cd901422b4b09099', $provenance['package']['shasum'] );

		foreach ( $provenance['files'] as $file ) {
			$path = $this->fallback_root . '/' . $file['path'];
			$this->assertFileExists( $path );
			$this->assertSame( $file['bytes'], filesize( $path ), $file['path'] );
			$this->assertSame( $file['sha256'], hash_file( 'sha256', $path ), $file['path'] );
			$this->assertSame( $file['sha512'], hash_file( 'sha512', $path ), $file['path'] );
		}
	}

	public function test_metadata_integrity_values_match_packaged_stylesheets() {
		$metadata = json_decode( file_get_contents( $this->fallback_root . '/metadata.json' ), true );
		$paths    = array();

		foreach ( $metadata['release']['srisByLicense']['free'] as $asset ) {
			$path = $this->fallback_root . '/' . $asset['path'];
			$this->assertFileExists( $path );
			$this->assertSame( 'sha512-' . base64_encode( hash_file( 'sha512', $path, true ) ), $asset['value'] );
			$paths[] = $asset['path'];
		}

		$this->assertSame(
			array(
				'css/all.min.css',
				'css/v4-font-face.min.css',
				'css/v4-shims.min.css',
				'css/v5-font-face.min.css',
			),
			$paths
		);
	}

	public function test_css_uses_only_packaged_relative_webfont_paths() {
		$css_files = glob( $this->fallback_root . '/css/*.css' );
		$this->assertCount( 4, $css_files );

		$referenced = array();
		foreach ( $css_files as $css_file ) {
			$css = file_get_contents( $css_file );
			preg_match_all( '#url\(([^)]+)\)#', $css, $matches );
			foreach ( $matches[1] as $url ) {
				$url = trim( $url, " \t\n\r\0\x0B\"'" );
				$this->assertMatchesRegularExpression( '#^\.\./webfonts/fa-[a-z0-9-]+\.woff2$#', $url );
				$this->assertFileExists( $this->fallback_root . '/css/' . $url );
				$referenced[ basename( $url ) ] = true;
			}
		}

		$this->assertSame(
			array(
				'fa-brands-400.woff2',
				'fa-regular-400.woff2',
				'fa-solid-900.woff2',
				'fa-v4compatibility.woff2',
			),
			array_keys( $referenced )
		);
	}

	public function test_upstream_license_and_attribution_are_packaged() {
		$license     = file_get_contents( $this->fallback_root . '/LICENSE.txt' );
		$attribution = file_get_contents( $this->fallback_root . '/ATTRIBUTION.md' );

		$this->assertStringContainsString( 'Font Awesome Free License', $license );
		$this->assertStringContainsString( '# Icons: CC BY 4.0 License', $license );
		$this->assertStringContainsString( '# Fonts: SIL OFL 1.1 License', $license );
		$this->assertStringContainsString( '# Code: MIT License', $license );
		$this->assertStringContainsString( '@fortawesome/fontawesome-free@7.3.1', $attribution );
	}

	public function test_generated_json_has_deterministic_ordering() {
		$metadata   = json_decode( file_get_contents( $this->fallback_root . '/metadata.json' ), true );
		$provenance = json_decode( file_get_contents( $this->fallback_root . '/provenance.json' ), true );
		$icon_ids   = array_column( $metadata['release']['icons'], 'id' );
		$sorted_ids = $icon_ids;
		sort( $sorted_ids );

		$this->assertSame( $sorted_ids, $icon_ids );
		foreach ( $metadata['release']['icons'] as $icon ) {
			$aliases        = $icon['aliases']['names'];
			$sorted_aliases = $aliases;
			sort( $sorted_aliases );
			$this->assertSame( $sorted_aliases, $aliases );
		}

		$paths        = array_column( $provenance['files'], 'path' );
		$sorted_paths = $paths;
		sort( $sorted_paths );
		$this->assertSame( $sorted_paths, $paths );
		$this->assertStringEndsWith( "\n", file_get_contents( $this->fallback_root . '/metadata.json' ) );
		$this->assertStringEndsWith( "\n", file_get_contents( $this->fallback_root . '/provenance.json' ) );
	}

	public function test_inactive_fallback_does_not_replace_font_awesome_5_runtime_fallback() {
		$runtime = file_get_contents( dirname( __DIR__ ) . '/better-font-awesome-library.php' );

		$this->assertSame( 'inc/fallback-release-data.json', Better_Font_Awesome_Library::FALLBACK_RELEASE_DATA_PATH );
		$this->assertSame( '5.x', Better_Font_Awesome_Library::get_instance()->get_release_channel() );
		$this->assertStringNotContainsString( 'font-awesome-7-fallback', $runtime );
		$this->assertSame( 0, $GLOBALS['bfa_test_http_calls'] );
	}

	private function get_inventory() {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $this->fallback_root, FilesystemIterator::SKIP_DOTS )
		);
		$files    = array();
		foreach ( $iterator as $file ) {
			$this->assertFalse( $file->isLink(), $file->getPathname() );
			if ( $file->isFile() ) {
				$files[] = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $this->fallback_root ) + 1 ) );
			}
		}
		sort( $files );
		return $files;
	}
}
