<?php
/**
 * Helpers
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
 * The class
 */
class Helpers {


    /**
     * Check if the assistant should be active based on visibility settings
     *
     * @return bool
     */
    public static function is_assistant_active() {
        $visibility = sanitize_key( get_option( 'wcagaat_assistant_visibility' ) );
        if ( ( $visibility === 'admins' && current_user_can( 'administrator' ) ) ||
            ( $visibility === 'logged-in' && is_user_logged_in() ) ||
            ( $visibility === 'everyone' ) ) {
            return true;
        }
        return false;
    } // End is_assistant_active()


    /**
     * Check if the modes should be active based on visibility settings
     *
     * @return void
     */
    public static function is_modes_active() {
        $visibility = sanitize_key( get_option( 'wcagaat_mode_visibility' ) );
        if ( ( $visibility === 'admins' && current_user_can( 'administrator' ) ) ||
            ( $visibility === 'logged-in' && is_user_logged_in() ) ||
            ( $visibility === 'everyone' ) ) {
            return true;
        }
        return false;
    } // End is_modes_active()


    public static function is_text_resizer_enabled() {
        return filter_var( get_option( 'wcagaat_tool_text_resizer', TRUE ), FILTER_VALIDATE_BOOLEAN );
    } // End is_text_resizer_enabled()


    public static function is_readable_font_enabled() {
        return filter_var( get_option( 'wcagaat_tool_readable_font', TRUE ), FILTER_VALIDATE_BOOLEAN );
    } // End is_readable_font_enabled()


    public static function is_modes_enabled() {
        return filter_var( get_option( 'wcagaat_tool_modes', TRUE ), FILTER_VALIDATE_BOOLEAN );
    } // End is_modes_enabled()

}