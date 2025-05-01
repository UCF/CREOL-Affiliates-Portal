<div class="container" style="width:50%; min-width: 300px; max-width: 100%;">
    <div class="row justify-content-center">
            <div class="col-md-12 ">
                <div class="row justify-content-center">
                    <img class="w-50" src="https://creol.ucf.edu/wp-content/uploads/sites/2/2024/10/CREOL-Logo-2024-e1744228032859.png" alt="CREOL Logo" class="img-fluid"/>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-12 text-center">
                <div class="row justify-content-center">
                    <p class="w-75">Welcome to the CREOL Affiliates Portal. Please login to access your account.</p>
                </div>
            </div>
        </div>
        <form method="post">
            <div class="row">
                <div class="col-md-12">
                    <label class="mb-2 w-100" for="affiliate_login">Company:</label>
                    <br/>
                    <select class="mb-2 w-100 custom-select" name="affiliate_login" id="affiliate_login" required>
                        <option value="">Select a Company</option>
                        <br/>
                        <?php if ( ! empty( $affiliates ) ) : ?>
                            <?php foreach ( $affiliates as $affiliate ) : ?>
                                <option value="<?php echo esc_attr( $affiliate->user_login ); ?>">
                                    <?php echo esc_html( $affiliate->display_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="mb-2 w-100" for="affiliates_password">Password:</label>
                    <br/>
                    <input class="mb-2 w-100 form-control" placeholder="Password" type="password" name="affiliates_password" id="affiliates_password" required/>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?php wp_nonce_field( 'affiliates_portal_login', 'affiliates_login_nonce' ); ?>
                    <input class="mb-2 w-100 btn btn-primary" type="submit" value="Login" class="btn btn-primary"/>
                </div>
            </div>
        </form>
        <div class="row text-center">
            <div class="col-md-12">
                <div class="row justify-content-center">
                    <?php if ( ! empty( $error ) ) : ?>
                        <p class="text-danger">Your credentials are incorrect. Please try again.</p>
                        <?php
                        // Output the error message to the console for debugging.
                        echo '<script>console.error("' . esc_js( $error ) . '");</script>';

                        // Clear the error message.
                        $error = '';
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="row text-center">
            <div class="col-md-12">
                <p>Having difficulties logging in? <a href="mailto:creolweb@ucf.edu">Contact Us.</a></p>
            </div>
        </div>
        <div class="row text-center">
            <div class="col-md-12">
                <p><strong><a href="https://creol.ucf.edu">Back to CREOL</a></strong></p>
            </div>
        </div>
    </div>
</div>