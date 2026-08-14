<?php
/**
 * Service listings behaviour.
 *
 * - Tags every product with its vendor's type on save and keeps catalog
 *   visibility visible so service listings stay on their vendor's store page.
 * - Excludes service listings from shop / archives / search product loops
 *   (also covers TheGem "Extended Products" widgets via
 *   `woocommerce_product_query_meta_query`).
 * - Makes service listings unpurchasable.
 * - Provides a reusable services loop ([cds_services]) to be adapted later.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Listing {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'save_post_product', array( $this, 'tag_listing_type' ), 20, 3 );
		add_filter( 'woocommerce_product_query_meta_query', array( $this, 'exclude_services_from_queries' ), 999 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'service_not_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'service_loop_cta' ), 20, 3 );
		add_shortcode( 'cds_services', array( $this, 'render_services_shortcode' ) );
	}

	/**
	 * Tag a product with its vendor's type and fix catalog visibility.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 *
	 * @return void
	 */
	public function tag_listing_type( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( empty( $post ) || 'product' !== $post->post_type ) {
			return;
		}

		// Guard against recursion when we save the product below.
		static $running = false;

		if ( $running ) {
			return;
		}

		$running = true;

		$type = cds_get_vendor_type( (int) $post->post_author );
		update_post_meta( $post_id, CDS_LISTING_TYPE_KEY, $type );

		$product = wc_get_product( $post_id );

		if ( $product ) {
			// Always visible: the meta-query exclusion hides services from
			// shop / search, while a vendor's own store page still shows them.
			if ( 'visible' !== $product->get_catalog_visibility() ) {
				$product->set_catalog_visibility( 'visible' );
				$product->save();
			}
		}

		$running = false;
	}

	/**
	 * Exclude service listings from product queries (shop, archives, search,
	 * TheGem product widgets). Respects the "hide from shop" and
	 * "hide from search" settings.
	 *
	 * @param array $meta_query WooCommerce product meta query.
	 *
	 * @return array
	 */
	public function exclude_services_from_queries( $meta_query ) {
		// Skip on real admin screens, but NOT on front-end AJAX (load more,
		// product grids, …) where admin-ajax.php also reports is_admin().
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $meta_query;
		}

		// Never filter an individual product's own page.
		if ( is_singular( 'product' ) ) {
			return $meta_query;
		}

		// A vendor's own store page keeps showing their listings.
		if ( function_exists( 'dokan_is_store_page' ) && dokan_is_store_page() ) {
			return $meta_query;
		}

		$is_search   = is_search();
		$hide_shop   = (bool) cds_get_setting( 'hide_services_from_shop', 1 );
		$hide_search = (bool) cds_get_setting( 'hide_services_from_search', 1 );

		if ( $is_search ) {
			if ( ! $hide_search ) {
				return $meta_query;
			}
		} elseif ( ! $hide_shop ) {
			return $meta_query;
		}

		// Rebuild with a top-level relation so the exclusion can be a named
		// group: plain "NOT EXISTS" OR "!= service" as one nested element
		// returns no rows in WP_Meta_Query.
		$merged = array(
			'relation' => 'AND',
		);

		foreach ( $meta_query as $key => $clause ) {
			if ( 'relation' === $key ) {
				continue;
			}

			$merged[] = $clause;
		}

		$merged['cds_service_exclusion'] = array(
			'relation' => 'OR',
			array(
				'key'     => CDS_LISTING_TYPE_KEY,
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => CDS_LISTING_TYPE_KEY,
				'value'   => 'service',
				'compare' => '!=',
			),
		);

		return $merged;
	}

	/**
	 * Service listings are never purchasable.
	 *
	 * @param bool        $purchasable Current purchasable flag.
	 * @param WC_Product  $product     Product object.
	 *
	 * @return bool
	 */
	public function service_not_purchasable( $purchasable, $product ) {
		if ( $product && 'service' === get_post_meta( $product->get_id(), CDS_LISTING_TYPE_KEY, true ) ) {
			return false;
		}

		return $purchasable;
	}

	/**
	 * Replace the (disabled) add-to-cart button on service cards with a
	 * link that opens the single service page.
	 *
	 * Runs after TheGem's own loop button filter (priority 10), which
	 * blanks the button for unpurchasable products.
	 *
	 * @param string     $link    Loop add-to-cart link HTML.
	 * @param WC_Product $product Product object.
	 * @param array      $args    Loop button args.
	 *
	 * @return string
	 */
	public function service_loop_cta( $link, $product, $args ) {
		if ( ! $product || 'service' !== get_post_meta( $product->get_id(), CDS_LISTING_TYPE_KEY, true ) ) {
			return $link;
		}

		return sprintf(
			'<a href="%s" class="button cds-view-service" aria-label="%s">%s</a>',
			esc_url( get_permalink( $product->get_id() ) ),
			esc_attr( wp_strip_all_tags( $product->get_title() ) ),
			esc_html__( 'View service', 'camalg-services' )
		);
	}

	/**
	 * Reusable WP_Query for service listings.
	 *
	 * @param array $args Query args (merged with defaults).
	 *
	 * @return WP_Query
	 */
	public static function query_services( $args = array() ) {
		$defaults = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'paged'          => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => CDS_LISTING_TYPE_KEY,
				'value' => 'service',
			),
		);

		return new WP_Query( $args );
	}

	/**
	 * Render a simple service listing grid ([cds_services]).
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function render_services_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'posts_per_page' => 12,
				'columns'        => 3,
				'orderby'        => 'date',
				'order'          => 'DESC',
			),
			$atts,
			'cds_services'
		);

		$query = self::query_services(
			array(
				'posts_per_page' => (int) $atts['posts_per_page'],
				'orderby'        => sanitize_key( $atts['orderby'] ),
				'order'          => 'DESC' === strtoupper( $atts['order'] ) ? 'DESC' : 'ASC',
			)
		);

		wp_enqueue_style( 'cds-frontend' );

		if ( ! $query->have_posts() ) {
			return '<p class="cds-no-services">' . esc_html__( 'No services found yet.', 'camalg-services' ) . '</p>';
		}

		$columns = max( 1, min( 4, (int) $atts['columns'] ) );

		ob_start();
		?>
		<div class="cds-services-grid cds-columns-<?php echo esc_attr( $columns ); ?>">
			<?php
			while ( $query->have_posts() ) {
				$query->the_post();
				wc_get_template_part( 'content', 'product' );
			}
			?>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}
}

/**
 * Standalone helper for service listings.
 *
 * @param array $args Query args.
 *
 * @return WP_Query
 */
function cds_get_service_products( $args = array() ) {
	return CDS_Listing::query_services( $args );
}
