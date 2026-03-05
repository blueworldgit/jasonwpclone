<?php

namespace Worldpay\Api\Forms;

class Challenge
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
	public string $jwt;

	/**
	 * @var string
	 */
	public string $md;

	/**
	 * @param  string  $formId
	 * @param  string  $url
	 * @param  string  $jwt
	 * @param  string  $md
	 */
	public function __construct(
		string $formId,
		string $url,
		string $jwt,
		string $md = ''
	) {
		$this->formId = $formId;
		$this->url = $url;
		$this->jwt = $jwt;
		$this->md = $md;
	}

	/**
	 * @return string
	 */
	public function render(): string {
		$html = '
			<html>
				<head></head>
				<body>
					<form id="'.$this->formId.'" method= "POST" action="'.$this->url.'">
						<input type = "hidden" name= "JWT" value= "'.$this->jwt.'" />
						<input type="hidden" name="MD" value="'.$this->md.'" />
					</form>
					<script>
						window.onload = function() {
						  document.getElementById("'.$this->formId.'").submit();
					    }
					</script>
				</body>
			</html>
		';
		return $html;
	}
}
