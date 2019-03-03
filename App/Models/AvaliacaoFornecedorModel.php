<?php

namespace App\Models;

use HTR\System\ModelCRUD as CRUD;

class AvaliacaoFornecedorModel extends CRUD
{

    protected $entidade = 'suppliers_evaluations';

    public function returnAll()
    {
        return $this->findAll();
    }

    public function novoRegistro($value)
    {
        $this->validaAll($value);

        $dados = [
            'evaluation' => $this->getEvaluation(),
            'requests_id' => $this->getRequestsId()
        ];

        parent::novo($dados);
    }

    private function validaAll($value)
    {
        // Seta todos os valores
        $this->setEvaluantion($value['evaluation'])
            ->setRequestsId($value['requests_id']);
    }

    public function findBestBadSuppliers()
    {
        $query = ""
            . " SELECT "
            . "     AVG(se.evaluation) AS evaluation,"
            . "     supp.name "
            . " FROM {$this->entidade} AS se "
            . " INNER JOIN "
            . "     requests AS req "
            . "     ON req.id = se.requests_id "
            . " INNER JOIN "
            . "     suppliers AS supp "
            . "     ON supp.id = req.suppliers_id "
            . " GROUP BY req.suppliers_id "
            . " ORDER BY evaluation DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
