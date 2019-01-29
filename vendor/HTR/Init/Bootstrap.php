<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Bootstrap.php
 * @version 0.2
 * - Class responsavel por organizar e gerenciar as requições passadas por URLs amigaveis
 * - setanto os Controllers, Actions e Parâmetros
 */
namespace HTR\Init;

class Bootstrap
{

    private $url;
    private $controller;
    private $action;
    private $parametros;

    public function __construct()
    {
        $this->setUrl()
            ->setController()
            ->setAction()
            ->setParametros();
    }

    /**
     * Recebe os dados da URL
     */
    private function setUrl()
    {
        $url = filter_input(INPUT_GET, 'url');
        // Controller e Action padrão
        $this->url = isset($url) ? $url : 'Index/index';
        return $this;
    }

    /**
     * Trata os dados enviados pela url e retorna os mesmos
     */
    private function getUrl()
    {
        return explode('/', $this->url);
    }

    /**
     * seta o Controller a ser usado
     */
    private function setController()
    {
        $url = $this->getUrl();
        $this->controller = ucfirst($url[0]) . 'Controller';
        return $this;
    }

    /**
     * retorna o nome do Controller
     */
    private function getController()
    {
        return $this->controller;
    }

    /**
     * seta a Action a ser usado
     */
    private function setAction()
    {
        $url = $this->getUrl();
        $this->action = !empty($url[1]) ? strtolower($url[1]) . 'Action' : 'indexAction';
        return $this;
    }

    /**
     * retorna o nome da Action
     */
    private function getAction()
    {
        return $this->action;
    }

    /**
     * seta os parâmetros enviados pela url
     */
    private function setParametros()
    {
        $url = $this->getUrl();
        unset($url[0], $url[1]);

        if (empty(end($url))) {
            array_pop($url);
        }

        $i = 0;
        if (!empty($url)) {
            foreach ($url as $val) {
                if ($i % 2 == 0) {
                    $indice[] = $val;
                } else {
                    $valor[] = $val;
                }
                $i++;
            }
        } else {
            $valor = $indice = array();
        }
        if (!empty($valor)) {
            if (count($indice) == count($valor) && !empty($indice) && !empty($valor)) {
                $this->parametros = array_combine($indice, $valor);
            } else {
                $this->parametros = array();
            }
        } else {
            $this->parametros = array();
        }
        return $this;
    }

    public function getParametros($key = null)
    {
        // verifica se a chave requisitada existe no Array de parâmetros
        // se existir, retorna o parâmetro com a chave indicada, caso
        // contrário, retornará o valor NULL
        if ($key && array_key_exists($key, $this->parametros)) {
            return $this->parametros[$key];
        } else if ($key && !array_key_exists($key, $this->parametros)) {
            return null;
        }
        return $this->parametros;
    }

    protected function run()
    {
        $class = 'App\\Controllers\\' . $this->getController();
        if (class_exists($class)) {

            // instacia o Controller
            $controller = new $class($this);

            // verifica se o método é existente
            if (!method_exists($class, $this->getAction())) {
                // caso o método (action) não exista, retorna um Erro 404
                new \HTR\Helpers\ErrorPag\ErrorPag('error_404');
            }
            // retorna  a Action
            $action = $this->getAction();
            // executa a Action
            $controller->$action();
        } else {
            // caso o Controller não exista, retorna um Erro 404
            new \HTR\Helpers\ErrorPag\ErrorPag('error_404');
        }
    }
}
