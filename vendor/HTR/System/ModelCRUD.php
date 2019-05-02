<?php
/*
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file ModelCRUD.php
 * @version 0.2
 * - Class que gerencia a abstração do Banco de Dados - CRUD
 */
namespace HTR\System;

use HTR\System\ModelAbstract;

class ModelCRUD extends ModelAbstract
{

    /**
     * Return all results of entity
     * @param callable $callback This callback receive three values as parameters.
     * The first value is an instance of \HTR\Database\Instruction.
     * The second value is an instance of \HTR\Database\Database.
     * And the last value is an instance of \HTR\System\ModelCRUD.
     * @return array
     */
    public function findAll($callback = null): array
    {
        $select = $this->db->instrucao('select')
            ->setaEntidade($this->entidade);

        if (is_callable($callback)) {
            $callback($select, $this->db, $this);
        }

        return $this->db->executa('select')->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function novo($dados)
    {
        $this->db->instrucao('insert')
            ->setaEntidade($this->entidade)
            ->setaValores($dados);

        return $this->db->executa();
    }

    public function editar($dados, $id)
    {
        $this->db->instrucao('update')
            ->setaEntidade($this->entidade)
            ->setaValores($dados)
            ->setaFiltros()
            ->where('id', '=', $id);

        return $this->db->executa();
    }

    public function remover($id)
    {
        $this->db->instrucao('delete')
            ->setaEntidade($this->entidade)
            ->setaFiltros()
            ->where('id', '=', $id);

        return $this->db->executa();
    }

    public function __call($metodo, $propriedades = null)
    {
        if (substr($metodo, 0, 6) == 'findBy') {
            $campo = strtolower(substr($metodo, 6, strlen($metodo)));
            $this->db->instrucao('select')
                ->setaEntidade($this->entidade)
                ->setaFiltros()
                ->where($campo, '=', $propriedades[0]);

            return $this->db->executa('select')->fetch(\PDO::FETCH_ASSOC);
        } elseif (substr($metodo, 0, 9) == 'findAllBy') {
            $campo = strtolower(substr($metodo, 9, strlen($metodo)));
            $this->db->instrucao('select')
                ->setaEntidade($this->entidade)
                ->setaFiltros()
                ->where($campo, '=', $propriedades[0]);

            return $this->db->executa('select')->fetchAll(\PDO::FETCH_ASSOC);
        } elseif (substr($metodo, 0, 3) == 'set') {

            $attribName = lcfirst(substr($metodo, 3, strlen($metodo)));
            $this->$attribName = isset($propriedades[0]) ? $propriedades[0] : null;
            return $this;
        } elseif (substr($metodo, 0, 3) == 'get') {

            $attribName = lcfirst(substr($metodo, 3, strlen($metodo)));
            if (isset($this->$attribName)) {
                return $this->$attribName;
            } else {
                throw new \Exception("Attribute '{$attribName}' is not found");
            }
        } else {
            throw new \Exception("The method '{$metodo}' is not accessible or not exists");
        }
    }
}
