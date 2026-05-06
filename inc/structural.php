<?php 
/**
 * Structural Enhancements
 */


namespace PluginRx\WCAGAdminAccessibilityTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Structural {


    /**
     * Option to check if a skip link is present
     *
     * @var string
     */
    private const OPTION_NAME = 'wcagaat_skip_link_present';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Structural $instance = null;


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
        add_action( 'init', [ $this, 'maybe_cache_skip_link_check' ] );
        add_action( 'update_option_wcagaat_skip_link', [ $this, 'refresh_skip_link_cache' ], 10, 2 );
        add_action( 'wp_body_open', [ $this, 'skip_to_content_link' ], 5 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	} // End __construct()


    /**
     * Maybe cache the skip link check
     *
     * @return void
     */
    public function maybe_cache_skip_link_check() {
        // Are we resetting the cache?
        if ( is_admin() && isset( $_GET[ 'settings-updated' ] ) && sanitize_key( wp_unslash( $_GET[ 'settings-updated' ] ) ) === 'true' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $has_skip_link = $this->check_for_skip_link() ? 'yes' : 'no';
            update_option( self::OPTION_NAME, $has_skip_link, false );

        // Add it if it doesn't exist
        } elseif ( false === Settings::get( 'skip_link_present' ) ) {
            $has_skip_link = $this->check_for_skip_link() ? 'yes' : 'no';
            add_option( self::OPTION_NAME, $has_skip_link, '', 'no' );
        }
    } // End maybe_cache_skip_link_check()


    /**
     * Check if a skip link is needed
     *
     * @return bool True if a skip link is needed, false otherwise.
     */
    public function check_for_skip_link() {
        if ( is_admin() ) {
            $home_url = home_url( '/' );
            $response = wp_remote_get( $home_url );

            if ( is_wp_error( $response ) ) {
                return false;
            }

            $html = wp_remote_retrieve_body( $response );

            // Normalize spacing to avoid matching failures due to formatting
            $normalized_html = preg_replace( '/\s+/', ' ', $html );

            // Exclude plugin's own skip link
            $normalized_plugin_link = preg_replace( '/\s+/', ' ', $this->skip_to_content_link() );

            // Remove it from search space
            $cleaned_html = str_replace( $normalized_plugin_link, '', $normalized_html );

            // Look for known skip link markers
            if ( stripos( $cleaned_html, 'skip-link' ) !== false ) {
                return true;
            }
        }

        return false;
    } // End check_for_skip_link()


    /**
     * Output the skip to content link immediately after <body>
     *
     * @return string
     */
    public function skip_to_content_link() {
        return '<a class="wcagaat-skip-link" href="#content">' . __( 'Skip to main content', 'wcag-admin-accessibility-tools' ) . '</a>';
    } // End skip_to_content_link()


    /**
     * Enqueue frontend assets
     */
    public function enqueue() {
        if ( is_admin() ) {
            return;
        }

        $script_version = Bootstrap::script_version();
        
        $handle = 'wcagaat-structural';
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( $handle, Bootstrap::url( 'inc/js/structural.js' ), [ 'jquery' ], $script_version, true );
        wp_enqueue_style( $handle, Bootstrap::url( 'inc/css/structural.css' ), [], $script_version );
    } // End enqueue()

}


Structural::instance();