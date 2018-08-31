<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Criptografia.php
 * @version 0.2
 * - Helper que auxilia nas tarefas de criptografia da aplicação
 */
namespace HTR\Helpers\Criptografia;

class Criptografia
{

    private $cost;

    public function encode($valor, $definitivo = false)
    {
        /// VERIFICA SE O VALOR A SER ENCRIPTOGRAFADO SERÁ DE APENAS UMA VIA (DEFINITIVO*)
        if ($definitivo) {
            /// ENCRIPTOGRAFA A STRING PASSADA
            $valor = sha1(STRSAL . md5($valor) . STRSAL);
        } else {
            /// ENCRIPTOGRAFA A STRING PASSADA
            $valor = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(STRSAL), $valor, MCRYPT_MODE_CBC, md5(md5(STRSAL))));
        }
        return $valor;
    }

    /////////////
    // MÉTODO USADO PARA DESENCRIPTOGRAFAR DADOS
    public function decode($valor)
    {
        /// ENCRIPTOGRAFA A STRING PASSADA
        $valor = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(STRSAL), base64_decode($valor), MCRYPT_MODE_CBC, md5(md5(STRSAL))), "\0");
        return $valor;
    }

    public function setCost($cost = null)
    {
        $this->cost = isset($cost) ?: 11;
    }

    private function getCost()
    {
        if (is_numeric($this->cost) && $this->cost > 0) {
            $cost = $this->cost;
        } else {
            $cost = 11;
        }

        return $cost;
    }

    public function passHash($password)
    {
        $options = [
            'cost' => $this->getCost(),
        ];
        // Nativo do PHP
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    public function passVerify($password, $hash)
    {
        // Nativo do PHP
        return password_verify($password, $hash);
    }
}
