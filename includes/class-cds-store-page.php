<?php
/**
 * Service-vendor store page wording.
 *
 * Renames "Products" to "Services" on the public store page of a
 * service-provider vendor (tab label, empty message, search placeholder).
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Store_Page {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_store_assets' ), 20 );
		add_filter( 'dokan_store_tabs', array( $this, 'relabel_tab' ), 10, 2 );
		add_filter( 'gettext_with_context', array( $this, 'relabel_dokan_strings' ), 10, 4 );
		add_filter( 'gettext', array( $this, 'relabel_dokan_strings' ), 10, 3 );
	}

	/**
	 * Load the service CTA styles on a service-vendor store page.
	 *
	 * @return void
	 */
	public function enqueue_store_assets() {
		$is_service_store_page = function_exists( 'dokan_is_store_page' ) && dokan_is_store_page();
		$is_services_listing   = cds_is_services_listing();

		if ( ! $is_service_store_page && ! $is_services_listing ) {
			return;
		}

		if ( $is_service_store_page ) {
			$vendor_id = (int) get_query_var( 'author' );

			if ( ! $vendor_id || 'service' !== cds_get_vendor_type( $vendor_id ) ) {
				return;
			}
		}

		wp_enqueue_style( 'cds-frontend' );
		// Ensure Dokan store listing scripts (filters, search, sort) load on the services listing page
		wp_enqueue_script( 'dokan-script' );
		wp_enqueue_script( 'dokan-frontend' );
		wp_enqueue_script( 'dokan-select2-js' );
		wp_enqueue_script( 'speaking-url' );
		wp_enqueue_script( 'select2' );
		wp_enqueue_script( 'dokan-tooltip' );
		wp_enqueue_script( 'dokan-form-validate' );
	}

	/**
	 * Rename the Products tab on a service-vendor store page.
	 *
	 * @param array $tabs     Dokan store tabs.
	 * @param int   $store_id Vendor user ID.
	 *
	 * @return array
	 */
	public function relabel_tab( $tabs, $store_id ) {
		if ( ! empty( $tabs['products'] ) && 'service' === cds_get_vendor_type( (int) $store_id ) ) {
			$tabs['products']['title'] = __( 'Services', 'camalg-services' );
		}

		return $tabs;
	}

	/**
	 * Rewrite Dokan's store-page strings for service vendors.
	 *
	 * @param string $translation  Translated text.
	 * @param string $text         Original text.
	 * @param string $domain       Text domain.
	 * @param string $context      Optional context (gettext_with_context).
	 *
	 * @return string
	 */
	public function relabel_dokan_strings( $translation, $text, $domain, $context = '' ) {
		if ( 'dokan-lite' !== $domain ) {
			return $translation;
		}

		if ( ! function_exists( 'dokan_is_store_page' ) || ! dokan_is_store_page() ) {
			return $translation;
		}

		$vendor_id = (int) get_query_var( 'author' );

		if ( ! $vendor_id || 'service' !== cds_get_vendor_type( $vendor_id ) ) {
			return $translation;
		}

		switch ( $text ) {
			case 'No products were found of this vendor!':
				return __( 'No services were found of this vendor!', 'camalg-services' );

			case 'Enter product name':
				return __( 'Enter service name', 'camalg-services' );
		}

		return $translation;
	}
}
