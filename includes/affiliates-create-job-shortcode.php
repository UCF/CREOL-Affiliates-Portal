<?php
/**
 * Shortcode to display a form for creating a job.
 * 
 * This widget provides a form to create a new job via the REST API.
 */

/**
 * Enqueue the create job JavaScript assets and localize necessary data.
 */
function affiliates_create_job_enqueue_assets() {
    wp_register_script(
        'affiliates-create-job',
        plugins_url( '../assets/js/affiliates-create-job.js', __FILE__ ),
        [],
        '1.0.0',
        true
    );

    wp_localize_script( 'affiliates-create-job', 'affiliatesCreateJob', [
        'restUrl' => esc_url( rest_url( 'affiliates/v1/jobs' ) ),
        'nonce'   => wp_create_nonce( 'wp_rest' ),
    ]);

    wp_enqueue_script( 'affiliates-create-job' );
}
add_action( 'wp_enqueue_scripts', 'affiliates_create_job_enqueue_assets' );

/**
 * Render the create job form shortcode.
 */
function affiliates_create_job_widget() {
    // Redirect if not logged in
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/portal-login' ) );
        exit;
    }

    ob_start();
    include plugin_dir_path( __FILE__ ) . '../templates/affiliates-create-job-form.php';
    return ob_get_clean();
}
add_shortcode('affiliates_portal_create_job', 'affiliates_create_job_widget');