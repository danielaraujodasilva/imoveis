CREATE DATABASE IF NOT EXISTS agente_imoveis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agente_imoveis;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buscas_imoveis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  nome VARCHAR(255),
  termos LONGTEXT,
  cidade VARCHAR(190) NULL,
  bairro VARCHAR(190) NULL,
  tipo_negocio VARCHAR(30) DEFAULT 'venda',
  tipo_imovel VARCHAR(80) NULL,
  preco_min DECIMAL(12,2) NULL,
  preco_max DECIMAL(12,2) NULL,
  quartos_min INT NULL,
  area_min DECIMAL(10,2) NULL,
  palavras_obrigatorias LONGTEXT NULL,
  palavras_proibidas LONGTEXT NULL,
  fontes LONGTEXT NULL,
  ativa TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS imoveis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  titulo VARCHAR(255),
  anunciante VARCHAR(255),
  tipo_negocio VARCHAR(30) DEFAULT 'venda',
  tipo_imovel VARCHAR(80) NULL,
  cidade VARCHAR(190) NULL,
  bairro VARCHAR(190) NULL,
  endereco VARCHAR(255) NULL,
  preco DECIMAL(12,2) NULL,
  preco_texto VARCHAR(120) NULL,
  condominio DECIMAL(12,2) NULL,
  iptu DECIMAL(12,2) NULL,
  quartos INT NULL,
  banheiros INT NULL,
  vagas_garagem INT NULL,
  area_m2 DECIMAL(10,2) NULL,
  fonte VARCHAR(100),
  url TEXT,
  descricao LONGTEXT,
  data_publicacao VARCHAR(100) NULL,
  hash_imovel VARCHAR(64),
  nota_oportunidade INT DEFAULT 0,
  resumo_oportunidade LONGTEXT NULL,
  status VARCHAR(50) DEFAULT 'novo',
  raw_json LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY idx_imoveis_usuario_hash (user_id, hash_imovel),
  KEY idx_imoveis_filtros (user_id, cidade, bairro, tipo_negocio, preco, quartos, area_m2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_execucao_imoveis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  tipo VARCHAR(100),
  mensagem LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
