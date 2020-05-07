<?php

namespace DCoders\Nagad;

use DCoders\Nagad\Woocommerce\PaymentProcessor;

/**
 * Class Routes
 * @package DCoders\Nagad
 */
class PageHandler {
    /**
     * Routes constructor.
     */
    public function __construct() {
        add_filter( 'query_vars', [ $this, 'query_vars' ] );
        add_action( 'template_include', [ $this, 'plugin_include_template' ] );
    }

    /**
     * @param $vars
     *
     * @return array
     */
    public function query_vars( $vars ) {
        $vars[] = 'dc_nagad_action';

        return $vars;
    }

    /**
     * @param $template
     *
     * @return mixed
     */
    function plugin_include_template( $template ) {
        if ( get_query_var( 'dc_nagad_action' ) && get_query_var( 'dc_nagad_action' ) == 'dc-payment-action' ) {
            $params = $_GET;

            $this->handle_nagad_payment_action( $params );
        }

        return $template;
    }

    /**
     * handle payment action by status
     *
     * @param $params
     *
     * @return bool
     */
    public function handle_nagad_payment_action( $params ) {
        $order_number = substr( $params['order_id'], 5 );
        $order        = wc_get_order( $order_number );

        if ( $params['status'] == 'Success' && $params['status_code'] == "00_0000_000" ) {
            $verification = PaymentProcessor::verify_payment( $params['payment_ref_id'] );

            if ( $verification['status'] == 'Success' && $verification['statusCode'] == '000' ) {
                if ( $order->get_total() == $verification['amount'] ) {
                    $insert_data = [
                        'customer_id'        => $order->get_customer_id(),
                        'payment_ref_id'     => sanitize_text_field( $verification['paymentRefId'] ),
                        'issuer_payment_ref' => sanitize_text_field( $verification['issuerPaymentRefNo'] ),
                        'invoice_number'     => sanitize_text_field( $verification['orderId'] ),
                        'order_number'       => sanitize_text_field( $order_number ),
                        'amount'             => sanitize_text_field( $verification['amount'] ),
                        'transaction_status' => sanitize_text_field( $verification['status'] ),
                    ];

                    insert_nagad_transaction( $insert_data );

                    $order->add_order_note( sprintf( __( 'Nagad payment completed. Amount: %s', 'dc-nagad' ), $order->get_total() ) );
                    $order->payment_complete();
                } else {
                    $order->update_status(
                        'on-hold',
                        __( 'Partial payment. Amount: %s', 'dc-nagad' ),
                        sanitize_text_field( $verification['amount'] )
                    );
                }
            }
        }

        return wp_redirect( $order->get_checkout_order_received_url() );
    }
}
