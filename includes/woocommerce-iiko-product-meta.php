<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'WC_IIKO_PRODUCT_META' ) ) {
    class WC_IIKO_PRODUCT_META {

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
            add_action( 'woocommerce_product_options_general_product_data', array( &$this, 'display_iiko_id_field' ) );
            add_action( 'woocommerce_process_product_meta', array( &$this, 'woocommerce_product_iiko_id_fields_save' ) );
            add_action( 'woocommerce_product_after_variable_attributes', array( &$this, 'woocommerce_term_production_fields' ), 10, 3 );
            add_action( 'woocommerce_save_product_variation', array( &$this, 'woocommerce_save_variation_settings_fields' ), 10, 2 );
        }

        /* Display fields for the new panel
        * @see https://docs.woocommerce.com/wc-apidocs/source-function-woocommerce_wp_checkbox.html
        * @since   1.0.0
        */
        public function display_iiko_id_field() { ?>

            <div class=" product_custom_field ">

                <?php
                woocommerce_wp_text_input(
                    array(
                        'id'        => 'iiko_product_id',
                        'label'     => __( 'Iiko product id', 'wc-iiko' ),
                        'type'      => 'text',
                        'desc_tip'  => __( 'Enter the Iiko product id this product', 'wc-iiko' )
                    )
                );
                ?>
            </div>

        <?php }

        public function woocommerce_product_iiko_id_fields_save( $post_id )
        {
            // Iiko product id Field
            $iiko_product_id = $_POST['iiko_product_id'];
            if ( ! empty( $iiko_product_id ) )
                update_post_meta( $post_id, 'iiko_product_id', esc_attr( $iiko_product_id ) );
        }

        public function woocommerce_term_production_fields( $loop, $variation_data, $variation ) {
            woocommerce_wp_text_input( array(
                'id'                => 'iiko_product_id', // id поля
                'label'             => 'Iiko product id', // Надпись над полем
                'description'       => 'Enter the Iiko product id this product',// Описание поля
                'desc_tip'          => 'true', // Всплывающая подсказка
                'type'              => 'text', // Тип поля
                'value'             => get_post_meta( $variation->ID, 'iiko_product_id', true ),
            ) );
        }

        function woocommerce_save_variation_settings_fields( $post_id ) {
            $woocommerce__term_prod_var = $_POST['iiko_product_id'][ $post_id ];
            if (isset($woocommerce__term_prod_var) && ! empty( $woocommerce__term_prod_var ) ) {
                update_post_meta( $post_id, 'iiko_product_id', esc_attr( $woocommerce__term_prod_var ) );
            }
        }

    }
    WC_IIKO_PRODUCT_META::instance();
}