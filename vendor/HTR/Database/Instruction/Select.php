<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Select.php
 * @version 0.1
 * - Class que gerencia a seleção de registros do Banco de Dados
 */
namespace HTR\Database\Instruction;

use HTR\Database\Instruction;

final class Select extends Instruction
{

    private $campos;

    public function setaCampos(Array $campos)
    {
        $this->campos = implode(', ', $campos);
        return $this;
    }

    public function retornaSql()
    {
        $this->campos = (empty($this->campos)) ? '*' : $this->campos;

        if (empty($this->entidade)) {
            throw new \Exception('Você não declarou a entidade!');
        }

        $sql = 'SELECT ' . $this->campos . ' FROM ' . $this->entidade . ' ';

        if (!empty($this->filtros)) {
            $sql .= $this->filtros->retornaSql();
        }
        return $sql . ';';
    }

    public function setaValores(Array $valores = [])
    {
        throw new \Exception('Você não pode chamar o método setaValores em um Select!');
    }
}
