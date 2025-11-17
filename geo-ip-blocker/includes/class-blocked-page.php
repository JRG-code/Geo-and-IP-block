<?php
/**
 * Blocked Page Handler
 *
 * Handles rendering of blocked access messages
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_IP_Blocker_Blocked_Page
 *
 * Renders blocked access pages with customizable templates
 */
class Geo_IP_Blocker_Blocked_Page {

	/**
	 * Render the blocked message page
	 *
	 * @param array $data Block data (ip, country, country_code, reason).
	 */
	public static function render( $data = array() ) {
		// Get settings.
		$settings = get_option( 'geo_ip_blocker_settings', array() );

		// Prepare template variables.
		$vars = array(
			'message'      => isset( $settings['block_message'] ) ? $settings['block_message'] : __( 'Acesso negado. Sua localização não tem permissão para acessar este site.', 'geo-ip-blocker' ),
			'reason'       => isset( $data['reason'] ) ? $data['reason'] : __( 'Sua localização geográfica está bloqueada.', 'geo-ip-blocker' ),
			'ip'           => isset( $data['ip'] ) ? $data['ip'] : '',
			'country'      => isset( $data['country'] ) ? $data['country'] : '',
			'country_code' => isset( $data['country_code'] ) ? $data['country_code'] : '',
			'show_details' => isset( $settings['show_block_details'] ) ? $settings['show_block_details'] : false,
			'contact_url'  => isset( $settings['contact_url'] ) ? $settings['contact_url'] : '',
		);

		// Allow filtering of template variables.
		$vars = apply_filters( 'geo_blocker_message_vars', $vars, $data );

		// Get template path.
		$template = self::get_template_path();

		// Ensure we don't cache this page.
		nocache_headers();

		// Set HTTP status code.
		status_header( 403 );

		// Extract variables for template use.
		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		// Include the template.
		if ( file_exists( $template ) ) {
			include $template;
		} else {
			// Fallback if template is missing.
			self::render_fallback( $vars );
		}

		// Stop execution.
		exit;
	}

	/**
	 * Get the template path
	 *
	 * Allows themes to override by placing template in:
	 * wp-content/themes/your-theme/geo-blocker/blocked-message.php
	 *
	 * @return string Template file path.
	 */
	public static function get_template_path() {
		$template_name = 'blocked-message.php';

		// Check if theme has override.
		$theme_template = get_stylesheet_directory() . '/geo-blocker/' . $template_name;
		if ( file_exists( $theme_template ) ) {
			return apply_filters( 'geo_blocker_template_path', $theme_template, $template_name );
		}

		// Check parent theme.
		$parent_template = get_template_directory() . '/geo-blocker/' . $template_name;
		if ( file_exists( $parent_template ) && $parent_template !== $theme_template ) {
			return apply_filters( 'geo_blocker_template_path', $parent_template, $template_name );
		}

		// Use plugin default.
		$plugin_template = GEO_IP_BLOCKER_PLUGIN_DIR . 'templates/' . $template_name;

		return apply_filters( 'geo_blocker_template_path', $plugin_template, $template_name );
	}

	/**
	 * Process shortcodes in message
	 *
	 * @param string $content Content with shortcodes.
	 * @param array  $data    Data for shortcode replacement.
	 * @return string Processed content.
	 */
	public static function process_shortcodes( $content, $data = array() ) {
		$replacements = array(
			'[geo_blocker_ip]'           => isset( $data['ip'] ) ? esc_html( $data['ip'] ) : '',
			'[geo_blocker_country]'      => isset( $data['country'] ) ? esc_html( $data['country'] ) : '',
			'[geo_blocker_country_code]' => isset( $data['country_code'] ) ? esc_html( $data['country_code'] ) : '',
			'[geo_blocker_reason]'       => isset( $data['reason'] ) ? esc_html( $data['reason'] ) : '',
			'[geo_blocker_date]'         => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'[geo_blocker_site_name]'    => get_bloginfo( 'name' ),
			'[geo_blocker_site_url]'     => home_url(),
		);

		// Allow filtering of shortcode replacements.
		$replacements = apply_filters( 'geo_blocker_shortcode_replacements', $replacements, $data );

		// Replace shortcodes.
		$content = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );

