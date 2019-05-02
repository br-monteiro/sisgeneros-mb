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

    public function novoRegistro($dados)
    {
        $result = $this->findByRequests_id($dados['requests_id'] ?? 0);

        if ($result) {
            parent::editar($dados, $result['id']);
        } else {
            parent::novo($dados);
        }
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
            . " ORDER BY evaluation DESC, supp.name ASC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
