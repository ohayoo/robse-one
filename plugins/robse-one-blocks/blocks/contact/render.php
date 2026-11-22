<?php
/** @var array $attributes */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$robse_one_contact_phone = isset( $attributes['phone'] ) ? sanitize_text_field( $attributes['phone'] ) : '';
$robse_one_contact_number = preg_replace( '/[\s().-]/', '', $robse_one_contact_phone );
$robse_one_contact_valid_phone = preg_match( '/^\+?[0-9]{7,15}$/', $robse_one_contact_number );
$robse_one_contact_email = isset( $attributes['email'] ) && is_email( $attributes['email'] ) ? sanitize_email( $attributes['email'] ) : '';
if ( ! $robse_one_contact_valid_phone && ! $robse_one_contact_email ) { return; }
$robse_one_contact_heading = isset( $attributes['heading'] ) ? $attributes['heading'] : __( 'Contact', 'robse-one-blocks' );
$robse_one_contact_wrapper = array( 'class' => 'robse-contact-links' );
if ( ! empty( $attributes['anchor'] ) ) { $robse_one_contact_wrapper['id'] = sanitize_title( $attributes['anchor'] ); }
?>
<section <?php echo get_block_wrapper_attributes( $robse_one_contact_wrapper ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes each wrapper attribute. ?>>
	<?php if ( $robse_one_contact_heading ) : ?><h2><?php echo esc_html( $robse_one_contact_heading ); ?></h2><?php endif; ?>
	<?php if ( $robse_one_contact_valid_phone ) : ?><p><a href="<?php echo esc_url( 'tel:' . $robse_one_contact_number ); ?>"><?php echo esc_html( $robse_one_contact_phone ); ?></a></p><?php endif; ?>
	<?php if ( $robse_one_contact_email ) : ?><p><a href="<?php echo esc_url( 'mailto:' . $robse_one_contact_email ); ?>"><?php echo esc_html( $robse_one_contact_email ); ?></a></p><?php endif; ?>
</section>
