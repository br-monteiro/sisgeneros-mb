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
}
