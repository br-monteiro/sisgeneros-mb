<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file Update.php
 * @version 0.1
 * - Class que gerencia a alteração de registros do Banco de Dados
 */
namespace HTR\Database\Instruction;

use HTR\Database\Instruction;

final class Update extends Instruction
{

    private $valores;

    public function retornaSql()
    {
        if (empty($this->entidade)) {
            throw new \Exception('Você não declarou a entidade!');
        }

        $sql = 'UPDATE ' . $this->entidade . ' SET ' . $this->valores . ' ';

        if (!empty($this->filtros)) {
            $sql .= $this->filtros->retornaSql();
        }
        return $sql . ';';
    }

    public function setaValores(Array $valores = [])
    {
        parent::setaValores($valores);

        $chaves = array_keys($valores);

        $sql = [];
        foreach ($chaves as &$chave) {
            $sql[] = $chave . '=:' . $chave;
        }

        $this->valores = implode(', ', $sql);

        return $this;
    }
}
