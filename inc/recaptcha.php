<?php 
/**
 * reCAPTCHA Enhancements
 */

namespace PluginRx\WCAGAdminAccessibilityTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Recaptcha {


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Recaptcha $instance = null;


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
        if ( is_admin() || ! ( new Integrations() )->is_gravityformsrecaptcha_active() ) {
            return;
        }

        $handle = 'wcagaat-recaptcha-label';
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( $handle, Bootstrap::url( 'inc/js/recaptcha-label.js' ), [ 'jquery' ], Bootstrap::script_version(), true );
        wp_localize_script( $handle, 'wcagaat_recaptcha', [
            'label' => __( 'reCAPTCHA response', 'wcag-admin-accessibility-tools' ),
        ] );
    } // End enqueue()

}


if ( Settings::get( 'recaptcha_label', true ) ) {
    Recaptcha::instance();
}