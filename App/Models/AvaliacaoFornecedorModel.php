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

    public function novo($value)
    {
        // Valida dados
        $this->validaAll($value);

        $dados = [
            'fornecedor_id' => $this->getFornecedorId(),
            'licitacao_id' => $this->getLicitacaoId(),
            'nota' => $this->getNota(),
            'nao_entregue' => $this->getNaoEntregue()
        ];
        if (parent::novo($dados)) {
            return true;
        }
    }

    private function validaAll($value)
    {
        // Seta todos os valores
        $this->setNota($value['nota'])
            ->setFornecedorId($value['fornecedor_id'])
            ->setLicitacaoId($value['licitacao_id'])
            ->setNaoEntregue($value['nao_entregue']);
    }
}
