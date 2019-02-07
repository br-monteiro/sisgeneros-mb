<?php
namespace App\Helpers;

class View
{

    /**
     * Slice the string passed by parameter according the length informed
     * @param string $str The string
     * @param int $length The length used to slice
     * @return string The sliced string
     */
    public static function limitString(string $str, int $length = 30): string
    {
        if (strlen($str) > $length) {
            return substr($str, 0, $length);
        }
        return $str;
    }
}
