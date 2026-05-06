<?php 
/**
 * User Assistant
 */

namespace PluginRx\WCAGAdminAccessibilityTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Assistant {


    /**
     * The user meta key
     *
     * @var string
     */
    public const USER_META_KEY = 'wcagaat_user_prefs';


    /**
     * Default user preferences
     *
     * @var array
     */
    public const USER_PREFS_DEFAULTS = [
        'text_resizer'  => 0,
        'readable_font' => false,
        'mode'          => 'default',
    ];


    /**
     * Get the modes
     *
     * @return array
     */
    public function modes() {
        $modes = [ 
            'default' => [
                'label'  => __( 'Default', 'wcag-admin-accessibility-tools' ),
                'switch' => __( 'Switch to default mode', 'wcag-admin-accessibility-tools' ),
                'active' => __( 'Default mode activated', 'wcag-admin-accessibility-tools' ),
                'icon'   => sanitize_text_field( Settings::get( 'mode_icon_default' ) ),
            ],
            'dark' => [
                'label'  => __( 'Dark', 'wcag-admin-accessibility-tools' ),
                'switch' => __( 'Switch to dark mode', 'wcag-admin-accessibility-tools' ),
                'active' => __( 'Dark mode activated', 'wcag-admin-accessibility-tools' ),
                'icon'   => sanitize_text_field( Settings::get( 'mode_icon_dark' ) ),
            ],
            'greyscale' => [
                'label'  => __( 'Greyscale', 'wcag-admin-accessibility-tools' ),
                'switch' => __( 'Switch to greyscale mode', 'wcag-admin-accessibility-tools' ),
                'active' => __( 'Greyscale mode activated', 'wcag-admin-accessibility-tools' ),
                'icon'   => sanitize_text_field( Settings::get( 'mode_icon_greyscale' ) ),
            ],
        ];

        return $modes;
    } // End modes()


    /**
     * Name of nonce used for ajax call
     *
     * @var string
     */
    public $nonce = 'wcagaat_assistant_nonce';


    /**
     * Location
     *
     * @var string
     */
    private $location;


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Assistant $instance = null;


    /**
     * Internal cache for user preferences to minimize get_user_meta or session access
     */
    private static ?array $cached_user_prefs = null;


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

        if ( ! is_admin() ) {
            
            // Add body classes
            add_filter( 'body_class', [ $this, 'body_class' ] );

            // Location of the assistant
            $this->location = Settings::get( 'assistant_location' );
            switch ( $this->location ) {
                case 'float-left':
                case 'float-right':
                    add_action( 'wp_footer', [ $this, 'float' ] );
                    break;
                case 'nav':
                    add_filter( 'wp_nav_menu_items', [ $this, 'nav' ], 10, 2 );
                    add_filter( 'render_block_core/navigation', [ $this, 'nav_block_theme' ], 10, 2 );
                    add_filter( 'render_block_core/page-list', [ $this, 'nav_block_theme' ], 10, 2 );
                    break;
                case 'shortcode':
                    add_shortcode( 'wcagaat_modes', [ $this, 'shortcode' ] ); // Deprecated - use [wcagaat_assistant] instead
                    add_shortcode( 'wcagaat_assistant', [ $this, 'shortcode' ] );
                    break;
            }
            
            // Enqueue the script
            add_action( 'wp_enqueue_scripts', [ $this, 'script_enqueuer' ] );
        }

