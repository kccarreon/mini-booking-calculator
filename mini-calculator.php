<?php
/**
 * Plugin Name: Mini Booking Cost Calculator
 * Description: A lightweight booking cost calculator with a shortcode to calculate quotes for bookings.
 * Version: 1.0
 * Author: Your Name
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Register Shortcode [booking_quote]
function booking_quote_shortcode() {
    ob_start();
    ?>
    <form id="booking-quote-form">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="address">Address:</label>
        <input type="text" id="address" name="address" required>

        <label for="distance">Distance (in km):</label>
        <input type="number" id="distance" name="distance" required>

        <label for="rooms">Number of Rooms:</label>
        <select id="rooms" name="rooms">
            <option value="1">1 Room</option>
            <option value="2">2 Rooms</option>
            <option value="3">3 Rooms</option>
            <option value="4">4 Rooms</option>
            <option value="5">5 Rooms</option>
        </select>

        <button type="submit">Get Quote</button>
    </form>

    <div id="booking-quote-result"></div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'booking_quote', 'booking_quote_shortcode' );

// Enqueue script to handle form submission and calculation
function booking_quote_enqueue_scripts() {
    wp_enqueue_script( 'booking-quote-js', plugin_dir_url( __FILE__ ) . 'js/booking-quote.js', array('jquery'), null, true );
    wp_localize_script( 'booking-quote-js', 'bookingQuoteParams', array(
        'ajax_url' => admin_url( 'admin-ajax.php' )
    ));
}
add_action( 'wp_enqueue_scripts', 'booking_quote_enqueue_scripts' );

// AJAX handler to calculate the quote
function booking_quote_calculate() {
    if( isset( $_POST['distance'] ) && isset( $_POST['rooms'] ) && isset( $_POST['name'] ) && isset( $_POST['address'] ) ) {
        $name = sanitize_text_field( $_POST['name'] );
        $address = sanitize_text_field( $_POST['address'] );
        $distance = intval( $_POST['distance'] );
        $rooms = intval( $_POST['rooms'] );

        // Base fee and additional costs
        $base_fee = 100;
        $distance_cost = $distance * 10;
        $room_cost = $rooms * 50;

        // Total cost calculation
        $total_cost = $base_fee + $distance_cost + $room_cost;

        // Save booking data to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking_quotes';
        $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'address' => $address,
                'distance' => $distance,
                'rooms' => $rooms,
                'total_cost' => $total_cost
            )
        );

        // Send email to admin
        $admin_email = get_option('admin_email');
        $subject = 'New Booking Quote Submission';
        $message = "New booking quote received:\n\nName: $name\nAddress: $address\nDistance: $distance km\nRooms: $rooms\nTotal Cost: $$total_cost";
        wp_mail($admin_email, $subject, $message);

        // Send response with calculated quote
        echo "Thanks $name! Your estimated booking cost is $$total_cost.";
    }

    wp_die(); // Always call wp_die() at the end of AJAX functions
}
add_action( 'wp_ajax_booking_quote', 'booking_quote_calculate' );
add_action( 'wp_ajax_nopriv_booking_quote', 'booking_quote_calculate' );

// Activation hook to create the custom table
function booking_quote_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'booking_quotes';

    // SQL query to create the table if it doesn't exist
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        address TEXT NOT NULL,
        distance INT NOT NULL,
        rooms INT NOT NULL,
        total_cost DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'booking_quote_activate');
