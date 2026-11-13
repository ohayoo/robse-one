<?php
/** A reversible, opt-in presentation mode. Never create or publish content. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function robse_one_home_page() {
	if ( 'page' !== get_option( 'show_on_front' ) ) { return null; }
	$page = get_post( (int) get_option( 'page_on_front' ) );
	return $page && 'page' === $page->post_type && 'publish' === $page->post_status ? $page : null;
}

function robse_one_content_home_template( $template, $id, $type ) {
	if ( 'wp_template' !== $type || get_stylesheet() . '//front-page' !== $id || ! $template || ! get_theme_mod( 'robse_one_content_home', false ) || ! robse_one_home_page() ) { return $template; }
	// A user-saved template always wins over the opt-in file template.
	if ( ! empty( $template->wp_id ) || 'custom' === $template->source ) { return $template; }
	$path = get_theme_file_path( 'templates/homepage-content.html' );
	if ( ! is_readable( $path ) ) { return $template; }
	$template = clone $template;
	$template->content = file_get_contents( $path ); // Local theme file only.
	return $template;
}
add_filter( 'get_block_template', 'robse_one_content_home_template', 10, 3 );
add_filter( 'get_block_file_template', 'robse_one_content_home_template', 10, 3 );
add_filter( 'get_block_templates', function ( $templates, $query, $type ) {
	foreach ( $templates as $index => $template ) {
		$templates[ $index ] = robse_one_content_home_template( $template, $template->id, $type );
	}
	return $templates;
}, 10, 3 );

add_action( 'admin_menu', function () {
	add_theme_page( __( 'ROBSE ONE setup', 'robse-one' ), __( 'ROBSE ONE setup', 'robse-one' ), 'edit_theme_options', 'robse-one-setup', 'robse_one_render_setup' );
} );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'edit_theme_options' ) || ! isset( $_GET['page'] ) || 'robse-one-setup' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) { return; }
	settings_errors( 'robse_one_home_mode' );
} );

add_action( 'admin_post_robse_one_home_mode', function () {
	if ( ! current_user_can( 'edit_theme_options' ) ) { wp_die( esc_html__( 'You cannot change this setting.', 'robse-one' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'robse_one_home_mode' );
	$enable = isset( $_POST['mode'] ) && 'content' === sanitize_key( wp_unslash( $_POST['mode'] ) );
	$page = robse_one_home_page();
	$template = get_block_template( get_stylesheet() . '//front-page' );
	$ready = $page && '' !== trim( $page->post_content ) && $template && empty( $template->wp_id ) && 'custom' !== $template->source;
	if ( $enable && ! $ready ) {
		add_settings_error( 'robse_one_home_mode', 'robse_one_home_blocked', __( 'No change was made. Select a published homepage with content. A customized Front Page template must be reviewed separately.', 'robse-one' ), 'error' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( admin_url( 'themes.php?page=robse-one-setup&settings-updated=true' ) ); exit;
	}
	set_theme_mod( 'robse_one_content_home', $enable );
	add_settings_error( 'robse_one_home_mode', 'robse_one_home_saved', __( 'Homepage display setting saved.', 'robse-one' ), 'updated' );
	set_transient( 'settings_errors', get_settings_errors(), 30 );
	wp_safe_redirect( admin_url( 'themes.php?page=robse-one-setup&settings-updated=true' ) ); exit;
} );

function robse_one_render_setup() {
	if ( ! current_user_can( 'edit_theme_options' ) ) { return; }
	$page = robse_one_home_page();
	$template = get_block_template( get_stylesheet() . '//front-page' );
	$custom = $template && ( ! empty( $template->wp_id ) || 'custom' === $template->source );
	$mode = get_theme_mod( 'robse_one_content_home', false );
	$ready = $page && '' !== trim( $page->post_content ) && ! $custom;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'ROBSE ONE setup', 'robse-one' ); ?></h1>
		<p><?php esc_html_e( 'Start with a page, connect your contact method, then check the site before publishing. Updating the theme does not switch your existing homepage.', 'robse-one' ); ?></p>
		<?php robse_one_render_corporate_setup(); ?>

		<h2><?php esc_html_e( '1. Choose where the homepage is edited', 'robse-one' ); ?></h2>
		<p><?php esc_html_e( 'Page content mode displays the page selected in Settings > Reading. The classic starter is edited in the Site Editor. A saved custom Front Page template always takes priority.', 'robse-one' ); ?></p>
		<p><a class="button" href="<?php echo esc_url( admin_url( 'options-reading.php' ) ); ?>"><?php esc_html_e( 'Select the homepage', 'robse-one' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>"><?php esc_html_e( 'Create a page with patterns', 'robse-one' ); ?></a></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="robse_one_home_mode">
			<?php wp_nonce_field( 'robse_one_home_mode' ); ?>
			<p><label><input type="radio" name="mode" value="legacy" <?php checked( ! $mode ); ?>> <?php esc_html_e( 'Classic starter (Site Editor)', 'robse-one' ); ?></label></p>
			<p><label><input type="radio" name="mode" value="content" <?php checked( $mode ); ?> <?php disabled( ! $ready ); ?>> <?php esc_html_e( 'Selected page content (Page Editor)', 'robse-one' ); ?></label></p>
			<?php if ( ! $ready ) : ?><p><?php esc_html_e( 'To enable page content mode, first publish a page with content and select it as the homepage. Custom templates are not overwritten.', 'robse-one' ); ?></p><?php endif; ?>
			<?php submit_button( __( 'Save homepage display', 'robse-one' ), 'secondary' ); ?>
		</form>
		<p><a class="button button-primary" href="<?php echo esc_url( $mode && $page && ! $custom ? get_edit_post_link( $page->ID, 'raw' ) : admin_url( 'site-editor.php' ) ); ?>"><?php esc_html_e( 'Edit the current homepage', 'robse-one' ); ?></a></p>
		<h2><?php esc_html_e( '2. Set your colors and logo', 'robse-one' ); ?></h2>
		<p><?php esc_html_e( 'Open Styles in the Site Editor and choose Default, Editorial or Midnight. Add your logo in the header. Text, surfaces and cards follow the palette; check contrast after changing colors.', 'robse-one' ); ?></p>
		<h2><?php esc_html_e( '3. Connect a contact method', 'robse-one' ); ?></h2>
		<p><?php esc_html_e( 'Replace sample phone numbers with a real tel: link, or insert your form plugin block. Link every contact button to the actual contact section or page. A contact pattern does not send messages.', 'robse-one' ); ?></p>
		<h2><?php esc_html_e( '4. Prepare the handover', 'robse-one' ); ?></h2>
		<p><?php esc_html_e( 'Give the update owner permission to edit the homepage. Page content mode lets an Editor update its text without access to site-wide templates. CMS menu settings do not change permissions or lock the layout.', 'robse-one' ); ?></p>
		<h2><?php esc_html_e( '5. Check before publishing', 'robse-one' ); ?></h2>
		<ul>
			<li><?php esc_html_e( 'Replace every sample name, phone number, price, opening hour and testimonial with verified information.', 'robse-one' ); ?></li>
			<li><?php esc_html_e( 'Check every button, phone link, form submission and notification email.', 'robse-one' ); ?></li>
			<li><?php esc_html_e( 'Check mobile navigation, long titles, empty search results and keyboard focus.', 'robse-one' ); ?></li>
			<li><?php esc_html_e( 'Preview as the update owner and confirm they can find and save the right page.', 'robse-one' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'Optional: ROBSE ONE Blocks adds a CTA and client menu guide. ROBSE ONE AI creates text outlines and editable drafts; it does not choose images, build a complete website or connect a form.', 'robse-one' ); ?></p>
	</div>
	<?php
}
