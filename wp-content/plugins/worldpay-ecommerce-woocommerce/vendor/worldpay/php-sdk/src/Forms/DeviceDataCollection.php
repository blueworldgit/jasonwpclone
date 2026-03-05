<?php

namespace Worldpay\Api\Forms;

class DeviceDataCollection
{

	/**
	 * @var string
	 */
	public string $formId;

	/**
	 * @var string
	 */
	public string $url;

	/**
	 * @var string
	 */
	public string $bin;

	/**
	 * @var string
	 */
	public string $jwt;

	/**
	 * @param  string  $formId
	 * @param  string  $url
	 * @param  string  $bin
	 * @param  string  $jwt
	 */
	public function __construct(
		string $formId,
		string $url,
		string $bin,
		string $jwt
	) {
		$this->formId = $formId;
		$this->url = $url;
		$this->bin = $bin;
		$this->jwt = $jwt;
	}

	/**
	 * @return string
	 */
	public function render(): string {
		return '
			<html>
				<head></head>
				<body>
					<form id="'.$this->formId.'" name="devicedata" method="POST" action="'.$this->url.'">
						<input type="hidden" name="Bin" value="'.$this->bin.'" />
						<input type="hidden" name="JWT" value="'.$this->jwt.'" />
					</form>
					<script>
						window.onload = function() {
							let event = "access_worldpay_checkout_post_message_ddc_trigger";
							window.parent.dispatchEvent(new CustomEvent(event));
							document.getElementById("'.$this->formId.'").submit();
						}
					</script>
				</body>
			</html>
		';
	}
}
