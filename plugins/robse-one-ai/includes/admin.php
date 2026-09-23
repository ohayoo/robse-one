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
	add_submenu_page( 'robse-one-ai', __( 'AI設定', 'robse-one-ai' ), __( 'AI設定', 'robse-one-ai' ), 'manage_options', 'robse-one-ai-settings', 'robse_one_ai_render_settings' );
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
				'working' => __( 'ページ案を作成しています…', 'robse-one-ai' ),
				'creating' => __( '下書きを作成しています…', 'robse-one-ai' ),
				'failed' => __( '処理に失敗しました。設定と入力内容を確認してください。', 'robse-one-ai' ),
				'success' => __( '下書きを作成しました。', 'robse-one-ai' ),
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
		<h1><?php esc_html_e( 'ROBSE ONE AI ページ案', 'robse-one-ai' ); ?></h1>
		<p><?php esc_html_e( '事業内容を入力すると、ページ構成案を作成して確認できます。内容を確認し、必要に応じて編集してから下書きに保存してください。', 'robse-one-ai' ); ?></p>
		<p class="notice inline notice-warning"><strong><?php esc_html_e( '入力した依頼内容はOpenAI APIへ送信されます。個人情報や機密情報を入力せず、送信に同意できる場合のみ実行してください。', 'robse-one-ai' ); ?></strong></p>
		<label for="robse-one-ai-brief"><strong><?php esc_html_e( 'どんなページを作りますか？', 'robse-one-ai' ); ?></strong></label>
		<textarea id="robse-one-ai-brief" rows="6" maxlength="4000" placeholder="<?php esc_attr_e( '例：地域密着の工務店。自然素材の注文住宅とリフォームを提供。', 'robse-one-ai' ); ?>"></textarea>
		<label class="robse-ai-consent"><input type="checkbox" id="robse-one-ai-consent"> <?php esc_html_e( '入力内容がOpenAI APIへ送信されることに同意します。', 'robse-one-ai' ); ?></label>
		<p><button class="button button-primary" id="robse-one-ai-generate"><?php esc_html_e( 'ページ案を作る', 'robse-one-ai' ); ?></button></p>
		<div id="robse-one-ai-status" role="status" aria-live="polite"></div>
		<div id="robse-one-ai-preview" hidden>
			<h2><?php esc_html_e( 'ページ案を確認・編集', 'robse-one-ai' ); ?></h2>
			<label><?php esc_html_e( 'ページタイトル', 'robse-one-ai' ); ?><input type="text" id="robse-one-ai-title" class="regular-text"></label>
			<div id="robse-one-ai-sections"></div>
			<button class="button button-primary" id="robse-one-ai-create"><?php esc_html_e( '下書きとして保存', 'robse-one-ai' ); ?></button>
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
		echo '<div class="notice notice-success"><p>' . esc_html__( '設定を保存しました。', 'robse-one-ai' ) . '</p></div>';
	}
	$options = get_option( 'robse_one_ai_settings', array() );
	$model = ! empty( $options['model'] ) ? $options['model'] : 'gpt-5';
	$has_key = (bool) robse_one_ai_api_key();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'ROBSE ONE AI 設定', 'robse-one-ai' ); ?></h1>
		<p><?php esc_html_e( 'APIキーはサーバー側に保存し、ブラウザーへは送信しません。wp-config.php に ROBSE_ONE_AI_API_KEY を定義すると、データベースのキーより優先します。', 'robse-one-ai' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'robse_one_ai_save_settings' ); ?>
			<table class="form-table"><tbody>
				<tr><th><label for="robse-one-ai-model"><?php esc_html_e( 'モデル名', 'robse-one-ai' ); ?></label></th><td><input class="regular-text" id="robse-one-ai-model" name="model" value="<?php echo esc_attr( $model ); ?>"><p class="description"><?php esc_html_e( 'OpenAI APIで利用可能なテキストモデル名を入力してください。', 'robse-one-ai' ); ?></p></td></tr>
				<tr><th><label for="robse-one-ai-key"><?php esc_html_e( 'APIキー', 'robse-one-ai' ); ?></label></th><td><input class="regular-text" type="password" autocomplete="new-password" id="robse-one-ai-key" name="api_key" value="" placeholder="<?php echo esc_attr( $has_key ? '••••••••••••••••' : '' ); ?>" <?php disabled( defined( 'ROBSE_ONE_AI_API_KEY' ) ); ?>><p class="description"><?php echo $has_key ? esc_html__( '登録済みです。空欄のまま保存すると現在のキーを保持します。', 'robse-one-ai' ) : esc_html__( 'キーは画面に再表示しません。', 'robse-one-ai' ); ?></p></td></tr>
			</tbody></table>
			<input type="hidden" name="robse_one_ai_save" value="1">
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
