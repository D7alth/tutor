<form method="post" enctype="multipart/form-data">
	<?php wp_nonce_field( tutor()->nonce_action, tutor()->nonce ); ?>
    <input type="hidden" value="tutor_register_student" name="tutor_action"/>

    <?php
    $errors = apply_filters('tutor_student_register_validation_errors', array());
    if (is_array($errors) && count($errors)){
        echo '<div class="tutor-alert-warning"><ul class="tutor-required-fields">';
        foreach ($errors as $error_key => $error_value){
            echo "<li>{$error_value}</li>";
        }
        echo '</ul></div>';
    }
    ?>

    <div class="tutor-form-row">
        <div class="tutor-form-col-6">
            <div class="tutor-form-group">
                <label>
					<?php _e('First Name', 'tutor'); ?>
                </label>

                <input type="text" name="first_name" value="<?php echo tutor_utils()->input_old('first_name'); ?>" placeholder="<?php _e('First Name', 'tutor'); ?>">
            </div>
        </div>

        <div class="tutor-form-col-6">
            <div class="tutor-form-group">
                <label>
					<?php _e('Last Name', 'tutor'); ?>
                </label>

                <input type="text" name="last_name" value="<?php echo tutor_utils()->input_old('last_name'); ?>" placeholder="<?php _e('Last Name', 'tutor'); ?>">
            </div>
        </div>
    </div>

    <div class="tutor-form-row">
        <div class="tutor-form-col-4">
            <div class="tutor-form-group">
                <label>
					<?php _e('E-Mail', 'tutor'); ?>
                </label>

                <input type="text" name="email" value="<?php echo tutor_utils()->input_old('email'); ?>" placeholder="<?php _e('E-Mail', 'tutor'); ?>">
            </div>
        </div>

        <div class="tutor-form-col-4">
            <div class="tutor-form-group">
                <label>
					<?php _e('User Name', 'tutor'); ?>
                </label>

                <input type="text" name="user_login" class="tutor_user_name" value="<?php echo tutor_utils()->input_old('user_login'); ?>" placeholder="<?php _e('User Name', 'tutor'); ?>">
            </div>
        </div>


        <div class="tutor-form-col-4">
            <div class="tutor-form-group">
                <label>
				    <?php _e('Phone Number', 'tutor'); ?>
                </label>

                <input type="text" name="phone_number" value="<?php echo tutor_utils()->input_old('phone_number'); ?>" placeholder="<?php _e('Phone Number', 'tutor'); ?>">
            </div>
        </div>

    </div>

    <div class="tutor-form-row">
        <div class="tutor-form-col-6">
            <div class="tutor-form-group">
                <label>
					<?php _e('Password', 'tutor'); ?>
                </label>

                <input type="password" name="password" value="<?php echo tutor_utils()->input_old('password'); ?>" placeholder="<?php _e('Password', 'tutor'); ?>">
            </div>
        </div>

        <div class="tutor-form-col-6">
            <div class="tutor-form-group">
                <label>
					<?php _e('Password confirmation', 'tutor'); ?>
                </label>

                <input type="password" name="password_confirmation" value="<?php echo tutor_utils()->input_old('password_confirmation'); ?>" placeholder="<?php _e('Password Confirmation', 'tutor'); ?>">
            </div>
        </div>
    </div>

    <div class="tutor-form-row">
        <div class="tutor-form-col-12">
            <div class="tutor-form-group">
                <label>
					<?php _e('Bio', 'tutor'); ?>
                </label>

                <textarea name="tutor_profile_bio"><?php echo tutor_utils()->input_old('tutor_profile_bio'); ?></textarea>
            </div>
        </div>
    </div>

    <div class="tutor-form-row">
        <div class="tutor-form-col-12">
            <div class="tutor-form-group">
                <button type="submit" name="tutor_register_student_btn" value="register"><?php _e('Register', 'tutor'); ?></button>
            </div>
        </div>
    </div>

</form>