<?php

/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Session.php
 * @version 0.2
 * - Helper que auxilia no gerenciamento de Sessões no PHP
 */
namespace HTR\Helpers\Session;

use HTR\Helpers\Criptografia\Criptografia as Cripto;
use App\Config\Configurations as cfg;

class Session
{
    
    private $sessionId;
    private $token;
    private $ip;
    private $userAgent;
    private $cripto;


    public function __construct()
    {
        // instancia a Class de criptografia
        $this->cripto = new Cripto();
        // configura os atributos
        $this->config();
    }
    
    /*
     * Método usado para configurar os atributos da Classe
     */
    private function config()
    {
        // recebe o valor IP do usuário
        $this->ip = $_SERVER['REMOTE_ADDR'];
        // recebe o User Agente do usuário
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
        // Seta o valor do Token gerado
        $this->setToken();
    }
    
    /*
     * Método usado para gerar os caracteres usados como token
     */
    private function setToken()
    {
        $app = cfg::htrFileConfigs()->application;
        // "salt+ip+ProgramaNome+ProgramaVersao+User Agent+salt"
        $strSalt = cfg::STR_SALT . $this->ip . $app->name . $app->version . $this->userAgent.cfg::STR_SALT;
        $this->token = $this->cripto->encode($strSalt, true);
    }

    /*
     * Método usado para gerar os caracteres usados como token
     */
    public function getToken()
    {
        return $this->token;
    }

    public function startSession($sessionId = null)
    {
        /// Verfica se foi passado um id de sessÃ£o existente
        if ($sessionId) {
            /// Recupera sessão exitente
            isset($this->sessionId) ? session_id($sessionId) : null;
        }

        /// verifica se a GLOBAL SESSION foi iniciada
        if (isset($_SESSION)) {
            /// Compara o token da sessão
            if ($_SESSION['token'] != $this->getToken()) {
                // se houver divergencia no token, destroy a sessão
               $this->stopSession();
               return false;
            }
            return true;

        } else {
            /// Caso a Sessão não seja iniciada, inicia o processo de criação da sessão
            session_set_cookie_params( 
                5400, // Tempo de vida da sessão. Padrão 1:30min
                cfg::DEFAULT_URI, // Path da Sessão
                cfg::DOMAIN, // Nome no Domínio
                false, // SSL
                true // HTTP Only
            );
            session_start();
            session_regenerate_id(true);
        }
        // gera um ID novo para a sessão
        // seta o ID da sessão para o atributo 'sessionId'
        $this->sessionId = session_id();
        return null;
    }
    
    /*
     * Método usado para destruir as sessões
     */
    public function stopSession()
    {
        // Verifica se a global foi iniciada, caso contrário inicia a sessão
        isset($_SESSION) ? null : session_start();
        // Destroi a sessão
        return session_destroy();
    }
}
