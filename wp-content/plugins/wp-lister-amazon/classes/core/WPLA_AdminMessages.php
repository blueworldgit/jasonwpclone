<?php

class WPLA_AdminMessages {

    private $messages = array();
	private $flash_messages = array();

    function __construct() {
        add_action( 'admin_notices', array( &$this, 'show_admin_notices' ), 10 );
        add_action( 'wpla_admin_notices', array( &$this, 'show_admin_notices' ), 10 );
        // add_action( 'admin_footer', array( &$this, 'show_admin_notices' ), 10 );
	    $this->flash_messages = array();
	    $this->messages       = get_transient( 'wpla_admin_messages' );

	    if ( !$this->messages ) {
		    $this->messages = array();
	    }
    }

    function add_message( $message, $type = 'info', $params = [] ) {

        // convert old error codes
        if ( $type === 0 ) $type = 'info';
        if ( $type === 1 ) $type = 'error';
        if ( $type === 2 ) $type = 'warn';

        $msg = new stdClass();
        $msg->type    = $type;
        $msg->message = $message;
        $msg->params  = $params;

	    if ( empty($msg->params['persistent']) ) {
		    $this->flash_messages[] = $msg;
	    } else {
		    $this->messages[] = $msg;
		    set_transient( 'wpla_admin_messages', $this->messages );
	    }

    } // show_admin_notices()


    function show_admin_notices() {
		// Don't show ouput when request is done via AJAX or REST
	    if ( wpla_request_is_ajax() || wpla_request_is_rest() ) {
		    return;
	    }

	    // dont output any messages when on SagePay endpoints #13032
	    if ( isset( $_POST['cwcontroller'] ) ) {
		    return;
	    }

	    // Start with flash messages
	    foreach ( $this->flash_messages as $msg ) {
		    $this->show_single_message( $msg->message, $msg->type, $msg->params );
	    }

	    // Display persistent messages
	    foreach ( $this->messages as $msg ) {
		    $this->show_single_message( $msg->message, $msg->type, $msg->params );
	    }


	    // clear messages after display
	    $this->messages = array();
	    $this->flash_messages = array();
	    set_transient( 'wpla_admin_messages', $this->messages );

    } // show_admin_notices()


    // display a single admin notice - the WordPress way
    function show_single_message( $message, $msg_type = 'info', $params = [] ) {
	    $params = wp_parse_args( $params, array(
		    'dismissible'   => false,
		    'persistent'    => false,
	    ));

	    switch ( $msg_type ) {
		    case 'error':
			    $class = 'notice error';
			    break;

		    case 'warn':
			    $class = 'notice notice-warning';
			    break;

		    case 'info':
			    $class = 'notice notice-success';
			    break;

		    default:
			    $class = 'notice';
			    break;
	    }

        $message_hash = 'wpla_notice_'. md5( $message );
        $class .= $params['dismissible'] ? ' is-dismissible' : '';

        // check if this has been dismissed before
        if ( get_option( 'wpla_dismissed_'. $message_hash, 0 ) ) {
            // yes, exit without showing anything
            return;
        }

        $message = apply_filters( 'wplister_admin_message_text', $message );
        echo '<div id="message" class="wpla-notice '.$class.'" data-msg_id="'. $message_hash .'" style="display:block !important; position: relative;"><p>'.$message.'</p></div>';

    } // show_single_message()




    // create JSON compatible array to display in progress window
    function get_admin_notices_for_json_result() {
        $errors = array();

        foreach ( $this->messages as $msg ) {
            $errors[] = $this->get_single_message_as_json_error( $msg->message, $msg->type );
        }

        return $errors;
    } // get_admin_notices_for_json_result()

    // get a single admin notice - for progress window
    function get_single_message_as_json_error( $message, $msg_type = 'info' ) {

        switch ( $msg_type ) {
            case 'error':
                $class = 'error';
                $SeverityCode = 'Error';
                break;
            
            case 'warn':
                $class = 'updated update-nag notice notice-warning';
                $SeverityCode = 'Warning';
                break;
            
            default:
                $class = 'updated';
                $SeverityCode = 'Note';
                break;
        }

        $message = apply_filters( 'wplister_admin_message_text', $message );
        $html_message = '<div id="message" class="'.$class.'" style="display:block !important"><p>'.$message.'</p></div>';

        // build error object
        $error = new stdClass();
        $error->SeverityCode = $SeverityCode;
        $error->ErrorCode    = 23;
        $error->ShortMessage = 'Your attention is required.';
        $error->LongMessage  = $message;
        $error->HtmlMessage  = $html_message;

        return $error;
    } // get_single_message_as_json_error()





} // class WPLA_AdminMessages
