-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema sisgeneros
-- -----------------------------------------------------
DROP SCHEMA IF EXISTS `sisgeneros` ;

-- -----------------------------------------------------
-- Schema sisgeneros
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `sisgeneros` DEFAULT CHARACTER SET utf8 ;
USE `sisgeneros` ;

-- -----------------------------------------------------
-- Table `sisgeneros`.`oms`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`oms` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`oms` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `naval_indicative` VARCHAR(6) NOT NULL,
  `uasg` INT(6) NOT NULL,
  `fiscal_agent` VARCHAR(100) NOT NULL COMMENT 'Nome do Agente Fiscal',
  `fiscal_agent_graduation` VARCHAR(50) NOT NULL,
  `munition_manager` VARCHAR(100) NOT NULL COMMENT 'Nome do Gestor de Municiamento',
  `munition_manager_graduation` VARCHAR(50) NOT NULL,
  `munition_fiel` VARCHAR(100) NOT NULL COMMENT 'Nome do Fiel de Municiamento',
  `munition_fiel_graduation` VARCHAR(50) NOT NULL,
  `created_at` DATE NOT NULL,
  `updated_at` DATE NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC),
  UNIQUE INDEX `naval_indicative_UNIQUE` (`naval_indicative` ASC))
ENGINE = InnoDB
COMMENT = 'Organizações Militares';


-- -----------------------------------------------------
-- Table `sisgeneros`.`users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`users` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `oms_id` INT NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `level` VARCHAR(15) NOT NULL DEFAULT 'NORMAL',
  `username` VARCHAR(20) NOT NULL,
  `password` VARCHAR(60) NOT NULL,
  `change_password` VARCHAR(3) NOT NULL DEFAULT 'yes',
  `active` VARCHAR(3) NOT NULL DEFAULT 'yes',
  `created_at` DATE NOT NULL,
  `updated_at` DATE NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC),
  INDEX `fk_users_oms_idx` (`oms_id` ASC),
  CONSTRAINT `fk_users_oms`
    FOREIGN KEY (`oms_id`)
    REFERENCES `sisgeneros`.`oms` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'tabela de usuário, contendo os dados do usuário e as credenciais de acesso';


-- -----------------------------------------------------
-- Table `sisgeneros`.`suppliers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`suppliers` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`suppliers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `cnpj` VARCHAR(18) NOT NULL,
  `details` VARCHAR(256) NULL DEFAULT 'Dados do fornecedor...',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC),
  UNIQUE INDEX `cnpj_UNIQUE` (`cnpj` ASC))
ENGINE = InnoDB
COMMENT = 'Fornecedores';


-- -----------------------------------------------------
-- Table `sisgeneros`.`biddings`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`biddings` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`biddings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `number` VARCHAR(10) NOT NULL,
  `uasg` INT(6) NOT NULL,
  `uasg_name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(30) NULL,
  `validate` DATE NOT NULL,
  `created_at` DATE NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `number_UNIQUE` (`number` ASC))
ENGINE = InnoDB
COMMENT = 'Licitações do sistema';


-- -----------------------------------------------------
-- Table `sisgeneros`.`ingredients`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`ingredients` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`ingredients` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'Igredientes usados na confecção de receitas';


-- -----------------------------------------------------
-- Table `sisgeneros`.`biddings_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`biddings_items` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`biddings_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `biddings_id` INT NOT NULL,
  `suppliers_id` INT NOT NULL,
  `ingredients_id` INT NOT NULL,
  `number` INT(5) NOT NULL,
  `name` VARCHAR(256) NOT NULL,
  `uf` VARCHAR(4) NOT NULL,
  `quantity` FLOAT(9,3) NOT NULL,
  `value` FLOAT(9,2) NOT NULL,
  `active` VARCHAR(3) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`id`),
  INDEX `fk_biddings_items_biddings1_idx` (`biddings_id` ASC),
  INDEX `fk_biddings_items_suppliers1_idx` (`suppliers_id` ASC),
  INDEX `fk_biddings_items_ingredients1_idx` (`ingredients_id` ASC),
  CONSTRAINT `fk_biddings_items_biddings1`
    FOREIGN KEY (`biddings_id`)
    REFERENCES `sisgeneros`.`biddings` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_biddings_items_suppliers1`
    FOREIGN KEY (`suppliers_id`)
    REFERENCES `sisgeneros`.`suppliers` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_biddings_items_ingredients1`
    FOREIGN KEY (`ingredients_id`)
    REFERENCES `sisgeneros`.`ingredients` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Itens das Licitações Registradas no Sistema';


-- -----------------------------------------------------
-- Table `sisgeneros`.`billboards`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`billboards` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`billboards` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL,
  `content` VARCHAR(256) NOT NULL,
  `beginning_date` DATE NOT NULL,
  `ending_date` DATE NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'Quadro de avisos';


-- -----------------------------------------------------
-- Table `sisgeneros`.`billboards_oms_lists`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`billboards_oms_lists` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`billboards_oms_lists` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `billboards_id` INT NOT NULL,
  `oms_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_billboards_oms_lists_billboards1_idx` (`billboards_id` ASC),
  INDEX `fk_billboards_oms_lists_oms1_idx` (`oms_id` ASC),
  CONSTRAINT `fk_billboards_oms_lists_billboards1`
    FOREIGN KEY (`billboards_id`)
    REFERENCES `sisgeneros`.`billboards` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_billboards_oms_lists_oms1`
    FOREIGN KEY (`oms_id`)
    REFERENCES `sisgeneros`.`oms` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Organizações Militares permitidas';


