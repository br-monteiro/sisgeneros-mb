<?php

namespace App;

use HTR\Init\Bootstrap;

class Init extends Bootstrap
{
    public function __construct()
    {
        parent::__construct();
        // Roda a aplicação
        $this->run();
    }
}
