<?php
/**
 * Public Job Submission Shortcode with Google reCAPTCHA
  */

  defined( 'ABSPATH' ) || exit;

// Job submission form functionality


// 3) Register the public job-submission form shortcode
add_shortcode('public_job_form', 'ap_public_job_form_shortcode');

function ap_public_job_form_shortcode() {
    ob_start();
    wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr(RECAPTCHA_SITE_KEY), array(), null, true);
    // Show thank you message if redirected after submission
    if ( isset($_GET['job_submitted']) && $_GET['job_submitted'] == '1' ) {
    $form_url = remove_query_arg('job_submitted');
    echo '<div class="ap-job-thankyou" style="padding:2rem;background:#e6ffe6;border:1px solid #b2ffb2;margin-bottom:2rem;">
        <strong>Thank you!</strong> Your job has been submitted and is pending review.
    </div>
    <div style="margin-bottom:2rem;">
        <a href="' . esc_url($form_url) . '">
            <button type="button" class="btn btn-secondary">Submit Another Job Posting</button>
        </a>
    </div>';
    return ob_get_clean();
}
    ?>
      <style>
    .ap-job-form label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

    .ap-job-form input[type="text"],
    .ap-job-form input[type="url"],
    .ap-job-form textarea {
        display: block;
        width: 95%;         /* Make fields nearly as wide as the form */
        max-width: 650px;   /* Or whatever max you want */
        min-width: 200px;
        box-sizing: border-box;
        margin-bottom: 1rem;
        height: 2.5rem;
        padding: 0.5rem;
        font-size: 1rem;
    }

    .ap-job-form textarea {
        min-height: 8rem;
        resize: vertical;
        height: auto;
    }

    @media (max-width: 800px) {
        .ap-job-form input[type="text"],
        .ap-job-form input[type="url"],
        .ap-job-form textarea {
            width: 100%;
            max-width: 100%;
        }
    }
    </style>
    <div >
        <form class="ap-job-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
            <?php wp_nonce_field('ap_submit_job', 'ap_submit_job_nonce'); ?>
            <input type="hidden" name="action" value="ap_submit_job">
            <input type="hidden" name="recaptcha_response" id="recaptchaResponse">
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
                    Location (Please specify if remote)<span style="color:red;">*</span><br>
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
            <p class="job-description-field">
                <label style="margin-bottom:0;">
                    Job Description<span style="color:red;">*</span>
                </label>
                <?php
                wp_editor(
                    '', // Default content
                    'job_description', // Unique ID
                    [
                        'textarea_name' => 'job_description',
                        'media_buttons' => false,
                        'teeny'        => true,
                        'quicktags'    => true,
                        'textarea_rows' => 10,
                        'editor_class' => 'required',
                        'editor_height' => 200,
                        'tinymce'      => [
                            'toolbar1' => 'bold,italic,bullist,numlist,link',
                            'toolbar2' => '',
                            'plugins'  => 'lists,paste,link'
                        ]
                    ]
                );
                ?>
                <span class="description-help" style="font-size:0.9rem;color:#666;">Use the editor to format your job description.</span>
            </p>
            <p>
                <label>
                    Link to Apply<span style="color:red;">*</span><br>
                    <input type="url" name="apply_link" required>
                </label>
            </p>
            <p>
                <label>
                    Contact<br>
                    <input type="text" name="contact">
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="is_affiliate" value="1">
                    Check if your company is a CREOL Industrial Affiliate
                </label>
            </p>
            <p>
                <label>
                    How long should this job posting stay active?<span style="color:red;">*</span><br>
                    <select name="job_duration" required>
                        <option value="60">60 days</option>
                        <option value="90">90 days</option>
                        <option value="120">120 days</option>
                    </select>
                </label>
            </p>
            <p>
                <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Submit Job</button>
            </p>
        
        </form>
        <div style="margin-top:2rem; text-align:right;">
            <p style="font-size:1.1rem; margin-bottom:0.5rem;"><strong>Need help?</strong></p>
            <a href="mailto:affiliates@creol.ucf.edu" class="btn btn-secondary">
                Contact Us
            </a>
        </div>
    </div>
 
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.ap-job-form');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    // Get description content
                    let desc = '';
                    if (window.tinyMCE) {
                        const editor = tinyMCE.get('job_description');
                        if (editor) {
                            desc = editor.getContent();
                        }
                    }
                    if (!desc) {
                        const textarea = document.getElementById('job_description');
                        if (textarea) desc = textarea.value.trim();
                    }
                    
                    if (!desc) {
                        alert('Please enter a job description.');
                        return;
                    }

                    try {
                        console.log('Executing reCAPTCHA...');
                        // Execute reCAPTCHA with site key from WordPress config
                        const token = await grecaptcha.execute('<?php echo esc_js(RECAPTCHA_SITE_KEY); ?>', {action: 'submit_job'});
                        console.log('Got token:', token ? 'yes' : 'no');
                        
                        if (!token) {
                            throw new Error('No reCAPTCHA token received');
                        }

                        document.getElementById('recaptchaResponse').value = token;
                        console.log('Submitting form...');
                        form.submit();
                    } catch (error) {
                        console.error('reCAPTCHA error:', error);
                        alert('Error validating form submission. Please try again.');
                    }
                });
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
    if (!isset($_POST['ap_submit_job_nonce']) 
        || !wp_verify_nonce($_POST['ap_submit_job_nonce'], 'ap_submit_job')) {
        wp_die('Security check failed.');
    }

    // b) Verify reCAPTCHA
    if (!isset($_POST['recaptcha_response'])) {
        wp_die('reCAPTCHA verification failed.');
    }

    $recaptcha_secret = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '';
    $recaptcha_verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret'   => $recaptcha_secret,
            'response' => $_POST['recaptcha_response'],
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ]
    ]);

    if (is_wp_error($recaptcha_verify)) {
        error_log('reCAPTCHA API Error: ' . $recaptcha_verify->get_error_message());
        wp_die('Error verifying reCAPTCHA.');
    }

    $recaptcha_result = json_decode(wp_remote_retrieve_body($recaptcha_verify));
    
    // Add detailed logging
    error_log('reCAPTCHA Response: ' . print_r($recaptcha_result, true));
    error_log('reCAPTCHA score: ' . (isset($recaptcha_result->score) ? $recaptcha_result->score : 'no score'));
    error_log('reCAPTCHA success: ' . ($recaptcha_result->success ? 'true' : 'false'));
    
    if (!$recaptcha_result->success || $recaptcha_result->score < 0.5) {
        wp_die('reCAPTCHA verification failed. Please try again.');
    }

    // Sanitize inputs
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
    $job_duration = isset($_POST['job_duration']) ? intval($_POST['job_duration']) : 60;
    // ...after other update_post_meta calls:
    update_post_meta( $post_id, 'job_duration', $job_duration );

    // d) Insert the job as pending
    // Assign only one category based on affiliate checkbox
    if ( $is_aff ) {
        $categories = [ get_cat_ID('Affiliate Job') ];
    } else {
        $categories = [ get_cat_ID('Portal Job') ];
    }


    // Create the post with the sanitized data
    $post_id = wp_insert_post([
        'post_type'    => 'portal_job', 
        'post_title'   => $title,
        'post_content' => $description,
        'post_category'=> $categories,
        'post_status'  => 'pending',
    ]);

    if ( is_wp_error( $post_id ) ) {
        wp_die( 'Error submitting job.' );
    }

    // e) Save all meta fields
    // Save all meta fields
    update_post_meta( $post_id, 'company_name', $company );
    update_post_meta( $post_id, 'location',     $location );
    update_post_meta( $post_id, 'job_type',     $job_types );
    update_post_meta( $post_id, 'apply_link',   $apply_link );
    update_post_meta( $post_id, 'contact',      $contact );
    update_post_meta( $post_id, 'is_affiliate', $is_aff );
    update_post_meta( $post_id, 'job_duration', $job_duration );

    add_action('save_post_portal_job', function($post_id, $post, $update) {
        // Only set on first creation, not on update
        if ($update) return;

        // Set the AE Post Template to your template's post ID
        update_post_meta($post_id, 'ae_template', 30479); 
    }, 10, 3);

    // f) Email with "Edit in WordPress" button
    $director_email = 'affiliates@creol.ucf.edu';
    $subject = 'New Job Portal Submission Pending Review';
    $edit_url = admin_url("post.php?post={$post_id}&action=edit");
    
    // Generate secure publish token
    $publish_token = generate_publish_token($post_id);
    $publish_url = admin_url("admin-post.php?action=ap_publish_job&post_id={$post_id}&token={$publish_token}");

