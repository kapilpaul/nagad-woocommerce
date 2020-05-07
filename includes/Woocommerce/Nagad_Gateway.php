<?php


namespace DCoders\Nagad\Woocommerce;

use WC_Payment_Gateway;

class Nagad_Gateway extends WC_Payment_Gateway {
    /**
     * Initialize the gateway
     * Nagad_Gateway constructor.
     */
    public function __construct() {
        $this->init_fields();
        $this->init_form_fields();
        $this->init_settings();
        $this->init_actions();
    }

    /**
     * init essential fields
     *
     * @return void
     */
    public function init_fields() {
        $this->id                 = 'dc_nagad';
        $this->icon               = false;
        $this->has_fields         = true;
        $this->method_title       = __( 'Nagad', 'dc-nagad' );
        $this->method_description = __( 'Pay via Nagad payment', 'dc-nagad' );
        $title                    = $this->get_option( 'title' );
        $this->title              = empty( $title ) ? __( 'Nagad', 'dc-nagad' ) : $title;
        $this->description        = $this->get_option( 'description' );
    }

    /**
     * Admin configuration parameters
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled'                    => [
                'title'   => __( 'Enable/Disable', 'dc-nagad' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Nagad', 'dc-nagad' ),
                'default' => 'yes',
            ],
            'test_mode'                  => [
                'title'   => __( 'Test Mode', 'dc-nagad' ),
                'type'    => 'select',
                'options' => [ "on" => "ON", "off" => "OFF" ],
                'default' => __( 'off', 'dc-nagad' ),
            ],
            'title'                      => [
                'title'   => __( 'Title', 'dc-nagad' ),
                'type'    => 'text',
                'default' => __( 'Nagad Payment', 'dc-nagad' ),
            ],
            'merchant_id'                => [
                'title' => __( 'Merchant ID', 'dc-nagad' ),
                'type'  => 'text',
            ],
            'merchant_private_key'       => [
                'title' => __( 'Merchant Private Key', 'dc-nagad' ),
                'type'  => 'textarea',
            ],
            'payment_gateway_public_key' => [
                'title' => __( 'Payment Gateway Public Key', 'dc-nagad' ),
                'type'  => 'textarea',
            ],
            'description'                => [
                'title'       => __( 'Description', 'dc-nagad' ),
                'type'        => 'textarea',
                'description' => __( 'Payment method description that the customer will see on your checkout.', 'dc-nagad' ),
                'default'     => __( 'Pay via Nagad', 'dc-nagad' ),
                'desc_tip'    => true,
            ],
        ];
    }

    /**
     * initialize necessary actions
     *
     * @return void
     */
    public function init_actions() {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
        add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'thank_you_page' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] );
    }

    /**
     * include payment scripts
     *
     * @return void
     */
    public function payment_scripts() {
        if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
            return;
        }

        // if our payment gateway is disabled
        if ( 'no' === $this->enabled ) {
            return;
        }

        wp_enqueue_style( 'dc-nagad-frontend' );
        wp_enqueue_script( 'dc-nagad-frontend' );

        $this->localizeScripts();
    }

    /**
     * localize scripts and pass data
     *
     * @return void
     */
    public function localizeScripts() {
        global $woocommerce;
        global $wp;

        $data = [
            'ajax_url' => WC()->ajax_url(),
            'nonce'    => wp_create_nonce( 'dc-nagad-nonce' ),
        ];

        wp_localize_script( 'dc-nagad-frontend', 'nagad_params', $data );
    }

    /**
     * Process the gateway integration
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        // Remove cart
        WC()->cart->empty_cart();

        $payment_process = PaymentProcessor::checkout( $order->get_id(), $order->get_total() );

        $url = $payment_process['status'] == 'success' ? $payment_process['url'] : $order->get_checkout_payment_url();

        return [
            'result'   => 'success',
            'redirect' => esc_url_raw( $url ),
        ];
    }

    /**
     * Thank you page after order
     *
     * @param $order_id
     *
     * @return void
     */
    public function thank_you_page( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( 'dc_nagad' === $order->get_payment_method() ) {
            $payment_data = get_nagad_payment( $order_id );

            if($payment_data) {
                $status = $payment_data->transaction_status;
            }

    ?>
            <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
                <li class="woocommerce-order-overview__payment-method method">
                    Payment Status:
                    <?php if ( isset( $status ) ) { ?>
                        <strong><?php echo strtoupper( $status ); ?></strong>
                    <?php } else { ?>
                        <strong><?php echo __( 'NOT PAID', 'dc-nagad' ); ?></strong>
                    <?php } ?>
                </li>
            </ul>
    <?php
        }

    }
}
