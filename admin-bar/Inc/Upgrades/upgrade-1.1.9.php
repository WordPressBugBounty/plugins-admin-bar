<?php
/**
 * Update: remove the remote promotional data (Google Sheet) leftovers.
 *
 * The plugin no longer fetches promotional campaign data from a remote
 * source, so the scheduled sync, its stored options and transient are
 * cleaned up here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove promo sync cron, options and transient.
 */
function jlt_admin_bar_editor_remove_remote_promo_data() {
	wp_clear_scheduled_hook( 'jlt_admin_bar_editor_sheet_promo_data_remote_sync' );

	delete_option( 'jlt_admin_bar_editor_sheet_promo_data' );
	delete_option( 'jlt_admin_bar_editor_sheet_promo_data_hash' );

	delete_transient( \JewelTheme\AdminBarEditor\Libs\Helper::jlt_admin_bar_editor_slug_cleanup() . '_sheet_promo_data' );

	// Popup notification state, the only popup was the promo campaign .
	delete_option( 'jlt_admin_bar_editor_popup_upgrade_notice' );
	delete_option( 'jlt_admin_bar_editor_popup_last_interact' );
}
jlt_admin_bar_editor_remove_remote_promo_data();

// update version once migration is completed.
update_option( $this->option_name, $version );
