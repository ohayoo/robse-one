<?php
/** Opt-in setup for the Japanese corporate starter. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function robse_one_starter_types() {
	return array(
		'corporate' => '企業サイト',
		'welfare' => '福祉事業所',
		'recruit' => '採用サイト',
	);
}

function robse_one_starter_key( $type, $key ) {
	return 'corporate' === $type ? $key : $type . ':' . $key;
}

function robse_one_corporate_can_manage() {
	return current_user_can( 'manage_options' ) && current_user_can( 'edit_theme_options' ) && current_user_can( 'publish_pages' );
}

function robse_one_corporate_can_launch() {
	if ( 'page' === get_option( 'show_on_front' ) ) {
		return false;
	}
	foreach ( array( 'front-page' => 'wp_template', 'header' => 'wp_template_part' ) as $slug => $type ) {
		$template = get_block_template( get_stylesheet() . '//' . $slug, $type );
		if ( ! $template || ! empty( $template->wp_id ) || 'custom' === $template->source ) {
			return false;
		}
	}
	return true;
}

function robse_one_corporate_records( $type ) {
	$records = array();
	foreach ( array( 'home', 'services', 'case', 'company', 'contact' ) as $key ) {
		$ids = get_posts( array(
			'post_type' => 'page',
			'post_status' => array( 'draft', 'pending', 'private', 'publish', 'future', 'trash' ),
			'meta_key' => '_robse_one_corporate_page',
			'meta_value' => robse_one_starter_key( $type, $key ),
			'numberposts' => 1,
			'fields' => 'ids',
			'suppress_filters' => true,
		) );
		if ( $ids ) {
			$records[ $key ] = (int) $ids[0];
		}
	}
	return $records;
}

function robse_one_corporate_prepare( $type ) {
	$data = json_decode( file_get_contents( get_theme_file_path( 'assets/starter/' . $type . '-ja.json' ) ), true );
	if ( ! is_array( $data ) || array_keys( $data ) !== array( 'home', 'services', 'case', 'company', 'contact' ) ) {
		return false;
	}
	$records = robse_one_corporate_records( $type );
	foreach ( $data as $key => list( $title, $content ) ) {
		if ( isset( $records[ $key ] ) ) {
			continue;
		}
		$content = strtr( $content, array(
			'{{workshop}}' => esc_url( get_theme_file_uri( 'assets/images/' . $type . '-workshop.webp' ) ),
			'{{corporate-hero}}' => esc_url( get_theme_file_uri( 'assets/images/corporate-hero.webp' ) ),
		) );
		$id = wp_insert_post( wp_slash( array(
			'post_type' => 'page',
			'post_status' => 'draft',
			'post_title' => $title,
			'post_name' => 'robse-' . ( 'corporate' === $type ? '' : $type . '-' ) . $key,
			'post_content' => $content,
			'meta_input' => array(
				'_robse_one_corporate_page' => robse_one_starter_key( $type, $key ),
				'_robse_one_corporate_design' => 'corporate' === $type ? 'cover' : $type,
			),
		) ), true );
		if ( is_wp_error( $id ) ) {
			return false;
		}
	}
	return true;
}

function robse_one_corporate_launch( $type ) {
	$records = robse_one_corporate_records( $type );
	if ( count( $records ) !== 5 || ! robse_one_corporate_can_launch() ) {
		return false;
	}
	$before = array();
	foreach ( $records as $id ) {
		$page = get_post( $id );
		if ( ! $page || ! in_array( $page->post_status, array( 'draft', 'publish' ), true ) || ! current_user_can( 'edit_post', $id ) || ! trim( $page->post_content ) ) {
			return false;
		}
		$before[ $id ] = $page;
	}

	// Resolve starter links by page ID. A changed slug or plain permalinks still work.
	$links = array();
	foreach ( $records as $key => $id ) {
		$links[ '{{page:' . $key . '}}' ] = esc_url( add_query_arg( 'page_id', $id, home_url( '/' ) ) );
	}
	$changed = array();
	$nav = 0;
	$header = 0;
	$complete = false;
	try {
		foreach ( $records as $key => $id ) {
			$changed[] = $id;
			$result = wp_update_post( wp_slash( array(
				'ID' => $id,
				'post_status' => 'publish',
				'post_content' => strtr( $before[ $id ]->post_content, $links ),
			) ), true );
			if ( is_wp_error( $result ) || 'publish' !== get_post_status( $id ) ) {
				return false;
			}
		}
		$items = '';
		$menu_records = 'cover' === get_post_meta( $records['home'], '_robse_one_corporate_design', true )
			? array( 'company' => $records['company'], 'services' => $records['services'], 'case' => $records['case'], 'contact' => $records['contact'] )
			: $records;
		foreach ( $menu_records as $key => $id ) {
			if ( 'home' === $key ) { continue; }
			$items .= '<!-- wp:navigation-link ' . serialize_block_attributes( array(
				'label' => get_the_title( $id ), 'url' => get_permalink( $id ),
				'id' => $id, 'kind' => 'post-type', 'type' => 'page',
			) ) . ' /-->';
		}
		$result = wp_insert_post( wp_slash( array(
			'post_type' => 'wp_navigation', 'post_status' => 'publish',
			'post_title' => robse_one_starter_types()[ $type ] . 'メニュー', 'post_content' => $items,
		) ), true );
		if ( is_wp_error( $result ) ) { return false; }
		$nav = $result;
		$source = file_get_contents( get_theme_file_path( 'parts/header.html' ) );
		$content = str_replace( '<!-- wp:navigation {"layout"', '<!-- wp:navigation {"ref":' . $nav . ',"layout"', $source, $count );
		if ( 1 !== $count || ! robse_one_corporate_can_launch() ) { return false; }
		$result = wp_insert_post( wp_slash( array(
			'post_type' => 'wp_template_part', 'post_status' => 'publish',
			'post_name' => 'header', 'post_title' => 'ヘッダー', 'post_content' => $content,
		) ), true );
		if ( is_wp_error( $result ) ) { return false; }
		$header = $result;
		if ( is_wp_error( wp_set_object_terms( $header, get_stylesheet(), 'wp_theme' ) ) ||
			is_wp_error( wp_set_object_terms( $header, 'header', 'wp_template_part_area' ) ) ) {
			return false;
		}
		update_option( 'page_on_front', $records['home'] );
		set_theme_mod( 'robse_one_content_home', true );
		update_option( 'show_on_front', 'page' );
		$complete = true;
		return true;
	} finally {
		if ( ! $complete ) {
			if ( $header ) { wp_delete_post( $header, true ); }
			if ( $nav ) { wp_delete_post( $nav, true ); }
			foreach ( $changed as $id ) {
				wp_update_post( wp_slash( array(
					'ID' => $id, 'post_status' => $before[ $id ]->post_status,
					'post_content' => $before[ $id ]->post_content,
				) ) );
			}
		}
	}
}

add_action( 'admin_post_robse_one_corporate', function () {
	if ( ! robse_one_corporate_can_manage() ) {
		wp_die( 'この操作は管理者のみ実行できます。', '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'robse_one_corporate' );
	$type = isset( $_POST['starter'] ) ? sanitize_key( wp_unslash( $_POST['starter'] ) ) : 'corporate';
	if ( ! isset( robse_one_starter_types()[ $type ] ) ) { wp_die( '用途を選び直してください。', '', array( 'response' => 400 ) ); }
	$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
	$status = 'busy';
	if ( add_option( 'robse_one_corporate_lock', time(), '', false ) ) {
		try {
			$status = 'error';
			if ( 'prepare' === $step && robse_one_corporate_prepare( $type ) ) { $status = 'prepared'; }
			if ( 'launch' === $step && ! empty( $_POST['reviewed'] ) && robse_one_corporate_launch( $type ) ) { $status = 'published'; }
		} finally {
			delete_option( 'robse_one_corporate_lock' );
		}
	}
	wp_safe_redirect( add_query_arg( array( 'corporate' => $status, 'starter' => $type ), admin_url( 'themes.php?page=robse-one-setup' ) ) );
	exit;
} );

function robse_one_render_corporate_setup() {
	if ( ! robse_one_corporate_can_manage() ) { return; }
	$types = robse_one_starter_types();
	$type = isset( $_GET['starter'] ) ? sanitize_key( wp_unslash( $_GET['starter'] ) ) : 'corporate';
	if ( ! isset( $types[ $type ] ) ) { $type = 'corporate'; }
	$records = robse_one_corporate_records( $type );
	$active = isset( $records['home'] ) && 'page' === get_option( 'show_on_front' ) && $records['home'] === (int) get_option( 'page_on_front' );
	$status = isset( $_GET['corporate'] ) ? sanitize_key( wp_unslash( $_GET['corporate'] ) ) : '';
	$messages = array(
		'prepared' => '固定ページを下書きで準備しました。下のリンクから会社情報を編集してください。',
		'published' => '5ページを公開し、ホームとヘッダーメニューを設定しました。',
		'busy' => '別のセットアップ処理が実行中です。しばらくしてから開き直してください。',
		'error' => 'セットアップを完了できませんでした。ページの状態とホーム・ヘッダーの設定を確認してください。',
	);
	?>
	<section class="card">
		<h2>用途を選んでサイトをセットアップ</h2>
		<p><?php foreach ( $types as $key => $label ) : ?>
			<a class="button <?php echo $key === $type ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'starter', $key, admin_url( 'themes.php?page=robse-one-setup' ) ) ); ?>" <?php echo $key === $type ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?></p>
		<p><a href="<?php echo esc_url( 'https://robse.jp/demo/robse-one-' . $type . '/' ); ?>" target="_blank" rel="noopener">この用途の完成デモを見る（新しいタブ）</a></p>
		<?php if ( isset( $messages[ $status ] ) ) : ?>
			<p role="status"><strong><?php echo esc_html( $messages[ $status ] ); ?></strong></p>
		<?php endif; ?>
		<p><?php echo esc_html( $types[ $type ] ); ?>用の固定ページをまとめて準備できます。</p>
		<?php if ( count( $records ) < 5 ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'robse_one_corporate' ); ?>
				<input type="hidden" name="action" value="robse_one_corporate">
				<input type="hidden" name="starter" value="<?php echo esc_attr( $type ); ?>">
				<input type="hidden" name="step" value="prepare">
				<p>まず下書きを作成します。この操作でホームや公開中のメニューは切り替わりません。</p>
				<?php submit_button( '5ページを下書きで作成', 'primary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
		<?php if ( $records ) : ?>
			<ol>
			<?php foreach ( $records as $id ) : ?>
				<li><a href="<?php echo esc_url( get_edit_post_link( $id, 'raw' ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?>を編集</a></li>
			<?php endforeach; ?>
			</ol>
			<p>サイト名は「設定 → 一般」で変更できます。見本の原稿・写真を確認し、お問い合わせページには実際の電話・メール・フォームを設定してください。</p>
		<?php endif; ?>
		<?php if ( $active ) : ?>
			<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>">サイトを表示</a></p>
		<?php elseif ( 5 === count( $records ) && robse_one_corporate_can_launch() ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'robse_one_corporate' ); ?>
				<input type="hidden" name="action" value="robse_one_corporate">
				<input type="hidden" name="starter" value="<?php echo esc_attr( $type ); ?>">
				<input type="hidden" name="step" value="launch">
				<p><label><input type="checkbox" name="reviewed" value="1" required> 掲載情報と問い合わせ先を確認しました</label></p>
				<?php submit_button( '5ページを公開し、ホームとメニューを設定', 'primary', 'submit', false ); ?>
			</form>
		<?php elseif ( 5 === count( $records ) ) : ?>
			<p>現在のホームまたはヘッダーに設定があります。下書きは編集できます。公開する場合は、既存の設定を確認し、ホームとメニューを手動で選んでください。</p>
		<?php endif; ?>
	</section>
	<?php
}
