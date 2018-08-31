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

