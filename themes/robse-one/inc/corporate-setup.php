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

require_once __DIR__ . '/starter-profile.php';

function robse_one_starter_page_url( $id ) {
	return esc_url( add_query_arg( 'page_id', $id, home_url( '/' ) ) );
}

function robse_one_starter_is_publishable( $page ) {
	return $page && 'page' === $page->post_type && in_array( $page->post_status, array( 'draft', 'publish' ), true ) &&
		trim( $page->post_content ) && current_user_can( 'edit_post', $page->ID );
}

// Replace links only while rendering an authorized preview. Keep the saved
// blocks intact so all links can be resolved again when the pages go live.
function robse_one_starter_preview( $content ) {
	if ( ! is_preview() || ! is_page() || ! in_the_loop() || ! is_main_query() || get_the_ID() !== get_queried_object_id() ) {
		return $content;
	}
	$id = get_the_ID();
	$marker = get_post_meta( $id, '_robse_one_corporate_page', true );
	if ( ! $marker || ! current_user_can( 'edit_post', $id ) ) {
		return $content;
	}
	$type = false !== strpos( $marker, ':' ) ? strstr( $marker, ':', true ) : 'corporate';
	if ( ! isset( robse_one_starter_types()[ $type ] ) ) {
		return $content;
	}
	$records = robse_one_corporate_records( $type );
	if ( ! in_array( $id, $records, true ) ) {
		return $content;
	}
	$links = array();
	$items = '';
	foreach ( $records as $key => $destination ) {
		if ( ! in_array( get_post_status( $destination ), array( 'draft', 'publish' ), true ) || ! current_user_can( 'edit_post', $destination ) ) {
			continue;
		}
		$url = esc_url( get_preview_post_link( $destination ) );
		$links[ 'href="{{page:' . $key . '}}"' ] = 'href="' . $url . '"';
		$label = esc_html( get_the_title( $destination ) );
		$items .= '<li>' . ( $id === $destination ? '<strong aria-current="page">' . $label . '</strong>' : '<a href="' . $url . '">' . $label . '</a>' ) . '</li>';
	}
	return '<nav class="robse-starter-preview" aria-label="下書きの確認"><strong>下書きの確認</strong><ul>' . $items .
		'</ul><a href="' . esc_url( add_query_arg( 'starter', $type, admin_url( 'themes.php?page=robse-one-setup' ) ) ) .
		'">導入ガイドへ戻る</a></nav>' . strtr( $content, $links );
}
add_filter( 'the_content', 'robse_one_starter_preview', 12 );

function robse_one_corporate_prepare( $type, $profile = array() ) {
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
			'{{workshop}}' => esc_url( get_theme_file_uri( 'assets/images/' . ( 'corporate' === $type ? 'corporate-workshop.webp' : $type . '-editorial.webp' ) ) ),
			'{{facility-photo}}' => esc_url( get_theme_file_uri( 'assets/images/welfare-facility.webp' ) ),
			'{{training-photo}}' => esc_url( get_theme_file_uri( 'assets/images/recruit-training.webp' ) ),
			'{{corporate-services}}' => esc_url( get_theme_file_uri( 'assets/images/corporate-services.webp' ) ),
			'{{corporate-case}}' => esc_url( get_theme_file_uri( 'assets/images/corporate-case.webp' ) ),
			'{{corporate-company}}' => esc_url( get_theme_file_uri( 'assets/images/corporate-company.webp' ) ),
			'{{corporate-hero}}' => esc_url( get_theme_file_uri( 'assets/images/corporate-hero.webp' ) ),
		) );
		$content = robse_one_starter_profile_content( $type, $key, $content, $profile );
		$id = wp_insert_post( wp_slash( array(
			'post_type' => 'page',
			'post_status' => 'draft',
			'post_title' => $title,
			'post_name' => 'robse-' . ( 'corporate' === $type ? '' : $type . '-' ) . $key,
			'post_content' => $content,
			'page_template' => 'home' === $key ? 'homepage-content' : 'default',
			'meta_input' => array(
				'_robse_one_corporate_page' => robse_one_starter_key( $type, $key ),
				'_robse_one_corporate_design' => 'corporate' === $type ? 'cover' : $type . '-editorial',
			),
		) ), true );
		if ( is_wp_error( $id ) ) {
			return false;
		}
	}
	return true;
}

