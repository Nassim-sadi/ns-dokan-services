<?php
/**
 * Admin-controlled commission for service vendors.
 *
 * Lets the marketplace admin decide whether service providers pay the Dokan
 * marketplace commission. When enabled, every service vendor's commission is
 * pinned to 0% (fixed) via per-vendor user meta, so they keep 100% of their
 * sales. The product form "You Earn" box, the live earning recalculation, the
 * order totals and withdraws all read from that same per-vendor setting, so
 * everything stays consistent.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Commission {

	/**
	 * One-time backfill flag.
	 *
	 * @var string
	 */
	const SYNCED_FLAG = 'cds_service_commission_synced';

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		// Backfill existing service vendors once, without any admin action.
		add_action( 'init', array( $this, 'maybe_backfill' ), 20 );

		// Re-sync whenever the settings page is saved (toggle on/off).
		add_action( 'update_option_' . CDS_SETTINGS_KEY, array( $this, 'resync_on_settings_save' ), 10, 3 );
	}

	/**
	 * Backfill existing service vendors the first time this feature loads.
	 *
	 * @return void
	 */
	public function maybe_backfill() {
		if ( get_option( self::SYNCED_FLAG ) ) {
			return;
		}

		$this->sync_all();
		update_option( self::SYNCED_FLAG, 1 );
	}

	/**
	 * Re-sync all service vendors whenever the settings are saved.
	 *
	 * @param mixed  $old_value Old option value.
	 * @param mixed  $value     New option value.
	 * @param string $option    Option name.
	 *
	 * @return void
	 */
	public function resync_on_settings_save( $old_value, $value, $option ) {
		$this->sync_all();
	}

	/**
	 * Sync every service vendor according to the current setting.
	 *
	 * @return void
	 */
	public function sync_all() {
		$service_vendors = get_users(
			array(
				'role__in'   => array( 'seller', 'vendor' ),
				'fields'     => 'ID',
				'meta_key'   => CDS_VENDOR_TYPE_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => 'service',
				'number'     => -1,
			)
		);

		foreach ( $service_vendors as $user_id ) {
			self::sync_for_user( $user_id );
		}
	}

	/**
	 * Sync a single user's commission meta to match the current setting.
	 *
	 * Only service vendors are touched — store vendors keep their own
	 * commission settings untouched.
	 *
	 * @param int $user_id Vendor user ID.
	 *
	 * @return void
	 */
	public static function sync_for_user( $user_id ) {
		if ( 'service' !== cds_get_vendor_type( $user_id ) ) {
			return;
		}

		if ( (int) cds_get_setting( 'disable_commission_services', 1 ) ) {
			update_user_meta( $user_id, 'dokan_admin_percentage_type', 'fixed' );
			update_user_meta( $user_id, 'dokan_admin_percentage', '0' );
			update_user_meta( $user_id, 'dokan_admin_additional_fee', '0' );

			return;
		}

		delete_user_meta( $user_id, 'dokan_admin_percentage_type' );
		delete_user_meta( $user_id, 'dokan_admin_percentage' );
		delete_user_meta( $user_id, 'dokan_admin_additional_fee' );
	}
}
