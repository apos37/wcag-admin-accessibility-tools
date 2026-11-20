<?php
/**
 * Integrations
 */


/**
 * Define Namespaces
 */
namespace PluginRx\WCAGAdminAccessibilityTools;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
new Integrations();


/**
 * The class
 */
class Integrations {

    
    public $identifiers = [
        'cornerstone' => [
            'label' => 'Cornerstone',
            'dir'   => 'cornerstone-companion/cornerstone-companion.php',
            'css'   => true,
            'short' => 'cs',
        ],
        'gravityforms' => [
			'label' => 'Gravity Forms',
			'dir'   => 'gravityforms/gravityforms.php',
            'css'   => true,
			'short' => 'gf',
		],
		'learndash' => [
			'label' => 'LearnDash',
			'dir'   => 'sfwd-lms/sfwd_lms.php',
            'css'   => true,
			'short' => 'ld',
		],
		'bbpress' => [
			'label' => 'bbPress',
			'dir'   => 'bbpress/bbpress.php',
            'css'   => true,
			'short' => 'bbp',
		],
		'gravityformsrecaptcha' => [
			'label' => 'Gravity Forms reCAPTCHA',
			'dir'   => 'gravityformsrecaptcha/recaptcha.php',
			'css'   => false,
		],
    ];


    /**
     * Check if the plugin is active
     *
     * @param string $name
     * @param array $args
     * @return boolean
     */
	public function __call( $name, $args ) {
		if ( preg_match( '/^is_(\w+)_active$/', $name, $matches ) ) {
			$key = $matches[1];

			if ( isset( $this->identifiers[ $key ] ) ) {
				if ( ! function_exists( 'is_plugin_active' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				return is_plugin_active( $this->identifiers[ $key ][ 'dir' ] );
			}
		}

		return false;
	} // End __call()


	/**
	 * Check if the Cornerstone builder is available, via the plugin or the Pro theme
	 *
	 * @return bool
	 */
	public function has_cornerstone_builder() {
		static $result = null;

		if ( null !== $result ) {
			return $result;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$has_plugin  = is_plugin_active( 'cornerstone/cornerstone.php' );
		$theme       = wp_get_theme();
		$check_theme = $theme->parent() ?: $theme;
		$name        = strtolower( $check_theme->get( 'Name' ) );
		$template    = strtolower( $check_theme->get( 'Template' ) );
		$author      = $check_theme->get( 'Author' );
		$author_uri  = $check_theme->get( 'AuthorURI' );

		$has_pro_theme = ( 'pro' === $name || 'pro' === $template ) && ( false !== stripos( $author, 'Themeco' ) || false !== stripos( $author_uri, 'theme.co' ) );

		$result = ( bool ) apply_filters( 'wcagaat_has_cornerstone_builder', $has_plugin || $has_pro_theme );

		return $result;
	} // End has_cornerstone_builder()

}