        // Ajax
        add_action( 'wp_ajax_wcagaat_update_user_prefs', [ $this, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_wcagaat_update_user_prefs', [ $this, 'ajax' ] );

	} // End __construct()


    /**
     * Get active tools
     *
     * @return array
     */
    private static function active_tools() : array {
        $tools = [];

        if ( Settings::get( 'tool_text_resizer' ) ) {
            $tools[] = 'text_resizer';
        }

        if ( Settings::get( 'tool_readable_font' ) ) {
            $tools[] = 'readable_font';
        }

        if ( Settings::get( 'tool_modes' ) ) {
            $tools[] = 'modes';
        }

        return $tools;
    } // End active_tools()
    

    /**
     * Check if a specific tool is enabled based on settings
     *
     * @param string $tool
     * @return bool
     */
    private static function is_tool_enabled( $tool ) : bool {
        return match ( $tool ) {
            'text_resizer'  => in_array( 'text_resizer', self::active_tools() ),
            'readable_font' => in_array( 'readable_font', self::active_tools() ),
            'modes'         => in_array( 'modes', self::active_tools() ),
            default         => false,
        };
    } // End is_tool_enabled()


    /**
     * Get user-specific preferences with local caching
     *
     * @param string $key     The specific pref (e.g., 'font_size')
     * @param mixed  $default Default value if pref isn't set
     * @return mixed
     */
    public static function get_user_pref( $key, $default = null ) : mixed {
        if ( null === self::$cached_user_prefs ) {

            if ( $user_id = get_current_user_id() ) {
                // Logged in: Use User Meta
                $saved = get_user_meta( $user_id, self::USER_META_KEY, true );
            } else {
                // Guest: Use PHP Session
                if ( session_status() !== PHP_SESSION_ACTIVE && ! headers_sent() ) {
                    session_start();
                }

                $saved = isset( $_SESSION[ self::USER_META_KEY ] ) 
                    ? wp_unslash( $_SESSION[ self::USER_META_KEY ] ) 
                    : [];
            }

            self::$cached_user_prefs = is_array( $saved ) ? $saved : [];
        }

        if ( array_key_exists( $key, self::$cached_user_prefs ) ) {
            return self::$cached_user_prefs[ $key ];
        }

        if ( $default !== null ) {
            return $default;
        }

        return self::USER_PREFS_DEFAULTS[ $key ] ?? null;
    } // End get_user_pref()


    /**
     * Add body class
     *
     * @param array $classes
     * @return array
     */
    public function body_class( $classes ) {
        if ( self::is_tool_enabled( 'readable_font' ) && self::get_user_pref( 'readable_font' ) ) {
            $classes[] = 'wcagaat-readable-font';
        }

        if ( self::is_tool_enabled( 'modes' ) ) {
            $classes[] = 'wcagaat-' . self::get_user_pref( 'mode' ) . '-mode';
        }

        return $classes;
    } // End body_class()


    /**
     * Get the allowed HTML for the assistant (for wp_kses)
     *
     * @return array
     */
    private function get_assistant_allowed_html() {
        return array_merge( wp_kses_allowed_html( 'post' ), [
            'div'    => [
                'id'            => true,
                'class'         => true,
                'data-location' => true,
                'data-current'  => true,
                'hidden'        => true,
            ],
            'button' => [
                'id'            => true,
                'class'         => true,
                'aria-label'    => true,
                'aria-expanded' => true,
                'aria-controls' => true,
                'data-size'     => true,
                'data-state'    => true,
            ],
            'select' => [
                'id'    => true,
                'class' => true,
            ],
            'option' => [
                'value'    => true,
                'selected' => true,
            ],
            'header' => [
                'class' => true,
            ],
            'span'   => [
                'class' => true,
                'id'    => true,
                'aria-hidden' => true,
            ],
            'i'      => [
                'class' => true,
            ],
        ] );
    } // End get_assistant_allowed_html()


    /**
     * Get the assistant HTML
     *
     * @return string
     */
    public function assistant() {
        ob_start();
        echo '<div id="wcagaat-assistant-container" data-location="' . esc_attr( $this->location ) . '">';

            // $prefs = [];
            // foreach ( self::USER_PREFS_DEFAULTS as $key => $def ) {
            //     $prefs[ $key ] = self::get_user_pref( $key );
            // }
            // dpr( $prefs );
            
            // 1. The Trigger Button (FAB)
            echo '<button id="wcagaat-assistant-trigger" aria-label="' . esc_attr__( 'Open Accessibility Menu', 'wcag-admin-accessibility-tools' ) . '" aria-expanded="false" aria-controls="wcagaat-assistant-panel">';
                echo '<i class="fa-solid fa-universal-access"></i>';
            echo '</button>';

            // 2. The Panel (Hidden by default)
            echo '<div id="wcagaat-assistant-panel" hidden>';
                echo '<header class="wcagaat-panel-header"><strong>' . esc_html__( 'Accessibility Assistant', 'wcag-admin-accessibility-tools' ) . '</strong></header>';
                
                if ( self::is_tool_enabled( 'text_resizer' ) ) {
                    $level = (int) self::get_user_pref( 'text_resizer' );
                    $percentage = 100 + ( $level * 10 );
                    echo '<div class="wcagaat-tool wcagaat-tool-text-resizer">
                        <span class="wcagaat-tool-label">' . esc_html__( 'Text Size', 'wcag-admin-accessibility-tools' ) . ' (<span class="wcagaat-text-resizer-percent">' . esc_attr( $percentage ) . '%</span>)</span>
                        <div class="wcagaat-button-group">
                            <button id="wcagaat-text-resizer-smaller" class="wcagaat-text-resizer-button" data-size="smaller" aria-label="' . esc_attr__( 'Smaller', 'wcag-admin-accessibility-tools' ) . '">-</button>
                            <button id="wcagaat-text-resizer-larger" class="wcagaat-text-resizer-button" data-size="larger" aria-label="' . esc_attr__( 'Larger', 'wcag-admin-accessibility-tools' ) . '">+</button>
                        </div>
                    </div>';
                }

                if ( self::is_tool_enabled( 'readable_font' ) ) {
                    $readable_font_state = self::get_user_pref( 'readable_font' ) ? 'on' : 'off';
                    $readable_font_label = self::get_user_pref( 'readable_font' ) ? __( 'On', 'wcag-admin-accessibility-tools' ) : __( 'Off', 'wcag-admin-accessibility-tools' );
                    echo '<div class="wcagaat-tool wcagaat-tool-readable-font">
                        <span class="wcagaat-tool-label">' . esc_html__( 'Readable Font', 'wcag-admin-accessibility-tools' ) . '</span>
                        <button id="wcagaat-readable-font-toggle" data-state="' . esc_attr( $readable_font_state ) . '">' . esc_html( $readable_font_label ) . '</button>
                    </div>';
                }

                if ( self::is_tool_enabled( 'modes' ) ) {
                    echo '<div class="wcagaat-tool wcagaat-tool-modes">
                        <span class="wcagaat-tool-label">' . esc_html__( 'Display Mode', 'wcag-admin-accessibility-tools' ) . '</span>
                        <select id="wcagaat-mode-dropdown">
                            <option value="default" ' . selected( self::get_user_pref( 'mode' ), 'default', false ) . '>' . esc_html__( 'Default', 'wcag-admin-accessibility-tools' ) . '</option>
                            <option value="dark" ' . selected( self::get_user_pref( 'mode' ), 'dark', false ) . '>' . esc_html__( 'Dark Mode', 'wcag-admin-accessibility-tools' ) . '</option>
                            <option value="greyscale" ' . selected( self::get_user_pref( 'mode' ), 'greyscale', false ) . '>' . esc_html__( 'Greyscale', 'wcag-admin-accessibility-tools' ) . '</option>
                        </select>
                    </div>';
                }

                // 3. The Reset Button
                echo '<a href="#" id="wcagaat-assistant-reset" aria-expanded="false" aria-controls="wcagaat-assistant-panel">' . esc_html__( 'Reset', 'wcag-admin-accessibility-tools' ) . '</a>';
                
            echo '</div>'; // End Panel

        echo '</div>'; // End Container
        return ob_get_clean();
    } // End assistant()


    /**
     * Get the text resizer HTML
     *
     * @param string $type
     * @return string
     */
    public function solo_text_resizer( $type ) {
        $level = (int) self::get_user_pref( 'text_resizer' );
        $percentage = 100 + ( $level * 10 );

        ob_start();
        ?>
        <div id="wcagaat-text-resizer-solo" class="wcagaat-solo-tool" data-type="<?php echo esc_attr( $type ); ?>" data-location="<?php echo esc_attr( $this->location ); ?>" data-current="<?php echo esc_attr( $level ); ?>">
            <div class="wcagaat-tooltip">
                <?php esc_html_e( 'Text Size', 'wcag-admin-accessibility-tools' ); ?>: 
                <span class="wcagaat-text-resizer-percent"><?php echo esc_html( $percentage ); ?>%</span>
            </div>
            <div class="wcagaat-button-group">
                <button id="wcagaat-text-resizer-smaller" class="wcagaat-text-resizer-button" data-size="smaller" aria-label="<?php esc_attr_e( 'Smaller', 'wcag-admin-accessibility-tools' ); ?>">-</button>
                <span id="wcagaat-text-resizer-divider" aria-hidden="true"></span>
                <button id="wcagaat-text-resizer-larger" class="wcagaat-text-resizer-button" data-size="larger" aria-label="<?php esc_attr_e( 'Larger', 'wcag-admin-accessibility-tools' ); ?>">+</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    } // End solo_text_resizer()


    /**
     * Get the readable font toggle HTML
     *
     * @param string $type
     * @return string
     */
    public function solo_readable_font( $type ) {
        $is_active = (bool) self::get_user_pref( 'readable_font' );
        $state     = $is_active ? 'on' : 'off';
        $label     = $is_active ? __( 'On', 'wcag-admin-accessibility-tools' ) : __( 'Off', 'wcag-admin-accessibility-tools' );
        
        ob_start();
        ?>
        <div id="wcagaat-readable-font-solo" class="wcagaat-solo-tool" data-type="<?php echo esc_attr( $type ); ?>" data-location="<?php echo esc_attr( $this->location ); ?>" data-current="<?php echo esc_attr( $state ); ?>">
            <div class="wcagaat-tooltip">
                <?php esc_html_e( 'Readable Font', 'wcag-admin-accessibility-tools' ); ?>: 
                <span class="wcagaat-readable-font-state"><?php echo esc_html( $label ); ?></span>
            </div>
            <button 
                id="wcagaat-readable-font-toggle" 
                data-state="<?php echo esc_attr( $state ); ?>" 
                aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
                aria-label="<?php esc_attr_e( 'Toggle Readable Font', 'wcag-admin-accessibility-tools' ); ?>">
                <i class="fa-solid fa-font wcagaat-font-icon"></i>
            </button>
        </div>
        <?php
        return ob_get_clean();
    } // End solo_readable_font()


    /**
     * Get the mode icon
     *
     * @param string $mode
     * @return string
     */
    public function get_mode_selector_icon( $mode ) {
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

        // Font Awesome classes (e.g., fa-solid fa-sun)
        if ( ! empty( $icon_value ) && ! $is_hex_code && str_contains( $icon_value, 'fa-' ) ) {
            $sanitized_icons = trim( preg_replace( '/[^a-zA-Z0-9\s\-]/', '', $icon_value ) );
            $classes[] = 'fa';
            $classes[] = $sanitized_icons;
            return '<i class="' . implode( ' ', $classes ) . '" ' . $data_attr . '></i>';
        }

        // Fallback to label
        return '<i class="' . implode( ' ', $classes ) . '" ' . $data_attr . '>' . esc_html( $label ) . '</i>';
    } // End get_mode_selector_icon()


    /**
     * Get the selector HTML
     *
     * @param string $type
     * @return string
     */
    public function solo_modes( $type ) {
        $current_mode = self::get_user_pref( 'mode' );
        $modes = $this->modes();

        if ( ! isset( $modes[ $current_mode ] ) ) {
            $current_mode = 'default';
        }

        $icon_html = $this->get_mode_selector_icon( $current_mode );
        $active_label = $modes[ $current_mode ][ 'active' ]; // Screen reader announcement

        $next_mode_keys = array_keys( $modes );
        $current_index = array_search( $current_mode, $next_mode_keys );
        $next_index = ( $current_index + 1 ) % count( $next_mode_keys );
        $next_switch = $modes[ $next_mode_keys[ $next_index ] ][ 'switch' ]; // Action label

        ob_start();
        echo '<div id="wcagaat-mode-switch" data-type="' . esc_attr( $type ) . '" data-location="' . esc_attr( $this->location ) . '" data-current="' . esc_attr( $current_mode ) . '">
            <button
                id="wcagaat-mode-toggle"
                aria-label="' . esc_attr( $next_switch ) . '"
                title="' . esc_attr( $next_switch ) . '">
                ' . wp_kses_post( $icon_html ) . '
                <span class="screen-reader-text">' . esc_html( $active_label ) . '</span>
            </button>
            <div id="wcagaat-mode-live" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>
        </div>';
        return ob_get_clean();
    } // End solo_modes()

    
    /**
     * Add a floating selector
     */
    public function float() {
        if ( count( self::active_tools() ) > 1 ) {
            echo wp_kses( $this->assistant(), $this->get_assistant_allowed_html() );
        } else {
            $tool = self::active_tools()[0] ?? null;
            if ( method_exists( $this, 'solo_' . $tool ) ) {
                $method = 'solo_' . $tool;
                echo wp_kses( $this->$method(  'float' ), $this->get_assistant_allowed_html() );
            }
        }
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
            
            if ( count( self::active_tools() ) > 1 ) {
                $items .= '<li class="menu-item wcagaat-assistant-menu-item"><div class="wcagaat-nav-wrapper">' . $this->assistant() . '</div></li>';
            } else {
                $tool = self::active_tools()[0] ?? null;
                if ( method_exists( $this, 'solo_' . $tool ) ) {
                    $method = 'solo_' . $tool;
                    $items .= '<li class="menu-item wcagaat-assistant-menu-item"><div class="wcagaat-nav-wrapper">' . $this->$method(  'nav' ) . '</div></li>';
                }
            }
        }
        return $items;
    } // End nav()


