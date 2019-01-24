<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file ErrorPag.php
 * @version 0.1
 * - Helper que gerencia a inclusão de páginas de erro do sistema
 */
namespace HTR\Helpers\ErrorPag;

class ErrorPag
{

    public function __construct($layout_error)
    {
        $this->getError($layout_error);
    }

    private function getError($layout_error)
    {
        require_once '../App/Views/ErrorPag/' . $layout_error . '.phtml';
        exit();
    }
}
