<?php
/** ROBSE ONE theme setup. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function robse_one_setup() {
	load_theme_textdomain( 'robse-one', get_template_directory() . '/languages' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true ) );
	add_editor_style( 'assets/css/theme.css' );
	add_editor_style( 'assets/css/editor.css' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	register_block_pattern_category( 'robse-one', array( 'label' => __( 'ROBSE ONE セクション', 'robse-one' ) ) );
}
add_action( 'after_setup_theme', 'robse_one_setup' );

function robse_one_enqueue_styles() {
	wp_enqueue_style( 'robse-one-theme', get_theme_file_uri( 'assets/css/theme.css' ), array(), wp_get_theme()->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'robse_one_enqueue_styles' );
