<?php
/**
 * Logs page class.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Geo_IP_Blocker_Logs_Page
 *
 * Handles logs display and management.
 */
class Geo_IP_Blocker_Logs_Page extends WP_List_Table {

	/**
	 * Logger instance.
	 *
	 * @var Geo_IP_Blocker_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Log Entry', 'geo-ip-blocker' ),
				'plural'   => __( 'Log Entries', 'geo-ip-blocker' ),
				'ajax'     => true,
			)
		);

		$this->logger = geo_ip_blocker_get_logger();
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'datetime'    => __( 'Date & Time', 'geo-ip-blocker' ),
			'ip_address'  => __( 'IP Address', 'geo-ip-blocker' ),
			'country'     => __( 'Country', 'geo-ip-blocker' ),
			'location'    => __( 'Location', 'geo-ip-blocker' ),
			'blocked_url' => __( 'Blocked URL', 'geo-ip-blocker' ),
			'reason'      => __( 'Reason', 'geo-ip-blocker' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'datetime'   => array( 'created_at', true ),
			'ip_address' => array( 'ip_address', false ),
			'country'    => array( 'country_code', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'geo-ip-blocker' ),
		);
	}

	/**
	 * Column default.
	 *
	 * @param object $item        Item data.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'datetime':
				return sprintf(
					'%s<br><small>%s</small>',
					esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->created_at ) ) ),
					esc_html( date_i18n( get_option( 'time_format' ), strtotime( $item->created_at ) ) )
				);

			case 'ip_address':
				return sprintf(
					'<code>%s</code>',
					esc_html( $item->ip_address )
				);

			case 'country':
				$country_name = $this->get_country_name( $item->country_code );
				return sprintf(
					'<span class="country-flag">%s</span> %s',
					esc_html( $item->country_code ),
					esc_html( $country_name )
				);

			case 'location':
				$location_parts = array_filter(
					array(
						$item->city,
						$item->region,
					)
				);
				return ! empty( $location_parts ) ? esc_html( implode( ', ', $location_parts ) ) : '—';

			case 'blocked_url':
				$url = $item->blocked_url;
				if ( strlen( $url ) > 50 ) {
					return sprintf(
						'<span title="%s">%s...</span>',
						esc_attr( $url ),
						esc_html( substr( $url, 0, 50 ) )
					);
				}
				return esc_html( $url );

			case 'reason':
				return $this->format_reason( $item->block_reason );

			default:
				return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '—';
		}
	}

	/**
	 * Column checkbox.
	 *
	 * @param object $item Item data.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="log_ids[]" value="%s" />',
			esc_attr( $item->id )
		);
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items() {
		$per_page = 20;
		$current_page = $this->get_pagenum();

		// Get filters.
		$filters = $this->get_filters();

		// Get total items.
		$total_items = $this->logger->get_logs_count( $filters );

		// Get logs.
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';

		$logs = $this->logger->get_logs(
			array_merge(
				$filters,
				array(
					'orderby' => $orderby,
					'order'   => $order,
					'limit'   => $per_page,
					'offset'  => ( $current_page - 1 ) * $per_page,
				)
			)
		);

		$this->items = $logs;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Get filters from request.
	 *
	 * @return array
	 */
	private function get_filters() {
		$filters = array();

		if ( ! empty( $_GET['filter_date_from'] ) ) {
			$filters['date_from'] = sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) );
		}

		if ( ! empty( $_GET['filter_date_to'] ) ) {
			$filters['date_to'] = sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) );
		}

		if ( ! empty( $_GET['filter_country'] ) ) {
			$filters['country_code'] = sanitize_text_field( wp_unslash( $_GET['filter_country'] ) );
		}

		if ( ! empty( $_GET['filter_ip'] ) ) {
			$filters['ip_address'] = sanitize_text_field( wp_unslash( $_GET['filter_ip'] ) );
		}

		if ( ! empty( $_GET['filter_reason'] ) ) {
			$filters['block_reason'] = sanitize_text_field( wp_unslash( $_GET['filter_reason'] ) );
		}

		return $filters;
	}

	/**
	 * Display filters.
	 */
	public function display_filters() {
		$filter_date_from = isset( $_GET['filter_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) : '';
		$filter_date_to   = isset( $_GET['filter_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) : '';
		$filter_country   = isset( $_GET['filter_country'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_country'] ) ) : '';
		$filter_ip        = isset( $_GET['filter_ip'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_ip'] ) ) : '';
		$filter_reason    = isset( $_GET['filter_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_reason'] ) ) : '';

		// Get unique countries from logs.
		$countries = $this->logger->get_blocked_countries();

		// Get block reasons.
		$reasons = array(
			'country_blocked'  => __( 'Country Blocked', 'geo-ip-blocker' ),
			'ip_blacklisted'   => __( 'IP Blacklisted', 'geo-ip-blocker' ),
			'region_blocked'   => __( 'Region Blocked', 'geo-ip-blocker' ),
			'woocommerce_page' => __( 'WooCommerce Page', 'geo-ip-blocker' ),
			'product_blocked'  => __( 'Product Blocked', 'geo-ip-blocker' ),
		);
		?>
		<div class="geo-ip-blocker-filters">
			<div class="filter-row">
				<div class="filter-group">
					<label for="filter-date-from"><?php esc_html_e( 'Date From:', 'geo-ip-blocker' ); ?></label>
					<input type="date" id="filter-date-from" name="filter_date_from" value="<?php echo esc_attr( $filter_date_from ); ?>" class="regular-text">
				</div>

				<div class="filter-group">
					<label for="filter-date-to"><?php esc_html_e( 'Date To:', 'geo-ip-blocker' ); ?></label>
					<input type="date" id="filter-date-to" name="filter_date_to" value="<?php echo esc_attr( $filter_date_to ); ?>" class="regular-text">
				</div>

				<div class="filter-group">
					<label for="filter-country"><?php esc_html_e( 'Country:', 'geo-ip-blocker' ); ?></label>
					<select id="filter-country" name="filter_country" class="regular-text">
						<option value=""><?php esc_html_e( 'All Countries', 'geo-ip-blocker' ); ?></option>
						<?php foreach ( $countries as $country ) : ?>
							<?php if ( ! empty( $country->country_code ) ) : ?>
								<option value="<?php echo esc_attr( $country->country_code ); ?>" <?php selected( $filter_country, $country->country_code ); ?>>
									<?php
									printf(
										'%s (%d)',
										esc_html( $this->get_country_name( $country->country_code ) ),
										intval( $country->count )
									);
									?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="filter-group">
					<label for="filter-ip"><?php esc_html_e( 'IP Address:', 'geo-ip-blocker' ); ?></label>
					<input type="text" id="filter-ip" name="filter_ip" value="<?php echo esc_attr( $filter_ip ); ?>" placeholder="192.168.1.1" class="regular-text">
				</div>

				<div class="filter-group">
					<label for="filter-reason"><?php esc_html_e( 'Reason:', 'geo-ip-blocker' ); ?></label>
					<select id="filter-reason" name="filter_reason" class="regular-text">
						<option value=""><?php esc_html_e( 'All Reasons', 'geo-ip-blocker' ); ?></option>
						<?php foreach ( $reasons as $reason_key => $reason_label ) : ?>
							<option value="<?php echo esc_attr( $reason_key ); ?>" <?php selected( $filter_reason, $reason_key ); ?>>
								<?php echo esc_html( $reason_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="filter-actions">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Apply Filters', 'geo-ip-blocker' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-ip-blocker-logs' ) ); ?>" class="button">
					<?php esc_html_e( 'Clear Filters', 'geo-ip-blocker' ); ?>
				</a>
				<button type="button" id="export-csv" class="button button-secondary">
					<?php esc_html_e( 'Export CSV', 'geo-ip-blocker' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Display statistics.
	 */
	public function display_statistics() {
		$filters = $this->get_filters();

		// Get statistics for filtered period.
		$stats = $this->logger->get_statistics( $filters );

		// Get today's stats.
		$today_stats = $this->logger->get_statistics(
			array(
				'date_from' => gmdate( 'Y-m-d' ),
				'date_to'   => gmdate( 'Y-m-d' ),
			)
		);

		// Get week stats.
		$week_stats = $this->logger->get_statistics(
			array(
				'date_from' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
				'date_to'   => gmdate( 'Y-m-d' ),
			)
		);

		// Get month stats.
		$month_stats = $this->logger->get_statistics(
			array(
				'date_from' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
				'date_to'   => gmdate( 'Y-m-d' ),
			)
		);
		?>
		<div class="geo-ip-blocker-stats">
			<div class="stat-card">
				<div class="stat-label"><?php esc_html_e( 'Total (Filtered)', 'geo-ip-blocker' ); ?></div>
				<div class="stat-value"><?php echo esc_html( number_format_i18n( $stats['total_blocks'] ) ); ?></div>
			</div>

			<div class="stat-card">
				<div class="stat-label"><?php esc_html_e( 'Today', 'geo-ip-blocker' ); ?></div>
				<div class="stat-value"><?php echo esc_html( number_format_i18n( $today_stats['total_blocks'] ) ); ?></div>
			</div>

			<div class="stat-card">
				<div class="stat-label"><?php esc_html_e( 'Last 7 Days', 'geo-ip-blocker' ); ?></div>
				<div class="stat-value"><?php echo esc_html( number_format_i18n( $week_stats['total_blocks'] ) ); ?></div>
			</div>

			<div class="stat-card">
				<div class="stat-label"><?php esc_html_e( 'Last 30 Days', 'geo-ip-blocker' ); ?></div>
				<div class="stat-value"><?php echo esc_html( number_format_i18n( $month_stats['total_blocks'] ) ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display charts.
	 */
	public function display_charts() {
		?>
		<div class="geo-ip-blocker-charts">
			<div class="chart-container">
				<h3><?php esc_html_e( 'Blocks Over Time (Last 30 Days)', 'geo-ip-blocker' ); ?></h3>
				<canvas id="blocks-timeline-chart" width="400" height="100"></canvas>
			</div>

			<div class="chart-row">
				<div class="chart-container chart-half">
					<h3><?php esc_html_e( 'Top 10 Blocked Countries', 'geo-ip-blocker' ); ?></h3>
					<canvas id="top-countries-chart" width="400" height="200"></canvas>
				</div>

				<div class="chart-container chart-half">
					<h3><?php esc_html_e( 'Block Reasons', 'geo-ip-blocker' ); ?></h3>
					<canvas id="block-reasons-chart" width="400" height="200"></canvas>
				</div>
			</div>

			<div class="chart-container">
				<h3><?php esc_html_e( 'Top 10 Blocked IPs', 'geo-ip-blocker' ); ?></h3>
				<div id="top-ips-list" class="top-ips-list"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get country name from code.
	 *
	 * @param string $code Country code.
	 * @return string
	 */
	private function get_country_name( $code ) {
		$countries = array(
			'US' => __( 'United States', 'geo-ip-blocker' ),
			'GB' => __( 'United Kingdom', 'geo-ip-blocker' ),
			'CA' => __( 'Canada', 'geo-ip-blocker' ),
			'AU' => __( 'Australia', 'geo-ip-blocker' ),
			'DE' => __( 'Germany', 'geo-ip-blocker' ),
			'FR' => __( 'France', 'geo-ip-blocker' ),
			'BR' => __( 'Brazil', 'geo-ip-blocker' ),
			'CN' => __( 'China', 'geo-ip-blocker' ),
			'IN' => __( 'India', 'geo-ip-blocker' ),
			'RU' => __( 'Russian Federation', 'geo-ip-blocker' ),
			'JP' => __( 'Japan', 'geo-ip-blocker' ),
			'ES' => __( 'Spain', 'geo-ip-blocker' ),
			'IT' => __( 'Italy', 'geo-ip-blocker' ),
			'MX' => __( 'Mexico', 'geo-ip-blocker' ),
			'NL' => __( 'Netherlands', 'geo-ip-blocker' ),
			// Add more as needed...
		);

		return isset( $countries[ $code ] ) ? $countries[ $code ] : $code;
	}

	/**
	 * Format block reason.
	 *
	 * @param string $reason Reason code.
	 * @return string
	 */
	private function format_reason( $reason ) {
		$reasons = array(
			'country_blocked'  => '<span class="reason-badge reason-country">' . __( 'Country Blocked', 'geo-ip-blocker' ) . '</span>',
			'ip_blacklisted'   => '<span class="reason-badge reason-ip">' . __( 'IP Blacklisted', 'geo-ip-blocker' ) . '</span>',
			'region_blocked'   => '<span class="reason-badge reason-region">' . __( 'Region Blocked', 'geo-ip-blocker' ) . '</span>',
			'woocommerce_page' => '<span class="reason-badge reason-woocommerce">' . __( 'WooCommerce Page', 'geo-ip-blocker' ) . '</span>',
			'product_blocked'  => '<span class="reason-badge reason-product">' . __( 'Product Blocked', 'geo-ip-blocker' ) . '</span>',
		);

		return isset( $reasons[ $reason ] ) ? $reasons[ $reason ] : '<span class="reason-badge">' . esc_html( $reason ) . '</span>';
	}

	/**
	 * Process bulk actions.
	 */
	public function process_bulk_action() {
		if ( 'delete' === $this->current_action() ) {
			// Verify nonce.
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( esc_html__( 'Security check failed', 'geo-ip-blocker' ) );
			}

			// Check permissions.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'geo-ip-blocker' ) );
			}

			// Get log IDs.
			$log_ids = isset( $_POST['log_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['log_ids'] ) ) : array();

			if ( ! empty( $log_ids ) ) {
				foreach ( $log_ids as $log_id ) {
					$this->logger->delete_log( $log_id );
				}

				add_settings_error(
					'geo_ip_blocker_logs',
					'logs_deleted',
					sprintf(
						/* translators: %d: number of deleted logs */
						_n( '%d log entry deleted.', '%d log entries deleted.', count( $log_ids ), 'geo-ip-blocker' ),
						count( $log_ids )
					),
					'success'
				);
			}
		}
	}

	/**
	 * Extra tablenav.
	 *
	 * @param string $which Top or bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			?>
			<div class="alignleft actions">
				<button type="button" id="clear-all-logs" class="button button-secondary">
					<?php esc_html_e( 'Clear All Logs', 'geo-ip-blocker' ); ?>
				</button>
			</div>
			<?php
		}
	}
}
