<?php
/**
 * Plugin Name:         WCAG Admin Accessibility Tools
 * Plugin URI:          https://pluginrx.com/plugin/wcag-admin-accessibility-tools/
 * Description:         Admin-side accessibility enhancements and tools to assist with WCAG compliance.
 * Version:             1.0.1
 * Requires at least:   5.9
 * Tested up to:        6.8
 * Requires PHP:        7.4
 * Author:              PluginRx
 * Author URI:          https://pluginrx.com/
 * Discord URI:         https://discord.gg/3HnzNEJVnR
 * Text Domain:         wcag-admin-accessibility-tools
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 * Created on:          June 18, 2025
 */


/**
 * Define Namespace
 */
namespace Apos37\WCAGAdminAccessibilityTools;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Defines
 */
$plugin_data = get_file_data( __FILE__, [
    'name'         => 'Plugin Name',
    'version'      => 'Version',
    'plugin_uri'   => 'Plugin URI',
    'requires_php' => 'Requires PHP',
    'textdomain'   => 'Text Domain',
    'author'       => 'Author',
    'author_uri'   => 'Author URI',
    'discord_uri'  => 'Discord URI'
] );

// Versions
define( 'WCAGAAT_VERSION', $plugin_data[ 'version' ] );
define( 'WCAGAAT_SCRIPT_VERSION', time() );                                                 // TODO: REPLACE WITH time() DURING TESTING
define( 'WCAGAAT_MIN_PHP_VERSION', $plugin_data[ 'requires_php' ] );

// Names
define( 'WCAGAAT_NAME', $plugin_data[ 'name' ] );
define( 'WCAGAAT_TEXTDOMAIN', $plugin_data[ 'textdomain' ] );
define( 'WCAGAAT__TEXTDOMAIN', str_replace( '-', '_', WCAGAAT_TEXTDOMAIN ) );
define( 'WCAGAAT_AUTHOR', $plugin_data[ 'author' ] );
define( 'WCAGAAT_AUTHOR_URI', $plugin_data[ 'author_uri' ] );
define( 'WCAGAAT_PLUGIN_URI', $plugin_data[ 'plugin_uri' ] );
define( 'WCAGAAT_GUIDE_URL', WCAGAAT_AUTHOR_URI . 'guide/plugin/' . WCAGAAT_TEXTDOMAIN . '/' );
define( 'WCAGAAT_DOCS_URL', WCAGAAT_AUTHOR_URI . 'docs/plugin/' . WCAGAAT_TEXTDOMAIN . '/' );
define( 'WCAGAAT_SUPPORT_URL', WCAGAAT_AUTHOR_URI . 'support/plugin/' . WCAGAAT_TEXTDOMAIN . '/' );
define( 'WCAGAAT_DISCORD_URL', $plugin_data[ 'discord_uri' ] );

// Paths
define( 'WCAGAAT_BASENAME', plugin_basename( __FILE__ ) );                                          //: text-domain/text-domain.php
define( 'WCAGAAT_ABSPATH', plugin_dir_path( __FILE__ ) );                                           //: /home/.../public_html/wp-content/plugins/text-domain/
define( 'WCAGAAT_DIR', plugin_dir_url( __FILE__ ) );                                                //: https://domain.com/wp-content/plugins/text-domain/
define( 'WCAGAAT_INCLUDES_ABSPATH', WCAGAAT_ABSPATH . 'inc/' );                                     //: /home/.../public_html/wp-content/plugins/text-domain/inc/
define( 'WCAGAAT_INCLUDES_DIR', WCAGAAT_DIR . 'inc/' );                                             //: https://domain.com/wp-content/plugins/text-domain/inc/
define( 'WCAGAAT_JS_PATH', WCAGAAT_INCLUDES_DIR . 'js/' );                                          //: https://domain.com/wp-content/plugins/text-domain/inc/js/
define( 'WCAGAAT_CSS_PATH', WCAGAAT_INCLUDES_DIR . 'css/' );                                        //: https://domain.com/wp-content/plugins/text-domain/inc/css/
define( 'WCAGAAT_SETTINGS_PATH', admin_url( 'tools.php?page=' . WCAGAAT__TEXTDOMAIN ) );            //: https://domain.com/wp-admin/tools.php?page=text-domain

// Screen IDs
define( 'WCAGAAT_SETTINGS_SCREEN_ID', 'tools_page_' . WCAGAAT__TEXTDOMAIN );


/**
 * Includes
 */
require_once WCAGAAT_INCLUDES_ABSPATH . 'common.php';
require_once WCAGAAT_INCLUDES_ABSPATH . 'helpers.php';
require_once WCAGAAT_INCLUDES_ABSPATH . 'integrations.php';
require_once WCAGAAT_INCLUDES_ABSPATH . 'settings.php';
require_once WCAGAAT_INCLUDES_ABSPATH . 'media-library.php';
require_once WCAGAAT_INCLUDES_ABSPATH . 'structural.php';
require_once WCAGAAT_INCLUDES_ABSPATH . 'modes.php';
require_once WCAGAAT_INCLUDES_ABSPATH . 'forms.php';

if ( get_option( 'wcagaat_frontend_tools', true ) ) {
    require_once WCAGAAT_INCLUDES_ABSPATH . 'admin-bar.php';
}