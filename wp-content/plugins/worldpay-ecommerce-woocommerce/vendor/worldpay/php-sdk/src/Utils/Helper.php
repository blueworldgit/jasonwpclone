<?php

namespace Worldpay\Api\Utils;

use Worldpay\Api\Exceptions\InvalidArgumentException;

class Helper
{
    /**
     * Generates a string with the requested length that can be used as a reference.
     *
     * @param  int  $length
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public static function generateString(int $length): string {
        if ($length <= 0) {
            throw new InvalidArgumentException('Length must be greater than zero.');
        }

        return substr(bin2hex(random_bytes($length)), 0, $length);
    }

    /**
     * Generates a GUIDv4 string.
     *
     * @param $data
     *
     * @return string|void
     * @throws \Exception
     */
    public static function guidv4($data = null) {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data ??= random_bytes(16);
        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Verify if GUID is valid
     *
     * @param $guid
     *
     * @return bool
     */
    public static function isValidGuid($guid): bool {
        if (!is_string($guid)) {
            return false;
        }
        return (bool) preg_match('/^\{?[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}\}?$/', $guid);
    }

    /**
     * Iterates over each value in the array and removes empty values.
     *
     * @param  array  $array
     * @return array
     */
    public static function cleanArray(array $array = []) {
        $array = array_filter($array);

        foreach ($array as $key => $item) {
            if(is_array($item)) {
                $array[$key] =  self::cleanArray($item);
            } else {
                $array[$key] = str_replace(PHP_EOL, " ", $array[$key]);
            }
        }

        return $array;
    }
}
