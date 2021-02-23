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
            self::$access_token = trim(self::get_iiko_access_token(), '"');
            self::$organization_id = self::get_iiko_organization_id()[0]['id'];
            $items_for_request = self::get_order_info_for_request( $order_id );

//            $created_order_iiko = self::create_order_in_iiko( $items_for_request );
            if ( empty( $created_order_iiko ) ) {
                self::send_notification_to_admin( $order_id, false );
            } else {
                self::send_notification_to_admin( $order_id );
            }

        }

        protected static function get_iiko_access_token() {
            $iiko_login = get_option('woocommerce_iiko_login');
            $iiko_password = get_option('woocommerce_iiko_password');
//            $iiko_login = 'demoDelivery';
//            $iiko_password = 'PI1yFaKFCGvvJKi';
            $requestUrl = self::$api_url . 'auth/access_token';
            $params = array(
                'user_id'     => $iiko_login,
                'user_secret' => $iiko_password
            );
            $communication = new Communication();

            return $communication::httpGetRequest($requestUrl, $params);

        }

        protected static function get_iiko_organization_id() {
            $requestUrl = self::$api_url . 'organization/list';
            $params = array(
                'access_token' => self::$access_token
            );
            $communication = new Communication();
            $orgList = $communication::httpGetRequest($requestUrl, $params);
            return $orgList;
        }

        protected static function get_create_iiko_customer( $order, $userPhone ){
            $requestUrl = self::$api_url . 'customers/get_customer_by_phone';
            $params = array(
                'access_token' => self::$access_token,
                'organization' => self::$organization_id,
                'phone'        => $userPhone
            );
            $communication = new Communication();
            $customer = $communication::httpGetRequest($requestUrl, $params);

            if ( isset($customer["message"]) && strripos( $customer["message"], 'There is no user with phone') == 0 ) {
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
            return $customer['id'];

        }

        protected static function get_formatted_phone( $phone, $mask = '#' ) {
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
            $requestUrl = self::$api_url . 'rmsSettings/getPaymentTypes';
            $params = array(
                'organization' => self::$organization_id,
                'access_token' => self::$access_token
            );

            $communication = new Communication();
            $payment_methods = $communication::httpGetRequest($requestUrl, $params);

            return $payment_methods;

        }

        protected static function get_delivery_terminals(){
            $requestUrl = self::$api_url . 'deliverysettings/getDeliveryTerminals';
            $params = array(
                'organization' => self::$organization_id,
                'access_token' => self::$access_token
            );

            $communication = new Communication();
            $delivery_terminals = $communication::httpGetRequest($requestUrl, $params);

            return $delivery_terminals;

        }

        protected static function get_order_info_for_request( $order_id ) {
            $order                = wc_get_order( $order_id );
            $billing_phone        = self::get_formatted_phone( $order->get_billing_phone() );
            $iiko_customer        = self::get_create_iiko_customer( $order, $billing_phone );
            $payment_methods      = self::get_payment_methods();
            $order_payment_method = $order->get_payment_method();
            $shipping_method      = $order->get_shipping_method();
            $delivery_terminals   = self::get_delivery_terminals();

            $pos = strripos($shipping_method, 'Липки');
            if ($pos === false) {
                $iiko_delivery_terminal = array_search('Fujiwara Yoshi: Основная группа', array_column($delivery_terminals['deliveryTerminals'], 'deliveryRestaurantName'));
            } else {
                $iiko_delivery_terminal = array_search('Новопечерские Липки: Драгомирова', array_column($delivery_terminals['deliveryTerminals'], 'deliveryRestaurantName'));
            }
            $delivery_terminal_id = $delivery_terminals['deliveryTerminals'][$iiko_delivery_terminal]['deliveryTerminalId'];

            if ( $order_payment_method == 'cod' ) {
                $iiko_payment_type = array_search('CASH', array_column($payment_methods['paymentTypes'], 'code'));
            } else {
                $iiko_payment_type = array_search('LPAY', array_column($payment_methods['paymentTypes'], 'code'));
            }
            $iiko_payment_method = $payment_methods['paymentTypes'][$iiko_payment_type];
            $order_date = date( 'Y-m-d H:i:s', $order->get_date_created()->getOffsetTimestamp());

            $array_product = array();
            // Get and Loop Over Order Items
            foreach ( $order->get_items() as $item_id => $item ) {
                $product = $item->get_product();
                if( $product->get_type() == 'variation' ) {
                    $product_id =  $item->get_variation_id();
                } else {
                    $product_id = $item->get_product_id();
                }
                $iiko_product_id = get_post_meta($product_id, 'iiko_product_id', true);

                $array_product[] = array(
                    "id"     => $iiko_product_id,
                    "name"   => $item->get_name(),
                    "amount" => $item->get_quantity(),
                    "code"   => $product->get_sku(),
                    "sum"    => $item->get_total(),
                );

            }

            $order_details = array(
                "organization" => self::$organization_id,
                "deliveryTerminalId" => $delivery_terminal_id,
                "customer" => array(
                    "id" => $iiko_customer,
                    "name" => $order->get_billing_first_name(),
                    "phone" => $billing_phone,
                    "email" => $order->get_billing_email()
                ),
                "order" => array(
                    "date" => $order_date,
                    "phone" => $billing_phone,
                    "customerName" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    "fullSum" => $order->get_total(),
                    "isSelfService" => 'false',
                    "items" => $array_product
                ),
                "address" => array(
                    "city" => $order->get_billing_city(),
                    "street" => $order->get_billing_address_1(),
                    "home" => $order->get_billing_address_2(),
                    "housing" => null,
                    "apartment" => null,
                    "entrance" => null,
                    "floor" => null,
                    "doorphone" => null,
                    "comment" => $order->get_customer_note()
                ),
                "paymentItems" => array(
                    "sum"  => $order->get_total(),
                    "paymentType"  => array(
                        "id"  => $iiko_payment_method["id"],
                        "code"  => $iiko_payment_method["code"],
                        "name"  => $iiko_payment_method["name"]
                    ),
                    "isProcessedExternally"  => true
                )
            );

            return $order_details;
        }

        protected static function create_order_in_iiko( $items_for_request ) {
            $requestUrl = self::$api_url . 'orders/add?access_token='. self::$access_token . '&organization='. self::$organization_id;
            $communication = new Communication();
            $order = $communication::httpPostRequest($requestUrl, json_encode($items_for_request));

            return $order;
        }

        protected static function send_notification_to_admin( $order_id, $type = true ) {
            $subject = 'Добавление ордера в Iiko';
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= 'Content-Type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: Доставка блюд из ресторана Fujiwara YOSHI <dostavka@yoshi-fujiwara.ua>' . "\r\n";
            $recipients = get_option( 'woocommerce_new_order_settings' )["recipient"];
            $message = self::get_mail_header();
            if ( $type ) {
                $message .= '<div>Ордер ' . $order_id . ' добавлен в Iiko успешно!</div>>';
            } else{
                $message .= '<div>Ордер ' . $order_id . ' не был добавлен в Iiko по какой-то причине! Обратитесь в техподдержку для выяснения причины!</div>';
            }
            $message .=  self::get_mail_footer();

            $result = wp_mail( $recipients, $subject, $message, $headers );

            return $result;
        }

        private static function get_mail_header() {
            ob_start(); ?>
            <!DOCTYPE html>
            <html dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
                    <title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
                </head>
                <body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
                    <div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
                        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
                            <tr>
                                <td align="center" valign="top">
                                    <div id="template_header_image">
                                        <?php
                                        if ( $img = get_option( 'woocommerce_email_header_image' ) ) {
                                            echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . get_bloginfo( 'name', 'display' ) . '" /></p>';
                                        }
                                        ?>
                                    </div>
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container">
                                        <tr>
                                            <td align="center" valign="top">
                                                <!-- Header -->
                                                <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_header">
                                                    <tr>
                                                        <td id="header_wrapper">
                                                            <h1>Добавление ордера в Iiko</h1>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- End Header -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" valign="top">
                                                <!-- Body -->
                                                <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body">
                                                    <tr>
                                                        <td valign="top" id="body_content">
                                                            <!-- Content -->
                                                            <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                                <tr>
                                                                    <td valign="top">
                                                                        <div id="body_content_inner">
            <?php
            $html = ob_get_clean();
            return $html;
        }

        private static function get_mail_footer() {
            ob_start(); ?>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                            <!-- End Content -->
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- End Body -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" valign="top">
                                                <!-- Footer -->
                                                <table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer">
                                                    <tr>
                                                        <td valign="top">
                                                            <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                                                <tr>
                                                                    <td colspan="2" valign="middle" id="credit">
                                                                        <?php echo wpautop( wp_kses_post( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) ); ?>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- End Footer -->
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </body>
            </html>
            <?php
            $html = ob_get_clean();
            return $html;
        }
    }
    WC_IIKO_API::instance();
}