function robse_one_corporate_launch( $type, $set_home = true ) {
	$records = robse_one_corporate_records( $type );
	if ( count( $records ) !== 5 || ( $set_home && ! robse_one_corporate_can_launch() ) ) {
		return false;
	}
	$before = array();
	foreach ( $records as $id ) {
		$page = get_post( $id );
		if ( ! robse_one_starter_is_publishable( $page ) ) {
			return false;
		}
		$before[ $id ] = $page;
	}

	// Resolve starter links by page ID. A changed slug or plain permalinks still work.
	$links = array();
	foreach ( $records as $key => $id ) {
		$links[ '{{page:' . $key . '}}' ] = robse_one_starter_page_url( $id );
	}
	$changed = array();
	$nav = 0;
	$header = 0;
	$complete = false;
	try {
		foreach ( $records as $key => $id ) {
			$content = strtr( $before[ $id ]->post_content, $links );
			if ( 'publish' === $before[ $id ]->post_status && $content === $before[ $id ]->post_content ) {
				continue;
			}
			$changed[] = $id;
			$result = wp_update_post( wp_slash( array(
				'ID' => $id,
				'post_status' => 'publish',
				'post_content' => $content,
			) ), true );
			if ( is_wp_error( $result ) || 'publish' !== get_post_status( $id ) ) {
				return false;
			}
		}
		if ( ! $set_home ) {
			$complete = true;
			return true;
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
			if ( 'prepare' === $step ) {
				$profile = robse_one_starter_profile_input( $type, wp_unslash( $_POST['profile'] ?? array() ) );
				if ( false === $profile ) {
					$status = 'invalid_profile';
				} else {
					update_option( 'robse_one_starter_profile_' . $type, $profile, false );
					if ( robse_one_corporate_prepare( $type, $profile ) ) { $status = 'prepared'; }
				}
			}
			if ( 'launch' === $step && ! empty( $_POST['reviewed'] ) && robse_one_corporate_launch( $type ) ) { $status = 'published'; }
			if ( 'publish_pages' === $step && ! empty( $_POST['reviewed'] ) && robse_one_corporate_launch( $type, false ) ) { $status = 'pages_published'; }
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
	$published = count( array_filter( $records, function ( $id ) { return 'publish' === get_post_status( $id ); } ) );
	$ready = 5 === count( $records ) && ! array_filter( $records, function ( $id ) {
		return ! robse_one_starter_is_publishable( get_post( $id ) );
	} );
	$can_launch = ! $active && $ready && robse_one_corporate_can_launch();
	$status = isset( $_GET['corporate'] ) ? sanitize_key( wp_unslash( $_GET['corporate'] ) ) : '';
	$messages = array(
		'invalid_profile' => '電話番号、メールアドレス、応募先URLの形式を確認してください。',
		'prepared' => '固定ページを下書きで準備しました。下のリンクから会社情報を編集してください。',
		'published' => '5ページを公開し、ホームとヘッダーメニューを設定しました。',
		'pages_published' => '5ページを公開しました。既存のホームとメニューはそのままです。',
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
				<?php robse_one_starter_render_profile( $type ); ?>
				<?php submit_button( '5ページを下書きで作成', 'primary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
		<?php if ( $records ) : ?>
			<h3>公開前のページ別チェック</h3>
			<p>準備済み <?php echo esc_html( count( $records ) ); ?> / 5 ・ 公開済み <?php echo esc_html( $published ); ?> / 5</p>
			<?php if ( isset( $records['home'] ) && 'draft' === get_post_status( $records['home'] ) && current_user_can( 'edit_post', $records['home'] ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( get_preview_post_link( $records['home'] ) ); ?>">下書きサイトを確認する</a>　プレビュー内で5ページを移動できます。</p>
			<?php endif; ?>
			<p>表示された箇所を編集し、PCとスマホで確認してください。外部リンクの到達、フォーム送信、通知の到着は実際にお試しください。</p>
			<ol>
			<?php foreach ( $records as $key => $id ) :
				$issues = robse_one_starter_page_checks( $type, $key, get_post( $id ), $records );
				?>
				<li>
					<strong><?php echo esc_html( get_the_title( $id ) ); ?></strong>
					<a href="<?php echo esc_url( get_edit_post_link( $id, 'raw' ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?>を編集</a>
					<a href="<?php echo esc_url( get_preview_post_link( $id ) ); ?>">プレビュー</a>
					<?php if ( 'publish' === get_post_status( $id ) ) : ?><a href="<?php echo esc_url( get_permalink( $id ) ); ?>">公開ページ</a><?php endif; ?>
					<?php if ( $issues ) : ?><ul><?php foreach ( $issues as $issue ) : ?><li><?php echo esc_html( $issue ); ?></li><?php endforeach; ?></ul>
					<?php else : ?><span>確認項目の検出なし</span><?php endif; ?>
				</li>
			<?php endforeach; ?>
			</ol>
			<p>サイト名は「設定 → 一般」で変更できます。見本の原稿・写真を確認し、お問い合わせページには実際の電話・メール・フォームを設定してください。</p>
		<?php endif; ?>
		<?php if ( $active ) : ?>
			<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>">サイトを表示</a></p>
		<?php endif; ?>
		<?php if ( $ready && ( $can_launch || $published < 5 ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'robse_one_corporate' ); ?>
				<input type="hidden" name="action" value="robse_one_corporate">
				<input type="hidden" name="starter" value="<?php echo esc_attr( $type ); ?>">
				<p><?php echo esc_html( $can_launch ? '新しいサイトなら、ホームとメニューもまとめて設定できます。' : '現在のホームとメニューを保ったまま、ページを追加できます。公開後、必要なページをナビゲーションに追加してください。' ); ?></p>
				<p><label><input type="checkbox" name="reviewed" value="1" required> 掲載情報と問い合わせ先を確認しました</label></p>
				<?php if ( $can_launch ) : ?><button class="button button-primary" name="step" value="launch">5ページを公開し、ホームとメニューを設定</button><?php endif; ?>
				<?php if ( $published < 5 ) : ?><button class="button" name="step" value="publish_pages">ページのみ公開</button><?php endif; ?>
			</form>
		<?php elseif ( ! $active && 5 === $published ) : ?>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'site-editor.php?path=%2Fnavigation' ) ); ?>">ナビゲーションを開く</a></p>
		<?php elseif ( 5 === count( $records ) && ! $ready ) : ?>
			<p>公開するには5ページすべてに本文が必要です。ゴミ箱・非公開・予約中のページがある場合は状態を確認してください。</p>
		<?php endif; ?>
	</section>
	<?php
}
