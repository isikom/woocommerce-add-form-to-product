<?php
/**
 * Plugin Name: WooCommerce Add Form to Product
 * Plugin URI: http://www.isikom.net/
 * Description: Add a custom text form to an item. This is required when item in your shop need to get a custom text, for example wedding invitations, plates, serigraphs for pens and more
 * Author: Michele Menciassi
 * Author URI: https://plus.google.com/+MicheleMenciassi
 * Version: 0.5.5
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
			var $form_id = 0;
						
			/**
			 * Gets things started by adding an action to initialize this plugin once
			 * WooCommerce is known to be active and initialized
			 */
			public function __construct() {
				add_action('woocommerce_init', array($this, 'init'));
			}

			/**
			 * to add the necessary actions for the plugin
			 */
			public function init() {
				if (defined(WPCF7_PLUGIN_DIR)){
					require_once WPCF7_PLUGIN_DIR . '/includes/controller.php';
				}
				if (is_admin()){
			        // backend stuff
			        add_action('woocommerce_product_write_panel_tabs', array($this, 'product_write_panel_tab'));
			        add_action('woocommerce_product_write_panels', array($this, 'product_write_panel'));
			        add_action('woocommerce_process_product_meta', array($this, 'product_save_data'), 10, 2);
			        add_action('admin_enqueue_scripts', array($this, 'wooaf2p_add_admin_scripts'));
					add_action('add_meta_boxes', array($this, 'wooaf2p_meta_boxes'));
					add_action('save_post', array($this, 'wooaf2p_meta_boxes_save'));
					//AJAX FUNCTIONS
					add_action('wp_ajax_send_text', array($this, 'send_text'));
					add_action('wp_ajax_nopriv_send_text', array($this, 'send_text'));					
					add_action('wp_ajax_send_report', array($this, 'send_report'));
					add_action('wp_ajax_nopriv_send_report', array($this, 'send_report'));					
					add_action('wp_ajax_send_approval', array($this, 'send_approval'));
					add_action('wp_ajax_nopriv_send_approval', array($this, 'send_approval'));
					
					//NOTIFICHE
					add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'send_transactional_email' ), 10, 10 );
				}else{
			        // frontend stuff
			        add_filter('wpcf7_form_elements', array($this, 'wpcf7_set_form_hidden'), 10, 1 );
					add_filter('wpcf7_form_id_attr', array($this, 'wpcf7_set_form_id'), 10, 1 );
			        add_action("wpcf7_before_send_mail", array($this, 'wpcf7_save_form_data'));
					add_filter('woocommerce_my_account_my_orders_actions', array($this, 'add_orders_actions'), 10, 2);
			        add_action('wp_enqueue_scripts', array($this, 'wooaf2p_add_wp_scripts'));
				}
				
				
				add_action('woocommerce_after_checkout_validation', array($this, 'after_checkout_validation'));
				
				add_action('woocommerce_checkout_update_order_meta', array($this, 'update_order_meta'));
				
				/*add_action('woocommerce_add_order_item_meta', array($this, 'add_order_meta'));
				add_action('woocommerce_checkout_order_processed', array($this, 'checkout_order_processed'));
				add_action('woocommerce_proceed_to_checkout', array($this, 'proceed_to_checkout'));
				add_filter('woocommerce_add_cart_item_data', array( $this, 'add_form_to_cart_item_data' ), 10, 3 );
				*/				
				add_shortcode("text_submition", array($this, 'textSubmitionShortcode'));
				add_filter('woocommerce_email_classes', array($this, 'request_text_email'));
				add_filter('woocommerce_resend_order_emails_available', array($this, 'request_text_email_available'), 10, 1);
		    }
						
			function wpcf7_set_form_id($form_id) {
				if ($this->form_id){
					error_log($this->form_id);
					return $this->form_id;
				}else{
					error_log($form_id);
					return $form_id;	
				}
				
			}
			
			function wpcf7_set_form_hidden($form){
				error_log("wpcf7_set_form_hidden");
				error_log($form);
				if ($this->form_id){
					$form .= '<input type="hidden" name="_product_key" value="'.$this->form_id.'">';
				}
				return $form;
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
				
				if (get_option('woocommerce_texts_page_id') == false){
					// Create post object
					$texts_post = array(
						'post_title'    => __('Texts submission', 'woo_af2p'),
						'post_content'  => '[text_submition]',
						'post_status'   => 'publish',
						'post_author'   => 1,
						'post_type'		=> 'page'
					);
										
					$post_id = wp_insert_post( $texts_post, $wp_error );
					
					update_option('woocommerce_texts_page_id', $post_id);
				}
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

			function add_orders_actions($actions, $order){
				if (get_post_meta($order->id, 'forms_required', true) === '1'){
					$actions['texts'] = array(
						'url'  => add_query_arg( 'order', $order->id, get_permalink( woocommerce_get_page_id( 'texts' ) ) ),
						'name' => __( 'Texts', 'woo_af2p' )
					);
				}
				return $actions;	
			}
			
			/**
			 * shortcode that manage the text submition page 
			 */
			public function textSubmitionShortcode($atts, $content = null) {
				global $woocommerce, $emails;
				$user_id = get_current_user_id();
				$order_id = intval($_REQUEST['order']) > 0 ? intval($_REQUEST['order']) : '';
				
				// logged?
				if (empty($user_id)) {
					?>
					<a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>" title="Login"><?php _e('You need to be logged in in your account area to see this page', 'woo_af2p'); ?></a>
					<?php
					exit;
				}
				
				// exist order number?
				if (empty($order_id)) {
					echo '<script>';
					echo 'window.location.replace("'.home_url().'");';
					echo '</script>';
					exit; 
				}
				
				$order = new WC_Order();
				$order->get_order($order_id);
				
				// exist order?
				//if (empty($order->id)){
				if (!$order->id == $order_id or !$order->customer_user == $user_id ){					
					echo '<script>';
					echo 'window.location.replace("'.home_url().'");';
					echo '</script>';
					exit; 
				}
				
				// order require texts?
				if (get_post_meta($order_id, 'forms_required', true) !== '1'){
					echo '<script>';
					echo 'window.location.replace("'.home_url().'");';
					echo '</script>';
					exit;					
				}
								
				$forms_status = get_post_meta($order_id, 'forms_status', true);
				$forms_status_description = '';
				switch ($forms_status) {
					case 'awaiting-submission':
						$forms_status_description = __('Awaiting submission', 'woo_af2p');
						break;
					case 'awaiting-preview':
						$forms_status_description = __('Awaiting preview', 'woo_af2p');
						break;
					case 'awaiting-approval':
						$forms_status_description = __('Awaiting approval', 'woo_af2p');
						break;
					case 'preview-approved':
						$forms_status_description = __('Preview approved', 'woo_af2p');
						break;
				}
				echo '<h2>' . __('Order', 'woocommerce') . ' ' . $order_id . '</h2>';
				echo '<p>' . __('Status', 'woocommerce') . ': ' . $order->status . '</p>';
				echo '<p class="dashicons-format-status action-icon">' . __('Text status', 'woo_af2p' ) . ': <strong class="forms-status ' . $forms_status . '">' . $forms_status_description . '</strong></p>';
				if ($order->status !== 'processing'){
					echo __('For send texts the order must be in processing status. Wait for changing order status.', 'woo_af2p') . '<br>';	
				}else{?>
					<?php
					$forms = get_post_meta($order_id, 'forms', true);
					
					//echo "<pre>";
					//print_r($emails);
					//echo "</pre>";

					//$mailer = $woocommerce->mailer();  
					
					//$mailer->emails['WC_Email_Customer_Request_Texts']->trigger($order_id );
					//echo "<pre>";
					//print_r($mailer->emails['WC_Email_Customer_Request_Texts']);
					//echo "</pre>";
/*
					echo "<pre>";
					print_r($emails['WC_Email_Customer_Request_Texts']);
					echo "</pre>";
*/
					if ($forms_status == 'awaiting-approval' or $forms_status == 'preview-approved'){
						$nonce_approval = wp_create_nonce('send_preview_approval');
						$nonce_report = wp_create_nonce('send_preview_report');
						$af2p_preview_url = get_post_meta($order_id, 'af2p-preview-url', true);
						?>
						<hr>
						<h3 class="dashicons-download action-icon"><?php if ($forms_status == 'preview-approved'){ _e('Preview approved','woo_af2p'); }else{ _e('Preview','woo_af2p'); }?></h3>
						<a href="<?php echo $af2p_preview_url; ?>" target="_blank"><img class="document-preview" src="<?php echo get_site_url(); ?>/wp-includes/images/crystal/document.png"></a>
						<?php
						if ($forms_status != 'preview-approved'){
						?>
						<p>
							<button class="button button-primary" name="send" id="sendapproval"><?php _e('Approve','woo_af2p'); ?></button>
						</p>
						<p class="disclaimer">
							<input type="checkbox" value="approve" name="accept-disclaimer" id="accept-disclaimer"/>
							<label for="accept-disclaimer">
							<?php _e('The approval will be final and not reversible. By approving this preview you start the order process, will no longer be possible corrections or changes. I confirm I have checked the texts and I accept them as proposed in the preview.','woo_af2p'); ?>
							</label>
						</p>
						<p id="approve-message" class="">
						</p>
						<?php } ?>
						<hr>
						<h3 class="dashicons-format-chat action-icon"><?php _e('Error reported','woo_af2p'); ?></h3>
						<?php
						$args = array(
							'post_id' 	=> $order_id,
							'approve' 	=> 'approve',
							'type' 		=> 'order_report'
						);
						//remove_filter('comments_clauses', 'woocommerce_exclude_order_comments');
						remove_filter('comments_clauses', array( 'WC_Comments' ,'exclude_order_comments'), 10, 1 );
						$notes = get_comments( $args );
						//add_filter('comments_clauses', 'woocommerce_exclude_order_comments');
						add_filter('comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );
						?>
						<ul class="order_notes" id="reports-container">
						<?php
						if ( $notes ) {
							foreach( $notes as $note ) {
								//$note_classes = get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ? array( 'customer-note', 'note' ) : array( 'note' );
								$note_classes = array( 'note' );
								?>
								<li rel="<?php echo absint( $note->comment_ID ) ; ?>" class="<?php echo implode( ' ', $note_classes ); ?>">
									<div class="note_content">
										<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
									</div>
									<p class="meta">
										<?php printf( __( 'added %s ago', 'ww_af2p' ), human_time_diff( strtotime( $note->comment_date_gmt ), current_time( 'timestamp', 1 ) ) ); ?>
									</p>
								</li>
								<?php
							}
						} else {
							if ($forms_status == 'preview-approved'){
								echo '<li>' . __( 'Any error was reported for this order.', 'woo_af2p' ) . '</li>';
							}else{
								echo '<li>' . __( 'There are no error reported for this order yet.', 'woo_af2p' ) . '</li>';
							}
						}
					
						echo '</ul>';
						
						if ($forms_status != 'preview-approved'){
						?>

						<textarea name="note" id="reportnote" placeholder="<?php _e('Write here your error note', 'woo_af2p'); ?>"></textarea>
						<p>
						<button class="button button-primary" name="send" id="sendreport"><?php _e('Report an error','woo_af2p'); ?></button>
						</p>
						<script type="text/javascript" >
						jQuery(document).ready(function($) {
							if(typeof ajaxurl === "undefined"){
								var ajaxurl ='<?php echo admin_url('admin-ajax.php'); ?>';
							}
							$('button#sendapproval').on('click', function(){
								var res = $('#accept-disclaimer').is(':checked');
								if (res == true){
									var data = {
										action: 'send_approval',
										_nonce: '<?php echo $nonce_approval; ?>',
										order: '<?php echo $order_id ?>'
									};
									// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
									$.post(ajaxurl, data, function(response) {
										console.log(response);
										if (response.success){
											$('#approve-message').html('<?php _e('Thank you for your approval, your order will be processed soon.','woo_af2p'); ?>');
											$('#approve-message').removeClass().addClass('success').show().delay(3200).fadeOut(300);
											location.reload(true);
										}else{
											$('#approve-message').html('<?php _e('Error on post approval, please try again after few minutes','woo_af2p'); ?>');
											$('#approve-message').removeClass().addClass('error').show().delay(3200).fadeOut(300);
										}
									});
								}else{
									$('#approve-message').html('<?php _e('Check the approval confirmation','woo_af2p'); ?>');
									$('#approve-message').removeClass().addClass('error').show().delay(3200).fadeOut(300);
								}
							});
							$('button#sendreport').on('click', function(){
								var note = $('#reportnote').val();
								
								if (typeof note === "string" && note.length > 0){
									var data = {
										action: 'send_report',
										_nonce: '<?php echo $nonce_report; ?>',
										order: '<?php echo $order_id ?>',
										note: note
									};
									// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
									$.post(ajaxurl, data, function(response) {
										if (response.success){
											console.log(response);
											if (typeof response.comment !== 'undefined'){
												//$(response.comment).appendTo('#reports-container');
												$('#reports-container').prepend(response.comment);
												$('#reportnote').val('');
											}
										}else{
											alert("error");
										}
									});
								}else{
									alert('<?php _e('You must insert a note', 'woo_af2p'); ?>');
								}
							});
						});
						</script>
						<?php
						}
					}

					$htmlform = ''; 
					$htmlform .= '<div id="manage_forms_container" class="'.$forms_status.'">';
					// START FORMS AREA
					if ($forms_status == 'awaiting-submission'){
						$htmlform .= '<div id="forms_container">';
						?>
						<script>
							jQuery(document).ready(function ($) {
								// reload page after form submission
								$(document).on('mailsent.wpcf7', function(){
									console.log('fired');
									setTimeout(function(){ location.reload(); }, 1000);
								})
							});
						</script>
						<?php
						$htmlform .= '<div class="wrapper">';
						$could_submit = true;
						foreach ($forms as $key => $form){
							$htmlform .= "<hr>";
							$htmlform .= get_the_post_thumbnail( $form['product_id'], 'shop_single');
							$htmlform .= "<h3>" . $form['product_title'] . "</h3>";
							if (!$form['submitted'] ){
								$could_submit = false;
							}else if (count($form['submitted']) !== count($form['forms']) ){
								$could_submit = false;
							}
							foreach ( $form['forms'] as $product_form){
								$form_data = get_page_by_path($product_form, OBJECT, 'wpcf7_contact_form');
								$htmlform .= "<h5>".$form_data->post_title."</h5>";
								$cf7_shortcode = '[contact-form-7 title="'.$form_data->post_title.'"]';
								$this->form_id = $key;
								
								$formdata = do_shortcode($cf7_shortcode);
								if (is_array($form['submitted'][$product_form]) && !empty($form['submitted'][$product_form])){
									foreach($form['submitted'][$product_form] as $field => $value){
									  $pattern = '/(<input.*name="'.$field.'".*value=")(".*>?)/i';
									  $replacement = '${1}'.$value.'${2}';
									  $formdata = preg_replace($pattern, $replacement, $formdata);
									  $pattern = '/(<textarea.*name="'.$field.'".*>?)(<\/textarea>?)/i';
									  $replacement = '${1}'.$value.'${2}';
									  $formdata = preg_replace($pattern, $replacement, $formdata);
									}
								}
								$noncesave = wp_create_nonce('save');
								$pattern = '/(<form.*>?)/i';
								$replacement = '${1}<input type="hidden" name="_af2p" value="'.$noncesave.'">';
								$formdata = preg_replace($pattern, $replacement, $formdata);
								$htmlform .= $formdata;
								$this->form_id = 0;
							}
						}
						$htmlform .=  "<hr>";
						$htmlform .=  '</div><!-- .wrapper -->';
						$htmlform .=  '</div><!--forms_container-->';
					// END FORM AREA
					}else{
					// START SUBMITTED TEXTS AREA
						$htmlform .=  '<div id="texts_container">';
						$htmlform .=  '<div class="wrapper">';
						$could_submit = true;
						foreach ($forms as $key => $form){
							$htmlform .=  "<hr>";
							$htmlform .= get_the_post_thumbnail( $form['product_id'], 'shop_single');
							$htmlform .=  "<h3>" . $form['product_title'] . "</h3>";
							if (!$form['submitted'] ){
								$could_submit = false;
							}else{
								if (count($form['submitted']) !== count($form['forms']) ){
									$could_submit = false;
								}
								foreach ( $form['submitted'] as $product_form => $data){
									$form_data = get_page_by_path($product_form, OBJECT, 'wpcf7_contact_form');
									$htmlform .= "<h5>".$form_data->post_title."</h5>";
									
									// GET FORM SEVEN LABEL
									$cf7_shortcode = '[contact-form-7 id="'.$form_data->ID.'" title="'.$form_data->post_title.'"]';
									$formdata = do_shortcode($cf7_shortcode);
									$label = array();
									// STOP GET FORM SEVEN LABEL
									
									
									foreach($data as $data_key => $data_value){
										$htmlform .= "<p>";
										$pattern = '/<p>(.+)<br.*\n.*name="'.$data_key.'"/i';
										if (preg_match($pattern, $formdata, $label)){
											$data_key = $label[1];
											$htmlform .= "<strong>$data_key</strong><br>$data_value";
										}
										$htmlform .= "</p>";
									}
								}
							}
						}
						$htmlform .= '</div><!-- .wrapper -->';
						$htmlform .= '</div><!--texts_container-->';
						// END SUBMITTED TEXTS AREA
					}
					$htmlform .= '</div><!--manage_forms_container-->';
					
					
										
					if ($forms_status == 'awaiting-submission'){
						echo '<h3 class="dashicons-editor-alignleft action-icon">' . __('Texts Forms', 'woo_af2p') . "</h3>";
					}else{
						echo '<h3 class="dashicons-editor-alignleft action-icon">' . __('Submitted Texts', 'woo_af2p') . "</h3>";
					}
					
					// START SEND TEXT BUTTON
					if ($forms_status == 'awaiting-submission'){
						if ($could_submit === true){
							$nonce = wp_create_nonce('send_text_submit');
							?>
							<button class="button button-primary sendtext" name="send" id="sendtext-top"><?php _e('Send texts','woo_af2p'); ?></button>
							<script type="text/javascript" >
							jQuery(document).ready(function($) {
								if(typeof ajaxurl === "undefined"){
									var ajaxurl ='<?php echo admin_url('admin-ajax.php'); ?>';
								}
								$('button.sendtext').on('click', function(){
									var data = {
										action: 'send_text',
										_nonce: '<?php echo $nonce; ?>',
										order: '<?php echo $order_id ?>'
										
									};
									// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
									$.post(ajaxurl, data, function(response) {
										if (response.success){
											<?php echo 'window.location.replace("'.$_SERVER['REQUEST_URI'].'&sent=ok");'; ?>
											//location.reload(true);
										}else{
											alert("error");
										}
									});
								});
							});
							</script>
							<?php
						}else{
							echo "<p>" . __("You must complete all forms requested. After that you'll can send texts and awaiting a preview", "woo_af2p") . "</p>";
						}
					}
					// END SEND TEXT BUTTON
					
					echo $htmlform;
					
					// START SEND TEXT BUTTON
					echo '<div class="af2p_action bottom">';
					if ($forms_status == 'awaiting-submission'){
						if ($could_submit === true){
							?>
							<button class="button button-primary sendtext" name="send" id="sendtext-bottom"><?php _e('Send texts','woo_af2p'); ?></button>
							<?php
						}else{
							echo "<p>" . __("You must complete all forms requested. After that you'll can send texts and awaiting a preview", "woo_af2p") . "</p>";
						}
					}else{
						// END SEND TEXT BUTTON
						$sent = $_REQUEST['sent'];
						if (!empty($sent) && $sent== 'ok'){
							echo "<p>" . __("Thank you for submitting your texts. Your order will be processed as soon as possible", "woo_af2p") . "</p>";
						}						
					}
					echo '</div>';
						
					//echo '<pre>';
					//print_r($forms);
					//echo '</pre>';
					
					//echo 'User: ' . $order->customer_user . '<br>';
					//echo 'Key: ' . $order->order_key . '<br>';
					//echo '<pre>';
					//print_r($order);
					//echo '</pre>';
				}
			}

			//AJAX FUNCTIONS
			function send_text() {
				global $wpdb, $woocommerce; // this is how you get access to the database
				$nonce = $_POST['_nonce'];
				$order_id = $_POST['order'];
				$user_id = get_current_user_id();
				
				header('Content-Type: application/json');
				if ( ! wp_verify_nonce( $nonce, 'send_text_submit' ) ) {
				    // This nonce is not valid.
					wp_send_json_error(); // {"success":false}
				    die();
				} 
				if ( !$order_id ) {
				    // Order id is empty.
					wp_send_json_error(); // {"success":false}
				    die();
				} 
				
				$order = new WC_Order();
				$order->get_order($order_id);
				
				if (!$order->id == $order_id or !$order->customer_user == $user_id ){
					wp_send_json_error(); // {"success":false}
				    die();					
				}
				
				//carico il mailer. Questo fa si che vengano chiamate le classi delle email e relativi filtri e azioni
				$mailer = $woocommerce->mailer();
				update_post_meta($order->id, 'forms_status', 'awaiting-preview');
				//la mail viene inviata grazie all'aggancio a questa azione  
				do_action( 'woocommerce_af2p_status_awaiting-submission_to_awaiting-preview', $order_id);
				
				echo json_encode(array('success' => true, 'status' => 'ok', 'order_id' => $order_id, 'user_id' => $user_id, 'oerder' => $order->id, 'customer user' => $order->customer_user));
				die(); // this is required to return a proper result
			}

			function send_report() {
				global $wpdb, $woocommerce, $current_user;
				$nonce = $_POST['_nonce'];
				$order_id = $_POST['order'];
				$user_id = get_current_user_id();
				$note = $_POST['note'];
				
				header('Content-Type: application/json');
				if ( ! wp_verify_nonce( $nonce, 'send_preview_report' ) ) {
				    // This nonce is not valid.
					wp_send_json_error(); // {"success":false}
				    die();
				} 
				if ( !$order_id || !$note) {
				    // Order id is empty.
					wp_send_json_error(); // {"success":false}
				    die();
				} 

				$order = new WC_Order();
				$order->get_order($order_id);
				
				if (!$order->id == $order_id or !$order->customer_user == $user_id ){
					wp_send_json_error(); // {"success":false}
				    die();					
				}
								
				$time = current_time('mysql');

				$data = array(
				    'comment_post_ID' => $order_id,
				    'comment_author' => $current_user->display_name,
				    'comment_author_email' => $current_user->user_email,
				    'comment_author_url' => $current_user->user_url,
				    'comment_content' => $note,
				    'comment_type' => 'order_report',
				    'comment_parent' => 0,
				    'user_id' => $user_id,
				    'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
				    'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
				    'comment_date' => $time,
				    'comment_approved' => 1,
				);
				$comment_id = wp_insert_comment($data);
				
				$args = array(
					'post_id' 	=> $order_id,
					'approve' 	=> 'approve',
					'type' 		=> 'order_report',
					'post_type' => 'shop_order'
				);
				remove_filter('comments_clauses', 'woocommerce_exclude_order_comments');
				$comments = get_comments($args);

				add_filter('comments_clauses', 'woocommerce_exclude_order_comments');
				$comments = (array) $comments;
				error_log(json_encode($comments));
				//$note = $comments[count($comments)-1];
				$note = $comments[0];
				ob_start();
				?>
				<li rel="<?php echo absint( $note->comment_ID ) ; ?>" class="note">
					<div class="note_content">
						<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
					</div>
					<p class="meta">
						<?php printf( __( 'added %s ago', 'ww_af2p' ), human_time_diff( strtotime( $note->comment_date_gmt ), current_time( 'timestamp', 1 ) ) ); ?>
					</p>
				</li>
				<?php
				$comment = ob_get_contents();
				ob_end_clean();
				
				//carico il mailer. Questo fa si che vengano chiamate le classi delle email e relativi filtri e azioni
				$mailer = $woocommerce->mailer();
				//la mail viene inviata grazie all'aggancio a questa azione  
				do_action( 'woocommerce_af2p_send_report', $order_id);
				
				
				echo json_encode(array('success' => true, 'status' => 'ok', 'order_id' => $order_id, 'user_id' => $user_id, 'oerder' => $order->id, 'customer user' => $order->customer_user, 'user_info' => $current_user, 'comments' => $comments, 'comment' => $comment));
				die(); // this is required to return a proper result
			}
			
			function send_approval() {
				global $wpdb, $woocommerce, $current_user;
				$nonce = $_POST['_nonce'];
				$order_id = $_POST['order'];
				$user_id = get_current_user_id();
				
				header('Content-Type: application/json');
				if ( ! wp_verify_nonce( $nonce, 'send_preview_approval' ) ) {
				    // This nonce is not valid.
					wp_send_json_error(); // {"success":false}
				    die();
				} 
				if ( !$order_id) {
				    // Order id is empty.
					wp_send_json_error(); // {"success":false}
				    die();
				} 

				$order = new WC_Order();
				$order->get_order($order_id);
				
				if (!$order->id == $order_id or !$order->customer_user == $user_id ){
					wp_send_json_error(); // {"success":false}
				    die();					
				}
				
				//carico il mailer. Questo fa si che vengano chiamate le classi delle email e relativi filtri e azioni
				$mailer = $woocommerce->mailer();
				update_post_meta($order->id, 'forms_status', 'preview-approved');
				//la mail viene inviata grazie all'aggancio a questa azione  
				do_action( 'woocommerce_af2p_status_awaiting-approval_to_preview-approved', $order_id);
				
				echo json_encode(array('success' => true, 'status' => 'ok', 'order_id' => $order_id, 'user_id' => $user_id, 'oerder' => $order->id, 'customer user' => $order->customer_user, 'user_info' => $current_user));
				die(); // this is required to return a proper result
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
				//wp_enqueue_media();
				// Respects SSL, style-admin.css is relative to the current file
				wp_register_style( 'wooaf2p-styles', plugins_url('css/style-admin.css', __FILE__) );
				wp_register_script( 'wooaf2p-scripts', plugins_url('js/script-admin.js', __FILE__), array('jquery') );
				wp_enqueue_style( 'wooaf2p-styles' );
				wp_enqueue_script( 'wooaf2p-scripts' );
			}

			function wooaf2p_add_wp_scripts() {
				//wp_enqueue_media();
				// Respects SSL, style-admin.css is relative to the current file
				wp_register_style( 'wooaf2p-styles', plugins_url('css/style.css', __FILE__) );
				//wp_register_script( 'wooaf2p-scripts', plugins_url('js/script-admin.js', __FILE__), array('jquery') );
				wp_enqueue_style( 'wooaf2p-styles' );
				//wp_enqueue_script( 'wooaf2p-scripts' );
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
			    require( 'includes/class-wc-email-text-submitted.php' );
				require( 'includes/class-wc-email-customer-preview-submitted.php' );
				require( 'includes/class-wc-email-preview-approved.php' );
				require( 'includes/class-wc-email-send-report.php' );
				require( 'includes/class-wc-email-customer-preview-approved.php' );
			    // add the email class to the list of email classes that WooCommerce loads
			    $email_classes['WC_Email_Customer_Request_Texts'] = new WC_Email_Customer_Request_Texts();
			    $email_classes['WC_Email_Text_Submitted'] = new WC_Email_Text_Submitted();
				$email_classes['WC_Email_Customer_Preview_Submitted'] = new WC_Email_Customer_Preview_Submitted();
			    $email_classes['WC_Email_Preview_Approved'] = new WC_Email_Preview_Approved();
				$email_classes['WC_Email_Send_Report'] = new WC_Email_Send_Report();
				$email_classes['WC_Email_Customer_Preview_Approved'] = new WC_Email_Customer_Preview_Approved();
			    return $email_classes;
			}

			function request_text_email_available($emails) {
				array_push($emails, 'wc_request_texts');
				array_push($emails, 'wc_preview_submitted');
				return $emails;
			}
			
			function after_checkout_validation ($posted){
				global $woocommerce;	
				error_log("==UPDATE== after_checkout_validation");
				foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
					error_log("==UPDATE== $cart_item_key => ".serialize($values));
				}				
				//error_log("==UPDATE== ".serialize($woocommerce));
				return $posted;
			}

			function update_order_meta ($order_id, $posted){
				global $woocommerce;				
				error_log("==UPDATE ORDER META== ");
				error_log("==UPDATE ORDER META== Order ID ".$order_id);
				$enabled = false;
				$items_forms = array();
				foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
					$product_id = $values['product_id'];
					if (get_post_meta($product_id, 'af2p_enamble', true)){
						if (get_post_meta($product_id, 'af2p_enamble')){
							$af2p_forms = get_post_meta($product_id, 'af2p_forms', true);
							error_log("==UPDATE== FORMS ".serialize($af2p_forms));
							if (!empty($af2p_forms) && is_array($af2p_forms)){
								if ($values['sample']){
									// is a sample we don't ask texts (TODO we will could enable it by check)
								}else{
									$enabled = true;
									$items_forms[$cart_item_key] = array('product_id' => $product_id,
																		 'product_title' => $values['data']->post->post_title,
																		 'forms' => $af2p_forms);
									error_log("==UPDATE== DATA ".serialize($values['data']));
									error_log("==UPDATE== POST ".serialize($values['data']->post));								
									error_log("==UPDATE== ITEMS ".serialize($items_forms));									
								}
							}
						}
					}
				}
				if ($enabled === true){
					add_post_meta($order_id, 'forms_required', true);
					add_post_meta($order_id, 'forms_status', 'awaiting-submission');
					//awaiting-submission
					//awaiting-preview
					//awaiting-approval
					//preview-approved
					//awaiting-corrections
					add_post_meta($order_id, 'forms', $items_forms);
				}
				return $posted;
			}

			function wooaf2p_meta_boxes(){
				add_meta_box( 'woocommerce-added-forms', __( 'Requested Text', 'woo_af2p' ), array($this, 'wooaf2p_meta_boxes_callback'), 'shop_order', 'normal', 'high');
			}
			
			function wooaf2p_meta_boxes_callback($post){
				global $wpdb, $thepostid, $theorder, $woocommerce;

				if ( !is_object( $theorder ) )
					$theorder = new WC_Order( $thepostid );
					
				$order = $theorder;
			
				$data = get_post_meta( $post->ID );
				?>
				<div class="woocommerce_order_texts_forms_for_items_wrapper">
				<?php
					wp_nonce_field('af2p_upload','_af2p_nonce');
					$forms_required = get_post_meta($post->ID, 'forms_required', true) ? get_post_meta($post->ID, 'forms_required', true) : false;
					  if ($forms_required !== '1') { ?>
					<p><?php _e( 'No text required for this order', 'woo_af2p' ); ?></p>
				<?php }else{ 
						$forms_status = get_post_meta($post->ID, 'forms_status', true);
						$forms_status_description = '';
						switch ($forms_status) {
							case 'awaiting-submission':
								$forms_status_description = __('Awaiting submission', 'woo_af2p');
								break;
							case 'awaiting-preview':
								$forms_status_description = __('Awaiting preview', 'woo_af2p');
								break;
							case 'awaiting-approval':
								$forms_status_description = __('Awaiting approval', 'woo_af2p');
								break;
							case 'preview-approved':
								$forms_status_description = __('Preview approved', 'woo_af2p');
								break;
						}					
					?>
					<p><?php _e( 'Text status', 'woo_af2p' ); ?>: <strong class="forms-status <?php echo $forms_status; ?>"><?php echo $forms_status_description ?></strong></p>

					<?php
					if ($forms_status != 'awaiting-submission'){
						$forms = get_post_meta($post->ID, 'forms', true);
						?>
						
						<div class="dashicons-list-view action-icon" id="expand-texts"><?php _e('Show texts', 'woo_af2p');?></div>
						<div class="dashicons-dismiss action-icon" id="collapse-texts"><?php _e('Collapse texts', 'woo_af2p');?></div>
						<div id="texts-container">
						<?php
						foreach ($forms as $key => $form){
							echo "<hr>";
							echo "<h3>" . $form['product_title'] . "</h3>";
																
							foreach ( $form['submitted'] as $product_form => $product_data){
								$form_data = get_page_by_path($product_form, OBJECT, 'wpcf7_contact_form');
								echo "<h5>".$form_data->post_title."</h5>";

								// GET FORM SEVEN LABEL
								//$cf7_shortcode = '[contact-form-7 id="'.$form_data->ID.'" title="'.$form_data->post_title.'"]';
								//$formdata = do_shortcode($cf7_shortcode);
								$formdata = wpcf7_contact_form_tag_func(array('id' => $form_data->ID, 'title' => $form_data->post_title), null, 'contact-form-7');
								$label = array();
								// STOP GET FORM SEVEN LABEL

								foreach($product_data as $data_key => $data_value){
									echo "<p>";
									$pattern = '/<p>(.+)<br.*\n.*name="'.$data_key.'"/i';
									if (preg_match($pattern, $formdata, $label)){
										$data_key = $label[1];
										echo "<strong>$data_key</strong><br>$data_value";
									}
									echo "</p>";
								}
							}
						}
						echo "</div>";						
					}
					if ($forms_status != 'awaiting-preview' && $forms_status != 'awaiting-submission'){
					?>
					<hr>
					<?php
					$args = array(
						'post_id' 	=> $post->ID,
						'approve' 	=> 'approve',
						'type' 		=> 'order_report'
					);
					$notes = get_comments( $args );
					?>
					<div class="dashicons-format-chat action-icon" id="expand-reports"><?php _e('Show errors', 'woo_af2p'); ?></div>
					<div class="dashicons-dismiss action-icon" id="collapse-reports"><?php _e('Collapse errors', 'woo_af2p');?></div>
					<ul class="order_notes" id="reports-container">
					<?php
					if ( $notes ) {
						foreach( $notes as $note ) {
							//$note_classes = get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ? array( 'customer-note', 'note' ) : array( 'note' );
							$note_classes = array( 'note' );
							?>
							<li rel="<?php echo absint( $note->comment_ID ) ; ?>" class="<?php echo implode( ' ', $note_classes ); ?>">
								<div class="note_content">
									<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
								</div>
								<p class="meta">
									<?php printf( __( 'added %s ago', 'ww_af2p' ), human_time_diff( strtotime( $note->comment_date_gmt ), current_time( 'timestamp', 1 ) ) ); ?>
								</p>
							</li>
							<?php
						}
					} else {
						echo '<li>' . __( 'There are no error reported for this order yet.', 'woo_af2p' ) . '</li>';
					}
				
					echo '</ul>';
					}
					if ($forms_status == 'awaiting-preview' || $forms_status == 'awaiting-approval'){
						$btn_value = __( 'Send Preview', 'woo_af2p' );
						if ($forms_status == 'awaiting-approval')
							$btn_value = __( 'Send Again', 'woo_af2p' );	
							?>
							<hr>
							<div class="upload_preview">
							    <?php if ( isset ( $data['af2p-preview-url'] ) ){ ?>
							    <div class="dashicons-upload action-icon" id="title_upload_preview"><?php _e( 'Preview uploaded', 'woo_af2p' )?></div>
							    <a href="<?php echo $data['af2p-preview-url'][0]; ?>" target="_blank"><img class="document-preview" src="<?php echo get_site_url(); ?>/wp-includes/images/crystal/document.png"></a>	
							    <?php }?>
							    <p>
							    <input type="text" name="meta-preview" id="meta-preview" readonly="readonly" value="<?php if ( isset ( $data['af2p-preview-url'] ) ) echo $data['af2p-preview-url'][0]; ?>" />
							    <input type="button" id="meta-preview-button" class="button" rel="<?php echo $post->ID ?>" value="<?php _e( 'Upload the Preview document', 'woo_af2p' )?>" />
								</p>
							    <?php if ( isset ( $data['af2p-preview-url'] ) ){ ?>
							    <p>
							    <input type="submit" class="button send_preview button-primary" style="float:right" name="send_preview" value="<?php echo $btn_value ?>">
							    </p>
							    <?php }?>					    
							</div>
					<?php } else if ($forms_status == 'preview-approved'){
						?>
						<hr>
						<div class="dashicons-upload action-icon" id="title_upload_preview"><?php _e( 'Preview uploaded and approved', 'woo_af2p' )?></div>
						<a href="<?php echo $data['af2p-preview-url'][0]; ?>" target="_blank"><img class="document-preview" src="<?php echo get_site_url(); ?>/wp-includes/images/crystal/document.png"></a>
						<?php	
					}else{
						// unfind status
					}?>
					<div class="clear"></div>

				<?php } ?>
				</div>
				<?php
			}

			function wooaf2p_meta_boxes_save( $post_id ) {
				global $woocommerce;
				
			    // Checks save status
			    $is_autosave = wp_is_post_autosave( $post_id );
			    $is_revision = wp_is_post_revision( $post_id );
			    $is_valid_nonce = ( isset( $_POST[ '_af2p_nonce' ] ) && wp_verify_nonce( $_POST[ '_af2p_nonce' ], 'af2p_upload') ) ? 'true' : 'false';
			 
			    // Exits script depending on save status
			    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
			        return;
			    }
			 
			    // Checks for input and sanitizes/saves if needed
			    //if( isset( $_POST[ 'meta-text' ] ) ) {
			    //    update_post_meta( $post_id, 'meta-text', sanitize_text_field( $_POST[ 'meta-text' ] ) );
			    //}
			    // Checks for input and saves if needed
				if( isset( $_POST[ 'meta-preview' ] ) ) {
				    update_post_meta( $post_id, 'af2p-preview-url', $_POST[ 'meta-preview' ] );
					//return;
				}
				if( isset( $_POST['send_preview'] ) ) {
					//carico il mailer. Questo fa si che vengano chiamate le classi delle email e relativi filtri e azioni
					$mailer = $woocommerce->mailer();
				    update_post_meta( $post_id, 'forms_status', 'awaiting-approval' );
				    do_action( 'woocommerce_af2p_status_awaiting-preview_to_awaiting-approval', $post_id);
					//return;
				}

			}

			function wpcf7_save_form_data(&$wpcf7_data)
			{
				// only _af2p form are saved, other form work as mail send form as ordinary
				$af2p_form = $wpcf7_data->posted_data['_af2p'];
				if ( !empty($af2p_form) && wp_verify_nonce( $af2p_form, 'save' ) ){
					// i need an order id
					$order_id = intval($_REQUEST['order']) > 0 ? intval($_REQUEST['order']) : '';
					if (!empty($order_id)){
						$user_id = get_current_user_id();
						$order = new WC_Order();
						$order->get_order($order_id);
						// i need a logged user and valid order id, user logged must be the owner of the order
						if (!empty($user_id) && $order->id == $order_id && $order->customer_user == $user_id ){
							//error_log('------------------------ CI PASSO --------------------------');
						    // Everything you should need is in this variable
						    //$log = var_export($wpcf7_data, true);
						    //error_log($log);
							//error_log('------------------------------------------------------------');
							//error_log(serialize($wpcf7_data->posted_data));
							//error_log($wpcf7_data->name);
							// saving data
							$product_key = $wpcf7_data->posted_data['_product_key'];
							$forms = get_post_meta($order_id, 'forms', true);
							if (!$forms[$product_key]['submitted']){
								$forms[$product_key]['submitted'] = array();
							}
							foreach ( $wpcf7_data->posted_data as $key => $value){
								if ($key[0] !== '_'){
									$submitted[$key] = $value;
								}
							}
							$forms[$product_key]['submitted'][$wpcf7_data->name] = $submitted;
							update_post_meta($order_id, 'forms', $forms);
							//error_log('------------------------ PASSATO ---------------------------');
							
						    // skip sending the mail
						    $wpcf7_data->skip_mail = true;
						}
					}
				}
			}

			public function send_transactional_email( $args = array() ) {
				global $woocommerce;
				$woocommerce->mailer();  
				do_action( current_filter() . '_notification', $args );
			}

		}// end class
		$woocommerce_af2p = new WooCommerce_AddFormToProduct();
		
		register_activation_hook( __FILE__, array( 'WooCommerce_AddFormToProduct', 'activation' ) );
		register_deactivation_hook( __FILE__, array( 'WooCommerce_AddFormToProduct', 'deactivation' ) );
		register_uninstall_hook( __FILE__, array( 'WooCommerce_AddFormToProduct', 'uninstall' ) );
	}
}
?>