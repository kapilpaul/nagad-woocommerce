<?php

/**
 * @param $data
 */
function dump( $data ) {
    echo "<pre>";
    var_dump( $data );
    die();
}


/**
 * Insert transaction in table
 *
 * @param $data
 *
 * @return false|int
 */
function insert_nagad_transaction( $data ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'dc_nagad_transactions';

    $insert = $wpdb->insert( $table_name, [
        "customer_id"        => sanitize_key( $data['customer_id'] ),
        "payment_ref_id"     => sanitize_text_field( $data['payment_ref_id'] ),
        "issuer_payment_ref" => sanitize_text_field( $data['issuer_payment_ref'] ),
        "invoice_number"     => sanitize_text_field( $data['invoice_number'] ),
        "order_number"       => sanitize_text_field( $data['order_number'] ),
        "amount"             => sanitize_text_field( $data['amount'] ),
        "transaction_status" => sanitize_text_field( $data['transaction_status'] ),
    ] );

    return $insert;
}

/**
 * Get payment form nagad table
 *
 * @param $order_number
 *
 * @return array|object|null
 */
function get_nagad_payment( $order_number ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'dc_nagad_transactions';

    $query = "SELECT * FROM $table_name WHERE order_number='%d'";

    $item = $wpdb->get_row(
        $wpdb->prepare( $query, $order_number )
    );

    return $item;
}

/**
 * Get all payment list form nagad table
 *
 * @param array $args
 *
 * @return array|object|null
 */
function get_nagad_payments_list( $args = [] ) {
    global $wpdb;

    $defaults = [
        'number'  => 20,
        'offset'  => 0,
        'orderby' => 'id',
        'order'   => 'ASC',
    ];

    $args = wp_parse_args( $args, $defaults );

    $table_name = $wpdb->prefix . 'dc_nagad_transactions';

    $query = "SELECT * FROM $table_name";

    if ( isset( $args['search'] ) ) {
        $query .= " WHERE order_number LIKE '%{$args['search']}%' OR WHERE invoice_number LIKE '%{$args['search']}%'";
    }

    $query .= " ORDER BY {$args['orderby']} {$args['order']} LIMIT %d, %d";

    $items = $wpdb->get_results(
        $wpdb->prepare( $query, $args['offset'], $args['number'] )
    );

    return $items;
}

/**
 * Get Count of total payments in DB
 * @return string|null
 */
function get_nagad_payments_count() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'dc_nagad_transactions';

    return (int) $wpdb->get_var( "SELECT COUNT(id) from $table_name" );
}

/**
 * Delete a payment
 *
 * @param int $id
 *
 * @return int|boolean
 */
function delete_nagad_payment( $id ) {
    global $wpdb;

    return $wpdb->delete(
        $wpdb->prefix . 'dc_nagad_transactions',
        [ 'id' => $id ],
        [ '%d' ]
    );
}

/**
 * delete multiple data from table
 *
 * @param array $ids
 *
 * @return bool|int
 */
function delete_multiple_nagad_payments( array $ids ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dc_nagad_transactions';

    $ids = implode( ',', $ids );
    return $wpdb->query( "DELETE FROM {$table_name} WHERE ID IN($ids)" );
}
