<?php

/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * 
 * @author Edson Monteiro <bruno.monteirodg@gmail.com>
 * @file Config.conf.php
 * @version 0.5
 * - Arquivo responsavel por conter as configurações do Aplicativo
 * 
 * ////////////////////////////////////////////////////
 * AS CONTANTES DEFINIDAS AQUI NÃO DEVEM SER RENOMEADAS
 */

// Separador de Diretório
define('DS', DIRECTORY_SEPARATOR);
// URI principal de acesso a aplicação : /
define('APPDIR', '/app/sisgeneros/');
// Nome da Aplicação
define('APPNAM', 'SisGêneros');
// Versão da Aplicação
define('APPVER', '1.7');
// Salt String usado na criptografia
define('STRSAL', 'n%0$8VgDH6U6At %% (B16XZdZwVPGT^55u4I)TBU3VV');
// Entidade Padrão usada para login
define('TBLOGI', 'users');
// Rota padrão para o formulário de login
define('CTRLOG', 'acesso/login');
// Rota padrão para o formulário de troca de senha no primeiro acesso
define('CTRMOP', 'acesso/mudarsenha');
// Coluna padrão que indica a necessidade de troca de senha
define('COLMOP', 'trocar_senha');
// Nome do Domínio onde o Aplicativo rodará
define('DOMAIN', 'ceimbe.mb');
// Contato do Administrador do Sistema
define('ADCONT', 'E-Mail: 30@ceimbe.mar.mil.br;<br>Fone: +55 91 3216-4512');
// Diretório onde se encontra a base de algoritmos da aplicação
// Default Value: ../
define('DRINST', '../');
