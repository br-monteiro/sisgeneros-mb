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

    /**
     * Highlight a text according to the search
     * @param string $fullText The full text
     * @param string $search The search text - to be apply Highlight
     * @param string $customClass The class used into <span> tag
     * @return string
     */
    public static function highlight(string $fullText, string $search, string $customClass = 'highlight'): string
    {
        $wrapTag = "<span class='{$customClass}'>{$search}</span>";
        return str_replace($search, $wrapTag, $fullText);
    }
}