    /**
     * Inject the assistant into the Block Navigation menu
     *
     * @param string $block_content
     * @param array  $block         
     * @return string
     */
    public function nav_block_theme( $block_content, $block ) {        
        $assistant_html = '';

        if ( count( self::active_tools() ) > 1 ) {
            $assistant_html = '<li class="wp-block-navigation-item wcagaat-assistant-menu-item"><div class="wcagaat-nav-wrapper">' . $this->assistant() . '</div></li>';
        } else {
            $tool = self::active_tools()[0] ?? null;
            if ( method_exists( $this, 'solo_' . $tool ) ) {
                $method = 'solo_' . $tool;
                $assistant_html = '<li class="wp-block-navigation-item wcagaat-assistant-menu-item"><div class="wcagaat-nav-wrapper">' . $this->$method( 'nav' ) . '</div></li>';
            }
        }

        // Append to the end of the list (before the closing </ul>)
        if ( ! empty( $assistant_html ) ) {
            $block_content = preg_replace( '/<\/ul>$/', $assistant_html . '</ul>', trim( $block_content ) );
        }

        return $block_content;
    } // End nav_block_theme()


    /**
     * Add a shortcode selector
     * USAGE: [wcagaat_assistant] - Deprecated: [wcagaat_modes]
     *
     * @param array  $atts
     * @param string $content
     * @param string $tag
     * @return string
     */
    public function shortcode( $atts = [], $content = null, $tag = '' ) {
        if ( 'wcagaat_modes' === $tag ) {
            _doing_it_wrong(
                $tag,
                __( 'The [wcagaat_modes] shortcode is deprecated. Please use [wcagaat_assistant] instead.', 'wcag-admin-accessibility-tools' ),
                '1.0.5'
            );

            // Only update if it's not already set to avoid redundant DB writes
            if ( ! get_option( 'wcagaat_deprecated_shortcode_used' ) ) {
                update_option( 'wcagaat_deprecated_shortcode_used', true );
            }
        }
        
        if ( count( self::active_tools() ) > 1 ) {
            return $this->assistant();
        } else {
            $tool = self::active_tools()[0] ?? null;
            if ( method_exists( $this, 'solo_' . $tool ) ) {
                $method = 'solo_' . $tool;
                return $this->$method(  'shortcode' );
            }
        }
    } // End shortcode()


