-- Criar banco
CREATE DATABASE IF NOT EXISTS smartroute;
USE smartroute;

-- Usuários do sistema
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE
);

-- Entregas
CREATE TABLE IF NOT EXISTS entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    lat VARCHAR(30) NOT NULL,
    lng VARCHAR(30) NOT NULL,
    estado VARCHAR(30) DEFAULT 'Em andamento',
    usuario_id INT,
    data_agendamento DATETIME NULL,
    data_entrega DATE NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);