<?php
/**
 * Public Job Submission Shortcode with Google reCAPTCHA
 */

defined( 'ABSPATH' ) || exit;

// 1) Your reCAPTCHA keys
define( 'AP_RECAPTCHA_SITE_KEY',   '6LeTskYrAAAAALvjNde9MM1Nahvx-oZAWHA6dSY7' );
define( 'AP_RECAPTCHA_SECRET_KEY', '6LeTskYrAAAAABOzBXcawC_nHotM4OePHTbKru5P' );

// 2) Enqueue Google reCAPTCHA script on the front end
add_action('wp_enqueue_scripts','ap_enqueue_recaptcha_script');
function ap_enqueue_recaptcha_script(){
    wp_register_script(
        'google-recaptcha',
        'https://www.google.com/recaptcha/enterprise.js',
        [],
        null,
        true
    );
    wp_enqueue_script('google-recaptcha');
}

add_filter('script_loader_tag','ap_recaptcha_async_defer',10,3);
function ap_recaptcha_async_defer($tag,$handle,$src){
    if($handle==='google-recaptcha'){
        return '<script src="'.esc_url($src).'" async defer></script>';
    }
    return $tag;
}


// 3) Register the public job-submission form shortcode
add_shortcode('public_job_form', 'ap_public_job_form_shortcode');

function ap_public_job_form_shortcode() {
  ob_start(); ?>
  
  <style>
    /* Style input fields */
    .ap-job-form input[type="text"],
    .ap-job-form input[type="url"] {
      width: 100%;
      max-width: 60rem;
      height: 2rem;
      padding: 0.5rem;
      font-size: 1rem;
      box-sizing: border-box;
    }

    /* Style the textarea */
    .ap-job-form textarea {
      width: 100%;
      max-width: 100rem;
      min-height: 20rem;
      padding: 0.5rem;
      font-size: 1rem;
      box-sizing: border-box;
    }

    /* Labels & button */
    .ap-job-form label {
      font-size: 1rem;
    }

    .ap-job-form button {
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
    }
  </style>

<div style="width:100%; max-width:100rem; margin:auto;">
  <form class="ap-job-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" style="max-width:100rem; width:100%;">
    <?php wp_nonce_field('ap_submit_job', 'ap_submit_job_nonce'); ?>
    <input type="hidden" name="action" value="ap_submit_job">

    <p>
      <label>
        Company<span style="color:red;">*</span><br>
        <input type="text" name="company_name" required>
      </label>
    </p>

    <p>
      <label>
        Job Title<span style="color:red;">*</span><br>
        <input type="text" name="job_title" required>
      </label>
    </p>

    <p>
      <label>
        Location<span style="color:red;">*</span><br>
        <input type="text" name="location" required>
      </label>
    </p>

    <fieldset>
      <legend>Job Type:</legend>
      <label><input type="checkbox" name="job_type[]" value="Full-time"> Full-time</label><br>
      <label><input type="checkbox" name="job_type[]" value="Part-time"> Part-time</label><br>
      <label><input type="checkbox" name="job_type[]" value="Fellowship"> Fellowship</label><br>
      <label><input type="checkbox" name="job_type[]" value="Internship"> Internship</label>
    </fieldset>

    <p>
      <label>
        Job Description<span style="color:red;">*</span><br>
        <textarea name="job_description" required></textarea>
      </label>
    </p>

    <p>
      <label>
        Link to Apply<br>
        <input type="url" name="apply_link">
      </label>
    </p>

    <p>
      <label>
        Contact<span style="color:red;">*</span><br>
        <input type="text" name="contact" required>
      </label>
    </p>

    <p>
      <label>
        <input type="checkbox" name="is_affiliate" value="1">
        Check if your company is a CREOL Industrial Affiliate
      </label>
    </p>

    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr(AP_RECAPTCHA_SITE_KEY); ?>"></div>

    <p>
      <button type="submit">Submit Job</button>
    </p>
  </form>
  </div>
  
  <script>
  document.addEventListener("DOMContentLoaded", function () {
    const textarea = document.querySelector(".ap-job-form textarea");
    if (textarea) {
      textarea.style.width = "100%";
      textarea.style.maxWidth = "100rem";
    }

    const form = document.querySelector(".ap-job-form");
    if (form) {
      form.style.width = "100%";
      form.style.maxWidth = "100rem";
    }

    const container = document.querySelector(".elementor-widget-container");
    if (container) {
      container.style.maxWidth = "100rem";
      container.style.width = "100%";
    }
  });
</script>


  <?php
  return ob_get_clean();
  
}




