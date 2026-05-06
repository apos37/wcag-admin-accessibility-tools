<?php
/**
 * Uninstall handler
 *
 * Deletes all plugin options and user meta if the cleanup setting is enabled.
 */

// Exit if not called by WP uninstall routine
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Execute the cleanup process
 */
function wcagaat_uninstall_plugin() {
    $settings = get_option( 'wcagaat_settings', [] );
    $should_cleanup = ( bool ) ( $settings[ 'uninstall_cleanup' ] ?? false );

    // Only proceed if the user explicitly opted-in to data removal
    if ( ! $should_cleanup ) {
        return;
    }

    // 1. Clean up Options
    delete_option( 'wcagaat_settings' );

    // 2. Clean up User Preferences for all users
    global $wpdb;
    
    $wpdb->delete(
        $wpdb->usermeta,
        [ 'meta_key' => 'wcagaat_user_prefs' ],
        [ '%s' ]
    );
}

// Run the function
wcagaat_uninstall_plugin();


// Finished.