$body  = '<p>A new job has been submitted and is awaiting your approval:</p>';
$body .= '<ul>';
$body .= '<li><strong>Company:</strong> ' . esc_html($company) . '</li>';
$body .= '<li><strong>Job Title:</strong> ' . esc_html($title) . '</li>';
$body .= '<li><strong>Location:</strong> ' . esc_html($location) . '</li>';
$body .= '<li><strong>Job Type:</strong> ' . esc_html(implode(', ', $job_types)) . '</li>';
$body .= '<li><strong>Description:</strong> ' . wp_kses_post($description) . '</li>';
$body .= '<li><strong>Apply Link:</strong> <a href="' . esc_url($apply_link) . '">' . esc_html($apply_link) . '</a></li>';
$body .= '<li><strong>Contact:</strong> ' . esc_html($contact) . '</li>';
$body .= '<li><strong>Affiliate Company:</strong> ' . ($is_aff ? 'Yes' : 'No') . '</li>';
$body .= '<li><strong>Posting Duration:</strong> ' . esc_html($job_duration) . ' days</li>';
$body .= '</ul>';
$body .= '<p style="text-align:center;margin:30px 0;">'
      . '<a href="' . esc_url($edit_url) . '"'
      .   ' style="display:inline-block;padding:12px 24px;'
      .   'background-color:#0073aa;color:#ffffff;'
      .   'text-decoration:none;border-radius:4px;'
      .   'font-weight:bold;margin-right:10px;">'
      .   'Edit in WordPress'
      . '</a>'
      . '<a href="' . esc_url($publish_url) . '"'
      .   ' style="display:inline-block;padding:12px 24px;'
      .   'background-color:#46b450;color:#ffffff;'
      .   'text-decoration:none;border-radius:4px;'
      .   'font-weight:bold;margin-left:10px;">'
      .   'Publish to Portal'
      . '</a>'
      . '</p>';
