<?php
unset( $wpl_feed->types );
?><html>
<head>
	<title>feed results</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<style type="text/css">
        pre {
            background-color: #eee;
            border: 1px solid #ccc;
            padding: 20px;
        }

        body, td, th {
            font-size: .8em;
            font-family: Helvetica Neue,Helvetica,sans-serif;
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
        .csv-table td {
            padding: 3px 10px;
        }
        .csv-table tr:nth-child(even) {
            background: #F2F2F2;
        }

	</style>
</head>

<body>

<h2>Processing Report for feed <?php echo $wpl_feed->FeedSubmissionId ?></h2>

<h3>Details</h3>
Feed Submission ID: <?php echo $wpl_feed->FeedSubmissionId ?><br>
Feed Type: <?php echo $wpl_feed->FeedType ?><br>

<h3>Submission Result</h3>

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
	$messageIds = array_column( $wpl_result_rows['issues'], 'messageId' );
	// Only sort if all issues have messageId to avoid array size mismatch
	if ( count($messageIds) === count($wpl_result_rows['issues']) ) {
		array_multisort( $messageIds, SORT_ASC, $wpl_result_rows['issues'] );
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


<p>
	<a href="admin.php?page=wpla-feeds&amp;action=wpla_download_feed_results&amp;amazon_feed=<?php echo $wpl_feed->id ?>&amp;_wpnonce=<?php echo wp_create_nonce( 'wpla_download_feed_results' ); ?>" class="button">Download</a>
</p>


</body>
</html>
