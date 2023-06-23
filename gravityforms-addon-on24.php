<?php
/*
Plugin Name: Gravity Forms On24 Add-On
Plugin URI: https://github.com/KineticTeam/gravityforms-addon-on24
Description: Allows Gravity Forms integration with the On24 API
Version: 1.1.0
Author: Kinetic
Author URI: https://kinetic.com
*/

define('GF_ON24_ADDON_VERSION', '1.1.0');

add_action('gform_loaded', ['GF_On24_AddOn_Bootstrap', 'load'], 5);

class GF_On24_AddOn_Bootstrap
{
    public static function load()
    {
        if (! method_exists('GFForms', 'include_feed_addon_framework')) {
            return;
        }

        require_once('class-gf-on24-addon.php');

        GFAddOn::register('GFOn24AddOn');

        // Load admin styles
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('gf-on24', plugin_dir_url(__FILE__) . '/styles/admin.css');
        });
    }
}

function gf_simple_feed_addon()
{
    return GFOn24AddOn::get_instance();
}
