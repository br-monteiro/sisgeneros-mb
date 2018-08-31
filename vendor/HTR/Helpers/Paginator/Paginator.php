<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Paginador.php
 * @version 0.4
 * - Helper que auxilia no gerenciamento de página onde há a necessidade do uso de Links de paginação
 */
namespace HTR\Helpers\Paginator;

use HTR\System\ModelCRUD as CRUD;

class Paginator extends CRUD
{

    protected $entidade;
    private $pagina;
    private $totalResult;
    private $totalPagina;
    private $maxResult;
    private $select;
    private $btn;
    private $resultado;
    private $maxOffSet;
    private $where;
    private $orderBy;
    private $bindValue;

    public function __construct(Array $dados)
    {
        /*
         * DEFAULT
         *  [
         *      'entidade' => tabela do Banco de Dados
         *      'pagina' => Página Corrente
         *      'maxResult' => Número Máximo de resultados por Página
         *      'where' => regras de consulta ao Banco de Dados
         *      'bindValue' => valor que complementam a consulta
         *      'select' => indica quais campos serão selecionados. DEFAULT = *
         *  ]
         */
        parent::__construct();

        $this->setEntidade($dados['entidade'])
            ->setWhere(isset($dados['where']) ? $dados['where'] : null)
            ->setOrderBy(isset($dados['orderBy']) ? $dados['orderBy'] : null)
            ->setBindValue(isset($dados['bindValue']) ? $dados['bindValue'] : null)
            ->setMaxResult(isset($dados['maxResult']) ? $dados['maxResult'] : null)
            ->setSelect(isset($dados['select']) ? $dados['select'] : null)
            ->setTotalResult()
            ->setPagina(isset($dados['pagina']) ? $dados['pagina'] : 1)
            ->setTotalPagina()
            ->setMaxOffSet()
            ->paginator()
            ->setBtn();
    }

    private function setEntidade($entidade)
    {
        $this->entidade = $entidade;
        return $this;
    }

    private function getEntidade()
    {
        return $this->entidade;
    }

    private function setPagina($pagina)
    {
        // Verifica se foi indicada algum número de página,
        // caso ainda não tenha sido setado um valor, por padrão
        // retornará o valor '1'
        $this->pagina = isset($pagina) ? $pagina : 1;
        // Verifica se o valor passado é numérico
        if (!is_numeric($this->pagina)) {
            // caso não seja, seta o valor '1' ao atributo $this->pagina
            $this->pagina = 1;
        } elseif ($this->getPagina() > $this->getTotalResult()) {
            $this->pagina = 1;
        }
        return $this;
    }

    private function getPagina()
    {
        return $this->pagina;
    }

    private function setMaxResult($valor = null)
    {
        $this->maxResult = isset($valor) ? $valor : 20;
        // Verifica se o valor passado é numérico
        if (!is_numeric($this->maxResult)) {
            // caso não seja, seta o valor '20' ao atributo $this->maxResult
            $this->maxResult = 20;
        }
        return $this;
    }

    private function getMaxResult()
    {
        return $this->maxResult;
    }

    private function setSelect($valor = null)
    {
        $this->select = isset($valor) ? $valor : '*';
        return $this;
    }

    private function getSelect()
    {
        return $this->select;
    }

    private function setTotalResult()
    {
        //SQL query
        $sql = "SELECT {$this->getSelect()} FROM {$this->getEntidade()} "
            . "{$this->getWhere()} "
            . "{$this->getOrderBy()} ;";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->getBindValue());
        $this->totalResult = count($stmt->fetchAll(\PDO::FETCH_ASSOC));
        return $this;
    }

    private function getTotalResult()
    {
        return $this->totalResult;
    }

    private function setTotalPagina()
    {
        $this->totalPagina = ceil($this->getTotalResult() / $this->getMaxResult());
        return $this;
    }

    private function getTotalPagina()
    {
        return $this->totalPagina;
    }

    private function setMaxOffSet()
    {
        $maxOffSet = ($this->getMaxResult() * $this->getPagina()) - $this->getMaxResult();
        if ($maxOffSet >= $this->getTotalResult()) {
            $this->maxOffSet = $this->getTotalResult() - 1;
        } else {
            $this->maxOffSet = $maxOffSet;
        }
        return $this;
    }

    private function getMaxOffSet()
    {
        return $this->maxOffSet;
    }

    private function setOrderBy($orderBy = null)
    {
        $this->orderBy = ($orderBy) ? ' ORDER BY ' . $orderBy : null;
        return $this;
    }

    private function getOrderBy()
    {
        return $this->orderBy;
    }

    private function setWhere($where = null)
    {
        $this->where = ($where) ? ' WHERE ' . $where : null;
        return $this;
    }

    private function getWhere()
    {
        return $this->where;
    }

    private function setBindValue($bindValue = null)
    {
        $this->bindValue = $bindValue;
        return $this;
    }

    private function getBindValue()
    {
        return $this->bindValue = is_array($this->bindValue) ? $this->bindValue : [];
    }

    private function setBtn()
    {
        for ($i = 1; $i < $this->getTotalPagina() + 1; $i++) {
            $this->btn[] = $i;
        }
        return $this;
    }

    private function getBtn()
    {
        return $this->btn;
    }

    private function makeBtn()
    {
        $btn['link'] = $this->getBtn();
        $btn['previus'] = 1;
        $btn['next'] = $this->getTotalPagina();
        return $btn;
    }

    public function getNaveBtn()
    {
        if (!$this->getTotalResult()) {
            // esconde os links de navegação da paginação
            echo '<style>.pagination{display:none !important;}</style>';
            return [
                'link' => [],
                'previus' => '#',
                'next' => '#'
            ];
        }
        return $this->btn = $this->makeBtn();
    }

    private function setResultado($resultado)
    {
        $this->resultado = $resultado;
        return $this;
    }

    public function getResultado()
    {
        return $this->resultado;
    }

    private function paginator()
    {
        //SQL query
        $sql = "SELECT {$this->getSelect()} FROM {$this->getEntidade()} "
            . "{$this->getWhere()} "
            . "{$this->getOrderBy()} "
            . "LIMIT {$this->getMaxOffSet()},{$this->getMaxResult()} ;";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->getBindValue());
        $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->setResultado($resultado);
        return $this;
    }
}
