<?php
namespace App\Helpers;

use App\Helpers\Utils;

class View extends Utils
{

    /**
     * Slice the string passed by parameter according the length informed
     * @param string $str The string
     * @param int $length The length used to slice
     * @param string $suffix The suffix concatened on sliced string
     * @return string The sliced string
     */
    public static function limitString($str, int $length = 30, string $suffix = '...')
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

    /**
     * List the PDF files from upload directory
     * @param string $directoryReference
     * @return array
     */
    public static function listFilesPdf(string $directoryReference): array
    {
        $result = [];

        if (file_exists($directoryReference)) {
            $result = scandir($directoryReference);
            $result = array_filter($result, function ($file) {
                return (bool) preg_match('/.+\.pdf$/', $file);
            });
        }

        return $result;
    }

    /**
     * Build the date in format DD-MM-YYYY from YYYY-MM-DD HH:MM:SS
     * @param string $date The raw date
     * @param string $delimiter The separate number. By default is '-'
     * @return string
     */
    public static function humanDate(string $date, string $delimiter = '-'): string
    {
        $explodedDate = explode(' ', $date);
        if (isset($explodedDate[0])) {
            $date = $explodedDate[0];
        }
        $dateEmplode = explode($delimiter, $date);
        return implode($delimiter, array_reverse($dateEmplode));
    }

    /**
     * Check if the values A and B is equals and returns 'selected'
     * @param mixed $a The input value
     * @param type $b The comparison value
     * @return string
     */
    public static function isSelected($a, $b): string
    {
        return $a === $b ? 'selected' : '';
    }
}
