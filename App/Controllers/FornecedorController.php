<?php
/**
 * @controller Fornecedor
 */
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;

class FornecedorController extends Controller implements CtrlInterface
{

    // Model padrão usado para este Controller
    private $modelPath = 'App\Models\FornecedorModel';
    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);

        $this->view->controller = APPDIR . 'fornecedor/';
        // Instancia o Helper que auxilia na proteção e autenticação de usuários
        $this->access = new Access();
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2, 3, 4]);
        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Novo Registro';

        // Renderiza a página
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2]);
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();

        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Editando Registro';

        // Executa a consulta no Banco de Dados
        $this->view->result = $model->findById($this->getParametro('id'));

        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2]);
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();

        $model->remover($this->getParametro('id'));
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2]);
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();

        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Lista de Todos os Fornecedores';

        $model->paginator($this->getParametro('pagina'));

        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();

        $this->render('index');
    }

    public function registraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2, 3, 4]);
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();
        $model->novo();
    }

    public function alteraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2]);
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();
        $model->editar();
    }
}
