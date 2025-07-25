<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// SANITIZING AND FORMATING

/**
 * Sanitize booking data
 * @since 1.9.0
 * @version 1.16.0
 * @param array $data_raw
 * @return array
 */
function bookacti_sanitize_booking_data( $data_raw ) {
	$default_status = bookacti_get_setting_value( 'bookacti_general_settings', 'default_booking_state' );
	$default_payment_status = bookacti_get_setting_value( 'bookacti_general_settings', 'default_payment_status' );
	$active_statuses = bookacti_get_active_booking_statuses();
	$default = array( 
		'id'              => 0,
		'user_id'         => 0,
		'form_id'         => 0,
		'order_id'        => 0,
		'group_id'        => 0,
		'activity_id'     => 0,
		'event_id'        => 0,
		'event_start'     => '',
		'event_end'       => '',
		'quantity'        => 0,
		'status'          => '', 
		'payment_status'  => '',
		'expiration_date' => '',
		'creation_date'   => '',
		'active'          => -1
	);
	
	$sanitized = array();
	foreach( $default as $key => $value ) {
		if( ! isset( $data_raw[ $key ] ) ) {
			$sanitized[ $key ] = $value;
			continue;
		}
		
		if( in_array( $key, array( 'user_id' ), true ) ) {
			$sanitized[ $key ] = is_numeric( $data_raw[ $key ] ) ? intval( $data_raw[ $key ] ) : ( is_email( $data_raw[ $key ] ) ? $data_raw[ $key ] : ( is_string( $data_raw[ $key ] ) && $data_raw[ $key ] ? sanitize_title_with_dashes( $data_raw[ $key ] ) : $value ) );
		}
		else if( in_array( $key, array( 'id', 'activity_id', 'event_id', 'quantity' ), true ) ) {
			$sanitized[ $key ] = is_numeric( $data_raw[ $key ] ) && intval( $data_raw[ $key ] ) >= 0 ? intval( $data_raw[ $key ] ) : $value;
		}
		else if( in_array( $key, array( 'form_id', 'order_id', 'group_id' ), true ) ) { // -1 = NULL
			$sanitized[ $key ] = is_numeric( $data_raw[ $key ] ) && intval( $data_raw[ $key ] ) >= -1 ? intval( $data_raw[ $key ] ) : $value;
		}
		else if( in_array( $key, array( 'event_start', 'event_end', 'creation_date', 'expiration_date' ), true ) ) {
			$datetime = is_string( $data_raw[ $key ] ) ? bookacti_sanitize_datetime( $data_raw[ $key ] ) : $value;
			$sanitized[ $key ] = $datetime ? $datetime : $value;
		}
		else if( $key === 'status' ) {
			$sanitized[ $key ] = in_array( $data_raw[ $key ], array_keys( bookacti_get_booking_statuses() ), true ) ? $data_raw[ $key ] : $value;
		}
		else if( $key === 'payment_status' ) {
			$sanitized[ $key ] = in_array( $data_raw[ $key ], array_keys( bookacti_get_payment_statuses() ), true ) ? $data_raw[ $key ] : $value;
		}
		else if( $key === 'active' ) {
			$sanitized[ $key ] = in_array( $data_raw[ $key ], array( -1, 0, 1, '-1', '0', '1', true, false ), true ) ? intval( $data_raw[ $key ] ) : ( in_array( $sanitized[ 'status' ], $active_statuses, true ) ? 1 : 0 );
		}
	}
	
	// Support both state and status keys
	$sanitized[ 'state' ] = $sanitized[ 'status' ];
	
	return apply_filters( 'bookacti_booking_data_sanitized', $sanitized, $data_raw );
}


/**
 * Sanitize booking group data
 * @since 1.9.0
 * @version 1.16.0
 * @param array $data_raw
 * @return array
 */
function bookacti_sanitize_booking_group_data( $data_raw ) {
	$default_status = bookacti_get_setting_value( 'bookacti_general_settings', 'default_booking_state' );
	$default_payment_status = bookacti_get_setting_value( 'bookacti_general_settings', 'default_payment_status' );
	$active_statuses = bookacti_get_active_booking_statuses();
	$default = array( 
		'id'              => 0,
		'group_date'      => '',
		'user_id'         => 0,
		'form_id'         => 0,
		'order_id'        => 0,
		'category_id'     => 0,
		'event_group_id'  => 0,
		'grouped_events'  => array(),
		'quantity'        => 0,
		'status'          => '', 
		'payment_status'  => '',
		'expiration_date' => '',
		'creation_date'   => '',
		'active'          => -1
	);
	
	$sanitized = array();
	foreach( $default as $key => $value ) {
		if( ! isset( $data_raw[ $key ] ) ) {
			$sanitized[ $key ] = $value;
			continue;
		}
		
		if( in_array( $key, array( 'user_id' ), true ) ) {
			$sanitized[ $key ] = is_numeric( $data_raw[ $key ] ) && $data_raw[ $key ] ? intval( $data_raw[ $key ] ) : ( is_email( $data_raw[ $key ] ) ? $data_raw[ $key ] : ( is_string( $data_raw[ $key ] ) && $data_raw[ $key ] ? sanitize_title_with_dashes( $data_raw[ $key ] ) : $value ) );
		}
		else if( in_array( $key, array( 'id', 'quantity' ), true ) ) {
			$sanitized[ $key ] = is_numeric( $data_raw[ $key ] ) && intval( $data_raw[ $key ] ) >= 0 ? intval( $data_raw[ $key ] ) : $value;
		}
		else if( in_array( $key, array( 'form_id', 'order_id', 'event_group_id', 'category_id' ), true ) ) { // -1 = NULL
			$sanitized[ $key ] = is_numeric( $data_raw[ $key ] ) && intval( $data_raw[ $key ] ) >= -1 ? intval( $data_raw[ $key ] ) : $value;
		}
		else if( in_array( $key, array( 'group_date' ), true ) ) {
			$date = is_string( $data_raw[ $key ] ) ? bookacti_sanitize_date( $data_raw[ $key ] ) : $value;
			$sanitized[ $key ] = $date ? $date : $value;
		}
		else if( in_array( $key, array( 'creation_date', 'expiration_date' ), true ) ) {
			$datetime = is_string( $data_raw[ $key ] ) ? bookacti_sanitize_datetime( $data_raw[ $key ] ) : $value;
			$sanitized[ $key ] = $datetime ? $datetime : $value;
		}
		else if( $key === 'grouped_events' ) {
			if( is_array( $data_raw[ $key ] ) ) {
				foreach( $data_raw[ $key ] as $grouped_event ) {
					$grouped_event = bookacti_format_picked_event( $grouped_event );
					if( $grouped_event ) { $sanitized[ $key ][] = $grouped_event; }
				}
			}
		}
		else if( $key === 'status' ) {
			$sanitized[ $key ] = in_array( $data_raw[ $key ], array_keys( bookacti_get_booking_statuses() ), true ) ? $data_raw[ $key ] : $value;
		}
		else if( $key === 'payment_status' ) {
			$sanitized[ $key ] = in_array( $data_raw[ $key ], array_keys( bookacti_get_payment_statuses() ), true ) ? $data_raw[ $key ] : $value;
		}
		else if( $key === 'active' ) {
			$sanitized[ $key ] = in_array( $data_raw[ $key ], array( -1, 0, 1, '-1', '0', '1', true, false ), true ) ? intval( $data_raw[ $key ] ) : ( in_array( $sanitized[ 'status' ], $active_statuses, true ) ? 1 : 0 );
		}
	}
	
	// Support both state and status keys
	$sanitized[ 'state' ] = $sanitized[ 'status' ];
	
	return apply_filters( 'bookacti_booking_group_data_sanitized', $sanitized, $data_raw );
}




// BOOKINGS

/**
 * Get booking filters according to the booking selection
 * @since 1.16.0
 * @version 1.16.1
 * @param string $booking_type
 * @param string $context
 * @return array
 */
function bookacti_get_selected_bookings_filters( $booking_type = 'both' ) {
	$booking_selection = isset( $_REQUEST[ 'booking_selection' ] ) ? ( is_array( $_REQUEST[ 'booking_selection' ] ) ? $_REQUEST[ 'booking_selection' ] : ( is_string( $_REQUEST[ 'booking_selection' ] ) ? bookacti_maybe_decode_json( stripslashes( $_REQUEST[ 'booking_selection' ] ), true ) : array() ) ) : array();
	$all_bookings      = ! empty( $booking_selection[ 'all' ] ) && $booking_type !== 'both';
	$booking_ids       = ! empty( $booking_selection[ 'booking_ids' ] ) && ! $all_bookings ? bookacti_ids_to_array( $booking_selection[ 'booking_ids' ] ) : array();
	$booking_group_ids = ! empty( $booking_selection[ 'booking_group_ids' ] ) && ! $all_bookings ? bookacti_ids_to_array( $booking_selection[ 'booking_group_ids' ] ) : array();
	$filters_raw       = isset( $booking_selection[ 'filters' ] ) && is_array( $booking_selection[ 'filters' ] ) ? $booking_selection[ 'filters' ] : array();
	
	if( ! $all_bookings ) {
		if( in_array( $booking_type, array( 'single', 'both' ), true ) ) {
			$in__booking_id = ! empty( $filters_raw[ 'in__booking_id' ] ) && is_array( $filters_raw[ 'in__booking_id' ] ) ? $filters_raw[ 'in__booking_id' ] : array();
			$filters_raw[ 'in__booking_id' ] = bookacti_ids_to_array( array_merge( $in__booking_id, $booking_ids ) );
		} 
		if( in_array( $booking_type, array( 'group', 'both' ), true ) ) {
			$in__booking_group_id = ! empty( $filters_raw[ 'in__booking_group_id' ] ) && is_array( $filters_raw[ 'in__booking_group_id' ] ) ? $filters_raw[ 'in__booking_group_id' ] : array();
			$filters_raw[ 'in__booking_group_id' ] = bookacti_ids_to_array( array_merge( $in__booking_group_id, $booking_group_ids ) );
		}
		if( ( $booking_type === 'single' && empty( $filters_raw[ 'booking_id' ] ) && empty( $filters_raw[ 'in__booking_id' ] ) )
		||  ( $booking_type === 'group' && empty( $filters_raw[ 'booking_group_id' ] ) && empty( $filters_raw[ 'in__booking_group_id' ] ) )
		||  ( $booking_type === 'both' && empty( $filters_raw[ 'booking_id' ] ) && empty( $filters_raw[ 'in__booking_id' ] ) && empty( $filters_raw[ 'booking_group_id' ] ) && empty( $filters_raw[ 'in__booking_group_id' ] ) ) ) {
			$filters_raw = array();
		}
		if( $booking_type === 'both' && $filters_raw ) {
			$filters_raw[ 'booking_group_id_operator' ] = 'OR';
		}
	} else {
		$booking_ids       = isset( $booking_selection[ 'booking_ids' ] ) ? bookacti_ids_to_array( $booking_selection[ 'booking_ids' ] ) : array();
		$booking_group_ids = isset( $booking_selection[ 'booking_group_ids' ] ) ? bookacti_ids_to_array( $booking_selection[ 'booking_group_ids' ] ) : array();
		if( ! $booking_ids && ! $booking_group_ids ) {
			$filters_raw = array();
		}
	}
	
	$filters = $filters_raw ? bookacti_format_booking_filters( $filters_raw ) : array();
	
	return apply_filters( 'bookacti_selected_bookings_filters', $filters, $booking_type );
}


/**
 * Get bookings according to the booking selection
 * @since 1.16.0
 * @version 1.16.1
 * @param boolean $no_cache
 * @return array
 */
function bookacti_get_selected_bookings( $no_cache = false ) {
	$booking_selection = isset( $_REQUEST[ 'booking_selection' ] ) ? ( is_array( $_REQUEST[ 'booking_selection' ] ) ? $_REQUEST[ 'booking_selection' ] : ( is_string( $_REQUEST[ 'booking_selection' ] ) ? bookacti_maybe_decode_json( stripslashes( $_REQUEST[ 'booking_selection' ] ), true ) : array() ) ) : array();
	$selection_hash    = md5( json_encode( $booking_selection ) );
	if( ! $no_cache ) {
		$selected_bookings = wp_cache_get( 'selected_bookings_' . $selection_hash, 'bookacti' );
		if( $selected_bookings ) { return $selected_bookings; }
	}
	
	// Bookings
	$bookings_filters = bookacti_get_selected_bookings_filters( 'single' );
	if( $bookings_filters ) {
		$bookings_filters[ 'group_by' ] = ! empty( $booking_selection[ 'all' ] ) ? 'booking_group' : 'none';
	}
	$bookings = $bookings_filters ? bookacti_get_bookings( array_merge( $bookings_filters, array( 'fetch_meta' => true ) ) ) : array();
	
	$booking_group_ids = array();
	foreach( $bookings as $i => $booking ) {
		$group_id = ! empty( $booking->group_id ) ? intval( $booking->group_id ) : 0;
		if( $group_id ) {
			$booking_group_ids[] = $group_id;
			if( $bookings_filters[ 'group_by' ] === 'booking_group' ) {
				unset( $bookings[ $i ] );
			}
		}
	}
	
	// Booking groups
	$group_filters = bookacti_get_selected_bookings_filters( 'group' );
	if( $group_filters && ! empty( $booking_selection[ 'all' ] ) ) {
		$group_filters[ 'in__booking_group_id' ] = bookacti_ids_to_array( array_merge( $group_filters[ 'in__booking_group_id' ], $booking_group_ids ) );
		if( empty( $group_filters[ 'in__booking_group_id' ] ) && empty( $group_filters[ 'booking_group_id' ] ) ) {
			$group_filters = array();
		}
	}
	$booking_groups = $group_filters ? bookacti_get_booking_groups( array_merge( $group_filters, array( 'fetch_meta' => true ) ) ) : array();
	
	// Groups' bookings
	$group_ids       = $booking_groups ? bookacti_ids_to_array( array_keys( $booking_groups ) ) : array();
	$grouped_filters = $group_ids ? bookacti_format_booking_filters( array( 'in__booking_group_id' => $group_ids, 'group_by' => 'none', 'fetch_meta' => true ) ) : array();
	$groups_bookings = $grouped_filters ? bookacti_get_bookings( $grouped_filters ) : array();
	
	$bookings_by_group = array();
	foreach( $groups_bookings as $groups_booking_id => $groups_booking ) {
		if( ! isset( $bookings_by_group[ $groups_booking->group_id ] ) ) {
			$bookings_by_group[ $groups_booking->group_id ] = array();
		}
		$bookings_by_group[ $groups_booking->group_id ][ $groups_booking_id ] = $groups_booking;
	}
	
	$selected_bookings = apply_filters( 'bookacti_selected_bookings', array(
		'bookings'        => $bookings,
		'booking_groups'  => $booking_groups,
		'groups_bookings' => $bookings_by_group,
	) );
	
	// Cache data
	wp_cache_set( 'selected_bookings_' . $selection_hash, $selected_bookings, 'bookacti' );
	
	return $selected_bookings;
}


/**
 * Sort selected bookings array by user (id or email)
 * @since 1.16.0
 * @param array $selected_bookings
 * @return array
 */
function bookacti_sort_selected_bookings_by_user( $selected_bookings ) {
	$selected_bookings_per_user = array();
	
	foreach( $selected_bookings[ 'bookings' ] as $booking_id => $booking ) {
		$user_id = is_numeric( $booking->user_id ) ? intval( $booking->user_id ) : $booking->user_id;
		if( is_email( $user_id ) ) {
			$user = get_user_by( 'email', $user_id );
			if( $user ) {
				$user_id = intval( $user->ID );
			}
		}
		if( ! $user_id || ( ! is_numeric( $user_id ) && ! is_email( $user_id ) ) ) { continue; }
		
		if( ! isset( $selected_bookings_per_user[ $user_id ] ) ) {
			$selected_bookings_per_user[ $user_id ] = array( 'bookings' => array(), 'booking_groups' => array(), 'groups_bookings' => array() );
		}
		
		$selected_bookings_per_user[ $user_id ][ 'bookings' ][ $booking_id ] = $booking;
	}
	
	foreach( $selected_bookings[ 'booking_groups' ] as $group_id => $booking_group ) {
		$user_id = is_numeric( $booking_group->user_id ) ? intval( $booking_group->user_id ) : $booking_group->user_id;
		if( is_email( $user_id ) ) {
			$user = get_user_by( 'email', $user_id );
			if( $user ) {
				$user_id = intval( $user->ID );
			}
		}
		if( ! $user_id || ( ! is_numeric( $user_id ) && ! is_email( $user_id ) ) ) { continue; }
		
		if( ! isset( $selected_bookings_per_user[ $user_id ] ) ) {
			$selected_bookings_per_user[ $user_id ] = array( 'bookings' => array(), 'booking_groups' => array(), 'groups_bookings' => array() );
		}
		
		$selected_bookings_per_user[ $user_id ][ 'booking_groups' ][ $group_id ] = $booking_group;
		
		if( isset( $selected_bookings[ 'groups_bookings' ][ $group_id ] ) ) {
			$selected_bookings_per_user[ $user_id ][ 'groups_bookings' ][ $group_id ] = $selected_bookings[ 'groups_bookings' ][ $group_id ];
		}
	}
	
	return $selected_bookings_per_user;
}


/**
 * Sort selected bookings array by order id
 * @since 1.16.0
 * @param array $selected_bookings
 * @return array
 */
function bookacti_sort_selected_bookings_by_order( $selected_bookings ) {
	$selected_bookings_per_order = array();
	
	foreach( $selected_bookings[ 'bookings' ] as $booking_id => $booking ) {
		$order_id = is_numeric( $booking->order_id ) ? intval( $booking->order_id ) : 0;
		if( ! $order_id ) { continue; }
		
		if( ! isset( $selected_bookings_per_order[ $order_id ] ) ) {
			$selected_bookings_per_order[ $order_id ] = array( 'bookings' => array(), 'booking_groups' => array(), 'groups_bookings' => array() );
		}
		
		$selected_bookings_per_order[ $order_id ][ 'bookings' ][ $booking_id ] = $booking;
	}
	
	foreach( $selected_bookings[ 'booking_groups' ] as $group_id => $booking_group ) {
		$order_id = is_numeric( $booking_group->order_id ) ? intval( $booking_group->order_id ) : 0;
		if( ! $order_id ) { continue; }
		
		if( ! isset( $selected_bookings_per_order[ $order_id ] ) ) {
			$selected_bookings_per_order[ $order_id ] = array( 'bookings' => array(), 'booking_groups' => array(), 'groups_bookings' => array() );
		}
		
		$selected_bookings_per_order[ $order_id ][ 'booking_groups' ][ $group_id ] = $booking_group;
		
		if( isset( $selected_bookings[ 'groups_bookings' ][ $group_id ] ) ) {
			$selected_bookings_per_order[ $order_id ][ 'groups_bookings' ][ $group_id ] = $selected_bookings[ 'groups_bookings' ][ $group_id ];
		}
	}
	
	return $selected_bookings_per_order;
}


/**
 * Get booking by id
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $booking_id
 * @param boolean $fetch_meta
 * @return object|false
 */
function bookacti_get_booking_by_id( $booking_id, $fetch_meta = false ) {
	$filters = bookacti_format_booking_filters( array( 'in__booking_id' => $booking_id, 'fetch_meta' => $fetch_meta ) );
	$bookings = bookacti_get_bookings( $filters );
	return ! empty( $bookings[ $booking_id ] ) ? $bookings[ $booking_id ] : false;
}


/**
 * Check if a booking is whithin the authorized delay as of now
 * @since 1.1.0
 * @version 1.13.0
 * @param object|int $booking
 * @param string $context 'cancel' or 'reschedule'
 * @return boolean
 */
function bookacti_is_booking_in_delay( $booking, $context = '' ) {
	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking, true ); }
	if( ! $booking ) { return false; }

	$is_in_delay  = false;
	$delay_global = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'booking_changes_deadline' );
	$timezone     = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	
	// Get the more specific per activity / group category delay
	$delay_specific = false;
	if( $booking->group_id ) {
		$category_data = ! empty( $booking->category_id ) ? bookacti_get_metadata( 'group_category', $booking->category_id ) : array();
		if( isset( $category_data[ 'booking_changes_deadline' ] ) && is_numeric( $category_data[ 'booking_changes_deadline' ] ) ) {
			$delay_specific = floatval( $category_data[ 'booking_changes_deadline' ] );
		}
	} else {
		$activity_data = ! empty( $booking->activity_id ) ? bookacti_get_metadata( 'activity', $booking->activity_id ) : array();
		if( isset( $activity_data[ 'booking_changes_deadline' ] ) && is_numeric( $activity_data[ 'booking_changes_deadline' ] ) ) {
			$delay_specific = floatval( $activity_data[ 'booking_changes_deadline' ] );
		}
	}

	// Sanitize
	if( ! is_numeric( $delay_specific ) || $delay_specific < 0 ) { $delay_specific = false; } 
	if( ! is_numeric( $delay_global ) || $delay_global < 0 )     { $delay_global = 0; } 

	// Choose the most specific defined value
	$delay = $delay_specific !== false ? $delay_specific : $delay_global;

	// Convert delay to a valid DateInterval constructor
	$date_interval_constructor = bookacti_format_duration( floatval( $delay ), 'iso8601' );

	$current_datetime = new DateTime( 'now', new DateTimeZone( $timezone ) );
	$date_interval    = apply_filters( 'bookacti_booking_changes_deadline_date_interval', new DateInterval( $date_interval_constructor ), $booking, $delay, $context );
	$delay_datetime   = DateTime::createFromFormat( 'Y-m-d H:i:s', $booking->event_start, new DateTimeZone( $timezone ) );
	$delay_datetime->sub( $date_interval );
	
	if( $current_datetime < $delay_datetime ) { $is_in_delay = true; }

	return apply_filters( 'bookacti_is_booking_in_delay', $is_in_delay, $booking, $delay, $context );
}




