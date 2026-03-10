START TRANSACTION;

CREATE DATABASE IF NOT EXISTS chicken_grazing_monitoring;
USE chicken_grazing_monitoring;


CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL
);


CREATE TABLE chickens (
    chicken_id INT AUTO_INCREMENT PRIMARY KEY,
    tag_number VARCHAR(50) NOT NULL UNIQUE,
    breed VARCHAR(50) NOT NULL,
    current_zone VARCHAR(50),
    boundary_status VARCHAR(50)
);


CREATE TABLE user_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chicken_id INT NOT NULL,
    zone VARCHAR(50),
    boundary_status VARCHAR(50),
    action VARCHAR(50) DEFAULT 'Monitoring',
    log_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (chicken_id) REFERENCES chickens(chicken_id)
);
INSERT INTO users (username, password, role) VALUES
('admin', 'admin123', 'admin'),
('farmer1', 'farmer123', 'user'),
('farmer2', 'farmer123', 'user');

COMMIT;