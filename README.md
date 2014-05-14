Better Font Awesome Library
===========================

*The easiest way to integrate Font Awesome into your theme or plugin.*

## Features ##
* Integrate any version of Font Awesome, including the option to always use most recent version (no more manual updates!).
* Generates easy-to-use PHP object that contains all relevant info including: version, CSS URL, icons available in this version, prefix used (`icon` or `fa`).
* CDN speeds - Font Awesome CSS is pulled from [Bootstrap CDN](http://www.bootstrapcdn.com/#fontawesome_tab).
* Includes optional TinyMCE plugin with drop-down shortcode generator.
* Ability to choose between minified or unminified CSS.

## Usage ##
1. Copy the better-font-awesome-libary folder into your project.

2. Add the following code to your main plugin file or your theme's function.php file.
   ```
	// Intialize Font Awesome once plugins are loaded
	add_action( 'plugins_loaded', 'my_slug_load_bfa' );
	function my_slug_load_bfa() {
		// Settings to load Font Awesome
		$args = array(
				'version' => 'latest',
				'minified' => true,
				'remove_existing_fa' => false,
				'load_styles' => true,
				'load_admin_styles' => true,
				'load_tinymce_plugin' => false,
		);
		
		// Initialize Font Awesome, or get the existing instance
		Better_Font_Awesome_Object::get_instance( $args );
	}
	
	// Get the Better Font Awesome instance anywhere in your code
	$my_bfa = Better_Font_Awesome_Object::get_instance();
```

## Parameters ##
**version**
Which version of Font Awesome you want to use. The default setting is `latest`.
* `'latest'` (default) - always use the latest available version


## Coming Soon. . . ##
* Support for transients to prevent loading CDN info every time
