<?php
/**
 * Booking Activities landing page
 * @version 1.16.33
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div class='wrap'>
<h1 class='wp-heading-inline'>Booking Activities</h1>
<hr class='wp-header-end' />

<div id='bookacti-landing-container'>
	<div id='bookacti-add-ons'>	
		<div id='bookacti-add-ons-intro'>
			<h3><?php esc_html_e( 'Make the most of Booking Activities', 'booking-activities' ); ?></h3>
			<p><?php esc_html_e( 'You can extend the functionality of Booking Activities with the following official add-ons. Choose the ones you like and try them out, you get a 30-day money back guarantee.', 'booking-activities' ); ?></p>
		</div>
		
		<div id='bookacti-add-ons-container'>
		<?php
			$add_ons = array(
				'resource-availability' => array( 
					'prefix'      => 'bara',
					'title'       => 'Resource Availability',
					'subtitle'    => '',
					'link'        => 'https://booking-activities.fr/en/downloads/resource-availability/?utm_source=plugin&utm_medium=plugin&utm_campaign=resource-availability&utm_content=landing',
					'screenshot'  => true,
					'light_color' => '#f29191',
					'dark_color'  => '#402626',
					'excerpt'     => esc_html__( 'Manage your resources\' availability and assign them to your simultaneous events to avoid overbookings and resource shortages.', 'booking-activities' ),
				),
				'prices-and-credits' => array( 
					'prefix'      => 'bapap',
					'title'       => 'Prices and Credits',
					'subtitle'    => '',
					'link'        => 'https://booking-activities.fr/en/downloads/prices-and-credits/?utm_source=plugin&utm_medium=plugin&utm_campaign=prices-and-credits&utm_content=landing',
					'screenshot'  => true,
					'light_color' => '#91d2f2',
					'dark_color'  => '#263740',
					'excerpt'     => esc_html__( 'Set per event prices, volume discounts and price categories (children, adults...). Sell booking passes and redeem them on your forms.', 'booking-activities' ),
				),
				'advanced-forms' => array( 
					'prefix'      => 'baaf',
					'title'       => 'Advanced Forms',
					'subtitle'    => '',
					'link'        => 'https://booking-activities.fr/en/downloads/advanced-forms/?utm_source=plugin&utm_medium=plugin&utm_campaign=advanced-forms&utm_content=landing',
					'screenshot'  => true,
					'light_color' => '#f291c2',
					'dark_color'  => '#402633',
					'excerpt'     => esc_html__( 'Add any kind of fields to your booking forms. Offer paid options. Collect data from each participant. View, edit and filter the values in your booking list.', 'booking-activities' ),
				),
				'display-pack' => array( 
					'prefix'      => 'badp',
					'title'       => 'Display Pack',
					'subtitle'    => '',
					'link'        => 'https://booking-activities.fr/en/downloads/display-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=display-pack&utm_content=landing',
					'screenshot'  => true,
					'light_color' => '#c291f2',
					'dark_color'  => '#332640',
					'excerpt'     => esc_html__( 'Customize Booking Activities appearance with the alternate views and customization options of this pack.', 'booking-activities' ),
				),
				'notification-pack' => array( 
					'prefix'      => 'banp',
					'title'       => 'Notification Pack',
					'subtitle'    => '',
					'link'        => 'https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=landing',
					'screenshot'  => true,
					'light_color' => '#91f2d2',
					'dark_color'  => '#264037',
					'excerpt'     => esc_html__( 'Set up automatic notifications to be sent before or after the bookings. All notifications can be configured per activity, and sent via email, SMS and Push.', 'booking-activities' ),
				),
				'order-for-customers' => array( 
					'prefix'      => 'baofc',
					'title'       => 'Order for Customers',
					'subtitle'    => '',
					'link'        => 'https://booking-activities.fr/en/downloads/order-for-customers/?utm_source=plugin&utm_medium=plugin&utm_campaign=order-for-customers&utm_content=landing',
					'screenshot'  => true,
					'light_color' => '#f2ed91',
					'dark_color'  => '#403f26',
					'excerpt'     => esc_html__( 'Order and book for your customers and allow them to pay later on your website. Perfect for your operators and your salespersons.', 'booking-activities' ),
				)
			);


			foreach( $add_ons as $add_on_slug => $add_on ) {
				$license_status = get_option( $add_on[ 'prefix' ] . '_license_status' );
				if( empty( $license_status ) || $license_status !== 'valid' ) {
					$img_url = '';
					if( $add_on[ 'screenshot' ] === true ) {
						$img_url = plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add-ons/' . $add_on_slug . '.png';
					} else if( is_string( $add_on[ 'screenshot' ] ) ) {
						$img_url = plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add-ons/' . $add_on[ 'screenshot' ];
					}
				?>
					<div class='bookacti-add-on-container'>
						<div class='bookacti-add-on-inner'>
							<?php if( $img_url !== '' ) { 
								$color1 = $add_on[ 'light_color' ];
								$color2 = $add_on[ 'dark_color' ];

								if( $color1 && $color2 ) {
								?>
									<style>
										#bookacti-add-on-image-<?php echo $add_on_slug; ?>:before {
											background: <?php echo $color1; ?>;
											background: -moz-radial-gradient(center, ellipse cover, <?php echo $color1; ?> 35%, <?php echo $color2; ?> 135%);
											background: -webkit-radial-gradient(center, ellipse cover, <?php echo $color1; ?> 35%, <?php echo $color2; ?> 135%);
											background: radial-gradient(ellipse at center, <?php echo $color1; ?> 35%, <?php echo $color2; ?> 135%);
											filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='<?php echo $color1; ?>', endColorstr='<?php echo $color2; ?>',GradientType=1 );
										}
										#bookacti-add-on-image-<?php echo $add_on_slug; ?> {
											background: <?php echo $color2; ?>;
										}
									</style>
								<?php
								}
							?>

							<div id='bookacti-add-on-image-<?php echo esc_attr( $add_on_slug ); ?>' class='bookacti-add-on-image'>
								<a href='<?php echo esc_url( $add_on[ 'link' ] ); ?>' title='<?php echo esc_attr( $add_on[ 'title' ] ); ?>' target='_blank'>
									<img src='<?php echo esc_url( $img_url ); ?>' title='<?php echo esc_attr( $add_on[ 'title' ] ); ?>'/>
								</a>
							</div>
							<?php } ?>

							<div class='bookacti-add-on-description'>
								<div class='bookacti-add-on-title'>
									<h4><?php echo esc_html( $add_on[ 'title' ] ); ?></h4>
									<?php if( $add_on[ 'subtitle' ] !== '' ) { ?>
									<em><?php echo esc_html( $add_on[ 'subtitle' ] ); ?></em>
									<?php } ?>
								</div>

								<div class='bookacti-add-on-excerpt'><p><?php echo esc_html( $add_on[ 'excerpt' ] ); ?></p></div>

								<div class='bookacti-add-on-button'>
									<a href='<?php echo esc_url( $add_on[ 'link' ] ); ?>' title='<?php echo esc_attr( $add_on[ 'title' ] ); ?>' target='_blank' ><?php esc_html_e( 'More information', 'booking-activities' ); ?></a>
								</div>
							</div>
						</div>
					</div>
				<?php
				}
			}
		?>
		</div>
		
		<div id='bookacti-add-ons-guarantees'>
			<div id='bookacti-add-ons-guarantees-intro'>
				<h3><?php esc_html_e( 'Benefit from the best guarantees', 'booking-activities' ); ?></h3>
				<p><?php esc_html_e( 'Our customers\' satisfaction is what keeps us moving in the right direction. We adapt our products based on your feedback to meet your needs. So give Booking Activities and its add-ons a try. If they don\'t meet your expectations, just let us know. That\'s why Booking Activities is completely free and we offer a 30-day money-back guarantee on all our add-ons.', 'booking-activities' ); ?></p>
			</div>
			<div id='bookacti-add-ons-guarantees-container'>
				<div class='bookacti-add-ons-guarantee'>
					<div class='bookacti-add-ons-guarantee-picto'><span class="dashicons dashicons-lock"></span></div>
					<h4><?php esc_html_e( 'Secure Payments', 'booking-activities' ); ?></h4>
					<div class='bookacti-add-ons-guarantee-description'><?php esc_html_e( 'Online payments are secured by PayPal and Stripe', 'booking-activities' ); ?></div>
				</div>
				<div class='bookacti-add-ons-guarantee'>
					<div class='bookacti-add-ons-guarantee-picto'><span class="dashicons dashicons-money"></span></div>
					<h4><?php esc_html_e( '30-Day money back guarantee', 'booking-activities' ); ?></h4>
					<div class='bookacti-add-ons-guarantee-description'><?php esc_html_e( 'If you are not satisfied you will be 100% refunded', 'booking-activities' ); ?></div>
				</div>
				<div class='bookacti-add-ons-guarantee'>
					<div class='bookacti-add-ons-guarantee-picto'><span class="dashicons dashicons-email-alt"></span></div>
					<h4><?php esc_html_e( 'Ready to help', 'booking-activities' ); ?></h4>
					<div class='bookacti-add-ons-guarantee-description'><?php /* translators: %s = support email address) */ echo sprintf( esc_html__( 'Contact us at %s, we answer within 48h', 'booking-activities' ), 'contact@booking-activities.fr' ); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>