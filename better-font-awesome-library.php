<?php
/**
 * Better Font Awesome Library
 *
 * A class to implement Font Awesome in WordPress.
 *
 * @see      jsDelivr CDN and API
 * @link     http://www.jsdelivr.com/
 * @link     https://github.com/jsdelivr/api
 *
 * @since    1.0.0
 *
 * @package  Better Font Awesome Library
 */

/**
 * @todo test in both pre and post TinyMCE V4 (make sure icons all appear in
 *       editor and front end)
 * @todo There may be a better way to do get_local_file_contents(), refer to:
 *       https://github.com/markjaquith/feedback/issues/33
 * @todo Icon menu icon not showing up in black studio widget - add attribute
 *       selector for admin CSS instead of exact ID selector. Not sure if this
 *       is still an issue?
 */

/**
 * 2.0.0 changes
 *
 * - [x] Switch to only using 1. FA@latest, or 2. 4@latest vs 5@latest (need to pin at 4 for any reason?)
 * - [x] Switch to using FA GraphQL API for #allthethings
 * 		- [x] Version data
 * 		- [x] Icons list
 * - [ ] Include v4 shim css if needed, add admin option
 * - [x] Display current version in the admin.
 * - [ ] Remove inc/icon-updater logic if possible
 * - [ ] Corroborate what shim actually does
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Better_Font_Awesome_Library' ) ) :
class Better_Font_Awesome_Library {

	/**
	 * Better Font Awesome Library slug.
	 *
	 * @since  1.0.0
	 *
	 * @var    string
	 */
	const SLUG = 'bfa';

	/**
	 * Font awesome GraphQL url.
	 *
	 * @since  2.0.0
	 *
	 * @var    string
	 */
	const FONT_AWESOME_API_BASE_URL = 'https://api.fontawesome.com';

	/**
	 * Font awesome CDN url.
	 *
	 * @since  2.0.0
	 *
	 * @var    string
	 */
	const FONT_AWESOME_CDN_BASE_URL = 'https://use.fontawesome.com/releases';

	/**
	 * Fallback release data path.
	 *
	 * @since  2.0.0
	 *
	 * @var    string
	 */
	const FALLBACK_RELEASE_DATA_PATH = 'inc/fallback-release-data.json';

	/**
	 * Icon prefix.
	 *
	 * @since  2.0.0
	 *
	 * @var    string
	 */
	const ICON_PREFIX = 'fa';

	/**
	 * Initialization args.
	 *
	 * @since  1.0.0
	 *
	 * @var    array
	 */
	private $args;

	/**
	 * Default args to use if any $arg isn't specified.
	 *
	 * @since  1.0.0
	 *
	 * @var    array
	 */
	private $default_args = array(
		'version'             => 'latest',
		'minified'            => true,
		'include_v4_shim'     => false,
		'remove_existing_fa'  => false,
		'load_styles'         => true,
		'load_admin_styles'   => true,
		'load_shortcode'      => true,
		'load_tinymce_plugin' => true,
	);

	/**
	 * Root URL of the library.
	 *
	 * @since  1.0.4
	 *
	 * @var    string
	 */
	private $root_url;

	/**
	 * Args for wp_remote_get() calls.
	 *
	 * @since  1.0.0
	 *
	 * @var    array
	 */
	private $wp_remote_get_args = array(
		'timeout'   => 10,
		'sslverify' => false,
	);

	/**
	 * Icon picker library dir.
	 *
	 * @var  string
	 */
	private $icon_picker_directory = 'lib/fontawesome-iconpicker/dist/';

	/**
	 * Instance-level variable to store Font Awesome release data to
	 * avoid refetches for a single page load.
	 *
	 * @var array
	 */
	private $release_data = array();

	/**
	 * Instance-level variable to store formatted icon array to avoid
	 * extra data transformations each time we want this data.
	 *
	 * @var array
	 */
	private $formatted_icon_array = array();

	/**
	 * Array to track errors and wp_remote_get() failures.
	 *
	 * @since  1.0.0
	 *
	 * @var    array
	 */
	private $errors = array();

	/**
	 * Instance of this class.
	 *
	 * @since  1.0.0
	 *
	 * @var    Better_Font_Awesome_Library
	 */
	private static $instance = null;

	/**
	 * Returns the instance of this class, and initializes
	 * the instance if it doesn't already exist.
	 *
	 * @since   1.0.0
	 *
	 * @return  Better_Font_Awesome_Library  The BFAL object.
	 */
	public static function get_instance( $args = array() ) {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self( $args );
		}

		return self::$instance;

	}

	/**
	 * Better Font Awesome Library constructor.
	 *
	 * @since  1.0.0
	 *
	 * @param  array  $args  Initialization arguments.
	 */
	private function __construct( $args = array() ) {

		// Get initialization args.
		$this->args = $args;

		// Load the library functionality.
		$this->load();

	}

	/**
	 * Set up all plugin library functionality.
	 *
	 * @since  1.0.0
	 */
	public function load() {

		// Initialize library properties and actions as needed.
		$this->initialize( $this->args );

		// Add Font Awesome and/or custom CSS to the editor.
		$this->add_editor_styles();

		// Output any necessary admin notices.
		add_action( 'admin_notices', array( $this, 'do_admin_notice' ) );

		/**
		 * Remove existing Font Awesome CSS and shortcodes if needed.
		 *
		 * Use priority 15 to ensure this is done after other plugin
		 * CSS/shortcodes are loaded. This must run before any other
		 * style/script/shortcode actions so it doesn't accidentally
		 * remove them.
		 */
		if ( $this->args['remove_existing_fa'] ) {

			add_action( 'wp_enqueue_scripts', array( $this, 'remove_font_awesome_css' ), 15 );
			add_action( 'init', array( $this, 'remove_icon_shortcode' ), 20 );

		}

		/**
		 * Load front-end scripts and styles.
		 *
		 * Use priority 15 to make sure styles/scripts load after other plugins.
		 */
		if ( $this->args['load_styles'] || $this->args['remove_existing_fa'] ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'register_font_awesome_css' ), 15 );
		}

		/**
		 * Load admin scripts and styles.
		 *
		 * Use priority 15 to make sure styles/scripts load after other plugins.
		 */
		if ( $this->args['load_admin_styles'] || $this->args['load_tinymce_plugin'] ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_font_awesome_css' ), 15 );
		}

		/**
		 * Add [icon] shortcode.
		 *
		 * Use priority 15 to ensure this is done after removing existing Font
		 * Awesome CSS and shortcodes.
		 */
		if ( $this->args['load_shortcode'] || $this->args['load_tinymce_plugin'] ) {
			add_action( 'init', array( $this, 'add_icon_shortcode' ), 20 );
		}

		// Load TinyMCE functionality.
		if ( $this->args['load_tinymce_plugin'] ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			// Add shortcode insertion button.
			add_action( 'media_buttons', array( $this, 'add_insert_shortcode_button' ), 99 );

		}

	}

	/**
	 * Do necessary initialization actions.
	 *
	 * @since  1.0.0
	 */
	private function initialize( $args ) {

		// Parse the initialization args with the defaults.
		$this->parse_args( $args );

		// Setup root URL, which differs for plugins vs. themes.
		$this->setup_root_url();
	}

	/**
	 * Parse the initialization args with the defaults and apply bfa_args filter.
	 *
	 * @since  1.0.0
	 *
	 * @param  array  $args  Args used to initialize BFAL.
	 */
	private function parse_args( $args = array() ) {

		// Parse initialization args with defaults.
		$this->args = wp_parse_args( $args, $this->default_args );

		/**
		 * Filter the initialization args.
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $this->args  BFAL initialization args.
		 */
		$this->args = apply_filters( 'bfa_init_args', $this->args );

		/**
		 * Filter the wp_remote_get args.
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $this->wp_remote_get_args  BFAL wp_remote_get_args args.
		 */
		$this->wp_remote_get_args = apply_filters( 'bfa_wp_remote_get_args', $this->wp_remote_get_args );

	}

	/**
	 * Set up root URL for library, which differs for plugins vs. themes.
	 *
	 * @since  1.0.4
	 */
	function setup_root_url() {

		// Get BFA directory and theme root directory paths.
		$bfa_directory = dirname(__FILE__);
		$theme_directory = get_stylesheet_directory();
		$plugin_dir = plugin_dir_url( __FILE__ );

		/**
		 * Check if we're inside a theme or plugin.
		 *
		 * If we're in a theme, than plugin_dir_url() will return a
		 * funky URL that includes the actual file path (e.g.
		 * /srv/www/site_name/wp-content/...)
		 */
		$is_theme = false;
		if ( strpos( $plugin_dir, $bfa_directory ) !== false ) {
			$is_theme = true;
		}

		// First check if we're inside a theme.
		if ( $is_theme ) {

			// Get relative BFA directory by removing theme root directory path.
			$bfa_rel_path = str_replace( $theme_directory, '', $bfa_directory );
			$this->root_url = trailingslashit( get_stylesheet_directory_uri() . $bfa_rel_path );

		} else { // Otherwise we're inside a plugin.
			$this->root_url = trailingslashit( plugin_dir_url( __FILE__ ) );
		}
	}

	/**
	 * Get fallback (hard-coded) release data in case failing from the
	 * Font Awesome API fails.
	 *
	 * @return array Fallback release data.
	 */
	private function get_fallback_release_data() {
		// Set fallback directory path.
		$fallback_release_data_path = plugin_dir_path( __FILE__ ) . SELF::FALLBACK_RELEASE_DATA_PATH;

		/**
		 * Filter the fallback release data path.
		 *
		 * @todo  add to docs
		 * @since  2.0.0
		 *
		 * @param  string  $fallback_release_data_path  The path to the fallback Font Awesome directory.
		 */
		$fallback_release_data_path = apply_filters( 'bfa_fallback_release_data_path', $fallback_release_data_path );

		return json_decode( $this->get_local_file_contents( $fallback_release_data_path ), true )['data']['release'];
	}

	/**
	 * Get Font Awesome release data from the Font Awesome GraphQL API.
	 *
	 * First check to see if the transient is current. If not, fetch the data.
	 *
	 * @since   2.0.0
	 *
	 * @return  array  Release data.
	 */
	private function get_font_awesome_release_data() {
		// 1. If we've already retrieved/set the instance-level data, use that for performance.
		if ( ! empty( $this->release_data ) ) {
			return $this->release_data;
		}

		$transient_slug = self::SLUG . '-release-data';
		$transient_value = $response = get_transient( $transient_slug );
		$release_data = array();

		// 2. Short-circuit return the transient value if set.
		// @todo this probably shouldn't be a false check :thinking:
		if ( false !== $transient_value ) {
			$release_data = $transient_value ;
		}

		// 3. Otherwise fetch the release data from the GraphQL API.
		else {
			$query_args = array_merge(
				$this->wp_remote_get_args,
				[
					'headers' => [
						'Content-Type' => 'application/json',
					],
					'body' => wp_json_encode([
						'query' => '
						{
							release(version: "latest") {
								version,
								icons {
									id,
									label,
									membership {
										free
									},
									styles
								}
								srisByLicense {
									free {
										path
										value
									}
								}
							}
						}
						'
					])
				]
			);

			$response = wp_remote_post( self::FONT_AWESOME_API_BASE_URL, $query_args );

			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			// Check for non-200 response.
			if ( 200 !== $response_code ) {
				$this->set_error( 'api', wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) . " - " . self::FONT_AWESOME_API_BASE_URL );
			}

			// Check for API errors - GraphQL returns a 200 even with errors.
			elseif ( ! empty( $response_body['errors'] ) ) {
				$this->set_error( 'api', 'GraphQL Error', print_r( $response_body['errors'], true ) );
			}

			// Check for faulty wp_remote_post()
			elseif ( is_wp_error( $response ) ) {
				$this->set_error( 'api', $response->get_error_code(), $response->get_error_message() . " - " . self::FONT_AWESOME_API_BASE_URL );
			}

			// Successful!
			else {
				$release_data = $response_body['data']['release'];

				/**
				 * Filter release data transient expiration.
				 *
				 * @todo  Renamed old filter, which was incorrectly named. Call out in readme.
				 * @since  2.0.0
				 *
				 * @param  int  Expiration for release data.
				 */
				$transient_expiration = apply_filters( 'bfa_release_data_transient_expiration', WEEK_IN_SECONDS );

				// Set the API transient.
				set_transient( $transient_slug, $release_data, $transient_expiration );
			}
		}

		// If we've made it this far, it means we:
		// 1. don't have a valid transient value
		// 2. don't have a valid fetched value
		// . . . and we should therefore return the fallback data.
		if ( empty( $release_data ) ) {
			$release_data = $this->get_fallback_release_data();
		}

		// Store an instance level release data for performance
		// (avoid hitting db each time), and return.
		$this->release_data = $release_data;
		return $release_data;
	}

	/**
	 * Get the transient copy of the CSS for the specified version.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $version  Font Awesome version to check.
	 *
	 * @return  string  		  Transient CSS if it exists, otherwise null.
	 */
	private function get_transient_css( $version ) {

		$transient_css_array = get_transient( self::SLUG . '-css' );
		return isset( $transient_css_array[ $version ] ) ? $transient_css_array[ $version ] : '';

	}

	/**
	 * Get the CSS from the remote jsDelivr CDN.
	 *
	 * @since   1.0.0
	 *
	 * @param   string           $url       URL for the remote stylesheet.
	 * @param   string           $version   Font Awesome version to fetch.
	 *
	 * @return  string|WP_ERROR  $response  Remote CSS, or WP_ERROR if
	 *                                      wp_remote_get() fails.
	 */
	private function get_remote_css( $url, $version ) {

		// Get the remote Font Awesome CSS.
		$url = set_url_scheme( $url );
		$response = wp_remote_get( $url, $this->wp_remote_get_args );

		/**
		 * If fetch was successful, return the remote CSS, and set the CSS
		 * transient for this version. Otherwise, return a WP_Error object.
		 */
		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {

			$response = wp_remote_retrieve_body( $response );
			$this->set_css_transient( $version, $response );

		} elseif ( is_wp_error( $response ) ) { // Check for faulty wp_remote_get()
			$response = $response;
		} elseif ( isset( $response['response'] ) ) { // Check for 404 and other non-WP_ERROR codes
			$response = new WP_Error( $response['response']['code'], $response['response']['message'] . " (URL: $url)" );
		} else { // Total failsafe
			$response = '';
		}

		return $response;

	}

	/**
	 * Set the CSS transient array.
	 *
	 * @since  1.0.0
	 *
	 * @param  string  $version  Version of Font Awesome for which to set the
	 *                           transient.
	 * @param  string  $value    CSS for corresponding version of Font Awesome.
	 */
	private function set_css_transient( $version, $value ) {

		/**
		 * Get the transient array, which contains data for multiple Font
		 * Awesome version.
		 */
		$transient_css_array = get_transient( self::SLUG . '-css' );

		// Set the new value for the specified Font Awesome version.
		$transient_css_array[ $version ] = $value;

		/**
		 * Filter the CSS transient expiration.
		 *
		 * @since  1.0.0
		 *
		 * @param  int  Expiration for the CSS transient.
		 */
		$transient_expiration = apply_filters( 'bfa_css_transient_expiration', 30 * DAY_IN_SECONDS );

		// Set the new CSS array transient.
		set_transient( self::SLUG . '-css', $transient_css_array, $transient_expiration );

	}

	/**
	 * Get array of icons for the current version.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  CSS for the current version of FA (only used pre-v5)
	 *
	 * @return  array   All available icon names (e.g. adjust, car, pencil).
	 */
	private function get_formatted_icon_array() {

		// If we have the instance-level var populated, use it.
		if ( ! empty( $this->formatted_icon_array ) ) {
			return $this->formatted_icon_array;
		}

		$icons_metadata = $this->get_release_icons();
		$icons = [];

		foreach ( $icons_metadata as $icon_metadata ) {

			$icon_styles = $icon_metadata['membership']['free'];

			// Only include if this icon supports FREE styles.
			// @see https://fontawesome.com/how-to-use/graphql-api/objects/membership
			if ( empty( $icon_styles ) ) {
				continue;
			}

			foreach ( $icon_styles as $icon_style ) {
				$icons[] = [
					'title'       => "{$icon_metadata['label']} ({$icon_style})",
					'slug'        => $icon_metadata['id'],
					'style'       => $icon_style,
					'base_class'  => $this->get_icon_base_class( $icon_metadata['id'], $icon_style ),
					// @todo this is not included in the GraphQL API :(
					'searchTerms' => $icon_metadata['id'],
				];
			}
		}

		/**
		 * [DEPRECATED] Filter the array of available icons.
		 *
		 * @since   1.0.0
		 *
		 * @param   array  $icons  Array of all available icons.
		 */
		$icons = apply_filters( 'bfa_icon_list', $icons );

		/**
		 * Filter the array of available icons.
		 *
		 * @since   2.0.0
		 *
		 * @param   array  $icons  Array of all available icons.
		 */
		$icons = apply_filters( 'bfa_icon_array', $icons );

		// Set instance-level variable to avoid recalculating this function each time.
		$this->formatted_icon_array = $icons;

		return $icons;
	}

	/**
	 * Remove styles that include 'fontawesome' or 'font-awesome' in their slug.
	 *
	 * @since  1.0.0
	 */
	public function remove_font_awesome_css() {

		global $wp_styles;

		// Loop through all registered styles and remove any that appear to be Font Awesome.
		foreach ( $wp_styles->registered as $script => $details ) {

			if ( false !== strpos( $script, 'fontawesome' ) || false !== strpos( $script, 'font-awesome' ) ) {
				wp_dequeue_style( $script );
			}

		}

	}

	/**
	 * Remove [icon] shortcode.
	 *
	 * @since  1.0.0
	 */
	public function remove_icon_shortcode() {
		remove_shortcode( 'icon' );
	}

	/**
	 * Add [icon] shortcode.
	 *
	 * Usage:
	 * [icon name="flag" class="fw 2x spin" unprefixed_class="custom_class"]
	 *
	 * @since  1.0.0
	 */
	public function add_icon_shortcode() {
		add_shortcode( 'icon', array( $this, 'render_shortcode' ) );
	}

	public function sanitize_shortcode_name_att( $name ) {
		/**
		 * Strip 'icon-' and 'fa-' from the BEGINNING of $name.
		 *
		 * This corrects for:
		 * 	1. Incorrect shortcodes (when user includes full class name including prefix)
		 *  2. Old shortcodes from other plugins that required prefixes
		 */
		$prefixes = array( 'icon-', 'fa-' );
		foreach ( $prefixes as $prefix ) {

			if ( substr( $name, 0, strlen( $prefix ) ) == $prefix ) {
				$name = substr( $name, strlen( $prefix ) );
			}

		}

		return $name;
	}

	public function sanitize_shortcode_class_att( $class ) {
		$prefix = $this->get_prefix();

		// Remove "icon-" and "fa-" from the icon class.
		$class = str_replace( 'icon-', '', $class );
		$class = str_replace( 'fa-', '', $class );

		// Remove extra spaces from the icon class.
		$class = trim( $class );
		$class = preg_replace( '/\s{3,}/', ' ', $class );

		// Add the version-specific prefix back on to each class.
		$class_array = array_filter( explode( ' ', $class ) );

		foreach ( $class_array as $index => $class ) {
			$class_array[ $index ] = $prefix ? $prefix . '-' . $class : $class;
		}

		return implode( ' ', $class_array );
	}

	/**
	 * Render [icon] shortcode.
	 *
	 * Usage:
	 * [icon name="flag" class="fw 2x spin" unprefixed_class="custom_class"]
	 *
	 * @param   array   $atts    Shortcode attributes.
	 * @return  string  $output  Icon HTML (e.g. <i class="fa fa-car"></i>).
	 */
	public function render_shortcode( $atts ) {

		extract( shortcode_atts( array(
			'name'             => '',
			'class'            => '',
			'unprefixed_class' => '',
			'title'            => '', /* For compatibility with other plugins */
			'size'             => '', /* For compatibility with other plugins */
			'space'            => '',
			'style'            => '', /* Style category */
		), $atts ));

		// @todo remove and verify this logic isn't needed with v4 shim CSS included
		// $icon = $this->get_icon_by_slug( $name );

		// // Maybe this is an old icon that needs an updated alias.
		// if ( ! $icon ) {
		// 	require __DIR__ . '/inc/icon-updater.php';
		// 	$name = bfa_get_updated_icon_slug( $name );

		// 	if ( ! $name ) {
		// 		return '';
		// 	}
		// }

		$prefix = $this->get_prefix();
		$classes = [];

		/**
		 * Include for backwards compatibility with Font Awesome More Icons plugin.
		 *
		 * @see https://wordpress.org/plugins/font-awesome-more-icons/
		 */
		$title = $title ? 'title="' . $title . '" ' : '';
		$space = 'true' == $space ? '&nbsp;' : '';
		$size = $size ? ' '. $prefix . '-' . $size : '';

		// Santize name.
		$name = $this->sanitize_shortcode_name_att( $name );

		// Generate classes array.
		$classes[] = $this->get_icon_base_class( $name, $style );
		$classes[] = $this->sanitize_shortcode_class_att( $class );
		$classes[] = $unprefixed_class;

		$class_string = implode( ' ', array_filter( $classes ) );

		/**
		 * Filter the icon class.
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $class_string  Classes attached to the icon.
		 */
		$class_string = apply_filters( 'bfa_icon_class', $class_string, $name );

		/**
		 * Filter the default <i> icon tag.
		 *
		 * @since  1.5.0
		 *
		 * @param  string  Tag to use for output icons (default = 'i').
		 */
		$tag = apply_filters( 'bfa_icon_tag', 'i' );

		// Generate the HTML <i> icon element output.
		$output = sprintf( '<%s class="%s %s" %s>%s</%s>',
			$tag,
			$class_string,
			$size,
			$title,
			$space,
			$tag
		);

		/**
		 * Filter the icon output.
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $output  Icon output.
		 */
		return apply_filters( 'bfa_icon', $output );

	}

	public function get_icon_base_class( $slug, $style = '' ) {
		return "{$this->get_icon_style_class( $style )} {$this->get_prefix()}-{$slug}";
	}

	private function get_icon_style_class( $style = '' ) {

		if ( version_compare( $this->get_version(), 5, '>=' ) ) {
			switch ( $style ) {
				case 'brands':
				return 'fab';

				case 'light':
				return 'fal';

				case 'regular':
				return 'far';

				case 'solid':
				return 'fas';

				default:
				return 'fa';
			}
		} else {
			return $this->get_prefix();
		}
	}

	/**
	 * Register and enqueue Font Awesome CSS.
	 */
	public function register_font_awesome_css() {

		wp_register_style( self::SLUG . '-font-awesome', $this->get_stylesheet_url() );
		wp_enqueue_style( self::SLUG . '-font-awesome' );

		// Conditionally include the Font Awesome v4 CSS shim.
		if ( $this->args['include_v4_shim'] ) {

			wp_register_style( self::SLUG . '-font-awesome-v4-shim', $this->get_stylesheet_url_v4_shim() );
			wp_enqueue_style( self::SLUG . '-font-awesome-v4-shim' );

		}
	}

	/**
	 * Add Font Awesome CSS to TinyMCE.
	 *
	 * @since  1.0.0
	 */
	public function add_editor_styles() {
		add_editor_style( $this->get_stylesheet_url() );

		// Conditionally include the Font Awesome v4 CSS shim.
		if ( $this->args['include_v4_shim'] ) {
			add_editor_style( $this->get_stylesheet_url_v4_shim() );
		}
	}

	/**
	 * Load admin CSS.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_admin_scripts() {

		// Check whether to get minified or non-minified files.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Custom admin CSS.
		wp_enqueue_style( self::SLUG . '-admin', $this->root_url . 'css/admin-styles.css' );

		// Custom admin JS.
		wp_enqueue_script( self::SLUG . '-admin', $this->root_url . 'js/admin.js' );

		// Icon picker JS and CSS.
		wp_enqueue_style( 'fontawesome-iconpicker', $this->root_url . $this->icon_picker_directory . 'css/fontawesome-iconpicker' . $suffix . '.css' );
		wp_enqueue_script( 'fontawesome-iconpicker', $this->root_url . $this->icon_picker_directory . 'js/fontawesome-iconpicker' . $suffix . '.js' );

		// Output PHP variables to JS.
		$bfa_vars = array(
			'fa_prefix'   => $this->get_prefix(),
			'fa_icons'    => $this->get_icons(),
		);
		wp_localize_script( self::SLUG . '-admin', 'bfa_vars', $bfa_vars );

	}

	/**
	 * Add a button to insert icon shortcode.
	 *
	 * @since  1.3.0
	 */
	public function add_insert_shortcode_button() {

		ob_start();
		?>
		<span class="bfa-iconpicker fontawesome-iconpicker" data-selected="fa-flag">
			<a href="#" class="button button-secondary iconpicker-component">
				<span class="fa icon fa-flag icon-flag"></span>&nbsp;
				<?php esc_html_e( 'Insert Icon', 'better-font-awesome' ); ?>
				<i class="change-icon-placeholder"></i>
			</a>
		</span>
		<?php
		echo ob_get_clean();

	}

	/**
	 * Generate admin notices.
	 *
	 * @since  1.0.0
	 */
	public function do_admin_notice() {

		if ( ! empty( $this->errors ) && apply_filters( 'bfa_show_errors', true ) ) :
			?>
		<div class="error">
			<p>
				<b><?php _e( 'Better Font Awesome', 'better-font-awesome' ); ?></b>
			</p>

			<!-- API Error -->
			<?php if ( is_wp_error ( $this->get_error('api') ) ) : ?>
				<p>
					<?php
						_e( 'It looks like something went wrong when trying to fetch data from the Font Awesome API:', 'better-font-awesome' );
					?>
				</p>
				<p>
					<code><?php echo $this->get_error('api')->get_error_code() . ': ' . $this->get_error('api')->get_error_message(); ?></code>
				</p>
			<?php endif; ?>

			<!-- Fallback Text -->
			<p>
				<?php
					echo __( 'Don\'t worry! Better Font Awesome will still render using the included fallback version:</b> ', 'better-font-awesome' ) . '<code>' . $this->get_version() . '</code>. ' ;
					printf( __( 'This may be the result of a temporary server or connectivity issue which will resolve shortly. However if the problem persists please file a support ticket on the %splugin forum%s, citing the errors listed above. ', 'better-font-awesome' ),
						'<a href="http://wordpress.org/support/plugin/better-font-awesome" target="_blank" title="Better Font Awesome support forum">',
						'</a>'
					);
				?>
			</p>
		</div>
		<?php
		endif;
	}

	/*----------------------------------------------------------------------------*
	 * Helper Functions
	 *----------------------------------------------------------------------------*/

	/**
	 * Get the contents of a local file.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $file_path  Path to local file.
	 *
	 * @return  string  $contents   Content of local file.
	 */
	private function get_local_file_contents( $file_path ) {

		ob_start();
		include $file_path;
		$contents = ob_get_clean();

		return $contents;

	}

	/**
	 * Determine whether or not to use the .min suffix on Font Awesome
	 * stylesheet URLs.
	 *
	 * @since   1.0.0
	 *
	 * @return  string  '.min' if minification is specified, empty string if not.
	 */
	private function get_min_suffix() {
		return ( $this->args['minified'] ) ? '.min' : '';
	}

	/**
	 * Add an error to the $this->errors array.
	 *
	 * @since  1.0.0
	 *
	 * @param  string  $error_type  Type of error (api, css, etc).
	 * @param  string  $code        Error code.
	 * @param  string  $message     Error message.
	 */
	private function set_error( $error_type, $code, $message ) {
		$this->errors[ $error_type ] = new WP_Error( $code, $message );
	}

	/**
	 * Retrieve a library error.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $process  Slug of the process to check (e.g. 'api').
	 *
	 * @return  WP_ERROR          The error for the specified process.
	 */
	public function get_error( $process ) {
		return isset( $this->errors[ $process ] ) ? $this->errors[ $process ] : '';
	}

	/*----------------------------------------------------------------------------*
	 * Public User Functions
	 *----------------------------------------------------------------------------*/

	/**
	 * Get Font Awesome release version.
	 *
	 * @since   2.0.0
	 *
	 * @return  string  Release version.
	 */
	public function get_version() {
		return $this->get_font_awesome_release_data()['version'];
	}

	// @todo Remove the functions below that aren't used.

	/**
	 * Get the main font awesome stylesheet URL.
	 *
	 * @since   2.0.0
	 *
	 * @return  string  Stylesheet URL.
	 */
	public function get_stylesheet_url() {
		$release_assets = $this->get_release_assets();
		$release_css_path = '';

		foreach ( $release_assets as $release_asset ) {
			$release_asset_path = $release_asset['path'];

			if ( strpos( $release_asset_path, 'all' ) !== false && strpos( $release_asset_path, '.css' ) !== false ) {
				$release_css_path = $release_asset_path;
				break;
			}
		}

		return sprintf(
			'%s/v%s/%s',
			self::FONT_AWESOME_CDN_BASE_URL,
			$this->get_version(),
			$release_css_path
		);
	}

	/**
	 * Get the v4 shim stylesheet URL.
	 *
	 * @since   1.0.0
	 *
	 * @return  string  Stylesheet URL.
	 */
	public function get_stylesheet_url_v4_shim() {
		$release_assets = $this->get_release_assets();
		$release_css_path = '';

		foreach ( $release_assets as $release_asset ) {
			$release_asset_path = $release_asset['path'];

			if ( strpos( $release_asset_path, 'shim' ) !== false && strpos( $release_asset_path, '.css' ) !== false ) {
				$release_css_path = $release_asset_path;
				break;
			}
		}

		return sprintf(
			'%s/v%s/%s',
			self::FONT_AWESOME_CDN_BASE_URL,
			$this->get_version(),
			$release_css_path
		);
	}

	/**
	 * Get the array of available icons, with their/data shape
	 * modified from the original GraphQL API response to better match
	 * our consumers.
	 *
	 * @since   1.0.0
	 *
	 * @return  array  Available Font Awesome icons.
	 */
	public function get_icons() {
		return $this->get_formatted_icon_array();
	}

	/**
	 * Get the array of available icon data in the original shape
	 * provided by the GraphQL API.
	 *
	 * @since   2.0.0
	 *
	 * @return  array  Release icons.
	 */
	public function get_release_icons() {
		return $this->get_font_awesome_release_data()['icons'];
	}

	/**
	 * Get Font Awesome release assets.
	 *
	 * @since   2.0.0
	 *
	 * @return  array  Release assets.
	 */
	public function get_release_assets() {
		return $this->get_font_awesome_release_data()['srisByLicense']['free'];
	}

	/**
	 * Get the icon prefix ('fa' or 'icon').
	 *
	 * @since   1.0.0
	 *
	 * @return  string  Font Awesome prefix.
	 */
	public function get_prefix() {
		return self::ICON_PREFIX;
	}

	/**
	 * Get errors.
	 *
	 * @since   1.0.0
	 *
	 * @return  array  All library errors that have occured.
	 */
	public function get_errors() {
		return $this->errors;
	}
}
endif;
