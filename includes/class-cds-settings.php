<?php
/**
 * Admin settings.
 *
 * - "Services shops page" dropdown (mirrors Dokan's Store Listing setting).
 * - Toggles for hiding services from shop / search / default store listing.
 * - Option to restrict the Dokan dashboard for service vendors.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Settings {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'dokan_get_dashboard_nav', array( $this, 'restrict_service_dashboard' ) );
	}

	/**
	 * Add the settings page under WooCommerce.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'NS Dokan Services', 'camalg-services' ),
			__( 'NS Dokan Services', 'camalg-services' ),
			'manage_woocommerce',
			'camalg-services',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings, sections and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'cds_settings_group',
			CDS_SETTINGS_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);

		add_settings_section(
			'cds_general',
			__( 'Services', 'camalg-services' ),
			array( $this, 'render_section_description' ),
			'camalg-services'
		);

		add_settings_field(
			'services_listing_page',
			__( 'Services shops page', 'camalg-services' ),
			array( $this, 'render_page_field' ),
			'camalg-services',
			'cds_general'
		);

		add_settings_field(
			'hide_services_from_shop',
			__( 'Hide services from shop', 'camalg-services' ),
			array( $this, 'render_checkbox_field' ),
			'camalg-services',
			'cds_general',
			array(
				'key'         => 'hide_services_from_shop',
				'default'     => 1,
				'description' => __( 'Hide service listings from the shop and product archives.', 'camalg-services' ),
			)
		);

		add_settings_field(
			'hide_services_from_search',
			__( 'Hide services from search', 'camalg-services' ),
			array( $this, 'render_checkbox_field' ),
			'camalg-services',
			'cds_general',
			array(
				'key'         => 'hide_services_from_search',
				'default'     => 1,
				'description' => __( 'Hide service listings from search results.', 'camalg-services' ),
			)
		);

		add_settings_field(
			'hide_service_shops_from_listing',
			__( 'Hide service shops from default listing', 'camalg-services' ),
			array( $this, 'render_checkbox_field' ),
			'camalg-services',
			'cds_general',
			array(
				'key'         => 'hide_service_shops_from_listing',
				'default'     => 1,
				'description' => __( 'Hide service-provider shops from every Dokan store listing except the page assigned above.', 'camalg-services' ),
			)
		);

		add_settings_field(
			'restrict_service_dashboard',
			__( 'Restrict service-vendor dashboard', 'camalg-services' ),
			array( $this, 'render_checkbox_field' ),
			'camalg-services',
			'cds_general',
			array(
				'key'         => 'restrict_service_dashboard',
				'default'     => 0,
				'description' => __( 'Hide Products, Orders and Withdraw menus in the Dokan dashboard for service vendors.', 'camalg-services' ),
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'NS Dokan Services', 'camalg-services' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'cds_settings_group' );
				do_settings_sections( 'camalg-services' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Section description.
	 *
	 * @return void
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure how "Prestataire de services" shops and their service listings behave.', 'camalg-services' ) . '</p>';
	}

	/**
	 * Services shops page dropdown.
	 *
	 * @return void
	 */
	public function render_page_field() {
		$current = cds_get_services_listing_page_id();
		$pages   = get_pages(
			array(
				'post_status' => 'publish',
				'number'      => 500,
			)
		);

		echo '<select name="' . esc_attr( CDS_SETTINGS_KEY ) . '[services_listing_page]">';
		echo '<option value="0">' . esc_html__( '— Select a page —', 'camalg-services' ) . '</option>';

		foreach ( $pages as $page ) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $page->ID,
				selected( $current, (int) $page->ID, false ),
				esc_html( $page->post_title )
			);
		}

		echo '</select>';
		echo '<p class="description">' . esc_html__( 'The Dokan store listing on this page (and its translations) will show service providers only. Leave empty to disable.', 'camalg-services' ) . '</p>';
	}

	/**
	 * Generic checkbox field.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return void
	 */
	public function render_checkbox_field( $args ) {
		$key         = $args['key'];
		$default     = isset( $args['default'] ) ? (int) $args['default'] : 0;
		$description = isset( $args['description'] ) ? $args['description'] : '';
		$current     = (int) cds_get_setting( $key, $default );

		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>',
			esc_attr( CDS_SETTINGS_KEY ),
			esc_attr( $key ),
			checked( $current, 1, false ),
			esc_html( $description )
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param array $input Raw input.
	 *
	 * @return array
	 */
	public function sanitize( $input ) {
		$output = array();

		if ( ! is_array( $input ) ) {
			return $output;
		}

		$output['services_listing_page'] = isset( $input['services_listing_page'] ) ? absint( $input['services_listing_page'] ) : 0;

		foreach ( array( 'hide_services_from_shop', 'hide_services_from_search', 'hide_service_shops_from_listing', 'restrict_service_dashboard' ) as $key ) {
			$output[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
		}

		return $output;
	}

	/**
	 * Optionally hide product/order/withdraw menus for service vendors.
	 *
	 * @param array $menus Dokan dashboard nav menus.
	 *
	 * @return array
	 */
	public function restrict_service_dashboard( $menus ) {
		if ( ! cds_get_setting( 'restrict_service_dashboard', 0 ) ) {
			return $menus;
		}

		if ( 'service' !== cds_get_vendor_type( get_current_user_id() ) ) {
			return $menus;
		}

		unset( $menus['products'], $menus['orders'], $menus['withdraw'] );

		return $menus;
	}
}
