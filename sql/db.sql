-- Criação das tabelas para login e entregas
CREATE DATABASE IF NOT EXISTS smartroute;
USE smartroute;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    lat VARCHAR(30) NOT NULL,
    lng VARCHAR(30) NOT NULL,
    estado VARCHAR(30) DEFAULT 'Em andamento',
    usuario_id INT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
