<?php 
/**
 * Admin bar
 */


/**
 * Define Namespaces
 */
namespace Apos37\WCAGAdminAccessibilityTools;
use Apos37\WCAGAdminAccessibilityTools\Settings;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
	(new AdminBar())->init();
} );


/**
 * The class
 */
class AdminBar {

    /**
     * The dashicon for the admin bar menu
     *
     * @var string
     */
    public $dashicon = 'dashicons-universal-access';

    
    /**
     * Nonce
     *
     * @var string
     */
    private $nonce_alt_text = 'media_library_alt_text';


    /**
     * Load on init
     */
    public function init() {
        
        // Add admin bar menu button
		add_action( 'admin_bar_menu', [ $this, 'menu' ], 100 );

        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

    } // End init()


    /**
     * Add a button to the admin bar
     * 
     * @return void
     */
    public function menu( $wp_admin_bar ) {
        if ( !current_user_can( 'manage_options' ) || is_admin() ) {
            return;
        }

        // The main toolbar menu item
        $wp_admin_bar->add_node( [
            'id'    => 'wcagaat',
            'title' => '<span class="ab-icon dashicons ' . esc_attr( $this->dashicon ) . '" title="' . esc_attr( WCAGAAT_NAME ) . '"></span><span class="ab-label">' . __( 'A11y Tools', 'wcag-admin-accessibility-tools' ) . '</span>',
            'href'  => false,
        ] );

        // AA or AAA for color contrast
        $aa_or_aaa = filter_var( get_option( 'wcagaat_contrast_aaa' ), FILTER_VALIDATE_BOOLEAN ) ? 'AAA' : 'AA';

        // The tools in the dropdown
        $tools = [
            [
                'key'   => 'alt-text',
                'label' => __( 'Missing Alt Text', 'wcag-admin-accessibility-tools' )
            ],
            [
                'key'   => 'contrast',
                // translators: %s is the WCAG level (AA or AAA)
                'label' => sprintf( __( 'Poor Color Contrast for %s', 'wcag-admin-accessibility-tools' ), $aa_or_aaa )
            ],
            [
                'key'   => 'vague-link-text',
                'label' => __( 'Vague Link Text', 'wcag-admin-accessibility-tools' )
            ],
            [
                'key'   => 'heading-hierarchy',
                'label' => __( 'Improper Heading Hierarchy', 'wcag-admin-accessibility-tools' )
            ],
            [
                'key'   => 'underline-links',
                'label' => __( 'Links Missing Underlines', 'wcag-admin-accessibility-tools' )
            ],
        ];
        
        foreach ( $tools as $tool ) {
            $wp_admin_bar->add_node( [
                'id'     => 'wcagaat_' . $tool[ 'key' ],
                'parent' => 'wcagaat',
                'title'  => '<label><input type="checkbox" class="wcagaat-toggle" data-tool="' . $tool[ 'key' ] . '"> ' . $tool[ 'label' ] . ' <span class="wcagaat-count" data-tool="' . $tool[ 'key' ] . '"></span></label>',
            ] );
        }
    } // End menu()


    /**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts() {
        if ( !current_user_can( 'administrator' ) || is_admin() ) {
            return;
        }

		$handle = 'wcagaat_admin_bar';
        wp_enqueue_script( 'jquery' );
		wp_enqueue_script( $handle, WCAGAAT_JS_PATH . 'admin-bar.js', [ 'jquery' ], WCAGAAT_SCRIPT_VERSION, true );
		wp_localize_script( $handle, 'admin_bar', [
            'ajaxurl'         => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( $this->nonce_alt_text ),
            'doing_aaa'       => filter_var( get_option( 'wcagaat_contrast_aaa' ), FILTER_VALIDATE_BOOLEAN ),
            'vague_link_text' => sanitize_textarea_field( get_option( 'wcagaat_meaningful_link_texts', (new Settings())->vague_link_phrases ) ),
            'text'            => [
                'edit'    => __( 'Edit', 'wcag-admin-accessibility-tools' ),
                'update'  => __( 'Update', 'wcag-admin-accessibility-tools' ),
                'missing' => __( 'Missing Alt Text', 'wcag-admin-accessibility-tools' )
            ]
        ] );
		wp_enqueue_style( WCAGAAT_TEXTDOMAIN . '-admin-bar', WCAGAAT_CSS_PATH . 'admin-bar.css', [], WCAGAAT_SCRIPT_VERSION );
    } // End enqueue_scripts()
}
