Better Font Awesome Library
===========================

*The easiest way to integrate Font Awesome into your WordPress project.*

## Table of contents ##
1. [Introduction](https://github.com/MickeyKay/better-font-awesome-library#introduction)
1. [Features](https://github.com/MickeyKay/better-font-awesome-library#features)
1. [Installation](https://github.com/MickeyKay/better-font-awesome-library#installation)
1. [Usage](https://github.com/MickeyKay/better-font-awesome-library#usage)
1. [Metadata lifecycle](https://github.com/MickeyKay/better-font-awesome-library#metadata-lifecycle)
1. [Initialization Parameters](https://github.com/MickeyKay/better-font-awesome-library#initialization-parameters-args)
1. [Shortcode](https://github.com/MickeyKay/better-font-awesome-library#shortcode)
1. [The Better Font Awesome Library Object](https://github.com/MickeyKay/better-font-awesome-library#the-better-font-awesome-library-object)
1. [Filters](https://github.com/MickeyKay/better-font-awesome-library#filters)
1. [To Do](https://github.com/MickeyKay/better-font-awesome-library#to-do)
1. [Credits](https://github.com/MickeyKay/better-font-awesome-library#credits)

## Introduction ##
The Better Font Awesome Library integrates validated Font Awesome Free 5.x metadata and version-coupled CDN assets into WordPress projects, along with CSS registration, a shortcode, and a TinyMCE icon shortcode generator. Consumers can supply locally resolved metadata and schedule asynchronous refresh work without making normal requests wait on the Font Awesome service.

## Features ##
* Returns validated local metadata immediately from a provider, transient, or bundled fallback.
* Exposes an explicit, bounded refresh operation for consumer-controlled asynchronous workers.
* Generates an easy-to-use [PHP object](#the-better-font-awesome-library-object) that contains all relevant info for the version of Font Awesome you're using, including: version, stylesheet URL, array of available icons, and prefix used (`icon` or `fa`).
* Loads exact-version Font Awesome Free 5 CSS and webfonts from the Font Awesome CDN.
* Includes a TinyMCE drop-down shortcode generator.
* Includes checksummed Font Awesome Free 5.14.0 metadata as a validated fallback. You can specify validated custom fallback JSON with the `bfa_fallback_release_data_path` filter.
* Preserves the established `bfa-release-data` transient for backward compatibility.

## Installation ##
The Better Font Awesome Library should ideally be installed via Composer:
```
composer require mickey-kay/better-font-awesome-library
```

Alternately, you can install the library manually, which can be useful for development and/or custom builds:
```
git clone https://github.com/MickeyKay/better-font-awesome-library.git
cd better-font-awesome-library
npm run build
```

## Usage ##
1. Copy the /better-font-awesome-library folder into your project.

2. Add the following code to your main plugin file or your theme's functions.php file.
```php
add_action( 'init', 'my_prefix_load_bfa' );
    /**
    * Initialize the Better Font Awesome Library.
    *
    * (see usage notes below on proper hook priority)
    */
    function my_prefix_load_bfa() {

    // Include the main library file. Make sure to modify the path to match your directory structure.
    require_once ( dirname( __FILE__ ) . '/better-font-awesome-library/better-font-awesome-library.php' );

    // Set the library initialization args (defaults shown).
    $args = array(
      'include_v4_shim'     => false,
      'remove_existing_fa'  => false,
      'load_styles'         => true,
      'load_admin_styles'   => true,
      'load_shortcode'      => true,
      'load_tinymce_plugin' => true,
      'release_data_provider' => null,
      'release_data_refresh_callback' => null,
    );

    // Initialize the Better Font Awesome Library.
    Better_Font_Awesome_Library::get_instance( $args );
}
```

3. If desired, use the [Better Font Awesome Library object](#the-better-font-awesome-library-object) to manually include Font Awesome CSS, output lists of available icons, create your own shortcodes, and much more.

#### Usage Notes ####
The Better Font Awesome Library is designed to work in conjunction with the [Better Font Awesome](https://wordpress.org/plugins/better-font-awesome/) WordPress plugin. The plugin initializes this library (with its own initialization args) on the `init` hook, priority `5`. When using the Better Font Awesome Library in your project, you have two options:

1. Initialize later, to ensure that any Better Font Awesome plugin settings override yours (this is the default behavior, shown above by initializing the library on the `init` hook with default priority `10`.
1. Initialize earlier, to "take over" and prevent Better Font Awesome settings from having an effect.

## Metadata lifecycle ##

Normal frontend, admin, editor, REST, and cron-triggering requests never call the Font Awesome metadata service synchronously. BFAL resolves release data in this order:

1. The per-request validated value.
2. An optional `release_data_provider` callable that returns already-resolved local data.
3. A validated value from the established `bfa-release-data` transient.
4. The checksummed and validated bundled Font Awesome Free 5.14.0 fallback.

When BFAL reaches the fallback, it invokes `release_data_refresh_callback` once if configured. Otherwise it fires `bfa_release_data_refresh_requested` with the supported channel and library instance. The handler must only schedule work and return promptly. Scheduling, locking, durable last-known-good persistence, retry backoff, jitter, and freshness policy belong to the consumer.

An asynchronous worker can call `refresh_release_data()`. That explicit operation requests the `5.x` Free channel with TLS verification, no redirects, a timeout of at most five seconds, and a response limit of 2 MiB. It stores the established transient shape only after the entire release record validates. It returns the validated release array on success or a sanitized `WP_Error` on failure.

The Font Awesome API and CDN are external services. Consumers should document when they contact those services and apply the consent, privacy, scheduling, and persistence policy appropriate to their application.

## Initialization Parameters ($args) ##
The following arguments can be used to initialize the library using `Better_Font_Awesome_Library::get_instance( $args )`:

#### $args['include_v4_shim'] ####
(boolean) Include the [Font Awesome v4 shim CSS stylesheet](https://fontawesome.com/how-to-use/on-the-web/setup/upgrading-from-version-4) to support legacy icon.
* `true`
* `false` (default)

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

#### $args['release_data_provider'] ####

(callable|null) Optional callable that returns an already-resolved release array or BFAL release record. Providers used by normal getters must not perform remote I/O.

#### $args['release_data_refresh_callback'] ####

(callable|null) Optional callback invoked once when BFAL falls back to bundled data. It receives the supported channel and BFAL instance and must schedule asynchronous work rather than perform transport inline.

### Deprecated

#### $args['version'] (2.0.0) ####
_The library now always defaults to the latest available version of Font Awesome._

(string) Which version of Font Awesome you want to use.
* `'latest'` (default) - always use the latest available version.
* `'3.2.1'` - any existing Font Awesome version number.

#### $args['minified'] (2.0.0) ####
_The library now always defaults to minified CSS._

(boolean) Use minified Font Awesome CSS.
* `true` (default) - uses minifed CSS.
* `false` - uses unminified CSS.

## Shortcode ##
If either the `$args['load_shortcode']` or `$args['load_tinymce_plugin']` initialization arg is set to `true`, then the Better Font Awesome Library will include an `[icon]` shortcode that can be used as follows:
```
[icon name="star" class="2x spin" unprefixed_class="my-custom-class"]
```

#### name
The unprefixed icon name (e.g. star). The version-specific prefix will be automatically prepended.

#### class
Unprefixed [Font Awesome icon classes](http://fortawesome.github.io/Font-Awesome/examples/). The version-specific prefix will be automatically prepended to each class.

#### unprefixed_class
Any additional classes that you wish to remain unprefixed (e.g. my-custom-class).

#### style
The specific icon style (e.g. `brand` vs. `solid`) to use.

### Shortcode Output
The following shortcode:
```
[icon name="moon" style="solid" class="2x spin" unprefixed_class="my-custom-class"]
```
. . . will produce the following HTML:
```
<i class="fas fa-moon fa-2x fa-spin my-custom-class "></i>
```

## The Better Font Awesome Library Object ##
The Better Font Awesome Library object can be accessed with the following code:
`Better_Font_Awesome_Library::get_instance();`

The object has the following public methods:
#### get_version() ####
(string) Returns the active version of Font Awesome being used.

#### get_stylesheet_url() ####
(string) Returns the Font Awesome stylesheet URL.

#### get_stylesheet_url_v4_shim() ####
(string) Returns the Font Awesome v4 shim stylesheet URL.

#### get_icons() ####
(array) Returns an associative array of icon hex values (index, e.g. \f000) and unprefixed icon names (values, e.g. rocket) for all available icons in the active Font Awesome version.

#### get_release_icons() ####
(array) Returns icon data in the exact format provided by the Font Awesome GraphQL API.

#### get_release_assets() ####
(array) Returns validated Free release asset data for the selected Font Awesome version.

#### get_release_record() ####
(array) Returns the validated internal record with `schema_version`, `channel`, `edition`, `source`, and the compatibility-preserving `release` array.

#### get_release_channel() ####
(string) Returns the supported Font Awesome channel, currently `5.x`.

#### request_release_data_refresh() ####
Requests asynchronous refresh scheduling through the configured callback or `bfa_release_data_refresh_requested` action. This method performs no remote transport.

#### refresh_release_data() ####
(array|WP_Error) Performs one bounded refresh attempt in an explicit worker context. Consumers own locking, retry, and durable persistence policy.

#### get_prefix() ####
(string) Returns the version-dependent prefix ('fa' or 'icon') that is used in the icons' CSS classes.

#### get_errors() ####
(array) Returns all library errors, including API and CDN fetch failures.

### Deprecated

#### get_api_data() (2.0.0) ####
_The library no longe relies on the jsDelivr CDN._

(object) Returns version data for the remote jsDelivr CDN (uses [jsDelivr API](https://github.com/jsdelivr/api)). Includes all available versions and latest version.

### Example:

```php
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

## Filters ##
The Better Font Awesome Library applies the following filters:

#### bfa_init_args ####
Applied to the initialization arguments after they have been parsed with default args, but before they are used to fetch any Font Awesome data.

**Parameters**

* `$init_args` (array)

#### bfa_wp_remote_get_args ####
Applied to arguments passed to the explicit metadata refresh request. TLS verification, blocking worker transport, no redirects, the maximum timeout, and the maximum response size remain enforced after this filter.

**Parameters**

* `$wp_remote_get_args` (array)

#### bfa_font_awesome_release_channel ####
Applied to the release channel used by explicit refresh. BFAL currently accepts only `5.x`; unsupported filtered values fail before transport.

**Parameters**

* `$channel` (string)

#### bfa_fallback_release_data_path ####
Applied to the path for the fallback release data JSON file. Can be used to specify an alternate fallback data file.

**Parameters**

* `$fallback_release_data_path` (string)

#### bfa_release_data_transient_expiration ####
This value controls how often the plugin will check for the latest updated version of Font Awesome. Can be used to increase/decrease the frequency of this check as desired.

**Parameters**

* `$api_expiration` (int) (default: `WEEK_IN_SECONDS`)

#### bfa_icon_list ####
Applied to the icon array after it has been generated from the Font Awesome stylesheet, and before it is assigned to the Better Font Awesome Library object's `$icons` property.

**Parameters**

* `$icons` (array)

#### bfa_icon_array ####
Applied to the normalized icon array after the deprecated `bfa_icon_list` filter.

**Parameters**

* `$icons` (array)

#### bfa_icon_class ####
Applied to the classes that are output on each icon's `<i>` element.

**Parameters**

* `$class` (string)

#### bfa_icon_tag ####
Applied to the tag that is output for each icon. Defaults is 'i', which outputs `<i>`.

**Parameters**

* `$tag` (string)

#### bfa_icon ####
Applied to the entire `<i>` element that is output for each icon.

**Parameters**

* `$output` (string)

#### bfa_show_errors ####
Applied to the boolean that determines whether or not to suppress all Font Awesome warnings that normally display in the admin.

**Parameters**

* `$show_errors` (true)

## Actions ##

#### bfa_release_data_refresh_requested ####
Fires once per BFAL request when no valid provider or transient value is available and bundled fallback data is selected. Handlers receive the `5.x` channel and BFAL instance. Handlers must schedule asynchronous work and return promptly.

### Deprecated

#### bfa_fallback_directory_path ####
_This is now replaced by the similar `bfa_fallback_release_data_path` filter._
Applied to the fallback directory path before setting up any fallback CSS info. Can be used to specify an alternate fallback directory to replace the default fallback directory.

*The path must be to a local, non-remote, directory.*

**Parameters**

* `$path` (string)

#### bfa_api_transient_expiration (2.0.0) ####
_This data now comes from the GraphQL API. The new `bfa_release_data_transient_expiration` replaces this legacy filter._

Applied to the API (version information) transient [expiration](http://codex.wordpress.org/Transients_API#Using_Transients). Can be used to increase/decrease the expiration as desired.

**Parameters**

* `$api_expiration` (int)

#### bfa_css_transient_expiration (2.0.0) ####
_This data is now no longer necessary._

Applied to the CSS stylesheet data transient [expiration](http://codex.wordpress.org/Transients_API#Using_Transients). Can be used to increase/decrease the expiration as desired.

**Parameters**

* `$css_expiration` (int)

#### bfa_force_fallback (2.0.0) ####
_There should no longer be a need to force a fallback._

Applied to the boolean that determines whether or not to force the included fallback version of Font Awesome to load. This can be useful if you're having trouble with delays or timeouts.

**Parameters**

* `$force_fallback` (false)

#### bfa_prefix (2.0.0) ####
_Given the update to v5+ always, there should be no need to modify the icon prefix._

Applied to the Font Awesome prefix ('fa' or 'icon') before it is assigned to the Better Font Awesome Library object's `$prefix` property.

**Parameters**

* `$prefix` (string)

## To Do ##
Ideas? File an issue or add a pull request!
* Add README section on manually updating the fallback version.
* Remove existing FA? - move to later hook so that it works for styles enqueued via shortcode (= wp_footer basically)

## Credits ##
Special thanks to the following folks and their plugins for inspiration and support:
* [Font Awesome Icons](http://wordpress.org/plugins/font-awesome/ "Font Awesome Icons") by [Rachel Baker](http://rachelbaker.me/ "Rachel Baker")
* [Font Awesome More Icons](https://wordpress.org/plugins/font-awesome-more-icons/ "Font Awesome More Icons") by [Web Guys](http://webguysaz.com/ "Web Guys")
* [Font Awesome Shortcodes](https://wordpress.org/plugins/font-awesome-shortcodes/) by [FoolsRun](https://profiles.wordpress.org/foolsrun/ "FoolsRun")
* Dmitriy Akulov and the awesome folks at [jsDelivr](http://www.jsdelivr.com/)

And many thanks to the following folks who helped with testing and QA:
* [Jeffrey Dubinksy](http://vanishingforests.org/)
* [Neil Gee](https://twitter.com/_neilgee)
* [Michael Beil](https://twitter.com/MichaelBeil)
* [Rob Neue](https://twitter.com/rob_neu)
* [Gary Jones](https://twitter.com/GaryJ)
* [Jan Hoek](https://twitter.com/JanHoekdotCom)
