<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\SolicitacaoModel as Solicitacao;

class IndexController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
    }

    public function indexAction()
    {
        $solicitacao = new Solicitacao();
        $this->view->chart = $solicitacao->chart($this->view->userLoggedIn);
        $this->render('index');
    }
}
