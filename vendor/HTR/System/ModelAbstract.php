<?php
/**
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

    /**
     * @var HTR\Database\Database
     */
    protected $db;

    /**
     * @var \PDO The PDO Instance
     */
    public $pdo;

    public function __construct()
    {
        if (class_exists('\App\Config\DatabaseConfig')) {
            $configDb = new Config();
            $this->db = new DB($configDb->db);
            $this->pdo = $this->db->conecta();
        } else {
            throw new \Exception(''
                . 'Arquivo de configuração do banco de dados não encontrado '
                . 'em App\Config\DatabaseConfig');
        }
    }
}
