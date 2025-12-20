<?php
/**
 * Blocked Message Template
 *
 * This template can be overridden by copying it to yourtheme/geo-blocker/blocked-message.php
 *
 * @package GeoIPBlocker
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get settings.
$settings = get_option( 'geo_ip_blocker_settings', array() );

// Template variables (passed from the blocker).
$message      = isset( $message ) ? $message : $settings['block_message'];
$reason       = isset( $reason ) ? $reason : __( 'Sua localização não tem permissão para acessar este site.', 'geo-ip-blocker' );
$ip           = isset( $ip ) ? $ip : '';
$country      = isset( $country ) ? $country : '';
$country_code = isset( $country_code ) ? $country_code : '';
$show_details = isset( $show_details ) ? $show_details : ! empty( $settings['show_block_details'] );
$contact_url  = isset( $contact_url ) ? $contact_url : ( ! empty( $settings['contact_url'] ) ? $settings['contact_url'] : '' );
$site_name    = get_bloginfo( 'name' );
$site_url     = home_url();
$template_style = isset( $settings['block_template_style'] ) ? $settings['block_template_style'] : 'default';

// Apply filters to allow customization.
$message      = apply_filters( 'geo_blocker_message', $message, $ip, $country_code );
$reason       = apply_filters( 'geo_blocker_reason', $reason, $ip, $country_code );
$show_details = apply_filters( 'geo_blocker_show_details', $show_details );

// Process shortcodes in message.
$message = Geo_IP_Blocker_Blocked_Page::process_shortcodes( $message, array(
	'ip'           => $ip,
	'country'      => $country,
	'country_code' => $country_code,
	'reason'       => $reason,
) );

// Fire before message action.
do_action( 'geo_blocker_before_message', compact( 'ip', 'country', 'country_code', 'reason' ) );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( sprintf( __( 'Acesso Restrito - %s', 'geo-ip-blocker' ), $site_name ) ); ?></title>

	<?php if ( ! empty( $settings['use_theme_styles'] ) ) : ?>
		<?php wp_head(); ?>
	<?php else : ?>
		<link rel="stylesheet" href="<?php echo esc_url( GEO_IP_BLOCKER_PLUGIN_URL . 'assets/css/frontend.css?ver=' . GEO_IP_BLOCKER_VERSION ); ?>">
	<?php endif; ?>

	<?php do_action( 'geo_blocker_head' ); ?>
</head>
<body class="geo-blocker-blocked geo-blocker-template-<?php echo esc_attr( $template_style ); ?>">

	<?php do_action( 'geo_blocker_before_container' ); ?>

	<div class="geo-blocker-container">
		<div class="geo-blocker-content">

			<?php if ( 'default' === $template_style ) : ?>
				<!-- Default Template Style -->
				<div class="geo-blocker-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="12" cy="12" r="10"></circle>
						<line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
					</svg>
				</div>

				<h1 class="geo-blocker-title"><?php esc_html_e( 'Acesso Restrito', 'geo-ip-blocker' ); ?></h1>

				<div class="geo-blocker-message">
					<?php echo wp_kses_post( $message ); ?>
				</div>

				<?php if ( $reason ) : ?>
				<div class="geo-blocker-info geo-blocker-reason">
					<strong><?php esc_html_e( 'Motivo:', 'geo-ip-blocker' ); ?></strong>
					<span><?php echo esc_html( $reason ); ?></span>
				</div>
				<?php endif; ?>

				<?php if ( $show_details && ( $ip || $country ) ) : ?>
				<div class="geo-blocker-info geo-blocker-details">
					<?php if ( $ip ) : ?>
						<div class="geo-blocker-detail-item">
							<strong><?php esc_html_e( 'Seu IP:', 'geo-ip-blocker' ); ?></strong>
							<code><?php echo esc_html( $ip ); ?></code>
						</div>
					<?php endif; ?>

					<?php if ( $country ) : ?>
						<div class="geo-blocker-detail-item">
							<strong><?php esc_html_e( 'País detectado:', 'geo-ip-blocker' ); ?></strong>
							<span><?php echo esc_html( $country ); ?> <?php echo $country_code ? '(' . esc_html( $country_code ) . ')' : ''; ?></span>
						</div>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<?php if ( $contact_url ) : ?>
				<div class="geo-blocker-actions">
					<a href="<?php echo esc_url( $contact_url ); ?>" class="geo-blocker-button">
						<?php esc_html_e( 'Entre em contato', 'geo-ip-blocker' ); ?>
					</a>
				</div>
				<?php endif; ?>

			<?php elseif ( 'minimal' === $template_style ) : ?>
				<!-- Minimal Template Style -->
				<div class="geo-blocker-minimal">
					<h1><?php esc_html_e( 'Acesso Negado', 'geo-ip-blocker' ); ?></h1>
					<p><?php echo wp_kses_post( $message ); ?></p>
					<?php if ( $contact_url ) : ?>
						<a href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contato', 'geo-ip-blocker' ); ?></a>
					<?php endif; ?>
				</div>

			<?php elseif ( 'dark' === $template_style ) : ?>
				<!-- Dark Template Style -->
				<div class="geo-blocker-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
						<line x1="12" y1="9" x2="12" y2="13"></line>
						<line x1="12" y1="17" x2="12.01" y2="17"></line>
					</svg>
				</div>

				<h1 class="geo-blocker-title"><?php esc_html_e( 'Acesso Restrito', 'geo-ip-blocker' ); ?></h1>

				<div class="geo-blocker-message">
					<?php echo wp_kses_post( $message ); ?>
				</div>

				<?php if ( $show_details ) : ?>
				<div class="geo-blocker-details-grid">
					<?php if ( $ip ) : ?>
						<div class="detail-card">
							<div class="detail-label"><?php esc_html_e( 'IP Address', 'geo-ip-blocker' ); ?></div>
							<div class="detail-value"><code><?php echo esc_html( $ip ); ?></code></div>
						</div>
					<?php endif; ?>

					<?php if ( $country ) : ?>
						<div class="detail-card">
							<div class="detail-label"><?php esc_html_e( 'Location', 'geo-ip-blocker' ); ?></div>
							<div class="detail-value"><?php echo esc_html( $country ); ?></div>
						</div>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<?php if ( $contact_url ) : ?>
				<div class="geo-blocker-actions">
					<a href="<?php echo esc_url( $contact_url ); ?>" class="geo-blocker-button geo-blocker-button-dark">
						<?php esc_html_e( 'Get Support', 'geo-ip-blocker' ); ?>
					</a>
				</div>
				<?php endif; ?>

			<?php else : ?>
				<!-- Custom Template - Basic structure -->
				<h1><?php esc_html_e( 'Acesso Restrito', 'geo-ip-blocker' ); ?></h1>
				<div><?php echo wp_kses_post( $message ); ?></div>
			<?php endif; ?>

			<?php do_action( 'geo_blocker_after_content' ); ?>

		</div>

		<?php if ( ! empty( $settings['show_powered_by'] ) ) : ?>
		<div class="geo-blocker-footer">
			<p>
				<?php
				printf(
					/* translators: %s: plugin name */
					esc_html__( 'Protegido por %s', 'geo-ip-blocker' ),
					'<a href="' . esc_url( $site_url ) . '" target="_blank">' . esc_html__( 'Geo & IP Blocker', 'geo-ip-blocker' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php endif; ?>
	</div>

	<?php do_action( 'geo_blocker_after_container' ); ?>

	<?php if ( ! empty( $settings['use_theme_styles'] ) ) : ?>
		<?php wp_footer(); ?>
	<?php endif; ?>

	<?php do_action( 'geo_blocker_footer' ); ?>

</body>
</html>
<?php
// Fire after message action.
do_action( 'geo_blocker_after_message', compact( 'ip', 'country', 'country_code', 'reason' ) );
?>
