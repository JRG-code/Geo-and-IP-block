<?php
/**
 * Database handler class.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_IP_Blocker_Database
 *
 * Handles all database operations for the plugin.
 */
class Geo_IP_Blocker_Database {

	/**
	 * Database version.
	 *
	 * @var string
	 */
	private $db_version = '1.0.0';

	/**
	 * Rules table name.
	 *
	 * @var string
	 */
	private $rules_table;

	/**
	 * Logs table name.
	 *
	 * @var string
	 */
	private $logs_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->rules_table = $wpdb->prefix . 'geo_ip_rules';
		$this->logs_table  = $wpdb->prefix . 'geo_ip_logs';
	}

	/**
	 * Create database tables.
	 */
	public function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Create rules table.
		$rules_table_sql = "CREATE TABLE IF NOT EXISTS {$this->rules_table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			rule_type varchar(20) NOT NULL,
			value varchar(255) NOT NULL,
			action varchar(20) NOT NULL DEFAULT 'block',
			priority int(11) NOT NULL DEFAULT 10,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY rule_type (rule_type),
			KEY action (action),
			KEY priority (priority)
		) {$charset_collate};";

		dbDelta( $rules_table_sql );

		// Create logs table.
		$logs_table_sql = "CREATE TABLE IF NOT EXISTS {$this->logs_table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			ip_address varchar(45) NOT NULL,
			country_code varchar(2) DEFAULT NULL,
			region varchar(100) DEFAULT NULL,
			city varchar(100) DEFAULT NULL,
			blocked_url text,
			user_agent text,
			block_reason varchar(100) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY ip_address (ip_address),
			KEY country_code (country_code),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $logs_table_sql );

		// Update database version.
		update_option( 'geo_ip_blocker_db_version', $this->db_version );
	}

	/**
	 * Get rules table name.
	 *
	 * @return string
	 */
	public function get_rules_table() {
		return $this->rules_table;
	}

	/**
	 * Get logs table name.
	 *
	 * @return string
	 */
	public function get_logs_table() {
		return $this->logs_table;
	}

	/**
	 * Add a new rule.
	 *
	 * @param array $data Rule data.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function add_rule( $data ) {
		global $wpdb;

		$defaults = array(
			'rule_type' => '',
			'value'     => '',
			'action'    => 'block',
			'priority'  => 10,
		);

		$data = wp_parse_args( $data, $defaults );

		// Validate rule type.
		if ( ! in_array( $data['rule_type'], array( 'country', 'ip', 'region' ), true ) ) {
			return false;
		}

		// Validate action.
		if ( ! in_array( $data['action'], array( 'block', 'allow' ), true ) ) {
			return false;
		}

		return $wpdb->insert(
			$this->rules_table,
			array(
				'rule_type'  => sanitize_text_field( $data['rule_type'] ),
				'value'      => sanitize_text_field( $data['value'] ),
				'action'     => sanitize_text_field( $data['action'] ),
				'priority'   => absint( $data['priority'] ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * Get all rules.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_rules( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'rule_type' => '',
			'action'    => '',
			'orderby'   => 'priority',
			'order'     => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = '1=1';

		if ( ! empty( $args['rule_type'] ) ) {
			$where .= $wpdb->prepare( ' AND rule_type = %s', $args['rule_type'] );
		}

		if ( ! empty( $args['action'] ) ) {
			$where .= $wpdb->prepare( ' AND action = %s', $args['action'] );
		}

		$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

		$query = "SELECT * FROM {$this->rules_table} WHERE {$where} ORDER BY {$orderby}";

		return $wpdb->get_results( $query );
	}

	/**
	 * Delete a rule.
	 *
	 * @param int $rule_id Rule ID.
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public function delete_rule( $rule_id ) {
		global $wpdb;

		return $wpdb->delete(
			$this->rules_table,
			array( 'id' => absint( $rule_id ) ),
			array( '%d' )
		);
	}

	/**
	 * Add a log entry.
	 *
	 * @param array $data Log data.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function add_log( $data ) {
		global $wpdb;

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

		return $wpdb->insert(
			$this->logs_table,
			array(
				'ip_address'   => sanitize_text_field( $data['ip_address'] ),
				'country_code' => sanitize_text_field( $data['country_code'] ),
				'region'       => sanitize_text_field( $data['region'] ),
				'city'         => sanitize_text_field( $data['city'] ),
				'blocked_url'  => esc_url_raw( $data['blocked_url'] ),
				'user_agent'   => sanitize_text_field( $data['user_agent'] ),
				'block_reason' => sanitize_text_field( $data['block_reason'] ),
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get logs.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'        => 100,
			'offset'       => 0,
			'orderby'      => 'created_at',
			'order'        => 'DESC',
			'country_code' => '',
			'ip_address'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = '1=1';

		if ( ! empty( $args['country_code'] ) ) {
			$where .= $wpdb->prepare( ' AND country_code = %s', $args['country_code'] );
		}

		if ( ! empty( $args['ip_address'] ) ) {
			$where .= $wpdb->prepare( ' AND ip_address = %s', $args['ip_address'] );
		}

		$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		$limit   = absint( $args['limit'] );
		$offset  = absint( $args['offset'] );

		$query = "SELECT * FROM {$this->logs_table} WHERE {$where} ORDER BY {$orderby} LIMIT {$offset}, {$limit}";

		return $wpdb->get_results( $query );
	}

	/**
	 * Get logs count.
	 *
	 * @param array $args Query arguments.
	 * @return int
	 */
	public function get_logs_count( $args = array() ) {
		global $wpdb;

		$where = '1=1';

		if ( ! empty( $args['country_code'] ) ) {
			$where .= $wpdb->prepare( ' AND country_code = %s', $args['country_code'] );
		}

		if ( ! empty( $args['ip_address'] ) ) {
			$where .= $wpdb->prepare( ' AND ip_address = %s', $args['ip_address'] );
		}

		$query = "SELECT COUNT(*) FROM {$this->logs_table} WHERE {$where}";

		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Delete old logs.
	 *
	 * @param int $days Number of days to keep.
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public function delete_old_logs( $days = 30 ) {
		global $wpdb;

		$date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->logs_table} WHERE created_at < %s",
				$date
			)
		);
	}

	/**
	 * Clear all logs.
	 *
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public function clear_logs() {
		global $wpdb;

		return $wpdb->query( "TRUNCATE TABLE {$this->logs_table}" );
	}

	/**
	 * Drop tables on uninstall.
	 */
	public function drop_tables() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$this->rules_table}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$this->logs_table}" );

		delete_option( 'geo_ip_blocker_db_version' );
	}
}
