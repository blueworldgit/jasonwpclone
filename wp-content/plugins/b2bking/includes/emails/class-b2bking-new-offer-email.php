<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('B2bking_New_Offer_Email')){
    class B2bking_New_Offer_Email extends WC_Email {

    public $offerid;
    public $offerlink;
    public $message;
    public $pdf_attachment_path;

    public function __construct() {

        $this->offerid = '';
        $this->offerlink = '';
        $this->message = '';
        $this->pdf_attachment_path = '';

        // set ID, this simply needs to be a unique name
        $this->id = 'b2bking_new_offer_email';

        // this is the title in WooCommerce Email settings
        $this->title = esc_html__('You received a new offer!', 'b2bking');

        // this is the description in WooCommerce email settings
        $this->description = esc_html__('This email is sent when an offer is emailed to customers', 'b2bking');
        $this->description .= '. <a href="https://woocommerce-b2b-plugin.com/docs/how-to-customize-b2bking-emails/" target="_blank">'.esc_html__('Learn how to customize B2BKing emails.', 'b2bking').'</a>';


        // these are the default heading and subject lines that can be overridden using the settings
        $this->heading = esc_html__('You received a new offer!', 'b2bking');
        $this->subject = esc_html__('You received a new offer!', 'b2bking');

        $this->template_base  = B2BKING_DIR . 'includes/emails/templates/';
        $this->template_html  = 'new-offer-email-template.php';
        $this->template_plain =  'plain-new-offer-email-template.php';
        
        // Call parent constructor to load any other defaults not explicity defined here
        parent::__construct();

        add_action( 'b2bking_new_offer_notification', array( $this, 'trigger'), 10, 4 );
        add_action( 'b2bking_new_offer', array( $this, 'trigger'), 10, 5 );

    }

    public function trigger($email_address, $message_or_user_type, $offerid, $offerlink, $pdf_path = null) {
        // Handle both 4-parameter calls (from b2bking_new_offer_notification) and 5-parameter calls (from b2bking_new_offer)
        // When called from b2bking_new_offer, second param is user_type ('1' or '0'), not message
        // When called from b2bking_new_offer_notification, second param is message
        
        $num_args = func_num_args();
        
        // If 5th parameter (PDF path) is provided, use it
        if ($num_args >= 5 && $pdf_path !== null && !empty($pdf_path)) {
            $this->pdf_attachment_path = $pdf_path;
            $message = '';
        } else {
            // Check transient for PDF path
            $transient_pdf = get_transient('b2bking_offer_pdf_' . $offerid);
            if ($transient_pdf && !empty($transient_pdf) && file_exists($transient_pdf)) {
                $this->pdf_attachment_path = $transient_pdf;
            }
            // When called with 4 params, second param is the message
            $message = $message_or_user_type;
        }

        $this->recipient = $email_address;
        $this->message = $message;
        $this->offerid = $offerid;
        $this->offerlink = $offerlink;

        if ( ! $this->is_enabled() || ! $this->get_recipient() ){
           return;
        }
        
        b2bking()->switch_to_user_locale($email_address);
        do_action('wpml_switch_language_for_email', $email_address);
        
        $this->heading = esc_html__('You received a new offer!', 'b2bking');
        $this->subject = esc_html__('You received a new offer!', 'b2bking');
        
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

        // Clean up temp PDF file after sending
        if (!empty($this->pdf_attachment_path) && file_exists($this->pdf_attachment_path)) {
            @unlink($this->pdf_attachment_path);
        }

        do_action('wpml_restore_language_from_email');
        b2bking()->restore_locale();
     
    }

    public function get_attachments() {
        $attachments = array();
        
        // Attach PDF if path is set and file exists
        if (!empty($this->pdf_attachment_path) && file_exists($this->pdf_attachment_path) && is_readable($this->pdf_attachment_path)) {
            $attachments[] = $this->pdf_attachment_path;
        }
        
        return $attachments;
    }

    public function get_content_html() {
        ob_start();
        if (method_exists($this, 'get_additional_content')){
            $additional_content_checked = $this->get_additional_content();
        } else {
            $additional_content_checked = false;
        }
        wc_get_template( $this->template_html, array(
            'email_heading'      => $this->get_heading(),
            'additional_content' => $additional_content_checked,
            'message'            => $this->message,
            'offerid'            => $this->offerid,
            'offerlink'          => $this->offerlink,
            'email'              => $this,
        ), $this->template_base, $this->template_base  );
        return ob_get_clean();
    }


    public function get_content_plain() {
        ob_start();
        if (method_exists($this, 'get_additional_content')){
            $additional_content_checked = $this->get_additional_content();
        } else {
            $additional_content_checked = false;
        }
        wc_get_template( $this->template_plain, array(
            'email_heading'      => $this->get_heading(),
    		'additional_content' => $additional_content_checked,
            'message'            => $this->message,
            'offerid'     => $this->offerid,
            'offerlink'     => $this->offerlink,
    		'email'              => $this,
        ), $this->template_base, $this->template_base );
        return ob_get_clean();
    }

    public function init_form_fields() {

        $this->form_fields = array(
            'enabled'    => array(
                'title'   => esc_html__( 'Enable/Disable', 'b2bking' ),
                'type'    => 'checkbox',
                'label'   => esc_html__( 'Enable this email notification', 'b2bking' ),
                'default' => 'yes',
            ),
            'subject'    => array(
                'title'       => 'Subject',
                'type'        => 'text',
                'description' => esc_html__('This controls the email subject line. Leave blank to use the default subject: ','b2bking').sprintf( '<code>%s</code>.', $this->subject ),
                'placeholder' => '',
                'default'     => ''
            ),
            'heading'    => array(
                'title'       => esc_html__('Email Heading','b2bking'),
                'type'        => 'text',
                'description' => esc_html__('This controls the main heading contained within the email notification. Leave blank to use the default heading: ','b2bking').sprintf( '<code>%s</code>.', $this->heading ),
                'placeholder' => '',
                'default'     => ''
            ),
            'email_type' => array(
                'title'       => esc_html__('Email type','b2bking'),
                'type'        => 'select',
                'description' => esc_html__('Choose which format of email to send.','b2bking'),
                'default'     => 'html',
                'class'       => 'email_type',
                'options'     => array(
                    'plain'     => 'Plain text',
                    'html'      => 'HTML', 'woocommerce',
                    'multipart' => 'Multipart', 'woocommerce',
                )
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'woocommerce' ),
                'description' => __( 'Text to appear below the main email content.', 'woocommerce' ),
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'woocommerce' ),
                'type'        => 'textarea',
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }

    }
}
return new B2bking_New_Offer_Email();