-- -----------------------------------------------------
-- Table `sisgeneros`.`requests`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`requests` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `oms_id` INT NOT NULL,
  `suppliers_id` INT NOT NULL,
  `biddings_id` INT NULL,
  `number` INT(8) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'ABERTO',
  `invoice` VARCHAR(20) NOT NULL DEFAULT 'S/N',
  `delivery_date` DATE NOT NULL,
  `observation` VARCHAR(512) NULL,
  `created_at` DATE NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `number_UNIQUE` (`number` ASC),
  INDEX `fk_requests_oms1_idx` (`oms_id` ASC),
  INDEX `fk_requests_suppliers1_idx` (`suppliers_id` ASC),
  CONSTRAINT `fk_requests_oms1`
    FOREIGN KEY (`oms_id`)
    REFERENCES `sisgeneros`.`oms` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_requests_suppliers1`
    FOREIGN KEY (`suppliers_id`)
    REFERENCES `sisgeneros`.`suppliers` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Solicitações de itens Licitados e Não Licitados';


-- -----------------------------------------------------
-- Table `sisgeneros`.`requests_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`requests_items` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`requests_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `requests_id` INT NOT NULL,
  `number` INT(8) NULL,
  `name` VARCHAR(256) NOT NULL,
  `uf` VARCHAR(4) NOT NULL,
  `quantity` FLOAT(9,3) NOT NULL COMMENT 'Quantidade solicitada',
  `delivered` FLOAT(9,3) NOT NULL COMMENT 'Quantidade entregue',
  `value` FLOAT(9,2) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_requests_items_requests1_idx` (`requests_id` ASC),
  CONSTRAINT `fk_requests_items_requests1`
    FOREIGN KEY (`requests_id`)
    REFERENCES `sisgeneros`.`requests` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Items das solicitações';


