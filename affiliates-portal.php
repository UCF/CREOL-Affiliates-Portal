<?php
/**
 * Affiliates Portal Plugin
 *
 * @wordpress-plugin
 * Plugin Name: Affiliates Portal Plugin
 * Description: Shortcode for user-facing CRUD operations on affiliate jobs and events using JWT-authorized WP REST API calls.
 * Version:     1.0.0
 * Author:      Gage Notarigacomo
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Register the Job CPT
require_once plugin_dir_path( __FILE__ ) . 'includes/affiliates-cpt.php';

// Register custom REST endpoints (if you still need them)
require_once plugin_dir_path( __FILE__ ) . 'api/affiliates-rest-controller.php';

// Shortcodes for listing and creating jobs via REST
require_once plugin_dir_path( __FILE__ ) . 'includes/affiliates-list-jobs-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/affiliates-create-job-shortcode.php';

// Shortcode for the new public job-submission form
require_once plugin_dir_path( __FILE__ ) . 'includes/job-submission-shortcode.php';

// Hook your REST controller
add_action( 'rest_api_init', function() {
    $controller = new Affiliates_REST_Controller();
    $controller->register_routes();
} );
