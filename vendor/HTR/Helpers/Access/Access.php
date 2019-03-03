<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Access.php
 * @version 0.3
 * - Helper que auxilia no gerencimento de controle de acesso a páginas
 */
namespace HTR\Helpers\Access;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Session\Session;
use App\Config\Configurations as cfg;

class Access extends CRUD
{

    protected $entidade = cfg::DB_USER_TABLE;
    private $url;
    private $nivelAcesso = [];
    private $breakRedirect = false;

    /**
     * Método usado para para setar os possíveis niveis de acesso
     */
    private function setNivelAcesso(array $nivelAcesso)
    {
        // seta para o array os possíveis níveis de acesso
        $this->nivelAcesso = array_merge($this->nivelAcesso, $nivelAcesso);
    }

    /**
     * Método usado para comparar o nível de acesso do usuário com os 
     * padrões de níveis de acesso
     */
    private function verificarNivelAcesso($nivelAcessoIndicado)
    {
        // procura no Array o nível de acesso indicado nos padrões de nivel de acesso
        return in_array($nivelAcessoIndicado, $this->nivelAcesso);
    }

    /**
     * Método usado para permitir o acesso somente ao usuário logado
     */
    public function authenticAccess(array $nivelAcesso)
    {
        $session = new Session();
        $session->startSession();
        // Compara o registro de token da sessão com o
        // token gerado automaticamente
        if (!isset($_SESSION['token'])) {
            $session->stopSession();
            $this->redirectTo(cfg::URI_LOGIN);
        }
        if ($_SESSION['token'] == $session->getToken()) {
            $result = $this->findById($_SESSION['userId']);
            // Seta os níveis de acesso permitidos na página
            $this->setNivelAcesso($nivelAcesso);
            // Verifica se o usuário tem permissão de acesso
            if (!$this->verificarNivelAcesso($result['level'])) {
                // Redireciona o usuário sem permissão
                // de acesso para página inicial
                $this->redirectTo();
                return;
            }
            if (isset($result[cfg::DB_USER_CHANGE_PASSWORD_FIELD]) && $result[cfg::DB_USER_CHANGE_PASSWORD_FIELD] === 'yes') {
                $this->redirectTo(cfg::URI_CHANGE_PASSWORD);
            }
            // Retorna o resultado da consulta
            // feita no Banco de Dados com o ID fornecido
            return $result;
        }
        $session->stopSession();
        $this->redirectTo();
    }

    /**
     * Método usado para evitar o RELOGIN do usuário
     */
    public function notAuthenticatedAccess()
    {
        $session = new Session();
        $session->startSession();
        // Compara o registro de token da sessão com o token gerado automaticamente
        if (isset($_SESSION['token']) && $_SESSION['token'] == $session->getToken()) {
            // Redireciona para página incial
            $this->redirectTo(); // url = /
        }
        $session->stopSession();
        return true;
    }

    private function redirectTo($url = null)
    {
        $url = $this->url ?: $url;
        // Redireciona se o atributo breakRedirect conter o valor false
        if (!$this->breakRedirect) {
            echo '<meta http-equiv="refresh" content="0;URL=' . cfg::DEFAULT_URI . $url . '" />'
            . '<script>window.location = "' . cfg::DEFAULT_URI . $url . '"; </script>';
            header('Location:' . cfg::DEFAULT_URI . $url);
            exit;
        }
    }

    public function setRedirect($url = null)
    {
        $this->url = $url ?: false;
        return $this;
    }

    public function breakRedirect()
    {
        $this->breakRedirect = true;
        return $this;
    }

    public function clearAccessList()
    {
        $this->nivelAcesso = [];
        return $this;
    }
}
