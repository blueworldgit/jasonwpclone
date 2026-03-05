<?php

namespace Worldpay\Api\Enums;

class ChallengeWindowSize
{
	/**
	 * 3ds windows challenge size
	 */
	public const SIZE_01 = '250x400';
	public const SIZE_02 = '390x400';
	public const SIZE_03 = '500x600';
	public const SIZE_04 = '600x400';
	public const SIZE_05 = 'fullPage';


	/**
	 * @var array
	 */
	public static array $challengeWindowSizeMapping = [
		'01' => [
			'width' => '250',
			'height' => '400',
		],
		'02' => [
			'width' => '390',
			'height' => '400',
		],
		'03' => [
			'width' => '500',
			'height' => '600',
		],
		'04' => [
			'width' => '600',
			'height' => '400',
		],
		'05' => [
			'width' => '100%',
			'height' => '100%',
		]
	];

}