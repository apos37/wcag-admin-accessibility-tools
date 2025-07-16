<?php 
/**
 * Modes
 */


/**
 * Define Namespaces
 */
namespace Apos37\WCAGAdminAccessibilityTools;
use Apos37\WCAGAdminAccessibilityTools\Integrations;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Initiate the class
 */
add_filter( 'init', function() {
    $visibility = sanitize_key( get_option( 'wcagaat_mode_visibility' ) );
    if ( ( $visibility === 'admins' && current_user_can( 'administrator' ) ) ||
         ( $visibility === 'logged-in' && is_user_logged_in() ) ||
         ( $visibility === 'everyone' ) ) {
        new Modes();
    }
} );


/**
 * The class.
 */
class Modes {

    /**
     * The key that is used to identify the ajax response
     *
     * @var string
     */
    public $ajax_key = 'wcagaat_modes';


    /**
     * Name of nonce used for ajax call
     *
     * @var string
     */
    public $nonce = 'wcagaat_modes_nonce';


    /**
     * The user meta key
     *
     * @var string
     */
    public $meta_key = 'wcagaat_mode';


    /**
	 * Constructor
	 */
	public function __construct() {

        // Add body classes
        add_filter( 'body_class', [ $this, 'body_class' ] );

        // Callback
        $mode_selector = sanitize_key( get_option( 'wcagaat_modes', 'float' ) );
        if ( $mode_selector == 'float' ) {
            add_action( 'wp_footer', [ $this, 'float' ] );
        } elseif ( $mode_selector == 'nav' ) {
            add_filter( 'wp_nav_menu_items', [ $this, 'nav' ], 10, 2 );
        }
        add_shortcode( 'wcagaat_modes', [ $this, 'shortcode' ] );

        // Ajax
        add_action( 'wp_ajax_' . $this->ajax_key, [ $this, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_' . $this->ajax_key, [ $this, 'ajax' ] );

        // Enqueue the script
        add_action( 'wp_enqueue_scripts', [ $this, 'script_enqueuer' ] );
        
	} // End __construct()


    /**
     * Get the modes
     *
     * @return array
     */
    public function modes() {
        $default = [ 'default' => [
            'label'  => __( 'Default', 'wcag-admin-accessibility-tools' ),
            'switch' => __( 'Switch to default mode', 'wcag-admin-accessibility-tools' ),
            'active' => __( 'Default mode activated', 'wcag-admin-accessibility-tools' ),
            'icon'   => 'f185', // fa-sun
        ] ];

        $modes = apply_filters( 'wcagaat_modes', [
            'dark' => [
                'label'  => __( 'Dark', 'wcag-admin-accessibility-tools' ),
                'switch' => __( 'Switch to dark mode', 'wcag-admin-accessibility-tools' ),
                'active' => __( 'Dark mode activated', 'wcag-admin-accessibility-tools' ),
                'icon'   => 'f186', // fa-moon
            ],
            // 'high-contrast' => [
            //     'label'  => __( 'High Contrast', 'wcag-admin-accessibility-tools' ),
            //     'switch' => __( 'Switch to high contrast mode', 'wcag-admin-accessibility-tools' ),
            //     'active' => __( 'High contrast mode activated', 'wcag-admin-accessibility-tools' ),
            //     'icon'   => 'f06a', // fa-circle-exclamation
            // ],
            'greyscale' => [
                'label'  => __( 'Greyscale', 'wcag-admin-accessibility-tools' ),
                'switch' => __( 'Switch to greyscale mode', 'wcag-admin-accessibility-tools' ),
                'active' => __( 'Greyscale mode activated', 'wcag-admin-accessibility-tools' ),
                'icon'   => 'f042', // fa-circle-half-stroke
            ],
        ] );

        $modes = $default + $modes;
        return $modes;
    } // End modes()


    /**
     * Get the mode icon
     *
     * @param string $mode
     * @return string
     */
    public function get_icon( $mode ) {
        $modes = $this->modes();

        $icon_value = isset( $modes[ $mode ][ 'icon' ] ) ? $modes[ $mode ][ 'icon' ] : '';
        $label      = isset( $modes[ $mode ][ 'label' ] ) ? $modes[ $mode ][ 'label' ] : $mode;
        $data_attr  = 'data-mode="' . esc_attr( $mode ) . '"';

        $classes = [ 'wcagaat-mode' ];

        // If no icon is defined, return label in span
        if ( ! $icon_value ) {
            return '<i class="' . implode( ' ', $classes ) . '" ' . $data_attr . '>' . esc_html( $label ) . '</i>';
        }

        // If full HTML is passed, inject class/data into the first tag
        if ( str_starts_with( trim( $icon_value ), '<' ) ) {
            return preg_replace(
                '/^<([a-z0-9]+)(\s|>)/i',
                '<$1 class="' . implode( ' ', $classes ) . '" ' . $data_attr . '$2',
                $icon_value,
                1
            );
        }

        $is_hex_code = preg_match( '/^[a-f0-9]{3,6}$/i', $icon_value );

        // Cornerstone-specific hex icon
        if ( ( new Integrations() )->is_cornerstone_active() && $is_hex_code ) {
            $classes[] = 'x-icon';
            $classes[] = 'fa';
            return '<i class="' . implode( ' ', $classes ) . '" ' . $data_attr . ' data-x-icon-s="&#x' . esc_attr( $icon_value ) . ';"></i>';
        }

        // Standard hex-based Font Awesome icon
        if ( $is_hex_code ) {
            $classes[] = 'fa';
            return '<i class="' . implode( ' ', $classes ) . '" ' . $data_attr . '>&#x' . esc_attr( $icon_value ) . ';</i>';
        }

        // Font Awesome class (e.g., fa-sun)
        if ( preg_match( '/^fa(-[a-z0-9\-]+)+$/i', $icon_value ) ) {
            $classes[] = 'fa';
            $classes[] = $icon_value;
            return '<i class="' . implode( ' ', $classes ) . '" ' . $data_attr . '></i>';
        }

        // Fallback to label
        return '<i class="' . implode( ' ', $classes ) . '" ' . $data_attr . '>' . esc_html( $label ) . '</i>';
    } // End get_icon()


    /**
     * Get the current user's mode
     *
     * @return string
     */
    public function get_user_mode() {
        // If logged in
        if ( $user_id = get_current_user_id() ) {
            $mode = sanitize_key( get_user_meta( $user_id, $this->meta_key, true ) );
            
        // Or else check their session
        } else {
            if ( session_status() !== PHP_SESSION_ACTIVE ) {
                session_start();
            }

            $session_key = $this->meta_key;
            $mode = isset( $_SESSION[ $session_key ] ) ? sanitize_key( wp_unslash( $_SESSION[ $session_key ] ) ) : '';
        }

        return array_key_exists( $mode, $this->modes() ) ? $mode : 'default';
    } // End get_user_mode()


    /**
     * Add body class
     *
     * @param array $classes
     * @return array
     */
    public function body_class( $classes ) {
        $classes[] = 'wcagaat-' . $this->get_user_mode() . '-mode';
        return $classes;
    } // End body_class()


    /**
     * Get the selector HTML
     *
     * @return string
     */
    public function selector( $type ) {
        $current_mode = $this->get_user_mode();
        $modes = $this->modes();

        if ( !isset( $modes[ $current_mode ] ) ) {
            $current_mode = 'default';
        }

        $icon_html = $this->get_icon( $current_mode );
        $active_label = $modes[ $current_mode ][ 'active' ]; // Screen reader announcement

        $next_mode_keys = array_keys( $modes );
        $current_index = array_search( $current_mode, $next_mode_keys );
        $next_index = ( $current_index + 1 ) % count( $next_mode_keys );
        $next_switch = $modes[ $next_mode_keys[ $next_index ] ][ 'switch' ]; // Action label

        ob_start();
        ?>
        <div id="wcagaat-mode-switch" data-type="<?php echo esc_attr( $type ); ?>" data-current="<?php echo esc_attr( $current_mode ); ?>">
            <button
                id="wcagaat-mode-toggle"
                aria-label="<?php echo esc_attr( $next_switch ); ?>"
                title="<?php echo esc_attr( $next_switch ); ?>">
                <?php echo wp_kses_post( $icon_html ); ?>
                <span class="screen-reader-text"><?php echo esc_html( $active_label ); ?></span>
            </button>
            <div id="wcagaat-mode-live" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>
        </div>
        <?php
        return ob_get_clean();
    } // End selector()

    
    /**
     * Add a floating selector
     */
    public function float() {
        echo wp_kses_post( $this->selector( 'float' ) );
    } // End float()


    /**
     * Inject mode selector into the primary nav menu
     *
     * @param string $items
     * @param object $args
     * @return string
     */
    public function nav( $items, $args ) {
        if ( isset( $args->theme_location ) && $args->theme_location === 'primary' ) {
            $items .= '<li class="menu-item wcagaat-mode-menu-item"><div class="wcagaat-nav-wrapper">' . $this->selector( 'nav' ) . '</div></li>';
        }
        return $items;
    } // End nav()


    /**
     * Add a shortcode selector
     *
     * @param array $atts
     * @return string
     */
    public function shortcode( $atts ) {
        $atts = shortcode_atts( [ 'type' => 'default' ], $atts );
        $type = strtolower( trim( $atts[ 'type' ] ) );

        $dropdown_types = [ 'select', 'dropdown', 'drop down' ];
        $current_mode   = $this->get_user_mode();
        $modes          = $this->modes();

        if ( in_array( $type, $dropdown_types, true ) ) {
            ob_start();
            ?>
            <select id="wcagaat-mode-dropdown" aria-label="<?php esc_attr_e( 'Select accessibility mode', 'wcag-admin-accessibility-tools' ); ?>">
                <?php foreach ( $modes as $key => $mode ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_mode, $key ); ?>>
                        <?php echo esc_html( $mode[ 'label' ] ?? $key ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
            return ob_get_clean();
        }

        return wp_kses_post( $this->selector( 'shortcode' ) );
    } // End shortcode()


    /**
     * Ajax call
     *
     * @return void
     */
    public function ajax() {
        // Verify nonce
        check_ajax_referer( $this->nonce, 'nonce' );

        // Sanitize and validate mode
        $mode = isset( $_REQUEST[ 'mode' ] ) ? sanitize_key( wp_unslash( $_REQUEST[ 'mode' ] ) ) : '';
        $available_modes = array_keys( $this->modes() );

        if ( !in_array( $mode, $available_modes, true ) ) {
            wp_send_json_error( [ 'message' => 'Invalid mode.' ] );
        }

        $user_id = get_current_user_id();

        if ( $user_id > 0 ) {
            // Logged-in user: update user meta
            $updated = update_user_meta( $user_id, $this->meta_key, $mode );
            if ( $updated ) {
                wp_send_json_success();
            } else {
                wp_send_json_error( [ 'message' => 'Could not update user mode.' ] );
            }

        } else {
            // Guest user: use PHP session
            if ( session_status() !== PHP_SESSION_ACTIVE ) {
                session_start();
            }

            $_SESSION[ $this->meta_key ] = $mode;
            wp_send_json_success();
        }

        wp_send_json_error( [ 'message' => 'Unhandled error.' ] );
    } // End ajax()


    /**
     * Enque the JavaScript
     *
     * @return void
     */
    public function script_enqueuer() {
        if ( is_admin() ) {
            return;
        }

        // Replace each mode's icon with the rendered HTML
        $modes = $this->modes();
        foreach ( $modes as $key => &$mode ) {
            $mode[ 'icon' ] = $this->get_icon( $key );
        }

        // JS
        $handle = 'wcagaat_modes_js';
        wp_register_script( $handle, WCAGAAT_JS_PATH . 'modes.js', [ 'jquery' ], WCAGAAT_SCRIPT_VERSION, true );
        wp_localize_script( $handle, 'wcagaat_modes', [ 
            'nonce'           => wp_create_nonce( $this->nonce ),
            'ajaxurl'         => admin_url( 'admin-ajax.php' ),
            'current_mode'    => $this->get_user_mode(),
            'modes'           => $modes,
            'light_mode_logo' => sanitize_url( get_option( 'wcagaat_light_logo' ) ),
            'dark_mode_logo'  => sanitize_url( get_option( 'wcagaat_dark_logo' ) ),
        ] );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( $handle );
        
        // CSS
        wp_enqueue_style( 'wcagaat_modes_css', WCAGAAT_CSS_PATH . 'modes.css', [], WCAGAAT_SCRIPT_VERSION );
        wp_enqueue_style( 'wcagaat_modes_greyscale_css', WCAGAAT_CSS_PATH . 'mode-greyscale.css', [], WCAGAAT_SCRIPT_VERSION );
        wp_enqueue_style( 'wcagaat_modes_high_contrast_css', WCAGAAT_CSS_PATH . 'mode-high-contrast.css', [], WCAGAAT_SCRIPT_VERSION );
        wp_enqueue_style( 'wcagaat_modes_dark_css', WCAGAAT_CSS_PATH . 'mode-dark.css', [], WCAGAAT_SCRIPT_VERSION );

        // Integration-specific dark mode CSS
        $INTEGRATIONS = new Integrations();
        foreach ( $INTEGRATIONS->identifiers as $key => $plugin ) {
            if ( isset( $plugin[ 'css' ] ) && $plugin[ 'css' ] && !empty( $plugin[ 'short' ] ) ) {
                $handle = 'wcagaat_modes_dark_css_' . $plugin[ 'short' ];
                $src    = WCAGAAT_CSS_PATH . 'mode-dark-' . $plugin[ 'short' ] . '.css';
                wp_enqueue_style( $handle, $src, [], WCAGAAT_SCRIPT_VERSION );
            }
        }

        // Integration-specific nav bar
        if ( $INTEGRATIONS->is_cornerstone_active() ) {
            wp_enqueue_style( 'wcagaat_modes_nav_css_cs', WCAGAAT_CSS_PATH . 'mode-nav-cs.css', [], WCAGAAT_SCRIPT_VERSION );
        }
        
    } // End script_enqueuer()
    
}