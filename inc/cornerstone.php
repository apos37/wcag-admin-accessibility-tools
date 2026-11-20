<?php 
/**
 * Cornerstone Enhancements
 */

namespace PluginRx\WCAGAdminAccessibilityTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Cornerstone {


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Cornerstone $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
    } // End __construct()


    /**
     * Enqueue frontend assets
     */
    public function enqueue() {
        if ( is_admin() || ! ( new Integrations() )->has_cornerstone_builder() ) {
            return;
        }

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'wcagaat-cornerstone-menus', Bootstrap::url( 'inc/js/cornerstone-menus.js' ), [ 'jquery' ], Bootstrap::script_version(), true );
    } // End enqueue()

}


if ( Settings::get( 'cornerstone_menus', false ) ) {
    Cornerstone::instance();
}