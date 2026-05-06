<?php 
/**
 * Form Enhancements
 */

namespace PluginRx\WCAGAdminAccessibilityTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Forms {


    /**
     * Option to check if a skip link is present
     *
     * @var string
     */
    public $protected_password_eye_option = 'wcagaat_protected_password_eye';


    /**
     * Whether to add the password eye to password protected pages
     *
     * @var bool
     */
    private $add_eye_to_password_protected_pages = false;


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Forms $instance = null;


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
        add_action( 'the_password_form', [ $this, 'add_password_eye_to_form' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	} // End __construct()


    /**
     * Adds the password eye icon HTML to the password protection form.
     *
     * @param string $output The default password form HTML.
     * @return string Modified password form HTML.
     */
    public function add_password_eye_to_form( $output ) {
        $pattern = '/(<input[^>]*?name=["\']post_password["\'][^>]*?>)/i';

        if ( preg_match( $pattern, $output, $matches ) ) {
            $input_field = $matches[1];

            $eye_icon_html = '<span class="wcagaat-password-toggle">
                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
            </span>';

            $output = str_replace( $input_field, $input_field . $eye_icon_html, $output );
        }

        return $output;
    } // End add_password_eye_to_form()


    /**
     * Enqueue frontend assets
     */
    public function enqueue() {
        if ( is_admin() ) {
            return;
        }
        
        $handle = 'wcagaat-protected-password-eye';
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( $handle, Bootstrap::url( 'inc/js/protected-password-eye.js' ), [ 'jquery' ], Bootstrap::script_version(), true );
        wp_enqueue_style( $handle, Bootstrap::url( 'inc/css/protected-password-eye.css' ), [], Bootstrap::script_version() );
    } // End enqueue()

}


if ( Settings::get( 'protected_password_eye', false ) ) {
    Forms::instance();
}