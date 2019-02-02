<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\SolicitacaoModel as Solicitacao;
use App\Models\FornecedorModel;
use App\Models\AvaliacaoFornecedorModel as Avaliacao;

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
        $fornecedor = new FornecedorModel();
        $avaliacao = new Avaliacao();
        $this->view->melhoresAvaliacoes = $avaliacao->findBestBadSuppliers();
        $this->view->pioresAvaliacoes = $avaliacao->findBestBadSuppliers("ASC");
        $this->view->pendAprov = $solicitacao->findQtdSolicitByStatus($this->view->userLoggedIn, 'ABERTO');
        $this->view->solicitacoesMensal = $solicitacao->findSolitacoesMensal($this->view->userLoggedIn);
        $this->view->fornecedor = $fornecedor->findAll();
        $this->view->chart = $solicitacao->chart($this->view->userLoggedIn);
        $this->render('index');
    }
}