    /**
     * Enque the JavaScript
     *
     * @return void
     */
    public function script_enqueuer() {
        wp_enqueue_script( 'jquery' );

        // Get the modes
        $modes = [];
        $has_mode = false;
        if ( self::is_tool_enabled( 'modes' ) ) {
            $modes = $this->modes();
            foreach ( $modes as $key => &$mode ) {
                $mode[ 'icon' ] = $this->get_mode_selector_icon( $key );
            }
            $has_mode = self::get_user_pref( 'mode', '__NOT_SET__' ) !== '__NOT_SET__';
        }
    
        $current_prefs = [];
        foreach ( self::USER_PREFS_DEFAULTS as $key => $def ) {
            $current_prefs[ $key ] = self::get_user_pref( $key );
        }        

        // JS
        $handle = 'wcagaat_assistant_js';
        wp_register_script( $handle, Bootstrap::url( 'inc/js/assistant.js' ), [ 'jquery' ], Bootstrap::script_version(), true );
        wp_localize_script( $handle, 'wcagaat_assistant', [ 
            'nonce'           => wp_create_nonce( $this->nonce ),
            'ajaxurl'         => admin_url( 'admin-ajax.php' ),
            'active_tools'    => self::active_tools(),
            'location'        => $this->location,
            'modes'           => $modes,
            'light_mode_logo' => sanitize_url( Settings::get( 'default_logo' ) ),
            'dark_mode_logo'  => sanitize_url( Settings::get( 'dark_logo' ) ),
            'current_prefs'   => $current_prefs,
            'has_mode'        => $has_mode,
            'text'      => [
                'reset'             => __( 'Reset to default', 'wcag-admin-accessibility-tools' ),
                'resetting'         => __( 'Resetting', 'wcag-admin-accessibility-tools' ),
                'reset_success'     => __( 'Reset successful', 'wcag-admin-accessibility-tools' ),
                'reset_fail'        => __( 'Reset failed', 'wcag-admin-accessibility-tools' ),
                'on'                => __( 'On', 'wcag-admin-accessibility-tools' ),
                'off'               => __( 'Off', 'wcag-admin-accessibility-tools' ),
                'yes'               => __( 'Yes', 'wcag-admin-accessibility-tools' ),
                'no'                => __( 'No', 'wcag-admin-accessibility-tools' ),
                'enable_dark_mode'  => __( 'Enable Dark Mode?', 'wcag-admin-accessibility-tools' ),
                'suggest_dark_mode' => __( 'Your device is using dark mode. Would you like to enable it on this site?', 'wcag-admin-accessibility-tools' ),
            ],
        ] );
        wp_enqueue_script( $handle );

        // Font Awesome for icons
        $common_handles = [ 'font-awesome', 'fontawesome', 'fa-css', 'fa6-css', 'elementor-icons-fa-solid', 'x-font-awesome' ];

        $is_enqueued = false;
        foreach ( $common_handles as $handle ) {
            if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
                $is_enqueued = true;
                break;
            }
        }

