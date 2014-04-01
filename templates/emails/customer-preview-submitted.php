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

<h2><?php echo __( 'Order:', 'woocommerce' ) . ' ' . $order_id ?></h2>
<p><?php echo __( "A preview of your items was uploaded in your personal area", 'woo_af2p' ); ?></p>

<?php do_action('woocommerce_email_footer'); ?>