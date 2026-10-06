<?php
/**
 * Plugin Name: ROBSE ONE Blocks
 * Description: A CTA block, a menu profile for non-administrators and a dashboard editing guide for ROBSE ONE sites.
 * Version: 0.3.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: robse-one-blocks
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function robse_one_blocks_init() {
	load_plugin_textdomain( 'robse-one-blocks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	register_block_type( __DIR__ . '/blocks/cta' );
	register_block_type( __DIR__ . '/blocks/contact' );
	wp_set_script_translations( 'robse-one-cta-editor-script', 'robse-one-blocks', __DIR__ . '/languages' );
	wp_set_script_translations( 'robse-one-contact-editor-script', 'robse-one-blocks', __DIR__ . '/languages' );
}
add_action( 'init', 'robse_one_blocks_init' );

function robse_one_blocks_default_cms_settings() {
	return array(
		'enabled' => 0,
		'menus'   => array( 'index.php', 'edit.php', 'edit.php?post_type=page', 'upload.php', 'profile.php' ),
	);
}
register_activation_hook( __FILE__, function () {
	add_option( 'robse_one_cms_mode', robse_one_blocks_default_cms_settings(), '', false );
} );

function robse_one_blocks_get_cms_settings() {
	$settings = get_option( 'robse_one_cms_mode', null );
	return is_array( $settings ) ? $settings : robse_one_blocks_default_cms_settings();
}

/**
 * Register a reversible, administrator-configured menu profile for client roles.
 */
function robse_one_blocks_register_cms_settings() {
	register_setting(
		'robse_one_cms',
		'robse_one_cms_mode',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'robse_one_blocks_sanitize_cms_settings',
			'default'           => array( 'enabled' => 0, 'menus' => array( 'index.php', 'edit.php', 'edit.php?post_type=page', 'upload.php', 'profile.php' ) ),
		)
	);
}
add_action( 'admin_init', 'robse_one_blocks_register_cms_settings' );

function robse_one_blocks_get_cms_menu_choices() {
	$choices = array(
		'index.php'               => __( 'Dashboard', 'robse-one-blocks' ),
		'edit.php'                => __( 'Posts', 'robse-one-blocks' ),
		'edit.php?post_type=page' => __( 'Pages', 'robse-one-blocks' ),
		'upload.php'              => __( 'Media', 'robse-one-blocks' ),
		'edit-comments.php'       => __( 'Comments', 'robse-one-blocks' ),
		'profile.php'             => __( 'Profile', 'robse-one-blocks' ),
	);
	$post_types = get_post_types( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), 'objects' );
	foreach ( $post_types as $post_type ) {
		if ( ! $post_type->show_in_menu ) {
			continue;
		}
		$slug = is_string( $post_type->show_in_menu ) ? $post_type->show_in_menu : 'edit.php?post_type=' . $post_type->name;
		// translators: %s is the label of a public custom post type.
		$choices[ $slug ] = sprintf( __( 'Post type: %s', 'robse-one-blocks' ), $post_type->labels->name );
	}
	return $choices;
}

function robse_one_blocks_sanitize_cms_settings( $input ) {
	$choices = robse_one_blocks_get_cms_menu_choices();
	$submitted = array();
	if ( isset( $input['menus'] ) && is_array( $input['menus'] ) ) {
		foreach ( $input['menus'] as $menu ) {
			if ( is_string( $menu ) ) {
				$submitted[] = sanitize_text_field( $menu );
			}
		}
	}
	return array(
		'enabled' => ! empty( $input['enabled'] ) ? 1 : 0,
		'menus'   => array_values( array_intersect( array_keys( $choices ), $submitted ) ),
	);
}

function robse_one_blocks_add_cms_settings_page() {
	add_options_page(
		__( 'ROBSE ONE CMS mode', 'robse-one-blocks' ),
		__( 'ROBSE ONE CMS', 'robse-one-blocks' ),
		'manage_options',
		'robse-one-cms',
		'robse_one_blocks_render_cms_settings'
	);
}
add_action( 'admin_menu', 'robse_one_blocks_add_cms_settings_page' );

