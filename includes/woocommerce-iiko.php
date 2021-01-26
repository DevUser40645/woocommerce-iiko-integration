<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'WC_IIKO_API' ) ) {
    class WC_IIKO_API {

        protected static $_instance = null;

        protected static $api_url = 'https://iiko.biz:9900/api/0/';

        protected static $access_token = '';
        protected static $organization_id = '';

        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __construct(){
            $this->init();
        }

        /**
         * Load hook in functions.
         */
        public function init() {
            add_action( 'woocommerce_thankyou', array( &$this, 'generate_data_for_iiko' ) );
        }

        /**
         * @param $order_id
         */
        public function generate_data_for_iiko( $order_id ){
            self::$access_token = self::get_iiko_access_token();
            self::$organization_id = self::get_iiko_organization_id()[0]->id;
            $items_for_request = self::get_order_info_for_request( $order_id );
//            var_dump($items_for_request);
        }

        protected static function get_iiko_access_token() {
//            $iiko_login = get_option('woocommerce_iiko_login');
//            $iiko_password = get_option('woocommerce_iiko_password');
            $iiko_login = 'demoDelivery';
            $iiko_password = 'PI1yFaKFCGvvJKi';
            $requestUrl = self::$api_url . 'auth/access_token?user_id=' . $iiko_login . '&user_secret=' . $iiko_password;
            $communication = new Communication();

            return $communication::httpGetRequest($requestUrl);

        }

        protected static function get_iiko_organization_id() {
            $requestUrl = self::$api_url . 'organization/list?access_token=' . self::$access_token . '&request_timeout=00:01:00';
            $communication = new Communication();

            return $communication::httpGetRequest($requestUrl);
        }
        protected static function get_iiko_nomenclatures() {

            $requestUrl = self::$api_url . 'nomenclature/'. self::$organization_id . '?access_token='. self::$access_token;
            $communication = new Communication();

            $nomenclatures = $communication::httpGetRequest($requestUrl);

            return $nomenclatures->products;
        }

        protected static function get_create_iiko_customer( $order, $userPhone ){
            $userPhone = '71235678901';
            $requestUrl = self::$api_url . 'customers/get_customer_by_phone?access_token='. self::$access_token . '&organization='. self::$organization_id . '&phone=' . $userPhone;
            $communication = new Communication();
            $customer = $communication::httpGetRequest($requestUrl);

            if ( isset($customer->message) && strripos( $customer->message, 'There is no user with phone') == false ) {

                $requestUrl = self::$api_url . 'customers/create_or_update?access_token='. self::$access_token . '&organization='. self::$organization_id;
                $postFields = array(
                    'customer' => array(
                        'name'          => $order->get_billing_first_name(),
                        'phone'         => $userPhone,
                        'email'         => $order->get_billing_email(),
                        'surName'       => $order->get_billing_last_name(),
                        'consentStatus' => '1'
                    ),
                );
                $customer = $communication::httpPostRequest($requestUrl, json_encode($postFields));
            }
            return $customer->id;

        }

        protected static function get_formatted_phone($phone, $mask = '#', $codeSplitter = '0') {
            $format = array(
                '13'=>'+############', // for +38 0XX XX XXX XX or 38 0XX XX XXX XX
                '10'=>'+38##########', // for 0XX XX XXX XX
                '9'=>'+380##########' // for XX XX XXX XX
            );

            $phone = preg_replace('/[^0-9]/', '', $phone);

            if (is_array($format)) {
                if (array_key_exists(strlen($phone), $format)) {
                    $format = $format[strlen($phone)];
                } else {
                    return $phone;
                }
            }

            $pattern = '/' . str_repeat('([0-9])?', substr_count($format, $mask)) . '(.*)/';

            $format = preg_replace_callback(
                str_replace('#', $mask, '/([#])/'),
                function () use (&$counter)  {
                    return '${' . (++$counter) . '}';
                },
                $format
            );

            return ($phone) ? trim(preg_replace($pattern, $format, $phone, 1)) : false;
        }

        protected static function get_payment_methods(){
            $requestUrl = self::$api_url . 'rmsSettings/getPaymentTypes?organization='. self::$organization_id . '&access_token='. self::$access_token;
            $communication = new Communication();
            $payment_methods = $communication::httpGetRequest($requestUrl);
            var_dump($payment_methods);

        }

        protected static function get_order_info_for_request( $order_id ) {
            $order = wc_get_order( $order_id );
            $billing_phone = self::get_formatted_phone( $order->get_billing_phone() );
            $iiko_customer = self::get_create_iiko_customer( $order, $billing_phone );
            $payment_methods = self::get_payment_methods();
            $array_product = array();
            // Get and Loop Over Order Items
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                if( $item->get_type() == 'variable' ) {
                    $product_id =  $item->get_variation_id();
                }
                $product = $item->get_product();
                $iiko_product_id = get_post_meta($product_id, 'iiko_product_id', true);
                $array_product[] = array(
                    "id"     => $iiko_product_id,
                    "name"   => $item->get_name(),
                    "amount" => $item->get_quantity(),
                    "code"   => $product->get_sku(),
                    "sum"    => $item->get_total(),
                );
            }

//            $order_details = array(
//                "organization" => self::$organization_id,
//                "customer" => array(
//                    "id" => $iiko_customer,
//                    "name" => $order->get_billing_first_name(),
//                    "phone" => $billing_phone,
//                    "email" => $order->get_billing_email()
//                ),
//                "order" => array(
//                    "date" => $order->get_date_created();,
//                    "phone" => $billing_phone,
//                    "customerName" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
//                    "fullSum" => $order->get_total();,
//                    "isSelfService" => 'false',
//                    "items" => $array_product
//                ),
//                "address" => array(
//                    "city" => $order->get_billing_city();,
//                    "street" => $order->get_billing_address_1();,
//                    "home" => $order->get_billing_address_2();,
//                    "housing" => null,
//                    "apartment" => null,
//                    "entrance" => null,
//                    "floor" => null,
//                    "doorphone" => null,
//                    "comment" => $order->get_customer_note();
//                ),
//                "paymentItems" => array(
//                    "sum"  => $order->get_total();,
//                    "paymentType"  => array(
//                        "id"  => $paymentTypeId,
//                        "code"  => $paymentTypeCode,
//                        "name"  => "",
//                        "comment"  => "full"
//                    ),
//                    "isProcessedExternally"  => true
//                )
//            );

            return json_encode($array_product);
        }
    }
    WC_IIKO_API::instance();
}