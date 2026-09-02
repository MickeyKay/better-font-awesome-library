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

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/inc/class-bfa-release-data-validator.php';
require_once __DIR__ . '/inc/class-bfa-release-channel.php';
require_once __DIR__ . '/inc/class-bfa-release-data-v2-validator.php';
require_once __DIR__ . '/inc/class-bfa-release-data-v2-adapter.php';
require_once __DIR__ . '/inc/class-bfa-release-data-v2-refresher.php';

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
	 * Better Font Awesome Library version.
	 *
	 * @since  2.0.0
	 *
	 * @var    string
	 */
	const VERSION = '2.1.0';

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

	/** Font Awesome 7 fallback root path. */
	const FONT_AWESOME_7_FALLBACK_PATH = 'inc/font-awesome-7-fallback/';

	/** Exact-version cdnjs runtime base URL. */
	const FONT_AWESOME_7_CDN_BASE_URL = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome';

	/**
	 * Icon prefix.
	 *
	 * @since  2.0.0
	 *
	 * @var    string
	 */
	const ICON_PREFIX = 'fa';

	/**
	 * Release data transient expiration.
	 *
	 * Aka how often will we check for new Font Awesome release.
	 *
	 * @since  2.0.0
	 *
	 * @var    int
	 */
	const TRANSIENT_EXPIRATION = DAY_IN_SECONDS;

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
		'include_v4_shim'              => false,
		'remove_existing_fa'           => false,
		'load_styles'                  => true,
		'load_admin_styles'            => true,
		'load_shortcode'               => true,
		'load_tinymce_plugin'          => true,
		'release_data_provider'        => null,
		'release_data_refresh_callback' => null,
		'release_channel'              => '7.x',
	);

	/**
	 * Immutable release channel selected by the first caller.
	 *
	 * @var string
	 */
	private $release_channel = '';

	/**
	 * Whether the immutable release channel has been resolved.
	 *
	 * @var bool
	 */
	private $release_channel_resolved = false;

	/**
	 * Whether initialization received an unsupported filtered channel.
	 *
	 * @var bool
	 */
	private $release_channel_invalid = false;

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
		'timeout'             => 3,
		'sslverify'           => true,
		'limit_response_size' => Better_Font_Awesome_Release_Data_Validator::MAX_RESPONSE_BYTES,
		'redirection'         => 0,
		'reject_unsafe_urls'  => true,
		'blocking'            => true,
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
	 * Canonical and alias name map for the selected schema-2 record.
	 *
	 * @var array
	 */
	private $icon_name_map = array();

	/**
	 * Validated aliases keyed by schema-2 canonical name.
	 *
	 * @var array
	 */
	private $icon_alias_map = array();

	/**
	 * Validated internal release record.
	 *
	 * @var array
	 */
	private $release_record = array();

	/**
	 * Whether BFAL loaded the active record from its own packaged FA7 fallback.
	 *
	 * Record source is provenance supplied by the record and is not trusted for
	 * local asset routing.
	 *
	 * @var bool
	 */
	private $using_bundled_font_awesome_7_fallback = false;

	/**
	 * Whether a refresh request has been emitted during this request.
	 *
	 * @var bool
	 */
	private $refresh_requested = false;

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

		/*
		 * Keep BFAL's CDN styles in anonymous CORS mode on every normal load.
		 * This prevents a non-CORS response for the immutable URL from being
		 * cached before WordPress requests the same URL in an isolated editor.
		 */
		add_filter(
			'style_loader_tag',
			function ( $html, $handle ) {
				return $this->add_font_awesome_crossorigin_attribute( $html, $handle );
			},
			10,
			2
		);

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
		 * @param array $args BFAL initialization args.
		 */
		$this->args = apply_filters( 'bfa_init_args', $this->args );

		/*
		 * Resolve the selected channel exactly once. The established filter remains
		 * available, but later calls to load() and later singleton callers cannot
		 * mutate the first caller's selection.
		 */
		if ( ! $this->release_channel_resolved ) {
			$selected = array_key_exists( 'release_channel', $this->args ) ? $this->args['release_channel'] : null;
			$selected = apply_filters( 'bfa_font_awesome_release_channel', $selected );

			if ( Better_Font_Awesome_Release_Channel::is_supported( $selected ) ) {
				$this->release_channel = $selected;
			} else {
				$this->release_channel         = '';
				$this->release_channel_invalid = true;
				$this->set_error( 'channel', 'bfa_channel_unsupported', 'The selected Font Awesome release channel is not supported.' );
			}

			$this->release_channel_resolved = true;
		}

		$this->args['release_channel'] = $this->release_channel;

		/**
		 * Filter the wp_remote_get args.
		 *
		 * @since  1.0.0
		 *
		 * @param array $request_args BFAL HTTP request arguments.
		 */
		$this->wp_remote_get_args = apply_filters( 'bfa_wp_remote_get_args', $this->wp_remote_get_args );

		// Security and availability invariants cannot be disabled by filters.
		$this->wp_remote_get_args['sslverify']          = true;
		$this->wp_remote_get_args['redirection']        = 0;
		$this->wp_remote_get_args['reject_unsafe_urls'] = true;
		$this->wp_remote_get_args['blocking']           = true;

		$timeout = isset( $this->wp_remote_get_args['timeout'] ) ? (float) $this->wp_remote_get_args['timeout'] : 3;
		$this->wp_remote_get_args['timeout'] = max( 1, min( 5, $timeout ) );

		$response_size = isset( $this->wp_remote_get_args['limit_response_size'] ) ? (int) $this->wp_remote_get_args['limit_response_size'] : Better_Font_Awesome_Release_Data_Validator::MAX_RESPONSE_BYTES;
		$this->wp_remote_get_args['limit_response_size'] = max( 1, min( Better_Font_Awesome_Release_Data_Validator::MAX_RESPONSE_BYTES, $response_size ) );

	}

	/**
	 * Set up root URL for library, which differs for plugins vs. themes.
	 *
	 * @since  1.0.4
	 */
	function setup_root_url() {

		// Get BFA directory and theme root directory paths.
		$bfa_directory = dirname(__FILE__);
		$theme_directory = get_template_directory();
		$child_theme_directory = get_stylesheet_directory();
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

			// Use appropriate file paths for parent themes and child themes.
			if ( strpos( $bfa_directory, $theme_directory ) !== false ) {

				// Get relative BFA directory by removing theme root directory path.
				$bfa_rel_path = str_replace( $theme_directory, '', $bfa_directory );
				$this->root_url = trailingslashit( get_template_directory_uri() . $bfa_rel_path );

			} else {

				$bfa_rel_path = str_replace( $child_theme_directory, '', $bfa_directory );
				$this->root_url = trailingslashit( get_stylesheet_directory_uri() . $bfa_rel_path );

			}

		} else { // Otherwise we're inside a plugin.

			$this->root_url = trailingslashit( plugin_dir_url( __FILE__ ) );

		}

	}

	/**
	 * Get fallback (hard-coded) release data in case failing from the
	 * Font Awesome API fails.
	 *
	 * @since  2.0.0
	 *
	 * @return array Fallback release data.
	 */
	private function get_fallback_release_data() {
		if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ) {
			return $this->get_font_awesome_7_fallback_release_data();
		}

		$this->using_bundled_font_awesome_7_fallback = false;

		// Set fallback directory path.
		$bundled_release_data_path  = plugin_dir_path( __FILE__ ) . self::FALLBACK_RELEASE_DATA_PATH;
		$fallback_release_data_path = $bundled_release_data_path;

		/**
		 * Filter the fallback release data path.
		 *
		 * @since  2.0.0
		 *
		 * @param  string  $fallback_release_data_path  The path to the fallback Font Awesome directory.
		 */
		$fallback_release_data_path = apply_filters( 'bfa_fallback_release_data_path', $fallback_release_data_path );

		$fallback_json = $this->get_local_file_contents( $fallback_release_data_path );
		if ( $bundled_release_data_path === $fallback_release_data_path ) {
			$checksum_path = plugin_dir_path( __FILE__ ) . 'inc/fallback-release-data.sha256';
			$checksum      = $this->get_local_file_contents( $checksum_path );
			$expected_hash = substr( trim( $checksum ), 0, 64 );

			if ( ! preg_match( '/^[a-f0-9]{64}$/', $expected_hash ) || $expected_hash !== hash( 'sha256', $fallback_json ) ) {
				$this->set_error( 'fallback', 'bfa_fallback_checksum_invalid', 'The bundled Font Awesome metadata checksum is invalid.' );
				return $this->get_empty_release_data();
			}
		}

		$result        = Better_Font_Awesome_Release_Data_Validator::parse_fallback_json( $fallback_json );

		if ( ! $result['valid'] ) {
			$this->set_validation_error( 'fallback', $result );
			return $this->get_empty_release_data();
		}

		$this->release_record = $result['record'];
		return $result['record']['release'];
	}

	/**
	 * Load and validate the packaged Font Awesome 7 Free baseline.
	 *
	 * @return array Valid release data or an empty release shape.
	 */
	private function get_font_awesome_7_fallback_release_data() {
		$this->using_bundled_font_awesome_7_fallback = false;
		$path   = plugin_dir_path( __FILE__ ) . self::FONT_AWESOME_7_FALLBACK_PATH . 'metadata.json';
		$result = Better_Font_Awesome_Release_Data_V2_Validator::parse_record_json( $this->get_local_file_contents( $path ) );

		if ( ! $result['valid'] ) {
			$this->set_validation_error( 'fallback', $result );
			return $this->get_empty_release_data();
		}

		$this->release_record = $result['record'];
		$this->using_bundled_font_awesome_7_fallback = true;
		return $result['record']['release'];
	}

	/**
	 * Get locally available Font Awesome release data.
	 *
	 * Normal request getters never perform remote transport. They resolve data
	 * from an injected local provider, the existing transient, or the bundled
	 * fallback. A consumer can schedule asynchronous refresh work through the
	 * refresh callback or action.
	 *
	 * @since   2.0.0
	 *
	 * @return  array  Release data.
	 */
	private function get_font_awesome_release_data() {
		if ( $this->release_channel_invalid ) {
			return $this->get_empty_release_data();
		}

		// 1. Reuse validated instance data for this request.
		if ( ! empty( $this->release_data ) ) {
			return $this->release_data;
		}

		// 2. Prefer an explicitly injected, already-resolved local provider.
		$release_data = $this->get_provider_release_data();
		if ( ! empty( $release_data ) ) {
			$this->release_data = $release_data;
			return $release_data;
		}

		// 3. Preserve and validate the established transient value shape.
		$transient_value = get_transient( self::SLUG . '-release-data' );
		if ( false !== $transient_value ) {
			$result = $this->validate_selected_release_data( $transient_value, 'transient' );
			if ( $result['valid'] ) {
				$this->using_bundled_font_awesome_7_fallback = false;
				$this->release_record                           = $result['record'];
				$this->release_data                             = $result['record']['release'];
				return $this->release_data;
			}

			$this->set_validation_error( 'cache', $result );
		}

		// 4. Return validated bundled data immediately and request async refresh.
		$release_data       = $this->get_fallback_release_data();
		$this->release_data = $release_data;
		$this->request_release_data_refresh();

		return $release_data;
	}

	/**
	 * Resolve release data from an optional local provider.
	 *
	 * @return array Valid release data, or an empty array.
	 */
	private function get_provider_release_data() {
		$provider = isset( $this->args['release_data_provider'] ) ? $this->args['release_data_provider'] : null;
		if ( ! is_callable( $provider ) ) {
			return array();
		}

		$provided = call_user_func( $provider );
		if ( is_wp_error( $provided ) ) {
			$this->set_error( 'provider', 'bfa_provider_error', 'The release data provider could not supply Font Awesome metadata.' );
			return array();
		}

		$result = $this->validate_selected_release_data( $provided, 'provider' );
		if ( ! $result['valid'] ) {
			$this->set_validation_error( 'provider', $result );
			return array();
		}

		$this->using_bundled_font_awesome_7_fallback = false;
		$this->release_record                           = $result['record'];
		return $result['record']['release'];
	}

	/**
	 * Validate local data against only the immutable selected channel.
	 *
	 * @param mixed  $data   Release data or a declared release record.
	 * @param string $source Local source identifier.
	 * @return array Validation result.
	 */
	private function validate_selected_release_data( $data, $source ) {
		$is_record = is_array( $data ) && ( array_key_exists( 'schema_version', $data ) || array_key_exists( 'release', $data ) );

		if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ) {
			return $is_record
				? Better_Font_Awesome_Release_Data_V2_Validator::validate_record( $data )
				: Better_Font_Awesome_Release_Data_V2_Validator::validate_release( $data, $source );
		}

		return $is_record
			? Better_Font_Awesome_Release_Data_Validator::validate_record( $data )
			: Better_Font_Awesome_Release_Data_Validator::validate_release( $data, $source );
	}

	/**
	 * Return a warning-safe empty release data shape.
	 *
	 * @return array Empty release data.
	 */
	private function get_empty_release_data() {
		return array(
			'version'       => '',
			'icons'         => array(),
			'srisByLicense' => array(
				'free' => array(),
			),
		);
	}

	/**
	 * Check for an exact validated release asset identity.
	 *
	 * @param string $expected_path Expected relative asset path.
	 * @return bool Whether the release contains the asset.
	 */
	private function release_has_asset( $expected_path ) {
		foreach ( $this->get_release_assets() as $asset ) {
			if ( isset( $asset['path'] ) && $expected_path === $asset['path'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a validated release asset integrity value.
	 *
	 * @param string $expected_path Expected relative asset path.
	 * @return string Integrity value, or an empty string.
	 */
	private function get_release_asset_integrity( $expected_path ) {
		foreach ( $this->get_release_assets() as $asset ) {
			if ( isset( $asset['path'], $asset['value'] ) && $expected_path === $asset['path'] ) {
				return $asset['value'];
			}
		}

		return '';
	}

	/**
	 * Build a URL for an exact validated stylesheet path.
	 *
	 * Packaged fallback assets are used only for the bundled schema-2 record.
	 * Completely validated newer records use exact-version cdnjs URLs.
	 *
	 * @param string $path Relative stylesheet path.
	 * @return string Asset URL, or an empty string.
	 */
	private function get_release_asset_url( $path ) {
		$version = $this->get_version();
		if ( '' === $version || ! $this->release_has_asset( $path ) ) {
			return '';
		}

		if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 !== $this->release_channel ) {
			return sprintf( '%s/v%s/%s', self::FONT_AWESOME_CDN_BASE_URL, $version, $path );
		}

		if ( $this->using_bundled_font_awesome_7_fallback ) {
			return $this->root_url . self::FONT_AWESOME_7_FALLBACK_PATH . $path;
		}

		return sprintf( '%s/%s/%s', self::FONT_AWESOME_7_CDN_BASE_URL, $version, $path );
	}

	/**
	 * Request asynchronous release data refresh work from a consumer.
	 *
	 * The callback or action handler must schedule work and return promptly. BFAL
	 * does not run remote transport from this method.
	 *
	 * @since 2.1.0
	 */
	public function request_release_data_refresh() {
		if ( $this->release_channel_invalid || $this->refresh_requested ) {
			return;
		}

		$this->refresh_requested = true;
		$callback                = isset( $this->args['release_data_refresh_callback'] ) ? $this->args['release_data_refresh_callback'] : null;

		if ( is_callable( $callback ) ) {
			call_user_func( $callback, $this->release_channel, $this );
			return;
		}

		/**
		 * Request that a consumer schedule asynchronous metadata refresh work.
		 *
		 * @since 2.1.0
		 *
		 * @param string                       $channel Supported release channel.
		 * @param Better_Font_Awesome_Library $library BFAL instance.
		 */
		do_action( 'bfa_release_data_refresh_requested', $this->release_channel, $this );
	}

	/**
	 * Refresh Font Awesome release data in an explicit worker context.
	 *
	 * Consumers own scheduling, locking, retry backoff, and durable last-known-
	 * good persistence. This method performs one bounded refresh attempt and
	 * only replaces the established transient after complete validation.
	 *
	 * @since 2.1.0
	 *
	 * @return array|WP_Error Valid release data or a sanitized failure.
	 */
	public function refresh_release_data() {
		if ( $this->release_channel_invalid ) {
			return $this->get_error( 'channel' );
		}

		if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ) {
			return $this->refresh_font_awesome_7_release_data();
		}

		$query_args            = $this->wp_remote_get_args;
		$query_args['headers'] = array(
			'Content-Type' => 'application/json',
		);
		$query_args['body'] = wp_json_encode(
			array(
				'query' => '
				{
					release(version: "5.x") {
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
				',
			)
		);

		$response = wp_remote_post( self::FONT_AWESOME_API_BASE_URL, $query_args );
		if ( is_wp_error( $response ) ) {
			$this->set_error( 'api', 'bfa_transport_error', 'The Font Awesome metadata service could not be reached.' );
			return $this->get_error( 'api' );
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 300 ) {
			$this->set_error( 'api', 'bfa_http_error', 'The Font Awesome metadata service returned an unsuccessful HTTP status.' );
			return $this->get_error( 'api' );
		}

		$result = Better_Font_Awesome_Release_Data_Validator::parse_api_response( wp_remote_retrieve_body( $response ) );
		if ( ! $result['valid'] ) {
			$this->set_validation_error( 'api', $result );
			return $this->get_error( 'api' );
		}

		$release_data = $result['record']['release'];

		/**
		 * Filter release data transient expiration.
		 *
		 * @since  2.0.0
		 *
		 * @param int $expiration Expiration for release data.
		 */
		$transient_expiration = apply_filters( 'bfa_release_data_transient_expiration', $this->get_transient_expiration() );
		$stored               = set_transient( self::SLUG . '-release-data', $release_data, $transient_expiration );

		if ( false === $stored && $release_data !== get_transient( self::SLUG . '-release-data' ) ) {
			$this->set_error( 'api', 'bfa_cache_write_failed', 'Validated Font Awesome metadata could not be persisted.' );
			return $this->get_error( 'api' );
		}

		$this->using_bundled_font_awesome_7_fallback = false;
		$this->release_record                           = $result['record'];
		$this->release_data                             = $release_data;
		$this->formatted_icon_array                     = array();
		unset( $this->errors['api'] );

		return $release_data;
	}

	/**
	 * Refresh the selected Font Awesome 7 channel without BFAL persistence.
	 *
	 * The consumer owns durable last-known-good storage. A successful attempt
	 * returns a complete schema-2 record and updates only this request's instance.
	 *
	 * @return array|WP_Error Complete schema-2 record or sanitized failure.
	 */
	private function refresh_font_awesome_7_release_data() {
		$this->get_font_awesome_release_data();
		$result = Better_Font_Awesome_Release_Data_V2_Refresher::refresh( $this->wp_remote_get_args, $this->release_record );

		if ( is_wp_error( $result ) ) {
			$this->set_error( 'api', $result->get_error_code(), $result->get_error_message() );
			return $this->get_error( 'api' );
		}

		$validated = Better_Font_Awesome_Release_Data_V2_Validator::validate_record( $result );
		if ( ! $validated['valid'] ) {
			$this->set_validation_error( 'api', $validated );
			return $this->get_error( 'api' );
		}

		$this->using_bundled_font_awesome_7_fallback = false;
		$this->release_record                           = $validated['record'];
		$this->release_data                             = $validated['record']['release'];
		$this->formatted_icon_array                     = array();
		$this->icon_name_map                            = array();
		$this->icon_alias_map                           = array();
		unset( $this->errors['api'] );

		return $validated['record'];
	}

	/**
	 * Get array of icons for the current version.
	 *
	 * @since   1.0.0
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
				$search_terms = $icon_metadata['id'];
				if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ) {
					$aliases      = $this->get_font_awesome_7_icon_aliases( $icon_metadata['id'] );
					$search_terms = implode( ' ', array_merge( array( $icon_metadata['id'] ), $aliases ) );
				}

				$icons[] = [
					'title'       => "{$icon_metadata['label']} ({$icon_style})",
					'slug'        => $icon_metadata['id'],
					'style'       => $icon_style,
					'base_class'  => $this->get_icon_base_class( $icon_metadata['id'], $icon_style ),
					'searchTerms' => $search_terms,
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
	 * Get validated aliases for one schema-2 canonical icon.
	 *
	 * @param string $canonical_id Canonical icon ID.
	 * @return array Name aliases.
	 */
	private function get_font_awesome_7_icon_aliases( $canonical_id ) {
		$this->initialize_font_awesome_7_icon_name_maps();
		return isset( $this->icon_alias_map[ $canonical_id ] ) ? $this->icon_alias_map[ $canonical_id ] : array();
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

		if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ) {
			$resolved = $this->resolve_font_awesome_7_icon_name( $name );
			if ( null !== $resolved ) {
				$name = $resolved;
			}
		}

		return $name;
	}

	/**
	 * Resolve a schema-2 canonical icon name or validated alias.
	 *
	 * @param string $name Canonical name or alias.
	 * @return string|null Canonical name, or null when unknown.
	 */
	private function resolve_font_awesome_7_icon_name( $name ) {
		$this->initialize_font_awesome_7_icon_name_maps();

		return isset( $this->icon_name_map[ $name ] ) ? $this->icon_name_map[ $name ] : null;
	}

	/**
	 * Build canonical and alias lookup maps once per validated schema-2 record.
	 */
	private function initialize_font_awesome_7_icon_name_maps() {
		if ( ! empty( $this->icon_name_map ) ) {
			return;
		}

		foreach ( $this->get_font_awesome_release_data()['icons'] as $icon ) {
			$this->icon_name_map[ $icon['id'] ]  = $icon['id'];
			$this->icon_alias_map[ $icon['id'] ] = $icon['aliases']['names'];
		}

		foreach ( $this->get_font_awesome_release_data()['icons'] as $icon ) {
			foreach ( $icon['aliases']['names'] as $alias ) {
				$this->icon_name_map[ $alias ] = $icon['id'];
			}
		}
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

		$prefix = $this->get_prefix();
		$classes = [];

		/**
		 * Include for backwards compatibility with Font Awesome More Icons plugin.
		 *
		 * @see https://wordpress.org/plugins/font-awesome-more-icons/
		 */
		$title = $title ? 'title="' . esc_attr( $title ) . '" ' : '';
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
		 * @param  string  $name          Sanitized icon name.
		 */
		$class_string = apply_filters( 'bfa_icon_class', $class_string, $name );

		/**
		 * Filter the default <i> icon tag.
		 *
		 * @since  1.5.0
		 *
		 * @param string $tag Tag to use for output icons (default = 'i').
		 */
		$tag = apply_filters( 'bfa_icon_tag', 'i' );

		// Generate the HTML <i> icon element output.
		$output = sprintf( '<%s class="%s %s" %s>%s</%s>',
			$tag,
			esc_attr( $class_string ),
			esc_attr( $size ),
			// The esc_attr() call for $title happens earlier because we actually want to conditionally output the full title="" string only if there's a value to output.
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

		if ( version_compare( $this->get_version(), '5', '>=' ) ) {
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
	 * Add anonymous CORS mode to BFAL's enqueued Font Awesome stylesheets.
	 *
	 * @since  2.1.0
	 *
	 * @param  string  $html    The stylesheet link tag.
	 * @param  string  $handle  The stylesheet handle.
	 * @return string  The filtered stylesheet link tag.
	 */
	private function add_font_awesome_crossorigin_attribute( $html, $handle ) {
		$font_awesome_handles = array(
			self::SLUG . '-font-awesome'              => array( self::SLUG . '-font-awesome-css', 'css/all.min.css' ),
			self::SLUG . '-font-awesome-v5-compat'    => array( self::SLUG . '-font-awesome-v5-compat-css', 'css/v5-font-face.min.css' ),
			self::SLUG . '-font-awesome-v4-font-face' => array( self::SLUG . '-font-awesome-v4-font-face-css', 'css/v4-font-face.min.css' ),
			self::SLUG . '-font-awesome-v4-shim'      => array( self::SLUG . '-font-awesome-v4-shim-css', 'css/v4-shims.min.css' ),
		);

		if ( ! isset( $font_awesome_handles[ $handle ] ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $html;
		}

		$processor   = new WP_HTML_Tag_Processor( $html );
		$expected_id = $font_awesome_handles[ $handle ][0];
		while ( $processor->next_tag( array( 'tag_name' => 'LINK' ) ) ) {
			if ( $expected_id !== $processor->get_attribute( 'id' ) ) {
				continue;
			}

			if ( ! $processor->set_attribute( 'crossorigin', 'anonymous' ) ) {
				return $html;
			}

			if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ) {
				$integrity = $this->get_release_asset_integrity( $font_awesome_handles[ $handle ][1] );
				if ( '' === $integrity || ! $processor->set_attribute( 'integrity', $integrity ) ) {
					return $html;
				}
			}

			return $processor->get_updated_html();
		}

		return $html;
	}

	/**
	 * Register and enqueue Font Awesome CSS.
	 */
	public function register_font_awesome_css() {
		if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ) {
			$this->register_font_awesome_7_css();
			return;
		}

		$stylesheet_url = $this->get_stylesheet_url();
		if ( '' === $stylesheet_url ) {
			return;
		}

		wp_register_style(
			self::SLUG . '-font-awesome',
			$stylesheet_url,
			array(),
			self::VERSION
		);
		wp_enqueue_style( self::SLUG . '-font-awesome' );

		// Conditionally include the Font Awesome v4 CSS shim.
		if ( $this->args['include_v4_shim'] ) {
			$v4_shim_url = $this->get_stylesheet_url_v4_shim();
			if ( '' === $v4_shim_url ) {
				return;
			}

			wp_register_style(
				self::SLUG . '-font-awesome-v4-shim',
				$v4_shim_url,
				array(),
				self::VERSION
			);
			wp_enqueue_style( self::SLUG . '-font-awesome-v4-shim' );

			// Enqueue inline shim CSS as well.
			$this->register_v4_shim_inline_css();

		}
	}

	/**
	 * Register the minimal Font Awesome 7 runtime and compatibility styles.
	 */
	private function register_font_awesome_7_css() {
		$styles = array(
			self::SLUG . '-font-awesome'           => array( $this->get_stylesheet_url(), array() ),
			self::SLUG . '-font-awesome-v5-compat' => array( $this->get_release_asset_url( 'css/v5-font-face.min.css' ), array( self::SLUG . '-font-awesome' ) ),
		);

		if ( $this->args['include_v4_shim'] ) {
			$styles[ self::SLUG . '-font-awesome-v4-font-face' ] = array(
				$this->get_release_asset_url( 'css/v4-font-face.min.css' ),
				array( self::SLUG . '-font-awesome' ),
			);
			$styles[ self::SLUG . '-font-awesome-v4-shim' ] = array(
				$this->get_stylesheet_url_v4_shim(),
				array( self::SLUG . '-font-awesome', self::SLUG . '-font-awesome-v4-font-face' ),
			);
		}

		foreach ( $styles as $handle => $style ) {
			if ( '' === $style[0] ) {
				return;
			}

			wp_register_style( $handle, $style[0], $style[1], self::VERSION );
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Enqueue inline v4 shim CSS to alias legacy @font-face declarations.
	 *
	 * @since  2.0.1
	 */
	public function register_v4_shim_inline_css () {
		if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ) {
			return;
		}

		$v4_shim_font_face = "
			@font-face {
				font-family: 'FontAwesome';
				src: url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-brands-400.eot'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-brands-400.eot?#iefix') format('embedded-opentype'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-brands-400.woff2') format('woff2'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-brands-400.woff') format('woff'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-brands-400.ttf') format('truetype'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-brands-400.svg#fontawesome') format('svg');
			}

			@font-face {
				font-family: 'FontAwesome';
				src: url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-solid-900.eot'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-solid-900.eot?#iefix') format('embedded-opentype'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-solid-900.woff2') format('woff2'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-solid-900.woff') format('woff'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-solid-900.ttf') format('truetype'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-solid-900.svg#fontawesome') format('svg');
			}

			@font-face {
				font-family: 'FontAwesome';
				src: url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-regular-400.eot'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-regular-400.eot?#iefix') format('embedded-opentype'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-regular-400.woff2') format('woff2'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-regular-400.woff') format('woff'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-regular-400.ttf') format('truetype'),
				url('https://use.fontawesome.com/releases/v{$this->get_version()}/webfonts/fa-regular-400.svg#fontawesome') format('svg');
				unicode-range: U+F004-F005,U+F007,U+F017,U+F022,U+F024,U+F02E,U+F03E,U+F044,U+F057-F059,U+F06E,U+F070,U+F075,U+F07B-F07C,U+F080,U+F086,U+F089,U+F094,U+F09D,U+F0A0,U+F0A4-F0A7,U+F0C5,U+F0C7-F0C8,U+F0E0,U+F0EB,U+F0F3,U+F0F8,U+F0FE,U+F111,U+F118-F11A,U+F11C,U+F133,U+F144,U+F146,U+F14A,U+F14D-F14E,U+F150-F152,U+F15B-F15C,U+F164-F165,U+F185-F186,U+F191-F192,U+F1AD,U+F1C1-F1C9,U+F1CD,U+F1D8,U+F1E3,U+F1EA,U+F1F6,U+F1F9,U+F20A,U+F247-F249,U+F24D,U+F254-F25B,U+F25D,U+F267,U+F271-F274,U+F279,U+F28B,U+F28D,U+F2B5-F2B6,U+F2B9,U+F2BB,U+F2BD,U+F2C1-F2C2,U+F2D0,U+F2D2,U+F2DC,U+F2ED,U+F328,U+F358-F35B,U+F3A5,U+F3D1,U+F410,U+F4AD;
			}
		";

		wp_add_inline_style(
			self::SLUG . '-font-awesome-v4-shim',
			$v4_shim_font_face
		);
	}

	/**
	 * Add Font Awesome CSS to TinyMCE.
	 *
	 * @since  1.0.0
	 */
	public function add_editor_styles() {
		$stylesheet_url = $this->get_stylesheet_url();
		if ( '' !== $stylesheet_url ) {
			add_editor_style( $stylesheet_url );
		}

		if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ) {
			$v5_compat_url = $this->get_release_asset_url( 'css/v5-font-face.min.css' );
			if ( '' !== $v5_compat_url ) {
				add_editor_style( $v5_compat_url );
			}

			if ( $this->args['include_v4_shim'] ) {
				$v4_font_face_url = $this->get_release_asset_url( 'css/v4-font-face.min.css' );
				if ( '' !== $v4_font_face_url ) {
					add_editor_style( $v4_font_face_url );
				}
			}
		}

		// Conditionally include the Font Awesome v4 CSS shim.
		if ( $this->args['include_v4_shim'] ) {
			$v4_shim_url = $this->get_stylesheet_url_v4_shim();
			if ( '' !== $v4_shim_url ) {
				add_editor_style( $v4_shim_url );
			}
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
		wp_enqueue_style(
			self::SLUG . '-admin',
			$this->root_url . 'css/admin-styles.css',
			array(),
			self::VERSION
		);

		// Custom admin JS.
		wp_enqueue_script(
			self::SLUG . '-admin',
			$this->root_url . 'js/admin.js',
			array(),
			self::VERSION
		);

		// Icon picker JS and CSS.
		wp_enqueue_style(
			'fontawesome-iconpicker',
			$this->root_url . $this->icon_picker_directory . 'css/fontawesome-iconpicker' . $suffix . '.css',
			array(),
			self::VERSION
		);
		wp_enqueue_script(
			'fontawesome-iconpicker',
			$this->root_url . $this->icon_picker_directory . 'js/fontawesome-iconpicker' . $suffix . '.js',
			array(),
			self::VERSION
		);

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
		<div class="bfa-iconpicker" data-selected="fa-flag">
			<button type="button" class="button iconpicker-component">
				<span class="fa icon fa-flag icon-flag"></span>&nbsp;
				<?php esc_html_e( 'Insert Icon', 'better-font-awesome' ); ?>
				<i class="change-icon-placeholder"></i>
			</button>
		</div>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static local markup is escaped when constructed above.
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
		<div class="notice notice-error is-dismissible">
			<p>
				<b><?php echo esc_html( __( 'Better Font Awesome', 'better-font-awesome' ) ); ?></b>
			</p>

			<p><?php echo esc_html( __( 'Font Awesome metadata could not be refreshed or validated:', 'better-font-awesome' ) ); ?></p>
			<?php foreach ( $this->errors as $error ) : ?>
				<?php
				if ( ! is_wp_error( $error ) ) {
					continue;
				}
				?>
				<p>
					<code><?php echo esc_html( $error->get_error_code() . ': ' . $error->get_error_message() ); ?></code>
				</p>
			<?php endforeach; ?>

			<!-- Fallback Text -->
			<p>
				<?php
					echo esc_html( __( 'Better Font Awesome will use its validated local metadata when available: ', 'better-font-awesome' ) ) . '<code>' . esc_html( $this->get_version() ) . '</code>. ';
					/* translators: 1: opening support link, 2: closing support link. */
					echo wp_kses_post(
						sprintf(
							__( 'This may be the result of a temporary server or connectivity issue which will resolve shortly. However if the problem persists please file a support ticket on the %1$splugin forum%2$s, citing the errors listed above. ', 'better-font-awesome' ),
							'<a href="https://wordpress.org/support/plugin/better-font-awesome" target="_blank" rel="noopener noreferrer" title="Better Font Awesome support forum">',
							'</a>'
						)
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
		if ( ! is_string( $file_path ) || ! is_readable( $file_path ) ) {
			return '';
		}

		$contents = file_get_contents( $file_path );
		return false === $contents ? '' : $contents;

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
		$code = preg_replace( '/[^a-z0-9_-]/i', '_', (string) $code );
		if ( '' === $code ) {
			$code = 'bfa_unknown_error';
		}

		$message = strip_tags( (string) $message );
		$message = preg_replace( '/[\x00-\x1F\x7F]/', ' ', $message );
		$message = trim( preg_replace( '/\s+/', ' ', $message ) );
		$message = substr( $message, 0, 200 );

		$this->errors[ $error_type ] = new WP_Error( $code, $message );
	}

	/**
	 * Store a deterministic validator failure.
	 *
	 * @param string $error_type Error category.
	 * @param array  $result     Validator result.
	 */
	private function set_validation_error( $error_type, $result ) {
		$error   = isset( $result['error'] ) && is_array( $result['error'] ) ? $result['error'] : array();
		$code    = isset( $error['code'] ) ? $error['code'] : 'bfa_validation_error';
		$message = isset( $error['message'] ) ? $error['message'] : 'Font Awesome metadata validation failed.';
		$this->set_error( $error_type, $code, $message );
	}

	/**
	 * Retrieve a library error.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $process  Slug of the process to check (e.g. 'api').
	 *
	 * @return WP_Error|string The error for the specified process, or an empty string.
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
		$release_data = $this->get_font_awesome_release_data();
		return isset( $release_data['version'] ) && is_string( $release_data['version'] ) ? $release_data['version'] : '';
	}

	/**
	 * Get the main font awesome stylesheet URL.
	 *
	 * @since   2.0.0
	 *
	 * @return  string  Stylesheet URL.
	 */
	public function get_stylesheet_url() {
		$path = Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ? 'css/all.min.css' : 'css/all.css';
		return $this->get_release_asset_url( $path );
	}

	/**
	 * Get the v4 shim stylesheet URL.
	 *
	 * @since   1.0.0
	 *
	 * @return  string  Stylesheet URL.
	 */
	public function get_stylesheet_url_v4_shim() {
		$path = Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 === $this->release_channel ? 'css/v4-shims.min.css' : 'css/v4-shims.css';
		return $this->get_release_asset_url( $path );
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
		$release_data = $this->get_font_awesome_release_data();
		$icons        = isset( $release_data['icons'] ) && is_array( $release_data['icons'] ) ? $release_data['icons'] : array();
		if ( Better_Font_Awesome_Release_Channel::FONT_AWESOME_7 !== $this->release_channel ) {
			return $icons;
		}

		$adapted = array();
		foreach ( $icons as $icon ) {
			$styles = array();
			foreach ( $icon['familyStylesByLicense']['free'] as $membership ) {
				$styles[] = $membership['style'];
			}

			$adapted[] = array(
				'id'         => $icon['id'],
				'label'      => $icon['label'],
				'membership' => array( 'free' => $styles ),
				'styles'     => $styles,
			);
		}

		return $adapted;
	}

	/**
	 * Get Font Awesome release assets.
	 *
	 * @since   2.0.0
	 *
	 * @return  array  Release assets.
	 */
	public function get_release_assets() {
		$release_data = $this->get_font_awesome_release_data();
		return isset( $release_data['srisByLicense']['free'] ) && is_array( $release_data['srisByLicense']['free'] ) ? $release_data['srisByLicense']['free'] : array();
	}

	/**
	 * Get the validated internal release record.
	 *
	 * @since 2.1.0
	 *
	 * @return array Release record.
	 */
	public function get_release_record() {
		$this->get_font_awesome_release_data();
		return $this->release_record;
	}

	/**
	 * Get the supported Font Awesome release channel.
	 *
	 * @since 2.1.0
	 *
	 * @return string Release channel.
	 */
	public function get_release_channel() {
		return $this->release_channel;
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
	 * Get release data transient duration.
	 *
	 * @since   2.0.0
	 *
	 * @return  int  Release data transient expiration.
	 */
	public function get_transient_expiration() {
		return self::TRANSIENT_EXPIRATION;
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
