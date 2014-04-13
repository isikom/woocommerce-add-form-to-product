<?php
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
/**
 * Customer Request Texts Email
 *
 * @since 0.1
 * @extends WC_Email
 */
class WC_Email_Customer_Preview_Approved extends WC_Email {
 
	 /**
	 * Set email defaults
	 *
	 * @since 0.1
	 */
	public function __construct() {
	 
	    // set ID, this simply needs to be a unique name
	    $this->id = 'wc_customer_preview_approved';
	 
	    // this is the title in WooCommerce Email settings
	    $this->title = __( 'Customer Preview Approved', 'woo_af2p' );
	 
	    // this is the description in WooCommerce email settings
	    $this->description = __( 'Customer Preview Approved emails are sent when a preview document was approved', 'woo_af2p' );
	 
	    // these are the default heading and subject lines that can be overridden using the settings
	    $this->heading = __( 'Preview Approved', 'woo_af2p' );;
	    $this->subject = __( 'Preview Approved', 'woo_af2p' );;
	 
	    // these define the locations of the templates that this email should use, we'll just use the new order template since this email is similar
	    $this->template_base = untrailingslashit(  dirname(plugin_dir_path( __FILE__ ) ) ) . '/templates/';
		$this->template_html 	= 'emails/customer-preview-approved.php';
		$this->template_plain 	= 'emails/plain/customer-preview-approved.php';
	 
	    // Trigger on new paid orders
	    add_action( 'woocommerce_af2p_status_awaiting-approval_to_preview-approved', array( $this, 'trigger' ) );

	    // Call parent constructor to load any other defaults not explicity defined here
	    parent::__construct();
			 
	}

	/**
	 * Determine if the email should actually be sent and setup email merge variables
	 *
	 * @since 0.1
	 * @param int $order_id
	 */
	public function trigger( $order_id ) {
		
		error_log('Trigger inside');
	 
	    // bail if no order ID is present
	    if ( ! $order_id )
	        return;
	 
	    // setup order object
	    $this->object = new WC_Order( $order_id );
		$this->recipient	= $this->object->billing_email;

	    // bail if shipping method is not expedited
	    //if ( ! in_array( $this->object->get_shipping_method(), array( 'Three Day Shipping', 'Next Day Shipping' ) ) )
	    //    return;
	 
	    // replace variables in the subject/headings
	    $this->find[] = '{order_date}';
	    $this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );
	 
	    $this->find[] = '{order_number}';
	    $this->replace[] = $this->object->get_order_number();
	 
	    if ( ! $this->is_enabled() || ! $this->get_recipient() )
	        return;
	 
	    // woohoo, send the email!
	    $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}	
	

	/**
	 * get_content_html function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_html() {
	    ob_start();
	    woocommerce_get_template( $this->template_html, array(
	        'order'         => $this->object,
	        'email_heading' => $this->get_heading()
	    ) );
	    return ob_get_clean();
	}
	 
	 
	/**
	 * get_content_plain function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_plain() {
	    ob_start();
	    woocommerce_get_template( $this->template_plain, array(
	        'order'         => $this->object,
	        'email_heading' => $this->get_heading()
	    ) );
	    return ob_get_clean();
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 0.1
	 */
	public function init_form_fields() {
	 
	    $this->form_fields = array(
	        'enabled'    => array(
	            'title'   => __( 'Enable/Disable', 'woocommerce' ),
	            'type'    => 'checkbox',
	            'label'   => __( 'Enable this email notification', 'woocommerce' ),
	            'default' => 'yes'
	        ),
	        'subject'    => array(
	            'title'       => __( 'Subject', 'woocommerce' ),
	            'type'        => 'text',
	            'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce' ), $this->subject ),
	            'placeholder' => '',
	            'default'     => ''
	        ),
	        'heading'    => array(
	            'title'       => __( 'Email Heading', 'woocommerce' ),
	            'type'        => 'text',
	            'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce' ), $this->heading ),
	            'placeholder' => '',
	            'default'     => ''
	        ),
	        'email_type' => array(
	            'title'       => __( 'Email type', 'woocommerce' ),
	            'type'        => 'select',
	            'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
	            'default'     => 'html',
	            'class'       => 'email_type',
	            'options'     => array(
	                'plain'     => 'Plain text',
	                'html'      => 'HTML', 'woocommerce',
	                'multipart' => 'Multipart', 'woocommerce',
	            )
	        )
	    );
	}

} // end WC_Email_Customer_Request_Texts class