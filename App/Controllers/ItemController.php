<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\FornecedorModel as Fornecedor;
use App\Models\LicitacaoModel as Licitacao;
use App\Models\ItemModel;
use App\Config\Configurations as cfg;

class ItemController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = cfg::DEFAULT_URI . 'item/';
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
        $this->view->idLista = $this->getParametro('idlista');
        if (!$this->view->idLista) {
            header("Location:" . cfg::DEFAULT_URI . "licitacao/");
        }
    }

    public function indexAction()
    {
        $this->listarAction();
    }

    public function novoAction()
    {
        $this->view->title = 'Novo Registro';
        $fornecedor = new Fornecedor();
        $this->view->resultFornecedor = $fornecedor->findAll();
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $model = new ItemModel();
        $this->view->title = 'Editando Registro';
        $fornecedor = new Fornecedor();
        $this->view->resultFornecedor = $fornecedor->findAll();
        $this->view->result = $model->findById($this->getParametro('id'));
        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $model = new ItemModel();
        $model->remover($this->getParametro('id'), $this->view->idLista);
    }

    public function listarAction()
    {
        $model = new ItemModel();
        $this->view->title = 'Lista de Itens da Licitação';
        $model->paginator($this->getParametro('pagina'), $this->view->idLista);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $licitacao = new Licitacao();
        $this->view->resultLicitacao = $licitacao->findById_lista($this->view->idLista);
        $this->render('index');
    }

    public function registraAction()
    {
        $model = new ItemModel();
        $model->novo();
    }

    public function alteraAction()
    {
        $model = new ItemModel();
        $model->editar();
    }
}
