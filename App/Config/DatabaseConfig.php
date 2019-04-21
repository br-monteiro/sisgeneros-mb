<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * 
 * @author Edson Monteiro <bruno.monteirodg@gmail.com>
 * @file DatabaseConfig.php
 * @version 0.1
 * - Class que configura as diretrizes para conexão com o Banco de Dados
 */
namespace App\Config;

class DatabaseConfig
{

    public $db = [
        'servidor' => '127.0.0.1',
        'banco' => 'sisgeneros',
        'usuario' => 'webapp',
        'senha' => 'webapp',
        'opcoes' => [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"],
        // Altere este campo apenas se for usar a Base de Dados Sqlite
        'sqlite' => null
    ];

}
