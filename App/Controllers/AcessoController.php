<?php
/**
 * @Controller Acesso
 */
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\OmModel;

class AcessoController extends Controller implements CtrlInterface
{

    // Model padrão usado para este Controller
    private $modelPath = 'App\Models\AcessoModel';
    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        /**
         * Nome do controller
         * USADO NOS LINKS DA CAMA VIEW
         * Exemplo
         *
         * Controller
         * $this->view->controller = APPDIR.'teste/'
         * 
         * View
         * <a href='<?=$this->view->controller;?>novo' > Novo</a>
         * 
         * Browser
         * <a href='/teste/novo' > Novo</a>
         */
        $this->view->controller = APPDIR . 'acesso/';
        // Instancia o Helper que auxilia na proteção e autenticação de usuários
        $this->access = new Access();
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        // Inicia a proteção das páginas com permissão de acesso apenas para
        // usuários autenticados com o nível 1 e 2.
        $this->view->userLoggedIn = $this->access->authenticAccess([1]);
        $om = new OmModel();
        $this->view->resultOm = $om->findAll();
        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Novo Registro';

        // Renderiza a página
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2, 3, 4]);
        $om = new OmModel();
        $this->view->resultOm = $om->findAll();
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();

        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Editando Registro';

        $id = $this->view->userLoggedIn['nivel'] > 1 ? $this->view->userLoggedIn['nivel'] : $this->getParametro('id');
        // Executa a consulta no Banco de Dados
        $this->view->result = $model->findById($id);

        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $this->access->authenticAccess([1]);

        $model = new $this->modelPath();

        $model->remover($this->getParametro('id'));
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([1]);
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();

        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Lista de Todos os Usuários';

        $model->paginator($this->getParametro('pagina'));

        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();

        $this->render('index');
    }

    public function registraAction()
    {
        // Inicia a proteção das páginas com permissão de acesso apenas para
        // usuários autenticados com o nível 1 e 2.
        $this->access->authenticAccess([1]);
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();
        $model->novo();
    }

    public function alteraAction()
    {
        $this->access->authenticAccess([1, 2, 3, 4]);
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();
        $model->editar();
    }

    public function loginAction()
    {
        // evita o relogin no sistema
        $this->access->notAuthenticatedAccess();
        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Autenticação de Usuário';

        $this->render('form_login', true, 'blank');
    }

    public function logoutAction()
    {
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();
        $model->logout();
        header('Location:' . APPDIR);
    }

    public function autenticaAction()
    {
        // Instanciando o Model padrão usado.
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
        $model = new $this->modelPath();
        $this->access->breakRedirect();
        $user = $this->access->authenticAccess([1, 2, 3, 4]);
        // Instanciando o Model padrão usado.
        $model->mudarSenha($user['id']);
    }
}
