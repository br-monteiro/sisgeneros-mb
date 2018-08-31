<?php
/**
 * FRAMEWORK - HTR - V 2.1
 * Desenvolvido por Bruno Monteiro
 */
header('Content-type: text/html; charset=UTF-8');

// Arquivo de configuração do Aplicativo
require_once '../App/Config/Config.conf.php';

// Arquivo de configuração do Sistema
require_once DRINST . 'vendor/HTR/System/ConfigSystem.conf.php';
// Inclui o autoload do Composer
require_once DRINST . 'vendor/autoload.php';

try {
    // Inicia a Aplicação
    new \App\Init();
} catch (\Exception $e) {

    echo
    'Código: <strong>' . $e->getCode() . '</strong> - Erro em <strong>' . $e->getFile() . '</strong>:<strong>' . $e->getLine() . '</strong><br>
    Erro informado: <strong>' . $e->getMessage() . '</strong><br>
    <pre>';
    echo $e->getTraceAsString();
    echo '</pre>';
}
