<?php
/** Business details and preflight checks for starter pages. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function robse_one_starter_profile_fields( $type ) {
	$fields = array(
		'name' => '会社・事業所名', 'address' => '所在地', 'hours' => '受付時間',
		'phone' => '電話番号', 'email' => '連絡先メールアドレス',
	);
	if ( 'welfare' === $type ) {
		$fields += array( 'service' => '支援種別', 'visit' => '見学の申し込み方法' );
	}
	if ( 'recruit' === $type ) {
		$fields += array( 'role' => '募集職種', 'apply' => '応募先URL' );
	}
	return $fields;
}

function robse_one_starter_profile_input( $type, $input ) {
	if ( ! is_array( $input ) ) { return false; }
	$profile = array();
	foreach ( robse_one_starter_profile_fields( $type ) as $key => $label ) {
		$value = isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
		if ( 'email' === $key && $value && ! is_email( $value ) ) { return false; }
		if ( 'phone' === $key && $value && ( ! preg_match( '/^\+?[0-9][0-9 ()-]{5,24}$/D', $value ) ||
			! preg_match( '/^[0-9]{6,15}$/D', preg_replace( '/[^0-9]/', '', $value ) ) ) ) { return false; }
		if ( 'apply' === $key && $value ) {
			$value = esc_url_raw( $value, array( 'http', 'https' ) );
			if ( ! wp_parse_url( $value, PHP_URL_HOST ) ) { return false; }
		}
		$profile[ $key ] = $value;
	}
	return $profile;
}

function robse_one_starter_sample_name( $type ) {
	return array( 'corporate'=>'青凪ワークス', 'welfare'=>'ひよりワークス', 'recruit'=>'北浜テクノ' )[ $type ];
}

function robse_one_starter_profile_content( $type, $key, $content, $profile ) {
	if ( ! empty( $profile['name'] ) ) {
		$content = str_replace( robse_one_starter_sample_name( $type ), esc_html( $profile['name'] ), $content );
	}
	if ( 'welfare' === $type && ! empty( $profile['service'] ) ) {
		$content = str_replace( '就労継続支援B型', esc_html( $profile['service'] ), $content );
	}
	if ( 'recruit' === $type && 'case' === $key && ! empty( $profile['role'] ) ) {
		$role = esc_html( $profile['role'] );
		$content = strtr( $content, array( '製造・組み立て担当（見本）' => $role, '製造・組み立て担当' => $role ) );
		unset( $profile['role'] );
	}
	if ( 'company' === $key && in_array( $type, array( 'corporate', 'welfare' ), true ) ) {
		$labels = 'corporate' === $type ? array( '所在地・受付時間' ) : array( '運営法人・所在地', '開所時間・アクセス' );
		$rows = '';
		foreach ( array( 'address', 'hours' ) as $field ) {
			$rows .= '<tr><td>' . esc_html( robse_one_starter_profile_fields( $type )[ $field ] ) .
				'</td><td>' . ( empty( $profile[ $field ] ) ? '公開前に入力' : esc_html( $profile[ $field ] ) ) . '</td></tr>';
		}
		$original = '';
		foreach ( $labels as $label ) {
			$original .= '<tr><td>' . $label . '</td><td>公開前に入力</td></tr>';
		}
		if ( false !== strpos( $content, $original ) ) {
			$content = str_replace( $original, $rows, $content );
			unset( $profile['address'], $profile['hours'] );
		}
	}
	$targets = array(
		'company' => array( 'address', 'hours' ),
		'case' => 'recruit' === $type ? array( 'role' ) : array(),
		'contact' => array( 'phone', 'email', 'visit', 'apply' ),
	);
	$details = array();
	foreach ( $targets[ $key ] ?? array() as $field ) {
		if ( empty( $profile[ $field ] ) ) { continue; }
		$value = esc_html( $profile[ $field ] );
		$urls = array(
			'phone' => 'tel:' . preg_replace( '/[^+0-9]/', '', $profile[ $field ] ),
			'email' => 'mailto:' . $profile[ $field ],
			'apply' => $profile[ $field ],
		);
		if ( isset( $urls[ $field ] ) ) {
			$value = '<a href="' . esc_url( $urls[ $field ] ) . '">' . ( 'apply' === $field ? '応募フォームを開く' : $value ) . '</a>';
		}
		$label = robse_one_starter_profile_fields( $type )[ $field ];
		$details[ $field ] = "<!-- wp:paragraph -->\n<p>" . esc_html( $label ) . '　' . $value . "</p>\n<!-- /wp:paragraph -->";
	}
	if ( 'contact' === $key && array_intersect( array( 'phone', 'email', 'apply' ), array_keys( $details ) ) ) {
		$prompts = array(
			'corporate' => 'ここに実際の電話番号、メールリンク、またはフォームを設定してください。',
			'welfare' => '公開前に事業所の電話番号、メールリンク、または問い合わせフォームを設定してください。',
			'recruit' => '公開前に採用担当のメールリンク、電話番号、または応募フォームを設定してください。',
		);
		$prompt = "<!-- wp:paragraph -->\n<p>" . $prompts[ $type ] . "</p>\n<!-- /wp:paragraph -->";
		if ( false !== strpos( $content, $prompt ) ) {
			return str_replace( $prompt, implode( "\n", $details ), $content );
		}
	}
	return $content . ( $details ? "\n" . implode( "\n", $details ) : '' );
}

function robse_one_starter_page_checks( $type, $key, $page, $records ) {
	if ( ! $page || ! in_array( $page->post_status, array( 'draft', 'publish' ), true ) || ! trim( $page->post_content ) ) {
		return array( 'ページの状態または本文を確認してください。' );
	}
	$content = $page->post_content;
	$issues = array();
	if ( false !== strpos( $content, robse_one_starter_sample_name( $type ) ) ||
		preg_match( '/架空|見本の|見本です|公開前に|ここに実際/u', wp_strip_all_tags( $content ) ) ) {
		$issues[] = '見本の名称・文章・入力案内が残っています。';
	}
	$has_contact = preg_match( '~href=["\'](?:tel:|mailto:)~i', $content ) ||
		( 'recruit' === $type && preg_match( '~href=["\']https?://[^"\']+["\']>応募フォームを開く~u', $content ) );
	if ( shortcode_exists( 'contact-form-7' ) && preg_match_all( '/\[contact-form-7\s+[^\]]*id=["\']?(\d+)/i', $content, $forms ) ) {
		foreach ( $forms[1] as $form ) {
			if ( 'wpcf7_contact_form' === get_post_type( (int) $form ) && 'publish' === get_post_status( (int) $form ) ) { $has_contact = true; }
		}
	}
	if ( 'contact' === $key && ! $has_contact ) {
		$issues[] = '電話・メール・問い合わせフォームなどの連絡先を確認してください。';
	}
	if ( preg_match_all( '/\{\{page:([^}]+)\}\}/', $content, $links ) ) {
		foreach ( array_unique( $links[1] ) as $destination ) {
			if ( empty( $records[ $destination ] ) || ! in_array( get_post_status( $records[ $destination ] ), array( 'draft', 'publish' ), true ) ) {
				$issues[] = 'セットアップ内のリンク先ページが見つかりません。';
				break;
			}
		}
	}
	return $issues;
}

function robse_one_starter_render_profile( $type ) {
	$profile = get_option( 'robse_one_starter_profile_' . $type, array() );
	?>
	<h3>事業者情報を入力</h3>
	<p>入力は任意です。新しく作る下書きに反映します。</p>
	<?php foreach ( robse_one_starter_profile_fields( $type ) as $key => $label ) :
		$input_type = array( 'phone'=>'tel', 'email'=>'email', 'apply'=>'url' )[ $key ] ?? 'text';
		?>
		<p><label><?php echo esc_html( $label ); ?><br>
		<input class="regular-text" type="<?php echo esc_attr( $input_type ); ?>" name="profile[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $profile[ $key ] ?? '' ); ?>"></label></p>
	<?php endforeach; ?>
	<p><a href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>">ロゴはサイトエディターのヘッダーから設定</a></p>
	<?php
}
