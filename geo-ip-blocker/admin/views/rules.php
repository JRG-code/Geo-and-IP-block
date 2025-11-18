<?php
/**
 * Rules view.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$settings = get_option( 'geo_ip_blocker_settings', array() );
$blocking_enabled = ! empty( $settings['enabled'] );
$blocking_mode = isset( $settings['blocking_mode'] ) ? $settings['blocking_mode'] : 'blacklist';

// Get counts
$blocked_countries = isset( $settings['blocked_countries'] ) && is_array( $settings['blocked_countries'] ) ? $settings['blocked_countries'] : array();
$allowed_countries = isset( $settings['allowed_countries'] ) && is_array( $settings['allowed_countries'] ) ? $settings['allowed_countries'] : array();

global $wpdb;
$blocked_ips_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}geo_ip_blocker_ips WHERE type = 'blocked'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$allowed_ips_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}geo_ip_blocker_ips WHERE type = 'allowed'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="notice notice-info">
		<p>
			<?php esc_html_e( 'Manage your geo-blocking and IP blocking rules from the Settings page tabs.', 'geo-ip-blocker' ); ?>
		</p>
	</div>

	<div class="geo-ip-blocker-rules">
		<!-- Current Status -->
		<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h2><?php esc_html_e( 'Current Blocking Configuration', 'geo-ip-blocker' ); ?></h2>
			<table class="widefat">
				<tbody>
					<tr>
						<th style="width: 200px;"><?php esc_html_e( 'Status', 'geo-ip-blocker' ); ?></th>
						<td>
							<?php if ( $blocking_enabled ) : ?>
								<span style="color: #46b450; font-weight: bold;">✓ <?php esc_html_e( 'Active', 'geo-ip-blocker' ); ?></span>
							<?php else : ?>
								<span style="color: #dc3232; font-weight: bold;">✗ <?php esc_html_e( 'Inactive', 'geo-ip-blocker' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Blocking Mode', 'geo-ip-blocker' ); ?></th>
						<td><strong><?php echo esc_html( ucfirst( $blocking_mode ) ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Blocked Countries', 'geo-ip-blocker' ); ?></th>
						<td>
							<?php if ( ! empty( $blocked_countries ) ) : ?>
								<strong><?php echo count( $blocked_countries ); ?></strong> countries:
								<code><?php echo esc_html( implode( ', ', array_slice( $blocked_countries, 0, 10 ) ) ); ?></code>
								<?php if ( count( $blocked_countries ) > 10 ) : ?>
									<?php
									printf(
										/* translators: %s: number of additional countries */
										esc_html__( '+ %s more', 'geo-ip-blocker' ),
										count( $blocked_countries ) - 10
									);
									?>
								<?php endif; ?>
							<?php else : ?>
								<?php esc_html_e( 'None', 'geo-ip-blocker' ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Allowed Countries', 'geo-ip-blocker' ); ?></th>
						<td>
							<?php if ( ! empty( $allowed_countries ) ) : ?>
								<strong><?php echo count( $allowed_countries ); ?></strong> countries:
								<code><?php echo esc_html( implode( ', ', array_slice( $allowed_countries, 0, 10 ) ) ); ?></code>
								<?php if ( count( $allowed_countries ) > 10 ) : ?>
									<?php
									printf(
										/* translators: %s: number of additional countries */
										esc_html__( '+ %s more', 'geo-ip-blocker' ),
										count( $allowed_countries ) - 10
									);
									?>
								<?php endif; ?>
							<?php else : ?>
								<?php esc_html_e( 'None', 'geo-ip-blocker' ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Blocked IPs', 'geo-ip-blocker' ); ?></th>
						<td><strong><?php echo number_format_i18n( (int) $blocked_ips_count ); ?></strong> IP addresses</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Allowed IPs', 'geo-ip-blocker' ); ?></th>
						<td><strong><?php echo number_format_i18n( (int) $allowed_ips_count ); ?></strong> IP addresses</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Quick Links to Rule Management -->
		<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h2><?php esc_html_e( 'Manage Rules', 'geo-ip-blocker' ); ?></h2>
			<p><?php esc_html_e( 'Configure your blocking rules using the settings page:', 'geo-ip-blocker' ); ?></p>

			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 20px;">
				<!-- General Settings -->
				<div style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'General Settings', 'geo-ip-blocker' ); ?></h3>
					<p><?php esc_html_e( 'Enable/disable blocking, set blocking mode, configure block actions', 'geo-ip-blocker' ); ?></p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-settings' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Configure General Settings', 'geo-ip-blocker' ); ?>
						</a>
					</p>
				</div>

				<!-- Country Blocking -->
				<div style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'Country Blocking', 'geo-ip-blocker' ); ?></h3>
					<p><?php esc_html_e( 'Block or allow access by country and region', 'geo-ip-blocker' ); ?></p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-settings&tab=countries' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Manage Countries', 'geo-ip-blocker' ); ?>
						</a>
					</p>
				</div>

				<!-- IP Blocking -->
				<div style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'IP Blocking', 'geo-ip-blocker' ); ?></h3>
					<p><?php esc_html_e( 'Block or allow specific IP addresses and ranges', 'geo-ip-blocker' ); ?></p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-settings&tab=ips' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Manage IPs', 'geo-ip-blocker' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
