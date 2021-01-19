<?php
if ( ! class_exists( 'WC_IIKO_INIT_CLASS' ) ) {

    class WC_IIKO_INIT_CLASS {

        /**
         * @var Singleton The reference the *Singleton* instance of this class
         */
        private static $instance;

        /**
         * Returns the *Singleton* instance of this class.
         *
         * @return Singleton The *Singleton* instance.
         */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Private clone method to prevent cloning of the instance of the
         * *Singleton* instance.
         *
         * @return void
         */
        private function __clone() {}

        /**
         * Private unserialize method to prevent unserializing of the *Singleton*
         * instance.
         *
         * @return void
         */
        private function __wakeup() {}

        /**
         * Protected constructor to prevent creating a new instance of the
         * *Singleton* via the `new` operator from outside of this class.
         */
        private function __construct() {
            add_action( 'admin_init', array( $this, 'install' ) );
            $this->init();
        }

        /**
         * Init the plugin after plugins_loaded so environment variables are set.
         *
         * @since 1.0.0
         * @version 4.0.0
         */
        public function init() {
            require_once dirname(__FILE__) . '/woocommerce-iiko.php';
        }

        /**
         * Updates the plugin version in db
         *
         * @since 3.1.0
         * @version 4.0.0
         */
        public function update_plugin_version() {
            delete_option( 'wc_iiko_version' );
            update_option( 'wc_iiko_version', WC_IIKO_VERSION );
        }

        /**
         * Handles upgrade routines.
         *
         * @since 3.1.0
         * @version 3.1.0
         */
        public function install() {
            if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
                return;
            }

            if ( ! defined( 'IFRAME_REQUEST' ) && ( WC_IIKO_VERSION !== get_option( 'wc_iiko_version' ) ) ) {
                do_action( 'woocommerce_iiko_updated' );

                if ( ! defined( 'WC_IIKO_INSTALLING' ) ) {
                    define( 'WC_IIKO_INSTALLING', true );
                }

                $this->update_plugin_version();
            }
        }

    }

    WC_IIKO_INIT_CLASS::get_instance();
}