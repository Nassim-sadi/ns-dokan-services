<?php
/**
 * Single product summary for service listings.
 *
 * Replaces the "Add to cart" button with Call / Email buttons on service
 * listings. Regular products keep the default add to cart.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Single {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		// Remove the default button (registered by wc-template-hooks.php).
		add_action( 'wp', array( $this, 'remove_default_add_to_cart' ) );
		// Enqueue early so the CTA styles are in the head, not the footer.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 20 );
		// Render either the CTA (services) or the add to cart (products).
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_summary_cta' ), 30 );
	}

	/**
	 * Load the CTA stylesheet in the head for service listings.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		$product_id = get_queried_object_id();

		if ( 'service' === get_post_meta( $product_id, CDS_LISTING_TYPE_KEY, true ) ) {
			wp_enqueue_style( 'cds-frontend' );
		}
	}

	/**
	 * Remove the default add-to-cart template action.
	 *
	 * @return void
	 */
	public function remove_default_add_to_cart() {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}

	/**
	 * Output the CTA for service listings, the add to cart otherwise.
	 *
	 * @return void
	 */
	public function render_summary_cta() {
		global $product;

		if ( ! $product ) {
			return;
		}

		if ( 'service' !== get_post_meta( $product->get_id(), CDS_LISTING_TYPE_KEY, true ) ) {
			woocommerce_template_single_add_to_cart();

			return;
		}

		wp_enqueue_style( 'cds-frontend' );

		$vendor_id  = (int) get_post_field( 'post_author', $product->get_id() );
		$store_info = function_exists( 'dokan_get_store_info' ) ? dokan_get_store_info( $vendor_id ) : array();

		$phone = ! empty( $store_info['phone'] ) ? $store_info['phone'] : '';
		$email = '';

		if ( $vendor_id && ( ! empty( $store_info['show_email'] ) && 'yes' === $store_info['show_email'] ) ) {
			$user  = get_userdata( $vendor_id );
			$email = $user ? $user->user_email : '';
		}

		$has_phone = (bool) $phone;
		$has_email = (bool) $email;

		$store_name = ! empty( $store_info['store_name'] ) ? $store_info['store_name'] : ( $vendor_id ? get_the_author_meta( 'display_name', $vendor_id ) : '' );
		$address    = $this->format_address( $store_info );
		$website    = ! empty( $store_info['website'] ) ? esc_url( $store_info['website'] ) : '';
		$social     = $this->format_social( $store_info );
		$store_url  = function_exists( 'dokan_get_store_url' ) ? dokan_get_store_url( $vendor_id ) : '';

		echo '<div class="cds-service-contact">';

		echo '<div class="cds-contact-card">';

		echo '<div class="cds-contact-head">';

		$avatar = $this->contact_avatar( $vendor_id, $store_info, $store_name );

		if ( $avatar ) {
			echo '<span class="cds-contact-avatar">' . $avatar . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<div class="cds-contact-meta">';

		if ( $store_name ) {
			printf( '<span class="cds-contact-name">%1$s</span>', esc_html( $store_name ) );
		}

		echo '<span class="cds-contact-role">' . esc_html__( 'Service provider', 'camalg-services' ) . '</span>';

		echo '</div></div>';

		echo '<div class="cds-contact-actions">';

		if ( $has_phone ) {
			printf(
				'<a href="tel:%1$s" class="button cds-cta cds-cta-phone">%2$s %3$s</a> ',
				esc_attr( preg_replace( '/[^+0-9]/', '', $phone ) ),
				$this->icon( 'phone' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html__( 'Call provider', 'camalg-services' )
			);
		}

		if ( $has_email ) {
			printf(
				'<a href="mailto:%1$s" class="button cds-cta cds-cta-email">%2$s %3$s</a>',
				esc_attr( $email ),
				$this->icon( 'mail' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html__( 'Email provider', 'camalg-services' )
			);
		}

		if ( ! $has_phone && ! $has_email ) {
			echo '<p class="cds-cta-unavailable">' . esc_html__( 'This provider has not shared contact details yet.', 'camalg-services' ) . '</p>';
		}

		echo '</div>';

		$details = array();

		if ( $has_phone ) {
			$details[] = $this->detail_row( 'phone', $phone, 'tel:' . preg_replace( '/[^+0-9]/', '', $phone ) );
		}

		if ( $has_email ) {
			$details[] = $this->detail_row( 'mail', $email, 'mailto:' . $email );
		}

		if ( $address ) {
			$details[] = $this->detail_row( 'pin', $address, '' );
		}

		if ( $website ) {
			$details[] = $this->detail_row( 'globe', $website, $website );
		}

		if ( $social ) {
			$details[] = $this->detail_row( 'social', $social, '' );
		}

		if ( $details ) {
			echo '<ul class="cds-contact-list">';

			foreach ( $details as $row ) {
				echo '<li class="cds-contact-item">' . $row . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</ul>';
		}

		if ( $store_url ) {
			printf(
				'<a class="cds-visit-store" href="%1$s">%2$s</a>',
				esc_url( $store_url ),
				esc_html__( 'Visit the provider store', 'camalg-services' )
			);
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Contact avatar: store icon when available, otherwise the first letter.
	 *
	 * @param int    $vendor_id  Vendor user ID.
	 * @param array  $store_info Dokan store info.
	 * @param string $store_name Store name.
	 *
	 * @return string
	 */
	private function contact_avatar( $vendor_id, $store_info, $store_name ) {
		if ( ! empty( $store_info['icon'] ) ) {
			$image = wp_get_attachment_image_url( (int) $store_info['icon'], 'thumbnail' );

			if ( $image ) {
				return '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $store_name ) . '" loading="lazy" />';
			}
		}

		$letter = $store_name ? mb_strtoupper( mb_substr( $store_name, 0, 1 ) ) : '?';

		return '<span aria-hidden="true">' . esc_html( $letter ) . '</span>';
	}

	/**
	 * Format the vendor address for display.
	 *
	 * @param array $store_info Dokan store info.
	 *
	 * @return string
	 */
	private function format_address( $store_info ) {
		if ( empty( $store_info['address'] ) || ! is_array( $store_info['address'] ) ) {
			return '';
		}

		$a = $store_info['address'];

		$country = '';

		if ( ! empty( $a['country'] ) ) {
			$countries = WC()->countries ? WC()->countries->get_countries() : array();
			$country   = isset( $countries[ $a['country'] ] ) ? $countries[ $a['country'] ] : $a['country'];
		}

		$state = '';

		if ( ! empty( $a['country'] ) && ! empty( $a['state'] ) && WC()->countries ) {
			$states = WC()->countries->get_states( $a['country'] );
			$state  = ( is_array( $states ) && isset( $states[ $a['state'] ] ) ) ? $states[ $a['state'] ] : $a['state'];
		}

		$parts = array_filter(
			array(
				isset( $a['street_1'] ) ? $a['street_1'] : '',
				isset( $a['city'] ) ? $a['city'] : '',
				isset( $a['zip'] ) ? $a['zip'] : '',
				$state,
				$country,
			)
		);

		return implode( ', ', $parts );
	}

	/**
	 * Build the social links block.
	 *
	 * @param array $store_info Dokan store info.
	 *
	 * @return string
	 */
	private function format_social( $store_info ) {
		$social = ! empty( $store_info['social'] ) && is_array( $store_info['social'] ) ? array_filter( $store_info['social'] ) : array();

		if ( ! $social ) {
			return '';
		}

		$out = '';

		foreach ( $social as $network => $value ) {
			if ( ! $value ) {
				continue;
			}

			$url = ( 0 === strpos( $value, 'http' ) ) ? $value : 'https://' . $network . '.com/' . $value;

			$out .= sprintf(
				'<a href="%1$s" target="_blank" rel="noopener" aria-label="%2$s">%3$s</a>',
				esc_url( $url ),
				esc_attr( ucfirst( $network ) ),
				esc_html( ucfirst( $network ) )
			);
		}

		return $out;
	}

	/**
	 * Render a contact detail row.
	 *
	 * @param string $icon   Icon name.
	 * @param string $label  Display text.
	 * @param string $href   Optional link URL.
	 *
	 * @return string
	 */
	private function detail_row( $icon, $label, $href ) {
		$body = '<span class="cds-contact-icon">' . $this->icon( $icon ) . '</span>';

		if ( 'social' === $icon ) {
			return $body . '<span class="cds-contact-value">' . $label . '</span>';
		}

		if ( $href ) {
			return $body . '<a class="cds-contact-value" href="' . esc_attr( $href ) . '">' . esc_html( $label ) . '</a>';
		}

		return $body . '<span class="cds-contact-value">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Inline SVG icon.
	 *
	 * @param string $name Icon name.
	 *
	 * @return string
	 */
	private function icon( $name ) {
		$icons = array(
			'phone' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
			'mail'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>',
			'pin'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
			'globe' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
		);

		return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
	}
}
