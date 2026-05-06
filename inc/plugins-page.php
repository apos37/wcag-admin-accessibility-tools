<?php
/**
 * Plugins page
 */

namespace PluginRx\WCAGAdminAccessibilityTools;

if ( ! defined( 'ABSPATH' ) ) exit;

class PluginsPage {


    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?PluginsPage $instance = null;


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
    private function __construct() {
        add_filter( 'plugin_action_links_' . Bootstrap::plugin_file(), [ $this, 'plugins_settings_link' ] );
        add_filter( 'plugin_row_meta', [ $this, 'plugins_meta_links' ], 10, 2 );
    } // End __construct()


    /**
     * Add a "Settings" link to the plugin's action links on the Plugins page.
     *
     * @param array $links Existing action links for the plugin.
     * @return array Modified action links with the "Settings" link added.
     */
    public function plugins_settings_link( $links ) {
        $url = Bootstrap::settings_url();

        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $url ),
            esc_html__( 'Settings', 'wcag-admin-accessibility-tools' )
        );

        array_unshift( $links, $settings_link );

        return $links;
    } // End plugins_settings_link()


	/**
     * Add links to plugin row
     *
     * @param array $links
     * @return array
     */
    public function plugins_meta_links( $links, $file ) {
        if ( Bootstrap::plugin_file() == $file ) {
            $text_domain = Bootstrap::textdomain();
            $plugin_name = Bootstrap::name();
            $base_url    = Bootstrap::author_uri();

            $our_links   = [
                'guide' => [
                    'label' => __( 'How-To Guide', 'wcag-admin-accessibility-tools' ),
                    'url'   => "{$base_url}guide/plugin/{$text_domain}",
                ],
                'docs' => [
                    'label' => __( 'Developer Docs', 'wcag-admin-accessibility-tools' ),
                    'url'   => "{$base_url}docs/plugin/{$text_domain}",
                ],
                'support' => [
                    'label' => __( 'Support', 'wcag-admin-accessibility-tools' ),
                    'url'   => "{$base_url}support/plugin/{$text_domain}",
                ],
            ];

            foreach ( $our_links as $key => $link ) {
                $aria_label = sprintf(
                    // translators: %1$s: Link label, %2$s: Plugin name
                    __( '%1$s for %2$s', 'wcag-admin-accessibility-tools' ),
                    $link[ 'label' ],
                    $plugin_name
                );
                $links[ $key ] = '<a href="' . esc_url( $link[ 'url' ] ) . '" target="_blank" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $link[ 'label' ] ) . '</a>';
            }
        }

        return (array) $links;
    } // End plugins_meta_links()

}


PluginsPage::instance();