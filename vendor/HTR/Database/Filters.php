<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Filters.php
 * @version 0.1
 * - Class que gerencia os filtros e regras das consultas SQL
 */
namespace HTR\Database;

final class Filters
{

    private $sql;
    private $bind;

    public function where($coluna, $op, $valor)
    {
        $this->setaBind($coluna, $valor);
        $this->sql['where'][] = $coluna . $op . ':' . $coluna;
        return $this;
    }

    public function whereOperador($op)
    {
        $this->sql['where'][] = $op;
        return $this;
    }

    public function limit($limit)
    {
        $this->sql['limit'] = $limit;
        return $this;
    }

    public function orderBy($order)
    {
        $this->sql['order'] = $order;
        return $this;
    }

    public function retornaSql()
    {
        $sql = [];
        if (!empty($this->sql['where'])) {
            $sql_string = 'WHERE ';
            $sql_string .= implode(' ', $this->sql['where']);
            $sql[] = $sql_string;
        }

        if (!empty($this->sql['order'])) {
            $sql_string = 'ORDER BY ' . $this->sql['order'];
            $sql[] = $sql_string;
        }

        if (!empty($this->sql['limit'])) {
            $sql_string = 'LIMIT ' . $this->sql['limit'];
            $sql[] = $sql_string;
        }

        return implode(' ', $sql);
    }

    public function retornaBind()
    {
        return $this->bind;
    }

    private function setaBind($coluna, $valor)
    {
        $this->bind[$coluna] = $valor;
        return $this;
    }
}
