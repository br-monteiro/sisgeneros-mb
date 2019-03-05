<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Paginator\Paginator;
use HTR\System\ControllerAbstract;
use App\Helpers\Utils;

class RelatorioModel extends CRUD
{

    protected $entidade = 'suppliers_evaluations';

    /**
     * @var HTR\Helpers\Paginator\Paginator 
     */
    private $paginador;

    public function paginatorDeliveryReport(ControllerAbstract $controller): self
    {
        $select = ""
            . " ev.*, supp.name AS suppliers_name, "
            . " lic.number AS biddings_number, "
            . " lic.uasg_name AS biddings_uasg_name, "
            . " sol.number AS requests_number, "
            . " oms.naval_indicative AS om_naval_indicative, "
            . " sol.created_at,"
            . " sol.status AS requests_status ";
        $innerJoin = ""
            . " AS ev "
            . " INNER JOIN suppliers AS supp "
            . "     ON supp.id = ev.suppliers_id "
            . " INNER JOIN biddings AS lic "
            . "     ON lic.id = ev.biddings_id "
            . " INNER JOIN requests AS sol "
            . "     ON sol.id = ev.requests_id "
            . " INNER JOIN oms "
            . "     ON oms.id = sol.oms_id ";
        $dados = [
            'entidade' => $this->entidade . $innerJoin,
            'select' => $select,
            'pagina' => $controller->getParametro('pagina'),
            'maxResult' => 500,
            'orderBy' => 'sol.created_at ASC',
            'bindValue' => []
        ];

        $params = $controller->getParametro();

        // search by Om
        if (isset($params['om']) && intval($params['om']) !== 0) {
            $dados['where'] = ' oms.id = :omId ';
            $dados['bindValue'][':omId'] = $params['om'];
        }

        // search by Fornecedor
        if (isset($params['suppliers']) && intval($params['suppliers']) !== 0) {
            if (isset($dados['where'])) {
                $dados['where'] .= ' AND ev.suppliers_id = :suppId ';
            } else {
                $dados['where'] = ' ev.suppliers_id = :suppId ';
            }
            $dados['bindValue'][':suppId'] = $params['suppliers'];
        }

        // search by Date Init
        if (isset($params['dateInit']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateInit'])) {
            $date = Utils::dateDatabaseFormate($params['dateInit']);

            if (isset($dados['where'])) {
                $dados['where'] .= ' AND sol.created_at >= :dateInit ';
            } else {
                $dados['where'] = ' sol.created_at >= :dateInit ';
            }
            $dados['bindValue'][':dateInit'] = $date;
        }

        // search by Date Init
        if (isset($params['dateEnd']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateEnd'])) {
            $date = Utils::dateDatabaseFormate($params['dateEnd']);

            if (isset($dados['where'])) {
                $dados['where'] .= ' AND sol.created_at <= :dateEnd ';
            } else {
                $dados['where'] = ' sol.created_at <= :dateEnd ';
            }
            $dados['bindValue'][':dateEnd'] = $date;
        }

        $this->paginador = new Paginator($dados);
        return $this;
    }

    public function getResultadoPaginator()
    {
        return $this->paginador->getResultado();
    }

    public function getNavePaginator()
    {
        return $this->paginador->getNaveBtn();
    }
}
