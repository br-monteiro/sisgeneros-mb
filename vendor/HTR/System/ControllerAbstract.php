<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file ControllerAbstract.php
 * @version 0.2
 * - Class responsavel por gerenciar os Controllers da Aplicação
 */
namespace HTR\System;

use HTR\Helpers\ErrorPag\ErrorPag as Error;
use App\Config\Configurations as cfg;

class ControllerAbstract
{

    protected $view;
    protected $pagina;
    private $bootstrap;

    public function __construct($bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->view = new \stdClass();
    }

    protected function render($pagina, $useLaytou = true, $alternativeLayout = 'default')
    {
        $this->pagina = $pagina;
        $fileLayout = cfg::PATH_CORE . "App/Views/Layout/{$alternativeLayout}.phtml";
        if ($useLaytou == true && file_exists($fileLayout)) {
            include_once "$fileLayout";
        } else {
            echo $this->content();
        }
    }

    protected function content()
    {
        $classAtua = get_class($this);
        $controller = strtolower(str_replace("App\Controllers\\", "", $classAtua));
        $singleClassName = str_replace("controller", "", $controller);
        $filename = cfg::PATH_CORE . 'App/Views/' . ucfirst($singleClassName) . '/' . $this->pagina . '.phtml';
        if (!file_exists($filename)) {
            new Error('error_404');
        }
        include_once $filename;
    }

    public function getParametro($key = null)
    {
        return $this->bootstrap->getParametros($key);
    }

    public function getView()
    {
        return $this->view;
    }
}
