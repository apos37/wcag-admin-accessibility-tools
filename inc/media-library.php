<?php 
/**
 * Media Library
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
 * Initiate the class
 */
new MediaLibrary();


/**
 * The class.
 */
class MediaLibrary {

    /**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'media_library_alt_text';


    /**
	 * Constructor
	 */
	public function __construct() {

        // Add columns to List View
        add_filter( 'manage_upload_columns', [ $this, 'add_column' ] );
        add_action( 'manage_media_custom_column', [ $this, 'render_column' ], 10, 2 );
        add_filter( 'manage_upload_sortable_columns', [ $this, 'make_column_sortable' ] );
        add_action( 'pre_get_posts', [ $this, 'handle_sorting' ] );

        // Caching
        if ( $this->doing_other_cols() ) {
            add_action( 'add_attachment', [ $this, 'cache_file_size' ] );
            add_action( 'edit_attachment', [ $this, 'cache_file_size' ] );
        }

        // Alt text ajax (which is also used by the admin bar)
        if ( $this->doing_alt_text() || get_option( 'wcagaat_admin_bar', true ) ) {
            add_action( 'wp_ajax_alt_text_update', [ $this, 'ajax_update_alt_text' ] );
        }

        // Alt text enqueue
        if ( $this->doing_alt_text() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        }

	} // End __construct()


    /**
     * Check if the Alt Text column and inline editing are enabled.
     *
     * @return bool
     */
    private function doing_alt_text() {
        return (bool) get_option( 'wcagaat_media_library_alt_text', true );
    } // End doing_alt_text()


    /**
     * Check if the other media columns are enabled.
     *
     * @return bool
     */
    private function doing_other_cols() {
        return (bool) get_option( 'wcagaat_media_library_other_cols', true );
    } // End doing_other_cols()

    
    /**
     * Add custom columns to the Media Library list view if enabled in settings.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns with optional additions.
     */
    public function add_column( $columns ) {
        if ( $this->doing_alt_text() ) {
            $columns[ 'alt_text' ] = __( 'Alt Text', 'wcag-admin-accessibility-tools' );
        }

        if ( $this->doing_other_cols() ) {
            $columns[ 'dimensions' ] = __( 'Dimensions', 'wcag-admin-accessibility-tools' );
            $columns[ 'type' ]       = __( 'Type', 'wcag-admin-accessibility-tools' );
            $columns[ 'file_size' ]  = __( 'File Size', 'wcag-admin-accessibility-tools' );
        }

        return $columns;
    } // End add_column()


    /**
     * Render the content for the custom columns in the Media Library list view.
     *
     * @param string $column_name Name of the column being rendered.
     * @param int    $post_id     Attachment post ID.
     */
    public function render_column( $column_name, $post_id ) {
        if ( $column_name === 'alt_text' && $this->doing_alt_text() ) {
            $alt = get_post_meta( $post_id, '_wp_attachment_image_alt', true );
            echo '<span class="alt-text-display" data-id="' . esc_attr( $post_id ) . '">' . esc_html( $alt ?: '—' ) . '</span>';
            echo '<span class="alt-text-editing" style="display:none;"></span>';
            echo '<div class="row-actions visible-on-hover"><a href="#" class="alt-text-edit" data-id="' . esc_attr( $post_id ) . '">' . esc_html__( 'Edit', 'wcag-admin-accessibility-tools' ) . '</a></div>';
        }

        if ( !$this->doing_other_cols() ) {
            return;
        }

        switch ( $column_name ) {
            case 'dimensions':
                if ( wp_attachment_is_image( $post_id ) ) {
                    $meta = wp_get_attachment_metadata( $post_id );
                    echo isset( $meta[ 'width' ], $meta[ 'height' ] ) ? esc_html( $meta[ 'width' ] . '×' . $meta[ 'height' ] ) : '—';
                } else {
                    echo '—';
                }
                break;

            case 'type':
                $file = get_attached_file( $post_id );
                $mime = $file && file_exists( $file ) ? mime_content_type( $file ) : get_post_mime_type( $post_id );
                echo esc_html( $mime ?: '—' );
                break;

            case 'file_size':
                $path = get_attached_file( $post_id );
                echo file_exists( $path ) ? esc_html( size_format( filesize( $path ) ) ) : '—';
                break;
        }
    } // End render_column()


