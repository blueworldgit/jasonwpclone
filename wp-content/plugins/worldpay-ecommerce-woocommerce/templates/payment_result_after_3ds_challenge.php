<?php

$output = "
<html>
	<head></head>
	<body>
        <script>
            window.onload = function() {
                let eventType = 'access_worldpay_checkout_payment_3ds_completed';
                let eventData = {
                    detail: {
                        data: '$data'
                    },
                }
                window.parent.dispatchEvent(new CustomEvent(eventType, eventData));
            }
        </script>
	</body>
</html>";
