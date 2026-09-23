<?php
/**
 * Plugin Name: ROBSE ONE Blocks
 * Description: ROBSE ONEのデザインシステムと独立したGutenbergブロックを提供します。
 * Version: 0.2.1
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: robse-one-blocks
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function robse_one_blocks_init() {
	register_block_type( __DIR__ . '/blocks/cta' );
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
		'index.php'               => __( 'ダッシュボード', 'robse-one-blocks' ),
		'edit.php'                => __( '投稿', 'robse-one-blocks' ),
		'edit.php?post_type=page' => __( '固定ページ', 'robse-one-blocks' ),
		'upload.php'              => __( 'メディア', 'robse-one-blocks' ),
		'edit-comments.php'       => __( 'コメント', 'robse-one-blocks' ),
		'profile.php'             => __( 'プロフィール', 'robse-one-blocks' ),
	);
	$post_types = get_post_types( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), 'objects' );
	foreach ( $post_types as $post_type ) {
		if ( ! $post_type->show_in_menu ) {
			continue;
		}
		$slug = is_string( $post_type->show_in_menu ) ? $post_type->show_in_menu : 'edit.php?post_type=' . $post_type->name;
		$choices[ $slug ] = sprintf( __( '投稿タイプ: %s', 'robse-one-blocks' ), $post_type->labels->name );
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
		__( 'ROBSE ONE CMSモード', 'robse-one-blocks' ),
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
		<h1><?php esc_html_e( 'ROBSE ONE CMSモード', 'robse-one-blocks' ); ?></h1>
		<p><?php esc_html_e( '管理者以外のユーザーに表示する標準メニューと公開カスタム投稿タイプを選びます。プラグインが追加するメニューはこの設定の対象外です。', 'robse-one-blocks' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'robse_one_cms' ); ?>
			<?php $name = 'robse_one_cms_mode'; ?>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $enabled ); ?>>
				<?php esc_html_e( 'CMSモードを有効にする', 'robse-one-blocks' ); ?>
			</label>
			<h2><?php esc_html_e( '表示するメニュー', 'robse-one-blocks' ); ?></h2>
			<?php foreach ( $labels as $slug => $label ) : ?>
				<label style="display:block;margin:8px 0">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[menus][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected, true ) ); ?>>
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
			<p class="description"><?php esc_html_e( '管理者には影響しません。標準メニューの表示制御のみを行い、各ユーザーの権限は変更しません。プラグイン独自メニューの制御は各プラグインで行ってください。', 'robse-one-blocks' ); ?></p>
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
		__( 'ROBSE ONE 編集ガイド', 'robse-one-blocks' ),
		'robse_one_blocks_render_dashboard_guide'
	);
}
add_action( 'wp_dashboard_setup', 'robse_one_blocks_register_dashboard_guide' );

function robse_one_blocks_render_dashboard_guide() {
	?>
	<p><?php esc_html_e( 'ページや投稿を編集するときは、内容を更新して「更新」を押してください。画像はメディアから追加できます。', 'robse-one-blocks' ); ?></p>
	<ul>
		<?php if ( current_user_can( 'edit_pages' ) ) : ?><li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>"><?php esc_html_e( '固定ページを編集', 'robse-one-blocks' ); ?></a></li><?php endif; ?>
		<?php if ( current_user_can( 'edit_posts' ) ) : ?><li><a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>"><?php esc_html_e( '投稿を編集', 'robse-one-blocks' ); ?></a></li><?php endif; ?>
		<?php if ( current_user_can( 'upload_files' ) ) : ?><li><a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>"><?php esc_html_e( '画像・ファイルを管理', 'robse-one-blocks' ); ?></a></li><?php endif; ?>
	</ul>
	<p><small><?php esc_html_e( '編集できる範囲はアカウントの権限によって異なります。', 'robse-one-blocks' ); ?></small></p>
	<?php
}
