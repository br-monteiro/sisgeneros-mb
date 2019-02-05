<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Paginator\Paginator;
use HTR\System\ControllerAbstract;

class RelatorioModel extends CRUD
{

    protected $entidade = 'avaliacao_fornecedor';

    /**
     * @var HTR\Helpers\Paginator\Paginator 
     */
    private $paginador;

    public function paginatorDeliveryReport(ControllerAbstract $controller): self
    {
        $select = ""
            . " af.*, forn.nome AS fornecedor_nome, "
            . " lic.numero AS licitacao_numero, "
            . " lic.nome_uasg AS licitacao_uasg_nome, "
            . " sol.numero AS solicitacao_numero, "
            . " om.indicativo_naval AS om_indicativo_naval, "
            . " sol.created_at AS criacao,"
            . " sol.status AS solicitacao_status ";
        $innerJoin = ""
            . " AS af "
            . " INNER JOIN fornecedor AS forn "
            . "     ON forn.id = af.fornecedor_id "
            . " INNER JOIN licitacao AS lic "
            . "     ON lic.id = af.licitacao_id "
            . " INNER JOIN solicitacao AS sol "
            . "     ON sol.id = af.solicitacao_id "
            . " INNER JOIN om "
            . "     ON om.id = sol.om_id ";
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
            $dados['where'] = ' om.id = :omId ';
            $dados['bindValue'][':omId'] = $params['om'];
        }

        // search by Fornecedor
        if (isset($params['fornecedor']) && intval($params['fornecedor']) !== 0) {
            if (isset($dados['where'])) {
                $dados['where'] .= ' AND af.fornecedor_id = :fornId ';
            } else {
                $dados['where'] = ' af.fornecedor_id = :fornId ';
            }
            $dados['bindValue'][':fornId'] = $params['fornecedor'];
        }

        // search by Date Init
        if (isset($params['dateInit']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateInit'])) {
            $exDate = explode('-', $params['dateInit']);
            $exDate = array_reverse($exDate);
            $exDate = implode('-', $exDate);
            $exDate .= 'T00:00:00+00:00';
            $date = new \DateTime($exDate);

            if (isset($dados['where'])) {
                $dados['where'] .= ' AND sol.created_at >= :dateInit ';
            } else {
                $dados['where'] = ' sol.created_at >= :dateInit ';
            }
            $dados['bindValue'][':dateInit'] = $date->getTimestamp();
        }

        // search by Date Init
        if (isset($params['dateEnd']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateEnd'])) {
            $exDate = explode('-', $params['dateEnd']);
            $exDate = array_reverse($exDate);
            $exDate = implode('-', $exDate);
            $exDate .= 'T23:59:00+00:00';
            $date = new \DateTime($exDate);

            if (isset($dados['where'])) {
                $dados['where'] .= ' AND sol.created_at <= :dateEnd ';
            } else {
                $dados['where'] = ' sol.created_at <= :dateEnd ';
            }
            $dados['bindValue'][':dateEnd'] = $date->getTimestamp();
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
