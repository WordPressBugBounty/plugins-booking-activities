<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Send a new status notification for a booking attached to an order item
 * @since 1.9.0
 * @version 1.16.0
 * @param array $order_item_booking
 * @param string $new_status
 * @param WC_Order $order
 * @param boolean $forced True to ignore the checks and send the notifications in any cases
 * @param boolean $is_new_order 
 */
function bookacti_wc_send_order_item_booking_status_notification( $order_item_booking, $new_status, $order, $forced = false, $is_new_order = false ) {
	// Get order current status
	if( is_numeric( $order ) ) { $order = wc_get_order( $order ); }
	$order_status = $order ? $order->get_status() : '';
	
	// Do not send notifications at all for transitionnal order status, because the booking is still considered as temporary
	if( ! $forced ) {
		$transitionnal_status_from = array( 'pending', 'failed' );
		$transitionnal_status_to = array( 'pending', 'failed', 'cancelled' );
		foreach( $transitionnal_status_from as $status_from ) {
			foreach( $transitionnal_status_to as $status_to ) {
				if( ( $order_status === $status_from && $new_status === $status_to ) || did_action( 'wc-' . $status_from . '_to_wc-' . $status_to ) ) { return; }
			}
		}
	}
	
	// If the state hasn't changed, do not send the notifications, unless it is a new order
	$old_status = $order_item_booking[ 'type' ] === 'group' ? $order_item_booking[ 'bookings' ][ 0 ]->group_state : $order_item_booking[ 'bookings' ][ 0 ]->state;
	if( $old_status === $new_status && ! $forced ) { return; }
	
	// Check if the administrator must be notified
	// If the booking status is pending or booked, notify administrator, unless if the administrator made this change
	$action = isset( $_REQUEST[ 'action' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'action' ] ) : '';
	$notify_admin = 0;
	if(  in_array( $new_status, bookacti_get_active_booking_statuses(), true )
	&& ! in_array( $action, array( 'woocommerce_mark_order_status', 'editpost' ) ) ) {
		$admin_notification = bookacti_get_notification_settings( 'admin_new_booking' );
		$notify_admin = ! empty( $admin_notification[ 'active_with_wc' ] ) ? 1 : 0;
	}
	
	// Check if the customer must be notified
	$customer_notification = bookacti_get_notification_settings( 'customer_' . $new_status . '_booking' );
	$notify_customer = ! empty( $customer_notification[ 'active_with_wc' ] ) ? 1 : 0;
	
	$notification_args = apply_filters( 'bookacti_wc_order_item_booking_status_notification_args', array(), $order_item_booking, $new_status, $order, $forced );
	
	// Send a booking confirmation to the customer
	if( $notify_customer && $new_status ) {
		bookacti_send_notification( 'customer_' . $new_status . '_booking', $order_item_booking[ 'id' ], $order_item_booking[ 'type' ], $notification_args );
	}

	// Notify administrators that a new booking has been made
	if( $notify_admin && $is_new_order ) {
		bookacti_send_notification( 'admin_new_booking', $order_item_booking[ 'id' ], $order_item_booking[ 'type' ], $notification_args );
	}
	
	do_action( 'bookacti_wc_send_order_item_booking_status_notification', $order_item_booking, $new_status, $order, $forced, $is_new_order, $notification_args );
}


/**
 * Send one notification per booking to admin and customer when an order contining bookings is made or when its status changes
 * @since 1.9.0 (was bookacti_send_notification_when_order_status_changes)
 * @version 1.15.10
 * @param array $order_item_booking
 * @param array $sanitized_data
 * @param WC_Order $order
 * @param array $new_data
 * @param array $where
 */
