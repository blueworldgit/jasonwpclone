<?php

$id  = $wpl_row->id;
$req = $wpl_row->request;
$url = $wpl_row->request_url;
$param = maybe_unserialize( $wpl_row->parameters );

$response = maybe_unserialize( $wpl_row->response );
if ( ! is_array( $response ) ) {
    $response = htmlspecialchars( $response );  
    
    // echo "<pre>";print_r(substr( $response, 0, 14 ));echo"</pre>";die();
    // ListMarketplaceParticipations response needs extra decoding
    if ( substr( $response, 0, 14 ) == '&quot;&lt;?xml'  ) {
        $response = str_replace( '\n', '<br>', $response );
        $response = stripcslashes( $response );
    }

} 


$result = json_decode( $wpl_row->result ) ? json_decode( $wpl_row->result ) : $wpl_row->result;
if ( ! is_object( $result ) && ! is_array( $result ) ) $result = htmlspecialchars( $result );

// remove <![CDATA[ * ]]> tags for readibily
$req = str_replace('<![CDATA[', '', $req);
$req = str_replace(']]>', '', $req);

$req = htmlspecialchars( $req );

?><html>
<head>
    <title>request details</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style type="text/css">
        pre {
        	background-color: #eee;
        	border: 1px solid #ccc;
        	padding: 20px;
        }
        #support_request_wrap {
        	margin-top: 15px;
        	padding: 20px;
        	padding-top: 0;
        	background-color:#eee;
        	border: 1px solid #ccc;
        	display: none;
        }
        #support_request_wrap label {
			float: left;
        	width: 25%;
        	line-height: 23px;
        }
        #support_request_wrap .text-input,
        #support_request_wrap textarea {
        	width: 70%;
        }
    </style>
</head>

<body>

	<?php if ( ( ! isset($_REQUEST['send_to_support']) ) && ( ! isset($_REQUEST['new_tab']) ) ) : ?>
		<div id="support_request_wrap" style="">
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" target="_blank" >
                <?php wp_nonce_field( 'wpla_send_to_support' ); ?>
				<input type="hidden" name="log_id" value="<?php echo $wpl_row->id ?>" />
				<input type="hidden" name="send_to_support" value="yes" />

				<h2><?php echo __( 'Send to support', 'wp-lister-for-amazon' ) ?></h2>
				Please try to provide as many details as possible about what we might need to do to reproduce the issue.
				<br><br>

				<label for="user_name"><?php echo __( 'Your Name', 'wp-lister-for-amazon' ) ?></label>
				<input type="text" name="user_name" value="" class="text-input"/>
				
				<label for="user_email"><?php echo __( 'Your Email', 'wp-lister-for-amazon' ) ?></label>
				<input type="text" name="user_email" value="<?php echo get_bloginfo ( 'admin_email' ) ?>" class="text-input"/>
				
				<label for="user_msg"><?php echo __( 'Your Message', 'wp-lister-for-amazon' ) ?></label>
				<textarea name="user_msg"></textarea>
				<br style="clear:both"/>

				<input type="submit" value="<?php echo __( 'Send to support', 'wp-lister-for-amazon' ) ?>" class="button-primary"/>
			</form>			
		</div>

		<div style="float:right;margin-top:10px;">
			<!-- <a href="<?php echo $_SERVER['REQUEST_URI']; ?>&send_to_support=yes" target="_blank">send to support</a> &middot; -->
			<a href="#" onclick="jQuery('#support_request_wrap').slideToggle();return false;" class="button-secondary"><?php echo __( 'Send to support', 'wp-lister-for-amazon' ) ?></a>&nbsp;
			<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&new_tab=yes" target="_blank" class="button-secondary">Open in new tab</a>
		</div>
	<?php endif; ?>

    <h2>Call: <?php echo $wpl_row->callname ?> (#<?php echo $wpl_row->id ?>)</h2>

    <!-- <h3>Request URL</h3> -->
    <!-- <pre><?php echo $url ?></pre> -->

    <h3>Request</h3>
    <pre><?php echo str_replace( '.wplab.com','', $req ) ?></pre>

    <h3>Parameters</h3>
    <?php 
    // Check if this is a PUT request with payload parameter for pretty JSON formatting
    $is_put_request = false;
    if ( !empty($req) ) {
        $request_parts = explode( ' ', $req, 2 );
        $is_put_request = ( count($request_parts) >= 2 && strtoupper(trim($request_parts[0])) === 'PUT' );
    }
    
    // Check if payload contains valid JSON before showing special formatting
    $has_json_payload = false;
    if ( $is_put_request && is_array($param) && isset($param['payload']) ) {
        $json_payload = $param['payload'];
        if ( is_string($json_payload) ) {
            $decoded = json_decode($json_payload, true);
            $has_json_payload = ( json_last_error() === JSON_ERROR_NONE );
        } elseif ( is_array($json_payload) || is_object($json_payload) ) {
            $has_json_payload = true;
        }
    }
    
    if ( $has_json_payload ) {
        // Display other parameters first
        $other_params = $param;
        unset($other_params['payload']);
        if ( !empty($other_params) ) {
            echo '<div style="margin-bottom: 15px;"><strong>Other Parameters:</strong>';
            echo '<pre style="margin-top: 5px;">';
            print_r( $other_params );
            echo '</pre></div>';
        }
        
        // Display JSON payload with syntax highlighting
        echo '<div><strong>JSON Payload:</strong>';
        echo '<pre>';
        if ( is_string($json_payload) ) {
            // Already validated as valid JSON above
            $decoded = json_decode($json_payload, true);
            echo htmlspecialchars( json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) );
        } else {
            // If already an array/object, encode it prettily
            echo htmlspecialchars( json_encode($json_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) );
        }
        echo '</pre></div>';
    } else {
        // Standard parameter display for non-PUT, non-payload, or non-JSON requests
        echo '<pre>';
        print_r( $param );
        echo '</pre>';
    }
    ?>

    <h3>Response</h3>
    <pre><?php print_r( $response ) ?></pre>

    <h3>Result</h3>
    <pre><?php print_r( $result ) ?></pre>

    <h3>Debug Info</h3>
    <pre>
    	WP-Lister: <?php echo $wpl_version ?> 
        WC       : <?php echo WC_VERSION ?>        
    	DB       : <?php echo get_option('wpla_db_version') ?>
    	
    	PHP      : <?php echo phpversion() ?>
    	
    	WordPress: <?php echo get_bloginfo ( 'version' ); ?>
    	
    	Locale   : <?php echo get_bloginfo ( 'language' ) ?>

    	Charset  : <?php echo get_bloginfo ( 'charset' ) ?>

    	Site URL : <?php echo get_bloginfo ( 'wpurl' ) ?>
    	
    	Admin    : <?php echo get_bloginfo ( 'admin_email' ) ?>

    	Email    : <?php echo get_option('wpla_license_email') ?>
    </pre>


</body>
</html>
