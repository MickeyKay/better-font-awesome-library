<?php
/**
 * jsDelivr Fetcher class
 *
 * A class to retrieve remote jsDelivr data using the
 * jsDelivr API along with helper functions.
 *
 * @since 0.9.0
 *
 * @package Better Font Awesome Library
 */

if ( ! class_exists( 'jsDeliver_Fetcher') ):
class jsDeliver_Fetcher {

	/**
	 * Constants
	 */
	const CDN_URL = 'http://api.jsdelivr.com/v1/jsdelivr/libraries/fontawesome/?fields=versions,lastversion';

	/**
	 * Properties
	 */
	private $cdn_data;

	function __construct() {
		$this->get_cdn_data();
	}

	private function get_cdn_data() {
		$this->cdn_data = $this->fetch_cdn_data( self::CDN_URL )[0];
	}
	
	private function fetch_cdn_data( $url ) {
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
   			$data = $response->get_error_message();
   		} else {
			$data = json_decode( wp_remote_retrieve_body( $response ) );
		}

		return $data;
	}

	function get_value( $value ) {
		return $this->cdn_data->$value;
	}
}
endif;