function bookacti_wc_send_notification_when_order_item_booking_status_changes( $order_item_booking, $sanitized_data, $order, $new_data, $where ) {
	if( empty( $sanitized_data[ 'status' ] ) || ( isset( $new_data[ 'send_notifications' ] ) && ! $new_data[ 'send_notifications' ] ) ) { return; }
	$new_booking_status = $sanitized_data[ 'status' ];
	
	if( empty( $new_data[ 'is_order_completed' ] ) ) {
		$is_new_order = ! empty( $new_data[ 'is_new_order' ] );
		bookacti_wc_send_order_item_booking_status_notification( $order_item_booking, $new_booking_status, $order, false, $is_new_order );
	} 
	
	// If the order is completed, differ the notifications to a hook that let us know what was the old order status (woocommerce_order_status_changed)
	else {
		$order_id_check = intval( $order->get_id() );
		
		/**
		 * Send booking notifications when an order is completed
		 * @since 1.15.10
		 * @param int $order_id
		 * @param string $old_status
		 * @param string $new_status
		 * @param WC_Order $order
		 */
		add_action( 'woocommerce_order_status_changed', function( $order_id, $old_order_status, $new_order_status, $order = null ) use ( $order_item_booking, $new_booking_status, $order_id_check ) {
			if( $new_order_status !== 'completed' || $order_id_check !== intval( $order_id ) ) { return; }
			if( ! $order ) { $order = wc_get_order( $order_id ); }
			$is_new_order = ! $old_order_status || in_array( $old_order_status, array( 'pending', 'failed', 'checkout-draft' ), true );
			bookacti_wc_send_order_item_booking_status_notification( $order_item_booking, $new_booking_status, $order, false, $is_new_order );
		}, 5, 4 );
	}
}
add_action( 'bookacti_wc_order_item_booking_updated', 'bookacti_wc_send_notification_when_order_item_booking_status_changes', 10, 5 );


/**
 * Add a mention to notifications
 * @since 1.8.6 (was bookacti_add_admin_refunded_booking_notification before)
 * @version 1.14.0
 * @param array $notifications
 * @return array
 */
function bookacti_add_price_info_to_admin_refund_notifications( $notifications ) {
	$refund_message = __( '<h4>Refund</h4>Price: {price}<br/>Coupon: {refund_coupon_code}', 'booking-activities' );
	if( isset( $notifications[ 'admin_refund_requested_booking' ] ) ) { 
		$notifications[ 'admin_refund_requested_booking' ][ 'email' ][ 'message' ] .= $refund_message;
	}
	if( isset( $notifications[ 'admin_refunded_booking' ] ) ) { 
		$notifications[ 'admin_refunded_booking' ][ 'email' ][ 'message' ] .= $refund_message;
	}
	return $notifications;
}
add_filter( 'bookacti_notifications_default_settings', 'bookacti_add_price_info_to_admin_refund_notifications', 10, 1 );


/**
 * Add WC-specific default notification settings
 * @since 1.2.2
 * @version 1.8.6
 * @param array $notifications
 * @return array
 */
function bookacti_add_wc_default_notification_settings( $notifications ) {
	// Add the active_with_wc option only for certain triggers (booking status changes, and new booking made)
	$active_with_wc_triggers = array( 'new', 'pending', 'booked', 'cancelled', 'refunded' );
	
	// Get WC stock email recipient
	$wc_stock_email_recipient = get_option( 'woocommerce_stock_email_recipient' );
	
	foreach( $notifications as $notification_id => $notification ) {
		// Check if the active_with_wc option should be added
		$add_active_with_wc_option = false;
		foreach( $active_with_wc_triggers as $active_with_wc_trigger ) {
			if( strpos( $notification_id, '_' . $active_with_wc_trigger ) !== false ) { $add_active_with_wc_option = true; } 
		}
		if( ! $add_active_with_wc_option ) { continue; }
		
		// Add the active_with_wc option
		if( ! isset( $notifications[ $notification_id ][ 'active_with_wc' ] ) ) {
			$notifications[ $notification_id ][ 'active_with_wc' ] = 0;
		}
		
		// Add the recipients to the refund request emails
		if( strpos( $notification_id, 'admin_refund_requested' ) !== false 
		&&  ! in_array( $wc_stock_email_recipient, $notifications[ $notification_id ][ 'email' ][ 'to' ], true ) ) {
			$notifications[ $notification_id ][ 'email' ][ 'to' ][] = $wc_stock_email_recipient;
		}
	}
	
	return $notifications;
}
add_filter( 'bookacti_notifications_default_settings', 'bookacti_add_wc_default_notification_settings', 100, 1 );


