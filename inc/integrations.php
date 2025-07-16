<?php
/**
 * Integrations
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
				return is_plugin_active( $this->identifiers[ $key ][ 'dir' ] );
			}
		}
		return false;
	} // End __call()

}