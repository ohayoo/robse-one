<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'rest_api_init', function () {
	register_rest_route(
		'robse-one-ai/v1',
		'/generate',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'robse_one_ai_generate',
			'permission_callback' => function () { return current_user_can( 'edit_pages' ); },
			'args'                => array(
				'brief'   => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ),
				'consent' => array( 'required' => true, 'type' => 'boolean' ),
			),
		)
	);
	register_rest_route(
		'robse-one-ai/v1',
		'/draft',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'robse_one_ai_create_draft',
			'permission_callback' => function () { return current_user_can( 'edit_pages' ); },
		)
	);
} );


function robse_one_ai_text_length( $text ) {
	if ( function_exists( 'mb_strlen' ) ) {
		return mb_strlen( $text, 'UTF-8' );
	}
	return preg_match_all( '/./us', $text, $matches );
}

function robse_one_ai_text_limit( $text, $limit ) {
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, 0, $limit, 'UTF-8' );
	}
	if ( preg_match_all( '/./us', $text, $matches ) ) {
		return implode( '', array_slice( $matches[0], 0, $limit ) );
	}
	return substr( $text, 0, $limit );
}

function robse_one_ai_api_key() {
	if ( defined( 'ROBSE_ONE_AI_API_KEY' ) && ROBSE_ONE_AI_API_KEY ) {
		return ROBSE_ONE_AI_API_KEY;
	}
	return (string) get_option( 'robse_one_ai_api_key', '' );
}

function robse_one_ai_generate( WP_REST_Request $request ) {
	if ( ! $request->get_param( 'consent' ) ) {
		return new WP_Error( 'robse_one_ai_consent_required', __( '外部AIへの送信に同意してください。', 'robse-one-ai' ), array( 'status' => 400 ) );
	}
	$key = robse_one_ai_api_key();
	if ( '' === $key ) {
		return new WP_Error( 'robse_one_ai_not_configured', __( 'AI設定にAPIキーを登録してください。', 'robse-one-ai' ), array( 'status' => 400 ) );
	}
	$user_id = get_current_user_id();
	$rate_key = 'robse_one_ai_rate_' . $user_id;
	if ( get_transient( $rate_key ) ) {
		return new WP_Error( 'robse_one_ai_rate_limited', __( '続けて実行する場合は、しばらく待ってからお試しください。', 'robse-one-ai' ), array( 'status' => 429 ) );
	}
	$brief = trim( (string) $request->get_param( 'brief' ) );
	if ( robse_one_ai_text_length( $brief ) < 10 || robse_one_ai_text_length( $brief ) > 4000 ) {
		return new WP_Error( 'robse_one_ai_invalid_brief', __( '依頼内容は10〜4000文字で入力してください。', 'robse-one-ai' ), array( 'status' => 400 ) );
	}
	set_transient( $rate_key, 1, 15 );

	$options = get_option( 'robse_one_ai_settings', array() );
	$model = ! empty( $options['model'] ) ? sanitize_text_field( $options['model'] ) : 'gpt-5';
	$input = "Create a Japanese WordPress landing page outline from the brief below. Return a JSON object only, with keys title (short Japanese page title) and sections (3 to 8 objects, each with heading and body; body is plain text, 1 to 3 sentences). Do not include HTML, markdown, URLs, factual claims not in the brief, or personal data not supplied.\n\nBrief:\n" . $brief;
	$response = wp_remote_post(
		'https://api.openai.com/v1/responses',
		array(
			'timeout' => 45,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'model'        => $model,
				'input'        => $input,
				'text'         => array( 'format' => array( 'type' => 'json_object' ) ),
				'store'        => false,
				'max_output_tokens' => 1400,
			) ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'robse_one_ai_request_failed', __( 'AIサービスに接続できませんでした。設定と通信環境を確認してください。', 'robse-one-ai' ), array( 'status' => 502 ) );
	}
	$status = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error( 'robse_one_ai_provider_error', __( 'AIサービスからエラーが返されました。', 'robse-one-ai' ), array( 'status' => 502, 'provider_status' => $status ) );
	}
	$output_text = '';
	if ( ! empty( $body['output'] ) && is_array( $body['output'] ) ) {
		foreach ( $body['output'] as $item ) {
			if ( empty( $item['content'] ) || ! is_array( $item['content'] ) ) {
				continue;
			}
			foreach ( $item['content'] as $content ) {
				if ( isset( $content['type'], $content['text'] ) && 'output_text' === $content['type'] ) {
					$output_text .= $content['text'];
				}
			}
		}
	}
	$data = json_decode( $output_text, true );
	$outline = robse_one_ai_sanitize_outline( $data );
	if ( is_wp_error( $outline ) ) {
		return $outline;
	}
	return rest_ensure_response( $outline );
}

