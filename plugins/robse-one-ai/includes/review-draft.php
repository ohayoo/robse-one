<?php
/** Apply reviewed text to a separate draft. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function robse_one_ai_review_text( $block ) {
	if ( ! in_array( $block['blockName'], array( 'core/heading', 'core/paragraph' ), true ) ) { return ''; }
	return trim( html_entity_decode( wp_strip_all_tags( preg_replace( '~<br\s*/?>~i', ' ', $block['innerHTML'] ) ), ENT_QUOTES, 'UTF-8' ) );
}

function robse_one_ai_review_plain( $block ) {
	return ! empty( robse_one_ai_review_text( $block ) ) &&
		preg_match( '~^\s*<(p|h[1-6])(?:\s+[^<>]*)?>[^<>]*(?:<br\s*/?>[^<>]*)*</\1>\s*$~iD', $block['innerHTML'] );
}

function robse_one_ai_review_replace( $blocks, $changes, &$index ) {
	foreach ( $blocks as &$block ) {
		if ( '' !== robse_one_ai_review_text( $block ) ) {
			$key = $index++;
			if ( isset( $changes[ $key ] ) && robse_one_ai_review_plain( $block ) ) {
				$replacement = str_replace( "\n", '<br>', esc_html( $changes[ $key ] ) );
				$block['innerHTML'] = preg_replace_callback( '~^(\s*<[^>]+>).*?(</[^>]+>\s*)$~sD', function ( $match ) use ( $replacement ) {
					return $match[1] . $replacement . $match[2];
				}, $block['innerHTML'] );
				$block['innerContent'] = array( $block['innerHTML'] );
			}
		}
		$children = robse_one_ai_review_replace( $block['innerBlocks'], $changes, $index );
		$block['innerBlocks'] = $children;
	}
	return $blocks;
}

function robse_one_ai_review_draftable( $blocks, &$index ) {
	$keys = array();
	foreach ( $blocks as $block ) {
		if ( '' !== robse_one_ai_review_text( $block ) ) {
			if ( robse_one_ai_review_plain( $block ) ) { $keys[ $index ] = true; }
			++$index;
		}
		$keys += robse_one_ai_review_draftable( $block['innerBlocks'], $index );
	}
	return $keys;
}

add_action( 'admin_post_robse_one_ai_review_draft', function () {
	check_admin_referer( 'robse_one_ai_review_draft' );
	$token = sanitize_key( wp_unslash( $_POST['proposal_token'] ?? '' ) );
	$key = 'robse_ai_review_' . get_current_user_id() . '_' . $token;
	$proposal = get_transient( $key );
	$page = $proposal ? robse_one_ai_review_page( $proposal['id'] ) : null;
	$back = add_query_arg( array( 'page'=>'robse-one-ai-review', 'page_id'=>$page ? $page->ID : 0 ), admin_url( 'admin.php' ) );
	if ( ! $page || ! hash_equals( $proposal['hash'], hash( 'sha256', $page->post_content ) ) ) {
		wp_safe_redirect( add_query_arg( 'draft_status', 'expired', $back ) );
		exit;
	}
	$changes = array_intersect_key( $proposal['changes'], array_flip( array_map( 'absint', array_filter( (array) ( $_POST['adopt'] ?? array() ), 'is_scalar' ) ) ) );
	$blocks = parse_blocks( $page->post_content );
	$index = 0;
	$changes = array_intersect_key( $changes, robse_one_ai_review_draftable( $blocks, $index ) );
	if ( ! $changes || ! add_option( $key . '_busy', time(), '', false ) ) {
		wp_safe_redirect( add_query_arg( 'draft_status', 'failed', $back ) );
		exit;
	}
	try {
		if ( ! get_transient( $key ) ) {
			wp_safe_redirect( add_query_arg( 'draft_status', 'expired', $back ) );
		} else {
			$index = 0;
			$meta = array( '_robse_one_ai_review_source' => $page->ID );
			foreach ( array( '_wp_page_template', '_robse_one_corporate_design', '_thumbnail_id' ) as $name ) {
				$value = get_post_meta( $page->ID, $name, true );
				if ( $value ) { $meta[ $name ] = $value; }
			}
			if ( function_exists( 'robse_one_home_page' ) && (int) get_option( 'page_on_front' ) === $page->ID && get_theme_mod( 'robse_one_content_home' ) ) {
				$meta['_wp_page_template'] = 'homepage-content';
			}
			$id = wp_insert_post( wp_slash( array(
				'post_type'=>'page', 'post_status'=>'draft', 'post_author'=>get_current_user_id(),
				'post_title'=>'【確認用】' . $page->post_title, 'post_parent'=>$page->post_parent,
				'post_content'=>serialize_blocks( robse_one_ai_review_replace( $blocks, $changes, $index ) ),
				'meta_input'=>$meta,
			) ), true );
			if ( is_wp_error( $id ) ) {
				wp_safe_redirect( add_query_arg( 'draft_status', 'failed', $back ) );
			} else {
				delete_transient( $key );
				wp_safe_redirect( get_edit_post_link( $id, 'raw' ) );
			}
		}
	} finally {
		delete_option( $key . '_busy' );
	}
	exit;
} );
