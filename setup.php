#!/usr/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';

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
        $fileNewContent = preg_replace("/(const STR_SALT = ')(.+)(';)/", "$1\${$newSaltKey}$3", $fileRawContent);
        $fileNewContent = preg_replace("/(const PATH_CORE = ')(.+)(';)/", '$1' . getcwd() . '/$3', $fileRawContent);
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

function changeAdminUser()
{
    try {
        $pdo = (new HTR\System\ModelCRUD())->pdo;
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
/**
 * IIFE that run config the system
 */
(function () {
    try {
        changeConstants();
        changeAdminUser();

        print ">> Configurações finalizadas." . PHP_EOL;
    } catch (\Exception $ex) {
        print $ex->getMessage();
    }
})();