function robse_one_ai_sanitize_outline( $data, $error_status = 502 ) {
	if ( ! is_array( $data ) || empty( $data['sections'] ) || ! is_array( $data['sections'] ) ) {
		return new WP_Error( 'robse_one_ai_invalid_response', __( 'AIの出力を読み取れませんでした。依頼内容を変えて再試行してください。', 'robse-one-ai' ), array( 'status' => $error_status ) );
	}
	$title = isset( $data['title'] ) && is_string( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
	$title = robse_one_ai_text_limit( $title, 120 );
	$sections = array();
	foreach ( array_slice( $data['sections'], 0, 8 ) as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$heading = isset( $section['heading'] ) && is_string( $section['heading'] ) ? sanitize_text_field( $section['heading'] ) : '';
		$body = isset( $section['body'] ) && is_string( $section['body'] ) ? sanitize_textarea_field( $section['body'] ) : '';
		if ( '' !== $heading && '' !== $body ) {
			$sections[] = array( 'heading' => robse_one_ai_text_limit( $heading, 120 ), 'body' => robse_one_ai_text_limit( $body, 1000 ) );
		}
	}
	if ( '' === $title || count( $sections ) < 2 ) {
		return new WP_Error( 'robse_one_ai_incomplete_response', __( 'ページ案に必要な内容が不足しています。依頼内容を変えて再試行してください。', 'robse-one-ai' ), array( 'status' => $error_status ) );
	}
	return array( 'title' => $title, 'sections' => $sections );
}

function robse_one_ai_make_block( $name, $attrs, $html ) {
	return array(
		'blockName'    => $name,
		'attrs'        => $attrs,
		'innerBlocks'  => array(),
		'innerHTML'    => $html,
		'innerContent' => array( $html ),
	);
}

function robse_one_ai_build_content( $outline ) {
	$blocks = array();
	foreach ( $outline['sections'] as $section ) {
		$heading = esc_html( $section['heading'] );
		$body = esc_html( $section['body'] );
		$section_blocks = array(
			robse_one_ai_make_block( 'core/heading', array( 'level' => 2 ), '<h2 class="wp-block-heading">' . $heading . '</h2>' ),
			robse_one_ai_make_block( 'core/paragraph', array(), '<p>' . $body . '</p>' ),
		);
		$group = array(
			'blockName'    => 'core/group',
			'attrs'        => array( 'className' => 'robse-ai-section', 'layout' => array( 'type' => 'constrained' ) ),
			'innerBlocks'  => $section_blocks,
			'innerHTML'    => '',
			'innerContent' => array( null, null ),
		);
		$blocks[] = $group;
	}
	return serialize_blocks( $blocks );
}

function robse_one_ai_create_draft( WP_REST_Request $request ) {
	$data = $request->get_json_params();
	$outline = robse_one_ai_sanitize_outline( $data, 400 );
	if ( is_wp_error( $outline ) ) {
		return $outline;
	}
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'draft',
			'post_title'   => $outline['title'],
			'post_content' => robse_one_ai_build_content( $outline ),
			'post_author'  => get_current_user_id(),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}
	return new WP_REST_Response(
		array(
			'id'        => $post_id,
			'edit_url'  => get_edit_post_link( $post_id, 'raw' ),
			'view_url'  => get_permalink( $post_id ),
			'status'    => 'draft',
		),
		201
	);
}
