<?php
/**
 * Plugin Name:       NS Dokan Services
 * Plugin URI:        https://github.com/Nassim-sadi/ns-dokan-services
 * Description:       Add a "Service provider" (Prestataire de services) vendor type to Dokan: service shops, service listings, contact CTA and dedicated services store listing.
 * Version:           1.4.0
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

define( 'CDS_VERSION', '1.4.0' );
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
require_once CDS_PLUGIN_DIR . 'includes/class-cds-dokan-i18n.php';

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
		// Default French translations for common Dokan strings (applied when locale is fr_FR).
		$fr_strings = ( get_locale() === 'fr_FR' ) ? array(
			'custom_dashboard_strings' => '{"Products":"Produits","Orders":"Commandes","Coupons":"Codes promo","Reports":"Rapports","Settings":"Paramètres","Log Out":"Déconnexion","Profile":"Profil","Store":"Boutique","Withdraw":"Retraits","Shipping":"Expédition","Reviews":"Avis","Attributes":"Attributs","Add Product":"Ajouter un produit","Add New Product":"Ajouter un nouveau produit","AllProducts":"Tous les produits","Orders":"Commandes","Withdraw Requests":"Demandes de retrait","Store Insights":"Statistiques de la boutique","Store Manager":"Gestion de la boutique","Subscribers":"Abonnés","Followers":"Abonnés","Vendor Statement":"Relevé du vendeur","Contact":"Contact","Shipping Zone":"Zone d\'expédition"," You Earn : ":" Vous gagnez : "}',
			'custom_filter_strings'    => '{"Filter":"Filtrer","Cancel":"Annuler","Apply":"Appliquer","Search Vendors":"Rechercher des vendeurs","Sort by":"Trier par :","Most Recent":"Récent","Most Popular":"Populaire","Random":"Aléatoire"}',
			'custom_store_page_strings' => '{"Visit Store":"Visiter la boutique","Add to cart":"Ajouter au panier","View cart":"Voir le panier","Checkout":"Commander","My account":"Mon compte","Logout":"Déconnexion","Login":"Connexion","Price":"Prix","Availability":"Disponibilité","In stock":"En stock","Out of stock":"Rupture de stock","Additional information":"Informations complémentaires","Description":"Description","Reviews":"Avis","Related products":"Produits similaires","Search":"Rechercher","Search for products":"Rechercher des produits"}',
		) : array(
			'custom_dashboard_strings' => '',
			'custom_filter_strings'    => '',
			'custom_store_page_strings' => '',
		);

		$defaults = array(
			'services_listing_page'      => 0,
			'hide_services_from_shop'    => 1,
			'hide_services_from_search'  => 1,
			'hide_service_shops_from_listing' => 1,
			'override_dashboard_new'     => 1,
			'override_dashboard_old'     => 1,
			'override_filters'           => 1,
			'override_store_page'        => 1,
			'hide_service_product_type_fields' => 1,
			'hide_service_inventory'     => 1,
			'hide_service_brands'        => 1,
			'hide_service_listing_filters' => 1,
			'disable_commission_services' => 1,
		);
		$defaults = array_merge( $defaults, $fr_strings );

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

	// Install the bundled Dokan French translation files (best-effort).
	if ( class_exists( 'CDS_Dokan_I18n' ) ) {
		CDS_Dokan_I18n::install_translations();
	}
}
register_activation_hook( __FILE__, 'cds_activate' );
