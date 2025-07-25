<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GLOBAL

/**
 * Check if plugin is active
 * 
 * @param string $plugin_path_and_name
 * @return boolean
 */
function bookacti_is_plugin_active( $plugin_path_and_name ) {
	if( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	return is_plugin_active( $plugin_path_and_name );
}


/**
 * Get login link or URL
 * @since 1.16.33
 * @param boolean|string $redirect
 * @param boolean $url_only
 * @return string
 */
function bookacti_get_login_link( $redirect = true, $url_only = false ) {
	$redirect_url = '';
	if( $redirect ) {
		$redirect_url = is_string( $redirect ) ? $redirect : home_url( $_SERVER[ 'REQUEST_URI' ] );
	}
	
	$link = wp_login_url( $redirect_url );
	
	if( ! $url_only ) {
		$link = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Log in', 'booking-activities' ) . '</a>';
	}
	
	return apply_filters( 'bookacti_login_link', $link, $redirect, $url_only );
}


/**
 * Display an admin notice
 * @since 1.7.18
 * @param array $array composed of a type and a message
 * @param string $action Name of the filter to allow third-party modifications
 */
function bookacti_display_admin_notice( $array, $action = '' ) {
	if( empty( $array[ 'type' ] ) ) { $array[ 'type' ] = 'error'; }
	if( empty( $array[ 'message' ] ) ) { $array[ 'message' ] = esc_html__( 'An error occurred, please try again.', 'booking-activities' ); }
	$notice = apply_filters( 'bookacti_display_admin_notice_' . $action, $array );
	?>
		<div class='notice is-dismissible bookacti-form-notice notice-<?php echo $notice[ 'type' ]; ?>' ><p><?php echo $notice[ 'message' ]; ?></p></div>
	<?php
}


/**
 * Send a filtered array via json during an ajax process
 * @since 1.5.0
 * @version 1.5.3
 * @param array $array Array to encode as JSON, then print and die.
 * @param string $action Name of the filter to allow third-party modifications
 */
function bookacti_send_json( $array, $action = '' ) {
	if( empty( $array[ 'status' ] ) ) { $array[ 'status' ] = 'failed'; }
	$response = apply_filters( 'bookacti_send_json_' . $action, $array );
	wp_send_json( $response );
}


/**
 * Send a filtered array via json to stop an ajax process running with an invalid nonce
 * @since 1.5.0
 * @version 1.9.0
 * @param string $action Name of the filter to allow third-party modifications
 */
function bookacti_send_json_invalid_nonce( $action = '' ) {
	$return_array = array( 
		'status'  => 'failed', 
		'error'   => 'invalid_nonce',
		'action'  => $action, 
		'message' => esc_html__( 'Invalid nonce.', 'booking-activities' ) . ' ' . esc_html__( 'Please reload the page and try again.', 'booking-activities' )
	);
	bookacti_send_json( $return_array, $action );
}


/**
 * Send a filtered array via json to stop a not allowed an ajax process
 * @since 1.5.0
 * @version 1.9.0
 * @param string $action Name of the filter to allow third-party modifications
 */
function bookacti_send_json_not_allowed( $action = '' ) {
	$return_array = array( 
		'status'  => 'failed', 
		'error'   => 'not_allowed', 
		'action'  => $action, 
		'message' => esc_html__( 'You are not allowed to do that.', 'booking-activities' )
	);
	bookacti_send_json( $return_array, $action );
}


/**
 * Write logs to log files
 * @version 1.16.34
 * @param string $message
 * @param string $filename
 * @return boolean
 */
function bookacti_log( $message = '', $filename = 'debug' ) {
	if( is_array( $message ) || is_object( $message ) ) { 
		$message = print_r( $message, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}

	if( is_bool( $message ) ) { 
		$message = $message ? 'true' : 'false';
	}

	$file = BOOKACTI_PATH . '/log/' . $filename . '.log'; 

	$time = gmdate( 'Y-m-d H:i:s' );
	$log  = $time . ' - ' . $message . PHP_EOL;

	$write = error_log( $log, 3, $file ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

	return $write;
}


/**
 * Increase the max_execution_time and the memory_limit
 * @since 1.7.0
 * @version 1.10.0
 * @param string $context
 */
function bookacti_increase_max_execution_time( $context = '' ) {
	$max_execution_time = apply_filters( 'bookacti_increased_max_execution_time', 600, $context ); // Default to 10mn
	$memory_limit = apply_filters( 'bookacti_increased_memory_limit', '512M', $context ); // Default to 512MB
	ini_set( 'max_execution_time', $max_execution_time );
	ini_set( 'memory_limit', $memory_limit );
	do_action( 'bookacti_increase_max_execution_time', $context );
}



/**
 * Get a substring between two specific strings
 * @since 1.7.10
 * @version 1.16.9
 * @param string $string
 * @param string $start
 * @param string $end
 * @return string
 */
function bookacti_get_string_between( $string, $start = '', $end = '' ) {
	$ini = $start !== '' ? strpos( $string, $start ) : false;
	$start_not_found = false;
	if( $ini === false ) { $ini = 0; $start_not_found = true; }

	$ini += strlen( $start );
	$len = $end !== '' && $ini <= strlen( $string ) ? strpos( $string, $end, $ini ) : false;
	$end_not_found = false;
	if( $len === false ) { $len = strlen( $string ); $end_not_found = true; }
	else { $len -= $ini; }
	
	if( ( $start !== '' && $start_not_found ) || ( $end !== '' && $end_not_found ) ) {
		return '';
	}
	
	return substr( $string, $ini, $len );
}



/**
 * Encrypt a string
 * @since 1.7.15
 * @version 1.16.0
 * @param string $string
 * @param $context $string
 * @return string
 */
function bookacti_encrypt( $string, $context = '' ) {
	$secret_key = get_option( 'bookacti_secret_key' );
	$secret_iv  = get_option( 'bookacti_secret_iv' );

	if( ! $secret_key ) { $secret_key = md5( microtime().rand() ); update_option( 'bookacti_secret_key', $secret_key ); }
	if( ! $secret_iv )  { $secret_iv  = md5( microtime().rand() ); update_option( 'bookacti_secret_iv', $secret_iv ); }

	$output = $string;
	$encrypt_method = 'AES-256-CBC';
	$key = hash( 'sha256', $context . $secret_key );
	$iv  = substr( hash( 'sha256', $context . $secret_iv ), 0, 16 );

	if( function_exists( 'openssl_encrypt' ) && version_compare( phpversion(), '5.3.3', '>=' ) ) {
		$output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
	}

	if( ! $output ) { return $string; }

	return $output;
}


/**
 * Dencrypt a string
 * @since 1.7.15
 * @version 1.16.0
 * @param string $string
 * @param string $context
 * @return string
 */
function bookacti_decrypt( $string, $context = '' ) {
	$secret_key = get_option( 'bookacti_secret_key' );
	$secret_iv  = get_option( 'bookacti_secret_iv' );

	if( ! $secret_key || ! $secret_iv ) { return $string; }

	$output = $string;
	$encrypt_method = 'AES-256-CBC';
	$key = hash( 'sha256', $context . $secret_key );
	$iv  = substr( hash( 'sha256', $context . $secret_iv ), 0, 16 );

	if( function_exists( 'openssl_decrypt' ) && version_compare( phpversion(), '5.3.3', '>=' ) ) {
		$output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
	}

	if( ! $output ) { return $string; }

	return $output;
}


/**
 * Generate CSV file
 * @since 1.8.0
 * @version 1.16.37
 * @param array $items
 * @param array $headers
 * @return string
 */
function bookacti_generate_csv( $items, $headers = array() ) {
	if( ! $headers && ! $items ) { return ''; }
	
	// Get headers from first item if not given
	if( ! $headers ) { $items = array_values( $items ); $headers = array_keys( $items[ 0 ] ); }
	if( ! $headers ) { return ''; }
	
	ob_start();

	// Display headers
	$count = 0;
	foreach( $headers as $title ) {
		if( $count ) { echo ','; }
		++$count;
		$title = html_entity_decode( strip_tags( $title ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' );
		if( strpos( $title, ',' ) !== false || strpos( $title, PHP_EOL ) !== false ) { $title = '"' . str_replace( '"', '""', $title ) . '"'; }
		echo $title;
	}

	// Display rows
	foreach( $items as $item ) {
		echo PHP_EOL;
		$count = 0;
		foreach( $headers as $column_name => $title ) {
			if( $count ) { echo ','; }
			++$count;
			if( ! isset( $item[ $column_name ] ) ) { continue; }
			$content = html_entity_decode( strip_tags( $item[ $column_name ] ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' );
			if( strpos( $content, ',' ) !== false || strpos( $content, PHP_EOL ) !== false ) { $content = '"' . str_replace( '"', '""', $content ) . '"'; }
			echo $content;
		}
	}

	return ob_get_clean();
}


/**
 * Generate iCal file
 * @since 1.8.0
 * @version 1.12.4
 * @param array $vevents
 * @param array $vcalendar
 * @return string
 */
function bookacti_generate_ical( $vevents, $vcalendar = array() ) {
	if( ! $vevents ) { return ''; }
	
	// Set default vcalendar properties
	$site_url       = home_url();
	$site_url_array = parse_url( $site_url );
	$site_host      = $site_url_array[ 'host' ];
	
	$timezone       = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$timezone_obj   = new DateTimeZone( $timezone );
	$current_time   = new DateTime( 'now', $timezone_obj );
	$current_time->setTimezone( new DateTimeZone( 'UTC' ) );
	$now_formatted  = $current_time->format( 'Ymd\THis\Z' );
	
	$site_name      = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$calname        = $site_name;
	/* translators: %1$s is a link to Booking Activities website. %2$s is the site URL. */
	$caldesc        = sprintf( esc_html__( 'This calendar was generated by %1$s from %2$s.' ), 'Booking Activities (https://booking-activities.fr)', $site_name . ' (' . $site_url . ')' );
	
	if( ! empty( $vcalendar[ 'X-WR-CALNAME' ] ) ) { $vcalendar[ 'X-WR-CALNAME' ] .= ' (' . $calname . ')'; }
	if( ! empty( $vcalendar[ 'X-WR-CALDESC' ] ) ) { $vcalendar[ 'X-WR-CALDESC' ] .= ' ' . $caldesc; }
	
	$vcalendar_default = apply_filters( 'bookacti_ical_vcalendar_default', array(
		'PRODID'        => '-//Booking Activities//Booking Activities Calendar//EN',
		'VERSION'       => '2.0',
		'CALSCALE'      => 'GREGORIAN',
		'METHOD'        => 'PUBLISH',
		'X-WR-CALNAME'  => $calname,
		'X-WR-TIMEZONE' => $timezone,
		'X-WR-CALDESC'  => $caldesc
	));
	
	foreach( $vcalendar_default as $property => $value ) {
		$vcalendar[ $property ] = isset( $vcalendar[ $property ] ) ? bookacti_sanitize_ical_property( $vcalendar[ $property ], $property ) : $value;
	}
	
	// Compulsory vevent properties
	$vevent_default = array( 'UID' => 0, 'DTSTAMP' => $now_formatted, 'DTSTART' => $now_formatted );
	
	ob_start();
	
	?>
	BEGIN:VCALENDAR
	<?php
		foreach( $vcalendar as $property => $value ) {
			if( $value === '' ) { continue; }
			echo $property . ':' . $value . PHP_EOL;
		}
		do_action( 'bookacti_ical_vcalendar_before', $vevents, $vcalendar );
		
		foreach( $vevents as $vevent ) {
			// Add compulsory properties
			if( empty( $vevent[ 'UID' ] ) ) { ++$vevent_default[ 'UID' ]; }
			$vevent = array_merge( $vevent_default, $vevent );
			$vevent[ 'UID' ] = bookacti_sanitize_ical_property( $vevent[ 'UID' ] . '@' . $site_host, 'UID' );
		?>
			BEGIN:VEVENT
			<?php
				foreach( $vevent as $property => $value ) {
					if( $value === '' ) { continue; }
					echo $property . ':' . $value . PHP_EOL;
				}
				do_action( 'bookacti_ical_vevent_after', $vevent, $vevents, $vcalendar );
			?>
			END:VEVENT
		<?php
		}

		do_action( 'bookacti_ical_vcalendar_after', $vevents, $vcalendar );
	
	?>
	END:VCALENDAR
	<?php
	
	// Remove tabs at the beginning and at the end of each new lines
	return preg_replace( '/^\t+|\t+$/m', '', ob_get_clean() );
}




// DATABASE

/**
 * Get mySQL or MariaDB version
 * @since 1.15.9
 */
function bookacti_get_db_min_versions() {
	$min_versions = array(
		'mysql'   => '5.7.22',
		'mariadb' => '10.5.4'
	);
	return apply_filters( 'bookacti_db_min_versions', $min_versions );
}


/**
 * Get mySQL or MariaDB version
 * @global wpdb $wpdb
 * @since 1.15.9
 */
function bookacti_get_db_info() {
	global $wpdb;
	$db_server_info = $wpdb->db_server_info();
	$is_mariadb     = str_contains( $db_server_info, 'MariaDB' );
	$db_version     = $is_mariadb ? str_replace( '5.5.5-', '', $db_server_info ) : $db_server_info;
	$db_version     = preg_replace( '/[^0-9.].*/', '', $db_version );
	$db_info        = array(
		'type'    => $is_mariadb ? 'mariadb' : 'mysql',
		'version' => $db_version
	);
	return apply_filters( 'bookacti_db_info', $db_info, $db_server_info );
}


/**
 * Check if database version is outdated
 * @since 1.15.9
 */
function bookacti_is_db_version_outdated() {
	$min_versions  = bookacti_get_db_min_versions();
	$db_info       = bookacti_get_db_info();
	$min_version   = isset( $min_versions[ $db_info[ 'type' ] ] ) ? $min_versions[ $db_info[ 'type' ] ] : '';
	$is_up_to_date = $min_version && $db_info[ 'version' ] && version_compare( $db_info[ 'version' ], $min_version, '>=' );
	return ! $is_up_to_date;
}




// JS variables

/**
 * Get the variables used with javascript
 * @since 1.8.0
 * @version 1.16.2
 * @return array
 */
function bookacti_get_js_variables() {
	$timezone_name          = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$timezone               = new DateTimeZone( $timezone_name );
	$current_datetime       = new DateTime( 'now', $timezone );
	$current_datetime_utc   = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
	$can_edit_bookings      = current_user_can( 'bookacti_edit_bookings' );
	$messages               = bookacti_get_messages();
	$fc_init_view_threshold = intval( bookacti_get_setting_value( 'bookacti_general_settings', 'default_calendar_view_threshold' ) );
	
	/**
	 * /!\ 
	 * Don't transtate strings here, read this documentation to learn how to translate booking activities into your language:
	 * https://booking-activities.fr/en/faq/translate-booking-activities-into-my-language/
	 */
	
	$bookacti_localized = array(
		// ERRORS
		'error'                              => esc_html__( 'An error occurred.', 'booking-activities' ),
		'error_select_event'                 => esc_html__( 'You haven\'t selected any event. Please select an event.', 'booking-activities' ),
		'error_corrupted_event'              => esc_html__( 'There is an inconsistency in the selected events data, please select an event and try again.', 'booking-activities' ),
		/* translators: %1$s is the quantity the user want. %2$s is the available quantity. */
		'error_less_avail_than_quantity'     => esc_html__( 'You want to make %1$s bookings but only %2$s are available for the selected events. Please choose another event or decrease the quantity.', 'booking-activities' ),
		'error_quantity_inf_to_0'            => esc_html__( 'The amount of desired bookings is less than or equal to 0. Please increase the quantity.', 'booking-activities' ),
		'error_not_allowed'                  => esc_html__( 'You are not allowed to do that.', 'booking-activities' ),
		'error_user_not_logged_in'           => esc_html__( 'You are not logged in. Please create an account and log in first.', 'booking-activities' ),
		'error_password_not_strong_enough'   => esc_html__( 'Your password is not strong enough.', 'booking-activities' ),
		'select2_search_placeholder'         => esc_html__( 'Please enter {nb} or more characters.', 'booking-activities' ),

		// OTHERS
		'loading'                            => esc_html__( 'Loading', 'booking-activities' ),
		'one_person_per_booking'             => esc_html__( 'for one person', 'booking-activities' ),
		/* translators: %1$s is the number of people who can enjoy the activity with one booking */
		'n_people_per_booking'               => esc_html__( 'for %1$s people', 'booking-activities' ),
		/* translators: This particle is used right after the quantity of bookings. Put the singular here. E.g.: 1 booking. */
		'booking'                            => esc_html__( 'booking', 'booking-activities' ),
		/* translators: This particle is used right after the quantity of bookings. Put the plural here. E.g.: 2 bookings. */
		'bookings'                           => esc_html__( 'bookings', 'booking-activities' ),
		/* translators: Button label to go to a specific date in the calendar */
		'go_to_button'                       => esc_html__( 'Go to', 'booking-activities' ),

		// VARIABLES
		'ajaxurl'                            => admin_url( 'admin-ajax.php' ),
		'nonce_query_select2_options'        => wp_create_nonce( 'bookacti_query_select2_options' ),
		
		'fullcalendar_timezone'              => 'UTC',
		'fullcalendar_locale'                => bookacti_convert_wp_locale_to_fc_locale( bookacti_get_current_lang_code( true ) ),
		'current_lang_code'                  => bookacti_get_current_lang_code(),
		'current_locale'                     => bookacti_get_current_lang_code( true ),

		'available_booking_methods'          => array_keys( bookacti_get_available_booking_methods() ),
		'booking_system_attributes_keys'     => array_keys( bookacti_get_booking_system_default_attributes() ),

		'event_tiny_height'                  => apply_filters( 'bookacti_event_tiny_height', 32 ),
		'event_small_height'                 => apply_filters( 'bookacti_event_small_height', 75 ),
		'event_narrow_width'                 => apply_filters( 'bookacti_event_narrow_width', 70 ),
		'event_wide_width'                   => apply_filters( 'bookacti_event_wide_width', 250 ),
		'calendar_width_classes'             => apply_filters( 'bookacti_calendar_width_classes', array( max( $fc_init_view_threshold, 350 ) => 'bookacti-calendar-narrow-width', 350 => 'bookacti-calendar-minimal-width' ) ),

		'started_events_bookable'            => bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' ) ? 1 : 0,
		'started_groups_bookable'            => bookacti_get_setting_value( 'bookacti_general_settings', 'started_groups_bookable' ) ? 1 : 0,
		'event_load_interval'                => bookacti_get_setting_value( 'bookacti_general_settings', 'event_load_interval' ),
		'initial_view_threshold'             => $fc_init_view_threshold,
		'event_touch_press_delay'            => 350,

		'date_format'                        => $messages[ 'date_format_short' ][ 'value' ],
		'date_format_long'                   => $messages[ 'date_format_long' ][ 'value' ],
		'time_format'                        => $messages[ 'time_format' ][ 'value' ],
		'dates_separator'                    => $messages[ 'dates_separator' ][ 'value' ],
		'date_time_separator'                => $messages[ 'date_time_separator' ][ 'value' ],

		'single_event'                       => $messages[ 'choose_group_dialog_single_event' ][ 'value' ],
		'selected_event'                     => $messages[ 'selected_event' ][ 'value' ],
		'selected_events'                    => $messages[ 'selected_events' ][ 'value' ],
		'no_events'                          => $messages[ 'no_events' ][ 'value' ],
		'avail'                              => $messages[ 'avail' ][ 'value' ],
		'avails'                             => $messages[ 'avails' ][ 'value' ],
		'not_bookable'                       => $messages[ 'not_bookable' ][ 'value' ],
		'hide_availability_fixed'            => apply_filters( 'bookacti_hide_availability_fixed', 0 ), // Threshold above which availability is masked. 0 to always show availability.

		'dialog_button_ok'                   => esc_html__( 'OK', 'booking-activities' ),
		'dialog_button_send'                 => esc_html__( 'Send', 'booking-activities' ),
		'dialog_button_cancel'               => $messages[ 'cancel_dialog_button' ][ 'value' ],
		'dialog_button_cancel_booking'       => $messages[ 'cancel_booking_dialog_button' ][ 'value' ],
		'dialog_button_reschedule'           => $messages[ 'reschedule_dialog_button' ][ 'value' ],
		'dialog_button_refund'               => $can_edit_bookings ? esc_html_x( 'Refund', 'Button label to trigger the refund action', 'booking-activities' ) : $messages[ 'refund_dialog_button' ][ 'value' ],

		'plugin_path'                        => plugins_url() . '/' . BOOKACTI_PLUGIN_NAME,
		'is_admin'                           => is_admin() ? 1 : 0,
		'current_user_id'                    => get_current_user_id(),
		'current_time'                       => $current_datetime->format( 'Y-m-d H:i:s' ),

		'calendar_localization'              => bookacti_get_setting_value( 'bookacti_general_settings', 'calendar_localization' ),
		'wp_date_format'                     => get_option( 'date_format' ),
		'wp_time_format'                     => get_option( 'time_format' ),
		'wp_start_of_week'                   => get_option( 'start_of_week' ),
		
		'price_format'                       => bookacti_get_price_format(),
		'price_currency_symbol'              => bookacti_get_setting_value( 'bookacti_general_settings', 'price_currency_symbol' ),
		'price_thousand_separator'           => bookacti_get_setting_value( 'bookacti_general_settings', 'price_thousand_separator' ),
		'price_decimal_separator'            => bookacti_get_setting_value( 'bookacti_general_settings', 'price_decimal_separator' ),
		'price_decimal_number'               => bookacti_get_setting_value( 'bookacti_general_settings', 'price_decimals_number' )
	);
	
	// Strings for backend only
	if( is_admin() ) {
		$bookacti_localized_backend = array(
			'nonce_dismiss_5stars_rating_notice' => wp_create_nonce( 'bookacti_dismiss_5stars_rating_notice' ),
			'admin_url'                          => admin_url(),
			'is_qtranslate'                      => bookacti_get_translation_plugin() === 'qtranslate',
			'utc_offset'                         => intval( $timezone->getOffset( $current_datetime_utc ) ),
			/* translators: {nb} is an integer alone or a number of items. E.g. "12 selected", or "12 items selected". */
			'nb_selected'                        => esc_html__( '{nb} selected', 'booking-activities' ),
			/* translators: {nb} is a number of items. E.g. "Select all 12 items". */
			'select_all'                         => esc_html__( 'Select all {nb}', 'booking-activities' ),
			'unselect_all'                       => esc_html__( 'Clear selection', 'booking-activities' ),
			'create_new'                         => esc_html__( 'Create new', 'booking-activities' ),
			'edit_id'                            => esc_html_x( 'id', 'An id is a unique identification number', 'booking-activities' ),
			'dialog_button_generate_link'        => esc_html__( 'Generate export link', 'booking-activities' ),
			'dialog_button_reset'                => esc_html__( 'Reset', 'booking-activities' ),
			'dialog_button_delete'               => esc_html__( 'Delete', 'booking-activities' ),
			'error_time_format'                  => esc_html__( 'The time format should be HH:mm where "HH" represents hours and "mm" minutes.', 'booking-activities' ),
			'error_availability_period'          => sprintf( 
				/* translators: %1$s = "At the latest". %2$s = "At the earliest". */
				esc_html__( 'The "%1$s" delay must be higher than the "%2$s" delay.', 'booking-activities' ), 
				esc_html__( 'At the earliest', 'booking-activities' ), 
				esc_html__( 'At the latest', 'booking-activities' )
			),
			'error_closing_before_opening'       => sprintf( 
				/* translators: %1$s = "Opening" or "Start". %2$s = "Closing" or "End". */
				esc_html__( 'The "%1$s" date must be prior to the "%2$s" date.', 'booking-activities' ), 
				esc_html__( 'Opening', 'booking-activities' ), 
				esc_html__( 'Closing', 'booking-activities' )
			),
			'nonce_get_booking_rows'             => wp_create_nonce( 'bookacti_get_booking_rows' )
		);

		// Strings for calendar editor only
		if( bookacti_is_booking_activities_screen( 'booking-activities_page_bookacti_calendars' ) ) {
			$calendar_editor_strings = array(
				'dialog_button_create_activity'      => esc_html__( 'Create Activity', 'booking-activities' ),
				'dialog_button_import_activity'      => esc_html__( 'Import Activity', 'booking-activities' ),
				/* translators: 'unbind' is the process to isolate one (or several) occurrence(s) of a repeated event in order to edit it independently. */
				'dialog_button_unbind'               => esc_html__( 'Unbind', 'booking-activities' ),
				/* translators: 'Move' is the button label to move an event to another date. */
				'dialog_button_move'                 => esc_html__( 'Move', 'booking-activities' ),
				
				'error_end_before_start'             => sprintf( 
					esc_html__( 'The "%1$s" date must be prior to the "%2$s" date.', 'booking-activities' ), 
					esc_html__( 'Start', 'booking-activities' ), 
					esc_html__( 'End', 'booking-activities' )
				),
				'error_on_a_form_field'              => esc_html__( 'There is an error on one of the form fields.', 'booking-activities' ),
				'error_fill_field'                   => esc_html__( 'Please fill this field.', 'booking-activities' ),
				'error_invalid_value'                => esc_html__( 'Please select a valid value.', 'booking-activities' ),
				'error_repeat_period_not_set'        => esc_html__( 'The repetition period is not set.', 'booking-activities' ),
				'error_repeat_end_before_begin'      => esc_html__( 'The repetition period cannot end before it started.', 'booking-activities' ),
				'error_repeat_start_before_template' => esc_html__( 'The repetition period should not start before the beginning date of the calendar.', 'booking-activities' ),
				'error_repeat_end_after_template'    => esc_html__( 'The repetition period should not end after the end date of the calendar.', 'booking-activities' ),
				'error_days_sup_to_365'              => esc_html__( 'The number of days should be between 0 and 365.', 'booking-activities' ),
				'error_hours_sup_to_23'              => esc_html__( 'The number of hours should be between 0 and 23.', 'booking-activities' ),
				'error_minutes_sup_to_59'            => esc_html__( 'The number of minutes should be between 0 and 59.', 'booking-activities' ),
				'error_activity_duration_is_null'    => esc_html__( 'The activity duration should not be null.', 'booking-activities' ),
				'error_event_not_btw_from_and_to'    => esc_html__( 'The selected event should be included in the period in which it will be repeated.', 'booking-activities' ),
				'error_freq_not_allowed'             => esc_html__( 'Error: The repetition frequency is not a valid value.', 'booking-activities' ),
				'error_no_templates_for_activity'    => esc_html__( 'The activity must be bound to at least one calendar.', 'booking-activities' ),
				'error_select_at_least_two_events'   => esc_html__( 'You must select at least two events.', 'booking-activities' ),
				'error_no_template_selected'         => esc_html__( 'You must select a calendar first.', 'booking-activities' ),
			);
			$bookacti_localized_backend = array_merge( $bookacti_localized_backend, $calendar_editor_strings );
		}
		$bookacti_localized = array_merge( $bookacti_localized, $bookacti_localized_backend );
	}

	return apply_filters( 'bookacti_translation_array', $bookacti_localized, $messages ); 
}




// ADD-ONS

/**
 * Get the active Booking Activities add-ons
 * @since 1.7.14
 * @version 1.9.0
 * @param string $prefix
 * @param array $exclude
 */
function bookacti_get_active_add_ons( $prefix = '', $exclude = array( 'balau' ) ) {
	$add_ons_data = bookacti_get_add_ons_data( $prefix, $exclude );
	
	$active_add_ons = array();
	foreach( $add_ons_data as $add_on_prefix => $add_on_data ) {
		$add_on_path = $add_on_data[ 'plugin_name' ] . '/' . $add_on_data[ 'plugin_name' ] . '.php';
		if( bookacti_is_plugin_active( $add_on_path ) ) {
			$active_add_ons[ $add_on_prefix ] = $add_on_data;
		}
	}

	return $active_add_ons;
}


/**
 * Get add-on data by prefix
 * @since 1.7.14
 * @version 1.16.40
 * @param string $prefix
 * @param array $exclude
 * @return array
 */
function bookacti_get_add_ons_data( $prefix = '', $exclude = array( 'balau' ) ) {
	$addons_data = array( 
		'badp' => array( 
			'title'       => 'Display Pack', 
			'slug'        => 'display-pack', 
			'plugin_name' => 'ba-display-pack', 
			'end_of_life' => '', 
			'download_id' => 482,
			'min_version' => '1.5.9'
		),
		'banp' => array( 
			'title'       => 'Notification Pack', 
			'slug'        => 'notification-pack', 
			'plugin_name' => 'ba-notification-pack', 
			'end_of_life' => '', 
			'download_id' => 1393,
			'min_version' => '1.3.3'
		),
		'bapap' => array( 
			'title'       => 'Prices and Credits', 
			'slug'        => 'prices-and-credits', 
			'plugin_name' => 'ba-prices-and-credits', 
			'end_of_life' => '', 
			'download_id' => 438,
			'min_version' => '1.8.29'
		),
		'baaf' => array( 
			'title'       => 'Advanced Forms', 
			'slug'        => 'advanced-forms', 
			'plugin_name' => 'ba-advanced-forms', 
			'end_of_life' => '', 
			'download_id' => 2705,
			'min_version' => '1.5.3'
		),
		'baofc' => array( 
			'title'	      => 'Order for Customers', 
			'slug'        => 'order-for-customers', 
			'plugin_name' => 'ba-order-for-customers', 
			'end_of_life' => '', 
			'download_id' => 436,
			'min_version' => '1.2.33'
		),
		'bara' => array( 
			'title'       => 'Resource Availability', 
			'slug'        => 'resource-availability', 
			'plugin_name' => 'ba-resource-availability', 
			'end_of_life' => '', 
			'download_id' => 29249,
			'min_version' => '1.2.3'
		),
		'balau' => array( 
			'title'       => 'Licenses & Updates', 
			'slug'        => 'licenses-and-updates', 
			'plugin_name' => 'ba-licenses-and-updates', 
			'end_of_life' => '',
			'download_id' => 880,
			'min_version' => '1.1.11'
		),
		'bapos' => array( 
			'title'       => 'Points of Sale', 
			'slug'        => 'points-of-sale', 
			'plugin_name' => 'ba-points-of-sale', 
			'end_of_life' => '2021-04-30 23:59:59', // This add-on has been discontinued
			'download_id' => 416,
			'min_version' => '1.0.15'
		)
	);
	
	// Exclude undesired add-ons
	if( $exclude ) { $addons_data = array_diff_key( $addons_data, array_flip( $exclude ) ); }
	
	if( ! $prefix ) { return $addons_data; }

	return isset( $addons_data[ $prefix ] ) ? $addons_data[ $prefix ] : array();
}




// LOCALE

/**
 * Get current translation plugin identifier
 * @version 1.14.0
 * @return string
 */
function bookacti_get_translation_plugin() {
	return apply_filters( 'bookacti_translation_plugin', '' );
}


/**
 * Get current site language
 * @version 1.16.35
 * @global string $bookacti_locale
 * @param boolean $with_locale
 * @return string 
 */
function bookacti_get_current_lang_code( $with_locale = false ) {
	global $bookacti_locale;
	
	// If the language is temporarily switched use $bookacti_locale, else, use get_locale()
	$locale    = $bookacti_locale ? $bookacti_locale : get_locale();
	$i         = strpos( $locale, '_' );
	$lang_code = $with_locale ? $locale : substr( $locale, 0, $i !== false ? $i : null );
	
	$lang_code = apply_filters( 'bookacti_current_lang_code', $lang_code, $with_locale );
	
	if( ! $lang_code ) { 
		$lang_code = $with_locale ? 'en_US' : 'en';
	}
	
	return $lang_code;
}


/**
 * Get site default locale
 * @since 1.12.2
 * @version 1.16.35
 * @param boolean $with_locale Whether to return also country code
 * @return string
 */
function bookacti_get_site_default_locale( $with_locale = true ) {
	$locale = get_locale();
	if( ! $with_locale ) {
		$i      = strpos( $locale, '_' );
		$locale = substr( $locale, 0, $i !== false ? $i : null );
	}
	
	$locale = apply_filters( 'bookacti_site_default_locale', $locale, $with_locale );
	
	if( ! $locale ) {
		$locale = $with_locale ? 'en_US' : 'en';
	}
	
	return $locale;
}


/* 
 * Get user locale, and default to site or current locale
 * @since 1.2.0
 * @version 1.12.4
 * @param int|WP_User $user_id
 * @param string $default 'current' or 'site'
 * @param boolean $country_code Whether to return also country code
 * @return string
 */
function bookacti_get_user_locale( $user_id, $default = 'current', $country_code = true ) {
	if ( 0 === $user_id && function_exists( 'wp_get_current_user' ) ) {
		$user = wp_get_current_user();
	} elseif ( $user_id instanceof WP_User ) {
		$user = $user_id;
	} elseif ( $user_id && is_numeric( $user_id ) ) {
		$user = get_user_by( 'id', $user_id );
	}
	
	if( ! $user ) { $locale = get_locale(); }
	else {
		if( $default === 'site' ) {
			// Get user locale
			$locale = strval( $user->locale );
			// If not set, get site default locale
			if( ! $locale ) {
				$alloptions	= wp_load_alloptions();
				$locale		= ! empty( $alloptions[ 'WPLANG' ] ) ? strval( $alloptions[ 'WPLANG' ] ) : get_locale();
			}
		} else {
			// Get user locale, if not set get current locale
			$locale = $user->locale ? strval( $user->locale ) : get_locale();
		}
	}

	// Remove country code from locale string
	if( ! $country_code ) {
		$_pos = strpos( $locale, '_' );
		if( $_pos !== false ) {
			$locale = substr( $locale, 0, $_pos );
		}
	}

	return apply_filters( 'bookacti_user_locale', $locale, $user_id, $default, $country_code );
}


/* 
 * Get site locale, and default to site or current locale
 * @since 1.2.0
 * @version 1.12.0
 * @param boolean $country_code Whether to return also country code
 * @return string
 */
function bookacti_get_site_locale( $country_code = true ) {
	// Get raw site locale, or current locale by default
	$locale = get_locale();

	// Remove country code from locale string
	if( ! $country_code ) {
		$_pos = strpos( $locale, '_' );
		if( $_pos !== false ) {
			$locale = substr( $locale, 0, $_pos );
		}
	}

	return apply_filters( 'bookacti_site_locale', $locale, $country_code );
}


/**
 * Switch Booking Activities locale
 * @since 1.2.0
 * @version 1.16.35
 * @global string $bookacti_locale
 * @param string $locale
 * @return string|false
 */
function bookacti_switch_locale( $locale ) {
	$callback = apply_filters( 'bookacti_switch_locale_callback', 'switch_to_locale', $locale );
	if( ! function_exists( $callback ) ) { return false; }
	
	// Convert lang code to locale and check if locale is installed
	$locale = bookacti_is_available_locale( $locale );
	if( ! $locale ) { return false; }
	
	$switched_locale = call_user_func( $callback, $locale );
	
	if( $switched_locale ) {
		if( ! is_string( $switched_locale ) ) { $switched_locale = $locale; }
		
		// Set $bookacti_locale global affects 'plugin_locale' hook
		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		global $bookacti_locale;
		$bookacti_locale = $switched_locale;
		
		// Load textdomain on bookacti_locale_switched
		do_action( 'bookacti_locale_switched', $switched_locale );
	}
	
	return $switched_locale;
}


/**
 * Switch Booking Activities locale back to the original
 * @since 1.2.0
 * @version 1.14.0
 * @global string $bookacti_locale
 */
function bookacti_restore_locale() {
	$callback = apply_filters( 'bookacti_restore_locale_callback', 'restore_previous_locale' );
	if( function_exists( $callback ) ) {
		$restored_locale = call_user_func( $callback );
		if( $restored_locale ) {
			if( ! is_string( $restored_locale ) ) { $restored_locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale(); }
			
			// Set $bookacti_locale global affects 'plugin_locale' hook
			// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
			global $bookacti_locale;
			$bookacti_locale = $restored_locale;
			
			// Load textdomain on bookacti_locale_switched
			do_action( 'bookacti_locale_restored', $restored_locale );
		}
	}
}


/**
 * Set plugin_locale to $bookacti_locale if defined
 * @since 1.14.0
 * @version 1.16.35
 * @global string $bookacti_locale
 * @param string $locale
 * @return string
 */
function bookacti_set_plugin_locale( $locale ) {
	global $bookacti_locale;
	return $bookacti_locale ? $bookacti_locale : $locale;
}
add_filter( 'plugin_locale', 'bookacti_set_plugin_locale', 100, 1 );


/**
 * Check if a locale exists and is installed
 * @since 1.16.35
 * @param string $locale
 * @return string|false
 */
function bookacti_is_available_locale( $locale ) {
	$is_available        = false;
	$installed_locales   = array_values( get_available_languages() );
	$installed_locales[] = 'en_US'; // Installed by default
	
	if( in_array( $locale, $installed_locales, true ) ) {
		$is_available = $locale;
		
	} else {
		// Try to find locale from lang code
		$i         = strpos( $locale, '_' );
		$lang_code = $i ? substr( $locale, 0, $i ) : $locale;
		foreach( $installed_locales as $installed_locale ) {
			if( $installed_locale === $lang_code || strpos( $installed_locale, $lang_code . '_' ) === 0 ) {
				$is_available = $installed_locale;
				break;
			}
		}
	}
	
	return apply_filters( 'bookacti_is_available_locale', $is_available, $locale );
}


/**
 * Get FullCalendar supported locale
 * @since 1.5.2
 * @version 1.15.8
 * @return array
 */
function bookacti_get_fullcalendar_supported_locales() {
	return apply_filters( 'bookacti_fullcalendar_locales', array( 
		'af', 'ar-dz', 'ar-kw', 'ar-ly', 'ar-ma', 'ar-sa', 'ar-tn', 'ar', 'az', 
		'bg', 'bn', 'bs', 'ca', 'cs', 'cy', 'da', 'de-at', 'de', 
		'el', 'en-au', 'en-gb', 'en-nz', 'eo', 'es-us', 'es', 'et', 'eu', 
		'fa', 'fi', 'fr-ca', 'fr-ch', 'fr', 
		'gl', 'he', 'hi', 'hr', 'hu', 'hy-am', 'id', 'is', 'it', 
		'ja', 'ka', 'kk', 'km', 'ko', 'ku', 'lb', 'lt', 'lv', 
		'mk', 'ms', 'nb', 'ne', 'nl', 'nn', 
		'pl', 'pt-br', 'pt', 'ro', 'ru', 
		'si-lk', 'sk', 'sl', 'sm', 'sq', 'sr-cyrl', 'sr', 'sv', 
		'ta-in', 'th', 'tr', 'ug', 'uk', 'uz', 'uz-cy', 'vi', 
		'zh-cn', 'zh-tw' 
	));
}


/**
 * Convert a WP formatted locale to the closest available FullCalendar locale
 * @since 1.5.2
 * @param string $wp_locale
 * @return string
 */
function bookacti_convert_wp_locale_to_fc_locale( $wp_locale = false ) {
	if( ! $wp_locale ) { $wp_locale = bookacti_get_site_locale(); }

	// Format the locale like FC locale formatting
	$fc_locale = $wp_locale;

	// Keep these formats "lang_COUNTRY" or "lang" only
	$pos = strpos( $wp_locale, '_', strpos( $wp_locale, '_' ) + 1 );
	if( $pos ) { $fc_locale = substr( $wp_locale, 0, $pos ); }

	// Replace _ by - and use lowercase only
	$fc_locale = strtolower( str_replace( '_', '-', $fc_locale ) ); 

	// Check if the locale exists
	$fc_locales = bookacti_get_fullcalendar_supported_locales();
	if( ! in_array( $fc_locale, $fc_locales, true ) ) {
		// Keep only the lang code
		$fc_locale = strstr( $wp_locale, '_', true );
		// Default to english if the locale doesn't exist at all
		if( ! in_array( $fc_locale, $fc_locales, true ) ) {
			$fc_locale = 'en';
		}
	}
	return apply_filters( 'bookacti_fullcalendar_locale', $fc_locale, $wp_locale );
}




// FORMS

/**
 * Display fields
 * @since 1.5.0
 * @version 1.15.13
 * @param array $args
 */
function bookacti_display_fields( $fields, $args = array() ) {
	if( empty( $fields ) || ! is_array( $fields ) )	{ return; }

	// Format parameters
	if( ! isset( $args[ 'hidden' ] ) || ! is_array( $args[ 'hidden' ] ) )  { $args[ 'hidden' ] = array(); }
	if( ! isset( $args[ 'prefix' ] ) || ! is_string( $args[ 'prefix' ] ) ) { $args[ 'prefix' ] = ''; }

	foreach( $fields as $field_name => $field ) {
		if( empty( $field[ 'type' ] ) ) { continue; }

		if( is_numeric( $field_name ) && ! empty( $field[ 'name' ] ) ) { $field_name = $field[ 'name' ]; }
		if( empty( $field[ 'name' ] ) ) { $field[ 'name' ] = $field_name; }
		$field[ 'name' ]   = ! empty( $args[ 'prefix' ] ) ? $args[ 'prefix' ] . '[' . $field_name . ']' : $field[ 'name' ];
		$field[ 'id' ]     = empty( $field[ 'id' ] ) ? 'bookacti-' . $field_name : $field[ 'id' ];
		$field[ 'hidden' ] = ! isset( $field[ 'hidden' ] ) ? ( in_array( $field_name, $args[ 'hidden' ], true ) ? 1 : 0 ) : $field[ 'hidden' ];
		
		$wrap_class = '';
		if( ! empty( $field[ 'hidden' ] ) ) { $wrap_class .= ' bookacti-hidden-field'; } 
		
		// If custom type, call another function to display this field
		if( substr( $field[ 'type' ], 0, 6 ) === 'custom' ) {
			do_action( 'bookacti_display_custom_field', $field, $field_name );
			continue;
		}
		
		// Else, display standard field
		?>
		<div class='bookacti-field-container <?php echo $wrap_class; ?>' id='<?php echo $field[ 'id' ] . '-container'; ?>'>
		<?php
			if( ! empty( $field[ 'before' ] ) ) { echo $field[ 'before' ]; }
		
			// Display field title
			if( ! empty( $field[ 'title' ] ) ) { 
				$fullwidth = ! empty( $field[ 'fullwidth' ] ) || in_array( $field[ 'type' ], array( 'checkboxes', 'editor' ), true );
			?>
				<label for='<?php echo esc_attr( sanitize_title_with_dashes( $field[ 'id' ] ) ); ?>' class='<?php if( $fullwidth ) { echo 'bookacti-fullwidth-label'; } ?>'>
					<?php echo $field[ 'title' ]; if( $fullwidth && ! empty( $field[ 'tip' ] ) ) { bookacti_help_tip( $field[ 'tip' ] ); unset( $field[ 'tip' ] ); } ?>
				</label>
			<?php
			}
			
			// Display field
			bookacti_display_field( $field );
			
			if( ! empty( $field[ 'after' ] ) ) { echo $field[ 'after' ]; }
		?>
		</div>
	<?php
	}
}


/**
 * Display various fields
 * @since 1.2.0
 * @version 1.15.16
 * @param array $args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'attr', 'value', 'tip', 'required']
 */
function bookacti_display_field( $args ) {
	$args = bookacti_format_field_args( $args );
	if( ! $args ) { return; }
	
	// Display field according to type

	// TEXT & NUMBER
	if( in_array( $args[ 'type' ], array( 'text', 'hidden', 'number', 'date', 'time', 'email', 'tel', 'password', 'file', 'color', 'url' ), true ) ) {
	?>
		<input type='<?php echo esc_attr( $args[ 'type' ] ); ?>' 
				name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
				value='<?php echo esc_attr( $args[ 'value' ] ); ?>' 
				autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
				id='<?php echo esc_attr( $args[ 'id' ] ); ?>' 
				class='bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
			<?php if( ! in_array( $args[ 'type' ], array( 'hidden', 'file' ) ) ) { ?>
				placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>' 
			<?php } 
			if( in_array( $args[ 'type' ], array( 'number', 'date', 'time' ), true ) ) { ?>
				min='<?php echo esc_attr( $args[ 'options' ][ 'min' ] ); ?>' 
				max='<?php echo esc_attr( $args[ 'options' ][ 'max' ] ); ?>'
				step='<?php echo esc_attr( $args[ 'options' ][ 'step' ] ); ?>'
			<?php }
			if( ! empty( $args[ 'attr' ] ) ) { echo $args[ 'attr' ]; }
			if( $args[ 'type' ] === 'file' && $args[ 'multiple' ] ) { echo ' multiple'; }
			if( $args[ 'required' ] ) { echo ' required'; } ?>
		/>
	<?php if( $args[ 'label' ] ) { ?>
		<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
			<?php echo $args[ 'label' ]; ?>
		</label>
	<?php
		}
	}

	// DURATION
	else if( $args[ 'type' ] === 'duration' ) {
		// Convert value from seconds
		$duration = is_numeric( $args[ 'value' ] ) ? bookacti_format_duration( $args[ 'value' ], 'array' ) : array( 'days' => '', 'hours' => '', 'minutes' => '', 'seconds' => '' );
		$step = is_numeric( $args[ 'options' ][ 'step' ] ) ? bookacti_format_duration( $args[ 'options' ][ 'step' ], 'array' ) : array( 'days' => '', 'hours' => '', 'minutes' => '', 'seconds' => '' );
		$min = is_numeric( $args[ 'options' ][ 'min' ] ) ? bookacti_format_duration( $args[ 'options' ][ 'min' ], 'array' ) : array( 'days' => '', 'hours' => '', 'minutes' => '', 'seconds' => '' );
		$max = is_numeric( $args[ 'options' ][ 'max' ] ) ? bookacti_format_duration( $args[ 'options' ][ 'max' ], 'array' ) : array( 'days' => '', 'hours' => '', 'minutes' => '', 'seconds' => '' );
		?>
		<input type='hidden' name='<?php echo esc_attr( $args[ 'name' ] ); ?>' value='<?php echo esc_attr( $args[ 'value' ] ); ?>' id='<?php echo esc_attr( $args[ 'id' ] ); ?>' class='bookacti-input bookacti-duration-value <?php echo esc_attr( $args[ 'class' ] ); ?>'/>
		<div class='bookacti-duration-field-container'>
			<input type='number' value='<?php echo esc_attr( $duration[ 'days' ] ); ?>' 
					id='<?php echo esc_attr( $args[ 'id' ] ) . '-days'; ?>' class='bookacti-input bookacti-duration-field'
					min='<?php echo ! empty( $min[ 'days' ] ) ? max( 0, $min[ 'days' ] ) : 0; ?>' 
					max='<?php echo ! empty( $max[ 'days' ] ) ? min( 99999, $max[ 'days' ] ) : 99999; ?>' 
					step='<?php echo ! empty( $step[ 'days' ] ) ? max( 1, $step[ 'days' ] ) : 1; ?>'
					placeholder='365' data-duration-unit='day'/>
			<label for='<?php echo esc_attr( $args[ 'id' ] ) . '-days'; ?>' class='bookacti-duration-field-label'><?php echo esc_html( _n( 'day', 'days', 2, 'booking-activities' ) ); ?></label>
		</div>
		<div class='bookacti-duration-field-container'>
			<input type='number' value='<?php echo esc_attr( $duration[ 'hours' ] ); ?>' 
					id='<?php echo esc_attr( $args[ 'id' ] ) . '-hours'; ?>' class='bookacti-input bookacti-duration-field'
					min='<?php echo empty( $min[ 'days' ] ) && ! empty( $min[ 'hours' ] ) ? max( 0, $min[ 'hours' ] ) : 0; ?>' 
					max='<?php echo empty( $max[ 'days' ] ) && ! empty( $max[ 'hours' ] ) ? min( 23, $max[ 'hours' ] ) : 23; ?>' 
					step='<?php echo empty( $step[ 'days' ] ) && ! empty( $step[ 'hours' ] ) ? max( 1, $step[ 'hours' ] ) : 1; ?>'
					placeholder='23' data-duration-unit='hour'/>
			<label for='<?php echo esc_attr( $args[ 'id' ] ) . '-hours'; ?>' class='bookacti-duration-field-label'><?php echo esc_html( _n( 'hour', 'hours', 2, 'booking-activities' ) ); ?></label>
		</div>
		<div class='bookacti-duration-field-container'>
			<input type='number' value='<?php echo esc_attr( $duration[ 'minutes' ] ); ?>' 
					id='<?php echo esc_attr( $args[ 'id' ] ) . '-minutes'; ?>' class='bookacti-input bookacti-duration-field'
					min='<?php echo empty( $min[ 'days' ] ) && empty( $min[ 'hours' ] ) && ! empty( $min[ 'minutes' ] ) ? max( 0, $min[ 'minutes' ] ) : 0; ?>' 
					max='<?php echo empty( $max[ 'days' ] ) && empty( $max[ 'hours' ] ) && ! empty( $max[ 'minutes' ] ) ? min( 59, $max[ 'minutes' ] ) : 59; ?>' 
					step='<?php echo empty( $step[ 'days' ] ) && empty( $step[ 'hours' ] ) && ! empty( $step[ 'minutes' ] ) ? max( 1, $step[ 'minutes' ] ) : 1; ?>'
					placeholder='59' data-duration-unit='minute'/>
			<label for='<?php echo esc_attr( $args[ 'id' ] ) . '-minutes'; ?>' class='bookacti-duration-field-label'><?php echo esc_html( _n( 'minute', 'minutes', 2, 'booking-activities' ) ); ?></label>
		</div>
		<?php if( $args[ 'label' ] ) { ?>
		<span><?php echo $args[ 'label' ]; ?></span>
		<?php
		}
	}

	// TEXTAREA
	else if( $args[ 'type' ] === 'textarea' ) {
	?>
		<textarea	
			name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
			id='<?php echo esc_attr( $args[ 'id' ] ); ?>' 
			autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
			class='bookacti-textarea <?php echo esc_attr( $args[ 'class' ] ); ?>' 
			placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>'
			<?php if( ! empty( $args[ 'attr' ] ) ) { echo $args[ 'attr' ]; } ?>
			<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
		><?php echo $args[ 'value' ]; ?></textarea>
	<?php if( $args[ 'label' ] ) { ?>
			<label	for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
				<?php echo $args[ 'label' ]; ?>
			</label>
	<?php
		}
	}

	// SINGLE CHECKBOX (boolean)
	else if( $args[ 'type' ] === 'checkbox' ) {
		$disabled = strpos( $args[ 'attr' ], 'disabled="disabled"' ) !== false;
		?>
		<div class='bookacti-onoffswitch' <?php if( $disabled ) { echo 'bookacti-disabled'; } ?>>
			<?php if( ! $disabled ) { ?>
			<input type='hidden' name='<?php echo esc_attr( $args[ 'name' ] ); ?>' value='0' class='bookacti-onoffswitch-hidden-input'/>
			<?php } ?>
			<input type='checkbox' 
				   name='<?php echo esc_attr( $args[ 'name' ] ); ?>'
				   id='<?php echo esc_attr( $args[ 'id' ] ); ?>'
				   class='bookacti-input bookacti-onoffswitch-checkbox<?php echo esc_attr( $args[ 'class' ] ); ?>' 
				   autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
				   value='1' 
				<?php 
					checked( '1', $args[ 'value' ] );
					if( ! empty( $args[ 'attr' ] ) ) { echo ' ' . $args[ 'attr' ]; }
					if( $args[ 'required' ] && ! $disabled ) { echo ' required'; }
				?>
			/>
			<?php if( $disabled ) { ?>
			<input type='hidden' name='<?php echo esc_attr( $args[ 'name' ] ); ?>' value='<?php echo esc_attr( $args[ 'value' ] ); ?>'/>
			<?php } ?>
			<div class='bookacti-onoffswitch-knobs'></div>
			<div class='bookacti-onoffswitch-layer'></div>
		</div>
		<?php 
		if( $args[ 'label' ] ) { ?>
		<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
			<?php echo $args[ 'label' ]; ?>
		</label>
		<?php 
		}
	}

	// MULTIPLE CHECKBOX
	else if( $args[ 'type' ] === 'checkboxes' ) {
		?>
		<input name='<?php echo esc_attr( $args[ 'name' ] ) . '[]'; ?>' 
				id='<?php echo esc_attr( $args[ 'id' ] ) . '_none'; ?>'
				type='hidden' 
				value='none' />
		<?php
		$count = count( $args[ 'options' ] );
		$i = 1;
		foreach( $args[ 'options' ] as $option ) {
		?>
			<div class='bookacti_checkbox <?php if( $i === $count ) { echo 'bookacti_checkbox_last'; } ?>'>
				<input name='<?php echo esc_attr( $args[ 'name' ] ) . '[]'; ?>' 
						id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' 
						class='bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
						type='checkbox' 
						value='<?php echo $option[ 'id' ]; ?>'
						<?php if( ! empty( $args[ 'attr' ][ $option[ 'id' ] ] ) ) { echo $args[ 'attr' ][ $option[ 'id' ] ]; } ?>
						<?php if( in_array( $option[ 'id' ], $args[ 'value' ], true ) ){ echo 'checked'; } ?>
				/>
			<?php if( ! empty( $option[ 'label' ] ) ) { ?>
				<label for='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' >
					<?php echo $option[ 'label' ]; ?>
				</label>
			<?php
				}
				// Display the tip
				if( ! empty( $option[ 'description' ] ) ) {
					$tip = $option[ 'description' ];
					bookacti_help_tip( $tip );
				}
			?>
			</div>
		<?php
			++$i;
		}
	}

	// RADIO
	else if( $args[ 'type' ] === 'radio' ) {
		$count = count( $args[ 'options' ] );
		$i = 1;
		foreach( $args[ 'options' ] as $option ) {
		?>
			<div class='bookacti_radio <?php if( $i === $count ) { echo 'bookacti_radio_last'; } ?>'>
				<input name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
						id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' 
						class='bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
						type='radio' 
						value='<?php echo esc_attr( $option[ 'id' ] ); ?>'
						<?php if( ! empty( $args[ 'attr' ][ $option[ 'id' ] ] ) ) { echo $args[ 'attr' ][ $option[ 'id' ] ]; } ?>
						<?php if( isset( $args[ 'value' ] ) ) { checked( $args[ 'value' ], $option[ 'id' ], true ); } ?>
						<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
				/>
			<?php if( $option[ 'label' ] ) { ?>
				<label for='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' >
					<?php echo $option[ 'label' ]; ?>
				</label>
			<?php
				}
				// Display the tip
				if( ! empty( $option[ 'description' ] ) ) {
					$tip = $option[ 'description' ];
					bookacti_help_tip( $tip );
				}
			?>
			</div>
		<?php
			++$i;
		}
	}

	// SELECT
	else if( $args[ 'type' ] === 'select' ) {
		$is_multiple = $args[ 'multiple' ] && ( $args[ 'multiple' ] !== 'maybe' || ( $args[ 'multiple' ] === 'maybe' && count( $args[ 'value' ] ) > 1 ) );
		if( $is_multiple && strpos( $args[ 'name' ], '[]' ) === false ) { $args[ 'name' ] .= '[]'; }
		if( ! $is_multiple && is_array( $args[ 'value' ] ) ) { $args[ 'value' ] = reset( $args[ 'value' ] ); }
		?>
		<select 
			name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
			id='<?php echo esc_attr( $args[ 'id' ] ); ?>' 
			class='bookacti-select <?php echo esc_attr( $args[ 'class' ] ); ?>'
			<?php 
				if( ! empty( $args[ 'placeholder' ] ) ) { echo ' data-placeholder="' . esc_attr( $args[ 'placeholder' ] ) . '"'; }
				if( ! empty( $args[ 'attr' ][ '<select>' ] ) ) { echo ' ' . $args[ 'attr' ][ '<select>' ]; }
				if( $is_multiple ) { echo ' multiple'; }
				if( $args[ 'required' ] ) { echo ' required'; }
			?>
		>
		<?php foreach( $args[ 'options' ] as $option_id => $option_value ) { ?>
			<option value='<?php echo esc_attr( $option_id ); ?>'
					id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option_id ); ?>' 
					<?php if( $args[ 'multiple' ] ) { ?> 
					title='<?php echo esc_html( $option_value ); ?>' 
					<?php } ?>
					<?php if( ! empty( $args[ 'attr' ][ $option_id ] ) ) { echo $args[ 'attr' ][ $option_id ]; } ?>
					<?php if( $is_multiple ) { selected( true, in_array( $option_id, $args[ 'value' ], true ) ); } else { selected( $args[ 'value' ], $option_id ); }?>
					<?php if( $is_multiple && ( in_array( $option_id, array( 'all', 'none', 'parent', 'site' ), true ) || ( ! empty( $args[ 'attr' ][ $option_id ] ) && strpos( 'data-not-multiple="1"', $args[ 'attr' ][ $option_id ] ) !== false ) ) ) { echo 'disabled="disabled"'; } ?>
			>
					<?php echo esc_html( $option_value ); ?>
			</option>
		<?php } ?>
		</select>
	<?php 
		if( $args[ 'multiple' ] === 'maybe' ) { ?>
			<span class='bookacti-multiple-select-container' <?php if( count( $args[ 'options' ] ) <= 1 ) { echo 'style="display:none;"'; } ?>>
				<label for='bookacti-multiple-select-<?php echo esc_attr( $args[ 'id' ] ); ?>' ><span class='dashicons dashicons-<?php echo $is_multiple ? 'minus' : 'plus';?>' title='<?php esc_attr_e( 'Multiple selection', 'booking-activities' ); ?>'></span></label>
				<input type='checkbox' 
					   class='bookacti-multiple-select' 
					   id='bookacti-multiple-select-<?php echo esc_attr( $args[ 'id' ] ); ?>' 
					   data-select-id='<?php echo esc_attr( $args[ 'id' ] ); ?>'
					   style='display:none' <?php checked( $is_multiple ) ?>/>
			</span>
	<?php 
			// Add select multiple values instructions
			if( $args[ 'tip' ] ) {
				/* translators: %s is the "+" icon to click on. */
				$args[ 'tip' ] .= '<br/>' . sprintf( esc_html__( 'To select multiple values, click on %s.', 'booking-activities' ), '<span class="dashicons dashicons-plus"></span>' );
			}
		} 
		if( $args[ 'label' ] ) { ?>
		<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
			<?php echo $args[ 'label' ]; ?>
		</label>
	<?php
		}
	}
	
	// TINYMCE editor
	else if( $args[ 'type' ] === 'editor' ) {
		wp_editor( $args[ 'value' ], $args[ 'id' ], $args[ 'options' ] );
	}

	// User ID
	else if( $args[ 'type' ] === 'user_id' ) {
		bookacti_display_user_selectbox( $args[ 'options' ] );
	}

	// Display the tip
	if( $args[ 'tip' ] ) {
		bookacti_help_tip( $args[ 'tip' ] );
	}
}


/**
 * Format arguments to display a proper field
 * @since 1.2.0
 * @version 1.16.12
 * @param array $raw_args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'attr', 'value', 'multiple', 'tip', 'required']
 * @return array|false
 */
function bookacti_format_field_args( $raw_args ) {
	// If $args is not an array, return
	if( ! is_array( $raw_args ) ) { return false; }

	// If fields type or name are not set, return
	if( ! isset( $raw_args[ 'type' ] ) || ! isset( $raw_args[ 'name' ] ) ) { return false; }

	// If field type is not supported, return
	if( ! in_array( $raw_args[ 'type' ], array( 'text', 'hidden', 'email', 'tel', 'date', 'time', 'password', 'number', 'duration', 'checkbox', 'checkboxes', 'select', 'radio', 'textarea', 'file', 'color', 'url', 'editor', 'user_id' ) ) ) { 
		return false; 
	}

	$default_args = array(
		'type'         => '',
		'name'         => '',
		'label'        => '',
		'id'           => '',
		'class'        => '',
		'placeholder'  => '',
		'options'      => array(),
		'attr'         => '',
		'value'        => '',
		'multiple'     => false,
		'tip'          => '',
		'required'     => 0,
		'autocomplete' => 0
	);
	
	$args = wp_parse_args( $raw_args, $default_args );
	
	// Sanitize id and name
	$args[ 'id' ] = sanitize_title_with_dashes( $args[ 'id' ] );
	
	// Generate a random id
	if( ! esc_attr( $args[ 'id' ] ) ) { $args[ 'id' ] = md5( microtime().rand() ); }

	// Sanitize required
	$args[ 'required' ] = isset( $args[ 'required' ] ) && $args[ 'required' ] ? 1 : 0;
	if( $args[ 'required' ] ) { $args[ 'class' ] .= ' bookacti-required-field'; }

	// Make sure 'attr' is an array for fields with multiple options
	if( in_array( $args[ 'type' ], array( 'checkboxes', 'radio', 'select', 'user_id' ) ) ) {
		if( ! is_array( $args[ 'attr' ] ) ) { $args[ 'attr' ] = array(); }
	} else {
		if( ! is_string( $args[ 'attr' ] ) ) { $args[ 'attr' ] = ''; }
	}
	
	// Pass attributes to user_id field
	if( $args[ 'type' ] === 'user_id' ) {
		if( empty( $args[ 'options' ][ 'name' ] ) ) { $args[ 'options' ][ 'name' ] = $args[ 'name' ]; }
		if( empty( $args[ 'options' ][ 'id' ] ) && ! empty( $args[ 'id' ] ) ) { $args[ 'options' ][ 'id' ] = $args[ 'id' ] . '-selectbox'; }
	}
	
	// If multiple, make sure name has brackets and value is an array
	if( in_array( $args[ 'multiple' ], array( 'true', true, '1', 1 ), true ) ) {
		if( strpos( $args[ 'name' ], '[]' ) === false ) { $args[ 'name' ] .= '[]'; }
	} else if( $args[ 'multiple' ] && $args[ 'type' ] === 'select' ) {
		$args[ 'multiple' ] = 'maybe';
	}

	// Make sure checkboxes have their value as an array
	if( $args[ 'type' ] === 'checkboxes' || ( $args[ 'multiple' ] && $args[ 'type' ] !== 'file' ) ){
		if( ! is_array( $args[ 'value' ] ) ) { $args[ 'value' ] = $args[ 'value' ] !== '' ? array( $args[ 'value' ] ) : array(); }
	}

	// Make sure 'number' has min and max
	else if( in_array( $args[ 'type' ], array( 'number', 'date', 'time', 'duration' ) ) ) {
		$args[ 'options' ][ 'min' ]  = isset( $args[ 'options' ][ 'min' ] ) ? $args[ 'options' ][ 'min' ] : '';
		$args[ 'options' ][ 'max' ]  = isset( $args[ 'options' ][ 'max' ] ) ? $args[ 'options' ][ 'max' ] : '';
		$args[ 'options' ][ 'step' ] = isset( $args[ 'options' ][ 'step' ] ) ? $args[ 'options' ][ 'step' ] : '';
	}

	// Make sure that if 'editor' has options, options is an array
	else if( $args[ 'type' ] === 'editor' ) {
		if( ! is_array( $args[ 'options' ] ) ) { $args[ 'options' ] = array(); }
		$args[ 'options' ][ 'default_editor' ] = ! empty( $args[ 'options' ][ 'default_editor' ] ) ? sanitize_title_with_dashes( $args[ 'options' ][ 'default_editor' ] ) : 'html'; // Workaround to correctly load TinyMCE in dialogs
		$args[ 'options' ][ 'textarea_name' ]  = $args[ 'name' ];
		$args[ 'options' ][ 'editor_class' ]   = $args[ 'class' ];
		$args[ 'options' ][ 'editor_height' ]  = ! empty( $args[ 'height' ] ) ? intval( $args[ 'class' ] ) : 120;
	}

	return $args;
}


/**
 * Display a toggled fieldset with tags list and description
 * @since 1.8.0
 * @param array $args_raw
 */
function bookacti_display_tags_fieldset( $args_raw = array() ) {
	$defaults = array(
		'title' => esc_html__( 'Available tags', 'booking-activities' ),
		'tip'   => '',
		'tags'  => array(),
		'id'    => 'bookacti-tags-' . rand()
	);
	$args = wp_parse_args( $args_raw, $defaults );
?>
	<fieldset id='<?php echo $args[ 'id' ]; ?>-container' class='bookacti-tags-fieldset bookacti-fieldset-no-css'>
		<legend class='bookacti-fullwidth-label'>
			<?php 
				echo $args[ 'title' ];
				if( $args[ 'tip' ] ) { bookacti_help_tip( $args[ 'tip' ] ); }
			?>
			<span class='bookacti-show-hide-advanced-options bookacti-show-advanced-options' for='<?php echo $args[ 'id' ]; ?>' data-show-title='<?php esc_html_e( 'show', 'booking-activities' ); ?>' data-hide-title='<?php esc_html_e( 'hide', 'booking-activities' ); ?>'><?php esc_html_e( 'show', 'booking-activities' ); ?></span>
		</legend>
		<div id='<?php echo $args[ 'id' ]; ?>' class='bookacti-fieldset-toggled' style='display:none;'>
			<?php
				if( $args[ 'tags' ] ) {
					$i = 1;
					$nb = count( $args[ 'tags' ] );
					foreach( $args[ 'tags' ] as $tag => $label ) {
						?>
							<code title='<?php echo esc_attr( $label ); ?>'><?php echo $tag; ?></code>
						<?php
						bookacti_help_tip( $label );
						if( $i < $nb ) { echo '<br/>'; }
						++$i;
					}
				}
			?>
		</div>
	</fieldset>
<?php
}


/**
 * Sanitize text from HTML editor in form fields
 * @since 1.5.2
 * @param string $html
 * @return string
 */
function bookacti_sanitize_form_field_free_text( $html ) {
	$html = wp_kses_post( stripslashes( $html ) );
	// Strip form tags
	$tags = array( 'form', 'input', 'textarea', 'select', 'option', 'output' );
	$html = preg_replace( '#<(' . implode( '|', $tags) . ')(?:[^>]+)?>.*?</\1>#s', '', $html );
	return $html;
}


/**
 * Display help toolbox
 * @version 1.7.12
 * @param string $tip
 */
function bookacti_help_tip( $tip, $echo = true ){
	$tip = "<span class='bookacti-tip-icon bookacti-tip' data-tip='" . esc_attr( $tip ) . "'></span>";
	if( $echo ) { echo $tip; }
	return $tip;
}


/**
 * Create a user selectbox
 * @since 1.3.0
 * @version 1.16.2
 * @param array $raw_args
 * @return string|void
 */
function bookacti_display_user_selectbox( $raw_args ) {
	$defaults = array(
		'allow_tags' => 0, 'allow_clear' => 1, 'allow_current' => 0, 
		'option_label' => array( 'display_name' ), 'placeholder' => esc_html__( 'Search...', 'booking-activities' ),
		'ajax' => 1, 'select2' => 1, 'sortable' => 0, 'echo' => 1,
		'selected' => array(), 'multiple' => 0, 'name' => 'user_id', 'class' => '', 'id' => '',
		'include' => array(), 'exclude' => array(),
		'role' => array(), 'role__in' => array(), 'role__not_in' => array(),
		'no_account' => false,
		'meta' => true, 'meta_single' => true,
		'orderby' => 'display_name', 'order' => 'ASC'
	);
	
	$args = apply_filters( 'bookacti_user_selectbox_args', wp_parse_args( $raw_args, $defaults ), $raw_args );
	
	$is_allowed = current_user_can( 'list_users' ) || current_user_can( 'edit_users' );
	$users = ! $args[ 'ajax' ] && $is_allowed ? bookacti_get_users_data( $args ) : array();
	$args[ 'class' ] = $args[ 'ajax' ] ? 'bookacti-select2-ajax ' . trim( $args[ 'class' ] ) : ( $args[ 'select2' ] ? 'bookacti-select2-no-ajax ' . trim( $args[ 'class' ] ) : trim( $args[ 'class' ] ) );
	
	// Format selected user ids
	if( ! is_array( $args[ 'selected' ] ) ) { $args[ 'selected' ] = $args[ 'selected' ] || in_array( $args[ 'selected' ], array( 0, '0' ), true ) ? array( $args[ 'selected' ] ) : array(); }
	$selected_user_ids = bookacti_ids_to_array( $args[ 'selected' ] );
	
	if( $args[ 'ajax' ] && $args[ 'selected' ] && $is_allowed ) {
		$selected_users = $selected_user_ids ? bookacti_get_users_data( array( 'include' => $selected_user_ids ) ) : array();
		if( $selected_users ) { $users = $selected_users; }
	}
	
	if( $args[ 'multiple' ] && strpos( $args[ 'name' ], '[]' ) === false ) { $args[ 'name' ] .= '[]'; } 
	
	$options = array();
	if( $users ) {
		foreach( $users as $user ) {
			$selected_key = array_search( intval( $user->ID ), $selected_user_ids, true );
			if( $selected_key !== false ) { unset( $selected_user_ids[ $selected_key ] ); }
			
			// Build the option label based on the array
			$label = '';
			foreach( $args[ 'option_label' ] as $show ) {
				// If the key contain "||" display the first not empty value
				if( strpos( $show, '||' ) !== false ) {
					$keys = explode( '||', $show );
					$show = $keys[ 0 ];
					foreach( $keys as $key ) {
						if( ! empty( $user->{ $key } ) ) { $show = $key; break; }
					}
				}

				// Display the value if the key exists, else display the key as is, as a separator
				if( isset( $user->{ $show } ) ) {
					$value  = maybe_unserialize( $user->{ $show } );
					$label .= is_array( $value ) ? implode( ',', $value ) : $value;
				} else {
					$label .= $show;
				}
			}
			$options[] = array( 'id' => $user->ID, 'text' => $label, 'selected' => $selected_key !== false );
		}
	}
	
	// Retrieve user emails from bookings made without accounts
	if( $args[ 'no_account' ] && ! $args[ 'ajax' ] && $is_allowed ) {
		$bookings = bookacti_get_bookings_without_account( array( 'distinct' => true ) );
		$booking_emails = array();
		foreach( $bookings as $booking ) {
			$options[] = array( 'id' => $booking->user_id, 'text' => $booking->user_id, 'selected' => in_array( $booking->user_id, $args[ 'selected' ], true ) );
		}
	}
	
	$options = apply_filters( 'bookacti_user_selectbox_options', $options, $args, $raw_args );
	
	ob_start();
	?>
	<input type='hidden' name='<?php echo $args[ 'name' ]; ?>' value=''/>
	<select <?php if( $args[ 'id' ] ) { echo 'id="' . $args[ 'id' ] . '"'; } ?> 
		name='<?php echo $args[ 'name' ]; ?>' 
		class='bookacti-user-selectbox <?php echo $args[ 'class' ]; ?>'
		data-tags='<?php echo ! empty( $args[ 'allow_tags' ] ) ? 1 : 0; ?>'
		data-allow-clear='<?php echo ! empty( $args[ 'allow_clear' ] ) ? 1 : 0; ?>'
		data-placeholder='<?php echo ! empty( $args[ 'placeholder' ] ) ? esc_attr( $args[ 'placeholder' ] ) : ''; ?>'
		data-sortable='<?php echo ! empty( $args[ 'sortable' ] ) ? 1 : 0; ?>'
		data-type='users'
		data-params='{"no_account":<?php echo ! empty( $args[ 'no_account' ] ) ? 1 : 0; ?>}'
		<?php if( $args[ 'multiple' ] ) { echo ' multiple'; } ?>>
		<?php if( ! $args[ 'multiple' ] ) {  ?>
			<option><!-- Used for the placeholder --></option>
		<?php
			}
			// Keep both numeric and string values
			foreach( $args[ 'selected' ] as $selected ) {
				if( $selected && ! is_numeric( $selected ) && is_string( $selected ) ) {
					$selected_user_ids[] = $selected;
				}
			}
			
			if( $args[ 'allow_current' ] ) {
				$selected_key = array_search( 'current', $selected_user_ids, true );
				if( $selected_key !== false ) { unset( $selected_user_ids[ $selected_key ] ); }
			?>
				<option value='current' <?php if( $selected_key !== false ) { echo 'selected'; } ?>><?php esc_html_e( 'Current user', 'booking-activities' ); ?></option>
			<?php
			}

			do_action( 'bookacti_add_user_selectbox_options', $args, $users );
			
			if( $options ) {
				foreach( $options as $option ) {
				?>
					<option value='<?php echo esc_attr( $option[ 'id' ] ); ?>' <?php if( ! empty( $option[ 'selected' ] ) ) { echo 'selected'; } ?>><?php echo esc_html( $option[ 'text' ] ); ?></option>
				<?php
				}
			}
			
			if( $args[ 'allow_tags' ] && $selected_user_ids ) {
				foreach( $selected_user_ids as $selected_user_id ) {
				?>
					<option value='<?php echo esc_attr( $selected_user_id ); ?>' selected><?php echo esc_html( $selected_user_id ); ?></option>
				<?php
				}
			}
		?>
	</select>
	<?php
	$output = ob_get_clean();

	if( ! $args[ 'echo' ] ) { return $output; }
	echo $output;
}


/**
 * Display tabs and their content
 * @version 1.8.0
 * @param array $tabs
 * @param string $id
 */
function bookacti_display_tabs( $tabs, $id ) {
	if( ! isset( $tabs ) || ! is_array( $tabs ) || empty( $tabs ) || ! $id || ! is_string( $id ) ) { return; }

	// Sort tabs in the desired order
	usort( $tabs, 'bookacti_sort_array_by_order' );
	?>
	
	<div class='bookacti-tabs'>
		<ul>
		<?php
			// Display tabs
			foreach( $tabs as $i => $tab ) {
				$tab_id	= isset( $tab[ 'id' ] ) ? sanitize_title_with_dashes( $tab[ 'id' ] ) : $i;
				?>
				<li class='bookacti-tab-<?php echo esc_attr(  $tab_id ); ?>'>
					<a href='#bookacti-tab-content-<?php echo esc_attr(  $tab_id ); ?>' ><?php echo esc_html( $tab[ 'label' ] ); ?></a>
				</li>
				<?php
			}
		?>
		</ul>
		<?php
			// Display tabs content
			foreach( $tabs as $i => $tab ) {
				$tab_id	= isset( $tab[ 'id' ] ) ? sanitize_title_with_dashes( $tab[ 'id' ] ) : $i;
				?>
				<div id='bookacti-tab-content-<?php echo esc_attr( $tab_id ); ?>' class='bookacti-tab-content bookacti-custom-scrollbar'>
				<?php
					if( isset( $tab[ 'callback' ] ) && is_callable( $tab[ 'callback' ] ) ) {
						if( isset( $tab[ 'parameters' ] ) ) {
							call_user_func( $tab[ 'callback' ], $tab[ 'parameters' ] );
						} else {
							call_user_func( $tab[ 'callback' ] );
						}
					}
				?>
				</div>
				<?php
			}
		?>
	</div>
	<?php
}


/**
 * Display a table from a properly formatted array
 * @since 1.7.0
 * @param array $array
 * @return string
 */
function bookacti_display_table_from_array( $array ) {
	if( empty( $array[ 'head' ] ) || empty( $array[ 'body' ] ) ) { return ''; }
	if( ! is_array( $array[ 'head' ] ) || ! is_array( $array[ 'body' ] ) ) { return ''; }

	$column_ids = array_keys( $array[ 'head' ] );
	?>
	<table class='bookacti-options-table'>
		<thead>
			<tr>
			<?php
			foreach( $array[ 'head' ] as $column_id => $column_label ) {
			?>
				<th class='bookacti-column-<?php echo $column_id; ?>'><?php echo is_string( $column_label ) || is_numeric( $column_label ) ? $column_label : $column_id; ?></th>
			<?php
			}
			?>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach( $array[ 'body' ] as $row ) {
		?>
			<tr>
				<?php
				foreach( $column_ids as $column_id ) {
					$column_content = isset( $row[ $column_id ] ) && ( is_string( $row[ $column_id ] ) || is_numeric( $row[ $column_id ] ) ) ? $row[ $column_id ] : '';
				?>
					<td class='bookacti-column-<?php echo $column_id; ?>'><?php echo $column_content; ?></td>
				<?php
				}
			?>
			</tr>
		<?php
		}
		?>
		</tbody>
	</table>
	<?php
}


/**
 * Display the Days off field
 * @since 1.13.0
 * @param array $field
 * @param string $field_name
 */
function bookacti_display_date_intervals_field( $field, $field_name ) {
	if( $field[ 'type' ] !== 'custom_date_intervals' ) { return; }
	if( empty( $field[ 'value' ] ) ) { $field[ 'value' ] = array( array( 'from' => '', 'to' => '' ) ); }
?>
	<div class='bookacti-field-container bookacti-date-intervals-container' id='<?php echo $field[ 'id' ] . '-container'; ?>'>
		<label for='<?php echo esc_attr( sanitize_title_with_dashes( $field[ 'id' ] ) ); ?>' class='bookacti-fullwidth-label'>
		<?php 
			echo ! empty( $field[ 'title' ] ) ? $field[ 'title' ] : '';
			bookacti_help_tip( $field[ 'tip' ] );
		?>
			<span class='dashicons dashicons-plus-alt bookacti-add-date-interval'></span>
		</label>
		<div id='<?php echo $field[ 'id' ]; ?>' class='bookacti-date-intervals-table-container bookacti-custom-scrollbar' data-name='<?php echo $field[ 'name' ]; ?>' >
			<table>
				<thead>
					<tr>
						<th><?php echo esc_html_x( 'From', 'date', 'booking-activities' ); ?></th>
						<th><?php echo esc_html_x( 'To', 'date', 'booking-activities' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php
					$i = 0;
					foreach( $field[ 'value' ] as $day_off ) {
					?>
						<tr>
							<td><input type='date' name='<?php echo $field[ 'name' ] . '[' . $i . '][from]'; ?>' value='<?php if( ! empty( $day_off[ 'from' ] ) ) { echo $day_off[ 'from' ]; } ?>' class='bookacti-date-interval-from'/></td>
							<td><input type='date' name='<?php echo $field[ 'name' ] . '[' . $i . '][to]'; ?>' value='<?php if( ! empty( $day_off[ 'to' ] ) ) { echo $day_off[ 'to' ]; } ?>' class='bookacti-date-interval-to'/></td>
							<td><span class='dashicons dashicons-trash bookacti-delete-date-interval'></span></td>
						</tr>
					<?php
						++$i;
					}
				?>
				</tbody>
			</table>
		</div>
	</div>
<?php
}
add_action( 'bookacti_display_custom_field', 'bookacti_display_date_intervals_field', 10, 2 );


/**
 * Get loading HTML
 * @since 1.15.0
 * @return string
 */
function bookacti_get_loading_html() {
	return '<div class="bookacti-loading-container"><div class="bookacti-loading-image"><div class="bookacti-spinner"></div></div><div class="bookacti-loading-text">' . esc_html__( 'Loading', 'booking-activities' ) . '</div></div>';
}


// BLOCK

/**
 * Return the first occurence of a parsed block recursively
 * @since 1.16.5
 * @param array $parsed_block
 * @param string $block_name
 * @return array
 */
function bookacti_find_parsed_block_recursively( $parsed_block, $block_name ) {
	$desired_block = array();
	if( $parsed_block[ 'blockName' ] === $block_name ) {
		$desired_block = $parsed_block;
	} else if( ! empty( $parsed_block[ 'innerBlocks' ] ) ) {
		// Find the desired block recursively in child blocks
		foreach( $parsed_block[ 'innerBlocks' ] as $i => $inner_block ) {
			$desired_block = bookacti_find_parsed_block_recursively( $inner_block, $block_name );
			if( $desired_block ) {
				break;
			}
		}
	}
	return $desired_block;
}


/**
 * Remove parsed blocks by name recursively
 * @since 1.16.5
 * @param array $parsed_block
 * @param string $block_name
 * @param boolean $once
 * @return array
 */
function bookacti_remove_parsed_block_recursively( $parsed_block, $block_name, $once = false ) {
	if( $parsed_block[ 'blockName' ] === $block_name ) {
		$parsed_block = (array) new WP_Block_Parser_Block( '', array(), array(), '', array() );
	} else if( ! empty( $parsed_block[ 'innerBlocks' ] ) ) {
		$deleted = false;
		
		// Apply this function recursively to all child blocks
		$removed_inner_blocks_positions = array();
		foreach( $parsed_block[ 'innerBlocks' ] as $i => $inner_block ) {
			$parsed_block[ 'innerBlocks' ][ $i ] = bookacti_remove_parsed_block_recursively( $inner_block, $block_name, $once );
			
			if( ! $parsed_block[ 'innerBlocks' ][ $i ][ 'blockName' ] ) {
				$removed_inner_blocks_positions[] = $i;
				unset( $parsed_block[ 'innerBlocks' ][ $i ] );
				$deleted = true;
				if( $once ) {
					break;
				}
			}
		}
		
		if( $removed_inner_blocks_positions ) {
			// Find the innerContent entries corresponding to the old blocks (innerContent has one non-string entry per block)
			$old_inner_content_positions = array();
			foreach( $parsed_block[ 'innerContent' ] as $i => $innerContent ) {
				if( ! is_string( $innerContent ) ) {
					$old_inner_content_positions[] = $i;
				}
			}
			
			// Remove the innerContent corresponding to the deleted blocks
			foreach( $removed_inner_blocks_positions as $removed_block_pos ) {
				$key = $removed_block_pos;
				if( ! isset( $old_inner_content_positions[ $removed_block_pos ] ) ) {
					$keys = array_keys( $old_inner_content_positions );
					$key  = end( $keys );
				}
				$pos = $old_inner_content_positions[ $key ];
				unset( $parsed_block[ 'innerContent' ][ $pos ] );
				unset( $old_inner_content_positions[ $key ] );
			}
			
			// Both innerBlocks and innerContent must have consistent sequential numeric indexes
			$parsed_block[ 'innerBlocks' ]  = array_values( $parsed_block[ 'innerBlocks' ] );
			$parsed_block[ 'innerContent' ] = array_values( $parsed_block[ 'innerContent' ] );
		}
	}
	return $parsed_block;
}


/**
 * Insert a parsed block after a specific block recursively
 * @since 1.16.5
 * @param array $parsed_block
 * @param array $parsed_block_to_add
 * @param string $block_name
 * @param string $position
 * @param boolean $once
 * @return array
 */
function bookacti_insert_parsed_block_recursively( $parsed_block, $parsed_block_to_add, $block_name = '', $position = 'after', $once = false ) {
	if( $parsed_block[ 'innerBlocks' ] ) {
		$added = false;
		
		// Apply this function recursively to all child blocks
		foreach( $parsed_block[ 'innerBlocks' ] as $i => $inner_block ) {
			if( ! empty( $inner_block[ 'innerBlocks' ] ) ) {
				$parsed_block[ 'innerBlocks' ][ $i ] = bookacti_insert_parsed_block_recursively( $inner_block, $parsed_block_to_add, $block_name, $position, $once );
				if( count( $parsed_block[ 'innerBlocks' ][ $i ][ 'innerBlocks' ] ) !== count( $inner_block[ 'innerBlocks' ] ) ) {
					$added = true;
					if( $once ) {
						break;
					}
				}
			}
		}
		
		// Create the new innerBlocks array and save the new block positions in that array
		$new_inner_blocks = array();
		$new_inner_blocks_positions = array();
		$i = 0;
		foreach( $parsed_block[ 'innerBlocks' ] as $inner_block ) {
			if( ( ! $once || ! $added ) && $inner_block[ 'blockName' ] === $block_name ) {
				if( $position === 'before' ) {
					$new_inner_blocks[] = $parsed_block_to_add;
					$new_inner_blocks[] = $inner_block;
					$new_inner_blocks_positions[] = $i;
				} else {
					$new_inner_blocks[] = $inner_block;
					$new_inner_blocks[] = $parsed_block_to_add;
					$new_inner_blocks_positions[] = $i + 1;
				}
				$added = true;
				$i += 2;
			} else {
				$new_inner_blocks[] = $inner_block;
				++$i;
			}
		}
		
		$inserted_nb = max( 0, abs( count( $new_inner_blocks ) - count( $parsed_block[ 'innerBlocks' ] ) ) );
		if( $inserted_nb ) {
			// Find the innerContent entries corresponding to the old blocks (innerContent has one non-string entry per block)
			$old_inner_content_positions = array();
			foreach( $parsed_block[ 'innerContent' ] as $i => $innerContent ) {
				if( ! is_string( $innerContent ) ) {
					$old_inner_content_positions[] = $i;
				}
			}
			
			// Insert a innerContent entry for each new block at their corresponding position
			$inserted = 0;
			$default_pos = end( $old_inner_content_positions ) ? end( $old_inner_content_positions ) : 0;
			foreach( $new_inner_blocks_positions as $new_block_pos ) {
				$pos = isset( $old_inner_content_positions[ $new_block_pos ] ) ? $old_inner_content_positions[ $new_block_pos ] : $default_pos;
				array_splice( $parsed_block[ 'innerContent' ], $pos + $inserted, 0, 'null' );
				++$inserted;
			}
			$parsed_block[ 'innerContent' ] = array_map( function( $value ) { return $value === 'null' ? null : $value; }, $parsed_block[ 'innerContent' ] );
		}
		
		$parsed_block[ 'innerBlocks' ] = $new_inner_blocks;
	}
	
	return $parsed_block;
}


// FORMATING AND SANITIZING

/**
 * Add HTML allowed tags through kses
 * @since 1.15.15
 * @param array $tags
 * @param string $context
 * @return array
 */
function bookacti_kses_allowed_html( $tags, $context ) {
	if( ! isset( $tags[ 'bdi' ] ) && $context = 'post' ) { $tags[ 'bdi' ] = array(); }
	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'bookacti_kses_allowed_html', 10, 2 );


/**
 * Check if a string is valid for UTF-8 use
 * @since 1.5.7
 * @param string $string
 * @return boolean
 */
function bookacti_is_utf8( $string ) {
	if( function_exists( 'mb_check_encoding' ) ) {
		if( mb_check_encoding( $string, 'UTF-8' ) ) { 
			return true;
		}
	}
	else if( preg_match( '%^(?:
			[\x09\x0A\x0D\x20-\x7E]            # ASCII
		  | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		  | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
		  | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		  | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
		  | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
		  | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		  | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
		)*$%xs', $string ) ) {
		return true;
	}
	return false;
}


/**
 * Encode to UTF-8
 * @since 1.16.26
 * @param string $string
 * @param boolean $check
 * @return string
 */
function bookacti_utf8_encode( $string, $check = true ) {
	if( $check && bookacti_is_utf8( $string ) ) {
		return $string;
	}
	if( function_exists( 'mb_convert_encoding' ) ) {
		return mb_convert_encoding( $string, 'UTF-8', 'ISO-8859-1' );
	}
	else if( function_exists( 'iconv' ) ) {
		return iconv( 'ISO-8859-1', 'UTF-8', $string );
	}
	return bookacti_iso8859_1_to_utf8( $string );
}


/**
 * Decode UTF-8
 * @since 1.16.26
 * @param string $string
 * @param boolean $check
 * @return string
 */
function bookacti_utf8_decode( $string, $check = true ) {
    if( $check && ! bookacti_is_utf8( $string ) ) {
        return $string;
    }
    if( function_exists( 'mb_convert_encoding' ) ) {
        return mb_convert_encoding( $string, 'ISO-8859-1', 'UTF-8' );
    }
    else if( function_exists( 'iconv' ) ) {
        return iconv( 'UTF-8', 'ISO-8859-1', $string );
    }
    return bookacti_utf8_to_iso8859_1( $string );
}


/**
 * Replacement for utf8_encode
 * https://github.com/symfony/polyfill-php72/blob/v1.30.0/Php72.php#L24-L38
 * @since 1.16.26
 * @param string $s
 * @return string
 */
function bookacti_iso8859_1_to_utf8( $s ) {
    $s .= $s;
	$len = \strlen($s);

	for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
		switch (true) {
			case $s[$i] < "\x80": $s[$j] = $s[$i]; break;
			case $s[$i] < "\xC0": $s[$j] = "\xC2"; $s[++$j] = $s[$i]; break;
			default: $s[$j] = "\xC3"; $s[++$j] = \chr(\ord($s[$i]) - 64); break;
		}
	}

	return substr($s, 0, $j);
}


/**
 * Replacement for utf8_decode
 * https://github.com/symfony/polyfill-php72/blob/v1.30.0/Php72.php#L40-L68
 * @since 1.16.26
 * @param string $s
 * @return string
 */
function bookacti_utf8_to_iso8859_1( $s ) {
    $s = (string) $s;
    $len = \strlen($s);

    for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
        switch ($s[$i] & "\xF0") {
            case "\xC0":
            case "\xD0":
                $c = (\ord($s[$i] & "\x1F") << 6) | \ord($s[++$i] & "\x3F");
                $s[$j] = $c < 256 ? \chr($c) : '?';
                break;

            case "\xF0":
                ++$i;
                // no break

            case "\xE0":
                $s[$j] = '?';
                $i += 2;
                break;

            default:
                $s[$j] = $s[$i];
        }
    }

    return substr($s, 0, $j);
}


/**
 * Use mb_substr if available, else use a regex
 * @since 1.6.0
 * @param string $string
 * @param int $offset
 * @param int|null $length
 * @return string
 */
function bookacti_substr( $string, $offset = 0, $length = null ) {
	$substr = '';
	if( function_exists( 'mb_substr' ) ) {
		$substr = mb_substr( $string, $offset, $length );
	} else {
		$arr = preg_split( '//u', $string );
		$slice = array_slice( $arr, $offset + 1, $length );
		$substr = implode( '', $slice );
	}
	return $substr;
}


/**
 * Array replace recursive, but only for associative arrays
 * @since 1.16.0
 * @param array $array
 * @param array $defaults
 * @param array $recursive_keys
 * @return array
 */
function bookacti_associative_array_replace_recursive( $array, $defaults, $recursive_keys = array() ) {
	if( ! is_array( $array ) )    { $array = array(); }
	if( ! is_array( $defaults ) ) { $defaults = array(); }
	
	$merged_array = $array;
	
	foreach( $defaults as $key => $default_value ) {
		// Check if the value and the default value are associative arrays
		$array_value   = isset( $array[ $key ] ) ? $array[ $key ] : array();
		$array_keys    = is_array( $array_value ) ? array_keys( $array_value ) : array();
		$defaults_keys = is_array( $default_value ) ? array_keys( $default_value ) : array();
		$are_assoc     = $array_keys && $defaults_keys && $defaults_keys !== array_keys( $defaults_keys ) && $array_keys !== array_keys( $array_keys );
		
		// Keep the array value, except if it is an associative array
		if( isset( $array[ $key ] ) && ( ! is_array( $array[ $key ] ) || ! $are_assoc ) ) {
			continue;
		}
		
		// For associative arrays, add the default value of each missing key
		if( $are_assoc ) {
			$parent_keys          = array_merge( $recursive_keys, array( $key ) );
			$merged_array[ $key ] = bookacti_associative_array_replace_recursive( $array_value, $default_value, $parent_keys );
			continue;
		}
		
		// Add the default value of the missing key
		if( ! isset( $array[ $key ] ) ) {
			$merged_array[ $key ] = $default_value;
		}
	}
	
	return $merged_array;
}


/**
 * Sort array of arrays with a ['order'] index
 * 
 * @param array $a
 * @param array $b
 * @return array 
 */
function bookacti_sort_array_by_order( $a, $b ) {
	return $a['order'] - $b['order'];
}


/**
 * Sort an array of events by dates
 * @since 1.15.7
 * @param array array
 * @param boolean sort_by_end
 * @param boolean desc
 * @param object labels
 * @returns array
 */
function bookacti_sort_events_array_by_dates( $array, $sort_by_end = false, $desc = false, $labels = array() ) {
	$default_labels = array( 'start' => 'start', 'end' => 'end' );
	$labels = wp_parse_args( $labels, $default_labels );
	
	$sorted_array = $array;
	usort( $sorted_array, function( $a, $b ) use ( $sort_by_end, $desc, $labels ) {
		$a = (array) $a; $b = (array) $b;
		
		// If start date is the same, then $sort by end date ASC
		if( $sort_by_end || $a[ $labels[ 'start' ] ] === $b[ $labels[ 'start' ] ] ) {
			$a_dt = new DateTime( $a[ $labels[ 'end' ] ] );
			$b_dt = new DateTime( $b[ $labels[ 'end' ] ] );
		} 
		// Sort by start date ASC by default
		else {
			$a_dt = new DateTime( $a[ $labels[ 'start' ] ] );
			$b_dt = new DateTime( $b[ $labels[ 'start' ] ] );
		}
		
		$sort = 0;
		if( $a_dt > $b_dt )  { $sort = 1; }
		if( $a_dt < $b_dt )  { $sort = -1; }
		if( $desc === true ) { $sort = $sort * -1; }
		
		return $sort;
	});
	
	return $sorted_array;
}


/**
 * Sanitize int ids to array
 * @version 1.16.0
 * @param array|int $ids
 * @param bool|?callable $filter
 * @return array 
 */
function bookacti_ids_to_array( $ids, $filter = 'empty' ) {
	if( is_array( $ids ) ){
		$ids = array_unique( array_map( 'intval', array_filter( $ids, 'is_numeric' ) ) );
	} else if( ! empty( $ids ) && is_numeric( $ids ) ) {
		$ids = array( intval( $ids ) );
	} else {
		$ids = array();
	}
	
	if( $ids && $filter && is_callable( $filter ) ) {
		$ids = array_filter( $ids, $filter );
	}
	
	return $ids;
}

/**
 * Sanitize str ids to array
 * @since 1.8.3
 * @version 1.16.0
 * @param array|string $ids
 * @return array 
 */
function bookacti_str_ids_to_array( $ids, $filter = 'empty' ) {
	if( is_array( $ids ) ){
		$ids = array_unique( array_map( 'sanitize_title_with_dashes', array_filter( $ids, 'is_string' ) ) );
	} else if( ! empty( $ids ) && is_string( $ids ) ){
		$ids = array( sanitize_title_with_dashes( $ids ) );
	} else {
		$ids = array();
	}
	
	if( $ids && $filter && is_callable( $filter ) ) {
		$ids = array_filter( $ids, $filter );
	}
	
	return $ids;
}


/**
 * Convert an array to string recursively
 * @since 1.6.0
 * @version 1.15.0
 * @param array $array
 * @param int|boolean $display_keys If int, keys will be displayed if >= $level
 * @param int $type "csv" or "ical"
 * @param int $level Used for recursion for multidimensional arrays. "1" is the first level.
 * @return string
 */
function bookacti_format_array_for_export( $array, $display_keys = false, $type = 'csv', $level = 1 ) {
	if( ! is_array( $array ) ) { return $array; }
	if( empty( $array ) ) { return ''; }
	
	$this_display_keys = $display_keys === true || ( is_numeric( $display_keys ) && $display_keys >= $level );
	
	$i = 0;
	$string = '';
	foreach( $array as $key => $value ) {
		$value = bookacti_maybe_decode_json( maybe_unserialize( $value ) );
		
		if( $this_display_keys || is_array( $value ) ) {                             // If keys are not displayed, display values on the same line
			if( $i>0 || $level>1 ) { $string .= $type === 'csv' ? PHP_EOL : '\n'; }  // Else, one line per value
			if( $level > 1 )       { $string .= str_repeat( '    ', ($level-1) ); }  // And indent it according to its level in the array (for multidimentional array)
		} else {
			if( $i > 0 )           { $string .= ', '; }                              // Separate each value with a comma
		}
		if( $this_display_keys )   { $string .= $key . ': '; }                       // Display key before value
		
		if( is_array( $value ) )   { $string .= bookacti_format_array_for_export( $value, $display_keys, $type, $level+1 ); } // Repeat. (for multidimentional array)
		else                       { $string .= $value; }
		
		++$i;
	}
	
	return apply_filters( 'bookacti_format_array_for_export', $string, $array, $display_keys, $type, $level );
}


/**
 * Format datetime to be displayed in a human comprehensible way
 * @version 1.16.26
 * @param string $datetime Date format "Y-m-d H:i:s" is expected
 * @param string $format 
 * @return string
 */
function bookacti_format_datetime( $datetime, $format = '' ) {
	$datetime = bookacti_sanitize_datetime( $datetime );
	if( $datetime ) {
		if( ! $format ) { $format = bookacti_get_message( 'date_format_long' ); }

		// Force timezone to UTC to avoid offsets because datetimes should be displayed regarless of timezones
		$dt = new DateTime( $datetime, new DateTimeZone( 'UTC' ) );
		$timestamp = $dt->getTimestamp();
		if( $timestamp === false ) { return $datetime; }
		
		// Do not use date_i18n() function to force the UTC timezone
		$datetime = apply_filters( 'date_i18n', wp_date( $format, $timestamp, new DateTimeZone( 'UTC' ) ), $format, $timestamp, false );

		// Encode to UTF8 to avoid any bad display of special chars
		$datetime = bookacti_utf8_encode( $datetime );
	}
	return $datetime;
}


/**
 * Check if a string is in a correct datetime format
 * @version 1.15.6
 * @param string $datetime Date format "Y-m-d H:i:s" is expected
 * @return string
 */
function bookacti_sanitize_datetime( $datetime ) {
	if( preg_match( '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])T([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $datetime ) 
	||  preg_match( '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]) ([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $datetime ) ) {
		$datetime_object = new DateTime( $datetime );
		return $datetime;
	}
	return '';
}


if( ! function_exists( 'wp_date' ) ) {
	/**
	 * Backward Compatibility - Retrieves the date, in localized format
	 * @since 1.8.7
	 * @param string $format
	 * @param int $timestamp
	 * @param DateTimeZone $timezone
	 * @return string
	 */
	function wp_date( $format, $timestamp = false, $timezone = false ) {
		return date_i18n( $format, $timestamp, false );
	}
}


/**
 * Check if a string is in a correct date format
 * @version 1.15.6
 * @param string $date Date format Y-m-d is expected
 * @return string 
 */
function bookacti_sanitize_date( $date ) {
	if( preg_match( '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $date ) ) {
		$datetime_object = new DateTime( $date );
		return $datetime_object->format( 'Y-m-d' );
	}
	return '';
}


/**
 * Convert duration from seconds
 * @since 1.8.0
 * @version 1.8.4
 * @param int $seconds
 * @param string $format Either "iso8601", "timespan" or "array"
 * @return string P%dDT%dH%dM%dS
 */
function bookacti_format_duration( $seconds, $format = 'iso8601' ) {
	$seconds = intval( $seconds );
	$array = array();
    
	$array[ 'days' ] = floor( $seconds / 86400 );
    $seconds = $seconds % 86400;

    $array[ 'hours' ] = floor( $seconds / 3600 );
    $seconds = $seconds % 3600;

    $array[ 'minutes' ] = floor( $seconds / 60 );
    $array[ 'seconds' ] = $seconds % 60;
	
	if( $format === 'array' ) { return $array; }
	
	if( $format === 'timespan' ) { 
		// The timespan format is limited to a INT64 number of seconds
		if( $array[ 'days' ] > 10675198 ) { $array[ 'days' ] = 10675198; }
		
		return sprintf( '%s.%s:%s:%s', str_pad( $array[ 'days' ], 3, '0', STR_PAD_LEFT ), str_pad( $array[ 'hours' ], 2, '0', STR_PAD_LEFT ), str_pad( $array[ 'minutes' ], 2, '0', STR_PAD_LEFT ), str_pad( $array[ 'seconds' ], 2, '0', STR_PAD_LEFT ) );
	}
	
	// The iso8601 format is limited to 12-digit numbers
	if( $array[ 'days' ] > 999999999999 ) { $array[ 'days' ] = 999999999999; }
	
    return sprintf( 'P%dDT%dH%dM%dS', $array[ 'days' ], $array[ 'hours' ], $array[ 'minutes' ], $array[ 'seconds' ] );
}


/**
 * Format a delay in seconds to a user friendly remaining time
 * @since 1.8.6
 * @param int $seconds
 * @param int $precision 1 for days, 2 for hours, 3 for minutes, 4 for seconds
 * @return string
 */
function bookacti_format_delay( $seconds, $precision = 3 ) {
	$time = bookacti_format_duration( $seconds, 'array' );
	$formatted_delay = '';
	
	if( intval( $time[ 'days' ] ) > 0 ) { 
		/* translators: %d is a variable number of days */
		$days_formated = sprintf( _n( '%d day', '%d days', $time[ 'days' ], 'booking-activities' ), $time[ 'days' ] );
		$formatted_delay .= $days_formated;
	}
	if( intval( $time[ 'hours' ] ) > 0 && $precision >= 2 ) { 
		/* translators: %d is a variable number of hours */
		$hours_formated = sprintf( _n( '%d hour', '%d hours', $time[ 'hours' ], 'booking-activities' ), $time[ 'hours' ] );
		$formatted_delay .= ' ' . $hours_formated;
	}
	if( intval( $time[ 'minutes' ] ) > 0 && $precision >= 3 ) { 
		/* translators: %d is a variable number of minutes */
		$minutes_formated = sprintf( _n( '%d minute', '%d minutes', $time[ 'minutes' ], 'booking-activities' ), $time[ 'minutes' ] );
		$formatted_delay .= ' ' . $minutes_formated;
	}
	if( intval( $time[ 'seconds' ] ) > 0 && $precision >= 4 ) { 
		/* translators: %d is a variable number of minutes */
		$seconds_formated = sprintf( _n( '%d second', '%d seconds', $time[ 'seconds' ], 'booking-activities' ), $time[ 'seconds' ] );
		$formatted_delay .= ' ' . $seconds_formated;
	}

	return apply_filters( 'bookacti_formatted_delay', $formatted_delay, $seconds, $precision );
}


/**
 * Convert a DateInterval to a DateInterval with seconds only
 * @since 1.16.7
 * @param DateInterval $interval
 * @return DateInterval
 */
function bookacti_php_date_interval_in_seconds( $interval ) {
	$days          = is_numeric( $interval->format( '%a' ) ) ? abs( intval( $interval->format( '%a' ) ) ) : 0;
	$hours         = is_numeric( $interval->h ) ? abs( intval( $interval->h ) ) : 0;
	$minutes       = is_numeric( $interval->i ) ? abs( intval( $interval->i ) ) : 0;
	$seconds       = is_numeric( $interval->s ) ? abs( intval( $interval->s ) ) : 0;
	$total_seconds = $days * 86400 + $hours * 3600 + $minutes * 60 + $seconds;
	$new_interval  = new DateInterval( 'PT' . $total_seconds . 'S' );
	$new_interval->invert = $interval->invert;
	return $new_interval;
}


/**
 * Check if a string is valid JSON
 * 
 * @since 1.1.0
 * @param string $string
 * @return boolean
 */
function bookacti_is_json( $string ) {
	if( ! is_string( $string ) ) { return false; }
	json_decode( $string );
	return ( json_last_error() == JSON_ERROR_NONE );
}


/**
 * Decode JSON if it is valid else return self
 * @since 1.6.0
 * @version 1.15.6
 * @param string $string
 * @param boolean $assoc
 * @return array|$string
 */
function bookacti_maybe_decode_json( $string, $assoc = false ) {
	if( ! is_string( $string ) ) { return $string; }
	$decoded = json_decode( $string, $assoc );
	if( json_last_error() == JSON_ERROR_NONE ) { return $decoded; }
	return $string;
}


/**
 * Sanitize the values of an array
 * @since 1.5.0
 * @version 1.16.20
 * @param array $default_data
 * @param array $raw_data
 * @param array $keys_by_type
 * @param array $sanitized_data
 * @return array
 */
function bookacti_sanitize_values( $default_data, $raw_data, $keys_by_type, $sanitized_data = array() ) {
	// Sanitize the keys-by-type array
	$allowed_types = array( 'int', 'absint', 'float', 'absfloat', 'numeric', 'bool', 'str', 'str_id', 'str_html', 'color', 'array', 'array_ids', 'array_str_ids', 'datetime', 'date' );
	foreach( $allowed_types as $allowed_type ) {
		if( ! isset( $keys_by_type[ $allowed_type ] ) ) { $keys_by_type[ $allowed_type ] = array(); }
	}

	// Make an array of all keys that will be sanitized
	$keys_to_sanitize = array();
	foreach( $keys_by_type as $type => $keys ) {
		if( ! in_array( $type, $allowed_types, true ) ) { continue; }
		if( ! is_array( $keys ) ) { $keys_by_type[ $type ] = array( $keys ); }
		foreach( $keys as $key ) {
			$keys_to_sanitize[] = $key;
		}
	}

	// Format each value according to its type
	foreach( $default_data as $key => $default_value ) {
		// Do not process keys without types
		if( ! in_array( $key, $keys_to_sanitize, true ) ) { continue; }
		// Skip already sanitized values
		if( isset( $sanitized_data[ $key ] ) ) { continue; }
		// Set undefined values to default and continue
		if( ! isset( $raw_data[ $key ] ) ) { $sanitized_data[ $key ] = $default_value; continue; }

		// Sanitize integers
		if( in_array( $key, $keys_by_type[ 'int' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? intval( $raw_data[ $key ] ) : $default_value;
		}
		
		// Sanitize absolute integers
		if( in_array( $key, $keys_by_type[ 'absint' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? abs( intval( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize floats
		if( in_array( $key, $keys_by_type[ 'float' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? floatval( $raw_data[ $key ] ) : $default_value;
		}

		// Sanitize absolute floats
		if( in_array( $key, $keys_by_type[ 'absfloat' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? abs( floatval( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize numeric
		if( in_array( $key, $keys_by_type[ 'numeric' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? $raw_data[ $key ] : $default_value;
		}

		// Sanitize string identifiers
		else if( in_array( $key, $keys_by_type[ 'str_id' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_title_with_dashes( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize text
		else if( in_array( $key, $keys_by_type[ 'str' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_text_field( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize text with html
		else if( in_array( $key, $keys_by_type[ 'str_html' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? wp_kses_post( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}
		
		// Sanitize hex color
		else if( in_array( $key, $keys_by_type[ 'color' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_hex_color( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize array
		else if( in_array( $key, $keys_by_type[ 'array' ], true ) ) { 
			$sanitized_data[ $key ] = is_array( $raw_data[ $key ] ) ? $raw_data[ $key ] : $default_value;
		}

		// Sanitize array of ids
		else if( in_array( $key, $keys_by_type[ 'array_ids' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) || is_array( $raw_data[ $key ] ) ? array_values( bookacti_ids_to_array( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize array of str ids
		else if( in_array( $key, $keys_by_type[ 'array_str_ids' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) || is_array( $raw_data[ $key ] ) ? array_values( bookacti_str_ids_to_array( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize boolean
		else if( in_array( $key, $keys_by_type[ 'bool' ], true ) ) { 
			$sanitized_data[ $key ] = in_array( $raw_data[ $key ], array( 1, '1', true, 'true' ), true ) ? 1 : ( in_array( $raw_data[ $key ], array( 0, '0', false, 'false' ), true ) ? 0 : $default_value );
		}

		// Sanitize datetime
		else if( in_array( $key, $keys_by_type[ 'datetime' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? bookacti_sanitize_datetime( stripslashes( $raw_data[ $key ] ) ) : '';
			if( ! $sanitized_data[ $key ] ) { $sanitized_data[ $key ] = $default_value; }
		}

		// Sanitize date
		else if( in_array( $key, $keys_by_type[ 'date' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? bookacti_sanitize_date( stripslashes( $raw_data[ $key ] ) ) : '';
			if( ! $sanitized_data[ $key ] ) { $sanitized_data[ $key ] = $default_value; }
		}
	}

	return apply_filters( 'bookacti_sanitized_data', $sanitized_data, $default_data, $raw_data, $keys_by_type );
}


/**
 * Escape illegal caracters in ical properties
 * @since 1.6.0
 * @version 1.16.37
 * @param string $value
 * @param string $property_name
 * @return string
 */
function bookacti_sanitize_ical_property( $value, $property_name = '' ) {
	$is_desc = $property_name === 'DESCRIPTION';
	$eol = $is_desc ? '\n' : ' ';
	$value = trim( $value );                               // Remove whitespaces at the start and at the end of the string
	$value = ! $is_desc ? strip_tags( $value ) : $value;   // Remove PHP and HTML elements
	$value = html_entity_decode( $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' ); // Decode html entities first because semicolons will be escaped
	$value = preg_replace( '/([\,;])/', '\\\$1', $value ); // Escape illegal caracters in ical properties
	$value = preg_replace( '/\r|\n/', $eol, $value );      // Replace End of lines with a whitespace or a \n in DESCRIPTION
	$value = preg_replace( '/\s{2,}/', ' ', $value );      // Replace multiple whitespaces with a single space
	$property_name_len = strlen( $property_name ) + 1;     // Add 1 character for the colon (:) after the property name
	$lines = array();
	while( strlen( $value ) > ( 75 - $property_name_len ) ) {
		$space = ( 75 - $property_name_len );
		$mbcc = $space;
		while( $mbcc ) {
			$line = bookacti_substr( $value, 0, $mbcc );
			$oct = strlen( $line );
			if ( $oct > $space ) {
				$mbcc -= $oct - $space;
			}
			else {
				$lines[] = $line;
				$property_name_len = 1; // The leading space doesn't count, but we still take it into account it for better compatibility
				$value = bookacti_substr( $value, $mbcc );
				break;
			}
		}
	}
	if( ! empty( $value ) ) {
		$lines[] = $value;
	}
	return implode( PHP_EOL . ' ', $lines ); // Line break + leading space
}


/**
 * Get the price format depending on the currency position.
 * @since 1.15.15
 * @return string %1$s = currency symbol, %2$s = price amount
 */
function bookacti_get_price_format() {
	$currency_pos = bookacti_get_setting_value( 'bookacti_general_settings', 'price_currency_position' );
	$format       = '%1$s%2$s';

	switch( $currency_pos ) {
		case 'left':
			$format = '%1$s%2$s';
			break;
		case 'right':
			$format = '%2$s%1$s';
			break;
		case 'left_space':
			$format = '%1$s&nbsp;%2$s';
			break;
		case 'right_space':
			$format = '%2$s&nbsp;%1$s';
			break;
	}

	return apply_filters( 'bookacti_price_format', $format, $currency_pos );
}


/**
 * Format a price with currency symbol
 * @since 1.15.15
 * @param int|float $price_raw
 * @param array $args_raw Arguments to format a price (default to price settings values) {
 *     @type string $currency_symbol    Currency symbol.
 *     @type string $decimal_separator  Decimal separator.
 *     @type string $thousand_separator Thousand separator.
 *     @type string $decimals           Number of decimals.
 *     @type string $price_format       Price format depending on the currency position.
 *     @type boolean $plain_text        Return plain text instead of HTML.
 * }
 * @return string
 */
function bookacti_format_price( $price_raw, $args_raw = array() ) {
	if( ! is_numeric( $price_raw ) ) { return ''; }
	
	$args = apply_filters(
		'bookacti_price_args',
		wp_parse_args(
			$args_raw,
			array(
				'currency_symbol'    => bookacti_get_setting_value( 'bookacti_general_settings', 'price_currency_symbol' ),
				'decimal_separator'  => bookacti_get_setting_value( 'bookacti_general_settings', 'price_decimal_separator' ),
				'thousand_separator' => bookacti_get_setting_value( 'bookacti_general_settings', 'price_thousand_separator' ),
				'decimals'           => bookacti_get_setting_value( 'bookacti_general_settings', 'price_decimals_number' ),
				'price_format'       => bookacti_get_price_format(),
				'plain_text'         => false
			)
		), $args_raw, $price_raw
	);

	// Convert to float to avoid issues on PHP 8.
	$price  = (float) $price_raw;
	$amount = number_format( abs( $price ), $args[ 'decimals' ], $args[ 'decimal_separator' ], $args[ 'thousand_separator' ] );

	if( apply_filters( 'bookacti_price_trim_zeros', false ) && $args[ 'decimals' ] > 0 ) {
		$amount = preg_replace( '/' . preg_quote( $args[ 'decimal_separator' ], '/' ) . '0++$/', '', $amount );
	}

	ob_start();
	if( empty( $args[ 'plain_text' ] ) ) {
	?>
	<span class='bookacti-price'>
		<bdi>
		<?php if( $price < 0 ) { ?>
			<span class='bookacti-price-sign'>-</span>
		<?php } 
			echo sprintf( 
				$args[ 'price_format' ], 
				'<span class="bookacti-price-currency-symbol">' . trim( $args[ 'currency_symbol' ] ) . '</span>',
				'<span class="bookacti-price-amount">' . $amount . '</span>'
			);
		?>
		</bdi>
	</span>
	<?php
	} else {
		if( $price < 0 ) { echo '-'; }
		echo sprintf( $args[ 'price_format' ], $amount, trim( $args[ 'currency_symbol' ] ) );
	}

	return apply_filters( 'bookacti_formatted_price', str_replace( array( "\t", "\r", "\n" ), '', trim( ob_get_clean() ) ), $amount, $price_raw, $args_raw );
}




// USERS

/**
 * Get users metadata
 * @version 1.15.0
 * @param array $args
 * @return array
 */
function bookacti_get_users_data( $args = array() ) {
	$defaults = array(
		'blog_id' => $GLOBALS[ 'blog_id' ],
		'include' => array(), 'exclude' => array(),
		'role' => '', 'role__in' => array(), 'role__not_in' => array(), 'who' => '',
		'meta_key' => '', 'meta_value' => '', 'meta_compare' => '', 'meta_query' => array(),
		'date_query' => array(),
		'orderby' => 'login', 'order' => 'ASC', 'offset' => '',
		'number' => '', 'paged' => '', 'count_total' => false,
		'search' => '', 'search_columns' => array(), 'fields' => 'all', 
		'meta' => true, 'meta_single' => true
	 ); 

	$args = apply_filters( 'bookacti_users_data_args', wp_parse_args( $args, $defaults ), $args );

	$users = get_users( $args );
	if( ! $users ) { return $users; }
	
	// Index the array by user ID
	$sorted_users = array();
	foreach( $users as $user ) {
		$sorted_users[ $user->ID ] = $user;
	}
	
	// Add user meta
	if( $args[ 'meta' ] && $sorted_users ) {
		// Make sure that all the desired users meta are in cache with a single db query
		update_meta_cache( 'user', array_keys( $sorted_users ) );
		
		// Add cached meta to user object
		foreach( $sorted_users as $user_id => $user ) {
			$meta = array();
			$meta_raw = wp_cache_get( $user_id, 'user_meta' );
			if( $meta_raw && is_array( $meta_raw ) ) {
				foreach( $meta_raw as $key => $values ) {
					$meta[ $key ] = $args[ 'meta_single' ] ? maybe_unserialize( $values[ 0 ] ) : array_map( 'maybe_unserialize', $values );
				}
				$sorted_users[ $user_id ]->meta = $meta; 
			}
		}
	}
		
	return $sorted_users;
}


/**
 * Get all available user roles
 * @since 1.8.0
 * @version 1.8.3
 * @return array
 */
function bookacti_get_roles() {
	global $wp_roles;
	if( ! $wp_roles ) { $wp_roles = new WP_Roles(); }
    $roles = array_map( 'translate_user_role', $wp_roles->get_names() );
	return $roles;
}


/**
 * Get all user roles per capability
 * @since 1.8.3
 * @param array $capabilities
 * @return array
 */
function bookacti_get_roles_by_capabilities( $capabilities = array() ) {
	global $wp_roles;
	if( ! $wp_roles ) { $wp_roles = new WP_Roles(); }
	
	$matching_roles = array();
	
	$roles = $wp_roles->roles;
	if( $roles ) {
		foreach( $roles as $role_id => $role ) {
			$role_cap = array_keys( $role[ 'capabilities' ] );
			if( array_intersect( $role_cap, $capabilities ) ) {
				$matching_roles[] = $role_id;
			}
		}
	}
	
	return $matching_roles;
}


/**
 * Programmatically logs a user in
 * @since 1.5.0
 * @version 1.16.9
 * @param string $username
 * @param boolean $remember
 * @return bool True if the login was successful; false if it wasn't
 */
function bookacti_log_user_in( $username, $remember = false ) {
	if( is_user_logged_in() ) { wp_logout(); }
	
	// hook in earlier than other callbacks to short-circuit them
	add_filter( 'authenticate', 'bookacti_allow_to_log_user_in_programmatically', 10, 3 );
	$user = wp_signon( array( 'user_login' => $username, 'remember' => $remember ) );
	remove_filter( 'authenticate', 'bookacti_allow_to_log_user_in_programmatically', 10, 3 );

	if( is_a( $user, 'WP_User' ) ) {
		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID, true );
		
		do_action( 'bookacti_user_logged_in', $user->ID );
		
		if( is_user_logged_in() ) { return true; }
	}
	
	return false;
}


/**
 * An 'authenticate' filter callback that authenticates the user using only the username.
 *
 * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
 * and unhooked immediately after it fires.
 * 
 * @since 1.5.0
 * @param WP_User $user
 * @param string $username
 * @param string $password
 * @return bool|WP_User a WP_User object if the username matched an existing user, or false if it didn't
 */
function bookacti_allow_to_log_user_in_programmatically( $user, $username, $password ) {
	return get_user_by( 'login', $username );
}


/**
 * Bypass managers checks for all administrators
 * @since 1.12.4
 * @version 1.12.7
 * @param boolean $true
 * @param int $user_id
 * @return boolean
 */
function bookacti_bypass_managers_check_for_administrators( $true, $user_id = 0 ) {
	if( ! $user_id ) { $user_id = wp_get_current_user(); }
	return user_can( $user_id, 'administrator' ) ? true : $true;
}
add_filter( 'bookacti_bypass_template_managers_check', 'bookacti_bypass_managers_check_for_administrators', 100, 2 );
add_filter( 'bookacti_bypass_activity_managers_check', 'bookacti_bypass_managers_check_for_administrators', 100, 2 );
add_filter( 'bookacti_bypass_form_managers_check', 'bookacti_bypass_managers_check_for_administrators', 100, 2 );