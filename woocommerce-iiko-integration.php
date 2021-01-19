<?php
/**
 * Plugin Name: WooCommerce Iiko Integration
 * Plugin URI:
 * Description: Send your completed orders to your Iiko program via Iiko API.
 * Version: 1.0.0
 * Author: Free UA
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WC_IIKO', '1.0.0' );
define( 'WC_IIKO_MAIN_FILE', __FILE__ );

function woocommerce_iiko_missing_wc_notice() {
    /* translators: 1. URL link. */
    echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'WooCommerce Iiko Integration requires WooCommerce to be installed and active. You can download %s here.', 'wc-iiko' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

add_action( 'plugins_loaded', 'woocommerce_iiko_init' );
function woocommerce_iiko_init(){
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'woocommerce_iiko_missing_wc_notice' );
        return;
    }
    require_once 'includes/woocommerce-iiko-init-class.php';
    require_once 'includes/woocommerce-iiko-product-meta.php';
    require_once 'includes/woocommerce-iiko-settings-tab.php';
}