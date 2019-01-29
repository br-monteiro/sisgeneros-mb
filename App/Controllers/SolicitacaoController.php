<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\LicitacaoModel as Licitacao;
use App\Models\ItemModel as Item;
use App\Models\SolicitacaoItemModel as SolicitacaoItem;
use App\Models\SolicitacaoModel;
use App\Models\FornecedorModel;
use App\Helpers\Pdf;
use App\Config\Configurations as cfg;

class SolicitacaoController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = cfg::DEFAULT_URI . 'solicitacao/';
        $this->access = new Access();
        $this->view->idLista = $this->getParametro('idlista');
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function licitacaoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO']);

        $licitacao = new Licitacao();
        $this->view->title = 'Licitações Disponíveis';
        $licitacao->paginator($this->getParametro('pagina'), time());
        $this->view->result = $licitacao->getResultadoPaginator();
        $this->view->btn = $licitacao->getNavePaginator();
        $this->render('mostra_licitacao_disponivel');
    }

    public function naolicitadoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $this->view->title = "Solicitação - Não Licitado";
        $fornecedorModel = new FornecedorModel();
        $fornecedorModel->paginator($this->getParametro('pagina'));
        $this->view->result = $fornecedorModel->getResultadoPaginator();
        $this->view->btn = $fornecedorModel->getNavePaginator();
        $this->render('form_nao_licitado');
    }

    public function formnaolicitadoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $this->view->title = "Adicionar itens";
        $this->view->idFornecedor = $this->getParametro('id');
        $this->render('mostra_item_nao_licitado');
    }

    public function itemAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $this->view->title = 'Lista dos Itens da Licitação';
        $item = new Item();
        $this->view->result = $item->findByIdLista($this->view->idLista, $this->getParametro('fornecedor'));
        $licitacao = new Licitacao();
        $this->view->resultLicitacao = $licitacao->findById_lista($this->view->idLista);
        $this->render('mostra_item');
    }

    public function receberAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $this->view->title = 'Lista de itens solicitados';
        $solicitacao = new SolicitacaoModel();
        $this->view->result = $solicitacao->retornaDadosPapeleta($this->getParametro('id'), $this->view->userLoggedIn, true);
        $this->render('mostra_item_recebimento');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $model = new SolicitacaoModel();
        $solicitacaoItem = new SolicitacaoItem();
        $model->avaliaAcesso($this->view->idLista, $this->view->userLoggedIn);
        $this->view->title = 'Editando Registro';
        $this->view->result = $solicitacaoItem->findById($this->getParametro('id'));
        $this->render('form_editar');
    }

    public function alterardataAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $model = new SolicitacaoModel();
        $model->avaliaAcesso($this->view->idLista, $this->view->userLoggedIn);
        $this->view->title = 'Alterando data de entrega';
        $this->view->result = $model->findById($this->getParametro('id'));
        $this->render('form_alteracao_data');
    }

    public function eliminarAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $model = new SolicitacaoModel();
        $solicitacaoItem = new SolicitacaoItem();
        $model->avaliaAcesso($this->view->idLista, $this->view->userLoggedIn);
        if ($solicitacaoItem->remover($this->getParametro('id'))) {
            $model->remover($this->getParametro('id'));
        }
    }

    public function eliminarItemAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $model = new SolicitacaoModel();
        $model->avaliaAcesso($this->view->idLista, $this->view->userLoggedIn);
        $solicitacaoItem = new SolicitacaoItem();
        $solicitacaoItem->eliminarItem($this->getParametro('id'), $this->view->idLista);
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
        $model = new SolicitacaoModel();
        $this->view->title = 'Histórico de Solicitações';
        $model->paginator($this->getParametro('pagina'), $this->view->userLoggedIn, $this->getParametro('busca'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function detalharAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
        $model = new SolicitacaoModel();
        $licitacao = new Licitacao();
        $solicitacaoItem = new SolicitacaoItem();
        $this->view->title = 'Itens da Solicitação';
        $this->view->resultSolicitacao = $model->findById_lista($this->view->idLista);
        $this->view->resultLicitacao = $licitacao->findById($this->view->resultSolicitacao['id_licitacao']);
        $solicitacaoItem->paginator($this->getParametro('pagina'), $this->view->idLista);
        $this->view->result = $solicitacaoItem->getResultadoPaginator();
        $this->view->btn = $solicitacaoItem->getNavePaginator();
        $this->render('mostra_item_solicitacao');
    }

    public function registraAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $model = new SolicitacaoModel();
        $model->novo($this->view->userLoggedIn['om_id']);
    }

    public function registranaolicitadoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $model = new SolicitacaoModel();
        $model->novoNaoLicitado($this->view->userLoggedIn['om_id']);
    }

    public function alteraAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $solicitacaoItem = new SolicitacaoItem();
        $solicitacaoItem->editar($this->view->idLista, $this->view->userLoggedIn);
    }

    public function alterandodataAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $solicitacao = new SolicitacaoModel();
        $solicitacao->alteraDataEntrega($this->view->idLista, $this->view->userLoggedIn);
    }

    public function aprovarAction()
    {
        $this->access->setRedirect('solicitacao/');
        $this->access->clearAccessList();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO']);
        $model = new SolicitacaoModel();
        $model->aprovar($this->getParametro('id'));
    }

    public function fornecedorAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
        $licitacao = new Licitacao;

        $this->view->result = $licitacao->listaPorFornecedor($this->view->idLista);
        $this->view->title = 'Lista de fornecedor';
        $this->render('mostra_fornecedor');
    }

    public function pdfAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $id = $this->getParametro('id');
        $pdf = new Pdf();
        $pdf->number = $id;
        $pdf->url = $this->view->controller . 'papeleta/id/' . $id;
        $pdf->gerar();
    }

    public function papeletaAction()
    {
        $model = new SolicitacaoModel();
        $this->view->title = 'Solicitação de Material';
        $this->view->result = $model->retornaDadosPapeleta($this->getParametro('id'));
        $this->render('papeleta_solicitacao', true, 'blank');
    }

    public function registrarrecebimentoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $solicitacao = new SolicitacaoModel();
        $resultSolicitacao = $solicitacao->findById($this->getParametro('id'));
        if ($resultSolicitacao['nao_licitado'] == 1) {
            $solicitacao->recbimentoNaoLicitado($this->getParametro('id'), $resultSolicitacao['id_lista']);
        }
        $solicitacao->recebimento($this->getParametro('id'), $this->view->userLoggedIn);
    }

    public function processarAction()
    {
        $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);

        $id = (int) $this->getParametro('id');
        $status = strtoupper($this->getParametro('status') ?? '');

        (new SolicitacaoModel())->processarStatus($id, $status);

        header('location: ' . $this->view->controller);
    }
}