// BOOKINGS PAGE

/**
 * Return the HTML code to display activities by templates in the bookings page
 * @version 1.14.0
 * @param array $template_ids
 * @param array $activity_ids
 * @return string
 */
function bookacti_get_activities_html_for_booking_page( $template_ids, $activity_ids = array() ) {
	$activities = bookacti_get_activities_by_template( $template_ids );
	$j = 0;
	$html = '';
	foreach ( $activities as $activity ) {
		if( ( empty( $activity_ids )  && $j === 0 ) || in_array( $activity[ 'id' ], $activity_ids ) ) { $selected = 'selected'; } else { $selected = ''; }

		// Display activity
		$html .= "<div class='bookacti-bookings-filter-activity bookacti-bookings-filter' "
			  .    "data-activity-id='" . esc_attr( $activity[ 'id' ] ) . "' "
			  .    "style='background-color: " . esc_attr( $activity[ 'color' ] ) . "; border-color: " . esc_attr( $activity[ 'color' ] ) . "' " 
			  .      esc_attr( $selected )
			  .  ">"
			  .    "<div class='bookacti-filter-content'>"
			  .      "<div class='bookacti-bookings-filter-activity-title'>"
			  .        "<strong>" . esc_html( $activity[ 'title' ] ). "</strong>"
			  .      "</div>"
			  .    "</div>"
			  .    "<div class='bookacti-bookings-filter-bg'></div>"
			  .  "</div>";

		$j++;
	}

	return apply_filters( 'bookacti_activities_html_by_templates', $html, $template_ids, $activity_ids );
}


/**
 * Get Default booking filters
 * @since 1.6.0
 * @version 1.16.12
 * @return array
 */
function bookacti_get_default_booking_filters() {
	return apply_filters( 'bookacti_default_booking_filters', array(
		'templates'                  => array(), 
		'activities'                 => array(), 
		'booking_id'                 => 0, 
		'booking_group_id'           => 0,
		'booking_group_id_operator'  => 'AND',
		'group_category_id'          => 0, 
		'event_group_id'             => 0, 
		'event_id'                   => 0, 
		'event_start'                => '', 
		'event_end'                  => '', 
		'status'                     => array(), 
		'payment_status'             => array(), 
		'user_id'                    => 0,
		'form_id'                    => 0,
		'from'                       => '',
		'to'                         => '',
		'end_from'                   => '',
		'end_to'                     => '',
		'created_from'               => '',
		'created_to'                 => '',
		'active'                     => false,
		'group_by'                   => '',
		'order_by'                   => array( 'creation_date', 'id', 'event_start' ), 
		'order'                      => 'desc',
		'offset'                     => 0,
		'per_page'                   => 0,
		'in__booking_id'             => array(),
		'in__booking_group_id'       => array(),
		'in__booking_group_date'     => array(),
		'in__group_category_id'      => array(),
		'in__event_group_id'         => array(),
		'in__event_id'               => array(),
		'in__user_id'                => array(),
		'in__form_id'                => array(),
		'in__order_id'               => array(),
		'not_in__booking_id'         => array(),
		'not_in__booking_group_id'   => array(),
		'not_in__booking_group_date' => array(),
		'not_in__group_category_id'  => array(),
		'not_in__event_group_id'     => array(),
		'not_in__event_id'           => array(),
		'not_in__user_id'            => array(),
		'not_in__form_id'            => array(),
		'not_in__order_id'           => array(),
		'not_in__status'             => array(),
		'not_in__payment_status'     => array(),
		'fetch_meta'                 => false
	));
}


/**
 * Format booking filters
 * @since 1.3.0
 * @version 1.16.12
 * @param array $filters 
 * @return array
 */
function bookacti_format_booking_filters( $filters = array() ) {
	$formatted_filters = array();
	$default_filters = bookacti_get_default_booking_filters();
	foreach( $default_filters as $filter => $default_value ) {
		// If a filter isn't set, use the default value
		if( ! isset( $filters[ $filter ] ) ) { $formatted_filters[ $filter ] = $default_value; continue; }

		$current_value = $filters[ $filter ];
		
		// Specific pre-format
		if( in_array( $filter, array( 'from', 'end_from', 'created_from' ), true ) ) {
			if( bookacti_sanitize_date( $current_value ) ) { $current_value = bookacti_sanitize_date( $current_value ) . ' 00:00:00'; }
		} else if( in_array( $filter, array( 'to', 'end_to', 'created_to' ), true ) ) {
			if( bookacti_sanitize_date( $current_value ) ) { $current_value = bookacti_sanitize_date( $current_value ) . ' 23:59:59'; }
		}
		
		// Else, check if its value is correct, or use default
		if( in_array( $filter, array( 'templates' ) ) ) {
			if( is_numeric( $current_value ) ) { $current_value = array( $current_value ); }
			if( is_array( $current_value ) ) {
				// Check if current user is allowed to manage desired templates, or unset them
				if( ! empty( $current_value ) ) {
					foreach( $current_value as $i => $template_id ) {
					if( ! is_numeric( $template_id ) || ! bookacti_user_can_manage_template( $template_id ) ) {
							unset( $current_value[ $i ] );
						}
					}
				}
				// Re-check if the template list is empty because some template filters may have been removed
				// and get all allowed templates if it is empty
				if( empty( $current_value ) ) {
					$current_value = array_keys( bookacti_fetch_templates() );
				}
			}
			else { $current_value = $default_value; }
			$current_value = array_values( bookacti_ids_to_array( $current_value, false ) );

		} else if( in_array( $filter, array( 'activities', 'in__booking_id', 'in__booking_group_id', 'in__group_category_id', 'in__event_group_id', 'in__event_id', 'in__form_id', 'in__order_id', 'not_in__booking_id', 'not_in__booking_group_id', 'not_in__group_category_id', 'not_in__event_group_id', 'not_in__event_id', 'not_in__form_id', 'not_in__order_id' ), true ) ) {
			if( is_numeric( $current_value ) ) { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value, true ) ) !== false ) { unset( $current_value[ $i ] ); }
			$current_value = array_values( bookacti_ids_to_array( $current_value, false ) );

		} else if( in_array( $filter, array( 'in__user_id', 'not_in__user_id' ), true ) ) {
			if( is_numeric( $current_value ) || is_string( $current_value ) ) { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			$current_value = array_values( array_filter( array_map( 'sanitize_text_field', $current_value ) ) );
			
		} else if( in_array( $filter, array( 'in__booking_group_date', 'not_in__booking_group_date' ), true ) ) {
			if( is_string( $current_value ) )  { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			else { $current_value = array_values( array_filter( array_map( 'bookacti_sanitize_date', array_filter( $current_value, 'is_string' ) ) ) ); }
		
		} else if( in_array( $filter, array( 'status', 'payment_status', 'not_in__status', 'not_in__payment_status' ), true ) ) {
			if( is_string( $current_value ) )  { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value, true ) ) !== false ) { unset( $current_value[ $i ] ); }
			$current_value = array_values( bookacti_str_ids_to_array( $current_value ) );

		} else if( in_array( $filter, array( 'booking_id', 'booking_group_id', 'group_category_id', 'event_group_id', 'event_id', 'offset', 'per_page' ), true ) ) {
			if( ! is_numeric( $current_value ) ) { $current_value = $default_value; }
			$current_value = intval( $current_value );

		} else if( in_array( $filter, array( 'event_start', 'event_end', 'from', 'to', 'end_from', 'end_to', 'created_from', 'created_to' ), true ) ) {
			$current_value = bookacti_sanitize_datetime( $current_value );
			if( ! $current_value ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'active' ), true ) ) {
				 if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) ) { $current_value = 1; }
			else if( in_array( $current_value, array( 0, '0' ), true ) ) { $current_value = 0; }
			if( ! in_array( $current_value, array( 0, 1 ), true ) ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'fetch_meta' ), true ) ) {
			if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) ) { $current_value = true; }
			else { $current_value = false; }

		} else if( $filter === 'order_by' ) {
			$sortable_columns = array( 
				'id', 
				'user_id', 
				'event_id', 
				'event_title', 
				'event_start', 
				'event_end', 
				'state', 
				'payment_status', 
				'quantity', 
				'template_id', 
				'activity_id', 
				'creation_date' 
			);
			if( is_string( $current_value ) ) { 
				if( ! in_array( $current_value, $sortable_columns, true ) ) { $current_value = $default_value; }
				else { $current_value = array( $current_value ); }
			}
			if( ! is_array( $current_value ) || ! $current_value ) { $current_value = $default_value; }
			$current_value = array_values( bookacti_str_ids_to_array( $current_value ) );
			if( count( $current_value ) === 1 ) {
				if( $current_value[ 0 ] === 'creation_date' ) { $current_value = array( 'creation_date', 'id', 'event_start' ); }
				else if( $current_value[ 0 ] === 'id' )       { $current_value = array( 'id', 'event_start' ); }
			}
			
		} else if( $filter === 'order' ) {
			if( ! in_array( $current_value, array( 'asc', 'desc' ), true ) ) { $current_value = $default_value; }

		} else if( $filter === 'group_by' ) {
			if( ! in_array( $current_value, array( 'none', 'booking_group' ), true ) ) { $current_value = $default_value; }

		} else if( $filter === 'user_id' ) {
			if( ! is_numeric( $current_value ) && ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = sanitize_text_field( $current_value );
			
		} else if( $filter === 'booking_group_id_operator' ) {
			if( ! in_array( $current_value, array( 'AND', 'OR', 'XOR' ), true ) ) { $current_value = $default_value; }
		}

		$formatted_filters[ $filter ] = $current_value;
	}

	return apply_filters( 'bookacti_formatted_booking_filters', $formatted_filters, $filters, $default_filters );
}


/**
 * Format booking filters manually input
 * @since 1.6.0
 * @version 1.16.0
 * @param array $filters
 * @return array
 */
function bookacti_format_string_booking_filters( $filters = array() ) {
	// Format arrays
	$formatted_arrays = array();
	$int_arrays = array( 'templates', 'activities', 'in__booking_id', 'in__booking_group_id', 'in__group_category_id', 'in__event_group_id', 'in__event_id','in__form_id', 'not_in__booking_id', 'not_in__booking_group_id', 'not_in__group_category_id', 'not_in__event_group_id', 'not_in__event_id', 'not_in__form_id' );
	$str_arrays = array( 'status', 'payment_status', 'order_by', 'columns' );
	$user_id_arrays = array( 'in__user_id', 'not_in__user_id' );

	foreach( array_merge( $int_arrays, $str_arrays, $user_id_arrays ) as $att_name ) {
		if( empty( $filters[ $att_name ] ) || is_array( $filters[ $att_name ] ) ) { continue; }

		$formatted_value = preg_replace( array(
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', $filters[ $att_name ] );

		if( in_array( $att_name, $int_arrays, true ) ) { 
			$formatted_arrays[ $att_name ] = explode( ',', preg_replace( array(
				'/[^\d,]/',    // Matches anything that's not a comma or number.
			), '', $formatted_value ) );
			$formatted_arrays[ $att_name ] = bookacti_ids_to_array( $formatted_arrays[ $att_name ], false ); 
		}
		if( in_array( $att_name, $str_arrays, true ) ) { 
			$formatted_arrays[ $att_name ] = explode( ',', $formatted_value );
			$formatted_arrays[ $att_name ] = bookacti_str_ids_to_array( $formatted_arrays[ $att_name ] ); 
		}
		if( in_array( $att_name, $user_id_arrays, true ) ) {
			$formatted_arrays[ $att_name ] = explode( ',', $formatted_value );
			$formatted_arrays[ $att_name ] = array_map( 'sanitize_text_field', $formatted_arrays[ $att_name ] ); 
		}
	}

	// Format datetime
	$from = ''; $to = '';
	if( ! empty( $filters[ 'from' ] ) || ! empty( $filters[ 'to' ] ) !== '' ) { 
		$timezone = new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		if( ! empty( $filters[ 'from' ] ) ) {
			$from_sanitized = sanitize_text_field( $filters[ 'from' ] );
			if( $from_sanitized && (bool) strtotime( $from_sanitized ) ) {
				$from_datetime = new DateTime( $from_sanitized, $timezone );
				$from = $from_datetime->format( 'Y-m-d H:i:s' );
			}
		}
		if( ! empty( $filters[ 'to' ] ) ) {
			$to_sanitized = sanitize_text_field( $filters[ 'to' ] );
			if( $to_sanitized && (bool) strtotime( $to_sanitized ) ) {
				$to_datetime = new DateTime( $to_sanitized, $timezone );
				$to = ! bookacti_sanitize_datetime( $to_sanitized ) && $to_datetime->format( 'H:i:s' ) === '00:00:00' ? $to_datetime->format( 'Y-m-d' ) . ' 23:59:59' : $to_datetime->format( 'Y-m-d H:i:s' );
			}
		}
	}

	return apply_filters( 'bookacti_format_string_booking_filters', array_merge( $filters, array( 'from' => $from, 'to' => $to ), $formatted_arrays ), $filters );
}


/**
 * Format bookings calendar settings
 * @since 1.8.0
 * @param array $raw_settings
 * @return array
 */
function bookacti_format_bookings_calendar_settings( $raw_settings = array() ) {
	if( empty( $raw_settings ) ) { $raw_settings = array(); }
	
	// Default settings
	$default_settings = apply_filters( 'bookacti_bookings_calendar_default_settings', array_merge( array( 
		'show' => 1, 
		'ajax' => 1,
		'tooltip_booking_list' => 1,
		'tooltip_booking_list_columns' => bookacti_get_event_booking_list_default_columns()
	), bookacti_get_booking_system_default_display_data() ) );
	
	$settings = array();
		
	// Check if all settings are filled
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( isset( $raw_settings[ $setting_key ] ) && is_string( $raw_settings[ $setting_key ] ) ){ $raw_settings[ $setting_key ] = stripslashes( $raw_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = isset( $raw_settings[ $setting_key ] ) ? $raw_settings[ $setting_key ] : $setting_default_value;
	}
	
	// Format non display data
	$settings[ 'show' ] = in_array( $settings[ 'show' ], array( 1, '1', true, 'true' ), true ) ? 1 : 0;
	$settings[ 'ajax' ] = in_array( $settings[ 'ajax' ], array( 1, '1', true, 'true' ), true ) ? 1 : 0;
	$settings[ 'tooltip_booking_list' ] = in_array( $settings[ 'tooltip_booking_list' ], array( 1, '1', true, 'true' ), true ) ? 1 : 0;
	
	// Check if desired columns are registered
	$formatted_atts[ 'tooltip_booking_list_columns' ] = is_array( $settings[ 'tooltip_booking_list_columns' ] ) && $settings[ 'tooltip_booking_list_columns' ] ? array_intersect( $settings[ 'tooltip_booking_list_columns' ], array_keys( bookacti_get_user_booking_list_columns_labels() ) ) : $default_settings[ 'tooltip_booking_list_columns' ];
	
	// Format display data
	$display_data = bookacti_format_booking_system_display_data( $raw_settings );
	
	return apply_filters( 'bookacti_bookings_calendar_settings_formatted', array_merge( $settings, $display_data ), $raw_settings, $default_settings );
}


/**
 * Get the bookings to be removed when an whole event is deleted / cancelled
 * @since 1.10.0
 * @version 1.13.0
 * @param object $event
 * @return array
 */
function bookacti_get_removed_event_bookings_to_cancel( $event ) {
	// For repeated event, cancel only future bookings
	$timezone = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$now_dt = new DateTime( 'now', new DateTimeZone( $timezone ) );
	$from = $event->repeat_freq && $event->repeat_freq !== 'none' ? $now_dt->format( 'Y-m-d H:i:s' ) : '';

	// Get the event future occurrences
	$occurrences = bookacti_get_occurrences_of_repeated_event( $event, array( 'past_events' => $from ? 0 : 1 ) );

	// Get bookings to cancel before cancelling them
	$filters = bookacti_format_booking_filters( array( 'event_id' => $event->event_id, 'from' => $from, 'active' => 1 ) );
	$old_bookings = bookacti_get_bookings( $filters );

	// Keep only the booking made on one of the occurrences
	foreach( $old_bookings as $booking_id => $old_booking ) {
		$is_on_occurrence = false;
		foreach( $occurrences as $occurrence ) {
			if( intval( $old_booking->event_id ) === intval( $occurrence[ 'id' ] ) && $old_booking->event_start === $occurrence[ 'start' ] && $old_booking->event_end === $occurrence[ 'end' ] ) {
				$is_on_occurrence = true;
				break;
			}
		}
		if( ! $is_on_occurrence ) { unset( $old_bookings[ $booking_id ] ); }
	}
	
	return $old_bookings;
}




// PERMISSIONS

// SINGLE BOOKINGS

/**
 * Check if user is allowed to manage a booking
 * @version 1.16.0
 * @param object|int $booking
 * @param bool $allow_self Return true if the booking belongs to the user.
 * @param array $capabilities The user must have one of these capabilities. Default to array( 'bookacti_edit_bookings' ).
 * @param int $user_id
 * @return boolean
 */
function bookacti_user_can_manage_booking( $booking, $allow_self = true, $capabilities = array(), $user_id = 0 ) {
	$user_can_manage_booking = false;
	
	// Get desired user (fallback to current user)
	$user = $user_id ? get_user_by( 'id', $user_id ) : wp_get_current_user();
	
	// Check capabilities
	if( ! $capabilities ) {
		$capabilities = array( 'bookacti_edit_bookings' );
	}
	
	$has_cap = false;
	if( $user ) {
		foreach( $capabilities as $capability ) {
			if( user_can( $user, $capability ) ) {
				$has_cap = true;
				break;
			}
		}
	}
	
	// Get booking
	if( is_numeric( $booking ) ) { 
		$booking = bookacti_get_booking_by_id( $booking, true );
	}
	
	if( $booking ) {
		$booking_user_id = isset( $booking->user_id ) && is_numeric( $booking->user_id ) ? intval( $booking->user_id ) : 0;
		$is_own = apply_filters( 'bookacti_allow_others_booking_changes', $booking_user_id && $booking_user_id === intval( $user->ID ), $booking, 'edit' );
		if( ( $has_cap && bookacti_user_can_manage_template( $booking->template_id, $user_id ) ) 
		 || ( $allow_self && $is_own ) ) { 
			$user_can_manage_booking = true; 
		}
	}
	
	return apply_filters( 'bookacti_user_can_manage_booking', $user_can_manage_booking, $booking, $allow_self, $capabilities, $user_id );
}


/**
 * Check if a booking can be cancelled
 * @version 1.16.0
 * @param object|int $booking
 * @param boolean $is_frontend
 * @param boolean $allow_grouped_booking
 * @return boolean
 */
function bookacti_booking_can_be_cancelled( $booking, $is_frontend = true, $allow_grouped_booking = false ) {
	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking, true ); }
	
	$is_allowed = true;
	if( ! $booking ) { $is_allowed = false; }
	else {
		if( ! current_user_can( 'bookacti_edit_bookings' ) || $is_frontend ) {
			$is_cancel_allowed = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_cancel' );
			$is_grouped        = apply_filters( 'bookacti_allow_grouped_booking_changes', $allow_grouped_booking, $booking, 'cancel' ) ? false : ! empty( $booking->group_id );
			$is_in_delay       = apply_filters( 'bookacti_bypass_booking_changes_deadline', false, $booking, 'cancel' ) ? true : bookacti_is_booking_in_delay( $booking, 'cancel' );
			$user_id           = isset( $booking->user_id ) && is_numeric( $booking->user_id ) ? intval( $booking->user_id ) : 0;
			$is_own            = apply_filters( 'bookacti_allow_others_booking_changes', $user_id && $user_id === get_current_user_id(), $booking, 'cancel' );
			if( ! $is_cancel_allowed || $is_grouped || ! $is_in_delay || ! $is_own || empty( $booking->active ) ) { $is_allowed = false; }
		}
	}
	
	return apply_filters( 'bookacti_booking_can_be_cancelled', $is_allowed, $booking, $is_frontend, $allow_grouped_booking );
}


/**
 * Check if a booking is allowed to be rescheduled
 * @version 1.16.0
 * @param object|int $booking
 * @param boolean $is_frontend
 * @return boolean
 */
function bookacti_booking_can_be_rescheduled( $booking, $is_frontend = true ) {
	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking, true ); }
	
	$is_allowed = $booking ? true : false;
	
	if( $booking && ( ! current_user_can( 'bookacti_edit_bookings' ) || $is_frontend ) ) {
		$is_reschedule_allowed = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_reschedule' );
		$is_grouped            = apply_filters( 'bookacti_allow_grouped_booking_changes', false, $booking, 'reschedule' ) ? false : ! empty( $booking->group_id );
		$is_in_delay           = apply_filters( 'bookacti_bypass_booking_changes_deadline', false, $booking, 'reschedule' ) ? true : bookacti_is_booking_in_delay( $booking, 'reschedule' );
		$user_id               = isset( $booking->user_id ) && is_numeric( $booking->user_id ) ? intval( $booking->user_id ) : 0;
		$is_own                = apply_filters( 'bookacti_allow_others_booking_changes', $user_id && $user_id === get_current_user_id(), $booking, 'reschedule' );
		if( ! $is_reschedule_allowed || ! $booking->active || $is_grouped || ! $is_in_delay || ! $is_own ) { $is_allowed = false; }
	}

	return apply_filters( 'bookacti_booking_can_be_rescheduled', $is_allowed, $booking, $is_frontend );
}


