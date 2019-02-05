<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Instruction.php
 * @version 0.1
 * - Class que auxilia no gerenciamento nas consultas SQL
 */
namespace HTR\Database;

abstract class Instruction
{

    protected $sql;
    protected $filtros;
    protected $entidade;
    protected $bind;

    final public function setaEntidade($entidade): self
    {
        if (is_string($entidade)) {
            $this->entidade = $entidade;
            return $this;
        } else {
            throw new \Exception('A entidade deve ser uma string');
        }
    }

    final public function setaBind($valores): self
    {
        $this->bind = $valores;
        return $this;
    }

    final public function retornaBind(): array
    {
        if (!empty($this->filtros)) {
            if (empty($this->bind)) {
                $this->bind = $this->filtros->retornaBind();
            } else {
                $this->bind = array_merge($this->bind, $this->filtros->retornaBind());
            }
        }
        if (!is_array($this->bind)) {
            $this->bind = [];
        }
        return $this->bind;
    }

    final public function setaFiltros(): Filters
    {
        $this->filtros = new Filters();
        return $this->filtros;
    }

    abstract public function retornaSql();

    public function setaValores(Array $valores)
    {
        $this->setaBind($valores);
    }
}
