<?php

namespace Worldpay\Api\Enums;

/**
 * Preference for how the Issuer decides on a 3DS challenge.
 */
class ChallengePreference
{
	/**
	 * No preference. Let the Issuer decide whether to challenge.
	 */
	public const NO_PREFERENCE = 'noPreference';

	/**
	 * No challenge requested (frictionless). Merchant prefers a frictionless flow.
	 */
	public const NO_CHALLENGE_REQUESTED = 'noChallengeRequested';

	/**
	 * Challenge requested. Merchant prefers challenge.
	 */
	public const CHALLENGE_REQUESTED = 'challengeRequested';

	/**
	 * Challenge requested (mandate challenge). Merchant explicitly requires a challenge.
	 */
	public const CHALLENGE_MANDATED = 'challengeMandated';
}
