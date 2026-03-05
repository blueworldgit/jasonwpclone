<?php

    // enqueue jQuery and button styles
    wp_enqueue_script('jquery');
    wp_enqueue_style('buttons');
    wp_enqueue_style('wp-admin');
    //wp_enqueue_emoji_styles();

    // get feed permalink
    $signature      = md5( $wpl_feed->id . get_option('wpla_instance') );
    $feed_permalink = admin_url( 'admin-ajax.php?action=wpla_feed_details' ) . '&id='.$wpl_feed->id.'&sig='.$signature;

    // clean debug data
    // unset( $wpl_feed->types );

    // page title
    $page_title = $wpl_feed->FeedSubmissionId ? 'Feed '.$wpl_feed->FeedSubmissionId : 'Pendind feed #'.$wpl_feed->id;

?><html>
<head>
    <title><?php echo $page_title ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php wp_print_styles(); ?>
    <?php wp_print_scripts(); ?>
    <style type="text/css">
        pre {
        	background-color: #eee;
        	border: 1px solid #ccc;
        	padding: 20px;
        }

        html {
            /*background-color: #fff;*/
        }

        /* nav tabs */
        .tab-content {
            background-color: #fff; 
            padding: 1em; 
            padding-bottom: 1.5em;
        }
        .nav-tab-active, .nav-tab-active:hover {
            border-bottom: 1px solid #fff;
            background: #fff;
        }
        a.nav-tab:focus {
            outline: 0;
            color: #000;
            box-shadow: none;
            -webkit-box-shadow: none;
        }

        body.wp-core-ui,
        body.wp-core-ui td,
        body.wp-core-ui th,
        .csv-table td,
        .csv-table th {
            font-size: .8em;
            font-family: Helvetica Neue,Helvetica,sans-serif;
        }

        .info-table {
            width: 300px;
            border: 1px solid #B0B0B0;
        }
        .csv-table {
            width: 100%;
            border: 1px solid #B0B0B0;
        }
        .csv-table tbody {
            /* Kind of irrelevant unless your .css is alreadt doing something else */
            margin: 0;
            padding: 0;
            border: 0;
            outline: 0;
            /*font-size: 100%;*/
            vertical-align: baseline;
            background: transparent;
        }
        .csv-table thead {
            text-align: left;
        }
        .csv-table thead th {
            background: -moz-linear-gradient(top, #F0F0F0 0, #DBDBDB 100%);
            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #F0F0F0), color-stop(100%, #DBDBDB));
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#F0F0F0', endColorstr='#DBDBDB', GradientType=0);
            border: 1px solid #B0B0B0;
            color: #444;
            /*font-size: 16px;*/
            font-weight: bold;
            padding: 3px 10px;
        }
        .info-table th {
            text-align: left;
        }
        .csv-table td, .info-table td {
            padding: 3px 10px;
        }
        .csv-table tr:nth-child(even), .info-table tr:nth-child(even) {
            background: #F2F2F2;
        }
        .csv-table tr:nth-child(odd) {
            background: #FFF;
        }


        #support_request_wrap {
            /*margin-top: 15px;*/
            /*padding: 20px;*/
            /*padding-top: 0;*/
            /*background-color:#eee;*/
            /*border: 1px solid #ccc;*/
            /*display: none;*/
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
        #support_request_wrap textarea {
            height: 18em;
        }

        .accordion {
            background: #f1f1f1;
            cursor: pointer;
            padding: 12px 16px;
            width: 100%;
            text-align: left;
            font-size: 16px;
            font-weight: bold;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .accordion .panel-arrow {
            margin-right: 6px;
            display: inline-block;
            transition: transform 0.2s ease;
        }

        .accordion.expanded {
            border-radius: 4px 4px 0 0;
        }

        .accordion.expanded .panel-arrow {
            transform: rotate(90deg);
        }

        .panel {
            display: none;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-top: none;
            background: #f9f9f9;
        }

        .panel table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .panel td {
            border: 1px solid #ddd;
            padding: 5px;
            vertical-align: top;
        }

    </style>
    <script>
        jQuery(document).ready(function() {
            expandAll();
        });
        function togglePanel(button) {
            const panel = button.nextElementSibling;
            panel.style.display = panel.style.display === "block" ? "none" : "block";
            button.classList.toggle('expanded');
        }
        function expandAll() {
            jQuery('button.accordion').each(function() {
                jQuery(this).addClass( 'expanded' );
                jQuery(this).next('.panel').show();
            });
        }
        function collapseAll() {
            jQuery('button.accordion').each(function() {
                jQuery(this).removeClass( 'expanded' );
                jQuery(this).next('.panel').hide();
            });
        }
    </script>
</head>

<body class="wp-core-ui">

    <h2 class="nav-tab-wrapper" style="margin-bottom:0;">  
        <a href="#" id="wpla_tab_feed_content" class="nav-tab nav-tab-active">Feed Content</a>  
        <a href="#" id="wpla_tab_feed_results" class="nav-tab"               >Processing Report</a>  
        <a href="#" id="wpla_tab_feed_support" class="nav-tab"               >Support</a>  
    </h2>

    <!-- Feed Content tab -->
    <div class="tab-content wpla_tab_feed_content_wrapper">

        <h2 style="margin-top:0;">
            <?php
            printf(
                '%s - %s #%d - Batch ID %s',
                $wpl_feed->template_name,
                $wpl_feed->getRecordTypeName( $wpl_feed->FeedType ),
                $wpl_feed->id,
	            $wpl_feed->FeedSubmissionId
            );
            ?>
        </h2>

        <!-- <h3>Details</h3> -->
        <table class="info-table" style="width: 400px;">
            <tr>
                <th>Product Type</th>
                <td><?php echo $wpl_feed->product_type ?></td>
            </tr>
            <tr>
                <th>Created</th>
                <td>
                    <?php echo $wpl_feed->date_created; ?>
                    ( <?php echo human_time_diff( strtotime($wpl_feed->date_created.' UTC') ) ?> ago )
                </td>
            </tr>
            <?php if ( $wpl_feed->SubmittedDate ) : ?>
            <tr>
                <th>Submitted</th>
                <td><?php echo $wpl_feed->SubmittedDate ?>
                ( <?php echo human_time_diff( strtotime($wpl_feed->SubmittedDate.' UTC') ) ?> ago )
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <br>
        <button class="button-secondary expand-all" onclick="expandAll()">Show All</button>  <button class="button-secondary collapse-all" onclick="collapseAll()">Collapse All</button>
        <?php
        $sku_map = [];
        $json = $wpl_feed->data;

        $data = json_decode( $json, true );
        unset( $data['header'] );

        /**
         * List View
         */
        foreach ($data['messages'] as $index => $item) {
            $msg_id = $index+1;
	        $flat = [];
	        wpla_flatten_row($item, $flat);
	        $title = $flat['sku'] ?? "Record #" . $msg_id;

            if ( !empty( $flat['sku'] ) ) {
                $sku_map[ $msg_id ] = $flat['sku'];
            }

	        echo '<button class="accordion" onclick="togglePanel(this)">
        <span class="panel-arrow">&#9654;</span>' .
	             '#'. $msg_id .' '. htmlspecialchars($title) .
	             '</button>';
	        echo '<div class="panel"><table>';
	        foreach ($flat as $key => $value) {
		        echo '<tr>';
		        echo '<td style="width: 40%; font-weight: bold;">' . htmlspecialchars($key) . '</td>';
		        echo '<td>' . htmlspecialchars($value) . '</td>';
		        echo '</tr>';
	        }
	        echo '</table></div>';
        }
        ?>

        <!-- <h3>Debug Data</h3> -->
        <br>
        <pre id="wpla_feed_details_debug" style="display:none"><?php unset( $wpl_feed->types ); print_r( $wpl_feed ) . print_r( ['Plugin Version' => $wpl_wplister_version, 'License' => $wpl_license_email] ) ?></pre>
        <a href="#" onclick="jQuery('#wpla_feed_details_debug').slideToggle();return false;" class="button">Debug Data</a> &nbsp;
        <a href="<?php echo $feed_permalink ?>" class="button">Permalink</a> &nbsp;
        <a href="admin.php?page=wpla-feeds&amp;action=view_amazon_feed_details_raw&amp;amazon_feed=<?php echo $wpl_feed->id ?>&amp;_wpnonce=<?php echo wp_create_nonce( 'wpla_view_feed_details_raw' ); ?>" class="button" target="_blank">View raw feed</a> &nbsp;
        <a href="admin.php?page=wpla-feeds&amp;action=wpla_download_feed_content&amp;amazon_feed=<?php echo $wpl_feed->id ?>&amp;_wpnonce=<?php echo wp_create_nonce( 'wpla_download_feed_content' ); ?>" class="button">Download</a>

    </div>


    <!-- Processing Results tab -->
    <div class="tab-content wpla_tab_feed_results_wrapper" style="display:none;">

        <h2 style="margin-top:0;">Processing Report for feed <?php echo $wpl_feed->FeedSubmissionId ?></h2>
        <!-- Feed Type: <?php echo $wpl_feed->FeedType ?><br> -->


        <table class="info-table">
            <tr>
                <th>Messages Processed</th>
                <td><?php echo $wpl_result_rows['summary']['messagesProcessed'] ?? '-'; ?></td>
            </tr>
            <tr>
                <th>Messages Accepted</th>
                <td><?php echo $wpl_result_rows['summary']['messagesAccepted'] ?? '-'; ?></td>
            </tr>
            <tr>
                <th>Messages Invalid</th>
                <td><?php echo $wpl_result_rows['summary']['messagesInvalid'] ?? '-'; ?></td>
            </tr>
            <tr>
                <th>Errors</th>
                <td><?php echo $wpl_result_rows['summary']['errors'] ?? '-'; ?></td>
            </tr>
            <tr>
                <th>Warnings</th>
                <td><?php echo $wpl_result_rows['summary']['warnings'] ?? '-'; ?></td>
            </tr>
        </table>
        <?php if ( $wpl_result_header ) : ?>
            <pre style="background-color:transparent; border:none; padding:0;"><?php echo $wpl_result_header ?></pre>
        <?php endif; ?>

        <!-- <h3>Submission Result</h3> -->
        <?php
        if ( isset($wpl_result_rows['issues']) && ( sizeof($wpl_result_rows['issues'])>0 ) ) :
            // Filter out any issues that don't have a messageId to prevent array size mismatch
            $valid_issues = array_filter($wpl_result_rows['issues'], function($issue) {
                return isset($issue['messageId']) && !empty($issue['messageId']);
            });
            
            if (!empty($valid_issues)) {
                $messageIds = array_column( $valid_issues, 'messageId' );
                // Only sort if we have valid messageIds and the arrays are the same size
                if (count($messageIds) === count($valid_issues)) {
                    array_multisort( $messageIds, SORT_ASC, $valid_issues );
                }
                $wpl_result_rows['issues'] = $valid_issues;
            }
        ?>

            <table class="csv-table">
                <thead>
                <tr>
                    <th>Message ID</th>
                    <th>Code</th>
                    <th>SKU</th>
                    <th>Severity</th>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($wpl_result_rows['issues'] as $row) :
                    $sku = !empty( $sku_map[ $row['messageId'] ] ) ? $sku_map[ $row['messageId'] ] : '-';
                ?>
                <tr>
                    <td><?php echo $row['messageId']; ?></td>
                    <td><?php echo $row['code']; ?></td>
                    <td><?php echo $sku; ?></td>
                    <td><?php echo $row['severity']; ?></td>
                    <td><?php echo $row['message']; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </div>


    <!-- Support tab -->
    <?php
        $msg_content  = "Hi Support,\n\n";
        $msg_content .= "please have a look at this feed for me, will you? I can't get this to work...\n\n";
        $msg_content .= $feed_permalink."\n\n";
        $msg_content .= "Example SKU: [__please_name_one_example_SKU_here__] \n\n";
        $msg_content .= "Thanks in advance!";
    ?>
    <div class="tab-content wpla_tab_feed_support_wrapper" style="display:none;">
        <h2 style="margin-top:0;">Request Support</h2>
        <div id="support_request_wrap" style="">
            <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" >
                <?php wp_nonce_field( 'wpla_send_to_support' ); ?>
                <input type="hidden" name="feed_id" value="<?php echo $wpl_feed->id ?>" />
                <input type="hidden" name="send_to_support" value="yes" />

                <!-- <h2><?php echo __( 'Send to support', 'wp-lister-for-amazon' ) ?></h2> -->
                Please try to provide as many details as possible about what we might need to do to reproduce the issue.
                <br><br>

                <label for="user_name"><?php echo __( 'Your Name', 'wp-lister-for-amazon' ) ?></label>
                <input type="text" name="user_name" id="user_name" value="" class="text-input" placeholder="Enter your name"/>
                
                <label for="user_email"><?php echo __( 'Your Email', 'wp-lister-for-amazon' ) ?></label>
                <input type="text" name="user_email" value="<?php echo get_bloginfo ( 'admin_email' ) ?>" class="text-input"/>
                
                <label for="user_msg"><?php echo __( 'Your Message', 'wp-lister-for-amazon' ) ?></label>
                <textarea name="user_msg" style="height:12em;"><?php echo $msg_content ?></textarea>
                <br style="clear:both"/>

                <input type="submit" value="<?php echo __( 'Send to support', 'wp-lister-for-amazon' ) ?>" class="button-primary"/>
            </form>         
        </div>
    </div>

<script type="text/javascript">
    // support form
    jQuery( document ).ready( function () {
        
        jQuery('#support_request_wrap form').submit(function() {
            
            if ( jQuery('#support_request_wrap form #user_name').val() == '' ) {
                alert('Please enter your name.');
                return false;
            }

        });

    }); 


    // nav tabs
    jQuery( document ).ready( function () {
        
        jQuery('.nav-tab').click(function() {
            
            jQuery('.nav-tab').removeClass('nav-tab-active');
            jQuery(this).addClass('nav-tab-active');

            jQuery('.tab-content').hide();
            jQuery('.' + this.id + '_wrapper').show();

            return false;
        });

    }); 

</script>


</body>
</html>
<?php
// Helper: Flatten and collect column names
function wpla_collect_columns(array $item, array &$columns, string $prefix = '') {
	foreach ($item as $key => $value) {
		$fullKey = $prefix ? "$prefix.$key" : $key;
		if ($key === 'attributes' && is_array($value)) {
			foreach ($value as $attrKey => $attrArray) {
				foreach ($attrArray as $i => $attr) {
					if (isset($attr['marketplace_id']) && isset($attr['value'])) {
						$colKey = "attributes.{$attrKey}[{$i}].marketplace_id[" . $attr['marketplace_id'] . "]";
						$columns[$colKey] = true;
					} else {
						wpla_collect_columns($attr, $columns, "attributes.{$attrKey}[{$i}]");
					}
				}
			}
		} elseif (is_array($value)) {
			wpla_collect_columns($value, $columns, $fullKey);
		} else {
			$columns[$fullKey] = true;
		}
	}
}

// Helper: Flatten a single row
function wpla_flatten_row(array $item, array &$flat, string $prefix = '') {
	foreach ($item as $key => $value) {
		if (is_numeric($key)) {
			$new_prefix = $prefix . '[' . $key . ']';
		} else {
			$new_prefix = $prefix ? $prefix . '.' . $key : $key;
		}

		if (is_array($value)) {
			if (wpla_is_assoc($value)) {
				// If associative, keep nesting
				wpla_flatten_row($value, $flat, $new_prefix);
			} else {
				// If indexed array, process each item with its index
				foreach ($value as $i => $sub_value) {
					if (is_array($sub_value)) {
						wpla_flatten_row($sub_value, $flat, $new_prefix . '[' . $i . ']');
					} else {
						$flat[$new_prefix . '[' . $i . ']'] = $sub_value;
					}
				}
			}
		} else {
			$flat[$new_prefix] = $value;
		}
	}
}

function wpla_is_assoc(array $arr) {
	return array_keys($arr) !== range(0, count($arr) - 1);
}