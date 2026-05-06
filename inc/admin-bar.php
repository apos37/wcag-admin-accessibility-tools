<?php 
/**
 * Admin bar
 */

namespace PluginRx\WCAGAdminAccessibilityTools;

if ( ! defined( 'ABSPATH' ) ) exit;

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
    // private $nonce = 'admin_bar_nonce';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?AdminBar $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Load on init
     */
    public function __construct() {
		add_action( 'admin_bar_menu', [ $this, 'menu' ], 100 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    } // End __construct()


    /**
     * Add a button to the admin bar
     * 
     * @param \WP_Admin_Bar $wp_admin_bar The admin bar object
     * @return void
     */
    public function menu( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) || is_admin() || ! filter_var( Settings::get( 'admin_bar' ), FILTER_VALIDATE_BOOLEAN ) ) {
            return;
        }

        // The main toolbar menu item
        $wp_admin_bar->add_node( [
            'id'    => 'wcagaat',
            'title' => '<span class="ab-icon dashicons ' . esc_attr( $this->dashicon ) . '" title="' . esc_attr( Bootstrap::name() ) . '"></span><span class="ab-label">' . __( 'A11y Tools ', 'wcag-admin-accessibility-tools' ) . '</span>',
            'href'  => false,
        ] );

        // AA or AAA for color contrast
        $aa_or_aaa = filter_var( Settings::get( 'contrast_aaa' ), FILTER_VALIDATE_BOOLEAN ) ? 'AAA' : 'AA';

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
        if ( ! current_user_can( 'administrator' ) || is_admin() || ! filter_var( Settings::get( 'admin_bar' ), FILTER_VALIDATE_BOOLEAN ) ) {
            return;
        }

		$handle = 'wcagaat_admin_bar';
        wp_enqueue_script( 'jquery' );
		wp_enqueue_script( $handle, Bootstrap::url( 'inc/js/admin-bar.js' ), [ 'jquery' ], Bootstrap::script_version(), true );
		wp_localize_script( $handle, $handle, [
            // 'ajaxurl'         => admin_url( 'admin-ajax.php' ),
            // 'nonce'           => wp_create_nonce( $this->nonce ),
            'doing_aaa'       => filter_var( Settings::get( 'contrast_aaa' ), FILTER_VALIDATE_BOOLEAN ),
            'doing_console'   => filter_var( Settings::get( 'admin_bar_console' ), FILTER_VALIDATE_BOOLEAN ),
            'vague_link_text' => sanitize_textarea_field( Settings::get( 'meaningful_link_texts', Settings::$vague_link_phrases ) ),
            'text'            => [
                'edit'    => __( 'Edit', 'wcag-admin-accessibility-tools' ),
                'update'  => __( 'Update', 'wcag-admin-accessibility-tools' ),
                'missing' => __( 'Missing Alt Text', 'wcag-admin-accessibility-tools' ),
                'total'   => __( 'Total Issues Found', 'wcag-admin-accessibility-tools' ),
            ]
        ] );

		wp_enqueue_style( Bootstrap::textdomain() . '-admin-bar', Bootstrap::url( 'inc/css/admin-bar.css' ), [], Bootstrap::script_version() );
    } // End enqueue_scripts()
}


AdminBar::instance();