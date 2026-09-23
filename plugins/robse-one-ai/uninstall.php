<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
delete_option( 'robse_one_ai_settings' );
delete_option( 'robse_one_ai_api_key' );