function robse_one_blocks_render_cms_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = robse_one_blocks_get_cms_settings();
	$enabled = ! empty( $settings['enabled'] );
	$selected = isset( $settings['menus'] ) && is_array( $settings['menus'] ) ? $settings['menus'] : array();
	$labels = robse_one_blocks_get_cms_menu_choices();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'ROBSE ONE CMS mode', 'robse-one-blocks' ); ?></h1>
		<p><?php esc_html_e( 'Choose the standard menus and public custom post types shown to users who are not administrators. Menus added by other plugins are not affected by this setting.', 'robse-one-blocks' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'robse_one_cms' ); ?>
			<?php $name = 'robse_one_cms_mode'; ?>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $enabled ); ?>>
				<?php esc_html_e( 'Enable CMS mode', 'robse-one-blocks' ); ?>
			</label>
			<h2><?php esc_html_e( 'Menus to show', 'robse-one-blocks' ); ?></h2>
			<?php foreach ( $labels as $slug => $label ) : ?>
				<label style="display:block;margin:8px 0">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[menus][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected, true ) ); ?>>
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
			<p><strong><?php esc_html_e( 'This profile hides menu links only. It does not change user permissions or prevent layout edits.', 'robse-one-blocks' ); ?></strong></p>
			<p class="description"><?php esc_html_e( 'Administrators are not affected. Only the visibility of standard menus changes; user capabilities stay the same. Menus added by other plugins are controlled by those plugins.', 'robse-one-blocks' ); ?></p>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function robse_one_blocks_apply_cms_menu_profile() {
	if ( ! is_admin() || current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = robse_one_blocks_get_cms_settings();
	if ( empty( $settings['enabled'] ) ) {
		return;
	}
	$keep = isset( $settings['menus'] ) && is_array( $settings['menus'] ) ? $settings['menus'] : array();
	$menu_slugs = array(
		'index.php', 'edit.php', 'upload.php', 'edit.php?post_type=page',
		'edit-comments.php', 'themes.php', 'plugins.php', 'users.php',
		'tools.php', 'options-general.php',
	);
	foreach ( get_post_types( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), 'objects' ) as $post_type ) {
		if ( $post_type->show_in_menu ) {
			$menu_slugs[] = is_string( $post_type->show_in_menu ) ? $post_type->show_in_menu : 'edit.php?post_type=' . $post_type->name;
		}
	}
	foreach ( $menu_slugs as $slug ) {
		if ( ! in_array( $slug, $keep, true ) ) {
			remove_menu_page( $slug );
		}
	}
}
add_action( 'admin_menu', 'robse_one_blocks_apply_cms_menu_profile', 999 );


/** A compact editing guide on the client dashboard. */
function robse_one_blocks_register_dashboard_guide() {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_add_dashboard_widget(
		'robse_one_client_guide',
		__( 'ROBSE ONE editing guide', 'robse-one-blocks' ),
		'robse_one_blocks_render_dashboard_guide'
	);
}
add_action( 'wp_dashboard_setup', 'robse_one_blocks_register_dashboard_guide' );

function robse_one_blocks_render_dashboard_guide() {

	$home = 'page' === get_option( 'show_on_front' ) ? get_post( (int) get_option( 'page_on_front' ) ) : null;
	$is_one = 'robse-one' === get_template();
	$home_template = $is_one ? get_block_template( get_stylesheet() . '//front-page' ) : null;
	$custom = $home_template && ( ! empty( $home_template->wp_id ) || 'custom' === $home_template->source );
	$page_mode = $is_one && get_theme_mod( 'robse_one_content_home', false ) && $home && ! $custom;
	?>
	<p><?php esc_html_e( 'To edit a page or post, change its content and click "Save". Images can be added from Media.', 'robse-one-blocks' ); ?></p>
	<?php if ( $is_one ) : ?>
		<?php if ( $page_mode && current_user_can( 'edit_post', $home->ID ) ) : ?>
			<p><a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $home->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Edit the homepage', 'robse-one-blocks' ); ?></a></p>
		<?php else : ?>
			<p><?php esc_html_e( 'The homepage is controlled by a site template. Ask your administrator to enable page content mode in Appearance > ROBSE ONE setup, or update the template in the Site Editor.', 'robse-one-blocks' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
	<ul>
		<?php if ( current_user_can( 'edit_pages' ) ) : ?><li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>"><?php esc_html_e( 'Edit pages', 'robse-one-blocks' ); ?></a></li><?php endif; ?>
		<?php if ( current_user_can( 'edit_posts' ) ) : ?><li><a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>"><?php esc_html_e( 'Edit posts', 'robse-one-blocks' ); ?></a></li><?php endif; ?>
		<?php if ( current_user_can( 'upload_files' ) ) : ?><li><a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>"><?php esc_html_e( 'Manage images and files', 'robse-one-blocks' ); ?></a></li><?php endif; ?>
	</ul>
	<p><small><?php esc_html_e( 'What you can edit depends on your account role.', 'robse-one-blocks' ); ?></small></p>
	<?php
}