		return $content;
	}

	/**
	 * Register shortcodes for WordPress
	 */
	public static function register_shortcodes() {
		add_shortcode( 'geo_blocker_ip', array( __CLASS__, 'shortcode_ip' ) );
		add_shortcode( 'geo_blocker_country', array( __CLASS__, 'shortcode_country' ) );
		add_shortcode( 'geo_blocker_country_code', array( __CLASS__, 'shortcode_country_code' ) );
		add_shortcode( 'geo_blocker_reason', array( __CLASS__, 'shortcode_reason' ) );
		add_shortcode( 'geo_blocker_date', array( __CLASS__, 'shortcode_date' ) );
		add_shortcode( 'geo_blocker_site_name', array( __CLASS__, 'shortcode_site_name' ) );
		add_shortcode( 'geo_blocker_site_url', array( __CLASS__, 'shortcode_site_url' ) );
	}

	/**
	 * Shortcode: Display visitor IP
	 *
	 * @return string IP address.
	 */
	public static function shortcode_ip() {
		$geolocation = geo_ip_blocker_get_geolocation();
		return $geolocation ? esc_html( $geolocation->get_visitor_ip() ) : '';
	}

	/**
	 * Shortcode: Display visitor country
	 *
	 * @return string Country name.
	 */
	public static function shortcode_country() {
		$geolocation = geo_ip_blocker_get_geolocation();
		if ( ! $geolocation ) {
			return '';
		}

		$location = $geolocation->get_location_data( $geolocation->get_visitor_ip() );
		return isset( $location['country_name'] ) ? esc_html( $location['country_name'] ) : '';
	}

	/**
	 * Shortcode: Display visitor country code
	 *
	 * @return string Country code.
	 */
	public static function shortcode_country_code() {
		$geolocation = geo_ip_blocker_get_geolocation();
		if ( ! $geolocation ) {
			return '';
		}

		$location = $geolocation->get_location_data( $geolocation->get_visitor_ip() );
		return isset( $location['country_code'] ) ? esc_html( $location['country_code'] ) : '';
	}

	/**
	 * Shortcode: Display block reason
	 *
	 * @return string Block reason.
	 */
	public static function shortcode_reason() {
		return esc_html__( 'Acesso bloqueado por restrição geográfica', 'geo-ip-blocker' );
	}

	/**
	 * Shortcode: Display current date/time
	 *
	 * @return string Formatted date.
	 */
	public static function shortcode_date() {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
	}

	/**
	 * Shortcode: Display site name
	 *
	 * @return string Site name.
	 */
	public static function shortcode_site_name() {
		return get_bloginfo( 'name' );
	}

	/**
	 * Shortcode: Display site URL
	 *
	 * @return string Site URL.
	 */
	public static function shortcode_site_url() {
		return home_url();
	}

	/**
	 * Render fallback message if template is missing
	 *
	 * @param array $vars Template variables.
	 */
	private static function render_fallback( $vars ) {
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php esc_html_e( 'Acesso Restrito', 'geo-ip-blocker' ); ?></title>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
					background: #f3f4f6;
					display: flex;
					align-items: center;
					justify-content: center;
					min-height: 100vh;
					margin: 0;
					padding: 20px;
				}
				.container {
					background: white;
					padding: 40px;
					border-radius: 8px;
					box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
					max-width: 500px;
					width: 100%;
					text-align: center;
				}
				h1 {
					color: #1f2937;
					margin: 0 0 16px;
					font-size: 24px;
				}
				p {
					color: #6b7280;
					line-height: 1.6;
					margin: 0;
				}
				.info {
					background: #fee;
					padding: 16px;
					border-radius: 6px;
					margin-top: 20px;
					text-align: left;
				}
				.info strong {
					display: block;
					margin-bottom: 8px;
					color: #991b1b;
				}
			</style>
		</head>
		<body>
			<div class="container">
				<h1><?php esc_html_e( 'Acesso Restrito', 'geo-ip-blocker' ); ?></h1>
				<p><?php echo wp_kses_post( $vars['message'] ); ?></p>
				<?php if ( $vars['reason'] ) : ?>
				<div class="info">
					<strong><?php esc_html_e( 'Motivo:', 'geo-ip-blocker' ); ?></strong>
					<?php echo esc_html( $vars['reason'] ); ?>
				</div>
				<?php endif; ?>
			</div>
		</body>
		</html>
		<?php
	}
}

// Register shortcodes.
Geo_IP_Blocker_Blocked_Page::register_shortcodes();
