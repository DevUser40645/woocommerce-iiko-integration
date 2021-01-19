<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'WC_IIKO_SETTINGS_TAB' ) ) {
    function iiko_integration_add_settings() {
        class WC_IIKO_SETTINGS_TAB extends WC_Settings_Page {
            public function __construct(){
                $this->id = 'settings-iiko-api';
                $this->label = __( 'Iiko API Settings', 'wc-iiko' );

                // Add the tab to the tabs array
                add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 99 );

                // Add settings
                add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );

                // Process/save the settings
                add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
            }

            /**
             *	Get settings array
             *
             *	@return array
             */
            public function get_settings() {
                $settings = array(
                    /**
                    *	For settings types, see:
                    *	https://github.com/woocommerce/woocommerce/blob/fb8d959c587ee95f543e682e065192553b3cc7ec/includes/admin/class-wc-admin-settings.php#L246
                    */
                    // Iiko input
                    array(
                        'title' => __( 'Iiko Settings', 'wc-iiko' ),
                        'type' => 'title',
                        'desc' =>  __( 'Manage your Iiko settings for the WooCommerce Iiko Integration plugin.', 'wc-iiko' ),
                        'id' => 'woocommerce_redirects_license_settings'
                    ),
                    array(
                        'title' => __( 'Iiko Login', 'wc-iiko' ),
                        'type' => 'text',
                        'desc' => __( 'Add your Iiko Login.', 'wc-iiko' ),
                        'desc_tip' => true,
                        'id' => 'woocommerce_iiko_login',
                        'css' => 'min-width:300px;',
                    ),
                    array(
                        'title' => __( 'Iiko password', 'wc-iiko' ),
                        'type' => 'text',
                        'desc' => __( 'Add your Iiko password.', 'wc-iiko' ),
                        'desc_tip' => true,
                        'id' => 'woocommerce_iiko_password',
                        'css' => 'min-width:300px;',
                    ),
                    array(
                        'type' => 'sectionend',
                        'id' => 'woocommerce_iiko_api_settings'
                    ),
                );

                return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
            }

            /**
             *	Output the settings
             */
            public function output() {
                $settings = $this->get_settings();
                WC_Admin_Settings::output_fields( $settings );
            }

            /**
             *	Process save
             *
             *	@return array
             */
            public function save() {

                global $current_section;

                $settings = $this->get_settings();

                WC_Admin_Settings::save_fields( $settings );

                if ( $current_section ) {
                    do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
                }
            }

        }
        return new WC_IIKO_SETTINGS_TAB();

    }
    add_filter( 'woocommerce_get_settings_pages', 'iiko_integration_add_settings', 15 );
}