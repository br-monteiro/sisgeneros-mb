<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Database.php
 * @version 0.1
 * - Class que Executa, Seleciona a Instrução e Gerencia a Conexão com o Banco de Dados
 */
namespace HTR\Database;

class Database
{

    private $config;
    private $instrucao;
    private static $pdo = null;

    public function __construct(Array $config)
    {
        $this->config = $config;
        $this->validaConexao();
    }

    public function conecta()
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $pdo = null;

        try {
            if ($this->config['sqlite'] == null) {
                $dns = 'mysql:host=' . $this->decode($this->config['servidor']) . ';dbname=' . $this->decode($this->config['banco']);
                $pdo = new \PDO($dns, $this->decode($this->config['usuario']), $this->decode($this->config['senha']), $this->config['opcoes']);
            } else {
                $pdo = new \PDO('sqlite:' . DATADR . $this->config['sqlite']);
                $pdo->exec('PRAGMA encoding = "UTF-8";');
            }
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new \Exception('Erro ao conectar. Código: ' . $e->getCode() . '! Mensagem: ' . $e->getMessage());
        }
        self::$pdo = $pdo;

        return $pdo;
    }

    public function instrucao($instrucao)
    {
        switch ($instrucao) {
            case 'select':
                $this->instrucao = new Instruction\Select();
                break;
            case 'update':
                $this->instrucao = new Instruction\Update();
                break;
            case 'delete':
                $this->instrucao = new Instruction\Delete();
                break;
            case 'insert':
                $this->instrucao = new Instruction\Insert();
                break;
            default:
                throw new \Exception('Este tipo de instrução não á válido');
        }
        return $this->instrucao;
    }

    public function executa($select = false)
    {
        $sql = self::$pdo->prepare($this->instrucao->retornaSql());

        $binds = $this->instrucao->retornaBind();

        foreach ($binds as $k => &$bind) {
            $sql->bindValue(':' . $k, $bind);
        }

        if (!$select) {
            return $sql->execute();
        }

        $sql->execute();
        return $sql;
    }

    private function validaConexao()
    {
        if (is_array($this->config)) {
            if (empty($this->config['servidor'])) {
                throw new \Exception('Você não informou o servidor!');
            }
            if (empty($this->config['banco'])) {
                throw new \Exception('Você não informou o banco de dados!');
            }
            if (empty($this->config['usuario'])) {
                throw new \Exception('Você não informou o usuário!');
            }
            if (!isset($this->config['senha'])) {
                throw new \Exception('Você não informou a senha!');
            }
            if (!isset($this->config['opcoes']) or ! is_array($this->config['opcoes'])) {
                throw new \Exception('Você não informou as opções ou não é '
                . 'um array, você precisa informar isso mesmo que vazio!');
            }

            return true;
        }
        throw new \Exception('Esta não é uma configuração válida!');
    }

    private function decode($str)
    {
        return base64_decode($str);
    }
}
