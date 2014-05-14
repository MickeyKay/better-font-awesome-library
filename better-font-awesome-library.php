<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Better Font Awesome class
 */
class Better_Font_Awesome_Object {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'Better Font Awesome';
	const slug = 'better-font-awesome';


	/*--------------------------------------------*
	 * Variables
	 *--------------------------------------------*/
	public $stylesheet_url, $prefix, $icons;
	public $args; /* For variable settings in BFA library */
	protected $cdn_data, $titan;
	protected $default_args = array(
		'version' => 'latest',
		'minified' => true,
		'remove_existing_fa' => false,
		'load_styles' => true,
		'load_admin_styles' => true,
		'load_tinymce_plugin' => false,
	);

	/**
     * Returns the instance of this class, and initializes 
     * the instance if it doesn't already exist
     *
     * @return Better_Font_Awesome_Object The BFA object
     */
	public static function get_instance( $args = '' ) {
				
        static $instance = null;
        if (null === $instance) {
            $instance = new static( $args );
        }

        return $instance;
    }

	/**
	 * Constructor
	 */
	protected function __construct( $args = '' ) {
				
		// Initialize with specific args if passed
		$this->args = wp_parse_args( $args, $this->default_args );

		// Register an activation hook for the plugin
		register_activation_hook( __FILE__, array( &$this, 'install' ) );

		// Get CDN data
		$this->setup_cdn_data();

		// Hook up to the init action - on 11 to make sure it loads after other FA plugins
		add_action( 'after_setup_theme', array( &$this, 'init_early' ), 1 );

		// Late init functions - make sure this happens after other FA plugins are loaded
		add_action( 'init', array( &$this, 'init_late' ), 11 );

		// Do scripts and styles - on 11 to make sure styles/scripts load after other plugins
		if ( $this->args['load_styles'] )
			add_action( 'wp_enqueue_scripts', array( &$this, 'register_scripts_and_styles' ), 11 );
		if ( $this->args['load_admin_styles'] || $this->args['load_tinymce_plugin'] )
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_scripts_and_styles' ), 11 );

		// Load TinyMCE plugin
		if ( $this->args['load_tinymce_plugin'] ) {
			add_action( 'admin_head', array( &$this, 'admin_init' ) );
			add_action( 'admin_head', array( &$this, 'admin_head_variables' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'custom_admin_css' ), 11 );
		}
	}

	/**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
  
	/**
	 * Runs when the plugin is activated
	 */  
	function install() {
		// do not generate any output here
	}

	/**
	 * Runs when the plugin is initialized
	 */
	function init_early() {
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Set Font Awesome variables (stylesheet url, prefix, etc)
		$this->setup_global_variables();

		// Add Font Awesome stylesheet to TinyMCE
		add_editor_style( $this->stylesheet_url );
	}

	/**
	 * Runs after other plugins are initialized
	 */
	function init_late() {
		// Remove existing [icon] shortcodes added via other plugins/themes
		if ( $this->args['remove_existing_fa'] ) {
			remove_shortcode('icon');
		}

		// Register the shortcode [icon]
		add_shortcode( 'icon', array( &$this, 'render_shortcode' ) );
	}

	/**
	 * Get CDN data and prefix based on selected version
	 */
	function setup_cdn_data() {
		$remote_data = wp_remote_get( 'http://api.jsdelivr.com/v1/bootstrap/libraries/font-awesome/' );
		$decoded_data = json_decode( wp_remote_retrieve_body( $remote_data ) );
		$this->cdn_data = $decoded_data[0];
	}

	/*
	 * Set the Font Awesome stylesheet url to use based on the settings
	 */
	function setup_global_variables() {
		// Get latest version if need be
		if ( 'latest' == $this->args['version'] )
			$this->args['version'] = $this->cdn_data->lastversion;

		// Set stylesheet URL
		$stylesheet = $this->args['minified'] ? '/css/font-awesome.min.css' : '/css/font-awesome.css';
		$this->stylesheet_url = '//netdna.bootstrapcdn.com/font-awesome/' . $this->args['version'] . $stylesheet;

		// Set proper prefix based on version
		if ( 0 <= version_compare( $this->args['version'], '4' ) )
			$this->prefix = 'fa-';
		elseif ( 0 <= version_compare( $this->args['version'], '3' ) )
			$this->prefix = 'icon-';

		// Setup icons for selected version of Font Awesome
		$this->get_icons();
	}

