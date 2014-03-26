<?php
/**
 * Customer request texts email
 *
 * @author		Isikom
 * @package 	WooCommerce/Templates/Emails
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$order_id = $order->get_order_number();
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php _e( "Texts are been submitted for this order.", 'woo_af2p' ); ?></p>

<h2><?php echo __( 'Order:', 'woocommerce' ) . ' ' . $order_id ?></h2>

<?php do_action('woocommerce_email_footer'); ?>