-- -----------------------------------------------------
-- Table `sisgeneros`.`suppliers_evaluations`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`suppliers_evaluations` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`suppliers_evaluations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `requests_id` INT NOT NULL,
  `evaluation` INT(1) NOT NULL DEFAULT 3,
  PRIMARY KEY (`id`),
  INDEX `fk_suppliers_evaluations_requests1_idx` (`requests_id` ASC),
  CONSTRAINT `fk_suppliers_evaluations_requests1`
    FOREIGN KEY (`requests_id`)
    REFERENCES `sisgeneros`.`requests` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Avaliação de entrega dos fornecedores';


-- -----------------------------------------------------
-- Table `sisgeneros`.`recipes_patterns`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`recipes_patterns` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`recipes_patterns` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'Receitas padrões registradas no sistema';


-- -----------------------------------------------------
-- Table `sisgeneros`.`recipes_patterns_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`recipes_patterns_items` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`recipes_patterns_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ingredients_id` INT NOT NULL,
  `recipes_patterns_id` INT NOT NULL,
  `quantity` FLOAT(9,3) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_recipes_patterns_items_recipes_patterns1_idx` (`recipes_patterns_id` ASC),
  INDEX `fk_recipes_patterns_items_ingredients1_idx` (`ingredients_id` ASC),
  CONSTRAINT `fk_recipes_patterns_items_recipes_patterns1`
    FOREIGN KEY (`recipes_patterns_id`)
    REFERENCES `sisgeneros`.`recipes_patterns` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_recipes_patterns_items_ingredients1`
    FOREIGN KEY (`ingredients_id`)
    REFERENCES `sisgeneros`.`ingredients` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Itens das receitas';


-- -----------------------------------------------------
-- Table `sisgeneros`.`menus`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`menus` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`menus` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `oms_id` INT NOT NULL,
  `users_id_requesters` INT NOT NULL,
  `users_id_authorizers` INT NOT NULL,
  `beginning_date` DATE NOT NULL,
  `ending_date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'ABERTO',
  `raw_menus_object` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_menus_oms1_idx` (`oms_id` ASC),
  INDEX `fk_menus_users1_idx` (`users_id_requesters` ASC),
  INDEX `fk_menus_users2_idx` (`users_id_authorizers` ASC),
  CONSTRAINT `fk_menus_oms1`
    FOREIGN KEY (`oms_id`)
    REFERENCES `sisgeneros`.`oms` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_menus_users1`
    FOREIGN KEY (`users_id_requesters`)
    REFERENCES `sisgeneros`.`users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_menus_users2`
    FOREIGN KEY (`users_id_authorizers`)
    REFERENCES `sisgeneros`.`users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Cardápios registrados pela Organizações Militares';


-- -----------------------------------------------------
-- Table `sisgeneros`.`meals`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`meals` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`meals` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sort` VARCHAR(15) NOT NULL DEFAULT 1,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'Refeições diárias';


-- -----------------------------------------------------
-- Table `sisgeneros`.`recipes`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`recipes` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`recipes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `meals_id` INT NOT NULL,
  `menus_id` INT NOT NULL,
  `recipes_patterns_id` INT NOT NULL COMMENT 'Receita padrão usada como base',
  `name` VARCHAR(50) NOT NULL,
  `quantity_people` INT(5) NOT NULL COMMENT 'Quantidade de pessoas a serem atendidas',
  `date` DATE NOT NULL,
  `sort` VARCHAR(15) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_recipes_recipes_patterns1_idx` (`recipes_patterns_id` ASC),
  INDEX `fk_recipes_meals1_idx` (`meals_id` ASC),
  INDEX `fk_recipes_menus1_idx` (`menus_id` ASC),
  CONSTRAINT `fk_recipes_recipes_patterns1`
    FOREIGN KEY (`recipes_patterns_id`)
    REFERENCES `sisgeneros`.`recipes_patterns` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_recipes_meals1`
    FOREIGN KEY (`meals_id`)
    REFERENCES `sisgeneros`.`meals` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_recipes_menus1`
    FOREIGN KEY (`menus_id`)
    REFERENCES `sisgeneros`.`menus` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sisgeneros`.`recipes_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sisgeneros`.`recipes_items` ;

CREATE TABLE IF NOT EXISTS `sisgeneros`.`recipes_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `recipes_id` INT NOT NULL,
  `biddings_items_id` INT NULL COMMENT 'Item da licitação quando houver',
  `name` VARCHAR(50) NOT NULL,
  `suggested_quantity` FLOAT(9,3) NOT NULL,
  `quantity` FLOAT(9,3) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_recispes_items_recipes1_idx` (`recipes_id` ASC),
  CONSTRAINT `fk_recispes_items_recipes1`
    FOREIGN KEY (`recipes_id`)
    REFERENCES `sisgeneros`.`recipes` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
