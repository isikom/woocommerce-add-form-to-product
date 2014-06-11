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

<h2><?php echo __( 'Order:', 'woocommerce' ) . ' ' . $order_id ?></h2>
<p><?php echo __( "A preview of your items was uploaded in your personal area", 'woo_af2p' ); ?></p>
<p><a href="<?php echo $text_page_permalink; ?>" target="_blank"><?php echo __( "see the preview", 'woo_af2p' ); ?></a></p>

<?php do_action('woocommerce_email_footer'); ?>