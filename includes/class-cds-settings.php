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

		// Text Overrides section
		add_settings_section(
			'cds_text_overrides',
			__( 'Text Overrides', 'camalg-services' ),
			array( $this, 'render_text_overrides_description' ),
			'camalg-services'
		);

		add_settings_field(
			'override_dashboard_new',
			__( 'Override new dashboard strings', 'camalg-services' ),
			array( $this, 'render_checkbox_field' ),
			'camalg-services',
			'cds_text_overrides',
			array(
				'key'         => 'override_dashboard_new',
				'default'     => 1,
				'description' => __( 'Enable custom strings for the new Dokan dashboard (Vue-based).', 'camalg-services' ),
			)
		);

		add_settings_field(
			'override_dashboard_old',
			__( 'Override legacy dashboard strings', 'camalg-services' ),
			array( $this, 'render_checkbox_field' ),
			'camalg-services',
			'cds_text_overrides',
			array(
				'key'         => 'override_dashboard_old',
				'default'     => 1,
				'description' => __( 'Enable custom strings for the legacy Dokan dashboard.', 'camalg-services' ),
			)
		);

		add_settings_field(
			'override_filters',
			__( 'Override filter strings', 'camalg-services' ),
			array( $this, 'render_checkbox_field' ),
			'camalg-services',
			'cds_text_overrides',
			array(
				'key'         => 'override_filters',
				'default'     => 1,
				'description' => __( 'Enable custom strings for store listing filters (search, sort, view toggle).', 'camalg-services' ),
			)
		);

		add_settings_field(
			'override_store_page',
			__( 'Override single store page strings', 'camalg-services' ),
			array( $this, 'render_checkbox_field' ),
			'camalg-services',
			'cds_text_overrides',
			array(
				'key'         => 'override_store_page',
				'default'     => 1,
				'description' => __( 'Enable custom strings for single store page (vendor store front).', 'camalg-services' ),
			)
		);

		// Custom text fields
		add_settings_field(
			'custom_dashboard_strings',
			__( 'Custom dashboard strings (JSON)', 'camalg-services' ),
			array( $this, 'render_json_textarea_field' ),
			'camalg-services',
			'cds_text_overrides',
			array(
				'key'         => 'custom_dashboard_strings',
				'description' => __( 'JSON object mapping original strings to custom replacements. Example: {"Products":"Services", "Orders":"Demandes"}', 'camalg-services' ),
			)
		);

		add_settings_field(
			'custom_filter_strings',
			__( 'Custom filter strings (JSON)', 'camalg-services' ),
			array( $this, 'render_json_textarea_field' ),
			'camalg-services',
			'cds_text_overrides',
			array(
				'key'         => 'custom_filter_strings',
				'description' => __( 'JSON object for filter strings: search placeholder, sort options, view labels.', 'camalg-services' ),
			)
		);

		add_settings_field(
			'custom_store_page_strings',
			__( 'Custom store page strings (JSON)', 'camalg-services' ),
			array( $this, 'render_json_textarea_field' ),
			'camalg-services',
			'cds_text_overrides',
			array(
				'key'         => 'custom_store_page_strings',
				'description' => __( 'JSON object for store page: "Visit Store", "Follow", "Contact", etc.', 'camalg-services' ),
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
	 * Text Overrides section description.
	 *
	 * @return void
	 */
	public function render_text_overrides_description() {
		echo '<p>' . esc_html__( 'Override default Dokan strings for service providers. Enable each override group and provide custom JSON mappings.', 'camalg-services' ) . '</p>';
	}

	/**
	 * JSON textarea field for custom string mappings.
	 *
	 * @param array $args Field arguments.
	 *
	 * @return void
	 */
	public function render_json_textarea_field( $args ) {
		$key         = $args['key'];
		$description = isset( $args['description'] ) ? $args['description'] : '';
		$current     = cds_get_setting( $key, '' );

		// Show French defaults when locale is fr_FR and field is empty.
		if ( '' === $current ) {
			$defaults = self::get_default_french_strings();
			if ( isset( $defaults[ $key ] ) ) {
				$current = $defaults[ $key ];
			}
		}

		printf(
			'<textarea name="%1$s[%2$s]" rows="5" cols="80" class="large-text code">%3$s</textarea><br><p class="description">%4$s</p>',
			esc_attr( CDS_SETTINGS_KEY ),
			esc_attr( $key ),
			esc_textarea( $current ),
			esc_html( $description )
		);
	}

	/**
	 * Default French JSON string mappings (used when locale is fr_FR and the
	 * stored setting is empty).
	 *
	 * @return array
	 */
	public static function get_default_french_strings() {
		return array(
			'custom_dashboard_strings' => '{"Products":"Produits","Orders":"Commandes","Coupons":"Codes promo","Reports":"Rapports","Settings":"Param\u00e8tres","Log Out":"D\u00e9connexion","Profile":"Profil","Store":"Boutique","Withdraw":"Retraits","Shipping":"Exp\u00e9dition","Reviews":"Avis","Attributes":"Attributs","Add Product":"Ajouter un produit","Add New Product":"Ajouter un nouveau produit","Subscribers":"Abonn\u00e9s","Followers":"Abonn\u00e9s","Contact":"Contact","Shipping Zone":"Zone d\u0027exp\u00e9dition"}',
			'custom_filter_strings'    => '{"Filter":"Filtrer","Cancel":"Annuler","Apply":"Appliquer","Search Vendors":"Rechercher des vendeurs","Sort by":"Trier par :","Most Recent":"R\u00e9cent","Most Popular":"Populaire","Random":"Al\u00e9atoire"}',
			'custom_store_page_strings' => '{"Visit Store":"Visiter la boutique","Add to cart":"Ajouter au panier","View cart":"Voir le panier","Checkout":"Commander","My account":"Mon compte","Logout":"D\u00e9connexion","Login":"Connexion","Price":"Prix","Availability":"Disponibilit\u00e9","In stock":"En stock","Out of stock":"Rupture de stock","Additional information":"Informations compl\u00e9mentaires","Description":"Description","Related products":"Produits similaires","Search":"Rechercher"}',
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

		foreach ( array( 'override_dashboard_new', 'override_dashboard_old', 'override_filters', 'override_store_page' ) as $key ) {
			$output[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
		}

		foreach ( array( 'custom_dashboard_strings', 'custom_filter_strings', 'custom_store_page_strings' ) as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$clean = wp_unslash( $input[ $key ] );
				$decoded = json_decode( $clean, true );
				$output[ $key ] = ( is_array( $decoded ) ? wp_json_encode( $decoded ) : '' );
			} else {
				$output[ $key ] = '';
			}
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