$body .= '<p>Or go to <a href="' . esc_url( admin_url('edit.php?post_type=portal_job&post_status=pending') )
      . '">Portal Jobs → Pending</a> in the WP admin.</p>';

$headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From: CREOL Job Board <creolweb@ucf.edu>'
];
wp_mail( $director_email, $subject, $body, $headers );

    // g) Redirect back with a thank-you flag
    wp_safe_redirect( add_query_arg( 'job_submitted', '1', wp_get_referer() ) );
    exit;
}
add_action('admin_post_ap_publish_job', function() {
    if (
        !isset($_GET['post_id']) ||
        !isset($_GET['token']) ||
        !current_user_can('publish_posts')
    ) {
        wp_die('Unauthorized', 403);
    }
    
    $post_id = intval($_GET['post_id']);
    $token = sanitize_text_field($_GET['token']);
    
    // Verify token
    $stored_token_data = get_post_meta($post_id, '_publish_token', true);
    if (
        empty($stored_token_data) ||
        $stored_token_data['token'] !== $token ||
        time() > $stored_token_data['expires']
    ) {
        wp_die('Invalid or expired publish link. Please use the Portal Jobs → Pending link to manage jobs.', 403);
    }
    
    // Token is valid, delete it so it can't be used again
    delete_post_meta($post_id, '_publish_token');
    
    // Publish the post
    wp_update_post([
        'ID' => $post_id,
        'post_status' => 'publish'
    ]);
    
    // Redirect to the post edit screen with a confirmation
    wp_safe_redirect(admin_url("post.php?post={$post_id}&action=edit&published=1"));
    exit;
});


// Add this near the top of the file, after the defined check
function generate_publish_token($post_id) {
    $token = wp_generate_password(32, false);
    $expiry = time() + (24 * 60 * 60); // 24 hours from now
    update_post_meta($post_id, '_publish_token', [
        'token' => $token,
        'expires' => $expiry
    ]);
    return $token;
}

/**
 * 1) Schedule a daily cron event on plugin activation
 */
register_activation_hook( __FILE__, 'ap_schedule_delete_old_jobs' );
function ap_schedule_delete_old_jobs() {
    if ( ! wp_next_scheduled( 'ap_delete_old_jobs' ) ) {
        wp_schedule_event( time(), 'daily', 'ap_delete_old_jobs' );
    }
}

register_deactivation_hook( __FILE__, 'ap_unschedule_delete_old_jobs' );
function ap_unschedule_delete_old_jobs() {
    wp_clear_scheduled_hook( 'ap_delete_old_jobs' );
}

/**
 * 2) Hook into 'ap_delete_old_jobs' to delete any posts in "Job" (and "Affiliate Job") older than 60 days.
 */
add_action( 'ap_delete_old_jobs', 'ap_delete_old_jobs_callback' );
function ap_delete_old_jobs_callback() {
    $job_cat_id = get_cat_ID( 'Portal Job' );
    $aff_cat_id = get_cat_ID( 'Affiliate Job' );

    $args = array(
        'post_type'      => 'portal_job',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'category__in'   => array_filter( array( $job_cat_id, $aff_cat_id ) ),
        'fields'         => 'ids',
    );

    $posts = get_posts( $args );
    if ( ! empty( $posts ) ) {
        foreach ( $posts as $post_id ) {
            $duration = intval( get_post_meta( $post_id, 'job_duration', true ) );
            if ( ! $duration ) {
                $duration = 60; // Default if not set
            }
            $post_date = get_post_field( 'post_date', $post_id );
            $expire_time = strtotime( $post_date . " +{$duration} days" );
            if ( time() > $expire_time ) {
                wp_delete_post( $post_id, true );
            }
        }
    }
}
function apply_link_shortcode() {
    $url = get_post_meta( get_the_ID(), 'apply_link', true );
    if ( ! empty( $url ) ) {
        return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">
                    <button type="button" class="btn btn-primary">Link to Apply</button>
                </a>';
    }
    return '';
}
add_shortcode( 'apply_link', 'apply_link_shortcode' );