/**
 * Sanitize WC-specific notifications settings
 * 
 * @since 1.2.2
 * @param array $notification
 * @param string $notification_id
 * @return array
 */
function bookacti_sanitize_wc_notification_settings( $notification, $notification_id ) {
	if( isset( $notification[ 'active_with_wc' ] ) ) {
		$notification[ 'active_with_wc' ] = intval( $notification[ 'active_with_wc' ] ) ? 1 : 0;
	}
	return $notification;
}
add_filter( 'bookacti_notification_sanitized_settings', 'bookacti_sanitize_wc_notification_settings', 20, 2 );


/**
 * Make sure that WC order data are up to date when a WC notification is sent
 * @since 1.2.2
 * @version 1.9.0
 * @param array $args
 * @return array
 */
function bookacti_wc_email_order_item_args( $args ) {
	// Check if the order contains bookings
	$has_bookings = false;
	foreach( $args[ 'items' ] as $item ) {
		$order_item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $item );
		if( $order_item_bookings_ids ) {
			$has_bookings = true;
			break;
		}
	}
	
	// If the order has no bookings, change nothing
	if( ! $has_bookings ) { return $args; }
		
	// If the order has bookings, refresh the order instance to make sure data are up to date
	$order_id = $args[ 'order' ]->get_id();
	$fresh_order_instance = wc_get_order( $order_id );
	
	$args[ 'order' ] = $fresh_order_instance;
	$args[ 'items' ] = $fresh_order_instance->get_items();
	
	return $args;
}
add_filter( 'woocommerce_email_order_items_args', 'bookacti_wc_email_order_item_args', 10, 1 );


/**
 * Add WC notifications tags descriptions
 * @since 1.6.0
 * @version 1.16.29
 * @param array $tags
 * @param int $notification_id
 * @return array
 */
function bookacti_wc_notifications_tags( $tags, $notification_id ) {
	if( strpos( $notification_id, 'refund' ) !== false ) {
		$tags[ '{refund_coupon_code}' ] = esc_html__( 'The WooCommerce coupon code generated when the booking was refunded.', 'booking-activities' );
	}
	
	$tags[ '{product_id}' ]           = esc_html__( 'The order item product ID.', 'booking-activities' );
	$tags[ '{product_title}' ]        = esc_html__( 'The order item title.', 'booking-activities' );
	$tags[ '{order_status}' ]         = esc_html__( 'The order status.', 'booking-activities' );
	$tags[ '{order_payment_status}' ] = esc_html__( 'The order payment status (Paid or Owed).', 'booking-activities' );
	$tags[ '{customer_order_note}' ]  = esc_html__( 'Customer order note', 'booking-activities' );
	
	return $tags;
}
add_filter( 'bookacti_notifications_tags', 'bookacti_wc_notifications_tags', 15, 2 );


/**
 * Set WC notifications tags values
 * @since 1.6.0
 * @version 1.16.29
 * @param array $tags
 * @param object $booking
 * @param string $booking_type
 * @param array $notification
 * @return array
 */