	/*
     * Create list of available icons based on selected version of Font Awesome
     */
    function get_icons() {
    	// Get Font Awesome CSS
	    if( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" )
		    $prefix = 'https:';
		else
		    $prefix = 'http:';

		$remote_data = wp_remote_get( $prefix . $this->stylesheet_url );
	    $css = wp_remote_retrieve_body( $remote_data );
	    		
	 
	 	// Get all CSS selectors that have a content: pseudo-element rule
	 	preg_match_all('/(\.[^}]*)\s*{\s*(content:)/s', $css, $matches );
	    $selectors = $matches[1];

	    // Select all icon- and fa- selectors from and split where there are commas
	    foreach ( $selectors as $selector ) {
	    	preg_match_all('/\.(icon-|fa-)([^,]*)\s*:before/s', $selector, $matches );
	    	$clean_selectors = $matches[2];

	    	// Create array of selectors
	   		foreach( $clean_selectors as $clean_selector )
	   			$this->icons[] = $clean_selector;
	    }

	    // Alphabetize & join with comma for use in JS array
		sort( $this->icons );
				
    }

	/**
	 * Output [icon] shortcode
	 *
	 * Example:
	 * 	[icon name="flag" class="fw 2x spin"]
	 *
	 * @param   array $atts Shortcode attributes
	 * @return  string <i> Font Awesome output
	 */
	function render_shortcode( $atts ) {
		extract(shortcode_atts(array(
			'name' => '',
			'class' => '',
			'title'     => '', /* For compatibility with other plugins */
            'size'      => '', /* For compatibility with other plugins */
            'space'     => '',
			), $atts)
		);

		// Include for backwards compatibility with Font Awesome More Icons plugin
		$title = $title ? 'title="' . $title . '" ' : '';
		$space = 'true' == $space ? '&nbsp;' : '';
        $size = $size ? ' '. $this->prefix . $size : '';

		// Remove "icon-" and "fa-" from name
		// This helps both:
		// 	1. Incorrect shortcodes (when user includes full class name including prefix)
		// 	2. Old shortcodes from other plugins that required prefixes
		$name = str_replace( 'icon-', '', $name );
		$name = str_replace( 'fa-', '', $name );
		
		// Add prefix to name
		$icon_name = $this->prefix . $name;

		// Remove "icon-" and "fa-" from classes
		$class = str_replace( 'icon-', '', $class );
		$class = str_replace( 'fa-', '', $class );
		
		// Remove extra spaces from class
		$class = trim( $class );
		$class = preg_replace('/\s{3,}/',' ', $class );

		// Add prefix to each class (separated by space)
		$class = ' ' . $this->prefix . str_replace( ' ', ' ' . $this->prefix, $class );

		return '<i class="fa ' . $icon_name . $class . $size . '" ' . $title . '>' . $space . '</i>';
	}
  
	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	function register_scripts_and_styles() {
		// Deregister any existing Font Awesome CSS (including Titan Framework)
		if ( $this->args['remove_existing_fa'] ) {
			wp_dequeue_style( 'tf-font-awesome' );
			wp_dequeue_style( 'font-awesome' );
		}

		// Enqueue Font Awesome CSS
		wp_register_style( 'font-awesome', $this->stylesheet_url, '', $this->args['version'] );
		wp_enqueue_style( 'font-awesome' );
	}

	/*
	 * Runs when admin is initialized
	 */
	function admin_init() {
        if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
            return;     

        if ( get_user_option('rich_editing') == 'true' ) {  
            add_filter( 'mce_external_plugins', array( &$this, 'register_tinymce_plugin' ) );
	        add_filter( 'mce_buttons', array( &$this, 'add_tinymce_buttons' ) );
        }  
    }  

	/**
	 * Load TinyMCE Font Awesome dropdown plugin
	*/
	function register_tinymce_plugin( $plugin_array ) {
        global $tinymce_version;

        // >= TinyMCE v4 - include newer plugin
        if ( version_compare( $tinymce_version, '4000', '>=' ) )
        	$plugin_array['bfa_plugin'] = plugins_url( 'inc/js/tinymce-icons.js', __FILE__ );
        // < TinyMCE v4 - include old plugin
        else
        	$plugin_array['bfa_plugin'] = plugins_url( 'inc/js/tinymce-icons-old.js', __FILE__ );

        return $plugin_array;
    }

    /*
     * Add TinyMCE dropdown element
     */
    function add_tinymce_buttons( $buttons ) {
        array_push( $buttons, 'bfaSelect' );

        return $buttons;
    }

    /**
	 * Add PHP variables in head for use by TinyMCE JavaScript
	 */
	function admin_head_variables() {
		$icon_list = implode( ",", $this->icons );
	    ?>
		<!-- Better Font Awesome PHP variables for use by TinyMCE JavaScript -->
		<script type='text/javascript'>
		var bfa_vars = {
		    'fa_prefix': '<?php echo $this->prefix; ?>', 
		    'fa_icons': '<?php echo $icon_list; ?>',
		};
		</script>
		<!-- TinyMCE Better Font Awesome Plugin -->
	    <?php
	}

	/*
	 * Load admin CSS
	 */
	function custom_admin_css() {
		wp_enqueue_style( 'bfa-admin-styles', plugins_url( 'inc/css/admin-styles.css', __FILE__ ) );
	}
  
}
