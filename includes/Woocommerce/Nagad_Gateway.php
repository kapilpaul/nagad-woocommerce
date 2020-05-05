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
            'enabled'     => [
                'title'   => __( 'Enable/Disable', 'dc-nagad' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Nagad', 'dc-nagad' ),
                'default' => 'yes',
            ],
            'test_mode'   => [
                'title'   => __( 'Test Mode', 'dc-nagad' ),
                'type'    => 'select',
                'options' => [ "on" => "ON", "off" => "OFF" ],
                'default' => __( 'off', 'dc-nagad' ),
            ],
            'title'       => [
                'title'   => __( 'Title', 'dc-nagad' ),
                'type'    => 'text',
                'default' => __( 'Nagad Payment', 'dc-nagad' ),
            ],
            'username'    => [
                'title' => __( 'User Name', 'dc-nagad' ),
                'type'  => 'text',
            ],
            'password'    => [
                'title' => __( 'Password', 'dc-nagad' ),
                'type'  => 'password',
            ],
            'app_key'     => [
                'title' => __( 'App Key', 'dc-nagad' ),
                'type'  => 'text',
            ],
            'app_secret'  => [
                'title' => __( 'App Secret', 'dc-nagad' ),
                'type'  => 'text',
            ],
            'description' => [
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
    public function init_actions(  ) {
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

        if ( $this->get_option( 'test_mode' ) == 'off' ) {
            $script = "";
        } else {
            $script = "";
        }

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

//        wp_localize_script( 'bkash_checkout', 'bkash_params', $data );
    }
}
