<?php
/**
 * Service-vendor product form simplifier.
 *
 * Hides product-only fields (product type, virtual / downloadable options,
 * inventory and brands) from the Dokan vendor dashboard product form for
 * service providers. Only affects the front-end Dokan interface — the
 * wp-admin product editor is never touched.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Product_Form {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		// Official Dokan filter: skip the whole inventory section.
		add_filter( 'dokan_hide_inventory_template', array( $this, 'hide_inventory' ), 10, 2 );

		// Hide virtual/downloadable/type/brand fields via CSS on the dashboard.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_hide_styles' ), 100 );
	}

	/**
	 * Whether the current request is the vendor dashboard product form for a
	 * service vendor.
	 *
	 * @return bool
	 */
	private function should_simplify() {
		if ( is_admin() ) {
			return false;
		}

		if ( ! function_exists( 'dokan_is_seller_dashboard' ) || ! dokan_is_seller_dashboard() ) {
			return false;
		}

		if ( 'service' !== cds_get_vendor_type( get_current_user_id() ) ) {
			return false;
		}

		global $wp;

		// Products listing / edit page and the one-step "new product" page.
		// The "products" and "new-product" query vars are rewrite endpoints, so
		// they are set to an empty string — isset() is the correct check.
		return isset( $wp->query_vars['products'] ) || isset( $wp->query_vars['new-product'] );
	}

	/**
	 * Hide the inventory section for service vendors when the setting is on.
	 *
	 * @param bool $hide    Current value.
	 * @param int  $post_id Product ID.
	 *
	 * @return bool
	 */
	public function hide_inventory( $hide, $post_id ) {
		if ( $hide ) {
			return $hide;
		}

		if ( (int) cds_get_setting( 'hide_service_inventory', 1 ) && $this->should_simplify() ) {
			return true;
		}

		return $hide;
	}

	/**
	 * Enqueue the hiding stylesheet on the service-vendor product form.
	 *
	 * @return void
	 */
	public function maybe_enqueue_hide_styles() {
		if ( ! $this->should_simplify() ) {
			return;
		}

		$settings = array(
			'hide_type'     => (int) cds_get_setting( 'hide_service_product_type_fields', 1 ),
			'hide_inv'      => (int) cds_get_setting( 'hide_service_inventory', 1 ),
			'hide_brand'    => (int) cds_get_setting( 'hide_service_brands', 1 ),
			'hide_filters'  => (int) cds_get_setting( 'hide_service_listing_filters', 1 ),
		);

		// If nothing is hidden, skip loading the stylesheet entirely.
		if ( ! array_filter( $settings ) ) {
			return;
		}

		wp_enqueue_style( 'cds-frontend' );

		$css = '';

		if ( $settings['hide_type'] ) {
			$css .= '.dokan-product-type-container,.dokan-download-options{display:none!important}.dokan-form-group:has(#product_type){display:none!important}';
		}

		if ( $settings['hide_inv'] ) {
			$css .= '.dokan-product-inventory{display:none!important}';
		}

		if ( $settings['hide_brand'] ) {
			$css .= '.dokan-form-group:has(#product_brand){display:none!important}label[for="product_brand"],select#product_brand{display:none!important}';
		}

		if ( $settings['hide_filters'] ) {
			$css .= 'form.dokan-product-date-filter{display:none!important}';
		}

		if ( $css ) {
			wp_add_inline_style( 'cds-frontend', $css );
		}
	}
}
