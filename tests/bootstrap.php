<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

class WP_Error {
	private $code;
	private $message;

	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_message() {
		return $this->message;
	}
}

function bfa_test_reset_wordpress_state() {
	$GLOBALS['bfa_test_actions']          = array();
	$GLOBALS['bfa_test_added_filters']    = array();
	$GLOBALS['bfa_test_applied_filters']  = array();
	$GLOBALS['bfa_test_editor_styles']    = array();
	$GLOBALS['bfa_test_enqueued_scripts'] = array();
	$GLOBALS['bfa_test_enqueued_styles']  = array();
	$GLOBALS['bfa_test_filter_callbacks'] = array();
	$GLOBALS['bfa_test_http_calls']       = 0;
	$GLOBALS['bfa_test_http_response']    = new WP_Error( 'unexpected_http', 'Unexpected HTTP request.' );
	$GLOBALS['bfa_test_inline_styles']    = array();
	$GLOBALS['bfa_test_localized']        = array();
	$GLOBALS['bfa_test_registered_styles'] = array();
	$GLOBALS['bfa_test_removed_shortcodes'] = array();
	$GLOBALS['bfa_test_shortcodes']       = array();
	$GLOBALS['bfa_test_transients']       = array();
	$GLOBALS['bfa_test_transient_writes'] = array();
}

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['bfa_test_filter_callbacks'][ $tag ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
	return true;
}

function apply_filters( $tag, $value ) {
	$args = func_get_args();
	array_shift( $args );
	$GLOBALS['bfa_test_applied_filters'][] = array(
		'tag'        => $tag,
		'arg_count'  => count( $args ),
		'arguments'  => $args,
	);

	if ( empty( $GLOBALS['bfa_test_filter_callbacks'][ $tag ] ) ) {
		return $value;
	}

	$callbacks = $GLOBALS['bfa_test_filter_callbacks'][ $tag ];
	usort(
		$callbacks,
		function ( $left, $right ) {
			return $left['priority'] - $right['priority'];
		}
	);

	foreach ( $callbacks as $registered ) {
		$callback_args = array_slice( $args, 0, $registered['accepted_args'] );
		$args[0]       = call_user_func_array( $registered['callback'], $callback_args );
	}

	return $args[0];
}

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['bfa_test_actions'][] = array(
		'tag'           => $tag,
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
	return true;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, $args );
}

function get_template_directory() {
	return '/tmp/theme';
}

function get_stylesheet_directory() {
	return '/tmp/theme-child';
}

function get_template_directory_uri() {
	return 'https://example.test/theme';
}

function get_stylesheet_directory_uri() {
	return 'https://example.test/theme-child';
}

function plugin_dir_url( $file ) {
	return 'https://example.test/plugin/';
}

function plugin_dir_path( $file ) {
	return dirname( $file ) . '/';
}

function trailingslashit( $value ) {
	return rtrim( $value, '/\\' ) . '/';
}

function add_editor_style( $url ) {
	$GLOBALS['bfa_test_editor_styles'][] = $url;
}

function get_transient( $key ) {
	return array_key_exists( $key, $GLOBALS['bfa_test_transients'] )
		? $GLOBALS['bfa_test_transients'][ $key ]
		: false;
}

function set_transient( $key, $value, $expiration ) {
	$GLOBALS['bfa_test_transients'][ $key ] = $value;
	$GLOBALS['bfa_test_transient_writes'][] = compact( 'key', 'value', 'expiration' );
	return true;
}

function wp_json_encode( $value ) {
	return json_encode( $value );
}

function wp_remote_post( $url, $args ) {
	++$GLOBALS['bfa_test_http_calls'];
	$GLOBALS['bfa_test_last_http_request'] = compact( 'url', 'args' );
	return $GLOBALS['bfa_test_http_response'];
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function wp_remote_retrieve_response_code( $response ) {
	return is_array( $response ) && isset( $response['response']['code'] )
		? $response['response']['code']
		: '';
}

function wp_remote_retrieve_response_message( $response ) {
	return is_array( $response ) && isset( $response['response']['message'] )
		? $response['response']['message']
		: '';
}

function wp_remote_retrieve_body( $response ) {
	return is_array( $response ) && isset( $response['body'] ) ? $response['body'] : '';
}

function add_shortcode( $tag, $callback ) {
	$GLOBALS['bfa_test_shortcodes'][ $tag ] = $callback;
}

function remove_shortcode( $tag ) {
	$GLOBALS['bfa_test_removed_shortcodes'][] = $tag;
}

function shortcode_atts( $defaults, $atts ) {
	return array_merge( $defaults, $atts );
}

function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_html_e( $text, $domain = null ) {
	echo esc_html( $text );
}

function __( $text, $domain = null ) {
	return $text;
}

function _e( $text, $domain = null ) {
	echo $text;
}

function wp_register_style( $handle, $src, $dependencies = array(), $version = false ) {
	$GLOBALS['bfa_test_registered_styles'][ $handle ] = compact( 'src', 'dependencies', 'version' );
}

function wp_enqueue_style( $handle, $src = '', $dependencies = array(), $version = false ) {
	$GLOBALS['bfa_test_enqueued_styles'][ $handle ] = compact( 'src', 'dependencies', 'version' );
}

function wp_enqueue_script( $handle, $src = '', $dependencies = array(), $version = false ) {
	$GLOBALS['bfa_test_enqueued_scripts'][ $handle ] = compact( 'src', 'dependencies', 'version' );
}

function wp_add_inline_style( $handle, $css ) {
	$GLOBALS['bfa_test_inline_styles'][ $handle ] = $css;
}

function wp_localize_script( $handle, $name, $data ) {
	$GLOBALS['bfa_test_localized'][ $handle ] = compact( 'name', 'data' );
}

function wp_dequeue_style( $handle ) {
	$GLOBALS['bfa_test_dequeued_styles'][] = $handle;
}

bfa_test_reset_wordpress_state();
require_once dirname( __DIR__ ) . '/better-font-awesome-library.php';
