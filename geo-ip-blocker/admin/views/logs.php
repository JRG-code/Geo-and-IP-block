<?php
/**
 * Logs view.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load logs page class.
require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'admin/class-logs-page.php';

// Create instance.
$logs_table = new Geo_IP_Blocker_Logs_Page();

// Process bulk actions.
$logs_table->process_bulk_action();

// Prepare items.
$logs_table->prepare_items();
?>

<div class="wrap geo-ip-blocker-logs">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors( 'geo_ip_blocker_logs' ); ?>

	<!-- Statistics Cards -->
	<?php $logs_table->display_statistics(); ?>

	<!-- Filters -->
	<div class="logs-filters-container">
		<form method="get" id="logs-filter-form">
			<input type="hidden" name="page" value="geo-ip-blocker-logs">
			<?php $logs_table->display_filters(); ?>
		</form>
	</div>

	<!-- Charts -->
	<?php $logs_table->display_charts(); ?>

	<!-- Logs Table -->
	<div class="logs-table-container">
		<h2><?php esc_html_e( 'Block Logs', 'geo-ip-blocker' ); ?></h2>
		<form method="post" id="logs-table-form">
			<?php
			$logs_table->display();
			?>
		</form>
	</div>

	<!-- Log Cleanup Settings -->
	<div class="logs-cleanup-container">
		<h2><?php esc_html_e( 'Log Maintenance', 'geo-ip-blocker' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="logs-cleanup-form">
			<input type="hidden" name="action" value="geo_ip_blocker_cleanup_logs">
			<?php wp_nonce_field( 'geo_ip_blocker_cleanup_logs', 'cleanup_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="log_retention_days"><?php esc_html_e( 'Keep logs for:', 'geo-ip-blocker' ); ?></label>
					</th>
					<td>
						<?php
						$settings        = get_option( 'geo_ip_blocker_settings', array() );
						$retention_days  = isset( $settings['log_retention_days'] ) ? $settings['log_retention_days'] : 90;
						$max_logs        = isset( $settings['max_logs'] ) ? $settings['max_logs'] : 10000;
						$auto_cleanup    = isset( $settings['auto_cleanup_logs'] ) ? $settings['auto_cleanup_logs'] : false;
						?>
						<select name="log_retention_days" id="log_retention_days">
							<option value="7" <?php selected( $retention_days, 7 ); ?>>7 <?php esc_html_e( 'days', 'geo-ip-blocker' ); ?></option>
							<option value="14" <?php selected( $retention_days, 14 ); ?>>14 <?php esc_html_e( 'days', 'geo-ip-blocker' ); ?></option>
							<option value="30" <?php selected( $retention_days, 30 ); ?>>30 <?php esc_html_e( 'days', 'geo-ip-blocker' ); ?></option>
							<option value="60" <?php selected( $retention_days, 60 ); ?>>60 <?php esc_html_e( 'days', 'geo-ip-blocker' ); ?></option>
							<option value="90" <?php selected( $retention_days, 90 ); ?>>90 <?php esc_html_e( 'days', 'geo-ip-blocker' ); ?></option>
							<option value="180" <?php selected( $retention_days, 180 ); ?>>180 <?php esc_html_e( 'days', 'geo-ip-blocker' ); ?></option>
							<option value="365" <?php selected( $retention_days, 365 ); ?>>365 <?php esc_html_e( 'days', 'geo-ip-blocker' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Automatically delete logs older than this period.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="max_logs"><?php esc_html_e( 'Maximum logs:', 'geo-ip-blocker' ); ?></label>
					</th>
					<td>
						<input type="number" name="max_logs" id="max_logs" value="<?php echo esc_attr( $max_logs ); ?>" min="1000" step="1000" class="small-text">
						<p class="description"><?php esc_html_e( 'Maximum number of log entries to keep. Oldest entries will be deleted when limit is reached.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Automatic cleanup:', 'geo-ip-blocker' ); ?>
					</th>
					<td>
						<label>
							<input type="checkbox" name="auto_cleanup_logs" value="1" <?php checked( $auto_cleanup, true ); ?>>
							<?php esc_html_e( 'Automatically clean up old logs daily', 'geo-ip-blocker' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Enable automatic cleanup based on retention period and maximum logs settings.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="cleanup_action" value="save_cleanup_settings" class="button button-primary">
					<?php esc_html_e( 'Save Settings', 'geo-ip-blocker' ); ?>
				</button>
				<button type="submit" name="cleanup_action" value="cleanup_now" class="button button-secondary">
					<?php esc_html_e( 'Clean Up Now', 'geo-ip-blocker' ); ?>
				</button>
			</p>
		</form>
	</div>
</div>

<?php
// Get chart data for JavaScript.
$logger = geo_ip_blocker_get_logger();

$chart_data = array(
	'timeline'      => $logger->get_timeline_data( 30 ),
	'top_countries' => $logger->get_top_countries( 10 ),
	'top_ips'       => $logger->get_top_ips( 10 ),
	'block_reasons' => $logger->get_block_reasons_stats(),
);
?>

<script type="text/javascript">
	var geoIPBlockerLogsData = <?php echo wp_json_encode( $chart_data ); ?>;
</script>