// 4) Handle form submissions (both logged-in and anonymous)
add_action( 'admin_post_nopriv_ap_submit_job', 'ap_handle_job_submission' );
add_action( 'admin_post_ap_submit_job',      'ap_handle_job_submission' );
function ap_handle_job_submission() {
    // a) Nonce verification
    if ( ! isset( $_POST['ap_submit_job_nonce'] )
      || ! wp_verify_nonce( $_POST['ap_submit_job_nonce'], 'ap_submit_job' ) ) {
        wp_die( 'Security check failed.' );
    }

    // b) Verify Google reCAPTCHA
    $recap = sanitize_text_field( $_POST['g-recaptcha-response'] ?? '' );
    if ( empty( $recap ) ) {
        wp_die( 'Please complete the CAPTCHA.' );
    }

    $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret'   => AP_RECAPTCHA_SECRET_KEY,
            'response' => $recap,
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ],
    ] );

    if ( is_wp_error( $response ) ) {
        wp_die( 'CAPTCHA verification error. Please try again.' );
    }

    $result = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $result['success'] ) ) {
        wp_die( 'CAPTCHA verification failed. Please try again.' );
    }

    // c) Sanitize other inputs
    $company     = sanitize_text_field( $_POST['company_name'] );
    $title       = sanitize_text_field( $_POST['job_title'] );
    $location    = sanitize_text_field( $_POST['location'] );
    $description = wp_kses_post( $_POST['job_description'] );
    $apply_link  = esc_url_raw( $_POST['apply_link'] );
    $job_types   = isset( $_POST['job_type'] )
                   ? array_map( 'sanitize_text_field', (array) $_POST['job_type'] )
                   : [];
    $contact     = sanitize_text_field( $_POST['contact'] );
    $is_aff      = isset( $_POST['is_affiliate'] ) ? 1 : 0;

    // d) Insert the job as pending
    $post_id = wp_insert_post([
        'post_type'    => 'post',
        'post_title'   => $title,
        'post_content' => $description,
        'post_category'=> [ get_cat_ID('Job') ], // Assign to "Job" category
        'post_status'  => 'pending',
    ]);

    if ( is_wp_error( $post_id ) ) {
        wp_die( 'Error submitting job.' );
    }

    // e) Save all meta fields
    update_post_meta( $post_id, 'company_name', $company );
    update_post_meta( $post_id, 'location',     $location );
    update_post_meta( $post_id, 'job_type',     $job_types );
    update_post_meta( $post_id, 'apply_link',   $apply_link );
    update_post_meta( $post_id, 'contact',      $contact );
    update_post_meta( $post_id, 'is_affiliate', $is_aff );

    // f) Email with “Edit in WordPress” button
    $director_email = 'affiliates@creol.ucf.edu';
    $edit_url       = admin_url( "post.php?post={$post_id}&action=edit" );

    $subject = 'New Job Submission Pending Review';
    $body  = '<p>A new job has been submitted and is awaiting your approval:</p>';
    $body .= '<p><strong>' . esc_html( $title ) . "</strong> (Job #{$post_id})</p>";
    $body .= '<p style="text-align:center;margin:30px 0;">'
           . '<a href="' . esc_url( $edit_url ) . '"'
           .   ' style="display:inline-block;padding:12px 24px;'
           .   'background-color:#0073aa;color:#ffffff;'
           .   'text-decoration:none;border-radius:4px;'
           .   'font-weight:bold;">'
           .   'Edit in WordPress'
           . '</a>'
           . '</p>';
    $body .= '<p>Or go to <a href="' . esc_url( $edit_url )
           . '">Jobs → Pending</a> in the WP admin.</p>';

    $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
    wp_mail( $director_email, $subject, $body, $headers );

    // g) Redirect back with a thank-you flag
    wp_safe_redirect( add_query_arg( 'job_submitted', '1', wp_get_referer() ) );
    exit;
}