/**
 * Check if a booking can be rescheduled to another event
 * @since 1.1.0
 * @version 1.16.40
 * @param object|int $booking
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param boolean $is_frontend
 * @return boolean
 */
function bookacti_booking_can_be_rescheduled_to( $booking, $event_id, $event_start, $event_end, $is_frontend = true ) {
	if( is_numeric( $booking ) ) {
		$booking = bookacti_get_booking_by_id( $booking, true );
	}
	
	if( ! $booking ) { 
		$return_array[ 'status' ]  = 'failed';
		$return_array[ 'error' ]   = 'booking_not_found';
		$return_array[ 'message' ] = esc_html__( 'You are not allowed to reschedule this booking.', 'booking-activities' );
	}
	else {
		$return_array = array( 'status' => 'success' );
		$is_allowed   = bookacti_booking_can_be_rescheduled( $booking, $is_frontend );
		
		if( ! $is_allowed ) {
			$return_array[ 'status' ]  = 'failed';
			$return_array[ 'error' ]   = 'reschedule_not_allowed';
			$return_array[ 'message' ] = esc_html__( 'You are not allowed to reschedule this booking.', 'booking-activities' );
		} 
		else {
			// Check the reschedule scope
			$is_admin                = current_user_can( 'bookacti_edit_bookings' ) && ! $is_frontend;
			$event                   = bookacti_get_event_by_id( $event_id );
			$activity_id             = ! empty( $booking->activity_id ) ? intval( $booking->activity_id ) : 0;
			$activity_meta           = $activity_id ? bookacti_get_metadata( 'activity', $activity_id ) : array();
			$reschedule_scope        = ! empty( $activity_meta[ 'reschedule_scope' ] ) ? $activity_meta[ 'reschedule_scope' ] : ( $is_admin ? 'all_self' : 'form_self' );
			$reschedule_activity_ids = ! empty( $activity_meta[ 'reschedule_activity_ids' ] ) ? bookacti_ids_to_array( $activity_meta[ 'reschedule_activity_ids' ] ) : array();
			$admin_reschedule_scope  = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'admin_reschedule_scope' );
			
			// For administrators, keep the widest reschedule scope
			if( $is_admin ) {
				if( strpos( $reschedule_scope, 'all_' ) !== false && strpos( $admin_reschedule_scope, 'form_' ) !== false ) {
					$admin_reschedule_scope = str_replace( 'form_', 'all_', $admin_reschedule_scope );
				}
				if( strpos( $reschedule_scope, '_custom' ) !== false && strpos( $admin_reschedule_scope, '_self' ) !== false ) {
					$admin_reschedule_scope = str_replace( '_self', '_custom', $admin_reschedule_scope );
				}
				else if( strpos( $reschedule_scope, '_any' ) !== false && strpos( $admin_reschedule_scope, '_self' ) !== false ) {
					$admin_reschedule_scope = str_replace( '_self', '_any', $admin_reschedule_scope );
				}
				$reschedule_scope = $admin_reschedule_scope;
			}
			
			if( ! $event ) {
				$return_array[ 'status' ]  = 'failed';
				$return_array[ 'error' ]   = 'reschedule_to_unknown_event';
				$return_array[ 'message' ] = esc_html__( 'The desired event is unknown.', 'booking-activities' );
			}
			else {
				$event->id         = $event_id;
				$event_activity_id = ! empty( $event->activity_id ) ? intval( $event->activity_id ) : 0;
				$event_template_id = ! empty( $event->template_id ) ? intval( $event->template_id ) : 0;
				$form_id           = ! empty( $booking->form_id ) ? intval( $booking->form_id ) : 0;
				
				if( strpos( $reschedule_scope, '_self' ) !== false && intval( $booking->activity_id ) !== $event_activity_id ) {
					$return_array[ 'status' ]  = 'failed';
					$return_array[ 'error' ]   = 'reschedule_to_different_activity';
					$return_array[ 'message' ] = esc_html__( 'The desired event does not have the same activity as the booked event.', 'booking-activities' );
				}
				else if( strpos( $reschedule_scope, '_custom' ) !== false && $reschedule_activity_ids ) {
					$reschedule_activity_ids = bookacti_ids_to_array( array_merge( array( $activity_id ), $reschedule_activity_ids ) );
					if( ! in_array( $event_activity_id, $reschedule_activity_ids, true ) ) {
						$return_array[ 'status' ]  = 'failed';
						$return_array[ 'error' ]   = 'reschedule_to_not_allowed_activity';
						$return_array[ 'message' ] = esc_html__( 'The desired event does not have the same activity as the booked event.', 'booking-activities' ) . ' ' 
												   . esc_html__( 'You cannot replace the booked activity with this activity.', 'booking-activities' );
					}
				}

				if( $return_array[ 'status' ] !== 'failed' ) {
					if( strpos( $reschedule_scope, 'form_' ) !== false ) {
						$picked_event   = bookacti_format_picked_event( array( 'id' => $event_id, 'start' => $event_start, 'end' => $event_end ) );
						$form_validated = $form_id ? bookacti_is_picked_event_available_on_form( $picked_event, (array) $event, $form_id ) : array( 'status' => 'failed', 'messages' => array() );
						if( $form_validated[ 'status' ] !== 'success' ) {
							reset( $form_validated[ 'messages' ] );
							$error = key( $form_validated[ 'messages' ] );
							$return_array[ 'status' ]  = 'failed';
							$return_array[ 'error' ]   = $error ? $error : 'reschedule_to_different_form';
							$return_array[ 'message' ] = ! empty( $form_validated[ 'messages' ][ $error ][ 0 ] ) ? $form_validated[ 'messages' ][ $error ][ 0 ] : esc_html__( 'The desired event is not in the same booking form as the booked event.', 'booking-activities' );
						}
					} else if( strpos( $reschedule_scope, 'all_' ) !== false ) {
						$form                 = $form_id && ! $is_admin ? bookacti_get_form_data( $form_id ) : array();
						$form_author_id       = ! empty( $form[ 'user_id' ] ) ? intval( $form[ 'user_id' ] ) : 0;
						$allowed_template_ids = bookacti_ids_to_array( array_keys( bookacti_fetch_templates( array(), $form_author_id ) ) );
						if( ! in_array( $event_template_id, $allowed_template_ids, true ) ) {
							$return_array[ 'status' ]  = 'failed';
							$return_array[ 'error' ]   = 'reschedule_to_not_allowed_calendar';
							$return_array[ 'message' ] = esc_html__( 'The desired event does not belong to the same calendar as the booked event.', 'booking-activities' ) . ' ' 
							                           . esc_html__( 'You cannot replace the booked event with an event from this calendar.', 'booking-activities' );
						}
					}
				}
			}
		}
	}

	return apply_filters( 'bookacti_booking_can_be_rescheduled_to', $return_array, $booking, $event_id, $event_start, $event_end, $is_frontend );
}


/**
 * Check if a booking can be refunded
 * @version 1.16.0
 * @param object|int $booking
 * @param boolean $is_frontend
 * @param string|false $refund_action
 * @return boolean
 */
function bookacti_booking_can_be_refunded( $booking, $is_frontend = true, $refund_action = false ) {
	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking, true ); }
	
	$true = true;
	if( ! $booking ) { $true = false; }
	else {
		$refund_actions = bookacti_get_selected_bookings_refund_actions( array( 'bookings' => array( $booking->id => $booking ), 'booking_groups' => array(), 'groups_bookings' => array() ), $is_frontend );
		
		$user_id = isset( $booking->user_id ) && is_numeric( $booking->user_id ) ? intval( $booking->user_id ) : 0;
		$is_own = apply_filters( 'bookacti_allow_others_booking_changes', $user_id && $user_id === get_current_user_id(), $booking, 'refund' );
		
		// Disallow refund in those cases:
		// -> If the booking is already marked as refunded, 
		if( $booking->state === 'refunded' 
		// -> If the booking is part of a group
		||  ! empty( $booking->group_id )
		// -> If there are no refund action available
		|| ( ! $refund_actions && $is_frontend )
		// -> If the refund action is set but doesn't exist in available refund actions list
		|| ( $refund_action && ! array_key_exists( $refund_action, $refund_actions ) ) 
		// -> If the user is not an admin, the booking status has to be 'cancelled' in the first place
		|| ( ( ! current_user_can( 'bookacti_edit_bookings' ) || $is_frontend ) && ! ( $is_own && $booking->state === 'cancelled' ) ) ) { 

			$true = false;
		}
	}
	
	return apply_filters( 'bookacti_booking_can_be_refunded', $true, $booking, $is_frontend );
}


/**
 * Check if a booking status can be changed to another
 * @since 1.16.0 (was bookacti_booking_state_can_be_changed_to)
 * @param object|int $booking
 * @param string $new_status
 * @param boolean $is_frontend
 * @return boolean
 */
function bookacti_booking_status_can_be_changed_to( $booking, $new_status, $is_frontend = true ) {
	$true = true;
	if( ! current_user_can( 'bookacti_edit_bookings' ) || $is_frontend ) {
		switch ( $new_status ) {
			case 'delivered':
				$true = false;
				break;
			case 'cancelled':
				$true = bookacti_booking_can_be_cancelled( $booking, $is_frontend );
				break;
			case 'refund_requested':
			case 'refunded':
				$true = bookacti_booking_can_be_refunded( $booking, $is_frontend );
				break;
		}
	}
	return apply_filters( 'bookacti_booking_status_can_be_changed_to', $true, $booking, $new_status, $is_frontend );
}


/**
 * Check if a booking quantity can be changed
 * @since 1.9.0
 * @version 1.14.0
 * @param object $booking
 * @param int $new_quantity 
 * @return boolean
 */
function bookacti_booking_quantity_can_be_changed( $booking, $new_quantity = 'current' ) {
	if( $new_quantity === 'current' ) { $new_quantity = $booking->quantity; }
	
	$events        = array( array( 'group_id' => 0, 'group_date' => '', 'events' => array( array( 'group_id' => 0, 'group_date' => '', 'id' => $booking->event_id, 'start' => $booking->event_start, 'end' => $booking->event_end ) ) ) );
	$picked_events = bookacti_get_picked_events_availability( $events );
	$picked_event  = $picked_events[ 0 ];
	$activity_data = bookacti_get_metadata( 'activity', $booking->activity_id );
	
	$is_active       = apply_filters( 'bookacti_booking_quantity_check_is_active', $booking->active, $booking, $new_quantity );
	$min_quantity    = isset( $activity_data[ 'min_bookings_per_user' ] ) ? intval( $activity_data[ 'min_bookings_per_user' ] ) : 0;
	$max_quantity    = isset( $activity_data[ 'max_bookings_per_user' ] ) ? intval( $activity_data[ 'max_bookings_per_user' ] ) : 0;
	$max_users       = isset( $activity_data[ 'max_users_per_event' ] ) ? intval( $activity_data[ 'max_users_per_event' ] ) : 0;
	$user_has_booked = ! empty( $picked_event[ 'args' ][ 'bookings_nb_per_user' ][ $booking->user_id ] );
	
	$params = apply_filters( 'bookacti_booking_quantity_can_be_changed_params', array(
		'delta_quantity'          => $is_active ? $new_quantity - $booking->quantity : $new_quantity,
		'user_has_booked'         => $user_has_booked,
		'quantity_already_booked' => $user_has_booked ? $picked_event[ 'args' ][ 'bookings_nb_per_user' ][ $booking->user_id ] : $booking->quantity,
		'number_of_users'         => count( $picked_event[ 'args' ][ 'bookings_nb_per_user' ] ),
		'availability'            => $picked_event[ 'args' ][ 'availability' ],
	), $booking, $new_quantity, $picked_event, $activity_data );
	
	// Make the tests
	$is_qty_inf_to_avail = $params[ 'delta_quantity' ] <= $params[ 'availability' ];
	$is_qty_sup_to_min   = $min_quantity === 0 || ( $params[ 'quantity_already_booked' ] + $params[ 'delta_quantity' ] ) >= $min_quantity;
	$is_qty_inf_to_max   = $max_quantity === 0 || ( $params[ 'quantity_already_booked' ] + $params[ 'delta_quantity' ] ) <= $max_quantity;
	$is_users_inf_to_max = $max_users === 0 || $params[ 'user_has_booked' ] || $params[ 'number_of_users' ] < $max_users;
	
	// Build feedback variables
	$title = ! empty( $booking->event_title ) ? apply_filters( 'bookacti_translate_text', $booking->event_title ) : '';
	$dates = bookacti_get_formatted_event_dates( $booking->event_start, $booking->event_end, false );
	$qty_already_booked_with_other_bookings = $is_active ? $params[ 'quantity_already_booked' ] - $booking->quantity : $params[ 'quantity_already_booked' ];
	
	// Feedback the error code and message
	$response = array( 'status' => 'failed', 'messages' => array() );
	if( ! $is_qty_inf_to_avail ) {
		$response[ 'messages' ][ 'qty_sup_to_avail' ] = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $new_quantity, 'booking-activities' ) ), $new_quantity, $title . ' (' . $dates . ')' )
			/* translators: %1$s is the number of available places */
			. ' ' . sprintf( esc_html( _n( 'but only %1$s is available on this time slot.', 'but only %1$s are available on this time slot. ', $is_active ? $params[ 'availability' ] + $booking->quantity : $params[ 'availability' ], 'booking-activities' ) ), $is_active ? $params[ 'availability' ] + $booking->quantity : $params[ 'availability' ] )
			. ' ' . esc_html__( 'Please choose another event or decrease the quantity.', 'booking-activities' );
		$response[ 'availability' ] = $params[ 'availability' ];
	}
	if( ! $is_qty_sup_to_min ) {
		/* translators: %1$s is a variable number of bookings, %2$s is the event title. */
		$response[ 'messages' ][ 'qty_inf_to_min' ] = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $new_quantity, 'booking-activities' ) ), $new_quantity, $title . ' (' . $dates . ')' );
		if( $qty_already_booked_with_other_bookings ) {
			/* translators: %1$s and %2$s are variable numbers of bookings, always >= 1. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or increase the quantity.' */
			$response[ 'messages' ][ 'qty_inf_to_min' ] .= ' ' . sprintf( esc_html( _n( 'and you have already booked %1$s place, but the minimum number of reservations required per user is %2$s.', 'and you have already booked %1$s places, but the minimum number of reservations required per user is %2$s.', $qty_already_booked_with_other_bookings, 'booking-activities' ) ), $qty_already_booked_with_other_bookings, $min_quantity );
		} else {
			/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or increase the quantity.' */
			$response[ 'messages' ][ 'qty_inf_to_min' ] .= ' ' . sprintf( esc_html__( 'but the minimum number of reservations required per user is %1$s.', 'booking-activities' ), $min_quantity );
		}	
		/* translators: %1$s is a variable quantity. */
		$response[ 'messages' ][ 'qty_inf_to_min' ] .= $min_quantity - $qty_already_booked_with_other_bookings > 0 ? ' ' . sprintf( esc_html__( 'Please choose another event or increase the quantity to %1$s.', 'booking-activities' ), $min_quantity - $qty_already_booked_with_other_bookings ) : ' ' . esc_html__( 'Please choose another event.', 'booking-activities' );
	}
	if( ! $is_qty_inf_to_max ) {
		$response[ 'messages' ][ 'qty_sup_to_max' ] = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $new_quantity, 'booking-activities' ) ), $new_quantity, $title . ' (' . $dates . ')' );
		if( $qty_already_booked_with_other_bookings ) {
			/* translators: %1$s and %2$s are variable numbers of bookings, always >= 1. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or decrease the quantity.' */
			$response[ 'messages' ][ 'qty_sup_to_max' ] .= ' ' . sprintf( esc_html( _n( 'but you have already booked %1$s place and the maximum number of reservations allowed per user is %2$s.', 'but you have already booked %1$s places and the maximum number of reservations allowed per user is %2$s.', $qty_already_booked_with_other_bookings, 'booking-activities' ) ), $qty_already_booked_with_other_bookings, $max_quantity );
		} else {
			/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or decrease the quantity.' */
			$response[ 'messages' ][ 'qty_sup_to_max' ] .= ' ' . sprintf( esc_html__( 'but the maximum number of reservations allowed per user is %1$s.', 'booking-activities' ), $max_quantity );
		}
		/* translators: %1$s is a variable quantity. */
		$response[ 'messages' ][ 'qty_sup_to_max' ] .= $max_quantity - $qty_already_booked_with_other_bookings > 0  ? ' ' . sprintf( esc_html__( 'Please choose another event or decrease the quantity to %1$s.', 'booking-activities' ), $max_quantity - $qty_already_booked_with_other_bookings ) : ' ' . esc_html__( 'Please choose another event.', 'booking-activities' );
	}
	if( ! $is_users_inf_to_max ) {
		/* translators: %s = The event title and dates. E.g.: The event "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)" has reached the maximum number of users allowed. */
		$response[ 'messages' ][ 'users_sup_to_max' ] = sprintf( esc_html__( 'The event "%s" has reached the maximum number of users allowed. Bookings from other users are no longer accepted. Please choose another event.', 'booking-activities' ), $title . ' (' . $dates . ')' );
	}
	if( empty( $response[ 'messages' ] ) ) { $response[ 'status' ] = 'success'; }
	
	return apply_filters( 'bookacti_booking_quantity_can_be_changed', $response, $booking, $new_quantity, $picked_event, $activity_data, $params );
}


/**
 * Check if a booking user can be changed
 * @since 1.9.0
 * @version 1.14.0
 * @param object|int $booking
 * @param string $new_user_id Default to current user
 * @return array
 */
function bookacti_booking_user_can_be_changed( $booking, $new_user_id = '' ) {
	$response = array( 'status' => 'failed', 'messages' => array() );
	
	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking, true ); }
	if( ! $booking ) { return $response; }
	
	if( $new_user_id === 'current' ) { $new_user_id = $booking->user_id; }
	
	// Plugins may totally bypass roles check
	$is_allowed = apply_filters( 'bookacti_bypass_roles_check', false );
	
	// Check if the user is parti of the allowed roles
	if( ! $is_allowed ) {
		$allowed_roles = bookacti_get_metadata( 'activity', $booking->activity_id, 'allowed_role', true );
		
		// If no roles are set, everybody is allowed
		if( ! $allowed_roles ) { $is_allowed = true; }
		else if( is_array( $allowed_roles ) ) {
			// If the "all" role is selected, everybody is allowed
			if( in_array( 'all', $allowed_roles, true ) ) { $is_allowed = true; }
			
			// Check if the user has one of the allowed roles
			else {
				$user = is_numeric( $new_user_id ) ? get_user_by( 'id', $new_user_id ) : ( ! $new_user_id ? wp_get_current_user() : null );
				if( $user && ! empty( $user->roles ) ) { $is_allowed = array_intersect( $user->roles, $allowed_roles ); }
			}
		}
	}
	if( $is_allowed ) { $response[ 'status' ] = 'success'; }
	
	// Feedback the error
	else { 
		$title = ! empty( $booking->event_title ) ? apply_filters( 'bookacti_translate_text', $booking->event_title ) : '';
		if( $new_user_id == get_current_user_id() ) {
			/* translators: %1$s is the event title. */
			$response[ 'messages' ][] = sprintf( esc_html__( 'The event "%1$s" is not available in your user category.', 'booking-activities' ), $title );
		} else {
			/* translators: %1$s is the event title. */
			$message = sprintf( esc_html__( 'The event "%1$s" is restricted to certain categories of users.', 'booking-activities' ), $title );
			if( ! is_user_logged_in() ) { $message .= ' ' . esc_html__( 'Please log in.', 'booking-activities' ); }
			$response[ 'messages' ][] = $message;
		}
	}
	
	return apply_filters( 'bookacti_booking_user_can_be_changed', $response, $booking, $new_user_id );
}




// BOOKING GROUPS

/**
 * Get a booking group by its id
 * @since 1.1.0
 * @version 1.12.0
 * @param int $booking_group_id
 * @param boolean $fetch_meta
 * @return object|false
 */
function bookacti_get_booking_group_by_id( $booking_group_id, $fetch_meta = false ) {
	$filters = bookacti_format_booking_filters( array( 'in__booking_group_id' => $booking_group_id, 'fetch_meta' => $fetch_meta ) );
	$booking_groups = bookacti_get_booking_groups( $filters );
	return ! empty( $booking_groups[ $booking_group_id ] ) ? $booking_groups[ $booking_group_id ] : false;
}


/**
 * Get bookings of a group by id
 * @since 1.12.0
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @param boolean $fetch_meta
 * @return array
 */
