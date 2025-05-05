<?php
/**
 * Shortcode for a custom login page.
 * 
 * This widget displays a custom login form with a dropdown for companies and a password input.
 * The dropdown is populated with display names of users with the custom role 'affiliate'.
 */
function affiliates_portal_login_shortcode( $atts ) {
    
    $error = '';
    
    // Process the form submission using a nonce to verify and secure the request
    if ( isset( $_POST['affiliates_login_nonce'] ) && wp_verify_nonce( $_POST['affiliates_login_nonce'], 'affiliates_portal_login' ) ) {
        
        $affiliate_login = sanitize_text_field( $_POST['affiliate_login'] );
        $password = $_POST['affiliates_password']; // let wp_signon() handle password sanitization
        
        if ( empty( $affiliate_login ) ) {
            $error = 'Please select a company from the dropdown.';
        } else {
            $user = get_user_by( 'login', $affiliate_login );
            if ( ! $user ) {
                $error = 'User not found.';
            } else {
                $creds = array(
                    'user_login'    => $user->user_login,
                    'user_password' => $password,
                    'remember'      => true,
                );
                $user = wp_signon( $creds, false );
                
                if ( is_wp_error( $user ) ) {
                    $error = $user->get_error_message();
                } else {
                    // Ensure cookies and current user are set.
                    wp_set_current_user( $user->ID );
                    wp_set_auth_cookie( $user->ID, true );
                    do_action( 'wp_login', $user->user_login, $user );
                    
                    // Redirect AFTER successful login
                    wp_safe_redirect( home_url() ); 
                    exit;
                }
            }
        }
    }

    // NOW, check if the user is logged in (and didn't just log out) and redirect if they shouldn't be here.
    // This runs if the login form wasn't submitted OR if the login attempt failed.
    if ( is_user_logged_in() && ! isset( $_GET['loggedout'] ) ) {
        if ( ! current_user_can( 'administrator' ) ) {
            wp_safe_redirect( home_url() );
            exit;
        }
    }
    
    // Fetch all users with the 'affiliate' role.
    $affiliates = get_users( array( 'role' => 'affiliate' ) );
    
    ob_start();
    // Pass $error to the template scope
    include plugin_dir_path( __FILE__ ) . '../templates/affiliates-login-form.php'; 
    return ob_get_clean();
}
add_shortcode( 'affiliates_portal_login', 'affiliates_portal_login_shortcode' );