function bookacti_wc_notifications_tags_values( $tags, $booking, $booking_type, $notification ) {
	if( ! $booking ) { return $tags; }
	
	$item = $booking_type === 'group' ? bookacti_get_order_item_by_booking_group_id( $booking ) : bookacti_get_order_item_by_booking_id( $booking );
	
	// Use WC user data if the booking was made with WC, or if we only have these data
	$user = is_numeric( $booking->user_id ) ? get_user_by( 'id', $booking->user_id ) : null;
	if( $user ) {
		if( ! empty( $user->billing_first_name ) && ( $item || empty( $tags[ '{user_firstname}' ] ) ) ) { $tags[ '{user_firstname}' ] = $user->billing_first_name; }
		if( ! empty( $user->billing_last_name )  && ( $item || empty( $tags[ '{user_lastname}' ] ) ) )  { $tags[ '{user_lastname}' ]  = $user->billing_last_name; }
		if( ! empty( $user->billing_email )      && ( $item || empty( $tags[ '{user_email}' ] ) ) )     { $tags[ '{user_email}' ]     = $user->billing_email; }
		if( ! empty( $user->billing_phone )      && ( $item || empty( $tags[ '{user_phone}' ] ) ) )     { $tags[ '{user_phone}' ]     = $user->billing_phone; }
	}
	
	if( ! $item ) { return $tags; }
	
	$item_id         = $item->get_id();
	$product_id      = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
	$order_id        = $item->get_order_id();
	$order           = $item->get_order();
	$order_item_name = $item->get_name();
	$item_price      = (float) $item->get_total() + (float) $item->get_total_tax();
	$currency        = $order->get_currency();
	$currency_symbol = $currency ? get_woocommerce_currency_symbol( $currency ) : '';
	
	$tags[ '{product_id}' ]           = $product_id ? $product_id : '';
	$tags[ '{product_title}' ]        = $order_item_name !== '' ? apply_filters( 'bookacti_translate_text_external', $order_item_name, '', true, array( 'domain' => 'woocommerce', 'object_type' => 'order_item', 'object_id' => $item_id, 'field' => 'title', 'order_id' => $order_id ) ) : $order_item_name;
	$tags[ '{order_status}' ]         = wc_get_order_status_name( $order->get_status() );
	$tags[ '{order_payment_status}' ] = $order->get_date_paid() ? esc_html__( 'Paid', 'booking-activities' ) : esc_html__( 'Owed', 'booking-activities' );
	$tags[ '{customer_order_note}' ]  = $order->get_customer_note();
	
	if( empty( $booking->booking_pass_id ) ) {
		$tags[ '{price_raw}' ] = $item_price;
		$tags[ '{price}' ]     = bookacti_format_price( $item_price, array( 'currency_symbol' => $currency_symbol ) );
	}
	
	if( strpos( $notification[ 'id' ], 'refund' ) !== false ) {
		$coupon_code = '';
		$refunds = ! empty( $booking->refunds ) ? maybe_unserialize( $booking->refunds ) : array();
		$refunds = is_array( $refunds ) ? bookacti_format_booking_refunds( $refunds, $booking->id, $booking_type ) : array();
		foreach( $refunds as $refund ) {
			if( isset( $refund[ 'coupon' ] ) ) { $coupon_code = $refund[ 'coupon' ]; break; }
		}
		
		// Backward compatibility
		if( ! $coupon_code ) {
			$coupon_code = wc_get_order_item_meta( $item_id, 'bookacti_refund_coupon', true );
		}
		
		$tags[ '{refund_coupon_code}' ]	= strtoupper( $coupon_code );
	}
	
	return $tags;
}
add_filter( 'bookacti_notifications_tags_values', 'bookacti_wc_notifications_tags_values', 100, 4 );


/**
 * Decide whether to send the notification when a refund is processed via WC
 * @since 1.16.0 (was bookacti_allow_refund_notification_during_manual_refund)
 * @param boolean $sending_allowed
 * @param array $notification
 * @param array $tags
 * @param string $locale
 * @param object $booking
 * @param string $booking_type "single" or "group"
 * @param array $args
 * @return boolean
 */
function bookacti_wc_allow_sending_refund_notification( $sending_allowed, $notification, $tags, $locale, $booking, $booking_type, $args ) {
	if( $sending_allowed 
	&&  strpos( $notification[ 'id' ], '_refunded' ) !== false
	&&  empty( $notification[ 'active_with_wc' ] )
	&&  ! empty( $args[ 'refund_action' ] )
	&&  in_array( $args[ 'refund_action' ], array( 'auto', 'manual' ) ) ) { 
		$sending_allowed = false;
	}
	return $sending_allowed;
}
add_filter( 'bookacti_notification_sending_allowed', 'bookacti_wc_allow_sending_refund_notification', 10, 7 );


