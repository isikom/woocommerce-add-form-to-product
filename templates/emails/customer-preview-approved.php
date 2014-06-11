<?php
/**
 * Customer preview approved email
 *
 * @author Isikom
 * @package WooCommerce/Templates/Emails/HTML
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$order_id = $order->get_order_number();
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php _e( "We are pleased, to inform you that with your approval of the preview your order will be quickly put into production. You will receive the ordered goods as soon as possible.", 'woo_af2p' ); ?></p>

<?php do_action('woocommerce_email_footer'); ?>