<?php
/**
 * Plugin Name: WooCommerce Add Form to Product
 * Plugin URI: http://www.isikom.net/
 * Description: Add a custom text form to an item. This is required when item in your shop need to get a custom text, for example wedding invitations, plates, serigraphs for pens and more
 * Author: Michele Menciassi
 * Author URI: https://plus.google.com/+MicheleMenciassi
 * Version: 0.0.2
 * License: GPLv2 or later
 */
 
 // Exit if accessed directly
if (!defined('ABSPATH'))
  exit;

//Checks if the WooCommerce plugins is installed and active.
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	if (!class_exists('WooCommerce_AddFormToProduct')) {
		class WooCommerce_AddFormToProduct {
			var $statuses = array('waiting-text', 'waiting-approval-preview');
			
			/**
			 * Gets things started by adding an action to initialize this plugin once
			 * WooCommerce is known to be active and initialized
			 */
			public function __construct() {
				add_action('woocommerce_init', array(&$this, 'init'));
			}

			/**
			 * to add the necessary actions for the plugin
			 */
			public function init() {
				if (is_admin()){
			        // backend stuff
			        add_action('woocommerce_product_write_panel_tabs', array(&$this, 'product_write_panel_tab'));
			        add_action('woocommerce_product_write_panels', array(&$this, 'product_write_panel'));
			        add_action('woocommerce_process_product_meta', array(&$this, 'product_save_data'), 10, 2);
			        add_action('admin_enqueue_scripts', array(&$this, 'wooaf2p_add_admin_scripts'));
				}else{
			        // frontend stuff
				}
				add_shortcode("text_submition", array(&$this, 'textSubmitionShortcode'));
				add_filter('woocommerce_email_classes', array(&$this, 'request_text_email'));
				add_filter('woocommerce_resend_order_emails_available', array(&$this, 'request_text_email_available'), 10, 1);
		    }
			
			/**
			 * run on plugin activation 
			 */
			public function activation() {
				//TODO
				/*
				foreach($statuses as $status) {
					wp_insert_term($status, 'shop_order_status');
				}
				*/				
			}

			/**
			 * run on plugin uninstall 
			 */
			public function uninstall() {
			}

			/**
			 * run on plugin deactivation 
			 */
			public function deactivation() {
				//TODO
				/*
				foreach($statuses as $status) {
					wp_delete_term($status,'shop_order_status');
				}
				*/
			}

			/**
			 * shortcode that manage the text submition page 
			 */
			public function textSubmitionShortcode($atts, $content = null) {
				//extract(shortcode_atts(array(
				//   'href' => 'http://'
				//), $atts));
				//return '<a href="'.$href.'">'.$content.'</a>';
				$order_id = intval($_REQUEST['order']) > 0 ? intval($_REQUEST['order']) : '';
				$order = new WC_Order();
				$html = "<h2>pippo $order_id </h2>";
				if ($order->get_order($order_id)){
					$html .= "l'ordine esiste";
					$html .= "<br>Status: " . $order->status;
					$html .= "<br>User: " . $order->customer_user;
					$html .= "<br>Key: " . $order->order_key;
					print_r($order); 
				}else{
					$html .= "l'ordine non esiste";
				}
				return $html;
			}

			/**
			 * creates the tab for the administrator, where administered product sample.
			 */
			public function product_write_panel_tab() {
				echo "<li><a class='added_af2p' href=\"#af2p_tab\">" . __('Form','woo_af2p') . "</a></li>";
			}

			/**
			 * build the panel for the administrator.
			 */
			public function product_write_panel() {
	        	global $post;
				if (in_array('contact-form-7/wp-contact-form-7.php', apply_filters('active_plugins', get_option('active_plugins')))) {
					$WPCF7 = new WPCF7_ContactForm();
					$forms = $WPCF7->find();
					//print_r($forms);
					$af2p_enable = get_post_meta($post->ID, 'af2p_enamble', true) ? get_post_meta($post->ID, 'af2p_enamble', true) : false;
					$af2p_forms = get_post_meta($post->ID, 'af2p_forms', true) ? get_post_meta($post->ID, 'af2p_forms', true) : array();
					$af2p_forms_selected = array();
					?>
					<div id="af2p_tab" class="panel woocommerce_options_panel">
						<p class="form-field af2p_enamble_field ">
							<label for="af2p_enamble"><?php _e('Enable Form', 'woo_af2p');?></label>
							<input type="checkbox" class="checkbox" name="af2p_enamble" id="af2p_enamble" value="yes" <?php echo $af2p_enable ? 'checked="checked"' : ''; ?>> <span class="description"><?php _e('Enable or disable form option for this item.', 'woo_af2p'); ?></span>
						</p>
						<p class="form-field af2p_add_form_field">
							<label for="af2p_add_form"><?php _e('Add a form', 'woo_af2p'); ?></label>
							<select id="af2p_add_form" name="af2p_add_form" class="select">
								<option value=""><?php _e('select a form', 'woo_af2p'); ?></option>
								<?php
								if (!empty($forms) and is_array($forms)){
									foreach ($forms as $key => $form){
										if (in_array($form->name, $af2p_forms)){
											$af2p_forms_selected[$form->name] = $form->title;
										   	//echo "Match found";
										}else{
											echo '<option value="'.$form->name.'">'.$form->title.'</option>';
										}
									}
								}
								?>
							</select>
							<br>
							<button id="af2p_add_form_button" class="button"><?php _e('add', 'woo_af2p'); ?></button>
						</p>
						<table id="forms_selected" class="wp-list-table widefat forms">
							<caption><?php _e('Selected forms', 'woo_af2p'); ?></caption>
							<thead>
							<tr>
								<th><span class="dashicons dashicons-admin-tools"></span></th>
								<th><?php _e('form name', 'woo_af2p'); ?></th>
							</tr>
							</thead>
							<tbody>
							<tr class="solo" <?php if(!empty($af2p_forms_selected)){ echo 'style="display:none"'; }?>>
								<td colspan="2"><?php _e('no form selected', 'woo_af2p'); ?></td>
							</tr>
							<?php
							if(!empty($af2p_forms_selected)){
								 foreach($af2p_forms_selected as $name => $title){
								 	echo '<tr><td><a class="remove dashicons" data-item="'.$name.'"></a><input type="hidden" name="af2p_forms[]" value="'.$name.'"></td><td>'.$title.'</td></tr>';
								 }
							}
							?>
							</tbody>							
						</table>
					</div>
					<?php
				}else{
					?>
					<div id="af2p_tab" class="panel woocommerce_options_panel">
						<p class="form-field">
							<?php _e('For add a form to this product you need to download and activate Contact Form 7 plugin', 'woo_af2p');?>
						</p>
					</div>
					<?php					
				}
			}

			/**
			 * updating the database post.
			 */
			public function product_save_data($post_id, $post) {
				$af2p_enamble = $_POST['af2p_enamble'];
				if (empty($af2p_enamble)) {
					delete_post_meta($post_id, 'af2p_enamble');
				}else{
					update_post_meta($post_id, 'af2p_enamble', true);
				}
				$af2p_forms = !empty($_POST['af2p_forms']) && is_array($_POST['af2p_forms']) ? $_POST['af2p_forms'] : array();
				update_post_meta($post_id, 'af2p_forms', $af2p_forms);
			}

			/**
			 * Enqueue plugin style-file
			 */
			function wooaf2p_add_admin_scripts() {
				// Respects SSL, style-admin.css is relative to the current file
				wp_register_style( 'wooaf2p-styles', plugins_url('css/style-admin.css', __FILE__) );
				wp_register_script( 'wooaf2p-scripts', plugins_url('js/script-admin.js', __FILE__), array('jquery') );
				wp_enqueue_style( 'wooaf2p-styles' );
				wp_enqueue_script( 'wooaf2p-scripts' );
			}

			/**
			 *  Add a custom email to the list of emails WooCommerce should load
			 *
			 * @since 0.1
			 * @param array $email_classes available email classes
			 * @return array filtered available email classes
			 */
			function request_text_email( $email_classes ) {
			    // include our custom email class
			    require( 'includes/class-wc-email-customer-request-texts.php' );
			    // add the email class to the list of email classes that WooCommerce loads
			    $email_classes['WC_Email_Customer_Request_Texts'] = new WC_Email_Customer_Request_Texts();
			    return $email_classes;
			}

			function request_text_email_available($emails) {
				array_push($emails, 'wc_request_texts');
				return $emails;
			}

		}// end class
		$woocommerce_af2p = new WooCommerce_AddFormToProduct();
		
		register_activation_hook( __FILE__, array( 'WooCommerce_AddFormToProduct', 'activation' ) );
		register_deactivation_hook( __FILE__, array( 'WooCommerce_AddFormToProduct', 'deactivation' ) );
		register_uninstall_hook( __FILE__, array( 'WooCommerce_AddFormToProduct', 'uninstall' ) );
	}
}

?>