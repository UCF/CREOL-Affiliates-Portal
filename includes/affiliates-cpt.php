<?php

// Function to register custom post types such as the "Job" CPT
function register_custom_post_types() {

    /**
     * Array of labels for the custom post type.
     * 
     * The second parameter in the __() function, 'affiliates-portal', is the text domain for translation.
     * It is used to make the strings translatable and should match the text domain defined in the plugin or theme.
     */
    $labels = array(
        'name' => __( 'Jobs', 'affiliates-portal' ),
        'singular_name' => __( 'Job', 'affiliates-portal' ),
        'menu_icon' => 'dashicons-briefcase',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_in_rest' => true, // This enables support for the WordPress REST API
        'supports' => array( 'title', 'editor', 'author', 'custom-fields' ),
        'has_archive' => true,
    );

    // Register the "Job" CPT
    register_post_type( 'job', $args );

    // Register any additional CPTs following the same pattern above
}
add_action( 'init', 'register_custom_post_types' );