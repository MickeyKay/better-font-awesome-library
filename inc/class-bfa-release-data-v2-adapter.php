<?php
/**
 * Internal Font Awesome 7 family-aware metadata adapter.
 *
 * @package Better_Font_Awesome_Library
 */

if ( ! class_exists( 'Better_Font_Awesome_Release_Data_V2_Adapter' ) ) :
	/**
	 * Adapt validated family/style metadata for internal consumers.
	 *
	 * @internal
	 */
	class Better_Font_Awesome_Release_Data_V2_Adapter {

		/**
		 * Build a deterministic family-aware catalog.
		 *
		 * @param mixed $record Validated schema 2 release record.
		 * @return array Catalog rows, or an empty array for invalid data.
		 */
		public static function get_catalog( $record ) {
			$result = Better_Font_Awesome_Release_Data_V2_Validator::validate_record( $record );
			if ( ! $result['valid'] ) {
				return array();
			}

			$catalog = array();
			foreach ( $result['record']['release']['icons'] as $icon ) {
				foreach ( $icon['familyStylesByLicense']['free'] as $membership ) {
					$catalog[] = array(
						'id'     => $icon['id'],
						'label'  => $icon['label'],
						'family' => $membership['family'],
						'style'  => $membership['style'],
					);
				}
			}

			usort(
				$catalog,
				function ( $left, $right ) {
					$left_key  = $left['id'] . "\0" . $left['family'] . "\0" . $left['style'];
					$right_key = $right['id'] . "\0" . $right['family'] . "\0" . $right['style'];
					return strcmp( $left_key, $right_key );
				}
			);

			return $catalog;
		}

		/**
		 * Resolve a canonical name first, then a validated alias.
		 *
		 * @param mixed  $record Validated schema 2 release record.
		 * @param string $name   Canonical icon name or alias.
		 * @return string|null Canonical icon name, or null when not found.
		 */
		public static function resolve_name( $record, $name ) {
			$result = Better_Font_Awesome_Release_Data_V2_Validator::validate_record( $record );
			if ( ! $result['valid'] || ! is_string( $name ) ) {
				return null;
			}

			$aliases = array();
			foreach ( $result['record']['release']['icons'] as $icon ) {
				if ( $name === $icon['id'] ) {
					return $icon['id'];
				}

				foreach ( $icon['aliases']['names'] as $alias ) {
					$aliases[ $alias ] = $icon['id'];
				}
			}

			return isset( $aliases[ $name ] ) ? $aliases[ $name ] : null;
		}
	}
endif;
