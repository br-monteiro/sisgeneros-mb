<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Mensagem.php
 * @version 0.2
 * - Helper responsavel por printar na tela as mensagens de diálogo com o usuário
 */
namespace HTR\Helpers\Mensagem;

class Mensagem
{

    private static $msg;

    private static function setMsgDefault()
    {
        self::$msg = [
            '000' => 'Erro ao executar operação.',
            '111' => 'Sucesso ao executar operação. <script>resetForm();</script>',
            '001' => 'Sucesso ao executar operação.'
        ];
    }

    public static function showMsg($msg, $tipo, $exit = true)
    {
        self::setMsgDefault();

        if (array_key_exists($msg, self::$msg)) {
            $msg = "<img src='" . DIRIMG . "icn_alert_" . $tipo . ".png' > " . self::$msg[$msg];
        } else {
            $msg = "<img src='" . DIRIMG . "icn_alert_" . $tipo . ".png' > " . $msg;
        }
        echo "<div class='alert alert-{$tipo}'>{$msg}</div>";

        if ($exit) {
            exit;
        }
    }
}
