<?php
/**
 * Plugin Name: ROBSE ONE AI
 * Description: Creates editable page outlines with the OpenAI API, after the editor's consent, and saves reviewed content as page drafts.
 * Author: Robse
 * Version: 0.3.2
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: robse-one-ai
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ROBSE_ONE_AI_VERSION', '0.3.2' );
define( 'ROBSE_ONE_AI_DIR', plugin_dir_path( __FILE__ ) );
require_once ROBSE_ONE_AI_DIR . 'includes/rest.php';
require_once ROBSE_ONE_AI_DIR . 'includes/admin.php';