/**
 * Do not send notifications for in_cart bookings when an event is updated in the calendar editor
 * @since 1.10.0
 * @param boolean $true
 * @param object $booking
 * @return boolean
 */
function bookacti_wc_disallow_event_notifications_for_in_cart_bookings( $true, $booking ) {
	if( $booking->state === 'in_cart' ) { $true = false; }
	return $true;
}
add_filter( 'bookacti_send_event_rescheduled_notification', 'bookacti_wc_disallow_event_notifications_for_in_cart_bookings', 10, 2 );
add_filter( 'bookacti_send_event_rescheduled_notification_count', 'bookacti_wc_disallow_event_notifications_for_in_cart_bookings', 10, 2 );
add_filter( 'bookacti_send_event_cancelled_notification_count', 'bookacti_wc_disallow_event_notifications_for_in_cart_bookings', 10, 2 );
add_filter( 'bookacti_send_group_of_events_cancelled_notification_count', 'bookacti_wc_disallow_event_notifications_for_in_cart_bookings', 10, 2 );


/**
 * Add WC message to the refund requested notification sent to administrators
 * @since 1.15.17 (was bookacti_woocommerce_add_refund_request_email_message)
 * @version 1.16.0
 * @param array $notification
 * @param array $tags
 * @param string $locale
 * @param object $booking
 * @param string $booking_type
 * @param array $args
 * @return array
 */
function bookacti_wc_add_refund_request_email_message( $notification, $tags, $locale, $booking, $booking_type, $args ) {
	if( strpos( $notification[ 'id' ], 'admin_refund_requested' ) === false || empty( $booking->order_id ) ) { return $notification; }
	
	$edit_order_url = '';
	if( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
		$edit_order_url = \Automattic\WooCommerce\Utilities\OrderUtil::get_order_admin_edit_url( absint( $booking->order_id ) );
	} else {
		// WooCommerce Backward Compatibility
		$order = wc_get_order( absint( $booking->order_id ) );
		if( $order ) {
			$edit_order_url = $order->get_edit_order_url();
		}
	}
	
	$go_to_order =	'<div style="background-color: #f5faff; padding: 10px; border: 1px solid #abc; margin-bottom: 30px;">' 
						. esc_html__( 'Click here to go to the order page and process the refund:', 'booking-activities' ) 
						. ' <a href="' . $edit_order_url . '" target="_blank">' 
							. esc_html__( 'Go to refund page', 'booking-activities' ) 
						. '</a>'
					. '</div>';
	
	$notification[ 'email' ][ 'message' ] = $go_to_order . $notification[ 'email' ][ 'message' ];
	return $notification;
}
add_filter( 'bookacti_notification_data', 'bookacti_wc_add_refund_request_email_message', 10, 6 );


/**
 * Replace recipient email with order email if the booking is bound to an order
 * @since 1.14.2
 * @param array $notification
 * @param array $tags
 * @param string $locale
 * @param object $booking
 * @param string $booking_type
 * @param array $args
 * @return array
 */
function bookacti_wc_replace_notification_recipient_user_email_with_order_email( $notification, $tags, $locale, $booking, $booking_type, $args ) {
	if( substr( $notification[ 'id' ], 0, 6 ) === 'admin_' || empty( $booking->order_id ) ) { return $notification; }
	
	$order = wc_get_order( $booking->order_id );
	if( ! $order ) { return $notification; }
	
	$billing_email = $order->get_billing_email( 'edit' );
	if( $billing_email && is_email( $billing_email ) ) { $notification[ 'email' ][ 'to' ] = array( $billing_email ); }
	
	return $notification;
}
add_filter( 'bookacti_notification_data', 'bookacti_wc_replace_notification_recipient_user_email_with_order_email', 5, 6 );