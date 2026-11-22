<?php
/** Shortcuts to routine edits for the corporate starter. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function robse_one_render_corporate_maintenance() {
	$tasks = array(
		'company' => array( '会社情報を変更', '会社概要の表で所在地・受付時間を編集します。' ),
		'case'    => array( '支援事例を追加', '支援事例ページで、ブロック挿入のパターンから「支援事例を追加」を選びます。' ),
		'contact' => array( '問い合わせ先を変更', '窓口欄で、表示する連絡先とリンク先を確認します。フォームの通知先はフォーム設定で変更します。' ),
	);
	echo '<ul>';
	foreach ( robse_one_corporate_records( 'corporate' ) as $key => $id ) {
		if ( ! isset( $tasks[ $key ] ) || 'publish' !== get_post_status( $id ) || ! current_user_can( 'edit_post', $id ) ) { continue; }
		$revision = wp_get_post_revisions_url( $id );
		?>
		<li>
			<a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><strong><?php echo esc_html( $tasks[ $key ][0] ); ?></strong></a>
			<p><?php echo esc_html( $tasks[ $key ][1] ); ?></p>
			<?php if ( $revision ) : ?><a href="<?php echo esc_url( $revision ); ?>"><?php echo esc_html( get_the_title( $id ) . 'の変更履歴・復元' ); ?></a><?php endif; ?>
		</li>
		<?php
	}
	echo '</ul>';
	if ( robse_one_corporate_can_manage() && has_action( 'robse_one_setup_extensions' ) ) {
		echo '<p><a href="' . esc_url( admin_url( 'themes.php?page=robse-one-setup#robse-one-forms' ) ) . '">フォームの通知先とテスト送信を設定</a></p>';
	}
}

add_action( 'wp_dashboard_setup', function () {
	foreach ( robse_one_corporate_records( 'corporate' ) as $key => $id ) {
		if ( 'home' !== $key && 'services' !== $key && 'publish' === get_post_status( $id ) && current_user_can( 'edit_post', $id ) ) {
			wp_add_dashboard_widget( 'robse_one_corporate_maintenance', '企業サイトの更新', 'robse_one_render_corporate_maintenance', null, null, 'normal', 'high' );
			break;
		}
	}
} );