        if ( ! $is_enqueued ) {
            wp_enqueue_style( 
                'wcagaat-font-awesome', 
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', 
                [], 
                '6.5.1'
            );
        }

        // Assistant styles
        if ( count( self::active_tools() ) > 1 ) {
            wp_enqueue_style( 'wcagaat_assistant_css', Bootstrap::url( 'inc/css/assistant.css' ), [], Bootstrap::script_version() );
        } elseif ( self::is_tool_enabled( 'text_resizer' ) ) {
            wp_enqueue_style( 'wcagaat_text_resizer_css', Bootstrap::url( 'inc/css/text-resizer.css' ), [], Bootstrap::script_version() );
        } elseif ( self::is_tool_enabled( 'readable_font' ) ) {
            wp_enqueue_style( 'wcagaat_readable_font_css', Bootstrap::url( 'inc/css/readable-font.css' ), [], Bootstrap::script_version() );
        }

        if ( 'nav' === $this->location) {
            wp_enqueue_style( 'wcagaat-assistant-nav', Bootstrap::url( 'inc/css/assistant-nav.css' ), [], Bootstrap::script_version() );
        }

        if ( 'shortcode' === $this->location ) {
            wp_enqueue_style( 'wcagaat-assistant-shortcode', Bootstrap::url( 'inc/css/assistant-shortcode.css' ), [], Bootstrap::script_version() );
        }

