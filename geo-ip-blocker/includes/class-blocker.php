<?php
/**
 * Main Blocker class.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_Blocker_Blocker
 *
 * Handles the main blocking logic with exceptions and actions.
 */
class Geo_Blocker_Blocker {

	/**
	 * Geolocation instance.
	 *
	 * @var Geo_Blocker_Geolocation
	 */
	private $geolocation;

	/**
	 * IP Manager instance.
	 *
	 * @var Geo_Blocker_IP_Manager
	 */
	private $ip_manager;

	/**
	 * Database instance.
	 *
	 * @var Geo_IP_Blocker_Database
	 */
	private $database;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->geolocation = new Geo_Blocker_Geolocation();
		$this->ip_manager  = new Geo_Blocker_IP_Manager();
		$this->database    = new Geo_IP_Blocker_Database();

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Use template_redirect with priority 1 for early execution.
		add_action( 'template_redirect', array( $this, 'check_access' ), 1 );
	}

	/**
	 * Check if access should be blocked.
	 */
	public function check_access() {
		// Skip if admin area, AJAX, cron, or REST API.
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || $this->is_rest_request() ) {
			return;
		}

		// Check if blocking is enabled.
		if ( ! geo_ip_blocker_get_setting( 'enable_blocking', true ) ) {
			return;
		}

		// Get visitor IP.
		$ip_address = $this->geolocation->get_visitor_ip();

		if ( empty( $ip_address ) ) {
			return;
		}

		// Check if user is exempted.
		if ( $this->is_user_exempted() ) {
			return;
		}

		// Check if page is exempted.
		if ( $this->is_page_exempted() ) {
			return;
		}

		// Get country code.
		$location_data = $this->geolocation->get_location_data( $ip_address );
		$country_code  = isset( $location_data['country_code'] ) ? $location_data['country_code'] : 'UNKNOWN';

		// Check if should block.
		$should_block = $this->should_block( $ip_address, $country_code );

		// Allow filtering.
		$should_block = apply_filters( 'geo_blocker_should_block', $should_block, $ip_address, $country_code, $location_data );

		if ( $should_block ) {
			$reason = $this->get_block_reason( $ip_address, $country_code );

			// Fire action before blocking.
			do_action( 'geo_blocker_access_blocked', $ip_address, $country_code, $reason, $location_data );

			// Log blocked access.
			$this->log_blocked_access( $ip_address, $location_data, $reason );

			// Apply block action.
			$this->apply_block( $reason, $ip_address, $country_code );
		}
	}

	/**
	 * Check if current request is REST API request.
	 *
	 * @return bool
	 */
	private function is_rest_request() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$rest_prefix = trailingslashit( rest_get_url_prefix() );
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

			return ( strpos( $request_uri, $rest_prefix ) !== false );
		}

		return false;
	}

	/**
	 * Determine if access should be blocked.
	 *
	 * @param string $ip      IP address.
	 * @param string $country Country code.
	 * @return bool
	 */
	public function should_block( $ip, $country ) {
		// Order of verification:
		// 1. IP Whitelist (always allow).
		if ( $this->ip_manager->is_ip_allowed( $ip ) ) {
			return false;
		}

		// 2. IP Blacklist (always block).
		if ( $this->ip_manager->is_ip_blocked( $ip ) ) {
			return true;
		}

		// Get blocking mode.
		$blocking_mode = geo_ip_blocker_get_setting( 'blocking_mode', 'blacklist' );

		// 3. Country Whitelist mode.
		if ( 'whitelist' === $blocking_mode ) {
			$whitelist_countries = geo_ip_blocker_get_setting( 'whitelist_countries', array() );
			return ! in_array( $country, $whitelist_countries, true );
		}

		// 4. Country Blacklist mode.
		if ( 'blacklist' === $blocking_mode ) {
			$blacklist_countries = geo_ip_blocker_get_setting( 'blacklist_countries', array() );
			return in_array( $country, $blacklist_countries, true );
		}

		// 5. Check database rules (for advanced rules).
		return $this->check_database_rules( $ip, $country );
	}

	/**
	 * Check database rules for blocking.
	 *
	 * @param string $ip      IP address.
	 * @param string $country Country code.
	 * @return bool
	 */
	private function check_database_rules( $ip, $country ) {
		$rules = $this->database->get_rules(
			array(
				'orderby' => 'priority',
				'order'   => 'ASC',
			)
		);

		if ( empty( $rules ) ) {
			return false;
		}

		$should_block = false;

		foreach ( $rules as $rule ) {
			$match = false;

			switch ( $rule->rule_type ) {
				case 'ip':
					$match = $this->ip_manager->is_ip_in_cidr( $ip, $rule->value ) ||
							$this->ip_manager->is_ip_in_hyphen_range( $ip, $rule->value ) ||
							$ip === $rule->value;
					break;

				case 'country':
					$match = ( strtoupper( $country ) === strtoupper( $rule->value ) );
					break;
			}

			if ( $match ) {
				// 'allow' action overrides previous blocks.
				if ( 'allow' === $rule->action ) {
					return false;
				}

				// 'block' action marks for blocking.
				if ( 'block' === $rule->action ) {
					$should_block = true;
				}
			}
		}

		return $should_block;
	}

	/**
	 * Check if current user is exempted.
	 *
	 * @return bool
	 */
	private function is_user_exempted() {
		// Always exempt administrators if configured.
		if ( geo_ip_blocker_get_setting( 'exempt_administrators', true ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Check if user is logged in and logged-in users are exempted.
		if ( geo_ip_blocker_get_setting( 'exempt_logged_in', false ) && is_user_logged_in() ) {
			return true;
		}

		// Check exempted roles.
		$exempted_roles = geo_ip_blocker_get_setting( 'exempted_roles', array() );
		if ( ! empty( $exempted_roles ) && is_user_logged_in() ) {
			$user = wp_get_current_user();
			foreach ( $exempted_roles as $role ) {
				if ( in_array( $role, $user->roles, true ) ) {
					return true;
				}
			}
		}

		// Check exempted user IDs.
		$exempted_users = geo_ip_blocker_get_setting( 'exempted_users', array() );
		if ( ! empty( $exempted_users ) && is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( in_array( $user_id, $exempted_users, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if current page is exempted.
	 *
	 * @return bool
	 */
	private function is_page_exempted() {
		global $post;

		// Check exempted pages/posts.
		$exempted_pages = geo_ip_blocker_get_setting( 'exempted_pages', array() );
		if ( ! empty( $exempted_pages ) && is_singular() && $post ) {
			if ( in_array( $post->ID, $exempted_pages, true ) ) {
				return true;
			}
		}

		// Check exempted URLs.
		$exempted_urls = geo_ip_blocker_get_setting( 'exempted_urls', array() );
		if ( ! empty( $exempted_urls ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$current_url = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

			foreach ( $exempted_urls as $url ) {
				if ( strpos( $current_url, $url ) !== false ) {
					return true;
				}
			}
		}

		// Check WooCommerce specific exemptions.
		if ( $this->is_woocommerce_active() ) {
			$woo_blocking_mode = geo_ip_blocker_get_setting( 'woo_blocking_mode', 'all' );

			// If mode is 'none', exempt all WooCommerce pages.
			if ( 'none' === $woo_blocking_mode && $this->is_woocommerce_page() ) {
				return true;
			}

			// If mode is 'shop_only', exempt non-shop pages.
			if ( 'shop_only' === $woo_blocking_mode && ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
				return true;
			}

			// If mode is 'checkout_only', exempt non-checkout pages.
			if ( 'checkout_only' === $woo_blocking_mode && ! is_checkout() && ! is_cart() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check if current page is a WooCommerce page.
	 *
	 * @return bool
	 */
	private function is_woocommerce_page() {
		if ( ! $this->is_woocommerce_active() ) {
			return false;
		}

		return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
	}

	/**
	 * Get block reason.
	 *
	 * @param string $ip      IP address.
	 * @param string $country Country code.
	 * @return string
	 */
	private function get_block_reason( $ip, $country ) {
		if ( $this->ip_manager->is_ip_blocked( $ip ) ) {
			return 'IP Blacklist';
		}

		$blocking_mode = geo_ip_blocker_get_setting( 'blocking_mode', 'blacklist' );

		if ( 'whitelist' === $blocking_mode ) {
			return sprintf( 'Country not in whitelist (%s)', $country );
		}

		if ( 'blacklist' === $blocking_mode ) {
			return sprintf( 'Country blocked (%s)', $country );
		}

		return 'Blocking rule matched';
	}

	/**
	 * Apply block action.
	 *
	 * @param string $reason       Block reason.
	 * @param string $ip_address   IP address.
	 * @param string $country_code Country code.
	 */
	public function apply_block( $reason, $ip_address = '', $country_code = '' ) {
		$block_action = geo_ip_blocker_get_setting( 'block_action', 'message' );

		switch ( $block_action ) {
			case 'redirect':
				$this->redirect_blocked_user( $ip_address, $country_code );
				break;

			case 'page':
				$this->show_blocked_page( $reason );
				break;

			case '403':
				$this->show_403_error( $reason );
				break;

			case 'message':
			default:
				$this->show_blocked_message( $reason );
				break;
		}
	}

	/**
	 * Show blocked message.
	 *
	 * @param string $reason Block reason.
	 */
	private function show_blocked_message( $reason ) {
		$message = geo_ip_blocker_get_setting(
			'block_message',
			__( 'Access denied. Your location or IP address is not allowed to access this site.', 'geo-ip-blocker' )
		);

		// Allow HTML in message.
		$message = wp_kses_post( $message );

		// Allow filtering.
		$message = apply_filters( 'geo_blocker_blocked_message', $message, $reason );

		// Check for custom template.
		$template = apply_filters( 'geo_blocker_blocked_template', '' );

		if ( ! empty( $template ) && file_exists( $template ) ) {
			include $template;
			exit;
		}

		// Default WordPress die screen.
		wp_die(
			$message,
			esc_html__( 'Access Denied', 'geo-ip-blocker' ),
			array(
				'response' => 403,
				'back_link' => false,
			)
		);
	}

	/**
	 * Show 403 error.
	 *
	 * @param string $reason Block reason.
	 */
	private function show_403_error( $reason ) {
		status_header( 403 );
		nocache_headers();

		$message = apply_filters(
			'geo_blocker_403_message',
			__( '403 Forbidden - Access Denied', 'geo-ip-blocker' ),
			$reason
		);

		echo '<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>' . esc_html__( '403 Forbidden', 'geo-ip-blocker' ) . '</title>
	<style>
		body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
		h1 { font-size: 50px; }
		p { font-size: 20px; }
	</style>
</head>
<body>
	<h1>403</h1>
	<p>' . esc_html( $message ) . '</p>
</body>
</html>';

		exit;
	}

	/**
	 * Show custom blocked page.
	 *
	 * @param string $reason Block reason.
	 */
	private function show_blocked_page( $reason ) {
		$page_id = geo_ip_blocker_get_setting( 'block_page_id', 0 );

		if ( empty( $page_id ) ) {
			$this->show_blocked_message( $reason );
			return;
		}

		// Get page content.
		$page = get_post( $page_id );

		if ( ! $page ) {
			$this->show_blocked_message( $reason );
			return;
		}

		// Setup post data.
		global $post;
		$post = $page;
		setup_postdata( $post );

		// Set 403 status.
		status_header( 403 );

		// Load page template.
		get_header();
		?>
		<div class="geo-blocker-blocked-page">
			<h1><?php echo esc_html( get_the_title() ); ?></h1>
			<div class="content">
				<?php echo wp_kses_post( apply_filters( 'the_content', get_the_content() ) ); ?>
			</div>
		</div>
		<?php
		get_footer();

		wp_reset_postdata();
		exit;
	}

	/**
	 * Redirect blocked user.
	 *
	 * @param string $ip_address   IP address.
	 * @param string $country_code Country code.
	 */
	private function redirect_blocked_user( $ip_address = '', $country_code = '' ) {
		$redirect_url = geo_ip_blocker_get_setting( 'redirect_url', home_url() );

		// Allow filtering.
		$redirect_url = apply_filters( 'geo_blocker_redirect_url', $redirect_url, $ip_address, $country_code );

		// Validate URL.
		$redirect_url = esc_url_raw( $redirect_url );

		if ( empty( $redirect_url ) ) {
			$this->show_blocked_message( 'Redirect URL not configured' );
			return;
		}

		wp_safe_redirect( $redirect_url, 302 );
		exit;
	}

	/**
	 * Log blocked access.
	 *
	 * @param string $ip_address    IP address.
	 * @param array  $location_data Location data.
	 * @param string $reason        Block reason.
	 */
	private function log_blocked_access( $ip_address, $location_data, $reason ) {
		// Check if logging is enabled.
		if ( ! geo_ip_blocker_get_setting( 'enable_logging', true ) ) {
			return;
		}

		$this->database->add_log(
			array(
				'ip_address'   => $ip_address,
				'country_code' => isset( $location_data['country_code'] ) ? $location_data['country_code'] : '',
				'region'       => isset( $location_data['region'] ) ? $location_data['region'] : '',
				'city'         => isset( $location_data['city'] ) ? $location_data['city'] : '',
				'blocked_url'  => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
				'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'block_reason' => $reason,
			)
		);
	}

	/**
	 * Get blocking statistics.
	 *
	 * @return array
	 */
	public function get_statistics() {
		$stats = array(
			'total_blocks'       => $this->database->get_logs_count(),
			'blocks_today'       => 0,
			'blocks_this_week'   => 0,
			'blocks_this_month'  => 0,
			'top_countries'      => array(),
			'top_ips'            => array(),
		);

		// Get blocks today.
		$today_start = gmdate( 'Y-m-d 00:00:00' );
		// This would need a custom query in database class.

		return $stats;
	}
}
