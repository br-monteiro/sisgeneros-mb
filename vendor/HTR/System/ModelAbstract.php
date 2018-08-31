<?php

/*
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file ModelAbstract.php
 * @version 0.1
 * - Class responsavel por processar as configurações e iniciar a conexão com o Banco de Dados
 */
namespace HTR\System;

use App\Config\DatabaseConfig as Config;
use HTR\Database\Database as DB;

abstract class ModelAbstract
{
    protected $db, $pdo;

    public function __construct()
    {
        if (class_exists('\App\Config\DatabaseConfig')) {
            $config_db = new Config();
            $this->db = new DB($config_db->db);
            $this->pdo = $this->db->conecta();
        } else {
            throw new \Exception('Você não criou o arquivo de configuração do banco de dados em App\Config\DatabaseConfig');
        }
    }

}
