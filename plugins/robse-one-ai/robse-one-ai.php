<?php
/**
 * Plugin Name: ROBSE ONE AI
 * Description: Creates editable page outlines with the OpenAI API, after the editor's consent, and saves reviewed content as page drafts.
 * Author: Robse
 * Version: 0.5.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: robse-one-ai
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ROBSE_ONE_AI_VERSION', '0.5.0' );
add_action( 'init', function () { load_plugin_textdomain( 'robse-one-ai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); } );

define( 'ROBSE_ONE_AI_DIR', plugin_dir_path( __FILE__ ) );
require_once ROBSE_ONE_AI_DIR . 'includes/rest.php';
require_once ROBSE_ONE_AI_DIR . 'includes/admin.php';

require_once ROBSE_ONE_AI_DIR . 'includes/review.php';

require_once ROBSE_ONE_AI_DIR . 'includes/review-draft.php';
