<?php
/**
 * FRAMEWORK - HTR - V 2.2
 * Desenvolvido por Bruno Monteiro
 */
// composer autoload
require_once '../vendor/autoload.php';

/**
 * @HACK
 * Debugger the values and exit.
 * If you want don't stop the program execution, just pass 'nonstop' as argument
 */
function d()
{
    $args = func_get_args();
    $stop = true;
    echo '<pre>';
    foreach ($args as $value) {
        if ($value === 'nonstop') {
            $stop = false;
        }
        var_dump($value);
    }
    echo '</pre>';
    if ($stop) {
        exit;
    }
}
try {
    // Inicia a Aplicação
    new \App\Init();
} catch (\Exception $e) {

    echo 'Código: <strong>'
    . $e->getCode() . '</strong> - Erro em <strong>'
    . $e->getFile() . '</strong>:<strong>'
    . $e->getLine() . '</strong><br>Erro informado: <strong>'
    . $e->getMessage() . '</strong><br><pre>';
    echo $e->getTraceAsString();
    echo '</pre>';
}
