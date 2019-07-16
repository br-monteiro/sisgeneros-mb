#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Configurations as cfg;

ini_set('max_execution_time', 300);

new class($argv) {

        /**
         * @var \PDO The PDO instance
         */
        private $connection;

        public function __construct()
        {
            try {
                $this->runFix();
                $this->message('>> Configurações finalizadas');
            } catch (\Exception $ex) {
                print $ex->getMessage();
            }
        }

        /**
         * Try connect with database and returns the connection reference
         * @return \PDO
         * @throws \Exception
         */
        private function connectDatabase(): \PDO
        {
            try {
                if (!$this->connection) {
                    $this->connection = (new HTR\System\ModelCRUD())->pdo;
                }
                return $this->connection;
            } catch (\Exception $ex) {
                throw new \Exception(""
                . "ERRO!"
                . PHP_EOL
                . "Não foi possível connectar ao banco de dados"
                . PHP_EOL
                . $ex->getMessage()
                . PHP_EOL
                . "" . PHP_EOL);
            }
        }

        /**
         * Print a message on screen. Use just one line.
         * @param string $message
         */
        private function message(string $message)
        {
            echo $message . PHP_EOL;
        }

        /**
         * Try creating the database schemas according the dump.sql file
         * @throws \Exception
         */
        private function runFix()
        {
            try {
                // connect into database and execute the SQL queries
                $this->connectDatabase()->exec(""
                . " ALTER TABLE `sisgeneros`.`recipes` "
                . " CHANGE COLUMN `sort` `sort` VARCHAR(15) NOT NULL; ");
                $this->message('> Banco de Dados alterado com sucesso');
            } catch (\PDOException $ex) {
                throw new \Exception(""
                . "Não foi possível executar operação" . PHP_EOL
                . "Log:" . $ex->getMessage()
                . "" . PHP_EOL);
            }
        }
    };
