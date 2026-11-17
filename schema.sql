CREATE DATABASE IF NOT EXISTS hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    capacity TINYINT NOT NULL DEFAULT 2,
    area DECIMAL(6,2) NOT NULL DEFAULT 0,
    image VARCHAR(255) NOT NULL,
    description TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE room_features (
    room_id INT NOT NULL,
    feature_id INT NOT NULL,
    PRIMARY KEY (room_id, feature_id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('new','approved','rejected') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_status_dates (room_id, status, check_in, check_out)
) ENGINE=InnoDB;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- администратор: hotel123 / adminHotel (хеш bcrypt)
INSERT INTO admins (login, password_hash) VALUES
('hotel123', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1H6mJZ4rUqLq8V5K8Y6p1u1n3g7Jc3a');

INSERT INTO categories (name) VALUES ('Стандартный'), ('Студия'), ('Люкс');

INSERT INTO features (name) VALUES
('Полный санузел'),('Двуспальная кровать'),('Телевизор'),
('Холодильник'),('Кондиционер'),('Кухонный уголок'),('Двухкомнатный');

-- пример номеров (цены из «Характеристики номеров.txt»)
INSERT INTO rooms (category_id, title, price, capacity, area, image, description) VALUES
(1,'Стандарт',      3500, 2, 18, 'img/standart.png', 'Уютный стандартный номер'),
(1,'Стандарт+',     3800, 2, 20, 'img/standart.png', 'Стандарт с видом на город'),
(2,'Студия',        5400, 2, 26, 'img/studio.jpg',   'Студия с кухонным уголком'),
(2,'Студия Люкс',   6200, 3, 30, 'img/studio.jpg',   'Просторная студия'),
(3,'Люкс',         10500, 2, 42, 'img/lux.png',      'Двухкомнатный люкс'),
(3,'Люкс Премиум', 15000, 4, 55, 'img/lux.png',      'Люкс с панорамным видом');