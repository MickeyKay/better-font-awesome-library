Better Font Awesome Library
===========================

*The easiest way to integrate Font Awesome into your WordPress project.*

## Table of contents ##
1. [Introduction](https://github.com/MickeyKay/better-font-awesome-library#introduction)
1. [Features](https://github.com/MickeyKay/better-font-awesome-library#features)
1. [Usage](https://github.com/MickeyKay/better-font-awesome-library#usage)
1. [Initialization Parameters](https://github.com/MickeyKay/better-font-awesome-library#initialization-parameters-args)
1. [The Better Font Awesome Library Object](https://github.com/MickeyKay/better-font-awesome-library#the-better-font-awesome-library-object)
1. [Shortcode](https://github.com/MickeyKay/better-font-awesome-library#shortcode)
1. [Filters](https://github.com/MickeyKay/better-font-awesome-library#filters)
1. [To Do](https://github.com/MickeyKay/better-font-awesome-library#to-do)

## Introduction ##
The Better Font Awesome Library allows you to automatically integrate the latest available version of [Font Awesome](http://fontawesome.io/) into your WordPress project, along with accompanying CSS, shortcode, and TinyMCE icon shortcode generator. Furthermore, it generates all the data you need to create new functionality of your own.

## Features ##
* Automatically fetches the most recent available version of Font Awesome, meaning you no longer need to manually update the version included in your theme/plugin.
* Generates an easy-to-use [PHP object](#the-better-font-awesome-library-object) that contains all relevant info for the version of Font Awesome you're using, including: version, stylesheet URL, array of available icons, and prefix used (`icon` or `fa`).
* CDN speeds - Font Awesome CSS is pulled from the super-fast and reliable [jsDelivr CDN](http://www.jsdelivr.com/#!fontawesome).
* Includes a TinyMCE drop-down shortcode generator.
* Includes a local copy of Font Awesome to use as a fallback in case the remote fetch fails (or you can specify your own with the [`bfa_fallback_directory_path`](https://github.com/MickeyKay/better-font-awesome-library#bfa_fallback_directory_path) filter).
* Utilizes transients to optimize for speed and performance.

## Usage ##
1. Copy the /better-font-awesome-library folder into your project.

2. Add the following code to your main plugin file or your theme's functions.php file.
   ```
	add_action( 'plugins_loaded', 'my_prefix_load_bfa' );
	/**	
	 * Initialize the Better Font Awesome Library.
	 */
	function my_prefix_load_bfa() {

		// Include the main library file. Make sure to modify the path to match your directory structure.
		require_once ( dirname( __FILE__ ) . '/better-font-awesome-library/better-font-awesome-library.php' );

		// Set the library initialization args (defaults shown).
		$args = array(
				'version' => 'latest',
				'minified' => true,
				'remove_existing_fa' => false,
				'load_styles'             => true,
				'load_admin_styles'       => true,
				'load_shortcode'          => true,
				'load_tinymce_plugin'     => true,
		);
		
		// Initialize the Better Font Awesome Library.
		Better_Font_Awesome_Library::get_instance( $args );
	}
```

3. If desired, use the [Better Font Awesome Library object](#the-better-font-awesome-library-object) to manually include Font Awesome CSS, output lists of available icons, create your own shortcodes, and much more.

#### Usage Notes ####
The Better Font Awesome Library is designed to work in conjunction with the [Better Font Awesome](https://wordpress.org/plugins/better-font-awesome/) WordPress plugin. The plugin initializes this library (with its initialization args) on the `plugins_loaded` hook, priority `5`. When using the Better Font Awesome Library in your project, you have two options:

1. Initialize later, to ensure that any Better Font Awesome plugin settings override yours.
1. Initialize earlier, to "take over" and prevent Better Font Awesome settings from having an effect.

## Initialization Parameters ($args) ##
The following arguments can be used to initialize the library using `Better_Font_Awesome_Library::get_instance( $args )`:

#### $args['version'] ####
(string) Which version of Font Awesome you want to use.
* `'latest'` (default) - always use the latest available version.
* `'3.2.1'` - any existing Font Awesome version number.

#### $args['minified'] ####
(boolean) Use minified Font Awesome CSS.
* `true` (default) - uses minifed CSS.
* `false` - uses unminified CSS.

#### $args['remove_existing_fa'] ####
(boolean) Attempts to remove existing Font Awesome styles and shortcodes. This can be useful to prevent conflicts with other themes/plugins, but is no guarantee.
* `true`
* `false` (default)

#### $args['load_styles'] ####
(boolean) Automatically loads Font Awesome CSS on the **front-end** of your site using `wp_enqueue_scripts()`.
* `true` (default)
* `false` - use this if you don't want to load the Font Awesome CSS on the front-end, or wish to do it yourself.

#### $args['load_admin_styles'] ####
(boolean) Automatically loads Font Awesome CSS on the **admin** of your site using `admin_enqueue_scripts()`.
* `true` (default)
* `false` - use this if you don't want to load the Font Awesome CSS in the admin, or wish to do it yourself.

#### $args['load_shortcode'] ####
(boolean) Loads the included `[icon]` [shortcode](https://github.com/MickeyKay/better-font-awesome-library#shortcode).
* `true` (default)
* `false`

#### load_tinymce_plugin ####
(boolean) Loads a TinyMCE drop-down list of available icons (based on the active Font Awesome version), which generates an `[icon]` shortcode.
* `true` (default)
* `false`

## The Better Font Awesome Library Object ##
The Better Font Awesome Library object can be accessed with the following code:  
`Better_Font_Awesome_Library::get_instance();`

The object has the following public methods:
#### get_version() ####
(string) Returns the active version of Font Awesome being used.

#### get_stylesheet_url() ####
(string) Returns the active Font Awesome stylesheet URL.

#### get_icons() ####
(array) Returns an alphabetical array of unprefixed icon names for all available icons in the active Font Awesome version.

#### get_prefix() ####
(string) Returns the version-dependent prefix ('fa' or 'icon') that is used in the icons' CSS classes.

#### get_api_data() ####
(object) Returns version data for the remote jsDelivr CDN (uses [jsDelivr API](https://github.com/jsdelivr/api)). Includes all available versions and latest version.

#### Example: ####
```
// Initialize the library with custom args.
Better_Font_Awesome_Library::get_instance( $args );

// Get the active Better Font Awesome Library Object.
$my_bfa = Better_Font_Awesome_Library::get_instance();

// Get info on the Better Font Awesome Library object.
$version = $my_bfa->get_version();
$stylesheet_url = $my_bfa->get_stylesheet_url();
$prefix = $my_bfa->get_prefix();
$icons = $my_bfa->get_icons();

// Output all available icons.
foreach ( $icons as $icon ) {
	echo $icon . '<br />';
}
```

## Shortcode ##
If either the `$args['load_shortcode']` or `$args['load_tinymce_plugin']` initialization arg is set to `true`, then the Better Font Awesome Library will include an icon shortcode that can be used as follows:
```
[icon name="star" class="2x spin" unprefixed_class="my-custom-class"]
```

**name**  
The unprefixed icon name (e.g. star). The version-specific prefix will be automatically prepended.

**class**  
Unprefixed [Font Awesome icon classes](http://fortawesome.github.io/Font-Awesome/examples/). The version-specific prefix will be automatically prepended to each class.

**unprefixed_class**  
Any additional classes that you wish to remain unprefixed (e.g. my-custom-class).

#### Shortcode Output ####
The example shortcode above would output the following depending on which version of Font Awesome you've selected:

**Version 4+**
```
<i class="fa fa-star fa-2x fa-spin my-custom-class"></i>
```
**Version 3**
```
<i class="icon icon-star icon-2x icon-spin my-custom-class"></i>
```

## Filters ##
The Better Font Awesome Library applies the following filters:

#### bfa_init_args ####
Applied to the initialization arguments after they have been parsed with default args, but before they are used to fetch any Font Awesome data.

**Parameters**

* `$init_args` (array)

#### bfa_wp_remote_get_args ####
Applied to arguments passed to all `wp_remote_get()` calls (useful for adjusting the timeout if needed).

**Parameters**

* `$wp_remote_get_args` (array)

#### bfa_fallback_directory_path ####
Applied to the fallback directory path before setting up any fallback CSS info. Can be used to specify an alternate fallback directory to replace the default fallback directory.

*The path must be to a local, non-remote, directory.*

**Parameters**

* `$path` (string)

#### bfa_api_transient_expiration ####
Applied to the API (version information) transient [expiration](http://codex.wordpress.org/Transients_API#Using_Transients). Can be used to increase/decrease the expiration as desired.

**Parameters**

* `$api_expiration` (int)

#### bfa_css_transient_expiration ####
Applied to the CSS stylesheet data transient [expiration](http://codex.wordpress.org/Transients_API#Using_Transients). Can be used to increase/decrease the expiration as desired.

**Parameters**

* `$css_expiration` (int)

#### bfa_icon_list ####
Applied to the icon array after it has been generated from the Font Awesome stylesheet, and before it is assigned to the Better Font Awesome Library object's `$icons` property.

**Parameters**

* `$icons` (array)

#### bfa_prefix ####
Applied to the Font Awesome prefix ('fa' or 'icon') before it is assigned to the Better Font Awesome Library object's `$prefix` property.

**Parameters**

* `$prefix` (string)

#### bfa_icon_class ####
Applied to the classes that are output on each icon's `<i>` element.

**Parameters**

* `$class` (string)

#### bfa_icon ####
Applied to the entire `<i>` element that is output for each icon.

**Parameters**

* `$output` (string)


## To Do ##
Ideas? File an issue or add a pull request!