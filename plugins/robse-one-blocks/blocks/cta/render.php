<?php
/**
 * Server-side output for the CTA block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Saved inner content.
 * @var WP_Block $block      Block instance.
 */
$heading = isset( $attributes['heading'] ) ? $attributes['heading'] : '';
$body = isset( $attributes['body'] ) ? $attributes['body'] : '';
$label = isset( $attributes['buttonLabel'] ) ? $attributes['buttonLabel'] : '';
$url = isset( $attributes['buttonUrl'] ) ? $attributes['buttonUrl'] : '';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'robse-block-cta' ) ); ?>>
	<div class="robse-block-cta__inner">
		<?php if ( $heading ) : ?><h2><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<?php if ( $body ) : ?><p><?php echo esc_html( $body ); ?></p><?php endif; ?>
		<?php if ( $label && $url ) : ?><a class="robse-block-cta__button" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a><?php endif; ?>
	</div>
</section>
