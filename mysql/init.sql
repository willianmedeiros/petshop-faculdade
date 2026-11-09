SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS petshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petshop;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    especie VARCHAR(50) NOT NULL,
    raca VARCHAR(50),
    idade VARCHAR(50),
    foto VARCHAR(255),
    observacoes TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descricao TEXT NULL,
    icone VARCHAR(50) DEFAULT '🐾',
    duracao_minutos INT DEFAULT 30,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO servicos (nome, preco, descricao, icone, duracao_minutos) VALUES
('Banho', 60.00, 'Banho completo com água morna, produtos específicos e secagem', '🛁', 40),
('Tosa Higiênica', 50.00, 'Aparagem de pelos das patas, barriga e região íntima', '✂️', 30),
('Banho & Tosa Completa', 95.00, 'Pacote completo: banho, hidratação e tosa geral', '🧼', 75),
('Consulta Veterinária', 150.00, 'Avaliação clínica geral com médico veterinário', '🩺', 45),
('Vacinação', 120.00, 'Aplicação de vacinas (V10, antirrábica, gripe ou giardíase)', '💉', 20),
('Hidratação de Pelagem', 45.00, 'Máscara hidratante reconstrutora para pelos macios e brilhantes', '✨', 25),
('Corte de Unhas & Limpeza Auricular', 35.00, 'Higiene das orelhas e corte seguro das unhas', '🐾', 20);

CREATE TABLE IF NOT EXISTS agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    servico_id INT NULL,
    servico VARCHAR(100) NOT NULL,
    data_hora DATETIME NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Agendado',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observacoes TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
