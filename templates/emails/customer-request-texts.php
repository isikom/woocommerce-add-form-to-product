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

<p><?php _e( "Your order to being processed needs your text. Follow the link and submit your texts.", 'woo_af2p' ); ?></p>

<h2><?php echo __( 'Order:', 'woocommerce' ) . ' ' . $order_id ?></h2>

<a target="_blank" href="<?php echo get_site_url(); ?>/testi/?order=<?php echo $order->id; ?>"><?php _e('Forms Page','woo_af2p'); ?></a>

<?php do_action('woocommerce_email_footer'); ?>