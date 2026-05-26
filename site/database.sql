CREATE DATABASE IF NOT EXISTS artes DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE artes;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','designer','production','financial','client') NOT NULL DEFAULT 'client',
    avatar VARCHAR(255) DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    designer_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('novo','em_producao','ajustes','aguardando_cliente','finalizado') NOT NULL DEFAULT 'novo',
    priority ENUM('urgente','alta','normal','baixa') NOT NULL DEFAULT 'normal',
    deadline DATE DEFAULT NULL,
    total_value DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (designer_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE order_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(10) NOT NULL,
    version INT DEFAULT 1,
    file_size INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('pendente','concluida') DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE time_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME DEFAULT NULL,
    duration INT DEFAULT 0,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE finances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT DEFAULT NULL,
    type ENUM('receber','pagar') NOT NULL,
    description VARCHAR(255) NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    status ENUM('pendente','pago','vencido') DEFAULT 'pendente',
    due_date DATE DEFAULT NULL,
    paid_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Senha: 123456 (bcrypt)
INSERT INTO users (name, email, password, role) VALUES
('Admin Master', 'admin@artes.com', '$2y$10$LxbN8rJqXch7P57FE6X83.KaRZ7dXrufrLH/op5IpQh2NQz3gmWoC', 'admin'),
('Designer Ana', 'designer@artes.com', '$2y$10$LxbN8rJqXch7P57FE6X83.KaRZ7dXrufrLH/op5IpQh2NQz3gmWoC', 'designer'),
('Cliente João', 'cliente@artes.com', '$2y$10$LxbN8rJqXch7P57FE6X83.KaRZ7dXrufrLH/op5IpQh2NQz3gmWoC', 'client'),
('Financeiro Carla', 'financeiro@artes.com', '$2y$10$LxbN8rJqXch7P57FE6X83.KaRZ7dXrufrLH/op5IpQh2NQz3gmWoC', 'financial'),
('Produção Pedro', 'producao@artes.com', '$2y$10$LxbN8rJqXch7P57FE6X83.KaRZ7dXrufrLH/op5IpQh2NQz3gmWoC', 'production');

INSERT INTO clients (user_id, company, phone) VALUES
(3, 'João Empresas Ltda', '(11) 99999-0001');
