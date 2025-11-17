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
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="notice notice-info">
		<p><?php esc_html_e( 'Welcome to Geo & IP Blocker! Configure your blocking rules to protect your WooCommerce store.', 'geo-ip-blocker' ); ?></p>
	</div>

	<div class="geo-ip-blocker-dashboard">
		<h2><?php esc_html_e( 'Quick Stats', 'geo-ip-blocker' ); ?></h2>
		<p><?php esc_html_e( 'Dashboard content will be implemented in the next phase.', 'geo-ip-blocker' ); ?></p>
	</div>
</div>
