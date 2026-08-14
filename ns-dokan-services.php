<?php
/**
 * Plugin Name:       NS Dokan Services
 * Plugin URI:        https://github.com/Nassim-sadi/ns-dokan-services
 * Description:       Add a "Service provider" (Prestataire de services) vendor type to Dokan: service shops, service listings, contact CTA and dedicated services store listing.
 * Version:           1.0.0
 * Author:            Nassim Sadi
 * Author URI:        https://github.com/Nassim-sadi
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       camalg-services
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   10.3
 */

defined( 'ABSPATH' ) || exit;

define( 'CDS_VERSION', '1.0.0' );
define( 'CDS_PLUGIN_FILE', __FILE__ );
define( 'CDS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CDS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Meta keys used across the plugin.
define( 'CDS_VENDOR_TYPE_KEY', 'dokan_vendor_type' );      // user meta: 'store' | 'service'
define( 'CDS_LISTING_TYPE_KEY', '_dokan_listing_type' );   // post meta on products
define( 'CDS_SETTINGS_KEY', 'cds_settings' );              // settings option name

// Bootstrap classes.
require_once CDS_PLUGIN_DIR . 'includes/class-cds-plugin.php';
require_once CDS_PLUGIN_DIR . 'includes/class-cds-text-overrides.php';

/**
 * Plugin-wide helper functions (available once the plugin loads).
 */
require_once CDS_PLUGIN_DIR . 'includes/cds-helpers.php';

/**
 * Returns the main plugin instance.
 *
 * @return CDS_Plugin
 */
function cds_plugin() {
	return CDS_Plugin::instance();
}

// Hook into plugins_loaded so dependencies are present before we initialize.
add_action( 'plugins_loaded', 'cds_plugin' );

/**
 * Activation: create sensible defaults once.
 *
 * @return void
 */
function cds_activate() {
	if ( get_option( CDS_SETTINGS_KEY ) === false ) {
		$defaults = array(
			'services_listing_page'      => 0,
			'hide_services_from_shop'    => 1,
			'hide_services_from_search'  => 1,
			'hide_service_shops_from_listing' => 1,
			'restrict_service_dashboard' => 0,
			'override_dashboard_new'     => 1,
			'override_dashboard_old'     => 1,
			'override_filters'           => 1,
			'override_store_page'        => 1,
			'custom_dashboard_strings'   => '',
			'custom_filter_strings'      => '',
			'custom_store_page_strings'  => '',
		);

		// Default to the "prestataires-de-services" page when it exists.
		$page = get_page_by_path( 'prestataires-de-services' );

		if ( $page ) {
			$defaults['services_listing_page'] = (int) $page->ID;
		}

		update_option( CDS_SETTINGS_KEY, $defaults );
	}

	// Give every existing vendor a default type so the type queries work.
	if ( class_exists( 'CDS_Registration' ) ) {
		CDS_Registration::backfill();
	}
}
register_activation_hook( __FILE__, 'cds_activate' );
