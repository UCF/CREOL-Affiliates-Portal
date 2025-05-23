<?php
/**
 * Shortcode to display a list of jobs in the affiliates portal.
 *
 * This widget fetches jobs via the REST API and displays them, optionally filtered by user IDs.
 * When the "self" attribute is true, each job will get Edit and Delete buttons.
 */

// Enqueue the JavaScript file and localize data.
function affiliates_list_jobs_enqueue_assets() {
    wp_register_script(
        'affiliates-list-jobs',
        plugins_url( '../assets/js/affiliates-list-jobs.js', __FILE__ ),
        [],
        '1.0.0',
        true
    );

    $base_url = rest_url( 'affiliates/v1/jobs' );
    wp_localize_script( 'affiliates-list-jobs', 'affiliatesJobs', [
        'restUrl'       => esc_url( $base_url ),
        'nonce'   => wp_create_nonce( 'wp_rest' ),
        'currentUserId' => get_current_user_id(),
    ] );
    wp_enqueue_script( 'affiliates-list-jobs' );
}
add_action( 'wp_enqueue_scripts', 'affiliates_list_jobs_enqueue_assets' );

// Render the jobs list shortcode.
function affiliates_list_jobs_widget( $atts ) {

    // Process shortcode attributes.
    $atts   = shortcode_atts( [ 'self' => false ], $atts, 'affiliates_portal_list_jobs' );
    $is_self = filter_var( $atts['self'], FILTER_VALIDATE_BOOLEAN );

    ob_start();
    include plugin_dir_path( __FILE__ ) . '../templates/affiliates-list-jobs.php';
    return ob_get_clean();
}
add_shortcode( 'affiliates_portal_list_jobs', 'affiliates_list_jobs_widget' );