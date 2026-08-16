<?php
/**
 * Uninstall cleanup for NS Dokan Services.
 *
 * Removes the plugin options and the user meta this plugin manages.
 * Commission metas are only removed for service vendors (the users this
 * plugin set them on), so store vendors keep any custom Dokan commission
 * settings. Product post meta is left untouched.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Options.
delete_option( 'cds_settings' );
delete_option( 'cds_strings_repaired' );
delete_option( 'cds_service_commission_synced' );

// Commission metas are only cleared for service vendors.
$service_vendors = get_users(
	array(
		'fields'     => 'ID',
		'meta_key'   => 'dokan_vendor_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value' => 'service',
		'number'     => -1,
	)
);

foreach ( $service_vendors as $user_id ) {
	delete_user_meta( $user_id, 'dokan_admin_percentage_type' );
	delete_user_meta( $user_id, 'dokan_admin_percentage' );
	delete_user_meta( $user_id, 'dokan_admin_additional_fee' );
}

// The vendor type meta is managed exclusively by this plugin.
global $wpdb;
$wpdb->delete(
	$wpdb->usermeta,
	array( 'meta_key' => 'dokan_vendor_type' ),
	array( '%s' )
);
