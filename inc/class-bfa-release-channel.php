<?php
/**
 * Internal Font Awesome release channel definitions.
 *
 * @package Better_Font_Awesome_Library
 */

if ( ! class_exists( 'Better_Font_Awesome_Release_Channel' ) ) :
	/**
	 * Major-aware release channel value helpers.
	 *
	 * This class defines the only release channels that the immutable runtime
	 * selection may activate.
	 *
	 * @internal
	 */
	class Better_Font_Awesome_Release_Channel {

		/** Existing production channel. */
		const FONT_AWESOME_5 = '5.x';

		/** Font Awesome 7 channel. */
		const FONT_AWESOME_7 = '7.x';

		/**
		 * Check whether a channel has an internal schema.
		 *
		 * @param mixed $channel Release channel.
		 * @return bool Whether the channel is known.
		 */
		public static function is_supported( $channel ) {
			return self::FONT_AWESOME_5 === $channel || self::FONT_AWESOME_7 === $channel;
		}

		/**
		 * Get the schema version for a known channel.
		 *
		 * @param mixed $channel Release channel.
		 * @return int|null Schema version, or null for an unknown channel.
		 */
		public static function get_schema_version( $channel ) {
			if ( self::FONT_AWESOME_5 === $channel ) {
				return 1;
			}

			if ( self::FONT_AWESOME_7 === $channel ) {
				return 2;
			}

			return null;
		}
	}
endif;
