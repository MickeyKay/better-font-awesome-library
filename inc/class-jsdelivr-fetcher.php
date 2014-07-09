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

if ( ! class_exists( 'jsDeliver_Fetcher' ) ):
class jsDeliver_Fetcher {

	/**
	 * Constants
	 */
	const CDN_URL = 'http://api.jsdelivr.com/v1/jsdelivr/libraries/fontawesome/?fields=versions,lastversion';

	/**
	 * Properties
	 */
	private $cdn_data;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	function __construct() {
		$this->get_cdn_data();
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

	private function get_cdn_data() {
		$this->cdn_data = $this->fetch_cdn_data( self::CDN_URL );
	}

	private function fetch_cdn_data( $url ) {
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$response = $response->get_error_message();
			add_action( 'admin_notices', array( $this, 'wp_remote_get_error_notice' ) );
		} else {
			$response = json_decode( wp_remote_retrieve_body( $response ) )[0];
		}

		return $response;
	}

	function get_value( $value ) {
		return $this->cdn_data->$value;
	}

	function wp_remote_get_error_notice() {
		?>
	    <div class="updated error">
	        <p>
	        	<?php echo __( 'wp_remote_get() failed with the following error: ', 'bfa' ) . "<code>$this->cdn_data</code>"; ?>
	        </p>
	    </div>
	    <?php
	}
}
endif;
