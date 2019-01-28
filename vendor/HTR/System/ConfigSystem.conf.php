<?php
/**
 * HTR FIREBIRD FRAMEWORK 2.2 - Copyright (C) <2015>  <BRUNO MONTEIRO>
 * Framework PHP e MVC para agilizar o desenvolvimento de Aplicativos Web
 * bruno.monteirodg@gmail.com
 * 
 * @file ConfigSystem.conf.php
 * @version 0.3
 * - Arquivo responsavel por conter as configurações do Sistema
 * 
 * ////////////////////////////////////////////////////
 * AS CONTANTES DEFINIDAS AQUI NÃO DEVEM SER RENOMEADAS
 */

// URI principal de acesso a aplicação : /
define('APPDIR', '/app/sisgeneros/');
// Nome da Aplicação
define('APPNAM', 'SisGêneros');
// Versão da Aplicação
define('APPVER', '1.7');
// Entidade Padrão usada para login
define('TBLOGI', 'users');
// Rota padrão para o formulário de login
define('CTRLOG', 'acesso/login');
// Rota padrão para o formulário de troca de senha no primeiro acesso
define('CTRMOP', 'acesso/mudarsenha');
// Coluna padrão que indica a necessidade de troca de senha
define('COLMOP', 'trocar_senha');
// Diretório padrão onde serão salvos os arquivos de Banco de Dados
define('DATADR', DRINST . 'App/Database/DbRepository/');
// Diretório padrão onde serão salvos os arquivos de Backup do Banco de Dados
define('DIRDBB', DRINST . 'App/Database/DbBackup/');
// Diretório padrão onde serão salvos os arquivos de outras bibliotecas
define('ATTACH', APPDIR . 'attach/');
// Diretório padrão onde serão salvos os arquivos de fragmentos de páginas
define('ATTPAG', 'attach/partPage/');
// Diretório padrão onde serão salvos os arquivos Javascript
define('DIRJS_', APPDIR . 'js/');
// Diretório padrão onde serão salvos os arquivos CSS
define('DIRCSS', APPDIR . 'css/');
// Diretório padrão onde serão salvos os arquivos de imagem
define('DIRIMG', APPDIR . 'images/');

