<?php 
/**
 * Form Enhancements
 */


/**
 * Define Namespaces
 */
namespace Apos37\WCAGAdminAccessibilityTools;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Initiate the class
 */
new Forms();


/**
 * The class.
 */
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
	 * Constructor
	 */
	public function __construct() {

        // Add a password eye to password protected pages
        $this->add_eye_to_password_protected_pages = filter_var( get_option( $this->protected_password_eye_option, false ), FILTER_VALIDATE_BOOLEAN );
        if ( $this->add_eye_to_password_protected_pages ) {
            add_action( 'the_password_form', [ $this, 'add_password_eye_to_form' ] );
        }

        // Enqueue scripts and styles
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
        
        if ( $this->add_eye_to_password_protected_pages ) {
            $handle = 'wcagaat-protected-password-eye';
            wp_enqueue_style( 'dashicons' );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( $handle, WCAGAAT_JS_PATH . 'protected-password-eye.js', [ 'jquery' ], WCAGAAT_SCRIPT_VERSION, true );
            wp_enqueue_style( $handle, WCAGAAT_CSS_PATH . 'protected-password-eye.css', [], WCAGAAT_SCRIPT_VERSION );
        }
    } // End enqueue()

}