#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Configurations as cfg;
use App\Config\DatabaseConfig;

function makeSaltKey()
{
    $strKey = openssl_random_pseudo_bytes(openssl_cipher_iv_length("AES-128-CBC"));
    return sha1($strKey);
}

function changeConstants()
{
    $pathToConfig = __DIR__ . '/App/Config/Configurations.php';

    if (file_exists($pathToConfig) || !is_readable($pathToConfig)) {
        $fileRawContent = file_get_contents($pathToConfig);
        $newSaltKey = makeSaltKey();
        // init all replaces
        $newSaltKey = 'H' . preg_quote($newSaltKey); // the letter H is just to fix the RegEx group
        $fileNewContent = preg_replace("/(const STR_SALT = ')(.+)(';)/", "$1{$newSaltKey}$3", $fileRawContent);
        $fileNewContent = preg_replace("/(const PATH_CORE = ')(.+)(';)/", '$1' . getcwd() . '/$3', $fileNewContent);
        // init the write changes
        $file = fopen($pathToConfig, 'w+');
        fwrite($file, $fileNewContent);
        fclose($file);

        echo "> Chave SALT alterada com sucesso" . PHP_EOL;
        echo "> Path do Core alterado com sucesso" . PHP_EOL;
    } else {
        throw new \Exception(""
        . "O arquivo de configuração não foi encontrado "
        . "ou não é legível" . PHP_EOL
        . "Arquivo: " . $pathToConfig . PHP_EOL);
    }
}

function changePathAutoload()
{
    $pathToIndex = __DIR__ . '/public/index.php';

    if (file_exists($pathToIndex) || !is_readable($pathToIndex)) {
        $fileRawContent = file_get_contents($pathToIndex);
        // init all replaces
        $fileNewContent = preg_replace("/(require_once\s')(.+)(\/vendor\/autoload\.php')/", '$1' . getcwd() . '$3', $fileRawContent);
        // init the write changes
        $file = fopen($pathToIndex, 'w+');
        fwrite($file, $fileNewContent);
        fclose($file);

        echo "> Path do autoload alterado com sucesso" . PHP_EOL;
    } else {
        throw new \Exception(""
        . "O arquivo de index não foi encontrado "
        . "ou não é legível" . PHP_EOL
        . "Arquivo: " . $pathToIndex . PHP_EOL);
    }
}

function changeAdminUser(\PDO $pdo)
{
    try {
        $username = 'administrador';
        $password = (new \HTR\Helpers\Criptografia\Criptografia())->passHash($username);
        $pdo->exec("UPDATE users SET username='{$username}', password='{$password}', trocar_senha='1' WHERE id = 1;");
        echo "> Usuário Administrador alterado com sucesso" . PHP_EOL;
    } catch (\Exception $ex) {
        throw new \Exception(""
        . "Não foi possível configurar o acesso do usuário Administrador" . PHP_EOL
        . "Log:" . $ex->getMessage()
        . "" . PHP_EOL);
    }
}

function createDataBase(): \PDO
{
    if (!is_dir(cfg::DIR_DATABASE) || !is_writable(cfg::DIR_DATABASE)) {
        throw new \Exception(""
        . "ERRO!"
        . PHP_EOL
        . "O diretório usado para salvar o Bando de dados não existe ou não tem permissão para escrita."
        . PHP_EOL
        . "Path informado:" . cfg::DIR_DATABASE
        . "" . PHP_EOL);
    }

    try {
        // load SQL query
        $sqlFile = file_get_contents('dump.sql');
        $dbName = (new DatabaseConfig())->db['sqlite'];
        // create the database file
        $file = fopen(cfg::DIR_DATABASE . cfg::DS . $dbName, 'w+');
        fclose($file);
        // connect into database and execute the SQL queries
        $pdo = (new HTR\System\ModelCRUD())->pdo;
        $pdo->exec($sqlFile);
        echo "> Arquivo de Sqlite criado com sucesso." . PHP_EOL;
        return $pdo;
    } catch (\Exception $ex) {
        throw new \Exception(""
        . "Não foi possível executar o dump.sql" . PHP_EOL
        . "Log:" . $ex->getMessage()
        . "" . PHP_EOL);
    }
}

function insertDataDefault(\PDO $pdo)
{
    try {
        $time = time();
        // nsert the first OM
        $pdo->exec("INSERT INTO om VALUES (1, 'OM PADRAO', 123456, 'OMPADR', {$time}, {$time}, 'AGENTE', 'AGENTE', 'GESTOR', 'GESTOR', 'FIEL', 'FIEL')");
        // insert the first User
        $pdo->exec("INSERT INTO users VALUES (1, '', '', 1, 'Administrador', 'admin@om.mb', 'ADMINISTRADOR', 1, '', {$time}, {$time}, {$time}, 1)");
        echo "> Dados padrão inseridos com sucesso." . PHP_EOL;
    } catch (\Exception $ex) {
        throw new \Exception(""
        . "Não foi possível inserir os primeiros dados no sistema." . PHP_EOL
        . "Log:" . $ex->getMessage()
        . "" . PHP_EOL);
    }
}
/**
 * IIFE that run config the system
 */
(function () {
    try {
        // init the flow execution
        changeConstants();
        changePathAutoload();
        $pdo = createDataBase();
        insertDataDefault($pdo);
        changeAdminUser($pdo);

        print ">> Configurações finalizadas." . PHP_EOL;
    } catch (\Exception $ex) {
        print $ex->getMessage();
    }
})();
