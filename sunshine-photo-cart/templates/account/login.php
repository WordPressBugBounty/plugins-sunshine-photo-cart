<div id="sunshine--account--login-form">

	<?php if ( ! empty( $message ) ) { ?>
		<div id="sunshine--account--login-signup--header">
			<?php echo esc_html( $message ); ?>
		</div>
	<?php } ?>

	<div class="sunshine--account--login--title sunshine--modal--title"><?php _e( 'Login', 'sunshine-photo-cart' ); ?></div>

	<form method="post" action="" class="sunshine--form--fields">
		<?php wp_nonce_field( 'sunshine_login', 'sunshine_login' ); ?>
		<div class="sunshine--form--field">
			<label for="sunshine-login-email"><?php _e( 'E-mail address', 'sunshine-photo-cart' ); ?></label>
			<input type="email" name="sunshine_login_email" id="sunshine-login-email" required="required" />
		</div>
		<div class="sunshine--form--field">
			<label for="sunshine-login-password"><?php _e( 'Password', 'sunshine-photo-cart' ); ?></label>
			<input type="password" name="sunshine_login_password" id="sunshine-login-password" required="required" />
		</div>
		<div class="sunshine--form--field sunshine--form--field-submit">
			<button type="submit" class="button sunshine--button"><?php _e( 'Login', 'sunshine-photo-cart' ); ?></button>
			<div class="sunshine--form--field--desc sunshine--account--reset-password-toggle"><a href="#password" onclick="jQuery( '#sunshine--account--login-form, #sunshine--account--reset-password-form' ).toggle(); return false;"><?php _e( 'Lost password?', 'sunshine-photo-cart' ); ?></a></div>
		</div>
		<?php if ( ! empty( $_GET['redirect'] ) ) { ?>
			<input type="hidden" name="redirect" value="<?php echo esc_url( $_GET['redirect'] ); ?>" />
		<?php } ?>
	</form>

</div>

<div id="sunshine--account--reset-password-form" style="display: none;">
	<div class="sunshine--account--login--title sunshine--modal--title"><?php _e( 'Reset Password', 'sunshine-photo-cart' ); ?></div>
	<form method="post" action="" class="sunshine--form--fields">
		<?php wp_nonce_field( 'sunshine_reset_password_nonce', 'sunshine_reset_password_nonce' ); ?>
		<div class="sunshine--form--field">
			<label for="sunshine-reset-password-email"><?php _e( 'E-mail address', 'sunshine-photo-cart' ); ?></label>
			<input type="email" name="sunshine_reset_password_email" id="sunshine-reset-password-email" required="required" autocomplete="email" />
			<div class="sunshine--form--field--desc"><?php _e( 'An email will be sent to this address with instructions on how to reset your password', 'sunshine-photo-cart' ); ?></div>
		</div>
		<div class="sunshine--form--field sunshine--form--field-submit">
			<button type="submit" class="button sunshine--button"><?php _e( 'Get New Password', 'sunshine-photo-cart' ); ?></button>
			<div class="sunshine--form--field--desc sunshine--account--reset-password-toggle"><a href="#password" onclick="jQuery( '#sunshine--account--login-form, #sunshine--account--reset-password-form' ).toggle(); return false;"><?php _e( 'Back to login', 'sunshine-photo-cart' ); ?></a></div>
		</div>
	</form>
</div>