        // Modes Only
        if ( ! self::is_tool_enabled( 'modes' ) ) {
            return;
        }

        // Base stylesheets for all modes
        wp_enqueue_style( 'wcagaat_modes_css', Bootstrap::url( 'inc/css/modes.css' ), [], Bootstrap::script_version() );
        wp_enqueue_style( 'wcagaat_modes_greyscale_css', Bootstrap::url( 'inc/css/mode-greyscale.css' ), [], Bootstrap::script_version() );

        // Dark mode stylesheets
        if ( filter_var( get_option( 'wcagaat_disable_dark_mode_stylesheets', FALSE ), FILTER_VALIDATE_BOOLEAN ) ) {
            return;
        }

        wp_enqueue_style( 'wcagaat_modes_dark_css', Bootstrap::url( 'inc/css/mode-dark.css' ), [], Bootstrap::script_version() );

        // Integration-specific dark mode CSS
        $INTEGRATIONS = new Integrations();
        foreach ( $INTEGRATIONS->identifiers as $key => $plugin ) {
            if ( isset( $plugin[ 'css' ] ) && $plugin[ 'css' ] && !empty( $plugin[ 'short' ] ) ) {
                $handle = 'wcagaat_modes_dark_css_' . $plugin[ 'short' ];
                $src    = Bootstrap::url( 'inc/css/mode-dark-' . $plugin[ 'short' ] . '.css' );
                wp_enqueue_style( $handle, $src, [], Bootstrap::script_version() );
            }
        }

