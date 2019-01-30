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

    public static function showMsg($message, $tipo, $exit = true)
    {
        self::setMsgDefault();
        $msg = '';

        if (isset(self::$msg[$message])) {
            $msg = "<img src='images/icn_alert_" . $tipo . ".png' > " . self::$msg[$message];
        } else {
            $msg = "<img src='images/icn_alert_" . $tipo . ".png' > " . $message;
        }
        echo "<div class='alert alert-{$tipo}'>{$msg}</div>";

        if ($exit) {
            exit;
        }
    }
}
