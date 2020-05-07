<?php

namespace DCoders\Nagad\Woocommerce;

/**
 * Class PaymentProcessor
 * @package DCoders\Nagad\Woocommerce
 */
class PaymentProcessor extends Nagad_Gateway {
    /**
     * class instance
     */
    private static $selfClassInstance;

    /**
     * order create url
     * @var string
     */
    private static $orderCreateUrl;

    /**
     * order submit url
     * @var string
     */
    private static $orderSubmitUrl;

    /**
     * Payment verification url
     * @var string
     */
    private static $verifyPaymentUrl;

    /**
     * initialize the necessary things
     *
     * @return void
     */
    public static function init() {
        date_default_timezone_set( 'Asia/Dhaka' );

        $base = "http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs/";

        self::$orderCreateUrl   = $base . "check-out/initialize/" . self::get_pgw_option( 'merchant_id' ) . "/";
        self::$orderSubmitUrl   = $base . "check-out/complete/";
        self::$verifyPaymentUrl = $base . "verify/payment/";
    }

    /**
     * @param $order_no
     * @param $amount
     * @param bool $mobile_number
     *
     * @return array|false|string
     */
    public static function checkout( $order_no, $amount, $mobile_number = false ) {
        self::init();
        $error_message = '';

        //creating order request
        $order_id = self::generate_random_string( 5 ) . $order_no;
        $response = self::checkout_init( $order_id, $mobile_number );

        if ( isset( $response['sensitiveData'] ) && isset( $response['signature'] ) ) {
            if ( $response['sensitiveData'] != "" && $response['signature'] != "" ) {
                //execute order request
                $execute = self::execute_payment( $response['sensitiveData'], $order_id, $amount, $order_no );

                if ( $execute ) {
                    if ( $execute['status'] == "Success" ) {
                        $url = json_encode( $execute['callBackUrl'] );

                        return [ 'status' => 'success', 'url' => esc_url_raw( $url ) ];
                    } else {
                        $error_message = "execute fail: " . $execute['message'];
                    }
                }
            }
        } else {
            $error_message = $response['message'];
        }

        return [ 'status' => 'fail', 'message' => $error_message ];
    }

    /**
     * @param $order_id
     * @param bool $mobile_number
     *
     * @return mixed
     */
    public static function checkout_init( $order_id, $mobile_number = false ) {
        $sensitive_data = self::get_sensitive_data( $order_id );

        $checkout_init_data = [
            'dateTime'      => date( 'YmdHis' ),
            'sensitiveData' => self::encrypt_data_with_public_key( $sensitive_data ),
            'signature'     => self::generate_signature( $sensitive_data ),
        ];

        if ( $mobile_number ) {
            $checkout_init_data['accountNumber'] = $mobile_number;
        }

        $url      = self::$orderCreateUrl . $order_id;
        $response = self::make_request( $url, $checkout_init_data );

        return $response;
    }

    /**
     * @param $sensitive_data
     * @param $order_id
     * @param $amount
     *
     * @param $original_order_no
     *
     * @return false|string
     */
    public static function execute_payment( $sensitive_data, $order_id, $amount, $original_order_no ) {
        $decrypted_response = json_decode( self::decrypt_data_with_private_key( $sensitive_data ), true );

        if ( isset( $decrypted_response['paymentReferenceId'] ) && isset( $decrypted_response['challenge'] ) ) {
            $payment_reference_id = $decrypted_response['paymentReferenceId'];

            $order_sensitive_data = [
                'merchantId'   => self::get_pgw_option( 'merchant_id' ),
                'orderId'      => $order_id,
                'currencyCode' => '050',
                'amount'       => $amount,
                'challenge'    => $decrypted_response['challenge'],
            ];

            $order_post_data = [
                'sensitiveData'          => self::encrypt_data_with_public_key( $order_sensitive_data ),
                'signature'              => self::generate_signature( $order_sensitive_data ),
                'merchantCallbackURL'    => site_url( '/dc-nagad/payment/action/' ),
                'additionalMerchantInfo' => [
                    'order_no' => $original_order_no,
                ],
            ];

            $url = self::$orderSubmitUrl . $payment_reference_id;

            $response = self::make_request( $url, $order_post_data );

            return $response;
        }

        return false;
    }

