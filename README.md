Better Font Awesome Library
===========================

*The easiest way to integrate Font Awesome into your WordPress project.*

## Table of contents ##
1. [Introduction](https://github.com/MickeyKay/better-font-awesome-library#introduction)
1. [Features](https://github.com/MickeyKay/better-font-awesome-library#features)
1. [Installation](https://github.com/MickeyKay/better-font-awesome-library#installation)
1. [Stable release and prerelease rollback](https://github.com/MickeyKay/better-font-awesome-library#stable-release-and-prerelease-rollback)
1. [Font Awesome 7 and BFAL 3](https://github.com/MickeyKay/better-font-awesome-library#font-awesome-7-and-bfal-3)
1. [Changelog](https://github.com/MickeyKay/better-font-awesome-library/blob/master/CHANGELOG.md)
1. [Usage](https://github.com/MickeyKay/better-font-awesome-library#usage)
1. [Metadata lifecycle](https://github.com/MickeyKay/better-font-awesome-library#metadata-lifecycle)
1. [Compatibility notes](https://github.com/MickeyKay/better-font-awesome-library#compatibility-notes)
1. [Initialization Parameters](https://github.com/MickeyKay/better-font-awesome-library#initialization-parameters-args)
1. [Shortcode](https://github.com/MickeyKay/better-font-awesome-library#shortcode)
1. [The Better Font Awesome Library Object](https://github.com/MickeyKay/better-font-awesome-library#the-better-font-awesome-library-object)
1. [Filters](https://github.com/MickeyKay/better-font-awesome-library#filters)
1. [To Do](https://github.com/MickeyKay/better-font-awesome-library#to-do)
1. [Credits](https://github.com/MickeyKay/better-font-awesome-library#credits)

## Introduction ##
The Better Font Awesome Library integrates validated Font Awesome Free metadata and channel-coupled assets into WordPress projects, along with CSS registration, a shortcode, and a TinyMCE icon shortcode generator. Consumers can supply locally resolved metadata and schedule asynchronous refresh work without making normal requests wait on the Font Awesome service. Tagged BFAL 2.x releases use Font Awesome 5.x; BFAL 3 defaults to Font Awesome 7.x.

## Features ##
* Returns validated local metadata immediately from a provider, transient, or bundled fallback.
* Exposes an explicit, bounded refresh operation for consumer-controlled asynchronous workers.
* Generates an easy-to-use [PHP object](#the-better-font-awesome-library-object) that contains all relevant info for the version of Font Awesome you're using, including: version, stylesheet URL, array of available icons, and prefix used (`icon` or `fa`).
* Loads the exact assets coupled to the immutable selected Font Awesome Free channel.
* Includes a TinyMCE drop-down shortcode generator.
* Includes validated Font Awesome Free 7.3.1 CSS, WOFF2, and metadata as the immediate default fallback, plus the established Font Awesome Free 5.14.0 metadata fallback for explicit 5.x consumers. The `bfa_fallback_release_data_path` filter remains available for the 5.x fallback.
* Preserves the established `bfa-release-data` transient for backward compatibility.

## Installation ##
The Better Font Awesome Library should ideally be installed via Composer:
```
composer require mickey-kay/better-font-awesome-library:2.1.0
```

Alternately, you can install the library manually, which can be useful for development and/or custom builds:
```
git clone https://github.com/MickeyKay/better-font-awesome-library.git
cd better-font-awesome-library
npm run build
```

## Stable release and prerelease rollback ##

BFAL 2.1.0 remains the stable release. Composer users can install it with:

```
composer require mickey-kay/better-font-awesome-library:2.1.0
```

BFAL 2.1.0 provides validated live Font Awesome Free 5.x metadata with no metadata HTTP on ordinary requests. It supports consumer-controlled asynchronous refresh orchestration, durable last-known-good data in the Better Font Awesome consumer, and a checksummed bundled fallback. The accepted BFA integration supplies WordPress-owned scheduling, retry, locking, migration, and multisite behavior.

Normal static Font Awesome stylesheet registration uses anonymous CORS mode on BFAL's exact main and optional v4 shim handles. This preserves Block Editor, Classic Editor, hybrid `wp_editor()` screens, TinyMCE picker, frontend, and v4 compatibility behavior. The runtime version makes WordPress request `?ver=2.1.0` for those handles.

To roll back from a BFAL 3 prerelease, restore BFAL 2.1.0 and redeploy the resulting lockfile:

```
composer require mickey-kay/better-font-awesome-library:2.1.0 --with-all-dependencies
```

BFAL follows versions published from repository tags. The stable release does not change the first-caller singleton ownership contract, introduce a post-construction registration API, or alter metadata transport, validation, caching, fallback, or ownership behavior from the accepted rc.2 implementation.

## Font Awesome 7 and BFAL 3 ##

BFAL 3 changes the default Font Awesome major. Existing tagged BFAL 2.x releases remain the stable Font Awesome 5-compatible line. BFAL 3 release candidates are intended for integration testing, and Better Font Awesome integration and browser acceptance are required before BFAL 3.0.0 stable publication.

BFAL 3 defaults to the `7.x` release channel when the first caller supplies no `release_channel` argument. Explicit `release_channel => '7.x'` is identical to that default. Consumers that deliberately require the legacy runtime can select `release_channel => '5.x'`. The first caller owns this immutable selection, just like every other initialization argument.

The 7.x channel validates and loads the packaged Font Awesome Free 7.3.1 baseline immediately. Its CSS and WOFF2 URLs are derived from the BFAL installation URL, so activation needs no HTTP request, cron run, migration, pending state, or setting. Normal frontend, admin, editor, REST, shortcode, picker, and getter paths perform no metadata HTTP.

An explicit 7.x background refresh discovers only the latest supported 7.x Free release. A same-version check uses one Font Awesome metadata request. A genuinely newer candidate is limited to 18 total requests, 4 MiB of response bodies, and 30 seconds. It must pass exact npm publication, cdnjs and jsDelivr byte comparison, CSS SRI, required WOFF2, CSS-to-font reference, family, style, icon, and alias validation. The worker returns one complete schema-2 record or a sanitized `WP_Error`; BFAL does not persist 7.x refresh results. Consumer code owns last-known-good storage, scheduling, locking, retry, and freshness policy.

The 7.x channel never crosses automatically to Font Awesome 8. Supporting another Font Awesome major requires a separately reviewed BFAL compatibility release.

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
      'release_channel' => '7.x',
    );

    // Initialize the Better Font Awesome Library.
    Better_Font_Awesome_Library::get_instance( $args );
}
```

3. If desired, use the [Better Font Awesome Library object](#the-better-font-awesome-library-object) to manually include Font Awesome CSS, output lists of available icons, create your own shortcodes, and much more.

#### Usage Notes ####
The Better Font Awesome Library is designed to work in conjunction with the [Better Font Awesome](https://wordpress.org/plugins/better-font-awesome/) WordPress plugin. The plugin initializes this library (with its own initialization args) on the `init` hook, priority `5`. When using the Better Font Awesome Library in your project, you have two options:

1. Initialize later so Better Font Awesome reaches the singleton first. Your later arguments are ignored, and Better Font Awesome owns the configuration. This is the default behavior shown above by initializing on the `init` hook at priority `10`.
1. Initialize earlier to take ownership. Better Font Awesome's later arguments are ignored and cannot override yours.

This first-caller contract is intentional and applies to every initialization argument, including the release channel, release-data provider, and refresh callback. Hook priority determines ownership. BFAL does not provide post-construction registration, reset, mutation, or ownership transfer.

## Metadata lifecycle ##

Normal frontend, admin, editor, REST, and cron-triggering requests never call the Font Awesome metadata service synchronously. BFAL resolves release data only for the immutable channel selected during first-caller initialization, in this order:

1. The per-request validated value.
2. An optional `release_data_provider` callable that returns already-resolved local data.
3. A validated value from the established `bfa-release-data` transient.
4. The validated bundled fallback for the selected channel: Font Awesome Free 7.3.1 for `7.x`, or the established Font Awesome Free 5.14.0 fallback for explicit `5.x`.

When BFAL reaches the fallback, it invokes `release_data_refresh_callback` once if configured. Otherwise it fires `bfa_release_data_refresh_requested` with the supported channel and library instance. The handler must only schedule work and return promptly. Scheduling, locking, durable last-known-good persistence, retry backoff, jitter, and freshness policy belong to the consumer.

A provider may return a release array or a declared BFAL release record. Declared records must use the exact supported `schema_version`, `channel`, and `edition`, an allowed `source`, and a fully valid nested release. BFAL rejects mismatches rather than discarding or normalizing them.

An asynchronous worker can call `refresh_release_data()`. For explicit `5.x`, the established operation retains its existing validated transient behavior and release-array return value. For `7.x`, one bounded attempt returns a complete validated schema-2 record or a sanitized `WP_Error` and performs no BFAL persistence. Both paths require TLS, reject redirects and unsafe URLs, and leave the prior validated data untouched on failure.

The Font Awesome API and CDN are external services. Consumers should document when they contact those services and apply the consent, privacy, scheduling, and persistence policy appropriate to their application.

## Compatibility notes ##

`Better_Font_Awesome_Library::get_instance( $args )` retains its established first-call contract. The first caller owns initialization, and arguments passed to later calls are ignored. An earlier plugin or theme can therefore intentionally own BFAL before Better Font Awesome initializes. Better Font Awesome is not guaranteed to override that owner.

This precedence is supported compatibility behavior. BFAL remains safe when another consumer owns it: normal metadata resolution performs no synchronous HTTP, release data is validated before adoption, and validated transient or bundled fallback data remains available. A new public ownership API would require a demonstrated interoperability need and explicit repository owner approval.

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

#### $args['release_channel'] ####

(string) Immutable Font Awesome Free major channel selected by the first singleton caller.
* `7.x` (default in BFAL 3)
* `5.x` - explicit legacy behavior compatible with the BFAL 2.x runtime

The selected 7.x channel follows completely validated 7.x releases only. It will not update across a future Font Awesome major.

An unsupported first-caller channel value fails closed. BFAL records a sanitized `bfa_channel_unsupported` error and returns no release metadata or stylesheet URLs. Because first-caller ownership remains immutable, a later caller cannot replace that invalid selection.

These metadata collaborators are initialization arguments and therefore follow the same first-caller ownership contract. They cannot be added or replaced through a later `get_instance()` call.

### Deprecated

#### $args['version'] (2.0.0) ####
_The library no longer selects a version through this argument. Validated Font Awesome Free metadata comes from the configured local provider, compatibility transient, or bundled fallback for the immutable selected channel._

(string) Retained for compatibility. Supplied values are ignored because the validated release record selects the supported version within the immutable major channel and its coupled assets.

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
(array) Returns icon data in BFAL's established public icon shape. Schema-2 family and style metadata is adapted without changing that shape.

#### get_release_assets() ####
(array) Returns validated Free release asset data for the selected Font Awesome version.

#### get_release_record() ####
(array) Returns the validated internal record with `schema_version`, `channel`, `edition`, `source`, and the compatibility-preserving `release` array.

#### get_release_channel() ####
(string) Returns the immutable selected Font Awesome channel, `7.x` by default or explicit `5.x`. Returns an empty string when an unsupported first-caller value has caused the runtime to fail closed.

#### request_release_data_refresh() ####
Requests asynchronous refresh scheduling through the configured callback or `bfa_release_data_refresh_requested` action. This method performs no remote transport.

#### refresh_release_data() ####
(array|WP_Error) Performs one bounded refresh attempt in an explicit worker context. Consumers own locking, retry, and durable persistence policy.

#### get_prefix() ####
(string) Returns the version-dependent prefix ('fa' or 'icon') that is used in the icons' CSS classes.

#### get_errors() ####
(array) Returns sanitized metadata, provider, cache, and fallback errors.

### Deprecated

#### get_api_data() (2.0.0) ####
_This deprecated method is no longer used for ordinary release discovery. The explicit Font Awesome 7 refresh worker uses exact-version jsDelivr files only for mandatory cross-provider byte validation._

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
Applied once while the first singleton caller's release channel is initialized. BFAL accepts only `5.x` and `7.x`. The resolved value is immutable for the lifetime of that instance, so later calls and later filter changes cannot switch metadata or assets. An unsupported result fails closed with no release metadata or stylesheet URLs.

**Parameters**

* `$channel` (string)

#### bfa_fallback_release_data_path ####
Applied to the path for the fallback release data JSON file. Can be used to specify an alternate fallback data file.

**Parameters**

* `$fallback_release_data_path` (string)

#### bfa_release_data_transient_expiration ####
This value controls how often the plugin will check for the latest updated version of Font Awesome. Can be used to increase/decrease the frequency of this check as desired.

**Parameters**

* `$api_expiration` (int) (default: `DAY_IN_SECONDS`)

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
Fires once per BFAL request when no valid provider or transient value is available and bundled fallback data is selected. Handlers receive the immutable selected channel (`5.x` or `7.x`) and BFAL instance. Handlers must schedule asynchronous work and return promptly.

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
