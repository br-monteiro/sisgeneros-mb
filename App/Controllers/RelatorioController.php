<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\SolicitacaoModel as Solicitacao;
use App\Models\LicitacaoModel as Licitacao;
use App\Models\SolicitacaoItemModel as SolItem;
use App\Models\ItemModel as Item;

class RelatorioController extends Controller implements CtrlInterface
{

    private $access;
    private $solItem;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = APPDIR . 'relatorio/';
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
        $this->view->idLista = $this->getParametro('idlista');
        $this->solItem = new SolItem();
    }

    public function indexAction()
    {
        $this->solicitacaoAction();
    }

    public function solicitacaoAction()
    {
        $model = new Solicitacao();
        $this->view->title = 'Relatório de Solicitações';
        $model->paginator($this->getParametro('pagina'), ['nivel' => 'ADMINISTRADOR']);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function detalharAction()
    {
        $model = new Solicitacao();
        $licitacao = new Licitacao();
        $solItem = new SolItem();
        $this->view->title = 'Itens da Solicitação';
        $this->view->resultSolicitacao = $model->recuperaDadosRelatorioSolicitacao($this->view->idLista);
        $this->view->resultLicitacao = $licitacao->findById($this->view->resultSolicitacao['id_licitacao']);
        $solItem->paginator($this->getParametro('pagina'), $this->view->idLista);
        $this->view->result = $solItem->getResultadoPaginator();
        $this->view->btn = $solItem->getNavePaginator();
        $this->render('mostra_item_solicitacao');
    }

    public function demandaAction()
    {
        $licitacao = new Licitacao();
        $this->view->title = 'Licitações Registradas';
        $licitacao->paginator($this->getParametro('pagina'));
        $this->view->result = $licitacao->getResultadoPaginator();
        $this->view->btn = $licitacao->getNavePaginator();
        $this->render('mostra_licitacao_disponivel');
    }

    public function licitacaoAction()
    {
        $item = new Item();
        $licitacao = new Licitacao();
        $this->view->title = 'Lista de Itens da Licitação';
        $item->paginator($this->getParametro('pagina'), $this->view->idLista);
        $this->view->result = $item->getResultadoPaginator();
        $this->view->btn = $item->getNavePaginator();
        $this->view->resultLicitacao = $licitacao->findById_lista($this->view->idLista);
        $this->render('mostra_item_demanda');
    }

    protected function demanda($itemNumero, $idLicitacao)
    {
        return $this->solItem->quantidadeDemanda($itemNumero, $idLicitacao);
    }
}
