<?php 
/**
 * Plugin settings
 */


/**
 * Define Namespaces
 */
namespace Apos37\WCAGAdminAccessibilityTools;
// use Apos37\WCAGAdminAccessibilityTools\Clear;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
	(new Settings())->init();
} );


/**
 * The class
 */
class Settings {

    /**
	 * The options group
	 *
	 * @var string
	 */
	private $group = WCAGAAT_TEXTDOMAIN;


    /**
     * Default value link texts
     *
     * @var string
     */
    public $vague_link_phrases = 'click here, read more, more info, learn more, details, here, more, info, link, see more, find out, read, go, continue, next, view, visit, download, watch, signup, register';
    

    /**
     * Load on init
     */
    public function init() {
        
		// Submenu
        add_action( 'admin_menu', [ $this, 'submenu' ] );

		// Register the options
        add_action( 'admin_init', [  $this, 'register' ] );

        // JQuery and CSS
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );

    } // End init()


	/**
     * Submenu
     *
     * @return void
     */
    public function submenu() {
        add_submenu_page(
            'tools.php',
            WCAGAAT_NAME . ' — ' . __( 'Settings', 'wcag-admin-accessibility-tools' ),
            WCAGAAT_NAME,
            'manage_options',
            WCAGAAT__TEXTDOMAIN,
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
        if ( $current_screen->id !== WCAGAAT_SETTINGS_SCREEN_ID ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_attr( get_admin_page_title() ) ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( $this->group ); ?>
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
            'modes'      => __( 'Modes', 'wcag-admin-accessibility-tools' ),
		];

		// Iter the sections
        foreach ( $sections as $key => $title ) {
			?>
			<div class="wcagaat-box-row">
				<div class="wcagaat-box-column">
					<header class="wcagaat-box-header"><h2><?php echo esc_html( $title ); ?></h2></header>

                    <?php if ( $key == 'modes' ) { ?>
                        <p class="inst"><?php echo wp_kses_post( __( 'Modes include Dark Mode and Greyscale Mode. Enabling Dark Mode applies a few basic style adjustments automatically, such as background and text color on some standard elements. However, every theme is different, and you will likely need to write additional CSS to ensure your design works as intended. When Dark Mode is active, a <code>wcagaat-dark-mode</code> class is added to the <code>&lt;body&gt;</code> element. You can use this as a starting point for targeting specific elements. Additionally, any element with the <code>dark-mode</code> class will automatically receive a <code>#222222</code> background and <code>#ffffff</code> text color.', 'wcag-admin-accessibility-tools' ) ); ?></p>
                        <p class="inst">Example CSS: <code>.wcagaat-dark-mode .element { background: #222222; color: #ffffff; }</code></p>
                    <?php } ?>

					<?php $this->fields( $key ); ?>
				</div>
			</div>
			<?php
        }
	} // End sections()


    /**
	 * The options to register
	 *
	 * @return array
	 */
	public function options() {
        // Check if the skip link is needed
        $skip_link_needed = sanitize_key( get_option( 'wcagaat_skip_link_present', 'no' ) );
        $warning = ( $skip_link_needed === 'yes' ) ? '<div class="wcagaat-warning">' . esc_html__( 'Warning: A skip link has already been detected in your theme or another plugin. Enabling this option may cause duplicate skip links.', 'wcag-admin-accessibility-tools' ) . '</div>' : '';

        // Options
		$options = [
            [
                'section'   => 'structural',
                'type'      => 'checkbox',
                'sanitize'  => 'sanitize_checkbox',
                'key'       => 'wcagaat_skip_link',
                'title'     => __( 'Skip to Content Link', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Adds a visually hidden "Skip to main content" link at the top of every page for improved keyboard navigation. Before enabling, please ensure this link has not already been added by your theme or a different plugin.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
                'comments'  => $warning,
            ],
            [
                'section'   => 'forms',
                'type'      => 'checkbox',
                'sanitize'  => 'sanitize_checkbox',
                'key'       => 'wcagaat_protected_password_eye',
                'title'     => __( 'Display Password Eye for Page Passwords', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Adds a "Show Password" toggle to password fields for improved accessibility.', 'wcag-admin-accessibility-tools' ),
                'default'   => FALSE,
            ],
            [
                'section'   => 'images',
                'type'      => 'checkbox',
                'sanitize'  => 'sanitize_checkbox',
                'key'       => 'wcagaat_media_library_alt_text',
                'title'     => __( 'Alt Text Column & Inline Editing', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Adds a sortable Alt Text column to the Media Library list view. Alt text can be updated directly within the table.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
            ],
            [
                'section'   => 'images',
                'type'      => 'checkbox',
                'sanitize'  => 'sanitize_checkbox',
                'key'       => 'wcagaat_media_library_other_cols',
                'title'     => __( 'Additional Media Columns', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Adds columns for Dimensions, MIME Type (e.g. image/png), and File Size to the Media Library list view.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
            ],
            [
                'section'   => 'previewer',
                'type'      => 'checkbox',
                'sanitize'  => 'sanitize_checkbox',
                'key'       => 'wcagaat_admin_bar',
                'title'     => __( 'Toolbar Toggles', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Adds a menu to the admin bar on the front end with tools you can toggle to show errors on the page.', 'wcag-admin-accessibility-tools' ),
                'default'   => TRUE,
            ],
            [
                'section'   => 'previewer',
                'type'      => 'checkbox',
                'sanitize'  => 'sanitize_checkbox',
                'key'       => 'wcagaat_contrast_aaa',
                'title'     => __( 'Use WCAG AAA Color Contrast Compliance', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'When enabled, color contrast checks will enforce stricter AAA standards in addition to the default AA criteria, ensuring higher accessibility compliance on your site.', 'wcag-admin-accessibility-tools' ),
                'default'   => FALSE,
                'conditions' => [ 'wcagaat_admin_bar' ]
            ],
            [
                'section'   => 'previewer',
                'type'      => 'textarea',
                'sanitize'  => 'sanitize_textarea_field',
                'key'       => 'wcagaat_meaningful_link_texts',
                'title'     => __( 'Vague Link Texts', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Comma-separated list of vague or generic link texts to check for, e.g. "click here, read more, more info".', 'wcag-admin-accessibility-tools' ),
                'default'   => $this->vague_link_phrases,
                'revert'    => TRUE,
                'conditions'=> [ 'wcagaat_admin_bar' ],
            ],
            [
                'section'   => 'modes',
                'type'      => 'select',
                'sanitize'  => 'sanitize_key',
                'key'       => 'wcagaat_mode_visibility',
                'title'     => __( 'Mode Visibility', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Controls who can see the mode switcher on the front end. Choose to limit visibility to administrators, logged-in users, or show it to everyone.', 'wcag-admin-accessibility-tools' ),
                'options'   => [
                    ''          => __( 'Disabled', 'wcag-admin-accessibility-tools' ),
                    'admins'    => __( 'Administrators Only', 'wcag-admin-accessibility-tools' ),
                    'logged-in' => __( 'Logged-In Only', 'wcag-admin-accessibility-tools' ),
                    'everyone'  => __( 'Everyone', 'wcag-admin-accessibility-tools' ),
                ],
                'default'   => '',
            ],
            [
                'section'   => 'modes',
                'type'      => 'select',
                'sanitize'  => 'sanitize_key',
                'key'       => 'wcagaat_modes',
                'title'     => __( 'Mode Selector', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Adds a selector for switching to dark mode or greyscale mode.', 'wcag-admin-accessibility-tools' ),
                'options'   => [
                    'float'     => __( 'Floating Switch', 'wcag-admin-accessibility-tools' ),
                    'nav'       => __( 'Navigation Menu', 'wcag-admin-accessibility-tools' ),
                    'shortcode' => __( 'Shortcode [wcagaat_modes]', 'wcag-admin-accessibility-tools' ),
                ],
                'default'   => 'float',
                'conditions'=> [ 'wcagaat_mode_visibility' ],
            ],
            [
                'section'   => 'modes',
                'type'      => 'url',
                'sanitize'  => 'sanitize_url',
                'key'       => 'wcagaat_light_logo',
                'title'     => __( 'Default Logo URL', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Optional. Provide the URL of a logo that should be replaced with the logo specified below when dark mode is enabled.', 'wcag-admin-accessibility-tools' ),
                'default'   => '',
                'conditions'=> [ 'wcagaat_mode_visibility' ],
            ],
            [
                'section'   => 'modes',
                'type'      => 'url',
                'sanitize'  => 'sanitize_url',
                'key'       => 'wcagaat_dark_logo',
                'title'     => __( 'Alternative Logo for Dark Mode', 'wcag-admin-accessibility-tools' ),
                'desc'      => __( 'Optional. Provide the URL of a light-colored logo to be used automatically when dark mode is enabled.', 'wcag-admin-accessibility-tools' ),
                'default'   => '',
                'conditions'=> [ 'wcagaat_mode_visibility' ],
            ],
        ];

        // Apply filter to allow developers to add custom fields
        $options = apply_filters( 'wcagaat_custom_settings', $options );

        return $options;
	} // End options()


    /**
	 * Register the options
	 *
	 * @return array
	 */
	public function register() {
		$options = $this->options();
		foreach ( $options as $option ) {
			register_setting( $this->group, $option[ 'key' ], $option[ 'sanitize' ] );
		}
	} // End register()


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
					$condition_option = array_filter( $options, fn( $opt ) => $opt[ 'key' ] === $condition_key );
					$condition = reset( $condition_option );
					$val = sanitize_text_field( get_option( $condition_key, $condition[ 'default' ] ?? '' ) );
					if ( !filter_var( $val, FILTER_VALIDATE_BOOLEAN ) ) {
						$not_applicable = true;
						break;
					}
				}
			}

			$classes = 'wcagaat-box-content has-fields';
			if ( $not_applicable ) {
				$classes .= ' not-applicable';
			}
			?>
			<div class="<?php echo esc_attr( $classes ); ?>">
				<div class="wcagaat-box-left">
					<label for="<?php echo esc_html( $option[ 'key' ] ); ?>"><?php echo esc_html( $option[ 'title' ] ); ?></label>
					<?php if ( isset( $option[ 'desc' ] ) ) { ?>
						<p class="wcagaat-box-desc"><?php echo esc_html( $option[ 'desc' ] ); ?></p>
					<?php } ?>
				</div>
				
				<div class="wcagaat-box-right">
					<?php
					$add_field = 'settings_field_' . $option[ 'type' ];
					$this->$add_field( $option );
					?>
				</div>
			</div>
			<?php
		}
	} // End fields()
  
    
    /**
     * Custom callback function to print text field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_text( $args ) {
        $width = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '43rem';
        $default = isset( $args[ 'default' ] )  ? $args[ 'default' ] : '';
        $value = sanitize_text_field( get_option( $args[ 'key' ], $default ) );
        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] == true && trim( $value ) == '' ) {
            $value = $default;
        }
        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            // Translators: %1$s is the input field id, %2$s is the input field name, %3$s is the current value of the field, %4$s is the CSS width, %5$s is comments HTML.
            '<input type="text" id="%1$s" name="%2$s" value="%3$s" style="width: %4$s;" />%5$s',
            esc_attr( $args[ 'key' ] ),
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
        $default = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';
        $value   = esc_url( get_option( $args[ 'key' ], $default ) );

        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] === true && trim( $value ) === '' ) {
            $value = esc_url( $default );
        }

        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            // Translators: %1$s is the input field id, %2$s is the input field name, %3$s is the current URL value, %4$s is the CSS width, %5$s is comments HTML.
            '<input type="url" id="%1$s" name="%2$s" value="%3$s" style="width: %4$s;" />%5$s',
            esc_attr( $args[ 'key' ] ),
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
        $default = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';
        $value = sanitize_textarea_field( get_option( $args[ 'key' ], $default ) );
        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] === true && trim( $value ) === '' ) {
            $value = $default;
        }
        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            // Translators: %1$s is the textarea field id, %2$s is the textarea field name, %3$s is the CSS width, %4$s is the CSS height, %5$s is the current textarea value, %6$s is comments HTML.
            '<textarea id="%1$s" name="%2$s" style="width: %3$s; height: %4$s;">%5$s</textarea>%6$s',
            esc_attr( $args[ 'key' ] ),
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
		$value = filter_var( get_option( $args[ 'key' ], $args[ 'default' ] ), FILTER_VALIDATE_BOOLEAN );
		$id    = esc_attr( $args[ 'key' ] );
		$label = $value ? __( 'On', 'wcag-admin-accessibility-tools' ) : __( 'Off', 'wcag-admin-accessibility-tools' );

		printf(
			'<label class="wcagaat-toggle">
				<input type="checkbox" id="%1$s" name="%1$s"%2$s />
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
			esc_attr( $id ),
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
        $default = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';
        $value   = sanitize_key( get_option( $args[ 'key' ], $default ) );
        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] === true && trim( $value ) === '' ) {
            $value = $default;
        }
        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            // Translators: %1$s is the select field id, %2$s is the select field name, %3$s is the CSS width style, %4$s is the rendered <option> tags, %5$s is comments HTML.
            '<select id="%1$s" name="%2$s" style="width: %3$s;">%4$s</select>%5$s',
            esc_attr( $args[ 'key' ] ),
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
     * @return void
     */
    public function enqueue( $hook ) {
        // Check if we are on the correct admin page
        if ( $hook !== WCAGAAT_SETTINGS_SCREEN_ID ) {
            return;
        }

        // Get the options
		$options_with_conditions = array_values( array_filter( $this->options(), function( $option ) {
			return isset( $option[ 'conditions' ] );
		} ) );

		// JS
		$handle = 'wcagaat_settings';
		wp_enqueue_script( $handle, WCAGAAT_JS_PATH . 'settings.js', [ 'jquery' ], WCAGAAT_SCRIPT_VERSION, true );
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
		wp_enqueue_style( WCAGAAT_TEXTDOMAIN . '-settings', WCAGAAT_CSS_PATH . 'settings.css', [], WCAGAAT_SCRIPT_VERSION );
    } // End enqueue()

}
