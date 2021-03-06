<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\SolicitacaoModel as Solicitacao;
use App\Models\AvaliacaoFornecedorModel as Avaliacao;
use App\Models\AvisosModel;

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
        $avaliacao = new Avaliacao();
        $arrEvaluation = $avaliacao->findBestBadSuppliers();
        $this->view->melhoresAvaliacoes = $arrEvaluation;
        $this->view->pioresAvaliacoes = array_reverse($arrEvaluation);
        $this->view->pendAprov = $solicitacao->findQtdSolicitByStatus($this->view->userLoggedIn, 'ABERTO');
        $this->view->solicitacoesMensal = $solicitacao->findSolitacoesMensal($this->view->userLoggedIn);
        $this->view->solicitacoesAtrasadas = $solicitacao->findQtdSolicitAtrasadas($this->view->userLoggedIn);
        $this->view->resultAvisos = (new AvisosModel())->fetchAllAvisosByOmId($this->view->userLoggedIn['oms_id']);
        $this->view->lastUpdated = $solicitacao->lastUpdated($this->view->userLoggedIn);
        $this->render('index');
    }
}
