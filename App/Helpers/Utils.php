<?php
namespace App\Helpers;

class Utils
{

    /**
     * Convert the date from DD-MM-YYYY to YYYY-MM-YY
     * @param string $date The inout date
     * @return string
     * @throws \Exception
     */
    public static function dateDatabaseFormate(string $date): string
    {
        if (preg_match('/\d{2}-\d{2}-\d{4}/', $date)) {
            $date = explode('-', $date);
            return $date[2] . '-' . $date[1] . '-' . $date[0];
        } else {
            throw new \Exception('The date entered is not in the format DD-MM-YYYY');
        }
    }

    /**
     * Convert the value to currency format
     * @param int|float $value The values to be converted
     * @param string $currencySymbol The currency symbol. By default is 'R$ '
     * @return string
     */
    public static function floatToMoney($value, string $currencySymbol = 'R$ '): string
    {
        return $currencySymbol . number_format($value, 2, ',', '.');
    }

    /**
     * Convert the value to float
     * @param int|string $value The values to be converted
     * @return float
     */
    public static function moneyToFloat($value): float
    {
        $value = str_replace(".", "", $value);
        $value = str_replace(",", ".", $value);
        $value = $value ? number_format($value, 2) : '0.0';
        return floatval($value);
    }

    /**
     * Normali a number to float
     * @param mixed $value
     * @param int $decimals
     * @param bool $withComma
     * @return type
     */
    public static function normalizeFloat($value, int $decimals = 2, bool $withComma = false)
    {
        if ($value) {
            $value = str_replace(",", ".", $value);
            $value = number_format($value, $decimals);

            if ($withComma) {
                $value = str_replace(".", ",", $value);
            }
        }

        return $value;
    }

    /**
     * Check the length of string
     * @param string $str The string to be verified
     * @param int $lengthInit The length init
     * @param int $lengthEnd The length end
     * @return bool
     */
    public static function checkLength($str = '', int $lengthInit = 0, int $lengthEnd = 0): bool
    {
        if ($lengthInit && $lengthEnd) {
            return strlen($str) >= $lengthInit && strlen($str) <= $lengthEnd;
        } elseif ($lengthInit) {
            return strlen($str) >= $lengthInit;
        } elseif ($lengthEnd) {
            return strlen($str) <= $lengthEnd;
        }
        return false;
    }
}
