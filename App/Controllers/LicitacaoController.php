<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\LicitacaoModel;
use App\Config\Configurations as cfg;

class LicitacaoController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = cfg::DEFAULT_URI . 'licitacao/';
        $this->access = new Access();
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
        $this->view->title = 'Novo Registro';
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
        $model = new LicitacaoModel();
        $this->view->title = 'Editando Registro';
        $this->view->result = $model->findById($this->getParametro('id'));
        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
        $model = new LicitacaoModel();
        $model->removerRegistro($this->getParametro('id'));
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
        $model = new LicitacaoModel();
        $this->view->title = 'Lista de Todas as Licitações';
        $model->paginator($this->getParametro('pagina'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function registraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
        $model = new LicitacaoModel();
        $model->novoRegistro();
    }

    public function alteraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
        $model = new LicitacaoModel();
        $model->editarRegistro();
    }

    public function ingrendientesAction()
    {
        $result = (new LicitacaoModel())->findAllByIngredientsId(intval($this->getParametro('id')));
        echo json_encode($result);
    }
}
