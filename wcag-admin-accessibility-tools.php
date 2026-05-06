<?php
/**
 * Plugin Name:         WCAG Admin Accessibility Tools
 * Plugin URI:          https://pluginrx.com/plugin/wcag-admin-accessibility-tools/
 * Description:         Admin-side accessibility enhancements and tools to assist with WCAG compliance.
 * Version:             1.2.0
 * Requires at least:   6.0
 * Tested up to:        6.9
 * Requires PHP:        8.0
 * Author:              PluginRx
 * Author URI:          https://pluginrx.com/
 * Discord URI:         https://discord.gg/3HnzNEJVnR
 * Text Domain:         wcag-admin-accessibility-tools
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 * Created on:          June 18, 2025
 */


namespace PluginRx\WCAGAdminAccessibilityTools;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * BOOTSTRAP
 *
 * Loads plugin metadata, performs environment checks, and initializes the plugin.
 */
final class Bootstrap {

    /**
     * Plugin files to load
     */
    public const FILES = [
        'integrations.php',
        'settings.php',
        'structural.php',
        'assistant.php',
    ];


    /**
     * Front-end files
     */
    public const FRONT_END_FILES = [
        'admin-bar.php',
        'forms.php',
    ];


    /**
     * Admin-only files
     */
    public const ADMIN_FILES = [
        'media-library.php',
        'plugins-page.php',
    ];


    /**
     * Plugin header keys
     */
    public const HEADER_KEYS = [
        'name'         => 'Plugin Name',
        'description'  => 'Description',
        'version'      => 'Version',
        'plugin_uri'   => 'Plugin URI',
        'requires_php' => 'Requires PHP',
        'textdomain'   => 'Text Domain',
        'author'       => 'Author',
        'author_uri'   => 'Author URI',
        'discord_uri'  => 'Discord URI'
    ];


    /**
     * Plugin metadata
     */
    private array $meta;


    /**
     * Singleton instance
     */
    private static ?Bootstrap $instance = null;


    /**
     * Get instance
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    private function __construct() {
        $this->meta = $this->load_meta();
        add_action( 'doing_it_wrong_run', [ $this, 'trace_translation_errors' ] );
        add_action( 'plugins_loaded', [ $this, 'load_files' ] );
    } // End __construct()


    /**
     * Check if test mode is enabled
     *
     * @return bool
     */
    public static function is_test_mode() : bool {
        return filter_var( apply_filters( 'wcagaat_test_mode', get_option( 'ddtt_test_mode' ) ), FILTER_VALIDATE_BOOLEAN );
    } // End is_test_mode()


    /**
     * Load plugin metadata
     */
    private function load_meta() : array {
        return get_file_data( __FILE__, self::HEADER_KEYS );
    } // End load_meta()


    /**
     * Trace translation errors
     *
     * This method is hooked to 'doing_it_wrong_run' to log backtraces when early translation functions are called.
     *
     * @param string $function_name The name of the function being called.
     */
    public function trace_translation_errors( $function_name ) : void {
        if ( self::is_test_mode() && '_load_textdomain_just_in_time' === $function_name ) {
            error_log( '--- PHP BACKTRACE: EARLY TRANSLATION DETECTED ---' );
            
            // Generates a readable stack trace in your debug.log
            error_log( ( new \Exception() )->getTraceAsString() );
        }
    } // End trace_translation_errors()


    /**
     * Environment checks
     */
    private function check_environment() : void {
        if ( version_compare( PHP_VERSION, $this->meta[ 'requires_php' ], '<' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( sprintf(
                /* translators: %1$s is plugin name, %2$s is required PHP version */
                esc_html( __( '%1$s requires PHP %2$s or higher.', 'wcag-admin-accessibility-tools' ) ),
                esc_html( $this->meta[ 'name' ] ),
                esc_html( $this->meta[ 'requires_php' ] )
            ) );
        }
    } // End check_environment()


    /**
     * Load plugin files
     */
    public function load_files() : void {
        $this->check_environment();

        foreach ( self::FILES as $file ) {
            $path = self::path( 'inc/' . $file );
            if ( file_exists( $path ) ) {
                require_once $path;
            } else {
                _doing_it_wrong(
                    __METHOD__,
                    sprintf( 'File not found: %s', esc_html( $path ) ),
                    esc_html( self::version() )
                );
            }
        }

        if ( ! is_admin() ) {
            foreach ( self::FRONT_END_FILES as $file ) {
                $path = self::path( 'inc/' . $file );
                if ( file_exists( $path ) ) {
                    require_once $path;
                } else {
                    _doing_it_wrong(
                        __METHOD__,
                        sprintf( 'File not found: %s', esc_html( $path ) ),
                        esc_html( self::version() )
                    );
                }
            }
        }

        if ( is_admin() ) {
            foreach ( self::ADMIN_FILES as $file ) {
                $path = self::path( 'inc/' . $file );
                if ( file_exists( $path ) ) {
                    require_once $path;
                } else {
                    _doing_it_wrong(
                        __METHOD__,
                        sprintf( 'File not found: %s', esc_html( $path ) ),
                        esc_html( self::version() )
                    );
                }
            }
        }
    } // End load_files()


    /**
     * Get metadata value
     */
    public static function meta( string $key ) : string {
        return self::$instance->meta[ $key ] ?? '';
    } // End meta()


    /**
     * Plugin URL
     */
    public static function url( string $append = '' ) : string {
        return plugin_dir_url( __FILE__ ) . ltrim( $append, '/' );
    } // End url()


    /**
     * Plugin path
     */
    public static function path( string $append = '' ) : string {
        return plugin_dir_path( __FILE__ ) . ltrim( $append, '/' );
    } // End path()


    /**
     * Settings URL
     */
    public static function settings_url() : string {
        return admin_url( 'tools.php?page=' . self::meta( 'textdomain' ) );
    } // End settings_url()


    /**
     * Plugin name
     */
    public static function name() : string {
        return self::meta( 'name' );
    } // End name()


    /**
     * Plugin version
     */
    public static function version() : string {
        return self::meta( 'version' );
    } // End version()


    /**
     * Script version
     */
    public static function script_version() : string {
        if ( self::is_test_mode() ) {
            return 'TEST-' . time();
        }
        return self::version();
    } // End script_version()


    /**
     * Text domain
     */
    public static function textdomain() : string {
        return self::meta( 'textdomain' );
    } // End textdomain()


    /**
     * Plugin file
     */
    public static function plugin_file() : string {
        return plugin_basename( __FILE__ );
    } // End plugin_file()


    /**
     * Author
     */
    public static function author() : string {
        return self::meta( 'author' );
    } // End author()


    /**
     * Plugin URI
     */
    public static function plugin_uri() : string {
        return self::meta( 'plugin_uri' );
    } // End plugin_uri()


    /**
     * Author URI
     */
    public static function author_uri() : string {
        return self::meta( 'author_uri' );
    } // End author_uri()


    /**
     * Discord URI
     */
    public static function discord_uri() : string {
        return self::meta( 'discord_uri' );
    } // End discord_uri()


    /**
     * Prevent cloning/unserializing
     */
    public function __clone() {}
    public function __wakeup() {}

}


Bootstrap::instance();