function bookacti_get_booking_group_bookings_by_id( $booking_group_id, $fetch_meta = false ) {
	$filters = bookacti_format_booking_filters( array( 'in__booking_group_id' => $booking_group_id, 'fetch_meta' => $fetch_meta ) );
	$bookings = bookacti_get_bookings( $filters );
	return $bookings;
}


/**
 * Check if user is allowed to manage a booking group
 * @since 1.1.0
 * @version 1.16.0
 * @param array|int $bookings
 * @param bool $allow_self Return true if the booking belongs to the user.
 * @param array $capabilities The user must have one of these capabilities. Default to array( 'bookacti_edit_bookings' ).
 * @param int $user_id
 * @return boolean
 */
function bookacti_user_can_manage_booking_group( $bookings, $allow_self = true, $capabilities = array(), $user_id = 0 ) {
	// Get grouped bookings
	if( is_numeric( $bookings ) ) {
		$bookings = bookacti_get_booking_group_bookings_by_id( $bookings, true );
	}
	if( ! is_array( $bookings ) ) {
		$bookings = array();
	}
	
	$user_can_manage_booking_group = ! empty( $bookings );
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking, $allow_self, $capabilities, $user_id );
		if( ! $is_allowed ) {
			$user_can_manage_booking_group = false;
			break; // If one of the booking of the group is not allowed, return false immediatly
		}
	}

	return apply_filters( 'bookacti_user_can_manage_booking_group', $user_can_manage_booking_group, $bookings, $allow_self, $capabilities, $user_id );
}


/**
 * Check if a booking group can be cancelled
 * @since 1.1.0
 * @version 1.16.0
 * @param array|int $booking_group
 * @param array $group_bookings
 * @param boolean $is_frontend
 * @return boolean
 */
function bookacti_booking_group_can_be_cancelled( $booking_group, $group_bookings, $is_frontend = true ) {
	if( is_numeric( $booking_group ) ) {
		$booking_group = bookacti_get_booking_group_by_id( $booking_group, true );
		if( $booking_group && empty( $group_bookings ) ) { 
			$group_bookings = bookacti_get_booking_group_bookings_by_id( $booking_group->id, true );
		}
	}
	
	$true = true;
	if( ! $booking_group || ! $group_bookings ) { $true = false; }
	else {
		if( ! current_user_can( 'bookacti_edit_bookings' ) || $is_frontend ) {
			if( empty( $booking_group->active ) ) { $true = false; }
			else {
				foreach( $group_bookings as $booking ) {
					$is_allowed = bookacti_booking_can_be_cancelled( $booking, $is_frontend, true );
					if( ! $is_allowed ) {
						$true = false;
						break; // If one of the booking of the group is not allowed, return false immediatly
					}
				}
			}
		}
	}

	return apply_filters( 'bookacti_booking_group_can_be_cancelled', $true, $booking_group, $group_bookings, $is_frontend );
}


/**
 * Check if a booking group can be refunded
 * @since 1.1.0
 * @version 1.16.0
 * @param array $booking_group
 * @param array $group_bookings
 * @param boolean $is_frontend
 * @param string $refund_action
 * @return boolean
 */
function bookacti_booking_group_can_be_refunded( $booking_group, $group_bookings = array(), $is_frontend = true, $refund_action = '' ) {
	if( is_numeric( $booking_group ) ) {
		$booking_group = bookacti_get_booking_group_by_id( $booking_group, true );
		if( $booking_group && empty( $group_bookings ) ) { 
			$group_bookings = bookacti_get_booking_group_bookings_by_id( $booking_group->id, true );
		}
	}
	
	$true = true;
	if( ! $booking_group || ! $group_bookings ) { $true = false; }
	else {
		$refund_actions = bookacti_get_selected_bookings_refund_actions( array( 'bookings' => array(), 'booking_groups' => array( $booking_group->id => $booking_group ), 'groups_bookings' => array( $booking_group->id => $group_bookings ) ), $is_frontend );
		
		$user_id = isset( $booking_group->user_id ) && is_numeric( $booking_group->user_id ) ? intval( $booking_group->user_id ) : 0;
		$is_own = apply_filters( 'bookacti_allow_others_booking_changes', $user_id && $user_id === get_current_user_id(), $booking_group, 'refund' );
		
		// Disallow refund in those cases:
		// -> If the booking group is already marked as refunded, 
		if( $booking_group->state === 'refunded' 
		// -> If there are no refund action available
		|| ( ! $refund_actions && $is_frontend )
		// -> If the refund action is set but doesn't exist in available refund actions list
		|| ( $refund_action && ! array_key_exists( $refund_action, $refund_actions ) ) 
		// -> If the user is not an admin, the booking group state has to be 'cancelled' in the first place
		|| ( ( ! current_user_can( 'bookacti_edit_bookings' ) || $is_frontend ) && ! ( $is_own && $booking_group->state === 'cancelled' ) ) ) { 
			$true = false;
		}
	}
	
	return apply_filters( 'bookacti_booking_group_can_be_refunded', $true, $booking_group, $group_bookings, $is_frontend, $refund_action );
}


/**
 * Check if a booking group state can be changed to another
 * @since 1.16.0 (was bookacti_booking_group_state_can_be_changed_to)
 * @param array|int $booking_group
 * @param array $group_bookings
 * @param string $new_status
 * @param boolean $is_frontend
 * @return boolean
 */
function bookacti_booking_group_status_can_be_changed_to( $booking_group, $group_bookings, $new_status, $is_frontend = true ) {
	$true = true;
	if( ! current_user_can( 'bookacti_edit_bookings' ) || $is_frontend ) {
		if( is_numeric( $booking_group ) ) {
			$booking_group = bookacti_get_booking_group_by_id( $booking_group, true );
			if( $booking_group && empty( $group_bookings ) ) { 
				$group_bookings = bookacti_get_booking_group_bookings_by_id( $booking_group->id, true );
			}
		}

		if( ! $booking_group || ! $group_bookings ) { $true = false; }
		else {
			switch ( $new_status ) {
				case 'delivered':
					$true = false;
					break;
				case 'cancelled':
					$true = bookacti_booking_group_can_be_cancelled( $booking_group, $group_bookings, $is_frontend );
					break;
				case 'refund_requested':
				case 'refunded':
					$true = bookacti_booking_group_can_be_refunded( $booking_group, $group_bookings, $is_frontend );
					break;
			}
		}
	}
	
	return apply_filters( 'bookacti_booking_group_status_can_be_changed', $true, $booking_group, $group_bookings, $new_status, $is_frontend );
}


/**
 * Check if a booking group quantity can be changed
 * @since 1.9.0
 * @version 1.15.7
 * @param array $bookings
 * @param int $new_quantity
 * @param string $context
 * @return boolean
 */
function bookacti_booking_group_quantity_can_be_changed( $bookings, $new_quantity = 'current' ) {
	// Get booking group data from its bookings
	$title = ''; $group_id = 0; $group_start = ''; $group_end = ''; $quantity = 0; $category_id = 0; $user_id = 0; $is_active = 0; $events = array();
	foreach( $bookings as $booking ) {
		$group_start_dt = new DateTime( $group_start );
		$group_end_dt = new DateTime( $group_end );
		$event_start_dt = new DateTime( $booking->event_start );
		$event_end_dt = new DateTime( $booking->event_end );
		$events[] = array( 'group_id' => 1, 'group_date' => '1992-12-26', 'id' => $booking->event_id, 'start' => $booking->event_start, 'end' => $booking->event_end );
		if( ! $group_start || $event_start_dt < $group_start_dt ) { $group_start = $booking->event_start; }
		if( ! $group_end || $event_end_dt < $group_end_dt )       { $group_end   = $booking->event_end; }
		if( ! $group_id && ! empty( $booking->group_id ) )        { $group_id    = $booking->group_id; }
		if( ! $title && ! empty( $booking->group_title ) )        { $title       = ! empty( $booking->group_title ) ? apply_filters( 'bookacti_translate_text', $booking->group_title ) : ''; }
		if( ! $category_id && ! empty( $booking->category_id ) )  { $category_id = $booking->category_id; }
		if( ! $user_id && ! empty( $booking->group_user_id ) )    { $user_id     = $booking->group_user_id; }
		if( ! $quantity || $booking->quantity > $quantity )       { $quantity    = $booking->quantity; }
		if( ! $is_active )                                        { $is_active   = apply_filters( 'bookacti_booking_quantity_check_is_active', $booking->active, $booking, $new_quantity ); }
	}
	if( $new_quantity === 'current' ) { $new_quantity = $quantity; }
	if( ! $title ) { $title = sprintf( esc_html__( 'Booking group #%d', 'booking-activities' ), $group_id ); }
	$dates = bookacti_get_formatted_event_dates( $group_start, $group_end, false );
	
	$picked_events = bookacti_get_picked_events_availability( array( array( 'group_id' => 1, 'group_date' => '1992-12-26', 'events' => $events ) ) );
	$picked_event  = $picked_events[ 0 ];
	$category_data = bookacti_get_metadata( 'group_category', $category_id );

	$delta_quantity  = $is_active ? $new_quantity - $quantity : $new_quantity;
	$min_quantity    = isset( $category_data[ 'min_bookings_per_user' ] ) ? intval( $category_data[ 'min_bookings_per_user' ] ) : 0;
	$max_quantity    = isset( $category_data[ 'max_bookings_per_user' ] ) ? intval( $category_data[ 'max_bookings_per_user' ] ) : 0;
	$max_users       = isset( $category_data[ 'max_users_per_event' ] ) ? intval( $category_data[ 'max_users_per_event' ] ) : 0;
	$user_has_booked = ! empty( $picked_event[ 'args' ][ 'bookings_nb_per_user' ][ $user_id ] );
	
	$params = apply_filters( 'bookacti_booking_group_quantity_can_be_changed_params', array(
		'delta_quantity'          => $is_active ? $new_quantity - $quantity : $new_quantity,
		'user_has_booked'         => $user_has_booked,
		'quantity_already_booked' => $user_has_booked ? $picked_event[ 'args' ][ 'bookings_nb_per_user' ][ $user_id ] : $quantity,
		'number_of_users'         => count( $picked_event[ 'args' ][ 'bookings_nb_per_user' ] ),
		'availability'            => $picked_event[ 'args' ][ 'availability' ],
	), $bookings, $new_quantity, $picked_event, $category_data );
	
	$qty_already_booked_with_other_bookings = $is_active ? $params[ 'quantity_already_booked' ] - $quantity : $params[ 'quantity_already_booked' ];
	
	// Make the tests
	$is_qty_inf_to_avail = $params[ 'delta_quantity' ] <= $params[ 'availability' ];
	$is_qty_sup_to_min   = $min_quantity === 0 || ( $params[ 'quantity_already_booked' ] + $params[ 'delta_quantity' ] ) >= $min_quantity;
	$is_qty_inf_to_max   = $max_quantity === 0 || ( $params[ 'quantity_already_booked' ] + $params[ 'delta_quantity' ] ) <= $max_quantity;
	$is_users_inf_to_max = $max_users === 0 || $params[ 'user_has_booked' ] || $params[ 'number_of_users' ] < $max_users;
	
	// Feedback the error code and message
	$response = array( 'status' => 'failed', 'messages' => array() );
	if( ! $is_qty_inf_to_avail ) {
		$response[ 'messages' ][ 'qty_sup_to_avail' ] = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $new_quantity, 'booking-activities' ) ), $new_quantity, $title . ' (' . $dates . ')' )
			. ' ' . sprintf( esc_html( _n( 'but only %1$s is available on this time slot.', 'but only %1$s are available on this time slot. ', $is_active ? $params[ 'availability' ] + $quantity : $params[ 'availability' ], 'booking-activities' ) ), $is_active ? $params[ 'availability' ] + $quantity : $params[ 'availability' ] )
			. ' ' . esc_html__( 'Please choose another event or decrease the quantity.', 'booking-activities' );
		$response[ 'availability' ] = $params[ 'availability' ];
	}
	if( ! $is_qty_sup_to_min ) {
		/* translators: %1$s is a variable number of bookings, %2$s is the event title. */
		$response[ 'messages' ][ 'qty_inf_to_min' ] = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $new_quantity, 'booking-activities' ) ), $new_quantity, $title . ' (' . $dates . ')' );
		if( $qty_already_booked_with_other_bookings ) {
			/* translators: %1$s and %2$s are variable numbers of bookings, always >= 1. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or increase the quantity.' */
			$response[ 'messages' ][ 'qty_inf_to_min' ] .= ' ' . sprintf( esc_html( _n( 'and you have already booked %1$s place, but the minimum number of reservations required per user is %2$s.', 'and you have already booked %1$s places, but the minimum number of reservations required per user is %2$s.', $qty_already_booked_with_other_bookings, 'booking-activities' ) ), $qty_already_booked_with_other_bookings, $min_quantity );
		} else {
			/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or increase the quantity.' */
			$response[ 'messages' ][ 'qty_inf_to_min' ] .= ' ' . sprintf( esc_html__( 'but the minimum number of reservations required per user is %1$s.', 'booking-activities' ), $min_quantity );
		}
		/* translators: %1$s is a variable quantity. */
		$response[ 'messages' ][ 'qty_inf_to_min' ] .= $min_quantity - $qty_already_booked_with_other_bookings > 0 ? ' ' . sprintf( esc_html__( 'Please choose another event or increase the quantity to %1$s.', 'booking-activities' ), $min_quantity - $qty_already_booked_with_other_bookings ) : ' ' . esc_html__( 'Please choose another event.', 'booking-activities' );
	}
	if( ! $is_qty_inf_to_max ) {
		$response[ 'messages' ][ 'qty_sup_to_max' ] = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $new_quantity, 'booking-activities' ) ), $new_quantity, $title . ' (' . $dates . ')' );
		if( $qty_already_booked_with_other_bookings ) {
			/* translators: %1$s and %2$s are variable numbers of bookings, always >= 1. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or decrease the quantity.' */
			$response[ 'messages' ][ 'qty_sup_to_max' ] .= ' ' . sprintf( esc_html( _n( 'but you have already booked %1$s place and the maximum number of reservations allowed per user is %2$s.', 'but you have already booked %1$s places and the maximum number of reservations allowed per user is %2$s.', $qty_already_booked_with_other_bookings, 'booking-activities' ) ), $qty_already_booked_with_other_bookings, $max_quantity );
		} else {
			/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or decrease the quantity.' */
			$response[ 'messages' ][ 'qty_sup_to_max' ] .= ' ' . sprintf( esc_html__( 'but the maximum number of reservations allowed per user is %1$s.', 'booking-activities' ), $max_quantity );
		}
		/* translators: %1$s is a variable quantity. */
		$response[ 'messages' ][ 'qty_sup_to_max' ] .= $max_quantity - $qty_already_booked_with_other_bookings > 0  ? ' ' . sprintf( esc_html__( 'Please choose another event or decrease the quantity to %1$s.', 'booking-activities' ), $max_quantity - $qty_already_booked_with_other_bookings ) : ' ' . esc_html__( 'Please choose another event.', 'booking-activities' );
	}
	if( ! $is_users_inf_to_max ) {
		/* translators: %s = The event title and dates. E.g.: The event "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)" has reached the maximum number of users allowed. */
		$response[ 'messages' ][ 'users_sup_to_max' ] = sprintf( esc_html__( 'The event "%s" has reached the maximum number of users allowed. Bookings from other users are no longer accepted. Please choose another event.', 'booking-activities' ), $title . ' (' . $dates . ')' );
	}
	if( empty( $response[ 'messages' ] ) ) { $response[ 'status' ] = 'success'; }
	
	return apply_filters( 'bookacti_booking_group_quantity_can_be_changed', $response, $bookings, $new_quantity, $picked_event, $category_data, $params );
}


/**
 * Check if a booking group user can be changed
 * @since 1.9.0
 * @version 1.14.0
 * @param array|int $bookings
 * @param string $new_user_id Default to current user
 * @return array
 */
function bookacti_booking_group_user_can_be_changed( $bookings, $new_user_id = '' ) {
	$response = array( 'status' => 'failed', 'messages' => array() );
	
	// Get grouped bookings
	if( is_numeric( $bookings ) ) { $bookings = bookacti_get_booking_group_bookings_by_id( $bookings, true ); }
	if( ! $bookings ) { return $response; }
	
	// Get booking group data from its bookings
	$title = ''; $group_id = 0; $group_start = ''; $group_end = ''; $category_id = 0; $user_id = 0;
	foreach( $bookings as $booking ) {
		$group_start_dt = new DateTime( $group_start );
		$group_end_dt   = new DateTime( $group_end );
		$event_start_dt = new DateTime( $booking->event_start );
		$event_end_dt   = new DateTime( $booking->event_end );
		if( ! $group_start || $event_start_dt < $group_start_dt ) { $group_start = $booking->event_start; }
		if( ! $group_end   || $event_end_dt < $group_end_dt )     { $group_end   = $booking->event_end; }
		if( ! $group_id    && ! empty( $booking->group_id ) )     { $group_id    = $booking->group_id; }
		if( ! $title       && ! empty( $booking->group_title ) )  { $title       = ! empty( $booking->group_title ) ? apply_filters( 'bookacti_translate_text', $booking->group_title ) : ''; }
		if( ! $category_id && ! empty( $booking->category_id ) )  { $category_id = $booking->category_id; }
		if( ! $user_id     && ! empty( $booking->user_id ) )      { $user_id     = $booking->user_id; }
	}
	
	if( $new_user_id === 'current' ) { $new_user_id = $user_id; }
	if( ! $title ) { $title = sprintf( esc_html__( 'Booking group #%d', 'booking-activities' ), $group_id ); }
	
	$dates = bookacti_get_formatted_event_dates( $group_start, $group_end, false );
	
	// Plugins may totally bypass roles check
	$is_allowed = apply_filters( 'bookacti_bypass_roles_check', false ) || ! $category_id;
	
	// Check if the user is parti of the allowed roles
	if( ! $is_allowed ) {
		$allowed_roles = bookacti_get_metadata( 'group_category', $category_id, 'allowed_role', true );
		
		// If no roles are set, everybody is allowed
		if( ! $allowed_roles ) { $is_allowed = true; }
		else if( is_array( $allowed_roles ) ) {
			// If the "all" role is selected, everybody is allowed
			if( in_array( 'all', $allowed_roles, true ) ) { $is_allowed = true; }
			
			// Check if the user has one of the allowed roles
			else {
				$user = is_numeric( $new_user_id ) ? get_user_by( 'id', $new_user_id ) : ( ! $new_user_id ? wp_get_current_user() : null );
				if( $user && ! empty( $user->roles ) ) { $is_allowed = array_intersect( $user->roles, $allowed_roles ); }
			}
		}
	}
	if( $is_allowed ) { $response[ 'status' ] = 'success'; }
	
	// Feedback the error
	else { 
		if( $new_user_id == get_current_user_id() ) {
			/* translators: %1$s is the event title. */
			$response[ 'messages' ][] = sprintf( esc_html__( 'The group of events "%1$s" is not available in your user category.', 'booking-activities' ), $title . ' (' . $dates . ')' );
		} else {
			/* translators: %1$s is the event title. */
			$message = sprintf( esc_html__( 'The group of events "%1$s" is restricted to certain categories of users.', 'booking-activities' ), $title . ' (' . $dates . ')' );
			if( ! is_user_logged_in() ) { $message .= ' ' . esc_html__( 'Please log in.', 'booking-activities' ); }
			$response[ 'messages' ][] = $message;
		}
	} 
	
	return apply_filters( 'bookacti_booking_group_user_can_be_changed', $response, $bookings, $new_user_id );
}




// BOOKING ACTIONS

// SINGLE BOOKING
/**
 * Get booking actions array
 * @since 1.6.0 (replace bookacti_get_booking_actions_array)
 * @version 1.16.0
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @return array
 */
