<?php
/**
 * Customer request texts email
 *
 * @author Isikom
 * @package WooCommerce/Templates/Emails/HTML
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$order_id = $order->get_order_number();

$text_page_id = get_option('woocommerce_texts_page_id');
$text_page_permalink = get_permalink( $text_page_id ) . '?order=' . ltrim ($order_id, '#');
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php _e( "Your order to being processed needs your text. Follow the link and submit your texts.", 'woo_af2p' ); ?></p>

<h2><?php echo __( 'Order:', 'woocommerce' ) . ' ' . $order_id ?></h2>

<a target="_blank" href="<?php echo $text_page_permalink; ?>"><?php _e('Forms Page','woo_af2p'); ?></a>

<?php do_action('woocommerce_email_footer'); ?>