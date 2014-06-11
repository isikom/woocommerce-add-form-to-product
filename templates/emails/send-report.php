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
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<h2><?php echo __( 'Order:', 'woocommerce' ) . ' ' . $order_id ?></h2>

<p><?php _e( "One or more errors was reported for this order. Please check and fix it.", 'woo_af2p' ); ?></p>

<p><a href="<?php echo get_admin_url() . 'post.php?post=' . ltrim ($order_id, '#') . '&amp;action=edit'; ?>" target="_blank"><?php _e( "Edit the order.", 'woo_af2p' ); ?></a></p>

<?php do_action('woocommerce_email_footer'); ?>