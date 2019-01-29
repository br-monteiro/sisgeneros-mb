<?php
namespace App\Config;

use HTR\System\InternalConfigurations;

class Configurations extends InternalConfigurations
{

    const DS = DIRECTORY_SEPARATOR;
    const STR_SALT = '03f3b903d39fa68ede0bbcb2204057fe44a6d49';
    const DOMAIN = 'www.ceimbe.mb';
    const ADMIN_CONTACT = 'E-mail: bruno.monteirodg@gmail.com';
    const PATH_CORE = '/path/to/core/';
    const DIR_DATABASE = self::PATH_CORE . 'App/Database/DbRepository/';
    const DEFAULT_URI = '/app/sisgeneros/';

    /**
     * Returns the configurations of htr.json files
     * @return \stdClass
     */
    public static function htrFileConfigs(): \stdClass
    {
        $projectDirectory = str_replace('App/Config', '', __DIR__);
        $file = $projectDirectory . self::DS . 'htr.json';

        if (file_exists($file)) {
            $fileContent = file_get_contents($file);
            $object = json_decode($fileContent);
            if (is_object($object)) {
                return $object;
            }
        }

        return new \stdClass();
    }
}
