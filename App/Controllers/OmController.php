<?php
/**
 * @controller Om
 */
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;

class OmController extends Controller implements CtrlInterface
{

    // Model padrão usado para este Controller
    private $modelPath = 'App\Models\OmModel';
    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);

        $this->view->controller = APPDIR . 'om/';
        // Instancia o Helper que auxilia na proteção e autenticação de usuários
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess([1, 2]);
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Novo Registro';

        // Renderiza a página
        $this->render('form_novo');
    }

    public function editarAction()
    {
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
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();

        $model->remover($this->getParametro('id'));
    }

    public function verAction()
    {
        // Instanciando o Model padrão usado.
        $model = new $this->modelPath();

        // Atribui título à página através do atributo padrão '$this->view->title'
        $this->view->title = 'Lista de Todos as OMs';

        $model->paginator($this->getParametro('pagina'));

        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();


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
