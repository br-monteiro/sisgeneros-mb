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
        $this->view->idlista = $this->getParametro('idlista');
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function licitacaoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $licitacao = new Licitacao();
        $this->view->title = 'Licitações Disponíveis';
        $licitacao->paginator($this->getParametro('pagina'), time());
        $this->view->result = $licitacao->getResultadoPaginator();
        $this->view->btn = $licitacao->getNavePaginator();
        $this->render('mostra_licitacao_disponivel');
    }

    public function formnaolicitadoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $this->view->title = "Adicionar itens";
        $this->view->resultFornecedor = (new FornecedorModel())->findAll(function($e) {
            return $e->setaCampos(['id', 'name', 'cnpj'])
                    ->setaFiltros()
                    ->orderBy('suppliers.name ASC');
        });
        $this->render('mostra_item_nao_licitado');
    }

    public function itemAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $this->view->title = 'Lista dos Itens da Licitação';
        $item = new Item();
        $this->view->result = $item->findByIdlista($this->view->idlista, $this->getParametro('fornecedor'));
        $licitacao = new Licitacao();
        $this->view->resultLicitacao = $licitacao->findById($this->view->idlista);
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
        $model->avaliaAcesso($this->view->idlista, $this->view->userLoggedIn);
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
        $this->view->title = 'Alterando data de entrega';
        $this->view->result = $model->avaliaAcesso($this->getParametro('id'), $this->view->userLoggedIn);
        $this->render('form_alteracao_data');
    }

    public function itensLicitadosAction()
    {
        $this->view->busca = $this->getParametro('busca');

        if ($this->view->busca) {
            $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
                ->clearAccessList()
                ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

            $licitacao = new Licitacao();
            $this->view->title = 'Forncedores e itens encontrados';
            $this->view->result = $licitacao->listaItemFornecedor($this->getParametro('busca'));
            $this->render('mostra_item_buscado');
        } else {
            $this->licitacaobuscaAction();
        }
    }

    public function eliminarAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $model = new SolicitacaoModel();
        $solicitacaoItem = new SolicitacaoItem();
        $model->avaliaAcesso($this->getParametro('id'), $this->view->userLoggedIn);
        if ($solicitacaoItem->removerRegistro($this->getParametro('id'))) {
            $model->removerRegistro($this->getParametro('id'));
        }
    }

    public function eliminarItemAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $model = new SolicitacaoModel();
        $model->avaliaAcesso($this->view->idlista, $this->view->userLoggedIn);
        $solicitacaoItem = new SolicitacaoItem();
        $solicitacaoItem->eliminarItem($this->getParametro('id'), $this->view->idlista);
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
        $this->view->resultSolicitacao = $model->findByIdlista($this->view->idlista);
        $this->view->resultLicitacao = $licitacao->findById($this->view->resultSolicitacao['biddings_id']);
        $solicitacaoItem->paginator($this->getParametro('pagina'), $this->view->idlista);
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
        $model->novoRegistro($this->view->userLoggedIn['oms_id']);
    }

    public function registranaolicitadoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        (new SolicitacaoModel())->novoNaoLicitado($this->view->userLoggedIn['oms_id'], getcwd());
    }

    public function alteraAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $solicitacaoItem = new SolicitacaoItem();
        $solicitacaoItem->editarRegistro($this->view->idlista, $this->view->userLoggedIn);
    }

    public function alterandodataAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);

        $solicitacao = new SolicitacaoModel();
        $solicitacao->alteraDeliveryDate($this->getParametro('id'), $this->view->userLoggedIn);
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

        $this->view->result = $licitacao->listaPorFornecedor($this->view->idlista);

        if (!$this->view->result) {
            header('Location: ' . $this->view->controller);
        }

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

        if ($resultSolicitacao['biddings_id']) {
            $solicitacao->recebimento($resultSolicitacao['id']);
        } else {
            $solicitacao->recebimentoNaoLicitado($resultSolicitacao['id']);
        }
    }

    public function processarAction()
    {
        $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);

        $id = (int) $this->getParametro('id');
        $status = strtoupper($this->getParametro('status') ?? '');
        $action = strtoupper($this->getParametro('acao') ?? '');

        (new SolicitacaoModel())->processStatus($id, $status, $action);

        if ($status == 'APROVADO' && $action == 'PROXIMO') {
            $solicitacao = (new SolicitacaoModel())->findById($id);
            header('location: '
                . $this->view->controller
                . 'detalhar/idlista/' . $solicitacao['id']);
        } else {
            header('location: ' . $this->view->controller);
        }
    }

    public function presolempAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);

        $model = new SolicitacaoModel();
        $licitacao = new Licitacao();
        $solicitacaoItem = new SolicitacaoItem();
        $this->view->resultSolicitacao = $model->findByIdlista($this->view->idlista);
        $this->view->resultLicitacao = $licitacao->findById($this->view->resultSolicitacao['biddings_id']);
        $solicitacaoItem->paginator($this->getParametro('pagina'), $this->view->idlista);
        $this->view->result = $solicitacaoItem->getResultadoPaginator();
        $this->view->btn = $solicitacaoItem->getNavePaginator();
        $this->render('papeleta_presolemp', true, 'blank');
    }

    public function eliminararquivoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'NORMAL']);

        $file = $this->getParametro('file');
        $solicitacao = (new SolicitacaoModel())->findByIdlista($this->view->idlista);
        $number = $solicitacao['number'] ?? 'error';
        $fullPath = getcwd() . cfg::DS . 'arquivos' . cfg::DS . $number . cfg::DS . $file;
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        header("Location: {$this->view->controller}detalhar/idlista/{$this->view->idlista}");
    }

    public function adicionararquivoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'NORMAL']);

        $this->view->title = 'Adicionar novo arquivo';
        $this->render('form_adicionar_arquivo');
    }

    public function salvararquivoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'NORMAL']);

        $solicitacaoModel = new SolicitacaoModel();
        $solicitacao = $solicitacaoModel->findByIdlista($this->view->idlista);
        $number = $solicitacao['number'] ?? 'error';
        if ($number !== 'error') {
            $solicitacaoModel->saveOneFile(getcwd(), $number);
        }
    }

    public function licitacaobuscaAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'NORMAL']);

        $this->view->title = 'Busca de itens licitados';
        $this->render('mostra_busca_fornecedor');
    }
}
