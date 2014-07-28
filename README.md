Better Font Awesome Library for WordPress
===========================

*The easiest way to integrate Font Awesome into your WordPress project.*

## Table of contents ##
1. [Introduction](https://github.com/MickeyKay/better-font-awesome-library#introduction)
1. [Features](https://github.com/MickeyKay/better-font-awesome-library#features)
1. [Usage](https://github.com/MickeyKay/better-font-awesome-library#usage)
1. [Initialization Parameters](https://github.com/MickeyKay/better-font-awesome-library#initialization-parameters)
1. [The Better Font Awesome Library Object](https://github.com/MickeyKay/better-font-awesome-library#the-better-font-awesome-library-object)
1. [To Do](https://github.com/MickeyKay/better-font-awesome-library#to-do)

## Introduction ##
The Better Font Awesome Library allows you to integrate any version of Font Awesome into your project (including the latest), along with accompanying CSS, a list of available icons, and a TinyMCE drop-down and shortcode generator.

## Features ##
* Integrates any version of Font Awesome, including the option to always use most recent version (no more manual updates!).
* Generates an easy-to-use [PHP object](#the-better-font-awesome-library-object) that contains all relevant info including: version, CSS URL, icons available in this version, prefix used (`icon` or `fa`).
* CDN speeds - Font Awesome CSS is pulled from [jsDelivr CDN](http://www.jsdelivr.com/#!fontawesome).
* Includes optional TinyMCE plugin with drop-down shortcode generator.
* Ability to choose between minified or unminified CSS.

## Usage ##
1. Copy the better-font-awesome-library folder into your project.

2. Add the following code to your main plugin file or your theme's function.php file.
   ```
	// Intialize Font Awesome once plugins are loaded
	add_action( 'plugins_loaded', 'my_slug_load_bfa' );
	function my_slug_load_bfa() {

		// Include the library - modify the require_once path to match your directory structure
		require_once ( dirname( __FILE__ ) . '/better-font-awesome-library/better-font-awesome-library.php' );

		// Settings to load Font Awesome (defaults shown)
		$args = array(
				'version' => 'latest',
				'minified' => true,
				'remove_existing_fa' => false,
				'load_styles'             => true,
				'load_admin_styles'       => true,
				'load_shortcode'          => true,
				'load_tinymce_plugin'     => true,
		);
		
		// Initialize Font Awesome, or get the existing instance
		Better_Font_Awesome_Library::get_instance( $args );
	}
```

3. If desired, use the [Better Font Awesome Library object](#the-better-font-awesome-library-object) to manually include Font Awesome CSS, output lists of available icons, create your own shortcodes, and much more.

## Initialization Parameters ($args) ##
The following parameters can be passed to `Better_Font_Awesome_Library::get_instance( $args )` in the `$args` array.

### version ###
(string) Which version of Font Awesome you want to use.
* `'latest'` (default) - always use the latest available version.
* `'3.2.1'` - any existing Font Awesome version number.

### minified ###
(boolean) Use minified Font Awesome CSS.
* `true` (default)
* `false` - uses unminified CSS.

### remove_existing_fa ###
(boolean) Attempts to remove existing Font Awesome styles and shortcodes. This can be useful to prevent conflicts with other themes/plugins, but is no guarantee..
* `false` (default)
* `true`

### load_styles ###
(boolean) Automatically loads Font Awesome CSS in the **front-end** of your site using `wp_enqueue_sripts()`.
* `true` (default)
* `false` - use this if you don't want to load the Font Awesome CSS in the front-end, or wish to do it yourself.

### load_admin_styles ###
(boolean) Automatically loads Font Awesome CSS in the **admin** of your site using `admin_enqueue_scripts()`.
* `true` (default)
* `false` - use this if you don't want to load the Font Awesome CSS in the admin, or wish to do it yourself.

### load_shortcode ###
(boolean) Loads the included `[icon]` shortcode.
* `false` (default)
* `true`

The shortcode looks like:
```
[icon name="" class="" unprefixed_class=""]
```

**name**  
Unprefixed icon name (e.g. star)

**class**  
Unprefixed [Font Awesome icon classes](http://fortawesome.github.io/Font-Awesome/examples/), to which the appropriate prefix will automatically be added (e.g. 2x spin)

**unprefixed_class**  
Classes that you wish to remain unprefixed (e.g. my-custom-class)

### load_tinymce_plugin ###
(boolean) Loads a TinyMCE drop-down list of available icons (based on `version`), which generates a `[icon]` shortcode.
* `false` (default)
* `true`

## The Better Font Awesome Library Object ##
The Better Font Awesome Library object can be accessed with the following code:  
`$my_bfa = Better_Font_Awesome_Library::get_instance();`

The object has the following properties:
### $stylesheet_url ###
(string) The URL of the jsDelivr CDN hosted stylesheet for the selected version of Font Awesome.

### $prefix ###
(string) The version-dependent prefix ('fa' or 'icon`) for use in CSS classes.

### $icons ###
(array) An alphabetical array of unprefixed icon names for all available icons in the selected version of Font Awesome.

### Example: ###
```
// Get the Better Font Awesome instance
$my_bfa = Better_Font_Awesome_Library::get_instance( $args );

// Get the URL for the Font Awesome stylesheet
$url = $my_bfa->stylesheet_url;

// Get the prefix for the version of Font Awesome you are using
$prefix = $my_bfa->prefix;

// Output a list of all available icons (unprefixed name, e.g. 'star')
$icons = $my_bfa->icons;
foreach ( $icons as $icon)
	echo $icon . '<br />';
```

## To Do ##
* Support for transients to prevent loading CDN info every time
* Clean up indenting
* Switch BFAL calls to use jsdelivr-fetcher class
* Remove & in class funtion calls
* Add version to public properties
* Switch from `admin_head_variables` to `wp_localize_script`
* Remove dash (-) from prefixes and add programatically when needed.
* Change `prefix` to `protocol` in get_icons
* De-activating on update (perhaps need to remove install functionality)
* Add fallbacks to wp_remote_get() - http://tommcfarlin.com/wp_remote_get/
* Add hard copy CSS file as final fallback.
* Add MIGHTYminnow to credit
* Icon menu icon not showing up in black studio widget - add attribute selector for admin CSS instead of exact ID selector.