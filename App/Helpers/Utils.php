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
}
