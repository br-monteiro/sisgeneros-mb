<?php
/**
 * @Controller Index
 */
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\SolicitacaoModel as Solicitacao;

class IndexController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct()
    {
        parent::__construct();
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2, 3, 4]);
    }

    public function indexAction()
    {
        $solicitacao = new Solicitacao();
        $this->view->chart = $solicitacao->chart($this->view->userLoggedIn);
        $this->render('index');
    }
}
