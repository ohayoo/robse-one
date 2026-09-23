<?php
/**
 * Server-side output for the CTA block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Saved inner content.
 * @var WP_Block $block      Block instance.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$robse_one_heading = isset( $attributes['heading'] ) ? $attributes['heading'] : '';
$robse_one_body    = isset( $attributes['body'] ) ? $attributes['body'] : '';
$robse_one_label   = isset( $attributes['buttonLabel'] ) ? $attributes['buttonLabel'] : '';
$robse_one_url     = isset( $attributes['buttonUrl'] ) ? $attributes['buttonUrl'] : '';

$robse_one_markup = '<section ' . get_block_wrapper_attributes( array( 'class' => 'robse-block-cta' ) ) . '><div class="robse-block-cta__inner">';
if ( $robse_one_heading ) {
	$robse_one_markup .= '<h2>' . esc_html( $robse_one_heading ) . '</h2>';
}
if ( $robse_one_body ) {
	$robse_one_markup .= '<p>' . esc_html( $robse_one_body ) . '</p>';
}
if ( $robse_one_label && $robse_one_url ) {
	$robse_one_markup .= '<a class="robse-block-cta__button" href="' . esc_url( $robse_one_url ) . '">' . esc_html( $robse_one_label ) . '</a>';
}
$robse_one_markup .= '</div></section>';

$robse_one_allowed_html            = wp_kses_allowed_html( 'post' );
$robse_one_allowed_html['section'] = array_merge(
	isset( $robse_one_allowed_html['div'] ) ? $robse_one_allowed_html['div'] : array(),
	array( 'aria-label' => true, 'data-*' => true )
);
echo wp_kses( $robse_one_markup, $robse_one_allowed_html );
