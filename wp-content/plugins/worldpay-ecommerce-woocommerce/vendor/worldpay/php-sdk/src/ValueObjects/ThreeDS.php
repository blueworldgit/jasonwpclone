<?php

namespace Worldpay\Api\ValueObjects;

class ThreeDS
{
	/**
	 * @var string
	 */
	public string $type = 'integrated';

	/**
	 * @var string
	 */
	public string $mode = 'always';

	/**
	 * @var string
	 */
	public string $deviceDataAcceptHeader = 'text/html';

	/**
	 * @var string
	 */
	public string $challengeReturnUrl;

	/**
	 * @var string Enum. See ChallengeWindowSize.
	 */
	public string $challengeWindowSize;

	/**
	 * @var string Enum. See ChallengePreference.
	 */
	public string $challengePreference;

	/**
	 * @var string
	 */
	public string $deviceDataAgentHeader;
}
