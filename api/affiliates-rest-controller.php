<?php

class Affiliates_REST_Controller extends WP_REST_Controller {
    public function __construct() {
        $this->namespace = 'affiliates/v1';
    }

    // Register the custom routes for the REST API
    public function register_routes() {

        // This endpoint is for getting all jobs using user IDs as a filter
        register_rest_route( $this->namespace, '/jobs', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_jobs' ),
            'permission_callback' => array( $this, 'get_jobs_permissions_check' ),
            'args' => array(
                'user_ids' => array(
                    'description' => 'One or more user IDs, comma-separated or array.',
                    'type'        => 'mixed', // Accept either string or array
                    'sanitize_callback' => null,
                ),
                'search' => array(
                    'description' => 'Search term to filter jobs by title or content.',
                    'type'        => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // This endpoint is for creating a new job
        register_rest_route( $this->namespace, '/jobs', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'create_job' ),
            'permission_callback' => array( $this, 'create_job_permissions_check'),
        ) );
        
        // This endpoint is for getting a specific job by ID
        register_rest_route( $this->namespace, '/jobs/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( $this, 'get_job' ),
            'permission_callback' => array( $this, 'get_job_permissions_check' ),
        ) );

        // This endpoint is for editing a specific job by ID
        register_rest_route( $this->namespace, '/jobs/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array( $this, 'edit_job' ),
            'permission_callback' => array( $this, 'edit_job_permissions_check' ),
        ) );