    /**
     * Make the custom columns sortable if the options are enabled.
     *
     * @param array $columns Existing sortable columns.
     * @return array Modified sortable columns.
     */
    public function make_column_sortable( $columns ) {
        if ( $this->doing_alt_text() ) {
            $columns[ 'alt_text' ] = 'alt_text';
        }

        if ( $this->doing_other_cols() ) {
            $columns[ 'type' ]      = 'type';
            $columns[ 'file_size' ] = 'file_size';
        }

        return $columns;
    } // End make_column_sortable()


    /**
     * Modify the query to handle sorting for custom media columns.
     *
     * @param WP_Query $query The current query object.
     */
    public function handle_sorting( $query ) {
        if ( !is_admin() || !$query->is_main_query() ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        if ( $orderby === 'alt_text' && $this->doing_alt_text() ) {
            $query->set( 'meta_key', '_wp_attachment_image_alt' );
            $query->set( 'orderby', 'meta_value' );
        }

        if ( $orderby === 'type' && $this->doing_other_cols() ) {
            $query->set( 'orderby', 'mime_type' );
        }

        if ( $orderby === 'file_size' && $this->doing_other_cols() ) {
            add_filter( 'posts_clauses', [ $this, 'add_file_size_sorting' ] );
        }
    } // End handle_sorting()


    /**
     * Helper function for sorting file size
     *
     * @param array $clauses
     * @return array
     */
    public function add_file_size_sorting( $clauses ) {
        global $wpdb;

        $clauses[ 'join' ] .= "
            LEFT JOIN {$wpdb->postmeta} AS filesize_meta
            ON ({$wpdb->posts}.ID = filesize_meta.post_id AND filesize_meta.meta_key = '_wcagaat_cached_file_size')";
        
        $clauses[ 'orderby' ] = "CAST( filesize_meta.meta_value AS UNSIGNED ) " . ( get_query_var( 'order' ) === 'desc' ? 'DESC' : 'ASC' );

        return $clauses;
    } // End add_file_size_sorting()


    /**
     * Caching file sizes
     *
     * @param int $post_id
     * @return void
     */
    public function cache_file_size( $post_id ) {
        $path = get_attached_file( $post_id );
        if ( file_exists( $path ) ) {
            update_post_meta( $post_id, '_wcagaat_cached_file_size', filesize( $path ) );
        }
    } // End cache_file_size()


    /**
     * Handle AJAX request to update the alt text.
     */
    public function ajax_update_alt_text() {
        if ( !current_user_can( 'upload_files' ) || !check_ajax_referer( $this->nonce, 'nonce', false ) ) {
            wp_send_json_error( 'Permission denied.' );
        }

        $post_id = isset( $_POST[ 'post_id' ] ) ? absint( wp_unslash( $_POST[ 'post_id' ] ) ) : 0;
        $alt = isset( $_POST[ 'alt_text' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'alt_text' ] ) ) : '';

        if ( !get_post( $post_id ) ) {
            wp_send_json_error( 'Invalid attachment ID.' );
        }

        update_post_meta( $post_id, '_wp_attachment_image_alt', $alt );
        wp_send_json_success( esc_html( $alt ?: '—' ) );
    } // End ajax_update_alt_text()


    /**
     * Enqueue scripts
     * 
     * @param string $hook
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'upload.php' ) {
            return;
        }

        $handle = 'wcagaat_media_alt_edit';
        wp_enqueue_script( $handle, WCAGAAT_JS_PATH . 'media-library.js', [ 'jquery' ], WCAGAAT_SCRIPT_VERSION, true );
        wp_localize_script( $handle, $handle, [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( $this->nonce ),
            'text'    => [
                'edit'   => __( 'Edit', 'wcag-admin-accessibility-tools' ),
                'update' => __( 'Update', 'wcag-admin-accessibility-tools' )
            ]
        ] );
    } // End enqueue_scripts()

}