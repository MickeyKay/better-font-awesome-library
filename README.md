Better Font Awesome Library
===========================

*The easiest way to integrate Font Awesome into your theme or plugin.*

The Better Font Awesome Library allows developers to integrate Font Awesome using a variety of settings

## Usage ##
1. Copy the better-font-awesome-libary folder into your project.

2. Add the following code to your main plugin file or your theme's function.php file.
   ```
add_action( 'plugins_loaded', 'my_slug_load_bfa' );
function my_slug_load_bfa() {
		
		$args = array(
				'version' => 'latest',
				'minified' => true,
				'remove_existing_fa' => false,
				'load_styles' => true,
				'load_admin_styles' => true,
				'load_tinymce_plugin' => false,
		);

		$my_bfa = Better_Font_Awesome_Object::get_instance( $args );
}
```
3. You will now have access to 


## Settings ##
```
$args = array(
		'version' => 'latest',
		'minified' => true,
		'remove_existing_fa' => false,
		'load_styles' => true,
		'load_admin_styles' => true,
		'load_tinymce_plugin' => false,
);
```


