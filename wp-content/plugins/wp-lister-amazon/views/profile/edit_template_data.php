<style>
    #feed-template-data {
        width: 100%;
        margin-top: 1em;
    }
    #feed-template-data th {
        text-align: left;
    }
    #feed-template-data th h4 {
        margin-bottom: 0;
    }
    #feed-template-data input,
    #feed-template-data select {
        width:90%;
    }
    #feed-template-searchbar {
        padding-bottom: 0.5em;
        border-bottom: 1px solid #eee;
    }
    .select2-container {
        box-sizing: border-box;
        display: inline-block;
        margin-bottom: 5px !important;
    }
</style>
<?php
/* @var WPLA_AmazonProfile $wpl_profile */
if ( $wpl_profile->isLegacyProfile() && !isset( $_REQUEST['product_type_form'] ) ) {
	include 'feed_template_form.php';
} else {
	if ( !empty($_REQUEST['product_type_form']) ) {
		//$wpl_profile->convertFieldNames();
		$converter = new \WPLab\Amazon\Helper\ProfileProductTypeConverter( $wpl_profile );
		$wpl_profile = $converter->convertFields();
	}
	include 'product_type_form.php';
}