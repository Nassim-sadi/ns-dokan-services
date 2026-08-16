<?php
/**
 * Vendor type selection at Dokan registration.
 *
 * Adds a "Boutique / Prestataire de services" radio to the Dokan vendor
 * registration form, makes it required and saves the chosen type to the
 * user meta (CDS_VENDOR_TYPE_KEY).
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Registration {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'dokan_seller_registration_field_after', array( $this, 'render_type_field' ) );
		add_filter( 'dokan_seller_registration_required_fields', array( $this, 'require_type_field' ) );
		add_action( 'dokan_new_seller_created', array( $this, 'save_vendor_type' ), 10, 2 );
	}

	/**
	 * Render the vendor type radio on the registration form.
	 *
	 * @return void
	 */
	public function render_type_field() {
		$postdata = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$current  = ! empty( $postdata['vendor_type'] ) ? sanitize_key( $postdata['vendor_type'] ) : 'store';

		if ( ! in_array( $current, array( 'store', 'service' ), true ) ) {
			$current = 'store';
		}
		?>
		<p class="form-row form-group form-row-wide cds-registration-type">
			<label><?php esc_html_e( 'I want to register as a', 'camalg-services' ); ?><span class="required">*</span></label>
			<br>
			<label class="radio" style="display:inline-block;margin-right:15px;">
				<input type="radio" name="vendor_type" value="store" <?php checked( $current, 'store' ); ?> />
				<?php esc_html_e( 'Store — I sell products', 'camalg-services' ); ?>
			</label>
			<label class="radio" style="display:inline-block;">
				<input type="radio" name="vendor_type" value="service" <?php checked( $current, 'service' ); ?> />
				<?php esc_html_e( 'Service provider — I offer services', 'camalg-services' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Make the vendor type a required registration field.
	 *
	 * @param array $required_fields Existing required fields.
	 *
	 * @return array
	 */
	public function require_type_field( $required_fields ) {
		$required_fields['vendor_type'] = __( 'Please select whether you are a Store or a Service provider.', 'camalg-services' );

		return $required_fields;
	}

	/**
	 * Persist the vendor type once the vendor account is created.
	 *
	 * @param int   $vendor_id      New vendor user ID.
	 * @param array $dokan_settings Dokan profile settings.
	 *
	 * @return void
	 */
	public function save_vendor_type( $vendor_id, $dokan_settings ) {
		$type = isset( $_POST['vendor_type'] ) ? sanitize_key( wp_unslash( $_POST['vendor_type'] ) ) : 'store'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! in_array( $type, array( 'store', 'service' ), true ) ) {
			$type = 'store';
		}

		update_user_meta( $vendor_id, CDS_VENDOR_TYPE_KEY, $type );

		// Keep the admin-controlled commission in sync with the vendor type.
		if ( class_exists( 'CDS_Commission' ) ) {
			CDS_Commission::sync_for_user( $vendor_id );
		}
	}

	/**
	 * Set a default vendor type ('store') for every existing vendor that has
	 * none. Keeps the store-listing queries simple (every vendor has a value).
	 *
	 * @return void
	 */
	public static function backfill() {
		$sellers = get_users(
			array(
				'role__in' => array( 'seller', 'vendor' ),
				'fields'   => 'ID',
				'number'   => -1,
			)
		);

		foreach ( $sellers as $user_id ) {
			if ( '' === get_user_meta( $user_id, CDS_VENDOR_TYPE_KEY, true ) ) {
				update_user_meta( $user_id, CDS_VENDOR_TYPE_KEY, 'store' );
			}
		}
	}
}
