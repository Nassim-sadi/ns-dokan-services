<?php
/**
 * Service shops in the Dokan store listing.
 *
 * - The page assigned as "services shops" only lists service-type vendors.
 * - Every other Dokan store listing (Espace artisan…) hides service shops.
 * - Provides [cds_service_stores] and [cds_stores] as explicit alternatives.
 * - Auto-injects the listing on the assigned services page if it does not
 *   already contain a store-listing shortcode (no content edits).
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Vendor_Listing {

	/**
	 * Forced vendor type for explicit shortcodes: 'service'|'store'|null.
	 *
	 * @var string|null
	 */
	private $force_type = null;

	/**
	 * Re-entry guard for the content injection.
	 *
	 * @var bool
	 */
	private $injecting = false;

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_filter( 'dokan_seller_listing_args', array( $this, 'filter_store_listing_args' ), 20, 2 );
		add_filter( 'dokan_seller_listing_search_args', array( $this, 'filter_store_search_args' ), 20, 2 );
		add_shortcode( 'cds_service_stores', array( $this, 'render_service_stores' ) );
		add_shortcode( 'cds_stores', array( $this, 'render_stores' ) );
		add_filter( 'the_content', array( $this, 'inject_services_listing' ), 20 );
	}

	/**
	 * Filter the initial Dokan store listing query.
	 *
	 * @param array $seller_args     Seller query args.
	 * @param array $requested_data  Request data.
	 *
	 * @return array
	 */
	public function filter_store_listing_args( $seller_args, $requested_data ) {
		if ( $this->force_type ) {
			return $this->apply_type_query( $seller_args, $this->force_type );
		}

		$listing_type = cds_get_setting( 'services_listing_type', 'show_all' );

		if ( cds_is_services_listing() ) {
			if ( 'show_products' === $listing_type ) {
				return $this->apply_type_query( $seller_args, 'store' );
			} elseif ( 'show_services' === $listing_type ) {
				return $this->apply_type_query( $seller_args, 'service' );
			}
			// show_all: fall through to existing logic
		}

		if ( 'show_products' === $listing_type && ! cds_is_services_listing() ) {
			if ( cds_get_setting( 'hide_service_shops_from_listing', 1 ) ) {
				return $this->apply_type_query( $seller_args, 'store' );
			}
		} elseif ( 'show_services' === $listing_type && ! cds_is_services_listing() ) {
			if ( ! cds_get_setting( 'hide_service_shops_from_listing', 1 ) ) {
				// Even with the setting off, still show services when type is services-only
				return $this->apply_type_query( $seller_args, 'service' );
			}
		}

		return $seller_args;
	}

	/**
	 * Filter the AJAX store listing search.
	 *
	 * The AJAX request does not send the page ID, so we resolve it from the
	 * HTTP referer. If it cannot be resolved we fall back to hiding service
	 * shops (safe default).
	 *
	 * @param array $seller_args Seller query args.
	 * @param array $request     Request data.
	 *
	 * @return array
	 */
	public function filter_store_search_args( $seller_args, $request ) {
		$page_id = $this->resolve_listing_page_id();

		if ( $page_id && cds_is_services_listing( $page_id ) ) {
			$listing_type = cds_get_setting( 'services_listing_type', 'show_all' );
			if ( 'show_products' === $listing_type ) {
				return $this->apply_type_query( $seller_args, 'store' );
			} elseif ( 'show_services' === $listing_type ) {
				return $this->apply_type_query( $seller_args, 'service' );
			}
		}

		if ( cds_get_setting( 'hide_service_shops_from_listing', 1 ) ) {
			return $this->apply_type_query( $seller_args, 'store' );
		}

		return $seller_args;
	}

	/**
	 * Shortcode: only service-provider shops.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function render_service_stores( $atts ) {
		return $this->render_dokan_stores( $atts, 'service' );
	}

	/**
	 * Shortcode: only store-type shops.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function render_stores( $atts ) {
		return $this->render_dokan_stores( $atts, 'store' );
	}

	/**
	 * Auto-inject the services listing onto the assigned page.
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public function inject_services_listing( $content ) {
		if ( $this->injecting ) {
			return $content;
		}

		if ( ! cds_get_services_listing_page_id() ) {
			return $content;
		}

		if ( ! is_singular( 'page' ) || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		if ( ! cds_is_services_listing() ) {
			return $content;
		}

		if ( false !== strpos( $content, '[dokan-stores' ) ) {
			return $content;
		}

		$this->injecting = true;
		$listing         = $this->render_dokan_stores( array( 'per_page' => 12 ), 'service' );
		$this->injecting = false;

		return $content . $listing;
	}

	/**
	 * Render a Dokan store listing for a forced vendor type.
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $type 'service' or 'store'.
	 *
	 * @return string
	 */
	private function render_dokan_stores( $atts, $type ) {
		if ( ! class_exists( 'WeDevs\Dokan\Shortcodes\Stores' ) ) {
			return '<p>' . esc_html__( 'Dokan store listing is not available.', 'camalg-services' ) . '</p>';
		}

		$this->force_type = ( 'service' === $type ) ? 'service' : 'store';

		$output = ( new \WeDevs\Dokan\Shortcodes\Stores() )->render_shortcode( $atts );

		$this->force_type = null;

		return $output;
	}

	/**
	 * Add a vendor-type clause to the seller query args.
	 *
	 * @param array  $seller_args Seller query args.
	 * @param string $type        'service' or 'store'.
	 *
	 * @return array
	 */
	private function apply_type_query( $seller_args, $type ) {
		if ( 'service' === $type ) {
			$seller_args['meta_query'][] = array(
				'key'   => CDS_VENDOR_TYPE_KEY,
				'value' => 'service',
			);
		} else {
			// Untagged (legacy) vendors are treated as store vendors.
			$seller_args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => CDS_VENDOR_TYPE_KEY,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => CDS_VENDOR_TYPE_KEY,
					'value'   => 'service',
					'compare' => '!=',
				),
			);
		}

		return $seller_args;
	}

	/**
	 * Resolve which listing page the current request belongs to.
	 *
	 * @return int
	 */
	private function resolve_listing_page_id() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			if ( $referer ) {
				$page_id = url_to_postid( $referer );

				if ( $page_id ) {
					return (int) $page_id;
				}
			}

			return 0;
		}

		return (int) get_the_ID();
	}
}
