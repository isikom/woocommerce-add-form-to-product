<?php
/**
 * Customer request texts email
 *
 * @author		Isikom
 * @package 	WooCommerce/Templates/Emails/Plain
 * @version     2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$order_id = $order->get_order_number();

echo $email_heading . "\n\n";

echo __( "Your order to being processed needs your text. Follow the link and submit your texts.", 'woo_af2p' ) . "\n\n";

echo "****************************************************\n\n";

echo sprintf( __( 'Order number: %s', 'woocommerce'), $order_id ) . "\n";
echo sprintf( __( 'Order date: %s', 'woocommerce'), date_i18n( woocommerce_date_format(), strtotime( $order->order_date ) ) ) . "\n\n";

echo __('Forms Page','woo_af2p') . "\n";
echo get_site_url() . '/testi/?order=' . $order->id . "\n\n";

echo "****************************************************\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );