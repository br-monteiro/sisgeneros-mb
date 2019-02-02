<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\AvisosModel;
use App\Models\OmModel;
use App\Config\Configurations as cfg;

class AvisosController extends Controller implements CtrlInterface
{

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);

        $this->view->controller = cfg::DEFAULT_URI . 'avisos/';
        $this->view->userLoggedIn = (new Access())->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->title = 'Novo Registro';
        $this->view->resultOms = (new OmModel())->findAll();
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $this->view->title = 'Editando Registro';
        $result = (new AvisosModel())->fetchDataToEdit((int) $this->getParametro('id'));
        $this->view->result = $result['result'];
        $this->view->resultOms = $result['oms'];
        $this->render('form_editar');
    }

    public function verAction()
    {
        $model = new AvisosModel();
        $this->view->title = 'Lista de Todos os Avisos';
        $model->paginator($this->getParametro('pagina'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function eliminarAction()
    {
        (new AvisosModel())->removerRegistro($this->getParametro('id'));
    }

    public function registraAction()
    {
        (new AvisosModel())->novoRegistro($this->view->userLoggedIn);
    }

    public function alteraAction()
    {
        (new AvisosModel())->editarRegistro();
    }

    public function eliminaromAction()
    {
        $id = $this->getParametro('id');
        $avisoId = $this->getParametro('avisoid');
        if ($id && $avisoId) {
            (new AvisosModel())->eliminarOm((int) $id, (int) $avisoId);
        } else {
            header('Location: ' . $this->view->controller);
        }
    }

    public function adicionaromAction()
    {
        $id = $this->getParametro('id');
        if ($id) {
            $result = (new AvisosModel())->fetchOmOut((int) $id);
            if (count($result)) {
                $this->view->title = 'Adicionar nova OM';
                $this->view->result = $result;

                $this->render('form_adicionar_om');
            } else {
                header('Location: ' . $this->view->controller . 'editar/id/' . $id);
            }
        } else {
            header('Location: ' . $this->view->controller);
        }
    }
    
    public function registrarnovaomAction()
    {
        (new AvisosModel())->adicionarNovaOM();
    }
}