        // This endpoint is for deleting a specific job by ID
        register_rest_route( $this->namespace, '/jobs/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array( $this, 'delete_job' ),
            'permission_callback' => array( $this, 'delete_job_permissions_check' ),
        ) );
    }

    // Callback function to get all jobs using user IDs as a filter
    // Valid user_ids can be passed as a comma-separated string or an array
    // Eg: /api/affiliates/v1/jobs?user_ids=1,2,3 or /api/affiliates/v1/jobs?user_ids[]=1&user_ids[]=2&user_ids[]=3
    public function get_jobs( $request ) {
        $user_ids_param = $request->get_param( 'user_ids' );
        $user_ids = array();
        $search = $request->get_param( 'search' );
    
        if ( is_array( $user_ids_param ) ) {
            $user_ids = array_map( 'intval', $user_ids_param );
        } elseif ( is_string( $user_ids_param ) ) {
            $user_ids = array_map( 'intval', explode( ',', $user_ids_param ) );
        } elseif ( is_numeric( $user_ids_param ) ) {
            $user_ids = [ intval( $user_ids_param ) ];
        }
    
        $per_page = isset( $request['per_page'] ) ? intval( $request['per_page'] ) : 5;
        $page     = isset( $request['page'] ) ? max( 1, intval( $request['page'] ) ) : 1;
    
        $args = array(
            'post_type'      => 'job',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        );
    
        // Add user IDs to the query if present
        if ( ! empty( $user_ids ) ) {
            $args['author__in'] = $user_ids;
        }

        // Add search term to the query if present
        if ( ! empty( $search ) ) {
            $args['s'] = sanitize_text_field( $search );
        }
    
        $query = new WP_Query( $args );
        if ( ! $query->have_posts() ) {
            return rest_ensure_response( array( 'message' => 'No jobs found' ) );
        }
    
        $jobs = $query->posts;
    
        $data = array_map( function( $job ) {
            $author_id = $job->post_author;
            $author = get_user_by( 'id', $author_id );
        
            return [
                'id'      => $job->ID,
                'title'   => $job->post_title,
                'content' => $job->post_content,
                'contact' => get_post_meta( $job->ID, 'contact', true ),
                'author'  => [
                    'id'   => $author_id,
                    'name' => $author->display_name,
                ],
            ];
        }, $jobs );
        
        $response = rest_ensure_response( $data );
        // Add headers to expose pagination info
        $response->header( 'X-WP-Total', $query->found_posts );
        $response->header( 'X-WP-TotalPages', $query->max_num_pages );
        
        return $response;
    }


    // Callback function to create a new job
    public function create_job( $request ) {
        $required_fields = array( 'title', 'job_description', 'contact' );
        $missing_fields = array();

        // Check for missing fields
        foreach ( $required_fields as $field ) {
            if ( empty( $request[$field] ) ) {
                $missing_fields[] = $field;
            }
        }

        // If there are missing fields, return an error
        if ( ! empty( $missing_fields ) ) {
            return new WP_Error(
                'missing_fields',
                __( 'The following fields are missing: ', 'affiliates-portal' ) . implode( ', ', $missing_fields ),
                array( 'status' => 400 )
            );
        }

        // Use current user id if no author is provided
        $author_id = ! empty( $request['author_id'] ) ? intval( $request['author_id'] ) : get_current_user_id();

        $job_data = array(
            'post_title'   => sanitize_text_field( $request['title'] ),
            'post_content' => sanitize_textarea_field( $request['job_description'] ),
            'post_type'    => 'job',
            'post_status'  => 'publish',
            'post_author'  => $author_id,
        );

        $job_id = wp_insert_post( $job_data );

        if ( is_wp_error( $job_id ) ) {
            return new WP_Error( 'cant-create', __( 'Cannot create job', 'affiliates-portal' ), array( 'status' => 500 ) );
        }

        // Save the contact information as a custom field
        // Follow similar patterns for additional custom fields as needed
        if ( ! empty( $request['contact'] ) ) {
            update_post_meta( $job_id, 'contact', sanitize_text_field( $request['contact'] ) );
        }

        return rest_ensure_response( array( 'id' => $job_id, 'title' => $request['title'] ) );
    }

    // Callback function to get a specific job by ID
    public function get_job( $request ) {
        $job_id = $request['id'];

        $job = get_post( $job_id );

        if ( empty( $job ) || 'job' !== $job->post_type ) {
            return new WP_Error( 'job_not_found', __( 'Job not found', 'affiliates-portal' ), array( 'status' => 404 ) );
        }

        $data = array(
            'id'      => $job->ID,
            'title'   => $job->post_title,
            'author'  => get_the_author_meta( 'display_name', $job->post_author ),
            'job_description' => $job->post_content,
            'contact' => get_post_meta( $job->ID, 'contact', true ),
        );

        return rest_ensure_response( $data );
    }

    // Callback function to edit a specific job by ID
    public function edit_job( $request ) {
        $job_id = $request['id'];

        $job = get_post( $job_id );

        if ( empty( $job ) || 'job' !== $job->post_type ) {
            return new WP_Error( 'job_not_found', __( 'Job not found', 'affiliates-portal' ), array( 'status' => 404 ) );
        }

        $job_data = array();

        if ( ! empty( $request['title'] ) ) {
            $job_data['post_title'] = sanitize_text_field( $request['title'] );
        }

        if ( ! empty( $request['job_description'] ) ) {
            $job_data['post_content'] = sanitize_textarea_field( $request['job_description'] );
        }

        if ( ! empty( $request['contact'] ) ) {
            update_post_meta( $job_id, 'contact', sanitize_text_field( $request['contact'] ) );
        }

        $updated = wp_update_post( array_merge( array( 'ID' => $job_id ), $job_data ) );

        if ( is_wp_error( $updated ) ) {
            return new WP_Error( 'cant-update', __( 'Cannot update job', 'affiliates-portal' ), array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'id' => $job_id, 'title' => $request['title'] ) );
    }

    // Callback function to delete a specific job by ID
    public function delete_job( $request ) {
        $job_id = $request['id'];

        $job = get_post( $job_id );

        if ( empty( $job ) || 'job' !== $job->post_type ) {
            return new WP_Error( 'job_not_found', __( 'Job not found', 'affiliates-portal' ), array( 'status' => 404 ) );
        }

        $deleted = wp_delete_post( $job_id );

        if ( ! $deleted ) {
            return new WP_Error( 'cant-delete', __( 'Cannot delete job', 'affiliates-portal' ), array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'deleted' => true , 'message' => 'Deleted job of id ' . $request['id']) );
    }

    // Permission checks for each endpoint
    public function get_jobs_permissions_check( $request ) {
        return true;
    }

    public function create_job_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function get_job_permissions_check( $request ) {
        return true;
    }

    public function edit_job_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function delete_job_permissions_check( $request ) {
        return current_user_can( 'delete_posts' );
    }
}