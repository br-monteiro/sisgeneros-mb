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

use App\Config\Configurations as cfg;

class Criptografia
{

    private $cost;

    public function encode($valor, $definitivo = false)
    {
        if ($definitivo) {
            /// ENCRIPTOGRAFA A STRING PASSADA
            $valor = sha1(cfg::STR_SALT . md5($valor) . cfg::STR_SALT);
        } else {
            //$key previously generated safely, ie: openssl_random_pseudo_bytes
            $key = cfg::STR_SALT;
            $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
            $iv = openssl_random_pseudo_bytes($ivlen);
            $ciphertextRaw = openssl_encrypt($valor, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
            $hmac = hash_hmac('sha256', $ciphertextRaw, $key, $as_binary = true);
            $valor = base64_encode($iv . $hmac . $ciphertextRaw);
        }
        return $valor;
    }

    public function decode($valor)
    {
        $c = base64_decode($valor);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        substr($c, $ivlen, $sha2len = 32);
        $ciphertextRaw = substr($c, $ivlen + $sha2len);
        $valor = openssl_decrypt($ciphertextRaw, $cipher, cfg::STR_SALT, $options = OPENSSL_RAW_DATA, $iv);
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
