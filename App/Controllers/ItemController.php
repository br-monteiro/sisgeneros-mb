<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\FornecedorModel as Fornecedor;
use App\Models\LicitacaoModel as Licitacao;
use App\Models\ItemModel;
use App\Models\IngredientesModel;
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
        $this->view->idlista = $this->getParametro('idlista');
        if (!$this->view->idlista) {
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
        $this->view->resultIngredients = (new IngredientesModel())->findAll(function ($i) {
            return $i->setaFiltros()
                    ->orderBY('name ASC');
        });
        $this->view->resultFornecedor = $fornecedor->findAll();
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $model = new ItemModel();
        $this->view->title = 'Editando Registro';
        $fornecedor = new Fornecedor();
        $this->view->resultFornecedor = $fornecedor->findAll();
        $this->view->resultIngredients = (new IngredientesModel())->findAll(function ($i) {
            return $i->setaFiltros()
                    ->orderBY('name ASC');
        });
        $this->view->result = $model->findById($this->getParametro('id'));
        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $model = new ItemModel();
        $model->removerRegistro($this->getParametro('id'), $this->view->idlista);
    }

    public function listarAction()
    {
        $model = new ItemModel();
        $this->view->title = 'Lista de Itens da Licitação';
        $model->paginator($this->getParametro('pagina'), $this->view->idlista);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $licitacao = new Licitacao();
        $this->view->resultLicitacao = $licitacao->findById($this->view->idlista);
        $this->render('index');
    }

    public function registraAction()
    {
        $model = new ItemModel();
        $model->novoRegistro();
    }

    public function alteraAction()
    {
        $model = new ItemModel();
        $model->editarRegistro();
    }
}
