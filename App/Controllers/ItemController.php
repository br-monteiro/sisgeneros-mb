<?php
/**
 * @controller Item
 */
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\FornecedorModel as Fornecedor;
use App\Models\LicitacaoModel as Licitacao;

class ItemController extends Controller implements CtrlInterface
{

    // Model padrão usado para este Controller
    private $modelPath = 'App\Models\ItemModel';
    private $access;

    public function __construct()
    {
        parent::__construct();
        $this->view->controller = APPDIR . 'item/';
        // Instancia o Helper que auxilia na proteção e autenticação de usuários
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2]);

        $this->view->idLista = $this->getParametro('idlista');
        // Redireciona caso não seja fonecido o Id da Lista
        if (!$this->view->idLista) {
            header("Location:" . APPDIR . "licitacao/");
        }
    }

    public function indexAction()
    {
        $this->listarAction();
    }

    public function novoAction()
    {
        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Novo Registro';

        // Busca por todos os fornecedores no Banco de Dados
        $fornecedor = new Fornecedor();
        $this->view->resultFornecedor = $fornecedor->findAll();

        // Renderiza a página
        $this->render('form_novo');
    }

    public function editarAction()
    {
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();

        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Editando Registro';

        // Busca por todos os fornecedores no Banco de Dados
        $fornecedor = new Fornecedor();
        $this->view->resultFornecedor = $fornecedor->findAll();

        // Executa a consulta no Banco de Dados
        $this->view->result = $model->findById($this->getParametro('id'));

        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();
        $model->remover($this->getParametro('id'), $this->view->idLista);
    }

    public function listarAction()
    {
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();

        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Lista de Itens da Licitação';
        $model->paginator($this->getParametro('pagina'), $this->view->idLista);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();

        // Busca os dados da licitação
        $licitacao = new Licitacao();
        $this->view->resultLicitacao = $licitacao->findById_lista($this->view->idLista);

        $this->render('index');
    }

    public function registraAction()
    {
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();
        $model->novo();
    }

    public function alteraAction()
    {
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();
        $model->editar();
    }
}
