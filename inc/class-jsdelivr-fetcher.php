<?php
/**
 * jsDelivr Fetcher class
 *
 * A class to retrieve remote jsDelivr data about the Font Awesome CDN
 * using the jsDelivr API along with helper functions.
 *
 * @since 0.9.0
 *
 * @package Better Font Awesome Library
 */

if ( ! class_exists( 'jsDeliver_Fetcher' ) ) :
class jsDeliver_Fetcher {

	/**
	 * Constants
	 */
	const API_URL = 'http://api.jsdelivr.com/v1/jsdelivr/libraries/fontawesome/?fields=versions,lastversion';

	/**
	 * Properties
	 */
	private $api_data;
	private $api_fetch_succeeded = false;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	function __construct() {
		$this->api_data = $this->fetch_api_data( self::API_URL );
	}

	/**
	 * Returns the instance of this class, and initializes
	 * the instance if it doesn't already exist
	 *
	 * @return Better_Font_Awesome_Library The BFAL object
	 */
	public static function get_instance( $args = '' ) {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static( $args );
		}

		return $instance;
	}

	private function fetch_api_data( $url ) {

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$response = $response->get_error_message();
		} else {
			$response = json_decode( wp_remote_retrieve_body( $response ) )[0];
			$this->api_fetch_succeeded = true;
		}

		return $response;
	}

	public function get_api_data() {
		return $this->api_data;
	}

	public function get_api_value( $value ) {
		return $this->api_fetch_succeeded() ? $this->api_data->$value : false;
	}

	public function api_fetch_succeeded() {
		return $this->api_fetch_succeeded;
	}

}
endif;
