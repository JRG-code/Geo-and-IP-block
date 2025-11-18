<?php
/**
 * Dashboard view.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get stats
$settings = get_option( 'geo_ip_blocker_settings', array() );

// Get recent blocks count
global $wpdb;
$table_name = $wpdb->prefix . 'geo_ip_blocker_logs';
$total_blocks = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE action = 'blocked'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$blocks_today = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$table_name} WHERE action = 'blocked' AND DATE(timestamp) = %s",
		current_time( 'Y-m-d' )
	)
);

// Get IP counts
$blocked_ips_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}geo_ip_blocker_ips WHERE type = 'blocked'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$allowed_ips_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}geo_ip_blocker_ips WHERE type = 'allowed'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Get country counts from settings
$blocked_countries = isset( $settings['blocked_countries'] ) && is_array( $settings['blocked_countries'] ) ? count( $settings['blocked_countries'] ) : 0;
$allowed_countries = isset( $settings['allowed_countries'] ) && is_array( $settings['allowed_countries'] ) ? count( $settings['allowed_countries'] ) : 0;

$blocking_enabled = ! empty( $settings['enabled'] );
$blocking_mode = isset( $settings['blocking_mode'] ) ? $settings['blocking_mode'] : 'blacklist';
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="notice notice-info">
		<p><?php esc_html_e( 'Welcome to Geo & IP Blocker! Configure your blocking rules to protect your WooCommerce store.', 'geo-ip-blocker' ); ?></p>
	</div>

	<div class="geo-ip-blocker-dashboard">
		<!-- Status Card -->
		<div class="geo-dashboard-status" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h2><?php esc_html_e( 'Blocking Status', 'geo-ip-blocker' ); ?></h2>
			<p style="font-size: 18px; margin: 15px 0;">
				<?php if ( $blocking_enabled ) : ?>
					<span style="color: #46b450; font-weight: bold;">✓ <?php esc_html_e( 'Active', 'geo-ip-blocker' ); ?></span>
					<span style="color: #666; margin-left: 20px;">
						<?php
						printf(
							/* translators: %s: blocking mode (whitelist or blacklist) */
							esc_html__( 'Mode: %s', 'geo-ip-blocker' ),
							'<strong>' . esc_html( ucfirst( $blocking_mode ) ) . '</strong>'
						);
						?>
					</span>
				<?php else : ?>
					<span style="color: #dc3232; font-weight: bold;">✗ <?php esc_html_e( 'Inactive', 'geo-ip-blocker' ); ?></span>
				<?php endif; ?>
			</p>
			<?php if ( ! $blocking_enabled ) : ?>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-settings' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Enable Blocking', 'geo-ip-blocker' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>

		<!-- Quick Stats -->
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
			<!-- Total Blocks -->
			<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h3 style="margin: 0 0 10px; color: #1d2327;"><?php esc_html_e( 'Total Blocks', 'geo-ip-blocker' ); ?></h3>
				<p style="font-size: 32px; font-weight: bold; margin: 10px 0; color: #2271b1;">
					<?php echo number_format_i18n( (int) $total_blocks ); ?>
				</p>
				<p style="margin: 0; color: #646970; font-size: 13px;">
					<?php
					printf(
						/* translators: %s: number of blocks today */
						esc_html__( '%s today', 'geo-ip-blocker' ),
						'<strong>' . number_format_i18n( (int) $blocks_today ) . '</strong>'
					);
					?>
				</p>
			</div>

			<!-- Countries -->
			<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h3 style="margin: 0 0 10px; color: #1d2327;"><?php esc_html_e( 'Country Rules', 'geo-ip-blocker' ); ?></h3>
				<p style="font-size: 24px; margin: 10px 0;">
					<span style="color: #dc3232; font-weight: bold;"><?php echo number_format_i18n( $blocked_countries ); ?></span>
					<span style="color: #646970; font-size: 14px;"><?php esc_html_e( 'blocked', 'geo-ip-blocker' ); ?></span>
					<span style="margin: 0 8px; color: #dcdcde;">|</span>
					<span style="color: #46b450; font-weight: bold;"><?php echo number_format_i18n( $allowed_countries ); ?></span>
					<span style="color: #646970; font-size: 14px;"><?php esc_html_e( 'allowed', 'geo-ip-blocker' ); ?></span>
				</p>
			</div>

			<!-- IP Addresses -->
			<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h3 style="margin: 0 0 10px; color: #1d2327;"><?php esc_html_e( 'IP Rules', 'geo-ip-blocker' ); ?></h3>
				<p style="font-size: 24px; margin: 10px 0;">
					<span style="color: #dc3232; font-weight: bold;"><?php echo number_format_i18n( (int) $blocked_ips_count ); ?></span>
					<span style="color: #646970; font-size: 14px;"><?php esc_html_e( 'blocked', 'geo-ip-blocker' ); ?></span>
					<span style="margin: 0 8px; color: #dcdcde;">|</span>
					<span style="color: #46b450; font-weight: bold;"><?php echo number_format_i18n( (int) $allowed_ips_count ); ?></span>
					<span style="color: #646970; font-size: 14px;"><?php esc_html_e( 'allowed', 'geo-ip-blocker' ); ?></span>
				</p>
			</div>
		</div>

		<!-- Quick Actions -->
		<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<h2><?php esc_html_e( 'Quick Actions', 'geo-ip-blocker' ); ?></h2>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-settings' ) ); ?>" class="button button-primary button-large">
					<?php esc_html_e( 'Configure Settings', 'geo-ip-blocker' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-settings&tab=countries' ) ); ?>" class="button button-large" style="margin-left: 10px;">
					<?php esc_html_e( 'Manage Countries', 'geo-ip-blocker' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-settings&tab=ips' ) ); ?>" class="button button-large" style="margin-left: 10px;">
					<?php esc_html_e( 'Manage IPs', 'geo-ip-blocker' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-logs' ) ); ?>" class="button button-large" style="margin-left: 10px;">
					<?php esc_html_e( 'View Logs', 'geo-ip-blocker' ); ?>
				</a>
			</p>
		</div>

		<!-- Recent Activity -->
		<?php
		$recent_logs = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT * FROM {$table_name}
			WHERE action = 'blocked'
			ORDER BY timestamp DESC
			LIMIT 10"
		);
		?>
		<?php if ( ! empty( $recent_logs ) ) : ?>
			<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h2><?php esc_html_e( 'Recent Blocks', 'geo-ip-blocker' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Time', 'geo-ip-blocker' ); ?></th>
							<th><?php esc_html_e( 'IP Address', 'geo-ip-blocker' ); ?></th>
							<th><?php esc_html_e( 'Country', 'geo-ip-blocker' ); ?></th>
							<th><?php esc_html_e( 'Reason', 'geo-ip-blocker' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log->timestamp ); ?></td>
								<td><code><?php echo esc_html( $log->ip_address ); ?></code></td>
								<td>
									<?php
									$country_code = $log->country_code;
									if ( $country_code && $country_code !== 'Unknown' ) {
										echo esc_html( $country_code );
									} else {
										echo '—';
									}
									?>
								</td>
								<td><?php echo esc_html( $log->reason ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p style="margin-top: 15px;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-logs' ) ); ?>">
						<?php esc_html_e( 'View all logs →', 'geo-ip-blocker' ); ?>
					</a>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>
