<?php
/**
 * Logger class.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_Blocker_Logger
 *
 * Handles logging of blocked access attempts with statistics and export.
 */
class Geo_Blocker_Logger {

	/**
	 * Database instance.
	 *
	 * @var Geo_IP_Blocker_Database
	 */
	private $database;

	/**
	 * Maximum number of logs to keep.
	 *
	 * @var int
	 */
	private $max_logs = 10000;

	/**
	 * Days to keep logs.
	 *
	 * @var int
	 */
	private $retention_days = 90;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->database = new Geo_IP_Blocker_Database();

		$this->max_logs        = apply_filters( 'geo_blocker_max_logs', $this->max_logs );
		$this->retention_days  = apply_filters( 'geo_blocker_log_retention_days', $this->retention_days );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Schedule log cleanup.
		add_action( 'geo_blocker_cleanup_logs', array( $this, 'clean_old_logs' ) );

		// Register cleanup cron.
		if ( ! wp_next_scheduled( 'geo_blocker_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'geo_blocker_cleanup_logs' );
		}

		// Add cron schedule if not exists.
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 604800, // 7 days in seconds.
				'display'  => __( 'Once Weekly', 'geo-ip-blocker' ),
			);
		}

		return $schedules;
	}

	/**
	 * Log blocked access.
	 *
	 * @param array $data Log data.
	 * @return int|false Log ID or false on failure.
	 */
	public function log_blocked_access( $data ) {
		// Check if logging is enabled.
		if ( ! geo_ip_blocker_get_setting( 'enable_logging', true ) ) {
			return false;
		}

		$defaults = array(
			'ip_address'   => '',
			'country_code' => '',
			'region'       => '',
			'city'         => '',
			'blocked_url'  => '',
			'user_agent'   => '',
			'block_reason' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		// Add to database.
		$result = $this->database->add_log( $data );

		// Check if we need to trim old logs.
		$this->maybe_trim_logs();

		return $result;
	}

	/**
	 * Maybe trim logs if exceeding max limit.
	 */
	private function maybe_trim_logs() {
		global $wpdb;

		$table_name = $this->database->get_logs_table();
		$count      = $this->database->get_logs_count();

		if ( $count > $this->max_logs ) {
			$delete_count = $count - $this->max_logs;

			// Delete oldest logs.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table_name} ORDER BY created_at ASC LIMIT %d",
					$delete_count
				)
			);
		}
	}

	/**
	 * Get logs with filters and pagination.
	 *
	 * @param array $filters Filters to apply.
	 * @param int   $page    Page number.
	 * @param int   $per_page Items per page.
	 * @return array
	 */
	public function get_logs( $filters = array(), $page = 1, $per_page = 20 ) {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		// Support new format with limit/offset.
		if ( isset( $filters['limit'] ) ) {
			$per_page = $filters['limit'];
		}
		if ( isset( $filters['offset'] ) ) {
			$offset = $filters['offset'];
		} else {
			$offset = ( $page - 1 ) * $per_page;
		}

		// Build WHERE clause.
		$where = '1=1';
		$values = array();

		// Filter by date range.
		if ( ! empty( $filters['date_from'] ) ) {
			$where .= ' AND created_at >= %s';
			$values[] = gmdate( 'Y-m-d 00:00:00', strtotime( $filters['date_from'] ) );
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where .= ' AND created_at <= %s';
			$values[] = gmdate( 'Y-m-d 23:59:59', strtotime( $filters['date_to'] ) );
		}

		// Filter by country.
		if ( ! empty( $filters['country_code'] ) ) {
			$where .= ' AND country_code = %s';
			$values[] = $filters['country_code'];
		}

		// Filter by IP.
		if ( ! empty( $filters['ip_address'] ) ) {
			$where .= ' AND ip_address LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $filters['ip_address'] ) . '%';
		}

		// Filter by URL.
		if ( ! empty( $filters['blocked_url'] ) ) {
			$where .= ' AND blocked_url LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $filters['blocked_url'] ) . '%';
		}

		// Filter by block reason.
		if ( ! empty( $filters['block_reason'] ) ) {
			$where .= ' AND block_reason LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $filters['block_reason'] ) . '%';
		}

		// Build query.
		if ( ! empty( $values ) ) {
			$where = $wpdb->prepare( $where, $values );
		}

		// Get orderby and order.
		$orderby = isset( $filters['orderby'] ) ? $filters['orderby'] : 'created_at';
		$order   = isset( $filters['order'] ) ? $filters['order'] : 'DESC';

		$query = "SELECT * FROM {$table_name} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$results = $wpdb->get_results(
			$wpdb->prepare( $query, $per_page, $offset )
		);

		return $results;
	}

	/**
	 * Get logs count with filters.
	 *
	 * @param array $filters Filters to apply.
	 * @return int
	 */
	public function get_logs_count( $filters = array() ) {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		// Build WHERE clause.
		$where = '1=1';
		$values = array();

		// Filter by date range.
		if ( ! empty( $filters['date_from'] ) ) {
			$where .= ' AND created_at >= %s';
			$values[] = gmdate( 'Y-m-d 00:00:00', strtotime( $filters['date_from'] ) );
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where .= ' AND created_at <= %s';
			$values[] = gmdate( 'Y-m-d 23:59:59', strtotime( $filters['date_to'] ) );
		}

		// Filter by country.
		if ( ! empty( $filters['country_code'] ) ) {
			$where .= ' AND country_code = %s';
			$values[] = $filters['country_code'];
		}

		// Filter by IP.
		if ( ! empty( $filters['ip_address'] ) ) {
			$where .= ' AND ip_address LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $filters['ip_address'] ) . '%';
		}

		// Filter by block reason.
		if ( ! empty( $filters['block_reason'] ) ) {
			$where .= ' AND block_reason LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $filters['block_reason'] ) . '%';
		}

		// Build query.
		if ( ! empty( $values ) ) {
			$where = $wpdb->prepare( $where, $values );
		}

		$count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where}";
		return (int) $wpdb->get_var( $count_query );
	}

	/**
	 * Export logs to CSV or JSON.
	 *
	 * @param string $format  Format ('csv' or 'json').
	 * @param array  $filters Optional filters.
	 * @return string|false File path or false on failure.
	 */
	public function export_logs( $format = 'csv', $filters = array() ) {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		// Build WHERE clause (same as get_logs).
		$where = '1=1';
		$values = array();

		if ( ! empty( $filters['date_from'] ) ) {
			$where .= ' AND created_at >= %s';
			$values[] = gmdate( 'Y-m-d 00:00:00', strtotime( $filters['date_from'] ) );
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where .= ' AND created_at <= %s';
			$values[] = gmdate( 'Y-m-d 23:59:59', strtotime( $filters['date_to'] ) );
		}

		if ( ! empty( $filters['country_code'] ) ) {
			$where .= ' AND country_code = %s';
			$values[] = $filters['country_code'];
		}

		if ( ! empty( $values ) ) {
			$where = $wpdb->prepare( $where, $values );
		}

		// Limit to 50,000 records for export.
		$query = "SELECT * FROM {$table_name} WHERE {$where} ORDER BY created_at DESC LIMIT 50000";
		$logs = $wpdb->get_results( $query );

		if ( empty( $logs ) ) {
			return false;
		}

		// Generate file.
		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/geo-ip-blocker-exports';

		if ( ! file_exists( $export_dir ) ) {
			wp_mkdir_p( $export_dir );
		}

		$filename = 'geo-blocker-logs-' . gmdate( 'Y-m-d-His' ) . '.' . $format;
		$filepath = $export_dir . '/' . $filename;

		if ( 'csv' === $format ) {
			return $this->export_to_csv( $logs, $filepath );
		} elseif ( 'json' === $format ) {
			return $this->export_to_json( $logs, $filepath );
		}

		return false;
	}

	/**
	 * Export logs to CSV.
	 *
	 * @param array  $logs     Logs data.
	 * @param string $filepath File path.
	 * @return string|false File path or false on failure.
	 */
	private function export_to_csv( $logs, $filepath ) {
		$file = fopen( $filepath, 'w' );

		if ( ! $file ) {
			return false;
		}

		// Write headers.
		$headers = array( 'ID', 'IP Address', 'Country', 'Region', 'City', 'Blocked URL', 'User Agent', 'Block Reason', 'Date/Time' );
		fputcsv( $file, $headers );

		// Write data.
		foreach ( $logs as $log ) {
			$row = array(
				$log->id,
				$log->ip_address,
				$log->country_code,
				$log->region,
				$log->city,
				$log->blocked_url,
				$log->user_agent,
				$log->block_reason,
				$log->created_at,
			);
			fputcsv( $file, $row );
		}

		fclose( $file );

		return $filepath;
	}

	/**
	 * Export logs to JSON.
	 *
	 * @param array  $logs     Logs data.
	 * @param string $filepath File path.
	 * @return string|false File path or false on failure.
	 */
	private function export_to_json( $logs, $filepath ) {
		$json_data = array(
			'export_date' => gmdate( 'Y-m-d H:i:s' ),
			'total_logs'  => count( $logs ),
			'logs'        => $logs,
		);

		$json = wp_json_encode( $json_data, JSON_PRETTY_PRINT );

		if ( false === $json ) {
			return false;
		}

		$result = file_put_contents( $filepath, $json );

		return $result !== false ? $filepath : false;
	}

	/**
	 * Delete logs older than specified days.
	 *
	 * @param int $days Number of days.
	 * @return int|false Number of deleted rows or false on failure.
	 */
	public function delete_logs( $days = null ) {
		if ( null === $days ) {
			$days = $this->retention_days;
		}

		return $this->database->delete_old_logs( $days );
	}

	/**
	 * Get statistics for a period.
	 *
	 * @param string $period Period ('today', 'week', 'month', 'year', 'all').
	 * @return array
	 */
	public function get_statistics( $period = 'all' ) {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		// Determine date filter.
		$date_filter = $this->get_date_filter( $period );

		// Total blocks.
		$total_query = "SELECT COUNT(*) FROM {$table_name}";
		if ( $date_filter ) {
			$total_query .= " WHERE {$date_filter}";
		}
		$total_blocks = (int) $wpdb->get_var( $total_query );

		// Top countries.
		$countries_query = "SELECT country_code, COUNT(*) as count FROM {$table_name}";
		if ( $date_filter ) {
			$countries_query .= " WHERE {$date_filter}";
		}
		$countries_query .= " GROUP BY country_code ORDER BY count DESC LIMIT 10";
		$top_countries = $wpdb->get_results( $countries_query );

		// Top IPs.
		$ips_query = "SELECT ip_address, COUNT(*) as count FROM {$table_name}";
		if ( $date_filter ) {
			$ips_query .= " WHERE {$date_filter}";
		}
		$ips_query .= " GROUP BY ip_address ORDER BY count DESC LIMIT 10";
		$top_ips = $wpdb->get_results( $ips_query );

		// Blocks by day (last 30 days).
		$days_query = "SELECT DATE(created_at) as date, COUNT(*) as count FROM {$table_name}";
		if ( $date_filter ) {
			$days_query .= " WHERE {$date_filter}";
		}
		$days_query .= " GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30";
		$blocks_by_day = $wpdb->get_results( $days_query );

		// Top block reasons.
		$reasons_query = "SELECT block_reason, COUNT(*) as count FROM {$table_name}";
		if ( $date_filter ) {
			$reasons_query .= " WHERE {$date_filter}";
		}
		$reasons_query .= " GROUP BY block_reason ORDER BY count DESC LIMIT 10";
		$top_reasons = $wpdb->get_results( $reasons_query );

		// Top URLs.
		$urls_query = "SELECT blocked_url, COUNT(*) as count FROM {$table_name}";
		if ( $date_filter ) {
			$urls_query .= " WHERE {$date_filter}";
		}
		$urls_query .= " WHERE blocked_url != '' GROUP BY blocked_url ORDER BY count DESC LIMIT 10";
		$top_urls = $wpdb->get_results( $urls_query );

		return array(
			'total_blocks'   => $total_blocks,
			'top_countries'  => $top_countries,
			'top_ips'        => $top_ips,
			'blocks_by_day'  => array_reverse( $blocks_by_day ), // Oldest first.
			'top_reasons'    => $top_reasons,
			'top_urls'       => $top_urls,
			'period'         => $period,
		);
	}

	/**
	 * Get date filter SQL for period.
	 *
	 * @param string $period Period.
	 * @return string|false
	 */
	private function get_date_filter( $period ) {
		global $wpdb;

		switch ( $period ) {
			case 'today':
				return $wpdb->prepare(
					'created_at >= %s',
					gmdate( 'Y-m-d 00:00:00' )
				);

			case 'week':
				return $wpdb->prepare(
					'created_at >= %s',
					gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) )
				);

			case 'month':
				return $wpdb->prepare(
					'created_at >= %s',
					gmdate( 'Y-m-d 00:00:00', strtotime( '-30 days' ) )
				);

			case 'year':
				return $wpdb->prepare(
					'created_at >= %s',
					gmdate( 'Y-m-d 00:00:00', strtotime( '-365 days' ) )
				);

			case 'all':
			default:
				return false;
		}
	}

	/**
	 * Clean old logs based on retention period.
	 */
	public function clean_old_logs() {
		$this->delete_logs( $this->retention_days );

		// Also trim if exceeding max count.
		$this->maybe_trim_logs();
	}

	/**
	 * Clear all logs.
	 *
	 * @return int|false Number of deleted rows or false on failure.
	 */
	public function clear_all_logs() {
		return $this->database->clear_logs();
	}

	/**
	 * Get recent logs (last 100).
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public function get_recent_logs( $limit = 100 ) {
		return $this->database->get_logs(
			array(
				'limit'   => $limit,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			)
		);
	}

	/**
	 * Get logs by IP.
	 *
	 * @param string $ip_address IP address.
	 * @param int    $limit      Limit.
	 * @return array
	 */
	public function get_logs_by_ip( $ip_address, $limit = 100 ) {
		return $this->database->get_logs(
			array(
				'ip_address' => $ip_address,
				'limit'      => $limit,
				'orderby'    => 'created_at',
				'order'      => 'DESC',
			)
		);
	}

	/**
	 * Get logs by country.
	 *
	 * @param string $country_code Country code.
	 * @param int    $limit        Limit.
	 * @return array
	 */
	public function get_logs_by_country( $country_code, $limit = 100 ) {
		return $this->database->get_logs(
			array(
				'country_code' => $country_code,
				'limit'         => $limit,
				'orderby'       => 'created_at',
				'order'         => 'DESC',
			)
		);
	}

	/**
	 * Get chart data for blocks over time.
	 *
	 * @param int    $days   Number of days to include.
	 * @param string $period Period ('today', 'week', 'month').
	 * @return array
	 */
	public function get_chart_data( $days = 30, $period = 'month' ) {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		$date_filter = $this->get_date_filter( $period );

		$query = "SELECT DATE(created_at) as date, COUNT(*) as count FROM {$table_name}";
		if ( $date_filter ) {
			$query .= " WHERE {$date_filter}";
		}
		$query .= " GROUP BY DATE(created_at) ORDER BY date ASC";

		$results = $wpdb->get_results( $query );

		$chart_data = array(
			'labels' => array(),
			'data'   => array(),
		);

		foreach ( $results as $row ) {
			$chart_data['labels'][] = $row->date;
			$chart_data['data'][]   = (int) $row->count;
		}

		return $chart_data;
	}

	/**
	 * Get summary statistics.
	 *
	 * @return array
	 */
	public function get_summary() {
		$stats = array(
			'total'      => $this->database->get_logs_count(),
			'today'      => 0,
			'this_week'  => 0,
			'this_month' => 0,
		);

		// Today.
		$today_stats = $this->get_statistics( 'today' );
		$stats['today'] = $today_stats['total_blocks'];

		// This week.
		$week_stats = $this->get_statistics( 'week' );
		$stats['this_week'] = $week_stats['total_blocks'];

		// This month.
		$month_stats = $this->get_statistics( 'month' );
		$stats['this_month'] = $month_stats['total_blocks'];

		return $stats;
	}

	/**
	 * Get blocked countries with counts.
	 *
	 * @return array
	 */
	public function get_blocked_countries() {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		$query = "SELECT country_code, COUNT(*) as count FROM {$table_name} WHERE country_code != '' GROUP BY country_code ORDER BY count DESC";

		return $wpdb->get_results( $query );
	}

	/**
	 * Get timeline data for charts.
	 *
	 * @param int $days Number of days.
	 * @return array
	 */
	public function get_timeline_data( $days = 30 ) {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		$query = $wpdb->prepare(
			"SELECT DATE(created_at) as date, COUNT(*) as count FROM {$table_name} WHERE created_at >= %s GROUP BY DATE(created_at) ORDER BY date ASC",
			gmdate( 'Y-m-d 00:00:00', strtotime( "-{$days} days" ) )
		);

		$results = $wpdb->get_results( $query );

		$data = array(
			'labels' => array(),
			'values' => array(),
		);

		foreach ( $results as $row ) {
			$data['labels'][] = $row->date;
			$data['values'][] = (int) $row->count;
		}

		return $data;
	}

	/**
	 * Get top countries.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public function get_top_countries( $limit = 10 ) {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		$query = $wpdb->prepare(
			"SELECT country_code, COUNT(*) as count FROM {$table_name} WHERE country_code != '' GROUP BY country_code ORDER BY count DESC LIMIT %d",
			$limit
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * Get top IPs.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public function get_top_ips( $limit = 10 ) {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		$query = $wpdb->prepare(
			"SELECT ip_address, COUNT(*) as count FROM {$table_name} WHERE ip_address != '' GROUP BY ip_address ORDER BY count DESC LIMIT %d",
			$limit
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * Get block reasons statistics.
	 *
	 * @return array
	 */
	public function get_block_reasons_stats() {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		$query = "SELECT block_reason, COUNT(*) as count FROM {$table_name} WHERE block_reason != '' GROUP BY block_reason ORDER BY count DESC";

		return $wpdb->get_results( $query );
	}

	/**
	 * Delete a single log entry.
	 *
	 * @param int $log_id Log ID.
	 * @return bool
	 */
	public function delete_log( $log_id ) {
		global $wpdb;

		$table_name = $this->database->get_logs_table();

		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $log_id ),
			array( '%d' )
		);

		return false !== $result;
	}
}