    /**
     * Verify nagad payment
     *
     * @param $payment_reference_id
     *
     * @return mixed
     */
    public static function verify_payment( $payment_reference_id ) {
        self::init();

        $url      = self::$verifyPaymentUrl . $payment_reference_id;
        $response = wp_remote_get( esc_url_raw( $url ) );
        $result   = json_decode( wp_remote_retrieve_body( $response ), true );

        return $result;
    }

    /**
     * Get self class
     *
     * @return PaymentProcessor
     */
    public static function get_self_class() {
        if ( ! self::$selfClassInstance ) {
            return self::$selfClassInstance = ( new self );
        }

        return self::$selfClassInstance;
    }

    /**
     * Get payment gateway option
     *
     * @param $key
     *
     * @return string
     */
    public static function get_pgw_option( $key ) {
        $selfClass = self::get_self_class();

        return $selfClass->get_option( $key );
    }

    /**
     * Generate random strings
     *
     * @param int $length
     *
     * @return string
     */
    public static function generate_random_string( $length = 40 ) {
        $characters        = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen( $characters );
        $random_string     = '';

        for ( $i = 0; $i < $length; $i ++ ) {
            $random_string .= $characters[ rand( 0, $characters_length - 1 ) ];
        }

        return $random_string;
    }

    /**
     * @param $data
     *
     * @return string
     */
    public static function encrypt_data_with_public_key( $data ) {
        if ( gettype( $data ) == 'array' ) {
            $data = json_encode( $data );
        }

        $pgw_public_key = self::get_pgw_option( 'payment_gateway_public_key' );
        $public_key     = "-----BEGIN PUBLIC KEY-----\n" . $pgw_public_key . "\n-----END PUBLIC KEY-----";
        $key_resource   = openssl_get_publickey( $public_key );

        openssl_public_encrypt( $data, $crypttext, $key_resource );

        return base64_encode( $crypttext );
    }

    /**
     * generate signature
     *
     * @param $data
     *
     * @return string
     */
    public static function generate_signature( $data ) {
        if ( gettype( $data ) == 'array' ) {
            $data = json_encode( $data );
        }

        $private_key = self::format_private_key();
        openssl_sign( $data, $signature, $private_key, OPENSSL_ALGO_SHA256 );

        return base64_encode( $signature );
    }

    /**
     * decrypt data with private key
     *
     * @param $crypt_text
     *
     * @return mixed
     */
    public static function decrypt_data_with_private_key( $crypt_text ) {
        $private_key = self::format_private_key();
        openssl_private_decrypt( base64_decode( $crypt_text ), $plain_text, $private_key );

        return $plain_text;
    }

    /**
     * format private key with prefix and suffix
     *
     * @return string
     */
    public static function format_private_key() {
        $merchant_private_key = self::get_pgw_option( 'merchant_private_key' );

        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n" . $merchant_private_key . "\n-----END RSA PRIVATE KEY-----";

        return $private_key;
    }

    /**
     * Get sensitive data
     *
     * @param $order_no
     *
     * @return array
     */
    public static function get_sensitive_data( $order_no ) {
        $sensitive_data = [
            'merchantId' => self::get_pgw_option( 'merchant_id' ),
            'datetime'   => date( 'YmdHis' ),
            'orderId'    => $order_no,
            'challenge'  => self::generate_random_string(),
        ];

        return $sensitive_data;
    }

    /**
     * Get client ip
     *
     * @return mixed|string
     */
    public static function get_client_ip() {
        $ipaddress = '';

        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ( $keys as $key ) {
            if ( isset( $_SERVER[ $key ] ) ) {
                $ipaddress = $_SERVER[ $key ];
                break;
            }
        }

        $ipaddress = $ipaddress ? $ipaddress : 'UNKNOWN';

        return $ipaddress;
    }

    /**
     * @param $url
     * @param $data
     *
     * @return mixed
     */
    public static function make_request( $url, $data ) {
        $args = [
            'body'        => json_encode( $data ),
            'timeout'     => '30',
            'redirection' => '30',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => self::getHeader(),
            'cookies'     => [],
        ];

        $response = wp_remote_retrieve_body( wp_remote_post( esc_url_raw( $url ), $args ) );

        return json_decode( $response, true );
    }

    /**
     * Headers data for curl request
     *
     * @return array
     */
    public static function getHeader() {
        $headers = [
            'Content-Type'     => 'application/json',
            'X-KM-Api-Version' => 'v-0.2.0',
            'X-KM-IP-V4'       => self::get_client_ip(),
            'X-KM-Client-Type' => 'PC_WEB',
        ];


        return $headers;
    }
}
