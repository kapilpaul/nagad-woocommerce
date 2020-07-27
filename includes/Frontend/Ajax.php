<?php

namespace DCoders\Nagad\Frontend;

use DCoders\Nagad\Woocommerce\PaymentProcessor;

/**
 * Class Ajax
 * @package DCoders\Nagad\Frontend
 */
class Ajax {
    /**
     * Ajax constructor.
     */
    public function __construct() {
        add_action( 'wp_ajax_dc-nagad-create-payment-request', [ $this, 'create_payment_request' ] );
    }

    /**
     * create payment request for nagad
     *
     * @return void
     */
    public function create_payment_request() {
        try {
            if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'dc-nagad-nonce' ) ) {
                $this->send_json_error( 'Something went wrong here!' );
            }

            if ( ! $this->validate_fields( $_POST ) ) {
                $this->send_json_error( 'Empty value is not allowed' );
            }

            $order_number = ( isset( $_POST['order_number'] ) ) ? sanitize_key( $_POST['order_number'] ) : '';

            $order = wc_get_order( $order_number );

            if ( ! is_object( $order ) ) {
                $this->send_json_error( 'Wrong or invalid order ID' );
            }

            $payment_process = PaymentProcessor::checkout( $order->get_id(), $order->get_total() );

            $url = $payment_process['status'] == 'success' ? $payment_process['url'] : $order->get_checkout_payment_url();

            if ( $payment_process['status'] == 'success' ) {
                wp_send_json_success( esc_url_raw( $url ) );
            }

            wp_send_json_error( $payment_process );

        } catch ( \Exception $e ) {
            $this->send_json_error( $e->getMessage() );
        }
    }

    /**
     * send json error
     *
     * @param $text
     *
     * @return void
     */
    public function send_json_error( $text ) {
        wp_send_json_error( __( $text, 'dc-nagad' ) );
        wp_die();
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function validate_fields( $data ) {
        foreach ( $data as $key => $value ) {
            if ( empty( $value ) ) {
                return false;
            }
        }

        return true;
    }
}
