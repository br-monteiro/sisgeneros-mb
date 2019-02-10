<?php
/**
 * @Model Om
 */
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Paginator\Paginator;

class AvaliacaoFornecedorModel extends CRUD
{

    protected $entidade = 'avaliacao_fornecedor';
    protected $id;
    protected $fornecedorId;
    protected $licitacaoId;
    protected $nota;
    protected $naoEntregue;
    protected $resultadoPaginator;
    protected $navPaginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 10,
            'orderBy' => 'nome ASC'
            //'where' => 'nome LIKE ? ORDER BY nome',
            //'bindValue' => [0 => '%MONTEIRO%']
        ];

        $paginator = new Paginator($dados);
        $this->resultadoPaginator = $paginator->getResultado();
        $this->navPaginator = $paginator->getNaveBtn();
    }

    public function getResultadoPaginator()
    {
        return $this->resultadoPaginator;
    }

    public function getNavePaginator()
    {
        return $this->navPaginator;
    }

    public function novoRegistro($value)
    {
        $this->validaAll($value);

        $dados = [
            'fornecedor_id' => $this->getFornecedorId(),
            'licitacao_id' => $this->getLicitacaoId(),
            'nota' => $this->getNota(),
            'nao_entregue' => $this->getNaoEntregue(),
            'solicitacao_id' => $value['solicitacao_id']
        ];

        parent::novo($dados);
    }

    private function validaAll($value)
    {
        // Seta todos os valores
        $this->setNota($value['nota'])
            ->setFornecedorId($value['fornecedor_id'])
            ->setLicitacaoId($value['licitacao_id'])
            ->setNaoEntregue($value['nao_entregue']);
    }

    public function findBestBadSuppliers()
    {
        $query = ""
            . " SELECT "
            . "     AVG(af.nota) AS nota,"
            . "     forn.nome "
            . " FROM {$this->entidade} AS af "
            . " INNER JOIN "
            . "     fornecedor AS forn "
            . "     ON forn.id = af.fornecedor_id "
            . " GROUP BY fornecedor_id"
            . " ORDER BY nota DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
