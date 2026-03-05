<?php
/**
 * @license BSD-3-Clause
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\SellingPartnerApi\Contract;

use WPLab\Amazon\Psr\Http\Message\RequestInterface;

interface RequestSignerContract
{
    public function signRequest(
        RequestInterface $request,
        ?string $scope = null,
        ?string $restrictedPath = null,
        ?string $operation = null
    ): RequestInterface;
}