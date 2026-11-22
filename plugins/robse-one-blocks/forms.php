<?php
/** Contact Form 7 setup for ROBSE ONE. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function robse_one_forms_presets() {
	return array( 'contact' => 'お問い合わせ', 'apply' => '応募・職場見学' );
}

function robse_one_forms_record( $kind ) {
	$record = get_option( 'robse_one_form_' . $kind, array() );
	return is_array( $record ) ? $record : array();
}

add_action( 'admin_post_robse_one_forms', function () {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_theme_options' ) ) { wp_die( '権限がありません。', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'robse_one_forms' );
	$kind = sanitize_key( wp_unslash( $_POST['kind'] ?? '' ) );
	if ( ! isset( robse_one_forms_presets()[ $kind ] ) || ! class_exists( 'WPCF7_ContactForm' ) ) { wp_die( 'フォームの種類とContact Form 7を確認してください。' ); }
	$action = sanitize_key( wp_unslash( $_POST['step'] ?? '' ) );
	$result = 'error';
	$lock = 'robse_one_forms_lock_' . $kind;
	if ( ! add_option( $lock, 1, '', false ) ) { wp_die( 'フォームの設定を処理中です。' ); }
	try {
		$record = robse_one_forms_record( $kind );
		$form = ! empty( $record['id'] ) ? WPCF7_ContactForm::get_instance( $record['id'] ) : null;
		if ( 'configure' === $action ) {
			$id = absint( $_POST['page_id'] ?? 0 );
			$page = get_post( $id );
			$to = trim( wp_unslash( $_POST['recipient'] ?? '' ) );
			$from = trim( wp_unslash( $_POST['sender'] ?? '' ) );
			if ( $page && 'page' === $page->post_type && in_array( $page->post_status, array( 'draft', 'publish', 'private' ), true ) &&
				current_user_can( 'edit_post', $id ) && is_email( $to ) && is_email( $from ) &&
				( empty( $record['page'] ) || $id === (int) $record['page'] ) ) {
				$new = ! $form;
				if ( $new ) {
					$form = WPCF7_ContactForm::get_template( array( 'title' => robse_one_forms_presets()[ $kind ], 'locale' => 'ja' ) );
				}
				$mail = $form->prop( 'mail' );
				$mail['recipient'] = $to;
				$mail['sender'] = $from;
				$props = array( 'mail' => $mail );
				if ( $new ) {
					$props['form'] = '<label>お名前 [text* your-name autocomplete:name]</label>' . "\n" .
						'<label>メールアドレス [email* your-email autocomplete:email]</label>' . "\n" .
						( 'apply' === $kind ? '<label>希望職種 [text your-subject]</label>' : '<label>件名 [text your-subject]</label>' ) . "\n" .
						'<label>ご連絡内容 [textarea* your-message]</label>' . "\n[submit \"送信する\"]";
					$mail['subject'] = '[' . robse_one_forms_presets()[ $kind ] . '] [your-subject]';
					$mail['body'] = "お名前: [your-name]\nメール: [your-email]\n件名・希望職種: [your-subject]\n\n[your-message]";
					$mail['additional_headers'] = 'Reply-To: [your-email]';
					$mail['attachments'] = '';
					$props['mail'] = $mail;
				}
				$form->set_properties( $props );
				$form_id = $form->save();
				if ( $form_id && ! is_wp_error( $form_id ) ) {
					$shortcode = sprintf( '[contact-form-7 id="%d"]', $form_id );
					update_option( 'robse_one_form_' . $kind, array( 'id' => $form_id, 'page' => $id ), false );
					if ( false !== strpos( $page->post_content, $shortcode ) || ! is_wp_error( wp_update_post( wp_slash( array(
						'ID' => $id, 'post_content' => $page->post_content . "\n<!-- wp:shortcode -->" . $shortcode . '<!-- /wp:shortcode -->',
					) ), true ) ) ) { $result = 'saved'; }
				}
			}
		}
		if ( 'test' === $action && $form && ! get_transient( $lock . '_test' ) ) {
			$mail = $form->prop( 'mail' );
			$to = $mail['recipient'] ?? '';
			$from = $mail['sender'] ?? '';
			if ( is_email( $to ) && is_email( $from ) ) {
				set_transient( $lock . '_test', 1, 30 );
				$code = strtoupper( wp_generate_password( 8, false, false ) );
				$sent = wp_mail( $to, '【ROBSE ONE テスト】' . $code, '受信を確認したら、この確認コードを導入ガイドに入力してください。 ' . $code, array( 'From: ' . $from ) );
				$record['test'] = array( 'sent' => $sent, 'hash' => hash_hmac( 'sha256', $code, wp_salt() ), 'expires' => time() + HOUR_IN_SECONDS, 'mail' => hash( 'sha256', $to . '|' . $from ) );
				update_option( 'robse_one_form_' . $kind, $record, false );
				$result = $sent ? 'sent' : 'failed';
			}
		}
		if ( 'confirm' === $action && $form && ! empty( $record['test']['sent'] ) && $record['test']['expires'] >= time() ) {
			$mail = $form->prop( 'mail' );
			$code = strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) ) ) );
			if ( hash_equals( $record['test']['hash'], hash_hmac( 'sha256', $code, wp_salt() ) ) &&
				hash_equals( $record['test']['mail'], hash( 'sha256', $mail['recipient'] . '|' . $mail['sender'] ) ) ) {
				$record['confirmed'] = $record['test']['mail'];
				unset( $record['test'] );
				update_option( 'robse_one_form_' . $kind, $record, false );
				$result = 'confirmed';
			}
		}
	} finally {
		delete_option( $lock );
	}
	wp_safe_redirect( add_query_arg( 'form_result', $result, admin_url( 'themes.php?page=robse-one-setup' ) ) . '#robse-one-forms' );
	exit;
} );

add_action( 'robse_one_setup_extensions', function () {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	?>
	<section class="card" id="robse-one-forms">
	<h2>問い合わせ・応募フォーム</h2>
	<?php if ( ! class_exists( 'WPCF7_ContactForm' ) ) : ?>
		<p>Contact Form 7をインストールして有効化すると、ここでフォームを設定できます。</p>
		<p><a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=search&s=Contact+Form+7' ) ); ?>">Contact Form 7を探す</a></p>
	<?php else :
		$messages = array( 'saved'=>'設定を保存しました。ページをプレビューしてフォームを確認してください。', 'sent'=>'テスト通知を送信処理に渡しました。受信箱の確認コードを入力してください。', 'failed'=>'テスト通知の送信処理が失敗しました。WordPressのメール設定を確認してください。', 'confirmed'=>'確認コードが一致しました。テスト通知の受信を確認済みです。', 'error'=>'操作を完了できませんでした。入力内容、確認コード、有効期限を確認してください。' );
		$status = sanitize_key( wp_unslash( $_GET['form_result'] ?? '' ) );
		if ( isset( $messages[ $status ] ) ) { echo '<p role="status"><strong>' . esc_html( $messages[ $status ] ) . '</strong></p>'; }
		foreach ( robse_one_forms_presets() as $kind => $label ) :
			$record = robse_one_forms_record( $kind );
			$form = ! empty( $record['id'] ) ? WPCF7_ContactForm::get_instance( $record['id'] ) : null;
			$mail = $form ? $form->prop( 'mail' ) : array();
			?>
			<h3><?php echo esc_html( $label ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'robse_one_forms' ); ?>
				<input type="hidden" name="action" value="robse_one_forms">
				<input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
				<p><label>設置先の固定ページ <?php
				if ( ! empty( $record['page'] ) ) {
					echo esc_html( get_the_title( $record['page'] ) );
					echo '<input type="hidden" name="page_id" value="' . esc_attr( $record['page'] ) . '">';
				} else {
					wp_dropdown_pages( array( 'name'=>'page_id', 'id'=>'robse-form-page-' . $kind, 'post_status'=>array( 'draft', 'publish', 'private' ), 'show_option_none'=>'選択してください' ) );
				}
				?></label></p>
				<p><label>通知先メール <input type="email" name="recipient" required value="<?php echo esc_attr( $mail['recipient'] ?? '' ); ?>"></label></p>
				<p><label>差出人メール <input type="email" name="sender" required value="<?php echo esc_attr( $mail['sender'] ?? '' ); ?>"></label> サイトと同じドメインで送信できるアドレスを指定してください。</p>
				<button class="button button-primary" name="step" value="configure">フォーム設定を保存</button>
			</form>
			<?php if ( $form ) : ?>
				<p><a href="<?php echo esc_url( get_preview_post_link( $record['page'] ) ); ?>">ページをプレビュー</a>。公開前に架空の入力でフォームを送信し、通知も確認してください。</p>
				<?php if ( isset( $record['confirmed'] ) && hash_equals( $record['confirmed'], hash( 'sha256', $mail['recipient'] . '|' . $mail['sender'] ) ) ) : ?><p>テスト通知の受信確認済み</p><?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'robse_one_forms' ); ?>
					<input type="hidden" name="action" value="robse_one_forms">
					<input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
					<button class="button" name="step" value="test" formnovalidate>テスト通知を送る</button>
					<label>受信した確認コード <input name="code" maxlength="8"></label>
					<button class="button" name="step" value="confirm">受信を確認</button>
				</form>
			<?php endif;
		endforeach;
	endif; ?>
	</section>
	<?php
} );
