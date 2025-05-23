<?php
// 1) Public shortcode for the job-submission form
function ap_public_job_form_shortcode() {
  ob_start(); ?>
  <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST">
    <?php wp_nonce_field('ap_submit_job','ap_submit_job_nonce'); ?>
    <input type="hidden" name="action" value="ap_submit_job">

    <p><label>Company:<br>
      <input type="text" name="company_name" required>
    </label></p>

    <p><label>Job Title:<br>
      <input type="text" name="job_title" required>
    </label></p>

    <p><label>Location:<br>
      <input type="text" name="location" required>
    </label></p>

    <fieldset>
      <legend>Job Type:</legend>
      <label><input type="checkbox" name="job_type[]" value="Full-time"> Full-time</label><br>
      <label><input type="checkbox" name="job_type[]" value="Part-time"> Part-time</label><br>
      <label><input type="checkbox" name="job_type[]" value="Fellowship"> Fellowship</label><br>
      <label><input type="checkbox" name="job_type[]" value="Internship"> Internship</label>
    </fieldset>

    <p><label>Job Description:<br>
      <textarea name="job_description" rows="6" required></textarea>
    </label></p>

    <p><label>Link to Apply:<br>
      <input type="url" name="apply_link">
    </label></p>

    <p><label>Contact:<br>
      <input type="text" name="contact" required>
    </label></p>

    <p><label>
      <input type="checkbox" name="is_affiliate" value="1">
      Check if your company is a CREOL Industrial Affiliate
    </label></p>

    <p><label>
      <input type="checkbox" name="captcha_confirm" required>
      CAPTCHA (I am not a robot)
    </label></p>

    <p><button type="submit">Submit Job</button></p>
  </form>
  <?php
  return ob_get_clean();
}
add_shortcode('public_job_form','ap_public_job_form_shortcode');

// 2) Hook your handler for both logged-in and anonymous users
add_action('admin_post_nopriv_ap_submit_job','ap_handle_job_submission');
add_action('admin_post_ap_submit_job','ap_handle_job_submission');

function ap_handle_job_submission() {
  // a) Nonce check
  if ( ! isset($_POST['ap_submit_job_nonce']) ||
       ! wp_verify_nonce($_POST['ap_submit_job_nonce'],'ap_submit_job') ) {
    wp_die('Security check failed');
  }

  // b) CAPTCHA
  if ( ! isset($_POST['captcha_confirm']) ) {
    wp_die('Please confirm you are not a robot.');
  }

  // c) Sanitize inputs
  $company     = sanitize_text_field( $_POST['company_name'] );
  $title       = sanitize_text_field( $_POST['job_title'] );
  $location    = sanitize_text_field( $_POST['location'] );
  $description = wp_kses_post( $_POST['job_description'] );
  $apply_link  = esc_url_raw( $_POST['apply_link'] );
  $job_types   = isset($_POST['job_type'])
                   ? array_map('sanitize_text_field',(array)$_POST['job_type'])
                   : [];
  $contact     = sanitize_text_field( $_POST['contact'] );
  $is_aff      = isset($_POST['is_affiliate']) ? 1 : 0;

  // d) Insert as pending
  $post_id = wp_insert_post([
    'post_type'    => 'job',
    'post_title'   => $title,
    'post_content' => $description,
    'post_status'  => 'pending',
  ]);
  if ( is_wp_error($post_id) ) {
    wp_die('Error submitting job.');
  }

  // e) Save all meta
  update_post_meta( $post_id, 'company_name',  $company );
  update_post_meta( $post_id, 'location',      $location );
  update_post_meta( $post_id, 'job_type',      $job_types );
  update_post_meta( $post_id, 'apply_link',    $apply_link );
  update_post_meta( $post_id, 'contact',       $contact );
  update_post_meta( $post_id, 'is_affiliate',  $is_aff );

  // f) Email with “Edit in WordPress” button
  $director_email = 'affiliates@creol.ucf.edu';
  $edit_url       = admin_url("post.php?post={$post_id}&action=edit");

  $subject = 'New Job Submission Pending Review';
  $body  = '<p>A new job has been submitted and is awaiting your approval:</p>';
  $body .= '<p><strong>' . esc_html( $title ) . "</strong> (Job #{$post_id})</p>";
  $body .= '<p style="text-align:center;margin:30px 0;">'
         . '<a href="' . esc_url( $edit_url ) . '"'
         .   ' style="display:inline-block;padding:12px 24px;'
         .   'background-color:#0073aa;color:#ffffff;text-decoration:none;'
         .   'border-radius:4px;font-weight:bold;">'
         .   'Edit in WordPress'
         . '</a>'
         . '</p>';
  $body .= '<p>Or go to <a href="' . esc_url( $edit_url ) . '">Jobs → Pending</a> in the WP admin.</p>';

  $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
  wp_mail( $director_email, $subject, $body, $headers );

  // g) Redirect back with “thank you”
  wp_safe_redirect( add_query_arg('job_submitted','1', wp_get_referer() ) );
  exit;
}

