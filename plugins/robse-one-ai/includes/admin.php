<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function () {
	add_menu_page(
		__( 'ROBSE ONE AI', 'robse-one-ai' ),
		__( 'ROBSE ONE AI', 'robse-one-ai' ),
		'edit_pages',
		'robse-one-ai',
		'robse_one_ai_render_page',
		'dashicons-edit-page',
		58
	);
	add_submenu_page( 'robse-one-ai', __( 'AI settings', 'robse-one-ai' ), __( 'AI settings', 'robse-one-ai' ), 'manage_options', 'robse-one-ai-settings', 'robse_one_ai_render_settings' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false === strpos( $hook, 'robse-one-ai' ) ) {
		return;
	}
	wp_enqueue_script( 'robse-one-ai-admin', plugins_url( '../assets/admin.js', __FILE__ ), array(), ROBSE_ONE_AI_VERSION, true );
	wp_localize_script(
		'robse-one-ai-admin',
		'robseOneAi',
		array(
			'apiUrl' => esc_url_raw( rest_url( 'robse-one-ai/v1/' ) ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'texts'  => array(
				'working' => __( 'Creating a page outline…', 'robse-one-ai' ),
				'creating' => __( 'Creating a draft…', 'robse-one-ai' ),
				'failed' => __( 'Something went wrong. Check the settings and your input.', 'robse-one-ai' ),
				'success' => __( 'Draft created.', 'robse-one-ai' ),
				'consent' => __( 'Please agree to sending your input to the external AI service.', 'robse-one-ai' ),
				'tooShort' => __( 'Enter at least 10 characters.', 'robse-one-ai' ),
				/* translators: %d: section number. */
				'section' => __( 'Section %d', 'robse-one-ai' ),
				'heading' => __( 'Heading', 'robse-one-ai' ),
				'body' => __( 'Body', 'robse-one-ai' ),
				'editDraft' => __( 'Edit the draft', 'robse-one-ai' ),
			),
		)
	);
	wp_enqueue_style( 'robse-one-ai-admin', plugins_url( '../assets/admin.css', __FILE__ ), array(), ROBSE_ONE_AI_VERSION );
} );

function robse_one_ai_render_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	?>
	<div class="wrap robse-one-ai">
		<h1><?php esc_html_e( 'ROBSE ONE AI page outline', 'robse-one-ai' ); ?></h1>
		<p><?php esc_html_e( 'Describe your business to get a page outline. Review and edit it as needed, then save it as a draft.', 'robse-one-ai' ); ?></p>
		<p class="notice inline notice-warning"><strong><?php esc_html_e( 'Your brief is sent to the OpenAI API. Do not include personal or confidential information, and run this only if you agree to send it.', 'robse-one-ai' ); ?></strong></p>
		<label for="robse-one-ai-brief"><strong><?php esc_html_e( 'What page do you want to create?', 'robse-one-ai' ); ?></strong></label>
		<textarea id="robse-one-ai-brief" rows="6" maxlength="4000" placeholder="<?php esc_attr_e( 'Example: A local builder offering custom homes with natural materials, and renovations.', 'robse-one-ai' ); ?>"></textarea>
		<label class="robse-ai-consent"><input type="checkbox" id="robse-one-ai-consent"> <?php esc_html_e( 'I agree that my input will be sent to the OpenAI API.', 'robse-one-ai' ); ?></label>
		<p><button class="button button-primary" id="robse-one-ai-generate"><?php esc_html_e( 'Create outline', 'robse-one-ai' ); ?></button></p>
		<div id="robse-one-ai-status" role="status" aria-live="polite"></div>
		<div id="robse-one-ai-preview" hidden>
			<h2><?php esc_html_e( 'Review and edit the outline', 'robse-one-ai' ); ?></h2>
			<label><?php esc_html_e( 'Page title', 'robse-one-ai' ); ?><input type="text" id="robse-one-ai-title" class="regular-text"></label>
			<div id="robse-one-ai-sections"></div>
			<button class="button button-primary" id="robse-one-ai-create"><?php esc_html_e( 'Save as draft', 'robse-one-ai' ); ?></button>
		</div>
	</div>
	<?php
}

function robse_one_ai_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['robse_one_ai_save'] ) ) {
		check_admin_referer( 'robse_one_ai_save_settings' );
		$model = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : 'gpt-5';
		$model = preg_match( '/^[a-zA-Z0-9._-]{1,100}$/', $model ) ? $model : 'gpt-5';
		update_option( 'robse_one_ai_settings', array( 'model' => $model ) );
		if ( ! defined( 'ROBSE_ONE_AI_API_KEY' ) && ! empty( $_POST['api_key'] ) ) {
			update_option( 'robse_one_ai_api_key', sanitize_text_field( wp_unslash( $_POST['api_key'] ) ), false );
		}
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'robse-one-ai' ) . '</p></div>';
	}
	$options = get_option( 'robse_one_ai_settings', array() );
	$model = ! empty( $options['model'] ) ? $options['model'] : 'gpt-5';
	$has_key = (bool) robse_one_ai_api_key();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'ROBSE ONE AI settings', 'robse-one-ai' ); ?></h1>
		<p><?php esc_html_e( 'The API key is stored on the server and never sent to the browser. If ROBSE_ONE_AI_API_KEY is defined in wp-config.php, it takes priority over the key in the database.', 'robse-one-ai' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'robse_one_ai_save_settings' ); ?>
			<table class="form-table"><tbody>
				<tr><th><label for="robse-one-ai-model"><?php esc_html_e( 'Model', 'robse-one-ai' ); ?></label></th><td><input class="regular-text" id="robse-one-ai-model" name="model" value="<?php echo esc_attr( $model ); ?>"><p class="description"><?php esc_html_e( 'Enter the name of a text model available in the OpenAI API.', 'robse-one-ai' ); ?></p></td></tr>
				<tr><th><label for="robse-one-ai-key"><?php esc_html_e( 'API key', 'robse-one-ai' ); ?></label></th><td><input class="regular-text" type="password" autocomplete="new-password" id="robse-one-ai-key" name="api_key" value="" placeholder="<?php echo esc_attr( $has_key ? '••••••••••••••••' : '' ); ?>" <?php disabled( defined( 'ROBSE_ONE_AI_API_KEY' ) ); ?>><p class="description"><?php echo $has_key ? esc_html__( 'A key is saved. Leave this blank to keep the current key.', 'robse-one-ai' ) : esc_html__( 'The key is never shown again on this screen.', 'robse-one-ai' ); ?></p></td></tr>
			</tbody></table>
			<input type="hidden" name="robse_one_ai_save" value="1">
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
