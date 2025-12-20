<?php
/**
 * WooCommerce integration class.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_IP_Blocker_WooCommerce
 *
 * Handles WooCommerce-specific geo-blocking functionality.
 */
class Geo_IP_Blocker_WooCommerce {

	/**
	 * Settings instance.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Blocker instance.
	 *
	 * @var Geo_IP_Blocker_Blocker
	 */
	private $blocker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Only initialize if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->settings = get_option( 'geo_ip_blocker_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Only proceed if WooCommerce blocking is enabled.
		if ( empty( $this->settings['woo_enable_blocking'] ) ) {
			return;
		}

		// Admin hooks.
		add_action( 'add_meta_boxes', array( $this, 'add_product_metabox' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ) );

		// Frontend hooks.
		add_action( 'template_redirect', array( $this, 'check_page_access' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout' ) );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_product_purchasable' ), 10, 2 );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'validate_cart' ) );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_restriction_message' ) );

		// Display option hooks.
		if ( ! empty( $this->settings['woo_hide_price'] ) ) {
			add_filter( 'woocommerce_get_price_html', array( $this, 'hide_price' ), 10, 2 );
		}

		if ( ! empty( $this->settings['woo_hide_add_to_cart'] ) ) {
			add_filter( 'woocommerce_is_purchasable', array( $this, 'hide_add_to_cart' ), 20, 2 );
		}
	}

	/**
	 * Add product metabox.
	 */
	public function add_product_metabox() {
		// Only add metabox if product blocking is enabled.
		if ( empty( $this->settings['woo_enable_product_blocking'] ) ) {
			return;
		}

		add_meta_box(
			'geo_ip_blocker_product',
			__( 'Geo Blocker - Restrictions', 'geo-ip-blocker' ),
			array( $this, 'render_product_metabox' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Render product metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_product_metabox( $post ) {
		wp_nonce_field( 'geo_ip_blocker_product_meta', 'geo_ip_blocker_product_nonce' );

		$enabled   = get_post_meta( $post->ID, '_geo_blocker_enabled', true );
		$countries = get_post_meta( $post->ID, '_geo_blocker_countries', true );

		if ( ! is_array( $countries ) ) {
			$countries = array();
		}

		// Get all countries.
		$all_countries = $this->get_all_countries();
		?>
		<div class="geo-ip-blocker-product-meta">
			<p>
				<label>
					<input type="checkbox" name="geo_blocker_enabled" value="yes" <?php checked( $enabled, 'yes' ); ?>>
					<?php esc_html_e( 'Apply geo-blocking to this product', 'geo-ip-blocker' ); ?>
				</label>
			</p>

			<p>
				<label for="geo_blocker_countries">
					<strong><?php esc_html_e( 'Blocked Countries:', 'geo-ip-blocker' ); ?></strong>
				</label>
				<select name="geo_blocker_countries[]" id="geo_blocker_countries" multiple="multiple" style="width: 100%;" class="geo-blocker-country-select">
					<?php foreach ( $all_countries as $code => $name ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php echo in_array( $code, $countries, true ) ? 'selected' : ''; ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p class="description">
				<?php esc_html_e( 'Select countries that should be blocked from purchasing this product. Leave empty to use global settings.', 'geo-ip-blocker' ); ?>
			</p>
		</div>

		<style>
			.geo-ip-blocker-product-meta p {
				margin-bottom: 12px;
			}
			.geo-ip-blocker-product-meta .description {
				font-size: 12px;
				font-style: italic;
				color: #646970;
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			if (typeof $.fn.select2 !== 'undefined') {
				$('#geo_blocker_countries').select2({
					placeholder: '<?php esc_html_e( 'Select countries...', 'geo-ip-blocker' ); ?>',
					allowClear: true
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Save product meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_product_meta( $post_id ) {
		// Check nonce.
		if ( ! isset( $_POST['geo_ip_blocker_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['geo_ip_blocker_product_nonce'] ) ), 'geo_ip_blocker_product_meta' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// Save enabled status.
		$enabled = isset( $_POST['geo_blocker_enabled'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_geo_blocker_enabled', $enabled );

		// Save countries.
		$countries = array();
		if ( isset( $_POST['geo_blocker_countries'] ) && is_array( $_POST['geo_blocker_countries'] ) ) {
			$countries = array_map( 'sanitize_text_field', wp_unslash( $_POST['geo_blocker_countries'] ) );
		}
		update_post_meta( $post_id, '_geo_blocker_countries', $countries );
	}

	/**
	 * Check page access for WooCommerce pages.
	 */
	public function check_page_access() {
		// Get blocker instance.
		if ( ! $this->blocker ) {
			$this->blocker = new Geo_IP_Blocker_Blocker();
		}

		// Check if visitor should be blocked based on page settings.
		$should_block = false;
		$blocking_level = isset( $this->settings['woo_blocking_level'] ) ? $this->settings['woo_blocking_level'] : 'entire_site';

		// Determine which pages to block based on blocking level.
		switch ( $blocking_level ) {
			case 'entire_site':
				if ( $this->is_woocommerce_page() ) {
					$should_block = true;
				}
				break;

			case 'shop_only':
				if ( is_shop() || is_product_category() || is_product_tag() || is_product() ) {
					$should_block = true;
				}
				break;

			case 'cart_checkout':
				if ( is_cart() || is_checkout() ) {
					$should_block = true;
				}
				break;

			case 'checkout_only':
				if ( is_checkout() ) {
					$should_block = true;
				}
				break;
		}

		// Check specific page settings.
		if ( ! $should_block ) {
			if ( is_shop() && ! empty( $this->settings['woo_block_shop'] ) ) {
				$should_block = true;
			} elseif ( is_cart() && ! empty( $this->settings['woo_block_cart'] ) ) {
				$should_block = true;
			} elseif ( is_checkout() && ! empty( $this->settings['woo_block_checkout'] ) ) {
				$should_block = true;
			} elseif ( is_account_page() && ! empty( $this->settings['woo_block_account'] ) ) {
				$should_block = true;
			}
		}

		// If should block, use the blocker to handle it.
		if ( $should_block ) {
			$this->blocker->check_and_block();
		}
	}

	/**
	 * Validate checkout.
	 */
	public function validate_checkout() {
		// Get blocker instance.
		if ( ! $this->blocker ) {
			$this->blocker = new Geo_IP_Blocker_Blocker();
		}

		// Get visitor country.
		$visitor_country = $this->blocker->get_visitor_country();

		if ( ! $visitor_country ) {
			return;
		}

		// Check if visitor country is blocked globally.
		$blocked_countries = isset( $this->settings['blocked_countries'] ) ? $this->settings['blocked_countries'] : array();

		if ( in_array( $visitor_country, $blocked_countries, true ) ) {
			wc_add_notice( __( 'Checkout is not available in your region.', 'geo-ip-blocker' ), 'error' );
			return;
		}

		// Check each cart item for product-specific restrictions.
		if ( ! empty( $this->settings['woo_enable_product_blocking'] ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product_id = $cart_item['product_id'];
				$enabled    = get_post_meta( $product_id, '_geo_blocker_enabled', true );

				if ( 'yes' === $enabled ) {
					$countries = get_post_meta( $product_id, '_geo_blocker_countries', true );

					if ( is_array( $countries ) && in_array( $visitor_country, $countries, true ) ) {
						$product = wc_get_product( $product_id );
						wc_add_notice(
							sprintf(
								/* translators: %s: product name */
								__( 'The product "%s" is not available in your region.', 'geo-ip-blocker' ),
								$product->get_name()
							),
							'error'
						);
					}
				}
			}
		}
	}

	/**
	 * Filter product purchasable status.
	 *
	 * @param bool       $purchasable Whether product is purchasable.
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	public function filter_product_purchasable( $purchasable, $product ) {
		if ( ! $purchasable ) {
			return $purchasable;
		}

		// Only check if product blocking is enabled.
		if ( empty( $this->settings['woo_enable_product_blocking'] ) ) {
			return $purchasable;
		}

		// Get blocker instance.
		if ( ! $this->blocker ) {
			$this->blocker = new Geo_IP_Blocker_Blocker();
		}

		// Get visitor country.
		$visitor_country = $this->blocker->get_visitor_country();

		if ( ! $visitor_country ) {
			return $purchasable;
		}

		// Check product-specific restrictions.
		$product_id = $product->get_id();
		$enabled    = get_post_meta( $product_id, '_geo_blocker_enabled', true );

		if ( 'yes' !== $enabled ) {
			return $purchasable;
		}

		$countries = get_post_meta( $product_id, '_geo_blocker_countries', true );

		if ( empty( $countries ) ) {
			// Use global settings if product countries are empty.
			$countries = isset( $this->settings['blocked_countries'] ) ? $this->settings['blocked_countries'] : array();
		}

		if ( is_array( $countries ) && in_array( $visitor_country, $countries, true ) ) {
			return false;
		}

		return $purchasable;
	}

	/**
	 * Validate cart items.
	 */
	public function validate_cart() {
		if ( ! WC()->cart ) {
			return;
		}

		// Only check if product blocking is enabled.
		if ( empty( $this->settings['woo_enable_product_blocking'] ) ) {
			return;
		}

		// Get blocker instance.
		if ( ! $this->blocker ) {
			$this->blocker = new Geo_IP_Blocker_Blocker();
		}

		// Get visitor country.
		$visitor_country = $this->blocker->get_visitor_country();

		if ( ! $visitor_country ) {
			return;
		}

		// Check each cart item.
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product_id = $cart_item['product_id'];
			$enabled    = get_post_meta( $product_id, '_geo_blocker_enabled', true );

			if ( 'yes' === $enabled ) {
				$countries = get_post_meta( $product_id, '_geo_blocker_countries', true );

				if ( empty( $countries ) ) {
					// Use global settings if product countries are empty.
					$countries = isset( $this->settings['blocked_countries'] ) ? $this->settings['blocked_countries'] : array();
				}

				if ( is_array( $countries ) && in_array( $visitor_country, $countries, true ) ) {
					// Remove item from cart.
					WC()->cart->remove_cart_item( $cart_item_key );

					$product = wc_get_product( $product_id );
					wc_add_notice(
						sprintf(
							/* translators: %s: product name */
							__( 'The product "%s" has been removed from your cart as it is not available in your region.', 'geo-ip-blocker' ),
							$product->get_name()
						),
						'notice'
					);
				}
			}
		}
	}

	/**
	 * Display restriction message on product page.
	 */
	public function display_restriction_message() {
		global $product;

		if ( ! $product ) {
			return;
		}

		// Only check if product blocking is enabled.
		if ( empty( $this->settings['woo_enable_product_blocking'] ) ) {
			return;
		}

		// Get blocker instance.
		if ( ! $this->blocker ) {
			$this->blocker = new Geo_IP_Blocker_Blocker();
		}

		// Get visitor country.
		$visitor_country = $this->blocker->get_visitor_country();

		if ( ! $visitor_country ) {
			return;
		}

		// Check product-specific restrictions.
		$product_id = $product->get_id();
		$enabled    = get_post_meta( $product_id, '_geo_blocker_enabled', true );

		if ( 'yes' !== $enabled ) {
			return;
		}

		$countries = get_post_meta( $product_id, '_geo_blocker_countries', true );

		if ( empty( $countries ) ) {
			// Use global settings if product countries are empty.
			$countries = isset( $this->settings['blocked_countries'] ) ? $this->settings['blocked_countries'] : array();
		}

		if ( is_array( $countries ) && in_array( $visitor_country, $countries, true ) ) {
			$message = isset( $this->settings['woo_blocked_product_message'] ) ? $this->settings['woo_blocked_product_message'] : __( 'This product is not available in your region.', 'geo-ip-blocker' );
			echo '<div class="woocommerce-info geo-ip-blocker-notice">' . wp_kses_post( $message ) . '</div>';
		}
	}

	/**
	 * Hide price for blocked products.
	 *
	 * @param string     $price Price HTML.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public function hide_price( $price, $product ) {
		// Check if product is purchasable (which checks geo restrictions).
		if ( ! $product->is_purchasable() ) {
			// Only check if product blocking is enabled.
			if ( empty( $this->settings['woo_enable_product_blocking'] ) ) {
				return $price;
			}

			// Get blocker instance.
			if ( ! $this->blocker ) {
				$this->blocker = new Geo_IP_Blocker_Blocker();
			}

			// Get visitor country.
			$visitor_country = $this->blocker->get_visitor_country();

			if ( ! $visitor_country ) {
				return $price;
			}

			// Check product-specific restrictions.
			$product_id = $product->get_id();
			$enabled    = get_post_meta( $product_id, '_geo_blocker_enabled', true );

			if ( 'yes' === $enabled ) {
				$countries = get_post_meta( $product_id, '_geo_blocker_countries', true );

				if ( empty( $countries ) ) {
					$countries = isset( $this->settings['blocked_countries'] ) ? $this->settings['blocked_countries'] : array();
				}

				if ( is_array( $countries ) && in_array( $visitor_country, $countries, true ) ) {
					return '';
				}
			}
		}

		return $price;
	}

	/**
	 * Hide add to cart button for blocked products.
	 *
	 * @param bool       $purchasable Whether product is purchasable.
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	public function hide_add_to_cart( $purchasable, $product ) {
		if ( ! $purchasable ) {
			return $purchasable;
		}

		// Only check if product blocking is enabled.
		if ( empty( $this->settings['woo_enable_product_blocking'] ) ) {
			return $purchasable;
		}

		// Get blocker instance.
		if ( ! $this->blocker ) {
			$this->blocker = new Geo_IP_Blocker_Blocker();
		}

		// Get visitor country.
		$visitor_country = $this->blocker->get_visitor_country();

		if ( ! $visitor_country ) {
			return $purchasable;
		}

		// Check product-specific restrictions.
		$product_id = $product->get_id();
		$enabled    = get_post_meta( $product_id, '_geo_blocker_enabled', true );

		if ( 'yes' !== $enabled ) {
			return $purchasable;
		}

		$countries = get_post_meta( $product_id, '_geo_blocker_countries', true );

		if ( empty( $countries ) ) {
			$countries = isset( $this->settings['blocked_countries'] ) ? $this->settings['blocked_countries'] : array();
		}

		if ( is_array( $countries ) && in_array( $visitor_country, $countries, true ) ) {
			return false;
		}

		return $purchasable;
	}

	/**
	 * Check if current page is a WooCommerce page.
	 *
	 * @return bool
	 */
	private function is_woocommerce_page() {
		return is_shop() || is_product_category() || is_product_tag() || is_product() || is_cart() || is_checkout() || is_account_page();
	}

	/**
	 * Get all countries.
	 *
	 * @return array
	 */
	private function get_all_countries() {
		return array(
			'AF' => __( 'Afghanistan', 'geo-ip-blocker' ),
			'AX' => __( 'Åland Islands', 'geo-ip-blocker' ),
			'AL' => __( 'Albania', 'geo-ip-blocker' ),
			'DZ' => __( 'Algeria', 'geo-ip-blocker' ),
			'AS' => __( 'American Samoa', 'geo-ip-blocker' ),
			'AD' => __( 'Andorra', 'geo-ip-blocker' ),
			'AO' => __( 'Angola', 'geo-ip-blocker' ),
			'AI' => __( 'Anguilla', 'geo-ip-blocker' ),
			'AQ' => __( 'Antarctica', 'geo-ip-blocker' ),
			'AG' => __( 'Antigua and Barbuda', 'geo-ip-blocker' ),
			'AR' => __( 'Argentina', 'geo-ip-blocker' ),
			'AM' => __( 'Armenia', 'geo-ip-blocker' ),
			'AW' => __( 'Aruba', 'geo-ip-blocker' ),
			'AU' => __( 'Australia', 'geo-ip-blocker' ),
			'AT' => __( 'Austria', 'geo-ip-blocker' ),
			'AZ' => __( 'Azerbaijan', 'geo-ip-blocker' ),
			'BS' => __( 'Bahamas', 'geo-ip-blocker' ),
			'BH' => __( 'Bahrain', 'geo-ip-blocker' ),
			'BD' => __( 'Bangladesh', 'geo-ip-blocker' ),
			'BB' => __( 'Barbados', 'geo-ip-blocker' ),
			'BY' => __( 'Belarus', 'geo-ip-blocker' ),
			'BE' => __( 'Belgium', 'geo-ip-blocker' ),
			'BZ' => __( 'Belize', 'geo-ip-blocker' ),
			'BJ' => __( 'Benin', 'geo-ip-blocker' ),
			'BM' => __( 'Bermuda', 'geo-ip-blocker' ),
			'BT' => __( 'Bhutan', 'geo-ip-blocker' ),
			'BO' => __( 'Bolivia', 'geo-ip-blocker' ),
			'BQ' => __( 'Bonaire, Sint Eustatius and Saba', 'geo-ip-blocker' ),
			'BA' => __( 'Bosnia and Herzegovina', 'geo-ip-blocker' ),
			'BW' => __( 'Botswana', 'geo-ip-blocker' ),
			'BV' => __( 'Bouvet Island', 'geo-ip-blocker' ),
			'BR' => __( 'Brazil', 'geo-ip-blocker' ),
			'IO' => __( 'British Indian Ocean Territory', 'geo-ip-blocker' ),
			'BN' => __( 'Brunei Darussalam', 'geo-ip-blocker' ),
			'BG' => __( 'Bulgaria', 'geo-ip-blocker' ),
			'BF' => __( 'Burkina Faso', 'geo-ip-blocker' ),
			'BI' => __( 'Burundi', 'geo-ip-blocker' ),
			'KH' => __( 'Cambodia', 'geo-ip-blocker' ),
			'CM' => __( 'Cameroon', 'geo-ip-blocker' ),
			'CA' => __( 'Canada', 'geo-ip-blocker' ),
			'CV' => __( 'Cape Verde', 'geo-ip-blocker' ),
			'KY' => __( 'Cayman Islands', 'geo-ip-blocker' ),
			'CF' => __( 'Central African Republic', 'geo-ip-blocker' ),
			'TD' => __( 'Chad', 'geo-ip-blocker' ),
			'CL' => __( 'Chile', 'geo-ip-blocker' ),
			'CN' => __( 'China', 'geo-ip-blocker' ),
			'CX' => __( 'Christmas Island', 'geo-ip-blocker' ),
			'CC' => __( 'Cocos (Keeling) Islands', 'geo-ip-blocker' ),
			'CO' => __( 'Colombia', 'geo-ip-blocker' ),
			'KM' => __( 'Comoros', 'geo-ip-blocker' ),
			'CG' => __( 'Congo', 'geo-ip-blocker' ),
			'CD' => __( 'Congo, Democratic Republic of the', 'geo-ip-blocker' ),
			'CK' => __( 'Cook Islands', 'geo-ip-blocker' ),
			'CR' => __( 'Costa Rica', 'geo-ip-blocker' ),
			'CI' => __( 'Côte d\'Ivoire', 'geo-ip-blocker' ),
			'HR' => __( 'Croatia', 'geo-ip-blocker' ),
			'CU' => __( 'Cuba', 'geo-ip-blocker' ),
			'CW' => __( 'Curaçao', 'geo-ip-blocker' ),
			'CY' => __( 'Cyprus', 'geo-ip-blocker' ),
			'CZ' => __( 'Czech Republic', 'geo-ip-blocker' ),
			'DK' => __( 'Denmark', 'geo-ip-blocker' ),
			'DJ' => __( 'Djibouti', 'geo-ip-blocker' ),
			'DM' => __( 'Dominica', 'geo-ip-blocker' ),
			'DO' => __( 'Dominican Republic', 'geo-ip-blocker' ),
			'EC' => __( 'Ecuador', 'geo-ip-blocker' ),
			'EG' => __( 'Egypt', 'geo-ip-blocker' ),
			'SV' => __( 'El Salvador', 'geo-ip-blocker' ),
			'GQ' => __( 'Equatorial Guinea', 'geo-ip-blocker' ),
			'ER' => __( 'Eritrea', 'geo-ip-blocker' ),
			'EE' => __( 'Estonia', 'geo-ip-blocker' ),
			'ET' => __( 'Ethiopia', 'geo-ip-blocker' ),
			'FK' => __( 'Falkland Islands (Malvinas)', 'geo-ip-blocker' ),
			'FO' => __( 'Faroe Islands', 'geo-ip-blocker' ),
			'FJ' => __( 'Fiji', 'geo-ip-blocker' ),
			'FI' => __( 'Finland', 'geo-ip-blocker' ),
			'FR' => __( 'France', 'geo-ip-blocker' ),
			'GF' => __( 'French Guiana', 'geo-ip-blocker' ),
			'PF' => __( 'French Polynesia', 'geo-ip-blocker' ),
			'TF' => __( 'French Southern Territories', 'geo-ip-blocker' ),
			'GA' => __( 'Gabon', 'geo-ip-blocker' ),
			'GM' => __( 'Gambia', 'geo-ip-blocker' ),
			'GE' => __( 'Georgia', 'geo-ip-blocker' ),
			'DE' => __( 'Germany', 'geo-ip-blocker' ),
			'GH' => __( 'Ghana', 'geo-ip-blocker' ),
			'GI' => __( 'Gibraltar', 'geo-ip-blocker' ),
			'GR' => __( 'Greece', 'geo-ip-blocker' ),
			'GL' => __( 'Greenland', 'geo-ip-blocker' ),
			'GD' => __( 'Grenada', 'geo-ip-blocker' ),
			'GP' => __( 'Guadeloupe', 'geo-ip-blocker' ),
			'GU' => __( 'Guam', 'geo-ip-blocker' ),
			'GT' => __( 'Guatemala', 'geo-ip-blocker' ),
			'GG' => __( 'Guernsey', 'geo-ip-blocker' ),
			'GN' => __( 'Guinea', 'geo-ip-blocker' ),
			'GW' => __( 'Guinea-Bissau', 'geo-ip-blocker' ),
			'GY' => __( 'Guyana', 'geo-ip-blocker' ),
			'HT' => __( 'Haiti', 'geo-ip-blocker' ),
			'HM' => __( 'Heard Island and McDonald Islands', 'geo-ip-blocker' ),
			'VA' => __( 'Holy See (Vatican City State)', 'geo-ip-blocker' ),
			'HN' => __( 'Honduras', 'geo-ip-blocker' ),
			'HK' => __( 'Hong Kong', 'geo-ip-blocker' ),
			'HU' => __( 'Hungary', 'geo-ip-blocker' ),
			'IS' => __( 'Iceland', 'geo-ip-blocker' ),
			'IN' => __( 'India', 'geo-ip-blocker' ),
			'ID' => __( 'Indonesia', 'geo-ip-blocker' ),
			'IR' => __( 'Iran', 'geo-ip-blocker' ),
			'IQ' => __( 'Iraq', 'geo-ip-blocker' ),
			'IE' => __( 'Ireland', 'geo-ip-blocker' ),
			'IM' => __( 'Isle of Man', 'geo-ip-blocker' ),
			'IL' => __( 'Israel', 'geo-ip-blocker' ),
			'IT' => __( 'Italy', 'geo-ip-blocker' ),
			'JM' => __( 'Jamaica', 'geo-ip-blocker' ),
			'JP' => __( 'Japan', 'geo-ip-blocker' ),
			'JE' => __( 'Jersey', 'geo-ip-blocker' ),
			'JO' => __( 'Jordan', 'geo-ip-blocker' ),
			'KZ' => __( 'Kazakhstan', 'geo-ip-blocker' ),
			'KE' => __( 'Kenya', 'geo-ip-blocker' ),
			'KI' => __( 'Kiribati', 'geo-ip-blocker' ),
			'KP' => __( 'Korea, Democratic People\'s Republic of', 'geo-ip-blocker' ),
			'KR' => __( 'Korea, Republic of', 'geo-ip-blocker' ),
			'KW' => __( 'Kuwait', 'geo-ip-blocker' ),
			'KG' => __( 'Kyrgyzstan', 'geo-ip-blocker' ),
			'LA' => __( 'Lao People\'s Democratic Republic', 'geo-ip-blocker' ),
			'LV' => __( 'Latvia', 'geo-ip-blocker' ),
			'LB' => __( 'Lebanon', 'geo-ip-blocker' ),
			'LS' => __( 'Lesotho', 'geo-ip-blocker' ),
			'LR' => __( 'Liberia', 'geo-ip-blocker' ),
			'LY' => __( 'Libya', 'geo-ip-blocker' ),
			'LI' => __( 'Liechtenstein', 'geo-ip-blocker' ),
			'LT' => __( 'Lithuania', 'geo-ip-blocker' ),
			'LU' => __( 'Luxembourg', 'geo-ip-blocker' ),
			'MO' => __( 'Macao', 'geo-ip-blocker' ),
			'MK' => __( 'Macedonia', 'geo-ip-blocker' ),
			'MG' => __( 'Madagascar', 'geo-ip-blocker' ),
			'MW' => __( 'Malawi', 'geo-ip-blocker' ),
			'MY' => __( 'Malaysia', 'geo-ip-blocker' ),
			'MV' => __( 'Maldives', 'geo-ip-blocker' ),
			'ML' => __( 'Mali', 'geo-ip-blocker' ),
			'MT' => __( 'Malta', 'geo-ip-blocker' ),
			'MH' => __( 'Marshall Islands', 'geo-ip-blocker' ),
			'MQ' => __( 'Martinique', 'geo-ip-blocker' ),
			'MR' => __( 'Mauritania', 'geo-ip-blocker' ),
			'MU' => __( 'Mauritius', 'geo-ip-blocker' ),
			'YT' => __( 'Mayotte', 'geo-ip-blocker' ),
			'MX' => __( 'Mexico', 'geo-ip-blocker' ),
			'FM' => __( 'Micronesia', 'geo-ip-blocker' ),
			'MD' => __( 'Moldova', 'geo-ip-blocker' ),
			'MC' => __( 'Monaco', 'geo-ip-blocker' ),
			'MN' => __( 'Mongolia', 'geo-ip-blocker' ),
			'ME' => __( 'Montenegro', 'geo-ip-blocker' ),
			'MS' => __( 'Montserrat', 'geo-ip-blocker' ),
			'MA' => __( 'Morocco', 'geo-ip-blocker' ),
			'MZ' => __( 'Mozambique', 'geo-ip-blocker' ),
			'MM' => __( 'Myanmar', 'geo-ip-blocker' ),
			'NA' => __( 'Namibia', 'geo-ip-blocker' ),
			'NR' => __( 'Nauru', 'geo-ip-blocker' ),
			'NP' => __( 'Nepal', 'geo-ip-blocker' ),
			'NL' => __( 'Netherlands', 'geo-ip-blocker' ),
			'NC' => __( 'New Caledonia', 'geo-ip-blocker' ),
			'NZ' => __( 'New Zealand', 'geo-ip-blocker' ),
			'NI' => __( 'Nicaragua', 'geo-ip-blocker' ),
			'NE' => __( 'Niger', 'geo-ip-blocker' ),
			'NG' => __( 'Nigeria', 'geo-ip-blocker' ),
			'NU' => __( 'Niue', 'geo-ip-blocker' ),
			'NF' => __( 'Norfolk Island', 'geo-ip-blocker' ),
			'MP' => __( 'Northern Mariana Islands', 'geo-ip-blocker' ),
			'NO' => __( 'Norway', 'geo-ip-blocker' ),
			'OM' => __( 'Oman', 'geo-ip-blocker' ),
			'PK' => __( 'Pakistan', 'geo-ip-blocker' ),
			'PW' => __( 'Palau', 'geo-ip-blocker' ),
			'PS' => __( 'Palestine, State of', 'geo-ip-blocker' ),
			'PA' => __( 'Panama', 'geo-ip-blocker' ),
			'PG' => __( 'Papua New Guinea', 'geo-ip-blocker' ),
			'PY' => __( 'Paraguay', 'geo-ip-blocker' ),
			'PE' => __( 'Peru', 'geo-ip-blocker' ),
			'PH' => __( 'Philippines', 'geo-ip-blocker' ),
			'PN' => __( 'Pitcairn', 'geo-ip-blocker' ),
			'PL' => __( 'Poland', 'geo-ip-blocker' ),
			'PT' => __( 'Portugal', 'geo-ip-blocker' ),
			'PR' => __( 'Puerto Rico', 'geo-ip-blocker' ),
			'QA' => __( 'Qatar', 'geo-ip-blocker' ),
			'RE' => __( 'Réunion', 'geo-ip-blocker' ),
			'RO' => __( 'Romania', 'geo-ip-blocker' ),
			'RU' => __( 'Russian Federation', 'geo-ip-blocker' ),
			'RW' => __( 'Rwanda', 'geo-ip-blocker' ),
			'BL' => __( 'Saint Barthélemy', 'geo-ip-blocker' ),
			'SH' => __( 'Saint Helena, Ascension and Tristan da Cunha', 'geo-ip-blocker' ),
			'KN' => __( 'Saint Kitts and Nevis', 'geo-ip-blocker' ),
			'LC' => __( 'Saint Lucia', 'geo-ip-blocker' ),
			'MF' => __( 'Saint Martin (French part)', 'geo-ip-blocker' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'geo-ip-blocker' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'geo-ip-blocker' ),
			'WS' => __( 'Samoa', 'geo-ip-blocker' ),
			'SM' => __( 'San Marino', 'geo-ip-blocker' ),
			'ST' => __( 'Sao Tome and Principe', 'geo-ip-blocker' ),
			'SA' => __( 'Saudi Arabia', 'geo-ip-blocker' ),
			'SN' => __( 'Senegal', 'geo-ip-blocker' ),
			'RS' => __( 'Serbia', 'geo-ip-blocker' ),
			'SC' => __( 'Seychelles', 'geo-ip-blocker' ),
			'SL' => __( 'Sierra Leone', 'geo-ip-blocker' ),
			'SG' => __( 'Singapore', 'geo-ip-blocker' ),
			'SX' => __( 'Sint Maarten (Dutch part)', 'geo-ip-blocker' ),
			'SK' => __( 'Slovakia', 'geo-ip-blocker' ),
			'SI' => __( 'Slovenia', 'geo-ip-blocker' ),
			'SB' => __( 'Solomon Islands', 'geo-ip-blocker' ),
			'SO' => __( 'Somalia', 'geo-ip-blocker' ),
			'ZA' => __( 'South Africa', 'geo-ip-blocker' ),
			'GS' => __( 'South Georgia and the South Sandwich Islands', 'geo-ip-blocker' ),
			'SS' => __( 'South Sudan', 'geo-ip-blocker' ),
			'ES' => __( 'Spain', 'geo-ip-blocker' ),
			'LK' => __( 'Sri Lanka', 'geo-ip-blocker' ),
			'SD' => __( 'Sudan', 'geo-ip-blocker' ),
			'SR' => __( 'Suriname', 'geo-ip-blocker' ),
			'SJ' => __( 'Svalbard and Jan Mayen', 'geo-ip-blocker' ),
			'SZ' => __( 'Swaziland', 'geo-ip-blocker' ),
			'SE' => __( 'Sweden', 'geo-ip-blocker' ),
			'CH' => __( 'Switzerland', 'geo-ip-blocker' ),
			'SY' => __( 'Syrian Arab Republic', 'geo-ip-blocker' ),
			'TW' => __( 'Taiwan', 'geo-ip-blocker' ),
			'TJ' => __( 'Tajikistan', 'geo-ip-blocker' ),
			'TZ' => __( 'Tanzania', 'geo-ip-blocker' ),
			'TH' => __( 'Thailand', 'geo-ip-blocker' ),
			'TL' => __( 'Timor-Leste', 'geo-ip-blocker' ),
			'TG' => __( 'Togo', 'geo-ip-blocker' ),
			'TK' => __( 'Tokelau', 'geo-ip-blocker' ),
			'TO' => __( 'Tonga', 'geo-ip-blocker' ),
			'TT' => __( 'Trinidad and Tobago', 'geo-ip-blocker' ),
			'TN' => __( 'Tunisia', 'geo-ip-blocker' ),
			'TR' => __( 'Turkey', 'geo-ip-blocker' ),
			'TM' => __( 'Turkmenistan', 'geo-ip-blocker' ),
			'TC' => __( 'Turks and Caicos Islands', 'geo-ip-blocker' ),
			'TV' => __( 'Tuvalu', 'geo-ip-blocker' ),
			'UG' => __( 'Uganda', 'geo-ip-blocker' ),
			'UA' => __( 'Ukraine', 'geo-ip-blocker' ),
			'AE' => __( 'United Arab Emirates', 'geo-ip-blocker' ),
			'GB' => __( 'United Kingdom', 'geo-ip-blocker' ),
			'US' => __( 'United States', 'geo-ip-blocker' ),
			'UM' => __( 'United States Minor Outlying Islands', 'geo-ip-blocker' ),
			'UY' => __( 'Uruguay', 'geo-ip-blocker' ),
			'UZ' => __( 'Uzbekistan', 'geo-ip-blocker' ),
			'VU' => __( 'Vanuatu', 'geo-ip-blocker' ),
			'VE' => __( 'Venezuela', 'geo-ip-blocker' ),
			'VN' => __( 'Vietnam', 'geo-ip-blocker' ),
			'VG' => __( 'Virgin Islands, British', 'geo-ip-blocker' ),
			'VI' => __( 'Virgin Islands, U.S.', 'geo-ip-blocker' ),
			'WF' => __( 'Wallis and Futuna', 'geo-ip-blocker' ),
			'EH' => __( 'Western Sahara', 'geo-ip-blocker' ),
			'YE' => __( 'Yemen', 'geo-ip-blocker' ),
			'ZM' => __( 'Zambia', 'geo-ip-blocker' ),
			'ZW' => __( 'Zimbabwe', 'geo-ip-blocker' ),
		);
	}
}
