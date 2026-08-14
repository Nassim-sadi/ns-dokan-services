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

		// jQuery UI Datepicker + Sortable — required by dokan.js ($('.datepicker').datepicker(), $('.sortable').sortable())
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'wp-jquery-ui-datepicker' );

		// dokan-form-validate (jQuery Validation) must load BEFORE dokan-script
		// — dokan.js calls $.validator.setDefaults() at the top of the file.
		wp_enqueue_script( 'dokan-form-validate' );
		wp_enqueue_script( 'dokan-tooltip' );
		wp_enqueue_script( 'dokan-frontend' );
		wp_enqueue_script( 'dokan-select2-js' );
		wp_enqueue_script( 'speaking-url' );
		wp_enqueue_script( 'select2' );
		wp_enqueue_script( 'dokan-script' );

		// Localize dokan-script with i18n date/time strings (Dokan core omits this for dokan-script)
		$dokan_i18n = array(
			'i18n_date_format'       => wc_date_format(),
			'i18n_time_format'       => wc_time_format(),
			'week_starts_day'        => intval( get_option( 'start_of_week', 0 ) ),
			'timepicker_locale'      => array(
				'am'   => _x( 'am', 'time constant', 'dokan-lite' ),
				'pm'   => _x( 'pm', 'time constant', 'dokan-lite' ),
				'AM'   => _x( 'AM', 'time constant', 'dokan-lite' ),
				'PM'   => _x( 'PM', 'time constant', 'dokan-lite' ),
				'hr'   => _x( 'hr', 'time constant', 'dokan-lite' ),
				'hrs'  => _x( 'hrs', 'time constant', 'dokan-lite' ),
				'mins' => _x( 'mins', 'time constant', 'dokan-lite' ),
			),
			'daterange_picker_local' => array(
				'toLabel'          => __( 'To', 'dokan-lite' ),
				'firstDay'         => intval( get_option( 'start_of_week', 0 ) ),
				'fromLabel'        => __( 'From', 'dokan-lite' ),
				'separator'        => __( ' - ', 'dokan-lite' ),
				'weekLabel'        => __( 'W', 'dokan-lite' ),
				'applyLabel'       => __( 'Apply', 'dokan-lite' ),
				'cancelLabel'      => __( 'Clear', 'dokan-lite' ),
				'customRangeLabel' => __( 'Custom', 'dokan-lite' ),
				'daysOfWeek'       => array(
					__( 'Su', 'dokan-lite' ),
					__( 'Mo', 'dokan-lite' ),
					__( 'Tu', 'dokan-lite' ),
					__( 'We', 'dokan-lite' ),
					__( 'Th', 'dokan-lite' ),
					__( 'Fr', 'dokan-lite' ),
					__( 'Sa', 'dokan-lite' ),
				),
				'monthNames'       => array(
					__( 'January', 'dokan-lite' ),
					__( 'February', 'dokan-lite' ),
					__( 'March', 'dokan-lite' ),
					__( 'April', 'dokan-lite' ),
					__( 'May', 'dokan-lite' ),
					__( 'June', 'dokan-lite' ),
					__( 'July', 'dokan-lite' ),
					__( 'August', 'dokan-lite' ),
					__( 'September', 'dokan-lite' ),
					__( 'October', 'dokan-lite' ),
					__( 'November', 'dokan-lite' ),
					__( 'December', 'dokan-lite' ),
				),
			),
		);

		wp_localize_script( 'dokan-script', 'dokan', $dokan_i18n );
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
