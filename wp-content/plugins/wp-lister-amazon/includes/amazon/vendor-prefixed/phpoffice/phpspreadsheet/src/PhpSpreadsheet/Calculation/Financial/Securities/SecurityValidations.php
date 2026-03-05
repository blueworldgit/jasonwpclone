<?php
/**
 * @license MIT
 *
 * Modified by __root__ on 07-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Financial\Securities;

use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Financial\FinancialValidations;
use WPLab\Amazon\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class SecurityValidations extends FinancialValidations
{
    /**
     * @param mixed $issue
     */
    public static function validateIssueDate($issue): float
    {
        return self::validateDate($issue);
    }

    /**
     * @param mixed $settlement
     * @param mixed $maturity
     */
    public static function validateSecurityPeriod($settlement, $maturity): void
    {
        if ($settlement >= $maturity) {
            throw new Exception(ExcelError::NAN());
        }
    }

    /**
     * @param mixed $redemption
     */
    public static function validateRedemption($redemption): float
    {
        $redemption = self::validateFloat($redemption);
        if ($redemption <= 0.0) {
            throw new Exception(ExcelError::NAN());
        }

        return $redemption;
    }
}
