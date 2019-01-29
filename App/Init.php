<?php
namespace App;

use HTR\Init\Bootstrap;

class Init extends Bootstrap
{

    public function __construct()
    {
        self::setUpHeaders();
        parent::__construct();
        // Roda a aplicação
        $this->run();
    }

    private static function setUpHeaders()
    {
        header('Content-type: text/html; charset=UTF-8');
    }
}
