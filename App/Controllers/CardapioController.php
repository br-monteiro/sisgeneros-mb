<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\CardapioModel;
use App\Models\OmModel;
use App\Models\MealsModel;
use App\Models\RecipesPatternsModel;
use App\Config\Configurations as cfg;

class CardapioController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);

        $this->view->controller = cfg::DEFAULT_URI . 'cardapio/';
        $this->access = new Access();
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
        $this->view->title = 'Novo Registro';
        $modelOms = new OmModel();
        $modelMeals = new MealsModel();
        $modelRecipes = new RecipesPatternsModel();
        $this->view->resultOms = $modelOms->findAll();
        $this->view->resultMeals = $modelMeals->findAll();
        $this->view->resultRecipes = $modelRecipes->findAll();
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
        $model = new CardapioModel();
        $this->view->title = 'Editando Registro';
        $this->view->result = $model->findById($this->getParametro('id'));
        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
        $model = new CardapioModel();
        $model->removerRegistro($this->getParametro('id'));
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
        $model = new CardapioModel();
        $this->view->title = 'Lista de Todos os Cardápios';
        $model->paginator($this->getParametro('pagina'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function registraAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
        $model = new CardapioModel();
        $model->novoRegistro($user);
    }

    public function alteraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
        $model = new CardapioModel();
        $model->editarRegistro();
    }

    public function checkdateAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ENCARREGADO', 'NORMAL']);
        $omId = intval($user['oms_id'] ?? 0);
        $result = (new CardapioModel())->checkDate($this->getParametro('value'), $omId);
        echo json_encode($result);
    }
}
