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
            var_dump($items_for_request);
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

        protected static function get_order_info_for_request( $order_id ) {
            $order = wc_get_order( $order_id );
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
//                "organization" => $organizationId,
//                "deliveryTerminalId" => $deliveryTerminalId,
//                "customer" => array(
//                    "id" => $customerId,
//                    "name" => "Asabix",
//                    "phone" => "+380979981891",
//                    "email" => "ask@asabix.com"
//                ),
//                "order" => array(
//                    "date" => $deliveryDate,
//                    "phone" => "+380979981891",
//                    "customerName" => "Asabix",
//                    "fullSum" => "613.00",
//                    "isSelfService" => true,
//                    "orderTypeId" => $orderTypeId,
//                    "personsCount" => $personsCount,
//                    "marketingSourceId" => $marketingSourceId,
//                    "items" => $array_product
//                ),
//                "address" => array(
//                    "city" => "Zhytomyr",
//                    "street" => "Vitruka",
//                    "home" => "9v",
//                    "housing" => null,
//                    "apartment" => null,
//                    "entrance" => null,
//                    "floor" => null,
//                    "doorphone" => null,
//                    "comment" => "From Small To Extra Large. Everywhere. Everytime"
//                ),
//                "paymentItems" => array(
//                    "sum"  => "613.00",
//                    "paymentType"  => array(
//                        "id"  => $paymentTypeId,
//                        "code"  => $paymentTypeCode,
//                        "name"  => "",
//                        "comment"  => "full"
//                    ),
//                    "isProcessedExternally"  => false
//                )
//            );

            return json_encode($array_product);
        }
    }
    WC_IIKO_API::instance();
}