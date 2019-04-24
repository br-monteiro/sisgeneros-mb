#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Configurations as cfg;

ini_set('max_execution_time', 300);

new class($argv) {

        const PATH_CONSTANTS = __DIR__ . '/App/Config/Configurations.php';
        const PATH_INDEX_FILE = __DIR__ . '/public/index.php';
        const PATH_DUMP_SQL = __DIR__ . '/dump.sql';
        const PATH_BACKUP_KEYS = __DIR__ . '/App/Config/keys';
        const DS = DIRECTORY_SEPARATOR;

        /**
         * @var \PDO The PDO instance
         */
        private $connection;
        private $args = [];

        public function __construct($args)
        {
            try {
                $this->buildCommands($args);
                // init the flow execution
                $this->changeConstants($this->commandGenerateKey(), $this->commandWithKey());
                $this->changePathAutoload();
                $this->createDataBase();
                $this->insertDataDefault();
                $this->changeAdminUser();
                $this->changeAccessModeOfDirectoryUpload();
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
                    $this->connection = (new HTR\System\ModelCRUD(true))->pdo;
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
         * Try write into file
         * @param string $path The path to file
         * @param mixed $content The content to be written
         * @param string $mode The mode access to open the file
         * @throws \Exception
         */
        private function writeFile(string $path, $content, string $mode = 'w+')
        {
            $file = fopen($path, $mode);

            if ($file === false) {
                throw new \Exception(""
                . "ERRO!"
                . PHP_EOL
                . "Não foi possível abrir o arquivo:"
                . PHP_EOL
                . "Path: {$path}"
                . "" . PHP_EOL);
            }

            $isWrote = fwrite($file, $content);

            if ($isWrote === false) {
                throw new \Exception(""
                . "ERRO!"
                . PHP_EOL
                . "Não foi possível alterar o arquivo:"
                . PHP_EOL
                . "Path: {$path}"
                . "" . PHP_EOL);
            }

            fclose($file);
        }

        /**
         * Try read the file according path
         * @param string $path The path of file
         * @return string The file content
         * @throws \Exception
         */
        private function loadFile(string $path): string
        {
            /**
             * Internal closure to throw a new Exception
             */
            $throwException = function() use ($path) {
                throw new \Exception(""
                . "ERRO!"
                . PHP_EOL
                . "Não foi possível ler arquivo:"
                . PHP_EOL
                . "Path: {$path}"
                . "" . PHP_EOL);
            };

            if (file_exists($path) || !is_readable($path)) {
                $fileContent = file_get_contents($path);
                if ($fileContent === false) {
                    $throwException();
                }
                return $fileContent;
            } else {
                $throwException();
            }
        }

        /**
         * Generate a unique string hash
         * @return string The hash
         */
        private function makeSaltKey(): string
        {
            $strKey = openssl_random_pseudo_bytes(openssl_cipher_iv_length("AES-128-CBC"));
            return sha1($strKey);
        }

        /**
         * Try changes the content of configuration constants
         * @throws \Exception
         */
        private function changeConstants(bool $generateKey = true, string $withKey = null)
        {
            $fileRawContent = $this->loadFile(self::PATH_CONSTANTS);
            $newSaltKey = $this->makeSaltKey();
            // init all replaces
            $rawKey = 'H' . $newSaltKey;
            $newSaltKey = 'H' . preg_quote($newSaltKey); // the letter H is just to fix the RegEx group

            if ($generateKey) {
                if ($withKey) {
                    $rawKey = $withKey;
                    $newSaltKey = preg_quote($withKey);
                }
                $this->saveKey($rawKey);
                $fileNewContent = preg_replace("/(const STR_SALT = ')(.+)(';)/", "$1{$newSaltKey}$3", $fileRawContent);
            } else {
                $fileNewContent = $fileRawContent;
            }

            $fileNewContent = preg_replace("/(const PATH_CORE = ')(.+)(';)/", '$1' . getcwd() . '/$3', $fileNewContent);
            // init the written changes
            $this->writeFile(self::PATH_CONSTANTS, $fileNewContent);
            $this->message('> Chave SALT alterada com sucesso');
            $this->message('> Path do Core alterado com sucesso');
        }

        /**
         * Try changes the path to autoload file into index.php
         * @throws \Exception
         */
        private function changePathAutoload()
        {
            $fileRawContent = $this->loadFile(self::PATH_INDEX_FILE);
            // init all replaces
            $fileNewContent = preg_replace("/(require_once\s')(.+)(\/vendor\/autoload\.php')/", '$1' . getcwd() . '$3', $fileRawContent);
            // init the written changes
            $this->writeFile(self::PATH_INDEX_FILE, $fileNewContent);
            $this->message('> Path do autoload alterado com sucesso');
        }

        /**
         * Try creating the database schemas according the dump.sql file
         * @throws \Exception
         */
        private function createDataBase()
        {
            try {
                // load SQL Dump
                $sqlFile = $this->loadFile(self::PATH_DUMP_SQL);
                // connect into database and execute the SQL queries
                $this->connectDatabase()->exec($sqlFile);
                $this->message('> Banco de Dados criado com sucesso');
            } catch (\PDOException $ex) {
                throw new \Exception(""
                . "Não foi possível executar o dump.sql" . PHP_EOL
                . "Log:" . $ex->getMessage()
                . "" . PHP_EOL);
            }
        }

        /**
         * Try insert the first data of Users and OMs
         * @throws \Exception
         */
        private function insertDataDefault()
        {
            try {
                $currentDate = date('Y-m-d');
                $sql = ""
                    . " INSERT INTO `sisgeneros`.`oms` ( "
                    . "    `id`, `name`, "
                    . "    `naval_indicative`, `uasg`, "
                    . "    `fiscal_agent`, `fiscal_agent_graduation`, "
                    . "    `munition_manager`, `munition_manager_graduation`, "
                    . "    `munition_fiel`, `munition_fiel_graduation`, "
                    . "    `created_at`, `updated_at` "
                    . " ) "
                    . " VALUES ( "
                    . "    1, 'OM PADRAO', "
                    . "    'OMPADR', 123456, "
                    . "    'AGENTE FISCAL', 'AGENTE FISCAL POSTO', "
                    . "    'GESTOR MUNICIAMENTO', 'GESTOR MUNICIAMENTO POSTO', "
                    . "    'FIEL MUNICIAMENTO', 'FIEL MUNICIAMENTO POSTO', "
                    . "    '{$currentDate}', '{$currentDate}' "
                    . " ); "
                    . ""
                    . " INSERT INTO `sisgeneros`.`users` ( "
                    . "    `id`, `oms_id`, "
                    . "    `name`, `email`, "
                    . "    `level`, `username`, "
                    . "    `password`, `change_password`, "
                    . "    `active`, `created_at`, "
                    . "    `updated_at` "
                    . " ) "
                    . " VALUES ( "
                    . "    1, 1, "
                    . "    'ADMINISTRADOR', 'admin@om.mb', "
                    . "    'ADMINISTRADOR', '', "
                    . "    '', 'yes', "
                    . "    'yes', '{$currentDate}', "
                    . "    '{$currentDate}' "
                    . " ); "
                    . ""
                    . " INSERT INTO `sisgeneros`.`ingredients` ( "
                    . "    `id`, `name` "
                    . " ) "
                    . " VALUES ( "
                    . "    1, 'DESCONHECIDO' "
                    . " ); "
                    . ""
                    . " INSERT INTO `sisgeneros`.`meals` ( "
                    . "    `id`, `sort`, `name` "
                    . " ) "
                    . " VALUES "
                    . " ( "
                    . "    1, 1, 'CAFE DA MANHA' "
                    . " ), "
                    . " ( "
                    . "    2, 2, 'ALMOCO' "
                    . " ), "
                    . " ( "
                    . "    3, 3, 'JANTAR' "
                    . " ), "
                    . " ( "
                    . "    4, 4, 'CEIA' "
                    . " ), "
                    . " ( "
                    . "    5, 5, 'DIETA' "
                    . " ); "
                    . "";

                $this->connectDatabase()->exec($sql);
                $this->message('> Dados padrão inseridos com sucesso');
            } catch (\PDOException $ex) {
                throw new \Exception(""
                . "Não foi possível inserir os primeiros dados no sistema." . PHP_EOL
                . "Log:" . $ex->getMessage()
                . "" . PHP_EOL);
            }
        }

        /**
         * Try update the first user access
         * @throws \Exception
         */
        private function changeAdminUser()
        {
            try {
                $username = 'administrador';
                $password = (new \HTR\Helpers\Criptografia\Criptografia())->passHash($username . cfg::STR_SALT);
                $sql = ""
                    . " UPDATE users SET "
                    . " username='{$username}', password='{$password}', change_password='yes' "
                    . " WHERE id = 1; ";
                $this->connectDatabase()->exec($sql);
                $this->message('> Usuário Administrador alterado com sucesso');
            } catch (\PDOException $ex) {
                throw new \Exception(""
                . "Não foi possível configurar o acesso do usuário Administrador" . PHP_EOL
                . "Log:" . $ex->getMessage()
                . "" . PHP_EOL);
            }
        }

        /**
         * Try change the CHMOD of upload files directory
         * @throws \Exception
         */
        private function changeAccessModeOfDirectoryUpload()
        {
            $fullPath = cfg::PATH_CORE . 'public' . cfg::DS . 'arquivos';

            if (file_exists($fullPath) && chmod($fullPath, 0777)) {
                $this->message('> Permissões de acesso no diretório de upload setadas com sucesso');
            } else {
                throw new \Exception(""
                . "Não foi possível configurar as permissões de acesso do diretório de upload de arquivos." . PHP_EOL
                . "Path:" . $fullPath
                . "" . PHP_EOL);
            }
        }

        /**
         * Save the SALT KEY
         * @param string $key
         * @throws \Exception
         */
        private function saveKey(string $key)
        {
            if (file_exists(self::PATH_BACKUP_KEYS) && chmod(self::PATH_BACKUP_KEYS, 0777)) {
                $file = self::PATH_BACKUP_KEYS . self::DS . date('Y-m-d_H-m-s') . '.txt';
                $this->writeFile($file, $key);
                $this->message('> Permissões de acesso no diretório de backup de tokens setadas com sucesso');
                $this->message('> Token salvo com sucesso');
            } else {
                throw new \Exception(""
                . "Não foi possível configurar as permissões de acesso do diretório de backup do token do sistema." . PHP_EOL
                . "Path:" . self::PATH_BACKUP_KEYS
                . "" . PHP_EOL);
            }
        }

        /**
         * Build the commands passed by CLI
         * @param array $args
         */
        private function buildCommands(array $args)
        {
            foreach ($args as $value) {
                $explodedCommand = explode('=', $value);
                $this->args[$explodedCommand[0]] = $explodedCommand[1] ?? null;
            }

            if (isset($this->args['gerar-chave'])) {
                $this->args['gerar-chave'] = $this->args['gerar-chave'] === 'true';
            } else {
                $this->args['gerar-chave'] = true;
            }
        }

        /**
         * Returns the value of command 'gerar-chave'
         * @return bool
         */
        private function commandGenerateKey(): bool
        {
            return $this->args['gerar-chave'];
        }

        /**
         * Returns the value of command 'com-cahve'
         * @return string
         */
        private function commandWithKey(): string
        {
            return $this->args['com-chave'] ?? '';
        }
    };
