<?php
namespace App\Helpers;

class View
{

    /**
     * Slice the string passed by parameter according the length informed
     * @param string $str The string
     * @param int $length The length used to slice
     * @param string $suffix The suffix concatened on sliced string
     * @return string The sliced string
     */
    public static function limitString(string $str, int $length = 30, string $suffix = '...'): string
    {
        if (strlen($str) > $length) {
            return substr($str, 0, $length) . $suffix;
        }
        return $str;
    }
}