        // Integration-specific nav bar
        if ( $INTEGRATIONS->is_cornerstone_active() ) {
            wp_enqueue_style( 'wcagaat_modes_nav_css_cs', Bootstrap::url( 'inc/css/mode-nav-cs.css' ), [], Bootstrap::script_version() );
        }
        
    } // End script_enqueuer()


    /**
     * Ajax call
     *
     * @return void
     */
    public function ajax() {
        check_ajax_referer( $this->nonce, 'nonce' );
        if ( ! current_user_can( 'read' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
        }

        // Fetch vars
        $pref = isset( $_POST[ 'pref' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'pref' ] ) ) : '';
        if ( ! array_key_exists( $pref, self::USER_PREFS_DEFAULTS ) && $pref !== 'reset' ) {
            wp_send_json_error( [ 'message' => 'Invalid preference.' ] );
        }

        // Reset to defaults
        if ( $pref === 'reset' ) {
            $user_id = get_current_user_id();

            if ( $user_id ) {
                delete_user_meta( $user_id, self::USER_META_KEY );
            } else {
                if ( session_status() !== PHP_SESSION_ACTIVE && ! headers_sent() ) {
                    session_start();
                }
                unset( $_SESSION[ self::USER_META_KEY ] );
            }
            wp_send_json_success();
            return;

        } else {

            // Set the preference
            $raw_value = isset( $_POST[ 'value' ] ) ? wp_unslash( $_POST[ 'value' ] ) : '';
            $value = match( $pref ) {
                'text_resizer'  => intval( $raw_value ),
                'readable_font' => filter_var( $raw_value, FILTER_VALIDATE_BOOLEAN ),
                'mode'          => sanitize_key( $raw_value ),
                default         => sanitize_text_field( $raw_value ),
            };

            $user_id = get_current_user_id();

            $current_prefs = [];
            foreach ( self::USER_PREFS_DEFAULTS as $key => $def ) {
                $current_prefs[ $key ] = self::get_user_pref( $key );
            }

            $current_prefs[ $pref ] = $value;

            if ( $user_id ) {
                update_user_meta( $user_id, self::USER_META_KEY, $current_prefs );
                wp_send_json_success();
            } else {
                if ( session_status() !== PHP_SESSION_ACTIVE && ! headers_sent() ) {
                    session_start();
                }

                $_SESSION[ self::USER_META_KEY ] = $current_prefs;
                wp_send_json_success();
            }
        }

        wp_send_json_error( [ 'message' => 'Unhandled error.' ] );
    } // End ajax()
    
}


if ( Settings::is_assistant_enabled() ) {
    Assistant::instance();
}