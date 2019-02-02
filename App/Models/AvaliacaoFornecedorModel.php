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
            'nao_entregue' => $this->getNaoEntregue()
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

    public function findBestBadSuppliers($orderBy = "DESC")
    {
        $query = ""
            . "SELECT "
            . "F.nome, sum(aval.nota) nota "
            . "FROM {$this->entidade} AS aval "
            . "INNER JOIN "
            . "fornecedor AS f ON f.id = aval.fornecedor_id "
            . "ORDER BY nota " . $orderBy;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