function bookacti_get_booking_actions( $admin_or_front = 'both' ) {
	$actions = apply_filters( 'bookacti_booking_actions', array(
		'edit_status' => array( 
			'class'          => 'bookacti-change-booking-status',
			'label'          => esc_html__( 'Change booking status',  'booking-activities' ),
			'description'    => esc_html__( 'Change the booking status to any available state.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'admin' ),
		'edit_quantity' => array( 
			'class'          => 'bookacti-change-booking-quantity',
			'label'          => esc_html__( 'Change booking quantity',  'booking-activities' ),
			'description'    => esc_html__( 'Change the quantity to any number.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'admin' ),
		'cancel' => array( 
			'class'          => 'bookacti-cancel-booking',
			'label'          => bookacti_get_message( 'cancel_booking_open_dialog_button' ),
			'description'    => esc_html__( 'Cancel the booking.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'front' ),
		'reschedule' => array( 
			'class'          => 'bookacti-reschedule-booking',
			'label'          => bookacti_get_message( 'reschedule_dialog_button' ),
			'description'    => esc_html__( 'Change the booking dates to any other available time slot for this event.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'both' ),
		'refund' => array( 
			'class'          => 'bookacti-refund-booking',
			'label'          => $admin_or_front === 'both' || $admin_or_front === 'admin' ? esc_html_x( 'Refund', 'Button label to trigger the refund action', 'booking-activities' ) : bookacti_get_message( 'refund_dialog_button' ),
			'description'    => esc_html__( 'Refund the booking with one of the available refund method.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'both' ),
		'send_notification' => array( 
			'class'          => 'bookacti-send-booking-notification',
			'label'          => esc_html__( 'Send notification', 'booking-activities' ),
			'description'    => esc_html__( 'Send a notification.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'admin' ),
		'delete' => array( 
			'class'          => 'bookacti-delete-booking bookacti-delete-button',
			'label'          => esc_html__( 'Delete', 'booking-activities' ),
			'description'    => esc_html__( 'Delete permanently the booking.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'admin' )
	), $admin_or_front );

	$possible_actions = array();
	foreach( $actions as $action_id => $action ){
		if( $admin_or_front === 'both' || $action[ 'admin_or_front' ] === 'both' || $admin_or_front === $action[ 'admin_or_front' ] ) {
			$possible_actions[ $action_id ] = $action;
		}
	}

	return $possible_actions;
}


/**
 * Get booking actions according to booking id
 * @since 1.6.0 (replace bookacti_get_booking_actions_array)
 * @version 1.16.0
 * @param object|int $booking
 * @param string $admin_or_front Can be "both", "admin", "front". Default "both".
 * @return array
 */
function bookacti_get_booking_actions_by_booking( $booking, $admin_or_front = 'both' ) {
	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking, true ); }
	if( ! $booking ) { return array(); }

	$actions     = bookacti_get_booking_actions( $admin_or_front );
	$is_frontend = $admin_or_front !== 'admin';
	
	if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
		if( isset( $actions[ 'edit_status' ] ) )      { unset( $actions[ 'edit_status' ] ); }
		if( isset( $actions[ 'edit_quantity' ] ) )   { unset( $actions[ 'edit_quantity' ] ); }
		if( isset( $actions[ 'send_notification' ] ) ) { unset( $actions[ 'send_notification' ] ); }
	}
	if( isset( $actions[ 'cancel' ] ) && ! bookacti_booking_can_be_cancelled( $booking, $is_frontend ) ) {
		unset( $actions[ 'cancel' ] );
	}
	if( isset( $actions[ 'reschedule' ] ) && ! bookacti_booking_can_be_rescheduled( $booking, $is_frontend ) ) {
		unset( $actions[ 'reschedule' ] );
	}
	if( isset( $actions[ 'refund' ] ) && ! bookacti_booking_can_be_refunded( $booking, $is_frontend ) ) {
		unset( $actions[ 'refund' ] );
	}
	if( isset( $actions[ 'delete' ] ) && ! current_user_can( 'bookacti_delete_bookings' ) ) {
		unset( $actions[ 'delete' ] );
	}
	
	return apply_filters( 'bookacti_booking_actions_by_booking', $actions, $booking, $admin_or_front );
}


/**
 * Get booking actions html
 * @version 1.16.24
 * @param object|int $booking
 * @param string $admin_or_front Can be "both", "admin", "front". Default "both".
 * @param array $actions
 * @param boolean $return_array
 * @param boolean $with_container
 * @return string
 */
function bookacti_get_booking_actions_html( $booking, $admin_or_front = 'both', $actions = array(), $return_array = false, $with_container = false ) {
	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking, true ); }

	// Get booking actions
	if( ! $actions ) { $actions = bookacti_get_booking_actions_by_booking( $booking, $admin_or_front ); }
	
	$auth_key = ! empty( $_REQUEST[ 'user_auth_key' ] ) ? sanitize_text_field( $_REQUEST[ 'user_auth_key' ] ) : '';
	
	$actions_html_array = array();
	foreach( $actions as $action_id => $action ) {
			$action_html = '<a '
			             . 'href="' . esc_url( $action[ 'link' ] ) . '" '
			             . 'id="bookacti-booking-action-' . esc_attr( $action_id . '-' . $booking->id ) . '" '
			             . 'class="button ' . esc_attr( $action[ 'class' ] ) . ' bookacti-booking-action bookacti-tip" '
			             . 'aria-label="' . esc_attr( esc_html( $action[ 'label' ] ) ) . '" '
			             . 'data-action="' . esc_attr( $action_id ) . '" '
			             . 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" '
			             . 'data-user-auth-key="' . esc_attr( $auth_key ) . '" '
			             . 'data-booking-id="' . esc_attr( $booking->id ) . '" >';

			if( $admin_or_front === 'front' || $action[ 'admin_or_front' ] === 'front' ) { 
				$action_html .= esc_html( $action[ 'label' ] ); 
			}

			$action_html .= '</a>';
			$actions_html_array[ $action_id ] = $action_html;
	}
	
	// Return the array of html actions
	if( $return_array ) {
		return apply_filters( 'bookacti_booking_actions_html_array', $actions_html_array, $booking, $admin_or_front );
	}

	$actions_html = implode( ' | ', $actions_html_array );

	// Add a container
	if( $with_container ) {
		$actions_html = '<div class="bookacti-booking-actions" data-booking-id="' . esc_attr( $booking->id ) . '" >'
		              . $actions_html
		              . '</div>';
	}

	return apply_filters( 'bookacti_booking_actions_html', $actions_html, $booking, $admin_or_front );
}


/**
 * Get booking price details html
 * @version 1.7.10
 * @param array $prices_array
 * @param object|int $booking
 * @return string
 */
function bookacti_get_booking_price_details_html( $prices_array, $booking ) {
	$prices_html = '<div class="bookacti-booking-price-details" data-booking-id="' . esc_attr( $booking->id ) . '" >';
	foreach( $prices_array as $price_id => $price ) {
		if( ! is_array( $price ) )         { continue; }
		if( ! isset( $price[ 'value' ] ) ) { continue; }
		if( ! isset( $price[ 'title' ] ) )         { $price[ 'title' ] = $price_id; }
		if( ! isset( $price[ 'display_value' ] ) ) { $price[ 'display_value' ] = $price[ 'value' ]; }

		$prices_html .= '<div class="bookacti-price-details bookacti-price-details-' . esc_attr( $price_id ) . '" data-price="' . $price[ 'value' ] . '"><span class="bookacti-booking-price-details-title">' . $price[ 'title' ] . ': </span><span class="bookacti-booking-price-details-value">' . $price[ 'display_value' ] . '</span></div>';
	}
	$prices_html .= '</div>';

	return apply_filters( 'bookacti_booking_price_details_html', $prices_html, $prices_array, $booking );
}




// BOOKING GROUPS

/**
 * Get booking group actions array
 * @since 1.6.0 (replace bookacti_get_booking_group_actions_array)
 * @version 1.16.0
 * @param string $admin_or_front Can be "both", "admin", "front". Default "both".
 * @return array
 */
function bookacti_get_booking_group_actions( $admin_or_front = 'both' ) {
	$actions = apply_filters( 'bookacti_booking_group_actions', array(
		'edit_status' => array( 
			'class'          => 'bookacti-change-booking-status',
			'label'          => esc_html__( 'Change booking status',  'booking-activities' ),
			'description'    => esc_html__( 'Change the booking group state to any available state.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'admin' ),
		'edit_quantity' => array( 
			'class'          => 'bookacti-change-booking-quantity',
			'label'          => esc_html__( 'Change booking quantity',  'booking-activities' ),
			'description'    => esc_html__( 'Change the quantity to any number.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'admin' ),
		'display_grouped_bookings' => array( 
			'class'          => 'bookacti-show-booking-group-bookings',
			'label'          => esc_html__( 'Edit bookings',  'booking-activities' ),
			'description'    => esc_html__( 'Edit each booking of the group separately.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'admin' ),
		'cancel' => array( 
			'class'          => 'bookacti-cancel-booking',
			'label'          => bookacti_get_message( 'cancel_booking_open_dialog_button' ),
			'description'    => esc_html__( 'Cancel the booking group.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'front' ),
		'refund' => array( 
			'class'          => 'bookacti-refund-booking',
			'label'          => $admin_or_front === 'both' || $admin_or_front === 'admin' ? esc_html_x( 'Refund', 'Button label to trigger the refund action', 'booking-activities' ) : bookacti_get_message( 'refund_dialog_button' ),
			'description'    => esc_html__( 'Refund the booking group with one of the available refund method.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'both' ),
		'send_notification' => array( 
			'class'          => 'bookacti-send-booking-notification',
			'label'          => esc_html__( 'Send notification', 'booking-activities' ),
			'description'    => esc_html__( 'Send a notification.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'admin' ),
		'delete' => array( 
			'class'          => 'bookacti-delete-booking bookacti-delete-button',
			'label'          => esc_html__( 'Delete', 'booking-activities' ),
			'description'    => esc_html__( 'Delete permanently the booking group.', 'booking-activities' ),
			'link'           => '',
			'admin_or_front' => 'admin' )
	), $admin_or_front );

	$possible_actions = array();
	foreach( $actions as $action_id => $action ){
		if( $admin_or_front === 'both' || $action[ 'admin_or_front' ] === 'both' || $admin_or_front === $action[ 'admin_or_front' ] ) {
			$possible_actions[ $action_id ] = $action;
		}
	}

	return $possible_actions;
}


/**
 * Get booking actions according to booking id
 * @since 1.6.0 (replace bookacti_get_booking_actions_array)
 * @version 1.16.0
 * @param array|int $booking_group
 * @param array $group_bookings
 * @param string $admin_or_front Can be "both", "admin", "front". Default "both".
 * @return array
 */
function bookacti_get_booking_group_actions_by_booking_group( $booking_group, $group_bookings, $admin_or_front = 'both' ) {
	if( is_numeric( $booking_group ) ) {
		$booking_group = bookacti_get_booking_group_by_id( $booking_group, true );
		if( $booking_group && empty( $group_bookings ) ) { 
			$group_bookings = bookacti_get_booking_group_bookings_by_id( $booking_group->id, true );
		}
	}
	if( ! $booking_group || ! $group_bookings ) { return array(); }

	$actions     = bookacti_get_booking_group_actions( $admin_or_front );
	$is_frontend = $admin_or_front !== 'admin';
	
	if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
		if( isset( $actions[ 'edit_status' ] ) )      { unset( $actions[ 'edit_status' ] ); }
		if( isset( $actions[ 'edit_quantity' ] ) )   { unset( $actions[ 'edit_quantity' ] ); }
		if( isset( $actions[ 'send_notification' ] ) ) { unset( $actions[ 'send_notification' ] ); }
	}
	if( ! current_user_can( 'bookacti_manage_bookings' ) && ! current_user_can( 'bookacti_edit_bookings' ) ) {
		if( isset( $actions[ 'display_grouped_bookings' ] ) ) { unset( $actions[ 'display_grouped_bookings' ] ); }
	}
	if( isset( $actions[ 'cancel' ] ) && ! bookacti_booking_group_can_be_cancelled( $booking_group, $group_bookings, $is_frontend ) ) {
		unset( $actions[ 'cancel' ] );
	}
	if( isset( $actions[ 'refund' ] ) && ! bookacti_booking_group_can_be_refunded( $booking_group, $group_bookings, $is_frontend ) ) {
		unset( $actions[ 'refund' ] );
	}
	if( isset( $actions[ 'delete' ] ) && ! current_user_can( 'bookacti_delete_bookings' ) ) {
		unset( $actions[ 'delete' ] );
	}
	
	return apply_filters( 'bookacti_booking_group_actions_by_booking_group', $actions, $booking_group, $group_bookings, $admin_or_front );
}


/**
 * Get booking group actions html
 * @version 1.16.24
 * @param array|int $booking_group
 * @param array $group_bookings
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @param array $actions
 * @param boolean $return_array
 * @param boolean $with_container
 * @return string
 */
function bookacti_get_booking_group_actions_html( $booking_group, $group_bookings = array(), $admin_or_front = 'both', $actions = array(), $return_array = false, $with_container = false ) {
	if( is_numeric( $booking_group ) ) {
		$booking_group = bookacti_get_booking_group_by_id( $booking_group, true );
		if( $booking_group && empty( $group_bookings ) ) { 
			$group_bookings = bookacti_get_booking_group_bookings_by_id( $booking_group->id, true );
		}
	}
	if( ! $booking_group || ! $group_bookings ) { return $return_array ? array() : ''; }
	
	$booking_group_id = intval( $booking_group->id );
	
	if( ! $actions ) {
		$actions = bookacti_get_booking_group_actions_by_booking_group( $booking_group, $group_bookings, $admin_or_front );
	}
	
	$auth_key = ! empty( $_REQUEST[ 'user_auth_key' ] ) ? sanitize_text_field( $_REQUEST[ 'user_auth_key' ] ) : '';

	$actions_html_array = array();
	foreach( $actions as $action_id => $action ) {
		$action_html = '<a '
		             . 'href="' . esc_url( $action[ 'link' ] ) . '" '
		             . 'id="bookacti-booking-group-action-' . esc_attr( $action_id . '-' . $booking_group_id ) . '" '
		             . 'class="button ' . esc_attr( $action[ 'class' ] ) . ' bookacti-booking-group-action bookacti-tip" '
		             . 'aria-label="' . esc_attr( esc_html( $action[ 'label' ] ) ) . '" '
		             . 'data-action="' . esc_attr( $action_id ) . '" '
		             . 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" '
		             . 'data-user-auth-key="' . esc_attr( $auth_key ) . '" '
		             . 'data-booking-group-id="' . esc_attr( $booking_group_id ) . '" >';

		if( $admin_or_front === 'front' || $action[ 'admin_or_front' ] === 'front' ) { 
			$action_html .= esc_html( $action[ 'label' ] ); 
		}

		$action_html .= '</a>';
		$actions_html_array[] = $action_html;
	}

	// Return the array of html actions
	if( $return_array ) {
		return apply_filters( 'bookacti_booking_group_actions_html_array', $actions_html_array, $booking_group, $group_bookings, $admin_or_front, $actions );
	}

	$actions_html = implode( ' | ', $actions_html_array );

	// Add a container
	if( $with_container ) {
		$actions_html = '<div class="bookacti-booking-group-actions" data-booking-group-id="' . esc_attr( $booking_group_id ) . '" >' 
		              . $actions_html
		              . '</div>';
	}

	return apply_filters( 'bookacti_booking_group_actions_html', $actions_html, $booking_group, $group_bookings, $admin_or_front, $actions, $with_container );
}




// BOTH SINGLE AND GROUPS

/**
 * Booking data that can be exported
 * @since 1.6.0
 * @version 1.8.0
 * @return array
 */
function bookacti_get_bookings_export_columns() {
	return apply_filters( 'bookacti_bookings_export_columns_labels', array(
		'booking_id'            => esc_html__( 'Booking ID', 'booking-activities' ),
		'booking_type'          => esc_html__( 'Booking type (single or group)', 'booking-activities' ),
		'status'                => esc_html__( 'Booking status', 'booking-activities' ),
		'payment_status'        => esc_html__( 'Payment status', 'booking-activities' ),
		'quantity'              => esc_html__( 'Quantity', 'booking-activities' ),
		'creation_date'         => esc_html__( 'Creation date', 'booking-activities' ),
		'customer_id'           => esc_html__( 'Customer ID', 'booking-activities' ),
		'customer_display_name' => esc_html__( 'Customer display name', 'booking-activities' ),
		'customer_first_name'   => esc_html__( 'Customer first name', 'booking-activities' ),
		'customer_last_name'    => esc_html__( 'Customer last name', 'booking-activities' ),
		'customer_email'        => esc_html__( 'Customer email', 'booking-activities' ),
		'customer_phone'        => esc_html__( 'Customer phone', 'booking-activities' ),
		'customer_roles'        => esc_html__( 'Customer roles', 'booking-activities' ),
		'event_id'              => esc_html__( 'Event ID', 'booking-activities' ),
		'event_title'           => esc_html__( 'Event title', 'booking-activities' ),
		'start_date'            => esc_html__( 'Start date', 'booking-activities' ),
		'end_date'              => esc_html__( 'End date', 'booking-activities' ),
		'template_id'           => esc_html__( 'Calendar ID', 'booking-activities' ),
		'template_title'        => esc_html__( 'Calendar title', 'booking-activities' ),
		'activity_id'           => esc_html__( 'Activity / Category ID', 'booking-activities' ),
		'activity_title'        => esc_html__( 'Activity / Category title', 'booking-activities' ),
		'form_id'               => esc_html__( 'Form ID', 'booking-activities' ),
		'order_id'              => esc_html__( 'Order ID', 'booking-activities' )
	) );
}


/**
 * Get bookings export default settings
 * @since 1.8.0
 * @version 1.14.0
 * @return array
 */
function bookacti_get_bookings_export_default_settings() {
	$defaults = array(
		// CSV
		'csv_columns' => array(
			'booking_id',
			'booking_type',
			'status',
			'payment_status',
			'quantity',
			'creation_date',
			'customer_display_name',
			'customer_email',
			'event_title',
			'start_date',
			'end_date'
		),
		'csv_raw' => 0,
		'csv_export_groups' => 'groups',
		
		// iCal
		'ical_columns' => array(
			'customer_display_name',
			'customer_email',
			'quantity',
			'status'
		),
		'ical_raw'                 => 0,
		'ical_booking_list_header' => 1,
		'vevent_summary'           => '[{event_booked_quantity}/{event_availability_total}] {event_title}',
		'vevent_description'       => '{booking_list}',
		
		// Global
		'per_page' => 200
	);
	return apply_filters( 'bookacti_bookings_export_default_settings', $defaults );
}


/**
 * Get bookings export settings per user
 * @since 1.8.0
 * @param int $user_id
 * @return array
 */
function bookacti_get_bookings_export_settings( $user_id = 0 ) {
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	
	$default_settings = bookacti_get_bookings_export_default_settings();
	$user_settings    = $user_id ? get_user_meta( $user_id, 'bookacti_bookings_export_settings', true ) : array();
	
	$settings = array();
	foreach( $default_settings as $key => $default ) {
		$settings[ $key ] = isset( $user_settings[ $key ] ) ? $user_settings[ $key ] : $default;
	}
	
	return apply_filters( 'bookacti_bookings_export_settings', $settings, $user_id );
}


/**
 * Sanitize bookings export settings
 * @since 1.8.0
 * @version 1.14.0
 * @param array $raw_settings
 * @return array
 */
function bookacti_sanitize_bookings_export_settings( $raw_settings ) {
	$default_settings = bookacti_get_bookings_export_default_settings();
	
	$columns_settings = array( 'csv_columns', 'ical_columns' );
	$keys_by_type = array( 
		'str'      => array( 'vevent_summary' ),
		'str_id'   => array( 'csv_export_groups' ),
		'str_html' => array( 'vevent_description' ),
		'int'      => array( 'per_page' ),
		'bool'     => array( 'csv_raw', 'ical_raw', 'ical_booking_list_header' ),
		'array'    => array( 'csv_columns', 'ical_columns' )
	);
	$settings = bookacti_sanitize_values( $default_settings, $raw_settings, $keys_by_type );
	
	// Sanitize export groups value
	if( ! in_array( $settings[ 'csv_export_groups' ], array( 'groups', 'bookings' ), true ) ) { $settings[ 'csv_export_groups' ] = $default_settings[ 'csv_export_groups' ]; }
	
	// Keep only allowed columns
	$allowed_columns = bookacti_get_bookings_export_columns();
	foreach( $columns_settings as $setting_name ) {
		foreach( $settings[ $setting_name ] as $i => $column_name ) {
			if( ! isset( $allowed_columns[ $column_name ] ) ) { unset( $settings[ $setting_name ][ $i ] ); }
		}
		$settings[ $setting_name ] = array_values( $settings[ $setting_name ] );
	}
	
	return apply_filters( 'bookacti_sanitized_bookings_export_settings', $settings, $raw_settings, $default_settings );
}


/**
 * Get bookings export event tags
 * @since 1.8.0
 * @version 1.16.31
 * @return array
 */
function bookacti_get_bookings_export_event_tags() {
	return apply_filters( 'bookacti_bookings_export_event_tags', array(
		'{event_id}'                 => esc_html__( 'Event ID', 'booking-activities' ),
		'{event_title}'              => esc_html__( 'The event title', 'booking-activities' ),
		'{event_start}'              => esc_html__( 'Event start date and time (formatted)', 'booking-activities' ),
		'{event_end}'                => esc_html__( 'Event end date and time (formatted)', 'booking-activities' ),
		'{event_start_raw}'          => esc_html__( 'Event start date and time (ISO)', 'booking-activities' ),
		'{event_end_raw}'            => esc_html__( 'Event end date and time (ISO)', 'booking-activities' ),
		'{event_booked_quantity}'    => esc_html__( 'Number of active bookings', 'booking-activities' ),
		'{event_availability}'       => esc_html__( 'Number of remaining places', 'booking-activities' ),
		'{event_availability_total}' => esc_html__( 'Total number of places', 'booking-activities' ),
		'{activity_id}'              => esc_html__( 'Activity ID', 'booking-activities' ),
		'{activity_title}'           => esc_html__( 'Activity title', 'booking-activities' ),
		'{calendar_id}'              => esc_html__( 'Calendar ID', 'booking-activities' ),
		'{calendar_title}'           => esc_html__( 'Calendar title', 'booking-activities' ),
		'{booking_count}'            => esc_html__( 'Number of active individual bookings', 'booking-activities' ),
		'{booking_list}'             => esc_html__( 'Event booking list (table)', 'booking-activities' ),
		'{booking_list_raw}'         => esc_html__( 'Event booking list (csv)', 'booking-activities' )
	) );
}


/**
 * Convert a list of bookings to CSV format
 * @since 1.6.0
 * @version 1.14.0
 * @param array $filters
 * @param array $args_raw
 * @return string
 */
function bookacti_convert_bookings_to_csv( $filters, $args_raw = array() ) {
	$default_settings = bookacti_get_bookings_export_default_settings();
	$args_default = array( 
		'columns' => $default_settings[ 'csv_columns' ],
		'raw'     => $default_settings[ 'csv_raw' ],
		'locale'  => '',
	);
	$args = wp_parse_args( $args_raw, $args_default );
	
	// Remove unknown columns 
	$headers = array();
	$allowed_columns = bookacti_get_bookings_export_columns();
	foreach( $args[ 'columns' ] as $i => $column_name ) {
		if( ! isset( $allowed_columns[ $column_name ] ) ) { unset( $args[ 'columns' ][ $i ] ); continue; }
		$headers[ $column_name ] = $allowed_columns[ $column_name ];
	}
	
	// Get booking items
	$export_args = array( 
		'filters' => $filters, 
		'columns' => $args[ 'columns' ], 
		'raw'     => $args[ 'raw' ], 
		'type'    => 'csv',
		'locale'  => $args[ 'locale' ]
	);
	$items = bookacti_get_bookings_for_export( $export_args );
	
	return bookacti_generate_csv( $items, $headers );
}


/**
 * Convert bookings to iCal format
 * @since 1.8.0
 * @version 1.8.8
 * @param array $filters
 * @param array $args_raw
 * @return string
 */
function bookacti_convert_bookings_to_ical( $filters = array(), $args_raw = array() ) {
	// Sanitized args
	$default_settings = bookacti_get_bookings_export_default_settings();
	$args_default = array( 
		'vevent_summary'               => $default_settings[ 'vevent_summary' ],
		'vevent_description'           => $default_settings[ 'vevent_description' ],
		'tooltip_booking_list_columns' => $default_settings[ 'ical_columns' ],
		'booking_list_header'          => $default_settings[ 'ical_booking_list_header' ],
		'raw'                          => $default_settings[ 'ical_raw' ],
		'sequence'                     => 0,
		'locale'                       => ''
	);
	$args = wp_parse_args( $args_raw, $args_default );
	
	// Get items and events
	$filters[ 'group_by' ] = 'none';
	$export_args = array( 
		'filters' => $filters, 
		'columns' => $args[ 'tooltip_booking_list_columns' ], 
		'raw'     => $args[ 'raw' ], 
		'type'    => 'ical',
		'locale'  => $args[ 'locale' ]
	);
	$booking_items = bookacti_get_bookings_for_export( $export_args );
	$events_tags   = bookacti_get_bookings_export_events_tags_values( $booking_items, $args );
		
	$vcalendar = apply_filters( 'bookacti_bookings_ical_vcalendar', array(
		'X-WR-CALNAME' => esc_html__( 'My bookings', 'booking-activities' ),
		'X-WR-CALDESC' => esc_html__( 'My bookings', 'booking-activities' ) . '.'
	), $booking_items, $events_tags, $args, $filters );
	
	$timezone = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$timezone_obj = new DateTimeZone( $timezone );
	$utc_timezone_obj = new DateTimeZone( 'UTC' );
	$occurrence_counter = array();
	$vevents = array();
	
	foreach( $booking_items as $item ) {
		$index = $item[ 'start_date_raw' ] . '_' . $item[ 'event_id' ];
		if( isset( $vevents[ $index ] ) ) { continue; }
		
		// Increase the occurrence counter
		if( ! isset( $occurrence_counter[ $item[ 'event_id' ] ] ) ) { $occurrence_counter[ $item[ 'event_id' ] ] = 0; }
		++$occurrence_counter[ $item[ 'event_id' ] ];

		$uid         = $item[ 'event_id' ] . '-' . $occurrence_counter[ $item[ 'event_id' ] ];
		$event_start = new DateTime( $item[ 'start_date_raw' ], $timezone_obj );
		$event_end   = new DateTime( $item[ 'end_date_raw' ], $timezone_obj );
		$event_start->setTimezone( $utc_timezone_obj );
		$event_end->setTimezone( $utc_timezone_obj );
		
		$events_tags[ $index ][ '{booking_list}' ] = $events_tags[ $index ][ '{booking_list}' ] ? '<table>' . $events_tags[ $index ][ '{booking_list}' ] . '</table>' : '';
		
		$summary     = $args[ 'vevent_summary' ] ? str_replace( array_keys( $events_tags[ $index ] ), array_values( $events_tags[ $index ] ), $args[ 'vevent_summary' ] ) : '';
		$description = $args[ 'vevent_description' ] ? str_replace( array_keys( $events_tags[ $index ] ), array_values( $events_tags[ $index ] ), $args[ 'vevent_description' ] ) : '';
		
		$vevents[ $index ] = apply_filters( 'bookacti_bookings_ical_vevent', array(
			'UID'         => $uid,
			'DTSTART'     => $event_start->format( 'Ymd\THis\Z' ),
			'DTEND'       => $event_end->format( 'Ymd\THis\Z' ),
			'SUMMARY'     => bookacti_sanitize_ical_property( $summary, 'SUMMARY' ),
			'DESCRIPTION' => bookacti_sanitize_ical_property( $description, 'DESCRIPTION' ),
			'SEQUENCE'    => $args[ 'sequence' ]
		), $item, $events_tags[ $index ], $vcalendar, $args, $filters );
	}
	
	$vevents = apply_filters( 'bookacti_bookings_ical_vevents', $vevents, $booking_items, $events_tags, $vcalendar, $args, $filters );
	
	return bookacti_generate_ical( $vevents, $vcalendar );
}


/**
 * Get the events tags values
 * @since 1.8.0
 * @version 1.16.31
 * @param array $booking_items
 * @param array $args
 * @return array
 */
function bookacti_get_bookings_export_events_tags_values( $booking_items, $args ) {
	$has_booking_list = ! empty( $args[ 'tooltip_booking_list_columns' ] ) && strpos( $args[ 'vevent_summary' ] . $args[ 'vevent_description' ], '{booking_list' ) !== false;
	
	// Remove unknown columns in the booking list
	if( $has_booking_list ) {
		$booking_list_headers = $booking_list_empty_columns = array();
		$allowed_columns = bookacti_get_bookings_export_columns();
		foreach( $args[ 'tooltip_booking_list_columns' ] as $i => $column_name ) {
			if( ! isset( $allowed_columns[ $column_name ] ) ) { unset( $args[ 'tooltip_booking_list_columns' ][ $i ] ); continue; }
			$booking_list_headers[ $column_name ] = $allowed_columns[ $column_name ];
			$booking_list_empty_columns[ $column_name ] = '';
		}
		if( empty( $args[ 'tooltip_booking_list_columns' ] ) ) { $has_booking_list = false; }
	}
	
	$date_format = bookacti_get_message( 'date_format_long' );
	
	// Order the items by event occurrence and merge data into an event array
	$qty_ack = array();
	$events_tags = array();
	foreach( $booking_items as $item ) {
		$index = $item[ 'start_date_raw' ] . '_' . $item[ 'event_id' ];
		
		// Get the initial tags values
		if( ! isset( $events_tags[ $index ] ) ) {
			$events_tags[ $index ] = array(
				'{event_id}'                 => $item[ 'event_id' ],
				'{event_title}'              => $item[ 'event_title' ],
				'{event_start}'              => bookacti_format_datetime( $item[ 'start_date_raw' ], $date_format ),
				'{event_end}'                => bookacti_format_datetime( $item[ 'end_date_raw' ], $date_format ),
				'{event_start_raw}'          => $item[ 'start_date_raw' ],
				'{event_end_raw}'            => $item[ 'end_date_raw' ],
				'{event_booked_quantity}'    => 0,
				'{event_availability}'       => intval( $item[ 'availability' ] ),
				'{event_availability_total}' => intval( $item[ 'availability' ] ),
				'{activity_id}'              => $item[ 'activity_id' ],
				'{activity_title}'           => $item[ 'activity_title' ],
				'{calendar_id}'              => $item[ 'template_id' ],
				'{calendar_title}'           => $item[ 'template_title' ],
				'{booking_count}'            => 0,
				'{booking_list}'             => $has_booking_list && ! empty( $args[ 'booking_list_header' ] ) ? '<tr><th>' . implode( '</th><th>', $booking_list_headers ) . '</th></tr>' : '',
				'{booking_list_raw}'         => $has_booking_list && ! empty( $args[ 'booking_list_header' ] ) ? implode( ', ', str_replace( ',', '', array_map( 'strip_tags', $booking_list_headers ) ) ) : ''
			);
		}
		
		// Increment booking quantity
		if( $item[ 'booking_active' ] && empty( $qty_ack[ $item[ 'booking_id' ] ] ) ) {
			$events_tags[ $index ][ '{event_booked_quantity}' ] += intval( $item[ 'quantity' ] );
			$events_tags[ $index ][ '{event_availability}' ]    -= intval( $item[ 'quantity' ] );
			$events_tags[ $index ][ '{booking_count}' ]         += 1;
			$qty_ack[ $item[ 'booking_id' ] ] = true; // Make sure each booking is counted only once
		}
		
		// Build the booking list tags (keep the columns order)
		if( $has_booking_list ) {
			$booking_list_values = array_replace( $booking_list_empty_columns, array_intersect_key( $item, $booking_list_empty_columns ) );
			if( $booking_list_values ) {
				$events_tags[ $index ][ '{booking_list}' ]     .= '<tr><td>' . implode( '</td><td>', $booking_list_values ) . '</td></tr>';
				$events_tags[ $index ][ '{booking_list_raw}' ] .= '\n' . implode( ', ', str_replace( ',', '', array_map( 'strip_tags', $booking_list_values ) ) );
			}
		}
	}
	
	return apply_filters( 'bookacti_bookings_export_events_tags_values', $events_tags, $booking_items, $args );
}


/**
 * Get an array of bookings data formatted to be exported
 * @since 1.6.0
 * @version 1.16.0
 * @param array $args_raw
 * @return array
 */
function bookacti_get_bookings_for_export( $args_raw = array() ) {
	// Format args
	$default_args = array(
		'filters' => array(),
		'columns' => array(),
		'raw'     => true,
		'type'    => 'csv',
		'locale'  => ''
	);
	$args = wp_parse_args( $args_raw, $default_args );
	
	// Check if we will need user data
	$has_user_data = false;
	foreach( $args[ 'columns' ] as $column_name ) {
		if( $column_name !== 'customer_id' && substr( $column_name, 0, 9 ) === 'customer_' ) { 
			$has_user_data = true; break; 
		}
	}
	$get_user_data = apply_filters( 'bookacti_bookings_export_get_users_data', $has_user_data, $args );
	if( $get_user_data ) { $args[ 'filters' ][ 'fetch_meta' ] = true; }

	$bookings = bookacti_get_bookings( $args[ 'filters' ] );

	// Check if the booking list can contain groups
	$may_have_groups = false; 
	$single_only = $args[ 'filters' ][ 'group_by' ] === 'none';
	if( ( ! $args[ 'filters' ][ 'booking_group_id' ] || $args[ 'filters' ][ 'group_by' ] === 'booking_group' ) && ! $args[ 'filters' ][ 'booking_id' ] ) {
		$may_have_groups = true;
	}

	// Gether all IDs in arrays
	$user_ids = array();
	$group_ids = array();
	if( ( $may_have_groups || $single_only ) || $get_user_data ) {
		foreach( $bookings as $booking ) {
			if( $booking->user_id && is_numeric( $booking->user_id ) && ! in_array( $booking->user_id, $user_ids, true ) ) { $user_ids[] = $booking->user_id; }
			if( $booking->group_id && ! in_array( $booking->group_id, $group_ids, true ) ) { $group_ids[] = $booking->group_id; }
		}
	}

	// Retrieve the required groups data only
	$booking_groups   = array();
	$displayed_groups = array();
	if( ( $may_have_groups || $single_only ) && $group_ids ) {
		// Get only the groups that will be displayed
		$group_filters  = bookacti_format_booking_filters( array( 'in__booking_group_id' => $group_ids, 'fetch_meta' => true ) );
		$booking_groups = bookacti_get_booking_groups( $group_filters );
	}

	// Retrieve information about users and stock them into an array sorted by user id
	$users = array();
	$roles_names = array();
	if( $get_user_data ) {
		$users = $user_ids ? bookacti_get_users_data( array( 'include' => $user_ids ) ) : array();
		$roles_names = bookacti_get_roles();
	}
	$unknown_user_id = esc_attr( apply_filters( 'bookacti_unknown_user_id', 'unknown_user' ) );
	
	$date_format      = $args[ 'raw' ] ? 'Y-m-d' : get_option( 'date_format' );
	$datetime_format  = $args[ 'raw' ] ? 'Y-m-d H:i:s' : bookacti_get_message( 'date_format_long' );
	$booking_statuses = $args[ 'raw' ] ? array() : bookacti_get_booking_statuses();
	$payment_statuses = $args[ 'raw' ] ? array() : bookacti_get_payment_statuses();
	
	$utc_timezone_obj = new DateTimeZone( 'UTC' );
	$timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
	try { $timezone_obj = new DateTimeZone( $timezone ); }
	catch ( Exception $ex ) { $timezone_obj = clone $utc_timezone_obj; }
	
	// Build booking list
	$booking_items = array();
	foreach( $bookings as $booking ) {
		$group = $booking->group_id && ! empty( $booking_groups[ $booking->group_id ] ) ? $booking_groups[ $booking->group_id ] : null;

		// Display one single row for a booking group, instead of each bookings of the group
		if( $booking->group_id && $may_have_groups && ! $single_only ) {
			// If the group row has already been displayed, or if it is not found, continue
			if( isset( $displayed_groups[ $booking->group_id ] ) ) { continue; }
			if( empty( $booking_groups[ $booking->group_id ] ) )   { continue; }

			$booking_type   = 'group';
			$id             = $group->id;
			$user_id        = $group->user_id;
			$status         = $group->state;
			$paid           = $group->payment_status;
			$event_id       = $group->event_group_id;
			$title          = $group->group_title;
			$start          = $group->start;
			$end            = $group->end;
			$quantity       = $group->quantity;
			$availability   = $booking->availability;
			$form_id        = $group->form_id;
			$order_id       = $group->order_id;
			$activity_id    = $group->category_id;
			$activity_title = $group->category_title;
			$active         = $group->active;

			$displayed_groups[ $booking->group_id ] = $booking->id;

		// Single booking
		} else {
			$booking_type   = 'single';
			$id             = $booking->id;
			$user_id        = $booking->user_id;
			$status         = $booking->state;
			$paid           = $booking->payment_status;
			$title          = $booking->event_title;
			$event_id       = $booking->event_id;
			$start          = $booking->event_start;
			$end            = $booking->event_end;
			$quantity       = $booking->quantity;
			$availability   = $booking->availability;
			$form_id        = $booking->form_id;
			$order_id       = $booking->order_id;
			$activity_id    = $booking->activity_id;
			$activity_title = $booking->activity_title;
			$active         = $booking->active;
		}
		
		// Creation date
		$creation_date_raw = ! empty( $booking->creation_date ) ? bookacti_sanitize_datetime( $booking->creation_date ) : '';
		$creation_date_dt = new DateTime( $creation_date_raw, $utc_timezone_obj );
		$creation_date_dt->setTimezone( $timezone_obj );
		$creation_date = $creation_date_raw ? bookacti_format_datetime( $creation_date_dt->format( 'Y-m-d H:i:s' ), $date_format ) : '';
		
		$booking_data = array( 
			'booking_id'            => $id,
			'booking_type'          => $booking_type,
			'booking_active'        => $active,
			'status'                => $args[ 'raw' ] ? $status : ( ! empty( $booking_statuses[ $status ] ) ? $booking_statuses[ $status ] : $status ),
			'status_raw'            => $status,
			'payment_status'        => $args[ 'raw' ] ? $paid : ( ! empty( $payment_statuses[ $paid ] ) ? $payment_statuses[ $paid ] : $paid ),
			'payment_status_raw'    => $paid,
			'quantity'              => $quantity,
			'availability'          => $availability,
			'creation_date'         => $args[ 'raw' ] ? $booking->creation_date : $creation_date,
			'creation_date_raw'     => $booking->creation_date,
			'event_id'              => $event_id,
			'event_title'           => $title ? apply_filters( 'bookacti_translate_text', $title ) : '',
			'start_date'            => $args[ 'raw' ] ? $start : bookacti_format_datetime( $start, $datetime_format ),
			'end_date'              => $args[ 'raw' ] ? $end : bookacti_format_datetime( $end, $datetime_format ),
			'start_date_raw'        => $start,
			'end_date_raw'          => $end,
			'template_id'           => $booking->template_id,
			'template_title'        => ! empty( $booking->template_title ) ? apply_filters( 'bookacti_translate_text', $booking->template_title ) : '',
			'activity_id'           => $activity_id,
			'activity_title'        => $activity_title ? apply_filters( 'bookacti_translate_text', $activity_title ) : '',
			'form_id'               => $form_id,
			'order_id'              => $order_id,
			'customer_id'           => $user_id,
			'customer_display_name' => '',
			'customer_first_name'   => '',
			'customer_last_name'    => '',
			'customer_email'        => '',
			'customer_phone'        => '',
			'customer_roles'        => ''
		);

		// Format customer column
		$user = null;
		if( $get_user_data ) {
			// If the customer has an account
			if( ! empty( $users[ $user_id ] ) ) {
				$user = $users[ $user_id ];
				$booking_data = array_merge( $booking_data, array(
					'customer_display_name' => $user->display_name,
					'customer_first_name'   => ! empty( $user->first_name ) ? $user->first_name : '',
					'customer_last_name'    => ! empty( $user->last_name ) ? $user->last_name : '',
					'customer_email'        => ! empty( $user->user_email ) ? $user->user_email : '',
					'customer_phone'        => ! empty( $user->phone ) ? $user->phone : '',
					'customer_roles'        => ! empty( $user->roles ) ? implode( ', ', array_replace( array_combine( $user->roles, $user->roles ), array_intersect_key( $roles_names, array_flip( $user->roles ) ) ) ) : ''
				));

			// If the booking was made without account
			} else if( $user_id === $unknown_user_id || is_email( $user_id ) ) {
				$booking_meta = $group && $args[ 'filters' ][ 'group_by' ] !== 'booking_group' ? $group : $booking;
				$booking_data = array_merge( $booking_data, array(
					'customer_first_name' => ! empty( $booking_meta->user_first_name ) ? $booking_meta->user_first_name : '',
					'customer_last_name'  => ! empty( $booking_meta->user_last_name ) ? $booking_meta->user_last_name : '',
					'customer_email'      => ! empty( $booking_meta->user_email ) ? $booking_meta->user_email : '',
					'customer_phone'      => ! empty( $booking_meta->user_phone ) ? $booking_meta->user_phone : ''
				));
				$booking_data[ 'customer_display_name' ] .= ! empty( $booking_data[ 'customer_first_name' ] ) ? $booking_data[ 'customer_first_name' ] : '';
				$booking_data[ 'customer_display_name' ] .= empty( $booking_data[ 'customer_first_name' ] ) && ! empty( $booking_data[ 'customer_last_name' ] ) ? ' ' : '';
				$booking_data[ 'customer_display_name' ] .= ! empty( $booking_data[ 'customer_last_name' ] ) ? $booking_data[ 'customer_last_name' ] : '';
			}
		}

		/**
		 * Third parties can add or change columns content, but do your best to optimize your process
		 * @since 1.6.0
		 */
		$booking_item = apply_filters( 'bookacti_booking_export_columns_content', $booking_data, $booking, $group, $user, $args );

		$booking_items[ $booking->id ] = $booking_item;
	}

	/**
	 * Third parties can add or change rows and columns, but do your best to optimize your process
	 * @since 1.6.0
	 */
	return apply_filters( 'bookacti_booking_items_to_export', $booking_items, $bookings, $booking_groups, $displayed_groups, $users, $args );
}


/**
 * Check if a secret key match a booking
 * @since 1.12.0
 * @param string $secret_key
 * @param int $booking_id
 * @param string $booking_type
 * @param boolean $strict TRUE = check the booking secret key only. FALSE = check user secret key too.
 * @return boolean
 */
function bookacti_is_booking_secret_key_valid( $secret_key, $booking_id, $booking_type = 'single', $strict = false ) {
	$true = false;
	
	// Check if the secret key match the booking's
	$booking_item = bookacti_get_booking_id_by_secret_key( $secret_key );
	if( $booking_item && $booking_item[ 'id' ] === $booking_id && $booking_item[ 'type' ] === $booking_type ) { $true = true; }
	else if( ! $strict ) {
		// Check if the user is a super admin
		$user_id = bookacti_get_user_id_by_secret_key( $secret_key );
		if( $user_id && is_super_admin( $user_id ) ) { $true = true; }
		else if( $user_id ) {
			// Check if the secret key match the booking's user's
			$booking = $booking_type === 'group' ? bookacti_get_booking_group_by_id( $booking_id ) : bookacti_get_booking_by_id( $booking_id );
			if( $booking && intval( $booking->user_id ) === $user_id ) { $true = true; } 
			else if( $booking && $booking->template_id && ( user_can( $user_id, 'bookacti_manage_bookings' ) || user_can( $user_id, 'bookacti_edit_bookings' ) ) ) {
				// Check if the secret key match a manager's allowed to manage this booking
				$managers = bookacti_get_template_managers( array( $booking->template_id ) );
				if( in_array( $user_id, $managers, true ) ) { $true = true; }
			}
		}
	}
	
	return $true;
}




// REFUND BOOKING

/**
 * Get available actions user can take to be refunded 
 * @version 1.9.0
 * @return array
 */
function bookacti_get_refund_actions() {
	$possible_actions_array = array(
		'email' => array( 
			'id'          => 'email',
			'label'       => esc_html__( 'Email', 'booking-activities' ),
			'description' => esc_html__( 'Send a refund request by email to the administrator.', 'booking-activities' )
		)
	);
	return apply_filters( 'bookacti_refund_actions', $possible_actions_array );
}


/**
 * Get formatted refund method label
 * @since 1.9.0
 * @param string $refund_method
 * @return string
 */
function bookacti_get_refund_label( $refund_method ) {
	$formatted_refund_label = $refund_method;
	$refund_actions = bookacti_get_refund_actions();
	if( ! empty( $refund_actions[ $refund_method ][ 'label' ] ) ) {
		$formatted_refund_label = $refund_actions[ $refund_method ][ 'label' ];
	}
	return $formatted_refund_label;
}


/**
 * Get refund actions for a specific booking or booking group
 * @since 1.16.0 (was bookacti_get_booking_refund_actions)
 * @param array $bookings 
 * @param string $booking_type
 * @param string $context
 * @return array
 */
function bookacti_get_selected_bookings_refund_actions( $selected_bookings, $is_frontend = true ) {
	$possible_actions = bookacti_get_refund_actions();

	// If current user is a customer
	if( ! current_user_can( 'bookacti_edit_bookings' ) || $is_frontend ) {
		// Keep only allowed action
		$allowed_actions = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'refund_actions_after_cancellation' );
		if( ! is_array( $allowed_actions ) ) {
			if( ! empty( $allowed_actions ) ) {
				$allowed_actions = array( $allowed_actions );
			} else {
				$allowed_actions = array();
			}
		}
		// Keep all possible actions that are allowed
		$possible_actions = array_intersect_key( $possible_actions, array_flip( $allowed_actions ) );
		
		if( isset( $possible_actions[ 'email' ] ) ) {
			$possible_actions[ 'email' ][ 'booking_ids' ]       = array_keys( $selected_bookings[ 'bookings' ] );
			$possible_actions[ 'email' ][ 'booking_group_ids' ] = array_keys( $selected_bookings[ 'booking_groups' ] );
		}
	
	// If current user is an admin
	} else {
		// Email action is useless, remove it
		unset( $possible_actions[ 'email' ] );
	}
	
	foreach( $possible_actions as $i => $possible_action ) {
		$possible_actions[ $i ][ 'bookings_nb' ] = count( $selected_bookings[ 'bookings' ] ) + count( $selected_bookings[ 'booking_groups' ] );
	}
	
	return apply_filters( 'bookacti_selected_bookings_refund_actions', $possible_actions, $selected_bookings, $is_frontend );
}


/**
 * Get refund actions html
 * @since 1.16.0 (was bookacti_get_booking_refund_options_html)
 * @version 1.16.0
 * @param array $actions
 * @return string
 */
function bookacti_get_refund_actions_html( $actions = array() ) {
	if( ! $actions ) { return ''; }
	
	ob_start();
	
	foreach( $actions as $action ) {
		$action_id      = esc_attr( $action[ 'id' ] );
		$has_compat_nb  = isset( $action[ 'booking_ids' ] ) || isset( $action[ 'booking_group_ids' ] );
		$bookings_nb    = ! empty( $action[ 'bookings_nb' ] ) ? $action[ 'bookings_nb' ] : 0;
		$compatible_nb  = ! empty( $action[ 'booking_ids' ] ) ? count( $action[ 'booking_ids' ] ) : 0;
		$compatible_nb += ! empty( $action[ 'booking_group_ids' ] ) ? count( $action[ 'booking_group_ids' ] ) : 0;
		$warning        = '';
		if( $has_compat_nb && $bookings_nb && $compatible_nb < $bookings_nb ) {
			$warning = sprintf( 
				/* translators: %1$s = Number of bookings compatible. %2$s = Total number of bookings. */
				esc_html( _n( 'Only %1$s out of %2$s is compatible with this refund method.', 'Only %1$s out of %2$s are compatible with this refund method.', $compatible_nb, 'booking-activities' ) ), 
				'<strong>' . sprintf( esc_html( _n( '%s booking', '%s bookings', $compatible_nb, 'booking-activities' ) ), $compatible_nb ) . '</strong>', 
				$bookings_nb
			);
		}
		
		if( $has_compat_nb && ! $compatible_nb ) { continue; }
		?>
			<div class='bookacti-refund-option' >
				<div class='bookacti-refund-option-radio'>
					<input type='radio' name='refund_action' value='<?php echo $action_id; ?>' id='bookacti-refund-action-<?php echo $action_id; ?>' class='bookacti-refund-action'/>
				</div>
				<div class='bookacti-refund-option-text'>
					<label class='bookacti-refund-option-label' for='bookacti-refund-action-<?php echo $action_id; ?>'>
						<span><?php echo esc_html( $action[ 'label' ] ); ?></span>
						<span class='bookacti-refund-option-description'><?php echo esc_html( $action[ 'description' ] ); ?></span>
					</label>
					<?php if( $warning ) { ?>
					<span class='bookacti-warning bookacti-refund-option-warning'>
						<span class='dashicons dashicons-warning'></span>
						<span><em><?php echo $warning ?></em></span>
					</span>
					<?php } ?>
				</div>
			</div>
		<?php
	}
	
	return apply_filters( 'bookacti_booking_refund_options_html', ob_get_clean(), $actions );
}


/**
 * Get the selected bookings total price
 * @since 1.16.0 (was bookacti_get_booking_refund_amount)
 * @param array $selected_bookings
 * @param bool $is_formatted
 * @return int|float|string
 */
function bookacti_get_selected_bookings_total_price( $selected_bookings, $is_formatted = false ) {
	return apply_filters( 'bookacti_selected_bookings_total_price', $is_formatted ? '' : 0, $selected_bookings, $is_formatted );
}


/**
 * Get formatted booking refunds
 * @since 1.9.0
 * @param array $refunds
 * @param int $booking_id
 * @param string $booking_type
 * @return array
 */
function bookacti_format_booking_refunds( $refunds, $booking_id = 0, $booking_type = 'single' ) {
	return apply_filters( 'bookacti_booking_refunds_formatted', $refunds, $booking_id, $booking_type );
}




// BOOKING STATUSES

/**
 * Get booking statuses
 * @version 1.16.0 (was bookacti_get_booking_state_labels)
 * @return array
 */
function bookacti_get_booking_statuses() {
	return apply_filters( 'bookacti_booking_statuses', array(
		'delivered'        => esc_html__( 'Delivered', 'booking-activities' ),
		'booked'           => esc_html__( 'Booked', 'booking-activities' ),
		'pending'          => esc_html__( 'Pending', 'booking-activities' ),
		'cancelled'        => esc_html__( 'Cancelled', 'booking-activities' ),
		'refunded'         => esc_html__( 'Refunded', 'booking-activities' ),
		'refund_requested' => esc_html__( 'Refund requested', 'booking-activities' )
	) );
}


/**
 * Get array of ACTIVE booking statuses, every other booking statuses will be considered as INACTIVE
 * @since 1.6.0 (was bookacti_get_active_booking_states)
 * @return array
 */
function bookacti_get_active_booking_statuses() {
	return apply_filters( 'bookacti_active_booking_statuses', array( 'delivered', 'booked', 'pending' ) );
}


/**
 * Get payment statuses
 * @since 1.3.0
 * @version 1.16.0
 * @return array
 */
function bookacti_get_payment_statuses() {
	return apply_filters( 'bookacti_payment_statuses', array(
		'none' => esc_html__( 'No payment required', 'booking-activities' ),
		'owed' => esc_html__( 'Owed', 'booking-activities' ),
		'paid' => esc_html__( 'Paid', 'booking-activities' )
	) );
}


/**
 * Get booking status HTML
 * @since 1.16.0 (was bookacti_format_booking_state)
 * @version 1.16.38
 * @param string $status
 * @param boolean $icon_only
 * @return string
 */
function bookacti_format_booking_status( $status, $icon_only = false ) {
	$statuses = bookacti_get_booking_statuses();
	$label    = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
	
	$atts    = ' title="' . esc_attr( $label ) . '"';
	$class   = 'bookacti-booking-status bookacti-booking-status-' . $status;
	$content = $label;
	
	if( $icon_only ) {
		$atts    = ' data-tip="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '"';
		$class  .= ' bookacti-tip';
		$content = '';
	}
	
	$html = '<span class="' . esc_attr( $class ) . '" data-booking-status="' . esc_attr( $status ) . '"' . $atts . '>' . esc_html( $content ) . '</span>';
	
	return apply_filters( 'bookacti_booking_status_html', $html, $status, $icon_only );
}


/**
 * Get payment status HTML
 * @since 1.8.0
 * @version 1.16.38
 * @param string $status
 * @param boolean $icon_only
 * @return string
 */
function bookacti_format_payment_status( $status, $icon_only = false ) {
	$statuses = bookacti_get_payment_statuses();
	$label    = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
	
	$atts    = ' title="' . esc_attr( $label ) . '"';
	$class   = 'bookacti-payment-status bookacti-payment-status-' . $status;
	$content = $label;
	
	if( $icon_only ) {
		$atts    = ' data-tip="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '"';
		$class  .= ' bookacti-tip';
		$content = '';
	}
	
	$html = '<span class="' . esc_attr( $class ) . '" data-payment-status="' . esc_attr( $status ) . '" ' . $atts . '>' . esc_html( $content ) . '</span>';

	return apply_filters( 'bookacti_payment_status_html', $html, $status, $icon_only );
}




// BOOKING LIST

/**
 * Booking list column labels
 * @since 1.7.4
 * @version 1.14.0
 * @return array
 */
function bookacti_get_user_booking_list_columns_labels() {
	return apply_filters( 'bookacti_user_booking_list_columns_labels', array(
		'booking_id'            => _x( 'id', 'An id is a unique identification number', 'booking-activities' ),
		'booking_type'          => esc_html_x( 'Type', 'Booking type (single or group)', 'booking-activities' ),
		'status'                => esc_html_x( 'Status', 'Booking status', 'booking-activities' ),
		'payment_status'        => esc_html_x( 'Paid', 'Payment status column name', 'booking-activities' ),
		'quantity'              => esc_html_x( 'Qty', 'Short for "Quantity"', 'booking-activities' ),
		'creation_date'         => esc_html__( 'Date', 'booking-activities' ),
		'customer_id'           => esc_html__( 'Customer ID', 'booking-activities' ),
		'customer_display_name' => esc_html__( 'Customer', 'booking-activities' ),
		'customer_first_name'   => esc_html__( 'First name', 'booking-activities' ),
		'customer_last_name'    => esc_html__( 'Last name', 'booking-activities' ),
		'customer_email'        => esc_html__( 'Email', 'booking-activities' ),
		'customer_phone'        => esc_html__( 'Phone', 'booking-activities' ),
		'customer_roles'        => esc_html__( 'Roles', 'booking-activities' ),
		'events'                => esc_html__( 'Events', 'booking-activities' ),
		'event_id'              => esc_html__( 'Event ID', 'booking-activities' ),
		'event_title'           => esc_html__( 'Title', 'booking-activities' ),
		'start_date'            => esc_html__( 'Start', 'booking-activities' ),
		'end_date'              => esc_html__( 'End', 'booking-activities' ),
		'template_id'           => esc_html__( 'Calendar ID', 'booking-activities' ),
		'template_title'        => esc_html__( 'Calendar', 'booking-activities' ),
		'activity_id'           => esc_html__( 'Activity ID', 'booking-activities' ),
		'activity_title'        => esc_html__( 'Activity', 'booking-activities' ),
		'form_id'               => esc_html__( 'Form ID', 'booking-activities' ),
		'order_id'              => esc_html__( 'Order ID', 'booking-activities' ),
		'actions'               => esc_html__( 'Actions', 'booking-activities' )
	) );
}


/**
 * Default user booking list columns
 * @since 1.7.4
 * @return array
 */
function bookacti_get_user_booking_list_default_columns() {
	$columns = apply_filters( 'bookacti_user_booking_list_default_columns', array(
		10  => 'booking_id',
		20  => 'events',
		30  => 'quantity',
		40  => 'status',
		100 => 'actions'
	) );

	// Order columns
	ksort( $columns );

	return $columns;
}


/**
 * Default event booking list columns
 * @since 1.8.0
 * @return array
 */
function bookacti_get_event_booking_list_default_columns() {
	$columns = apply_filters( 'bookacti_event_booking_list_default_columns', array(
		10 => 'booking_id',
		20 => 'status',
		30 => 'payment_status',
		40 => 'quantity',
		50 => 'customer_display_name'
	) );
	
	// Order columns
	ksort( $columns );

	return $columns;
}


/**
 * Private booking list columns
 * @since 1.8.0
 * @return array
 */
function bookacti_get_user_booking_list_private_columns() {
	return apply_filters( 'bookacti_user_booking_list_private_columns', array(
		'customer_id',
		'customer_display_name',
		'customer_first_name',
		'customer_last_name',
		'customer_email',
		'customer_phone',
		'customer_roles'
	));
}


/**
 * Get booking list items
 * @since 1.7.4
 * @version 1.16.0
 * @param array $filters
 * @param array $columns
 * @return string
 */
function bookacti_get_user_booking_list_items( $filters, $columns = array() ) {
	// Get default columns
	if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
	
	// Remove private columns if not allowed
	$current_user_id = get_current_user_id();
	$display_private_columns = apply_filters( 'bookacti_user_booking_list_display_private_columns', bookacti_get_setting_value( 'bookacti_general_settings', 'display_private_columns' ) || ! empty( $filters[ 'display_private_columns' ] ), $filters, $columns );
	$current_user_can_manage_bookings = apply_filters( 'bookacti_user_booking_list_can_manage_bookings', current_user_can( 'bookacti_manage_bookings' ) || current_user_can( 'bookacti_edit_bookings' ), $filters, $columns );
	if( ! $display_private_columns ) {
		$current_user_can_see_users_data = current_user_can( 'list_users' ) || current_user_can( 'edit_users' );
		$is_current_user_list = $current_user_id && ( ( intval( $filters[ 'user_id' ] ) && intval( $filters[ 'user_id' ] ) === $current_user_id ) || ( count( $filters[ 'in__user_id' ] ) === 1 && intval( $filters[ 'in__user_id' ][ 0 ] ) && intval( $filters[ 'in__user_id' ][ 0 ] ) === $current_user_id ) );
		$display_private_columns = $current_user_can_manage_bookings || $current_user_can_see_users_data || $is_current_user_list;
	}
	$private_columns = bookacti_get_user_booking_list_private_columns();
	$is_customer_email_pivate = in_array( 'customer_email', $private_columns, true );
	$is_customer_id_pivate = in_array( 'customer_id', $private_columns, true );
	
	// Check if we will need user data
	$has_user_data = false;
	foreach( $columns as $column_name ) {
		if( $column_name !== 'customer_id' && substr( $column_name, 0, 9 ) === 'customer_' ) { 
			$has_user_data = true; break; 
		}
	}
	$get_user_data = apply_filters( 'bookacti_user_booking_list_get_users_data', $has_user_data, $filters, $columns );
	if( $get_user_data ) { $filters[ 'fetch_meta' ] = true; }
	
	// Get bookings
	$bookings = bookacti_get_bookings( $filters );
	
	// Check if the booking list can contain groups
	$single_only = $filters[ 'group_by' ] === 'none';
	$may_have_groups = false; 
	if( ( ! $filters[ 'booking_group_id' ] || in_array( $filters[ 'group_by' ], array( 'booking_group', 'none' ), true ) ) && ! $filters[ 'booking_id' ] ) {
		$may_have_groups = true;
	}
	
	// Gether all IDs in arrays
	$user_ids = array();
	$group_ids = array();
	foreach( $bookings as $booking ) {
		if( $booking->user_id && is_numeric( $booking->user_id ) && ! in_array( $booking->user_id, $user_ids, true ) ) { $user_ids[] = $booking->user_id; }
		if( $booking->group_id && ! in_array( $booking->group_id, $group_ids, true ) ) { $group_ids[] = $booking->group_id; }
	}
	
	// Retrieve the required groups data only
	$booking_groups     = array();
	$displayed_groups   = array();
	$bookings_per_group = array();
	if( ( $may_have_groups || $single_only ) && $group_ids ) {
		$group_filters   = bookacti_format_booking_filters( array( 'in__booking_group_id' => $group_ids, 'fetch_meta' => $filters[ 'fetch_meta' ] ) );
		$booking_groups  = bookacti_get_booking_groups( $group_filters );
		$groups_bookings = bookacti_get_bookings( $group_filters );
		foreach( $groups_bookings as $booking ) {
			if( ! isset( $bookings_per_group[ $booking->group_id ] ) ) { $bookings_per_group[ $booking->group_id ] = array(); }
			$bookings_per_group[ $booking->group_id ][] = $booking;
		}
	}

	// Retrieve information about users and stock them into an array sorted by user id
	$users = array();
	$roles_names = array();
	if( $get_user_data ) {
		$users = $user_ids ? bookacti_get_users_data( array( 'include' => $user_ids ) ) : array();
		$roles_names = bookacti_get_roles();
	}
	$unknown_user_id = esc_attr( apply_filters( 'bookacti_unknown_user_id', 'unknown_user' ) );
	
	// Get datetime format
	$datetime_format = bookacti_get_message( 'date_format_long' );
	$date_format = get_option( 'date_format' );
	$utc_timezone_obj = new DateTimeZone( 'UTC' );
	$timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
	try { $timezone_obj = new DateTimeZone( $timezone ); }
	catch ( Exception $ex ) { $timezone_obj = clone $utc_timezone_obj; }
	
	// Build an array of bookings rows
	$booking_list_items = array();
	foreach( $bookings as $booking ) {
		$group            = $booking->group_id && ! empty( $booking_groups[ $booking->group_id ] ) ? $booking_groups[ $booking->group_id ] : null;
		$grouped_bookings = $booking->group_id && ! empty( $bookings_per_group[ $booking->group_id ] ) && ! $single_only ? $bookings_per_group[ $booking->group_id ] : array( $booking );
		
		// Display one single row for a booking group, instead of each bookings of the group
		if( $booking->group_id && $may_have_groups && ! $single_only ) {
			// If the group row has already been displayed, or if it is not found, continue
			if( isset( $displayed_groups[ $booking->group_id ] ) 
			||  empty( $booking_groups[ $booking->group_id ] ) ) { continue; }
			
			$group_id_link  = $current_user_can_manage_bookings ? '<a href="' . admin_url( 'admin.php?page=bookacti_bookings&booking_group_id=' . $group->id . '&group_by=booking_group&event_group_id=' . $group->event_group_id ) . '">' . $group->id . '</a>' : $group->id;
			
			$raw_id         = $group->id;
			$raw_group_id   = $group->id;
			$tr_class       = 'bookacti-booking-group';
			$id             = $group_id_link . '<span class="bookacti-booking-group-indicator">' . esc_html_x( 'Group', 'noun', 'booking-activities' ) . '</span>';
			$user_id        = $group->user_id;
			$status         = $group->state;
			$paid           = $group->payment_status;
			$event_id       = $group->event_group_id;
			$title          = $group->group_title;
			$start          = $group->start;
			$end            = $group->end;
			$quantity       = $group->quantity;
			$form_id        = $group->form_id;
			$order_id       = $group->order_id;
			$actions        = in_array( 'actions', $columns, true ) ? bookacti_get_booking_group_actions_by_booking_group( $group, $grouped_bookings, 'front' ) : array();
			$refund_actions = in_array( 'actions', $columns, true ) ? bookacti_get_selected_bookings_refund_actions( array( 'bookings' => array(), 'booking_groups' => array( $group->id => $group ), 'groups_bookings' => array( $group->id => $grouped_bookings ) ), true ) : array();
			$activity_id    = $group->category_id;
			$activity_title = $group->category_title;
			$booking_type   = 'group';
			
			$displayed_groups[ $booking->group_id ] = $booking->id;

		// Single booking
		} else {
			$booking_id_link = $current_user_can_manage_bookings ? '<a href="' . admin_url( 'admin.php?page=bookacti_bookings&booking_id=' . $booking->id . '&event_id=' . $booking->event_id . '&event_start=' . $booking->event_start . '&event_end=' . $booking->event_end ) . '">' . $booking->id. '</a>' : $booking->id;
			$group_id_link   = $current_user_can_manage_bookings ? '<a href="' . admin_url( 'admin.php?page=bookacti_bookings&booking_group_id=' . $booking->group_id . '&group_by=booking_group&event_group_id=' . ( $group ? $group->event_group_id : '' ) ) . '">' . $booking->group_id . '</a>' : $booking->group_id;
			
			$raw_id          = $booking->id;
			$raw_group_id    = $booking->group_id ? $booking->group_id : 0;
			$tr_class        = $booking->group_id ? 'bookacti-single-booking bookacti-gouped-booking bookacti-booking-group-id-' . $booking->group_id : 'bookacti-single-booking';
			$id              = $booking->group_id ? $booking_id_link . '<span class="bookacti-booking-group-id" >' . $group_id_link . '</span>' : $booking_id_link;
			$user_id         = $booking->user_id;
			$status          = $booking->state;
			$paid            = $booking->payment_status;
			$event_id        = $booking->event_id;
			$title           = $booking->event_title;
			$start           = $booking->event_start;
			$end             = $booking->event_end;
			$quantity        = $booking->quantity;
			$form_id         = $booking->form_id;
			$order_id        = $booking->order_id;
			$actions         = in_array( 'actions', $columns, true ) ? bookacti_get_booking_actions_by_booking( $booking, 'front' ) : array();
			$refund_actions  = in_array( 'actions', $columns, true ) ? bookacti_get_selected_bookings_refund_actions( array( 'bookings' => array( $booking->id => $booking ), 'booking_groups' => array(), 'groups_bookings' => array() ), true ) : array();
			$activity_id     = $booking->activity_id;
			$activity_title  = $booking->activity_title;
			$booking_type    = 'single';
		}
		
		// Creation date
		$creation_date_raw = ! empty( $booking->creation_date ) ? bookacti_sanitize_datetime( $booking->creation_date ) : '';
		$creation_date_dt = new DateTime( $creation_date_raw, $utc_timezone_obj );
		$creation_date_dt->setTimezone( $timezone_obj );
		$creation_date = $creation_date_raw ? bookacti_format_datetime( $creation_date_dt->format( 'Y-m-d H:i:s' ), $date_format ) : '';
		$creation_date = $creation_date ? '<span title="' . esc_attr( $booking->creation_date ) . '">' . $creation_date . '</span>' : '';
		
		// Format customer column
		// If the customer has an account
		if( ! empty( $users[ $user_id ] ) ) {
			$user = $users[ $user_id ];
			$customer   = ! empty( $user->first_name ) && ! empty( $user->last_name ) ? $user->first_name . ' ' . $user->last_name : $user->display_name;
			$first_name = ! empty( $user->first_name ) ? $user->first_name : '';
			$last_name  = ! empty( $user->last_name ) ? $user->last_name : '';
			$email      = ! empty( $user->user_email ) ? $user->user_email : '';
			$phone      = ! empty( $user->phone ) ? $user->phone : '';
			$roles      = ! empty( $user->roles ) ? implode( ', ', array_replace( array_combine( $user->roles, $user->roles ), array_intersect_key( $roles_names, array_flip( $user->roles ) ) ) ) : '';

		// If the booking was made without account
		} else if( $user_id === $unknown_user_id || is_email( $user_id ) ) {
			$user         = null;
			$customer     = ! empty( $user_id ) ? $user_id : '';
			$booking_meta = $group && $filters[ 'group_by' ] !== 'booking_group' ? $group : $booking;
			if( ! empty( $booking_meta->user_first_name ) || ! empty( $booking_meta->user_last_name ) ) {
				$customer = ! empty( $booking_meta->user_first_name ) ? $booking_meta->user_first_name . ' ' : '';
				$customer .= ! empty( $booking_meta->user_last_name ) ? $booking_meta->user_last_name . ' ' : '';
				$customer .= $user_id !== $unknown_user_id ? '<br/>(' . $user_id . ')' : '';
			}
			$first_name = ! empty( $booking_meta->user_first_name ) ? $booking_meta->user_first_name : '';
			$last_name  = ! empty( $booking_meta->user_last_name ) ? $booking_meta->user_last_name : '';
			$email      = ! empty( $booking_meta->user_email ) ? $booking_meta->user_email : '';
			$phone      = ! empty( $booking_meta->user_phone ) ? $booking_meta->user_phone : '';
			$roles      = '';

		// Any other cases
		} else {
			$user       = null;
			$customer   = esc_html( __( 'Unknown user', 'booking-activities' ) . ' (' . $user_id . ')' );
			$first_name = '';
			$last_name  = '';
			$email      = '';
			$phone      = '';
			$roles      = '';
		}
		
		/**
		 * Third parties can add or change columns content, do your best to optimize your process
		 */
		$booking_item = apply_filters( 'bookacti_user_booking_list_item', array( 
			'tr_class'              => $tr_class,
			'booking_id_raw'        => $raw_id,
			'booking_group_id_raw'  => $raw_group_id,
			'booking_id'            => $id,
			'booking_type'          => $booking_type,
			'status'                => bookacti_format_booking_status( $status ),
			'payment_status'        => bookacti_format_payment_status( $paid ),
			'quantity'              => $quantity,
			'creation_date'         => $creation_date,
			'customer_id'           => $user_id,
			'customer_display_name' => $customer,
			'customer_first_name'   => $first_name,
			'customer_last_name'    => $last_name,
			'customer_email'        => $email,
			'customer_phone'        => $phone,
			'customer_roles'        => $roles,
			'events'                => in_array( 'events', $columns, true ) ? bookacti_get_formatted_booking_events_list( $grouped_bookings, false ) : '',
			'event_id'              => $event_id,
			'event_title'           => $title ? apply_filters( 'bookacti_translate_text', $title ) : '',
			'start_date'            => bookacti_format_datetime( $start, $datetime_format ),
			'end_date'              => bookacti_format_datetime( $end, $datetime_format ),
			'start_date_raw'        => $start,
			'end_date_raw'          => $end,
			'template_id'           => $booking->template_id,
			'template_title'        => ! empty( $booking->template_title ) ? apply_filters( 'bookacti_translate_text', $booking->template_title ) : '',
			'activity_id'           => $activity_id,
			'activity_title'        => ! empty( $activity_title ) ? apply_filters( 'bookacti_translate_text', $activity_title ) : '',
			'form_id'               => $form_id,
			'order_id'              => $order_id,
			'actions'               => $actions,
			'refund_actions'        => $refund_actions
		), $booking, $group, $grouped_bookings, $user, $filters, $columns );
		
		$booking_list_items[ $booking->id ] = $booking_item;
	}
	
	/**
	 * Third parties can add or change rows and columns, do your best to optimize your process
	 */
	$booking_list_items = apply_filters( 'bookacti_user_booking_list_items', $booking_list_items, $bookings, $booking_groups, $bookings_per_group, $displayed_groups, $users, $filters, $columns );
	
	$get_empty_items = apply_filters( 'bookacti_user_booking_list_empty_items', false, $booking_list_items, $bookings, $booking_groups, $bookings_per_group, $displayed_groups, $users, $filters, $columns );
	foreach( $booking_list_items as $booking_id => $booking_list_item ) {
		// Turn the action array to HTML
		if( empty( $booking_list_item[ 'refund_actions' ] ) && isset( $booking_list_item[ 'actions' ][ 'refund' ] ) ) { unset( $booking_list_item[ 'actions' ][ 'refund' ] ); }
		if( $booking_list_item[ 'booking_type' ] === 'group' ) {
			$booking_list_items[ $booking_id ][ 'actions' ] = ! empty( $booking_list_item[ 'actions' ] ) ? bookacti_get_booking_group_actions_html( $booking_groups[ $booking_list_item[ 'booking_group_id_raw' ] ], $bookings_per_group[ $booking_list_item[ 'booking_id_raw' ] ], 'front', $booking_list_item[ 'actions' ] ) : '';
		} else if( $booking_list_item[ 'booking_type' ] === 'single' ) {
			$booking_list_items[ $booking_id ][ 'actions' ] = ! empty( $booking_list_item[ 'actions' ] ) ? bookacti_get_booking_actions_html( $bookings[ $booking_list_item[ 'booking_id_raw' ] ], 'front', $booking_list_item[ 'actions' ] ) : '';
		}
		
		// Remove the booking item if all the desired columns are empty
		$current_user_row = is_numeric( $booking_list_item[ 'customer_id' ] ) && $current_user_id === intval( $booking_list_item[ 'customer_id' ] );
		$empty_row = true;
		foreach( $columns as $column ) {
			$not_empty = ! empty( $booking_list_item[ $column ] ) || ( isset( $booking_list_item[ $column ] ) && in_array( $booking_list_item[ $column ], array( '0', 0 ), true ) );
			// Replace private column value
			// and make sure 'customer_id' and 'customer_display_name' columns don't display the customer email if not allowed
			$private_column = false;
			if( ! $display_private_columns && ! $current_user_row && $not_empty 
				&& ( in_array( $column, $private_columns, true ) 
					|| ( $is_customer_email_pivate && in_array( $column, array( 'customer_id', 'customer_display_name' ), true ) && is_email( $booking_list_item[ $column ] ) )
					|| ( $is_customer_id_pivate && $column === 'customer_display_name' && is_numeric( $booking_list_item[ 'customer_id' ] ) ) && strpos( $booking_list_item[ $column ], $booking_list_item[ 'customer_id' ] ) !== false
					) 
			) {
				$private_column = true;
				$booking_list_items[ $booking_id ][ $column ] = '<span class="bookacti-private-value">' . esc_html__( 'Private data', 'booking-activities' ) . '</span>';
			}
			if( $not_empty && ! $private_column ) {
				$empty_row = false;
				if( $display_private_columns ) { break; }
			}
		}
		if( $empty_row ) { 
			if( ! $get_empty_items ) {
				unset( $booking_list_items[ $booking_id ] );
			} else {
				if( empty( $booking_list_items[ $booking_id ][ 'tr_class' ] ) ) { $booking_list_items[ $booking_id ][ 'tr_class' ] = ''; }
				$booking_list_items[ $booking_id ][ 'tr_class' ] .= ' bookacti-empty-row';
			}
		}
	}
	
	return $booking_list_items;
}


/**
 * Display a booking list
 * @since 1.7.6
 * @version 1.16.33
 * @param array $filters
 * @param array $columns
 * @param int $per_page
 * @return string
 */
function bookacti_get_user_booking_list( $filters, $columns = array(), $per_page = 10 ) {
	if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
	
	// Set a counter of bookings list displayed on the same page
	if( empty( $GLOBALS[ 'bookacti_booking_list_count' ] ) ) { $GLOBALS[ 'bookacti_booking_list_count' ] = 0; }
	global $bookacti_booking_list_count;
	++$bookacti_booking_list_count;
	
	// Total number of bookings to display
	$bookings_nb = bookacti_get_number_of_booking_rows( $filters );
	
	// Pagination
	if( ! $per_page ) { $per_page = 9999; }
	$page_nb               = ! empty( $_GET[ 'bookacti_booking_list_paged_' . $bookacti_booking_list_count ] ) ? intval( $_GET[ 'bookacti_booking_list_paged_' . $bookacti_booking_list_count ] ) : 1;
	$page_max              = ceil( $bookings_nb / intval( $per_page ) );
	$filters[ 'per_page' ] = intval( $per_page );
	$filters[ 'offset' ]   = ( $page_nb - 1 ) * $filters[ 'per_page' ];
	
	$booking_list_items = bookacti_get_user_booking_list_items( $filters, $columns );
	
	ob_start();
	?>
	<div id='bookacti-user-booking-list-<?php echo $bookacti_booking_list_count; ?>' class='bookacti-user-booking-list <?php echo empty( $booking_list_items ) ? 'bookacti-no-items' : '' ?>' data-user-id='<?php echo $filters[ 'user_id' ] ? $filters[ 'user_id' ] : ''; ?>' >
		<?php
			echo bookacti_get_user_booking_list_table_html( $booking_list_items, $columns );
		
		if( $page_max > 1 ) { ?>
		<div class='bookacti-user-booking-list-pagination'>
		<?php
			if( $page_nb > 1 ) {
			?>
				<span class='bookacti-user-booking-list-previous-page'>
					<a href='<?php echo esc_url( add_query_arg( 'bookacti_booking_list_paged_' . $bookacti_booking_list_count, ( $page_nb - 1 ) ) ); ?>' class='button'>
						<?php esc_html_e( 'Previous', 'booking-activities' ); ?>
					</a>
				</span>
			<?php
			}
			?>
			<span class='bookacti-user-booking-list-current-page'>
				<span class='bookacti-user-booking-list-page-counter'><strong><?php echo $page_nb; ?></strong><span> / </span><em><?php echo $page_max; ?></em></span>
				<span class='bookacti-user-booking-list-total-bookings'><?php /* translators: %s is the number of bookings */ echo esc_html( sprintf( _n( '%s booking', '%s bookings', $bookings_nb, 'booking-activities' ), $bookings_nb ) ); ?></span>
			</span>
			<?php
			if( $page_nb < $page_max ) {
			?>
				<span class='bookacti-user-booking-list-next-page'>
					<a href='<?php echo esc_url( add_query_arg( 'bookacti_booking_list_paged_' . $bookacti_booking_list_count, ( $page_nb + 1 ) ) ); ?>' class='button'>
						<?php esc_html_e( 'Next', 'booking-activities' ); ?>
					</a>
				</span>
			<?php
			}
		?>
		</div>
		<?php } ?>
	</div>
	<?php
	
	// Include bookings dialogs if they are not already
	if( in_array( 'actions', $columns, true ) ) {
		include_once( WP_PLUGIN_DIR . '/' . BOOKACTI_PLUGIN_NAME . '/view/view-bookings-dialogs.php' );
	}
	
	return apply_filters( 'bookacti_user_booking_list_html', ob_get_clean(), $booking_list_items, $columns, $filters, $per_page );
}


/**
 * Get the booking list table HTML
 * @since 1.8.0
 * @version 1.16.33
 * @param array $booking_items
 * @param array $columns
 * @return array
 */
function bookacti_get_user_booking_list_table_html( $booking_items, $columns = array() ) {
	if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
	ob_start();
	?>
	<table class='bookacti-user-booking-list-table <?php echo empty( $booking_items ) ? 'bookacti-no-items' : '' ?>'>
		<thead>
			<tr>
			<?php
				$columns_labels = bookacti_get_user_booking_list_columns_labels();
				foreach( $columns as $column_id ) {
				?>
					<th class='bookacti-column-<?php echo sanitize_title_with_dashes( $column_id ); ?>' >
						<div class='bookacti-column-title-<?php echo $column_id; ?>' >
							<?php echo ! empty( $columns_labels[ $column_id ] ) ? esc_html( $columns_labels[ $column_id ] ) : $column_id; ?>
						</div>
					</th>
				<?php
				} 
			?>
			</tr>
		</thead>
		<tbody>
		<?php
			echo bookacti_get_user_booking_list_rows( $booking_items, $columns );
		?>
		</tbody>
	</table>
	<?php
	return apply_filters( 'bookacti_user_booking_list_table_html', ob_get_clean(), $booking_items, $columns );
}


/**
 * Display booking list rows
 * @since 1.7.6
 * @version 1.16.0
 * @param array $booking_list_items
 * @param array $columns
 * @return string
 */
function bookacti_get_user_booking_list_rows( $booking_list_items, $columns = array() ) {
	if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
	
	$columns_labels = bookacti_get_user_booking_list_columns_labels();
	
	ob_start();
	
	// If there are no booking rows
	if( empty( $booking_list_items ) ) {
	?>
		<tr>
			<td colspan='<?php echo esc_attr( count( $columns ) ); ?>'>
				<?php esc_html_e( 'No bookings found.', 'booking-activities' ); ?>
			</td>
		</tr>
	<?php
	} 
	
	// Display rows
	else {
		foreach( $booking_list_items as $list_item ) {
			$booking_type = ! empty( $list_item[ 'booking_type' ] ) && $list_item[ 'booking_type' ] === 'group' ? 'group' : 'single';
			$tr_data  = ! empty( $list_item[ 'booking_id_raw' ] ) && $booking_type !== 'group' ? ' data-booking-id="' . $list_item[ 'booking_id_raw' ] . '"' : '';
			$tr_data .= ! empty( $list_item[ 'booking_group_id_raw' ] ) ? ' data-booking-group-id="' . $list_item[ 'booking_group_id_raw' ] . '"' : '';
		?>
			<tr class='<?php echo ! empty( $list_item[ 'tr_class' ] ) ? $list_item[ 'tr_class' ] : ''; ?>' <?php echo $tr_data; ?>>
			<?php
				foreach( $columns as $column_id ) {
					$value        = isset( $list_item[ $column_id ] ) && ( is_string( $list_item[ $column_id ] ) || is_numeric( $list_item[ $column_id ] ) ) ? $list_item[ $column_id ] : '';
					$class_empty  = empty( $value ) ? 'bookacti-empty-column' : '';
					$class_group  = $list_item[ 'booking_type' ] === 'group' ? 'bookacti-booking-group-' . $column_id : '';
					$column_label = ! empty( $columns_labels[ $column_id ] ) ? $columns_labels[ $column_id ] : $column_id;
				?>
					<td data-column-id='<?php echo esc_attr( $column_id ); ?>' data-column-label='<?php echo esc_attr( $column_label ); ?>' class='bookacti-column-<?php echo $column_id . ' ' . $class_empty; ?>' >
						<div class='bookacti-booking-<?php echo $column_id . ' ' . $class_group; ?>' >
							<?php echo $value; ?>
						</div>
					</td>
				<?php
				}
			?>
			</tr>
		<?php
		}
	}
	
	return apply_filters( 'bookacti_user_booking_list_rows_html', ob_get_clean(), $booking_list_items, $columns );
}


/**
 * Get some booking list rows according to filters
 * @since 1.7.4
 * @version 1.16.0
 * @param string $context
 * @param array $filters
 * @param array $columns
 * @return string
 */
function bookacti_get_booking_list_rows_according_to_context( $context = 'user_booking_list', $filters = array(), $columns = array() ) {
	// Switch language
	$lang_switched = ! empty( $_REQUEST[ 'locale' ] ) ? bookacti_switch_locale( sanitize_title_with_dashes( $_REQUEST[ 'locale' ] ) ) : false;
	
	$rows = '';
	if( $context === 'admin_booking_list' ) {
		$Bookings_List_Table = new Bookings_List_Table();
		$Bookings_List_Table->prepare_items( $filters, true );
		$rows = $Bookings_List_Table->get_rows_or_placeholder();
	} else if( $context === 'user_booking_list' ) {
		if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
		$filters    = bookacti_format_booking_filters( $filters );
		$list_items = bookacti_get_user_booking_list_items( $filters, $columns );
		$rows       = bookacti_get_user_booking_list_rows( $list_items, $columns );
	}
	
	$rows = apply_filters( 'booking_list_rows_according_to_context', $rows, $context, $filters, $columns );
	
	// Restore language
	if( $lang_switched ) { bookacti_restore_locale(); }
	
	return $rows;
}


/**
 * Get booking lists by event
 * @since 1.8.0
 * @param array $filters_raw
 * @param array $columns
 * @param array $atts Booking system attributes
 * @return array
 */
function bookacti_get_events_booking_lists( $filters_raw, $columns = array(), $atts = array() ) {
	// Sanitize bookings filters
	$default_filters = array( 
		'status'     => array( 'pending', 'booked', 'delivered' ),
		'order_by'   => array( 'event_id', 'event_start' ), 
		'order'      => 'desc',
		'group_by'   => 'none',
		'fetch_meta' => true
	);
	$sanitized_filters = bookacti_format_booking_filters( wp_parse_args( $filters_raw, $default_filters ) );
	if( ! empty( $filters_raw[ 'display_private_columns' ] ) ) { $sanitized_filters[ 'display_private_columns' ] = 1; }
	
	// Allow plugins to change filters
	$filters = apply_filters( 'bookacti_events_booking_lists_filters', $sanitized_filters, $filters_raw, $columns, $atts );
	
	// Set default columns
	if( ! $columns ) { $columns = bookacti_get_event_booking_list_default_columns(); }
	
	// Get the bookings
	$booking_items = bookacti_get_user_booking_list_items( $filters, $columns );

	// Get the booking lists
	$booking_lists = array();
	if( $booking_items ) {
		// Order the bookings per event
		$ordered_items = array();
		foreach( $booking_items as $booking_item ) {
			$index = $booking_item[ 'event_id' ] . '_' . $booking_item[ 'start_date_raw' ];
			if( ! isset( $ordered_items[ $index ] ) ) { $ordered_items[ $index ] = array(); }
			$ordered_items[ $index ][] = $booking_item;
		}
		// Build the booking list for each event
		foreach( $booking_items as $booking_item ) {
			$index = $booking_item[ 'event_id' ] . '_' . $booking_item[ 'start_date_raw' ];
			if( empty( $ordered_items[ $index ] ) ) { continue; }
			if( ! isset( $booking_lists[ $booking_item[ 'event_id' ] ] ) ) { $booking_lists[ $booking_item[ 'event_id' ] ] = array(); }
			
			$event_booking_list = apply_filters( 'bookacti_event_booking_list_table_html', bookacti_get_user_booking_list_table_html( $ordered_items[ $index ], $columns ), $booking_item[ 'event_id' ], $booking_item[ 'start_date_raw' ], $filters, $filters_raw, $columns, $atts );
			
			$booking_lists[ $booking_item[ 'event_id' ] ][ $booking_item[ 'start_date_raw' ] ] = $event_booking_list;
		}
	}
	
	return apply_filters( 'bookacti_events_booking_lists', $booking_lists, $filters, $filters_raw, $columns, $atts );
}