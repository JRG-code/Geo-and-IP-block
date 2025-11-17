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
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="geo-ip-blocker-logs">
		<h2><?php esc_html_e( 'Access Logs', 'geo-ip-blocker' ); ?></h2>
		<p><?php esc_html_e( 'Logs display will be implemented in the next phase.', 'geo-ip-blocker' ); ?></p>
	</div>
</div>
