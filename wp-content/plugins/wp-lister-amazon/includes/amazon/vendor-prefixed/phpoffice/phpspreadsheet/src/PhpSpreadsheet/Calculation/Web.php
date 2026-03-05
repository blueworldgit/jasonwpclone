<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation;

/**
 * @deprecated 1.18.0
 */
class Web
{
    /**
     * WEBSERVICE.
     *
     * Returns data from a web service on the Internet or Intranet.
     *
     * Excel Function:
     *        Webservice(url)
     *
     * @see Web\Service::webService()
     *      Use the webService() method in the Web\Service class instead
     *
     * @return string the output resulting from a call to the webservice
     */
    public static function WEBSERVICE(string $url)
    {
        return Web\Service::webService($url);
    }
}
