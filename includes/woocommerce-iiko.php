<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'WC_IIKO_API' ) ) {
    class WC_IIKO_API {

        protected static $_instance = null;

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
            // action...
        }
    }
    WC_IIKO_API::instance();
}