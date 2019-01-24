<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Insert.php
 * @version 0.1
 * - Class que gerencia a inserção de registros do Banco de Dados
 */
namespace HTR\Database\Instruction;

use HTR\Database\Instruction;

final class Insert extends Instruction
{

    private $valores;

    public function retornaSql()
    {
        if (empty($this->entidade)) {
            throw new \Exception('Você não declarou a entidade!');
        }

        $sql = 'INSERT INTO ' . $this->entidade . ' ' . $this->valores . ';';
        return $sql;
    }

    public function setaValores(Array $valores = [])
    {
        parent::setaValores($valores);
        $chaves = array_keys($valores);
        $colunas = implode(', ', $chaves);
        $valores = implode(', :', $chaves);

        $this->valores = '(' . $colunas . ') VALUES (:' . $valores . ')';

        return $this;
    }
}
