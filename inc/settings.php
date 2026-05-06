<?php 
/**
 * Plugin settings
 */


namespace PluginRx\WCAGAdminAccessibilityTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {


    /**
     * Default vague link phrases
     *
     * @var string
     */
    public static $vague_link_phrases = 'click here, read more, more info, learn more, details, here, more, info, link, see more, find out, read, go, continue, next, view, visit, download, watch, signup, register';


    /**
	 * The option key
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'wcagaat_settings';


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Settings $instance = null;


    /**
     * Internal cache to prevent multiple get_option calls
     */
    private static ?array $cached_settings = null;


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
        add_action( 'admin_menu', [ $this, 'submenu' ] );
        add_action( 'admin_init', [  $this, 'register' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        add_action( 'wp_ajax_wcagaat_dismiss_deprecated_shortcode_notice', [ $this, 'ajax_dismiss_deprecated_shortcode_notice' ] );
        add_action( 'admin_notices', [ $this, 'deprecated_shortcode_notice' ] );
    } // End __construct()


    /**
     * Get a specific setting value with caching and defaults
     * 
     * @param string $key The specific metadata key to retrieve
     * @param mixed $default Default value to return if key is not found
     * @return mixed The value of the requested metadata key or default if not found
     */
    public static function get( $key, $default = null ) : mixed {
        if ( null === self::$cached_settings ) {
            $instance = self::instance();
            $definitions = $instance->options( true );
            
            // 1. Try to get the new grouped settings
            $saved = get_option( self::OPTION_NAME, null );
            $needs_migration_save = false;

            // 2. MIGRATION CHECK: If the new group doesn't exist, look for legacy keys
            if ( null === $saved ) {
                $saved = [];
                foreach ( $definitions as $opt ) {
                    $new_key = $opt[ 'key' ];
                    $old_key = 'wcagaat_' . $new_key;

                    // Check for the old individual option
                    $old_value = get_option( $old_key, 'NOT_FOUND' );

                    if ( 'NOT_FOUND' !== $old_value ) {
                        $saved[ $new_key ] = $old_value;
                        delete_option( $old_key );
                        $needs_migration_save = true;
                    }
                }

                // If we found legacy data, save the new grouped option immediately
                if ( $needs_migration_save ) {
                    update_option( self::OPTION_NAME, $saved );
                }
            }

            // 3. Handle environmental/detected options (like the skip link check)
            $saved[ 'skip_link_present' ] = get_option( 'wcagaat_skip_link_present', 'no' );

            // 4. Map Defaults
            $defaults = [];
            foreach ( $definitions as $opt ) {
                $defaults[ $opt[ 'key' ] ] = $opt[ 'default' ] ?? '';
            }

            self::$cached_settings = wp_parse_args( $saved, $defaults );
        }

        return self::$cached_settings[ $key ] ?? $default;
    } // End get()


    /**
     * Check if assistant is enabled based on visibility setting
     *
     * @return bool
     */
    public static function is_assistant_enabled() : bool {
        $visibility = sanitize_key( self::get( 'assistant_visibility' ) );
        if ( ( $visibility === 'admins' && current_user_can( 'administrator' ) ) ||
            ( $visibility === 'logged-in' && is_user_logged_in() ) ||
            ( $visibility === 'everyone' ) ) {
            
            if ( self::get( 'tool_text_resizer' ) || self::get( 'tool_readable_font' ) || self::get( 'tool_modes' ) ) {
                return true;
            }
        }
        return false;
    } // End is_assistant_enabled()


	/**
     * Submenu
     *
     * @return void
     */
    public function submenu() {
        add_submenu_page(
            'tools.php',
            Bootstrap::name() . ' — ' . __( 'Settings', 'wcag-admin-accessibility-tools' ),
            Bootstrap::name(),
            'manage_options',
            Bootstrap::textdomain(),
            [ $this, 'page' ]
        );
    } // End submenu()

    
    /**
     * The page
     *
     * @return void
     */
    public function page() {
        global $current_screen;
        if ( $current_screen->id !== 'tools_page_' . Bootstrap::textdomain() ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_attr( get_admin_page_title() ) ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_NAME ); ?>
				<div class="wcagaat-settings-wrapper">
					<div class="wcagaat-box-sections">
						<?php $this->sections(); ?>
					</div>
					<div class="wcagaat-sidebar">
						<div class="wcagaat-box-row">
							<div class="wcagaat-box-column">
								<header class="wcagaat-box-header"><h2><?php echo esc_html__( 'Save Settings', 'wcag-admin-accessibility-tools' ); ?></h2></header>
								<div class="wcagaat-box-content">
									<p><?php echo esc_html__( 'Once you are satisfied with your settings, click the button below to save them.', 'wcag-admin-accessibility-tools' ); ?></p>
									<?php submit_button( __( 'Update', 'wcag-admin-accessibility-tools' ) ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
        <?php
    } // End page()


    /**
	 * Get the settings sections
	 */
	public function sections() {
		$sections = [
			'structural' => __( 'Structural', 'wcag-admin-accessibility-tools' ),
            'forms'      => __( 'Forms', 'wcag-admin-accessibility-tools' ),
			'images'     => __( 'Images', 'wcag-admin-accessibility-tools' ),
            'previewer'  => __( 'Previewer', 'wcag-admin-accessibility-tools' ),
            'assistant'  => __( 'User Assistant', 'wcag-admin-accessibility-tools' ),
            'data_mgmt'  => __( 'Data Management', 'wcag-admin-accessibility-tools' ),
		];

		// Iter the sections
        foreach ( $sections as $key => $title ) {
			?>
			<div class="wcagaat-box-row">
				<div class="wcagaat-box-column">
					<header class="wcagaat-box-header"><h2><?php echo esc_html( $title ); ?></h2></header>
					<?php $this->fields( $key ); ?>
				</div>
			</div>
			<?php
        }
	} // End sections()


    /**
	 * The options to register
	 *
	 * @param bool $data_only Whether to include only data without extra markup.
	 * @return array
	 */
	public function options( $data_only = false ) {
        $skip_link_needed = get_option( 'wcagaat_skip_link_present', 'no' );
        $warning = ( $skip_link_needed === 'yes' && ! $data_only ) ? '<div class="wcagaat-warning">' . esc_html__( 'Warning: A skip link has already been detected in your theme or another plugin. Enabling this option may cause duplicate skip links.', 'wcag-admin-accessibility-tools' ) . '</div>' : '';

        // Options
		$options = [
            [
                'section'   => 'structural',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'skip_link',
                'title'     => $data_only ? '' : __( 'Skip to Content Link', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Adds a visually hidden "Skip to main content" link at the top of every page for improved keyboard navigation. Before enabling, please ensure this link has not already been added by your theme or a different plugin.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
                'comments'  => $warning,
            ],
            [
                'section'   => 'forms',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'protected_password_eye',
                'title'     => $data_only ? '' : __( 'Display Password Eye for Page Passwords', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Adds a "Show Password" toggle to password fields for improved accessibility.', 'wcag-admin-accessibility-tools' ),
                'default'   => FALSE,
            ],
            [
                'section'   => 'images',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'media_library_alt_text',
                'title'     => $data_only ? '' : __( 'Alt Text Column & Inline Editing', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Adds a sortable Alt Text column to the Media Library list view. Alt text can be updated directly within the table.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
            ],
            [
                'section'   => 'images',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'media_library_other_cols',
                'title'     => $data_only ? '' : __( 'Additional Media Columns', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Adds columns for Dimensions, MIME Type (e.g. image/png), and File Size to the Media Library list view.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
            ],
            [
                'section'   => 'previewer',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'admin_bar',
                'title'     => $data_only ? '' : __( 'Toolbar Toggles', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Adds a menu to the admin bar on the front end with tools you can toggle to show errors on the page.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
            ],
            [
                'section'   => 'previewer',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'admin_bar_console',
                'title'     => $data_only ? '' : __( 'Toolbar Console', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Console logs errors with color contrast and links missing underlines.', 'wcag-admin-accessibility-tools' ),
                'default'   => FALSE,
                'conditions' => [ 'admin_bar' ]
            ],
            [
                'section'   => 'previewer',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'contrast_aaa',
                'title'     => $data_only ? '' : __( 'Use WCAG AAA Color Contrast Compliance', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'When enabled, color contrast checks will enforce stricter AAA standards in addition to the default AA criteria, ensuring higher accessibility compliance on your site.', 'wcag-admin-accessibility-tools' ),
                'default'   => FALSE,
                'conditions' => [ 'admin_bar' ]
            ],
            [
                'section'   => 'previewer',
                'type'      => 'textarea',
                'sanitize'  => 'sanitize_textarea_field',
                'key'       => 'meaningful_link_texts',
                'title'     => $data_only ? '' : __( 'Vague Link Texts', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Comma-separated list of vague or generic link texts to check for, e.g. "click here, read more, more info".', 'wcag-admin-accessibility-tools' ),
                'default'   => self::$vague_link_phrases,
                'revert'    => TRUE,
                'conditions'=> [ 'admin_bar' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'select',
                'sanitize'  => 'sanitize_key',
                'key'       => 'assistant_visibility',
                'title'     => $data_only ? '' : __( 'Assistant Visibility', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Controls who can see the accessibility assistant on the front end. Choose to limit visibility to administrators, logged-in users, or show it to everyone.', 'wcag-admin-accessibility-tools' ),
                'options'   => $data_only ? [] : [
                    ''          => __( 'Disabled', 'wcag-admin-accessibility-tools' ),
                    'admins'    => __( 'Administrators Only', 'wcag-admin-accessibility-tools' ),
                    'logged-in' => __( 'Logged-In Only', 'wcag-admin-accessibility-tools' ),
                    'everyone'  => __( 'Everyone', 'wcag-admin-accessibility-tools' ),
                ],
                'default'   => '',
            ],
            [
                'section'   => 'assistant',
                'type'      => 'select',
                'sanitize'  => 'sanitize_key',
                'key'       => 'assistant_location',
                'title'     => $data_only ? '' : __( 'Assistant Location', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Select where the assistant button should appear.', 'wcag-admin-accessibility-tools' ),
                'options'   => $data_only ? [] : [
                    'float-left'  => __( 'Floating Switch (Bottom Left)', 'wcag-admin-accessibility-tools' ),
                    'float-right' => __( 'Floating Switch (Bottom Right)', 'wcag-admin-accessibility-tools' ),
                    'nav'         => __( 'Navigation Menu', 'wcag-admin-accessibility-tools' ),
                    'shortcode'   => __( 'Shortcode [wcagaat_assistant]', 'wcag-admin-accessibility-tools' ),
                ],
                'default'   => 'float-left',
                'conditions'=> [ 'assistant_visibility' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'tool_text_resizer',
                'title'     => $data_only ? '' : __( 'Tool: Text Resizer', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Includes buttons to increase or decrease text size within the assistant.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
                'conditions'=> [ 'assistant_visibility' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'tool_readable_font',
                'title'     => $data_only ? '' : __( 'Tool: Readable Font', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Includes a toggle to switch to a dyslexia-friendly system font stack.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
                'conditions'=> [ 'assistant_visibility' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'tool_modes',
                'title'     => $data_only ? '' : __( 'Tool: Modes', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Includes a dropdown to switch between different accessibility modes.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
                'conditions'=> [ 'assistant_visibility' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'subsection',
                'key'       => 'modes_divider',
                'title'     => $data_only ? '' : __( 'Mode Options', 'wcag-admin-accessibility-tools' ),
                'html'      => $data_only ? '' : '<p class="inst" >' . __( 'Modes include Dark Mode and Greyscale Mode. Enabling Dark Mode applies a few basic style adjustments automatically, such as background and text color on some standard elements. However, every theme is different, and you will likely need to write additional CSS to ensure your design works as intended. When Dark Mode is active, a <code>wcagaat-dark-mode</code> class is added to the <code>&lt;body&gt;</code> element. You can use this as a starting point for targeting specific elements. Additionally, any element with the <code>dark-mode</code> class will automatically receive a <code>#222222</code> background and <code>#ffffff</code> text color.', 'wcag-admin-accessibility-tools' ) . '</p>
                <p class="inst">Example CSS: <code>.wcagaat-dark-mode .element { background: #222222; color: #ffffff; }</code></p>',
                'conditions'=> [ 'assistant_visibility', 'tool_modes' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'auto_detect_dark_mode',
                'title'     => $data_only ? '' : __( 'Auto-Detect Device Dark Mode', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'If the user’s device is set to dark mode, a prompt will ask if they would like to enable dark mode for this site.', 'wcag-admin-accessibility-tools' ),
                'default'   => FALSE,
                'conditions'=> [ 'assistant_visibility', 'tool_modes' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'text',
                'sanitize'  => 'sanitize_text_field',
                'key'       => 'mode_icon_default',
                'title'     => $data_only ? '' : __( 'Default Mode Icon', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Only used if the modes tool is the only tool enabled.', 'wcag-admin-accessibility-tools' ),
                'default'   => 'f185', // fa-sun
                'conditions'=> [ 'assistant_visibility', 'tool_modes' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'text',
                'sanitize'  => 'sanitize_text_field',
                'key'       => 'mode_icon_dark',
                'title'     => $data_only ? '' : __( 'Dark Mode Icon', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Only used if the modes tool is the only tool enabled.', 'wcag-admin-accessibility-tools' ),
                'default'   => 'f186', // fa-moon
                'conditions'=> [ 'assistant_visibility', 'tool_modes' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'text',
                'sanitize'  => 'sanitize_text_field',
                'key'       => 'mode_icon_greyscale',
                'title'     => $data_only ? '' : __( 'Greyscale Mode Icon', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Only used if the modes tool is the only tool enabled.', 'wcag-admin-accessibility-tools' ),
                'default'   => 'f042', // fa-circle-half-stroke
                'conditions'=> [ 'assistant_visibility', 'tool_modes' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'url',
                'sanitize'  => 'sanitize_url',
                'key'       => 'default_logo',
                'title'     => $data_only ? '' : __( 'Default Logo URL', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Optional. Provide the URL of a logo that should be replaced with the logo specified below when dark mode is enabled.', 'wcag-admin-accessibility-tools' ),
                'default'   => '',
                'conditions'=> [ 'assistant_visibility', 'tool_modes' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'url',
                'sanitize'  => 'sanitize_url',
                'key'       => 'dark_logo',
                'title'     => $data_only ? '' : __( 'Alternative Logo for Dark Mode', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Optional. Provide the URL of a light-colored logo to be used automatically when dark mode is enabled. You must include the default URL above so we know what to replace with this one.', 'wcag-admin-accessibility-tools' ),
                'default'   => '',
                'conditions'=> [ 'assistant_visibility', 'tool_modes' ],
            ],
            [
                'section'   => 'assistant',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'disable_dark_mode_stylesheets',
                'title'     => $data_only ? '' : __( 'Disable Dark Mode Stylesheets', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Disable the automatic loading of pre-filled stylesheets for dark mode.', 'wcag-admin-accessibility-tools' ),
                'default'   => FALSE,
                'conditions'=> [ 'assistant_visibility', 'tool_modes' ],
            ],
            [
                'section'   => 'data_mgmt',
                'type'      => 'checkbox',
                'sanitize'  => [ $this, 'sanitize_checkbox' ],
                'key'       => 'uninstall_cleanup',
                'title'     => $data_only ? '' : __( 'Remove Data on Uninstall', 'wcag-admin-accessibility-tools' ),
                'desc'      => $data_only ? '' : __( 'Check this box to delete all user preferences and accessibility settings from the database when the plugin is deleted.', 'wcag-admin-accessibility-tools' ),
                'default'   => FALSE
            ]
        ];

        // Apply filter to allow developers to add custom fields
        $options = apply_filters( 'wcagaat_custom_settings', $options );

        if ( $data_only ) {
            $data = [];
            foreach ( $options as $opt ) {
                if ( isset( $opt[ 'key' ] ) ) {
                    $data[] = [
                        'key'       => $opt[ 'key' ],
                        'default'   => $opt[ 'default' ] ?? null,
                    ];
                }
            }
            return $data;
        }

        return $options;
	} // End options()


    /**
	 * Register the options
	 *
	 * @return array
	 */
	public function register() {
        register_setting( self::OPTION_NAME, self::OPTION_NAME, [ $this, 'sanitize_all_settings' ] );
    } // End register()


    /**
     * Sanitizes the entire array at once
     * 
     * @param array $input
     * @return array
     */
    public function sanitize_all_settings( $input ) {
        $output = [];
        $definitions = $this->options();

        foreach ( $definitions as $opt ) {
            $key = $opt[ 'key' ];
            
            if ( ( $opt[ 'type' ] ?? '' ) === 'subsection' ) {
                continue;
            }

            // Logic for checkboxes: if not in $input, it's unchecked (false)
            if ( $opt[ 'type' ] === 'checkbox' ) {
                $val = isset( $input[ $key ] ) ? $input[ $key ] : false;
            } else {
                $val = $input[ $key ] ?? $opt[ 'default' ];
            }

            // Check for callable (Works with [ $this, 'method' ] or 'global_func')
            if ( isset( $opt[ 'sanitize' ] ) && is_callable( $opt[ 'sanitize' ] ) ) {
                $output[ $key ] = call_user_func( $opt[ 'sanitize' ], $val );
            } else {
                $output[ $key ] = sanitize_text_field( $val );
            }
        }

        return $output;
    } // End sanitize_all_settings()


	/**
	 * Get the setting fields
	 *
	 * @param string $section
	 */
	public function fields( $section ) {
		$options = $this->options();

		foreach ( $options as $option ) {
			if ( $option[ 'section' ] !== $section ) {
				continue;
			}
			// Determine visibility based on conditions
			$not_applicable = false;
            if ( isset( $option[ 'conditions' ] ) && is_array( $option[ 'conditions' ] ) ) {
                foreach ( $option[ 'conditions' ] as $condition_key ) {
                    $val = self::get( $condition_key );
                    
                    if ( ! filter_var( $val, FILTER_VALIDATE_BOOLEAN ) ) {
                        $not_applicable = true;
                        break;
                    }
                }
            }

			$classes = 'wcagaat-box-content';
			if ( $not_applicable ) {
				$classes .= ' not-applicable';
			}

            if ( $option[ 'type' ] === 'subsection' ) {
                ?>
                <div class="<?php echo esc_attr( $classes ); ?>">
                    <div class="wcagaat-full-width">
                        <?php $this->settings_field_subsection( $option ); ?>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <div class="<?php echo esc_attr( $classes ); ?> has-fields">
                    <div class="wcagaat-box-left">
                        <label for="<?php echo esc_html( $option[ 'key' ] ); ?>"><?php echo esc_html( $option[ 'title' ] ); ?></label>
                        <?php if ( isset( $option[ 'desc' ] ) ) { ?>
                            <p class="wcagaat-box-desc"><?php echo esc_html( $option[ 'desc' ] ); ?></p>
                        <?php } ?>
                    </div>
                    
                    <div class="wcagaat-box-right">
                        <?php
                        $add_field = 'settings_field_' . $option[ 'type' ];
                        if ( method_exists( $this, $add_field ) ) {
                            $this->$add_field( $option );
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
		}
	} // End fields()


    /**
     * Custom callback function to print a subsection divider with optional HTML content
     *
     * @param array $args
     * @return void
     */
    public function settings_field_subsection( $args ) {
        echo '<div id="' . esc_html( $args[ 'key' ] ) . '" class="wcagaat-subsection">';
        if ( isset( $args[ 'title' ] ) ) {
            echo '<h3>' . esc_html( $args[ 'title' ] ) . '</h3>';
        }
        if ( isset( $args[ 'html' ] ) ) {
            echo wp_kses_post( $args[ 'html' ] );
        }
        echo '</div>';
    } // End settings_field_subsection()
  
    
    /**
     * Custom callback function to print text field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_text( $args ) {
        $width = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '43rem';
        $value = sanitize_text_field( self::get( $args[ 'key' ] ) );
        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            // Translators: %1$s is the input field id, %2$s is the current text value, %3$s is the CSS width, %4$s is comments HTML.
            '<input type="text" id="%1$s" name="wcagaat_settings[%1$s]" value="%2$s" style="width: %3$s;" />%4$s',
            esc_attr( $args[ 'key' ] ),
            esc_html( $value ),
            esc_attr( $width ),
            wp_kses_post( $comments )
        );
    } // settings_field_text()


    /**
     * Custom callback function to print URL field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_url( $args ) {
        $width   = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '43rem';
        $value   = esc_url( self::get( $args[ 'key' ] ) );
        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            // Translators: %1$s is the input field id, %2$s is the current URL value, %3$s is the CSS width, %4$s is comments HTML.
            '<input type="url" id="%1$s" name="wcagaat_settings[%1$s]" value="%2$s" style="width: %3$s;" />%4$s',
            esc_attr( $args[ 'key' ] ),
            esc_url( $value ),
            esc_attr( $width ),
            wp_kses_post( $comments )
        );
    } // settings_field_url()


    /**
     * Custom callback function to print textarea field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_textarea( $args ) {
        $width = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '43rem';
        $height = isset( $args[ 'height' ] ) ? $args[ 'height' ] : '6rem';
        $value = sanitize_textarea_field( self::get( $args[ 'key' ] ) );

        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] === true && trim( $value ) === '' ) {
            $value = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';
        }

        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            // Translators: %1$s is the textarea field id, %2$s is the CSS width style, %3$s is the CSS height style, %4$s is the current textarea value, %5$s is comments HTML.
            '<textarea id="%1$s" name="wcagaat_settings[%1$s]" style="width: %2$s; height: %3$s;">%4$s</textarea>%5$s',
            esc_attr( $args[ 'key' ] ),
            esc_attr( $width ),
            esc_attr( $height ),
            esc_textarea( $value ),
            wp_kses_post( $comments )
        );
    } // settings_field_textarea()


    /**
     * Custom callback function to print checkbox field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_checkbox( $args ) {
		$value = filter_var( self::get( $args[ 'key' ] ), FILTER_VALIDATE_BOOLEAN );
		$label = $value ? __( 'On', 'wcag-admin-accessibility-tools' ) : __( 'Off', 'wcag-admin-accessibility-tools' );

		printf(
			'<label class="wcagaat-toggle">
				<input type="checkbox" id="%1$s" name="wcagaat_settings[%1$s]"%2$s />
				<span>
					<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
						<path fill="none" stroke-width="2" stroke-linecap="square" stroke-miterlimit="10"
							d="M17,4.3c3,1.7,5,5,5,8.7 c0,5.5-4.5,10-10,10S2,18.5,2,13c0-3.7,2-6.9,5-8.7"
							stroke-linejoin="miter"></path>
						<line fill="none" stroke-width="2" stroke-linecap="square" stroke-miterlimit="10"
							x1="12" y1="1" x2="12" y2="8" stroke-linejoin="miter"></line>
					</svg>
					<span class="label">%3$s</span>
				</span>
			</label>
            %4$s',
			esc_attr( $args[ 'key' ] ),
			checked( $value, 1, false ),
			esc_html( $label ),
			wp_kses_post( isset( $args[ 'comments' ] ) ? $args[ 'comments' ] : '' )
		);
	} // End settings_field_checkbox()


    /**
     * Sanitize checkbox
     *
     * @param int $value
     * @return boolean
     */
    public function sanitize_checkbox( $value ) {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    } // End sanitize_checkbox()


    /**
     * Custom callback function to print select field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_select( $args ) {
        $width   = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '20rem';
        $options = isset( $args[ 'options' ] ) ? $args[ 'options' ] : [];
        $value   = sanitize_key( self::get( $args[ 'key' ] ) );
        
        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] === true && trim( $value ) === '' ) {
            $value = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';
        }
        
        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            // Translators: %1$s is the select field id, %2$s is the CSS width style, %3$s is the rendered <option> tags, %4$s is comments HTML.
            '<select id="%1$s" name="wcagaat_settings[%1$s]" style="width: %2$s;">%3$s</select>%4$s',
            esc_attr( $args[ 'key' ] ),
            esc_attr( $width ),
            wp_kses(
                $this->render_select_options( $options, $value ),
                [
                    'option' => [
                        'value'    => true,
                        'selected' => true,
                    ],
                ]
            ),
            wp_kses_post( $comments )
        );
    } // End settings_field_select()


    /**
     * Renders <option> tags for a select field
     *
     * @param array  $options
     * @param string $selected
     * @return string
     */
    private function render_select_options( $options, $selected ) {
        $html = '';
        foreach ( $options as $val => $label ) {
            $html .= sprintf(
                // Translators: %1$s is the option value, %2$s is 'selected' if this is the current value, %3$s is the label text.
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr( $val ),
                selected( $selected, $val, false ),
                esc_html( $label )
            );
        }
        return $html;
    } // End render_select_options()


    /**
     * Enqueue
     *
     * @param string $hook
     * @return void
     */
    public function enqueue( $hook ) {
        $script_version = Bootstrap::script_version();

        // Dismiss notice AJAX
		$handle = 'wcagaat_notice_dismissal';
		wp_enqueue_script( $handle, Bootstrap::url( 'inc/js/dismiss-notice.js' ), [ 'jquery' ], $script_version, true );
		wp_localize_script( $handle, $handle, [
			'nonce' => wp_create_nonce( 'wcagaat_dismiss_notice' ),
		] );

        // Settings page only
        $text_domain = Bootstrap::textdomain();
        if ( $hook !== 'tools_page_' . $text_domain ) {
            return;
        }

		$options_with_conditions = array_values( array_filter( $this->options(), function( $option ) {
			return isset( $option[ 'conditions' ] );
		} ) );

		// JS
		$handle = 'wcagaat_settings';
		wp_enqueue_script( $handle, Bootstrap::url( 'inc/js/settings.js' ), [ 'jquery' ], $script_version, true );
		wp_localize_script( $handle, $handle, [
			'on'      => __( 'On', 'wcag-admin-accessibility-tools' ),
			'off'     => __( 'Off', 'wcag-admin-accessibility-tools' ),
			'options' => array_map( function( $option ) {
				return [
					'key'        => $option[ 'key' ],
					'conditions' => $option[ 'conditions' ],
				];
			}, $options_with_conditions ),
		] );

		// CSS
		wp_enqueue_style( $text_domain . '-settings', Bootstrap::url( 'inc/css/settings.css' ), [], $script_version );
    } // End enqueue()


    /**
     * AJAX handler to dismiss deprecated shortcode notice
     *
     * @return void
     */
    public function ajax_dismiss_deprecated_shortcode_notice() {
        check_ajax_referer( 'wcagaat_dismiss_notice', 'nonce' );
        if ( current_user_can( 'manage_options' ) ) {
            delete_option( 'wcagaat_deprecated_shortcode_used' );
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Unauthorized', 403 );
        }
    } // End ajax_dismiss_deprecated_shortcode_notice()


    /**
     * Deprecated shortcode notice
     *
     * @return void
     */
    public function deprecated_shortcode_notice() {
        if ( get_option( 'wcagaat_deprecated_shortcode_used' ) && current_user_can( 'manage_options' ) ) {
            ?>
            <div id="wcagaat-deprecated-shortcode-notice" class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'WCAG Assistant Notice:', 'wcag-admin-accessibility-tools' ); ?></strong>
                    <?php esc_html_e( 'The [wcagaat_modes] shortcode has been detected on your site. This tag is deprecated; please update your pages to use [wcagaat_assistant] instead.', 'wcag-admin-accessibility-tools' ); ?>
                </p>
            </div>
            <?php
        }
    } // End deprecated_shortcode_notice()

}


Settings::instance();