<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\OmModel;
use App\Models\AcessoModel;

class AcessoController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = APPDIR . 'acesso/';
        $this->access = new Access();
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1]);
        $om = new OmModel();
        $this->view->resultOm = $om->findAll();
        $this->view->title = 'Novo Registro';
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2, 3, 4]);
        $om = new OmModel();
        $this->view->resultOm = $om->findAll();
        $model = new AcessoModel();
        $this->view->title = 'Editando Registro';
        $id = $this->view->userLoggedIn['nivel'] > 1 ? $this->view->userLoggedIn['nivel'] : $this->getParametro('id');
        $this->view->result = $model->findById($id);
        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $this->access->authenticAccess([1]);
        $model = new AcessoModel();
        $model->remover($this->getParametro('id'));
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1]);
        $model = new AcessoModel();
        $this->view->title = 'Lista de Todos os Usuários';
        $model->paginator($this->getParametro('pagina'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function registraAction()
    {
        $this->access->authenticAccess([1]);
        $model = new AcessoModel();
        $model->novo();
    }

    public function alteraAction()
    {
        $this->access->authenticAccess([1, 2, 3, 4]);
        // Instanciando o Model padrão usado.
        $model = new AcessoModel();
        $model->editar();
    }

    public function loginAction()
    {
        $this->access->notAuthenticatedAccess();
        $this->view->title = 'Autenticação de Usuário';
        $this->render('form_login', true, 'blank');
    }

    public function logoutAction()
    {
        $model = new AcessoModel();
        $model->logout();
        header('Location:' . APPDIR);
    }

    public function autenticaAction()
    {
        $model = new $this->modelPath;
        $model->login();
    }

    public function mudarSenhaAction()
    {
        $this->access->breakRedirect();
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2, 3, 4]);
        $this->view->title = "Mudano Senha";
        $this->render('form_mudar_senha');
    }

    public function mudandoSenhaAction()
    {
        $model = new AcessoModel();
        $this->access->breakRedirect();
        $user = $this->access->authenticAccess([1, 2, 3, 4]);
        $model->mudarSenha($user['id']);
    }
}
