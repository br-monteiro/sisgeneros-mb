--
-- File generated with SQLiteStudio v3.1.1 on sáb fev 2 05:37:54 2019
--
-- Text encoding used: UTF-8
--
PRAGMA foreign_keys = off;
BEGIN TRANSACTION;

-- Table: avaliacao_fornecedor
DROP TABLE IF EXISTS avaliacao_fornecedor;

CREATE TABLE avaliacao_fornecedor (
    id            INTEGER  PRIMARY KEY AUTOINCREMENT,
    fornecedor_id INT (15) REFERENCES fornecedor (id),
    nota          INT (1)  NOT NULL,
    nao_entregue  INT (5),
    licitacao_id  INT (15) REFERENCES licitacao (id) 
);


-- Table: fornecedor
DROP TABLE IF EXISTS fornecedor;

CREATE TABLE fornecedor (
    id    INTEGER      PRIMARY KEY AUTOINCREMENT,
    nome  TEXT,
    cnpj  VARCHAR (18),
    dados TEXT
);


-- Table: licitacao
DROP TABLE IF EXISTS licitacao;

CREATE TABLE licitacao (
    id        INTEGER      PRIMARY KEY AUTOINCREMENT,
    numero    VARCHAR (10),
    uasg      INT (6),
    nome_uasg VARCHAR (50),
    validade  TIMESTAMP,
    id_lista  INT,
    criacao   INTEGER
);


-- Table: licitacao_item
DROP TABLE IF EXISTS licitacao_item;

CREATE TABLE licitacao_item (
    id            INTEGER     PRIMARY KEY AUTOINCREMENT,
    id_lista      INT,
    id_fornecedor INT,
    numero        INT,
    nome          TEXT,
    uf            VARCHAR (4),
    quantidade    INT,
    valor         REAL (9, 2) DEFAULT (0),
    active        INTEGER
);


-- Table: om
DROP TABLE IF EXISTS om;

CREATE TABLE om (
    id                        INTEGER       PRIMARY KEY AUTOINCREMENT,
    nome                      VARCHAR (60),
    uasg                      INT (6),
    indicativo_naval          VARCHAR (6),
    created_at                TIMESTAMP,
    updated_at                TIMESTAMP,
    agente_fiscal             VARCHAR (100),
    agente_fiscal_posto       VARCHAR (40),
    gestor_municiamento       VARCHAR (100),
    gestor_municiamento_posto VARCHAR (40),
    fiel_municiamento         VARCHAR (100),
    fiel_municiamento_posto   VARCHAR (40) 
);

INSERT INTO om (id, nome, uasg, indicativo_naval, created_at, updated_at, agente_fiscal, agente_fiscal_posto, gestor_municiamento, gestor_municiamento_posto, fiel_municiamento, fiel_municiamento_posto) VALUES (1, 'OM PADRAO', 123456, 'OMPADR', 1549092471, 1549092471, 'ANGENTE', 'AGENTE POSTO', 'GESTOR', 'GESTOR POSTO', 'FIEL', 'FIEL POSTO');

-- Table: solicitacao
DROP TABLE IF EXISTS solicitacao;

CREATE TABLE solicitacao (
    id                 INTEGER AUTOINCREMENT,
    id_licitacao       INT,
    id_lista           INT,
    om_id              INTEGER (10),
    ano                INTEGER (4),
    numero             INTEGER,
    status             TEXT,
    created_at         TIMESTAMP,
    updated_at         TIMESTAMP,
    fornecedor_id      INT,
    nao_licitado       INT (1)      NOT NULL
                                    DEFAULT (0),
    numero_nota_fiscal VARCHAR (20),
    data_entrega       VARCHAR (10),
    observacao         TEXT,
    PRIMARY KEY (
        id
    ),
    FOREIGN KEY (
        om_id
    )
    REFERENCES om (id) 
);


-- Table: solicitacao_item
DROP TABLE IF EXISTS solicitacao_item;

CREATE TABLE solicitacao_item (
    id                       INTEGER      PRIMARY KEY AUTOINCREMENT,
    id_lista                 INT,
    item_numero              INT,
    item_nome                VARCHAR (50),
    item_uf                  VARCHAR (5),
    item_quantidade          REAL (9, 2),
    item_quantidade_atendida REAL (9, 2)  DEFAULT (0),
    item_valor               REAL (9, 2)  DEFAULT (0) 
);


-- Table: users
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id           INTEGER       PRIMARY KEY AUTOINCREMENT,
    username     VARCHAR (60),
    password     VARCHAR (60),
    om_id        VARCHAR (6)   REFERENCES om (id),
    name         VARCHAR (20),
    email        VARCHAR (256),
    nivel        VARCHAR (15),
    trocar_senha INT (1),
    last_ip      VARCHAR (20),
    last_access  INT (14),
    created_at   INT (14),
    updated_at   INT (14),
    active       INT (1) 
);

INSERT INTO users (id, username, password, om_id, name, email, nivel, trocar_senha, last_ip, last_access, created_at, updated_at, active) VALUES (1, 'administrador', '$2y$11$SAEHIb9ulossGd0kQelZhe664b5nGcHdHNW5GhgEXtCkq03j3KwyG', '1', 'Edson Monteiro', 'bruno.monteirodg@gmail.com', 'ADMINISTRADOR', 1, '0', 0, 1549092471, 1549092471, 1);

COMMIT TRANSACTION;
PRAGMA foreign_keys = on;
