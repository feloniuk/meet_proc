-- database/schema.sql
-- Створення бази даних
CREATE DATABASE IF NOT EXISTS sausage_production_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sausage_production_db;

-- Таблиця користувачів
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'warehouse_manager', 'supplier') NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблиця сировини
CREATE TABLE raw_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    unit VARCHAR(20) NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    min_stock DECIMAL(10, 2) NOT NULL,
    supplier_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Таблиця запасів на складі
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    raw_material_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    warehouse_manager_id INT,
    FOREIGN KEY (raw_material_id) REFERENCES raw_materials(id),
    FOREIGN KEY (warehouse_manager_id) REFERENCES users(id)
);

-- Таблиця рецептів
CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Таблиця інгредієнтів для рецептів
CREATE TABLE recipe_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    raw_material_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (raw_material_id) REFERENCES raw_materials(id)
);

-- Таблиця продукції
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    recipe_id INT NOT NULL,
    weight DECIMAL(10, 2) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id)
);

-- Таблиця виробничих процесів
CREATE TABLE production_processes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    status ENUM('planned', 'in_progress', 'completed', 'canceled') NOT NULL,
    manager_id INT NOT NULL,
    notes TEXT,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (manager_id) REFERENCES users(id)
);

-- Таблиця замовлень сировини
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    ordered_by INT NOT NULL,
    status ENUM('pending', 'accepted', 'shipped', 'delivered', 'canceled') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    delivery_date DATE,
    total_amount DECIMAL(10, 2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (supplier_id) REFERENCES users(id),
    FOREIGN KEY (ordered_by) REFERENCES users(id)
);

-- Таблиця деталей замовлення
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    raw_material_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (raw_material_id) REFERENCES raw_materials(id)
);

-- Таблиця повідомлень між постачальниками та адміністраторами
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);

-- Таблиця відео нагляду
CREATE TABLE video_surveillance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    location VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Тестові дані: користувачі
INSERT INTO users (username, password, role, name, email, phone) VALUES
('admin', '$2y$10$FkQjQbZ1IBw4QlgQbfYOsOVFYJ1qnHquHWFVTvL89nVeu48qcFTZO', 'admin', 'Адміністратор', 'admin@sausage.com', '+380991234567'),
('warehouse', '$2y$10$FkQjQbZ1IBw4QlgQbfYOsOVFYJ1qnHquHWFVTvL89nVeu48qcFTZO', 'warehouse_manager', 'Начальник складу', 'warehouse@sausage.com', '+380992345678'),
('supplier1', '$2y$10$FkQjQbZ1IBw4QlgQbfYOsOVFYJ1qnHquHWFVTvL89nVeu48qcFTZO', 'supplier', 'ТОВ "М\'ясопостач"', 'meat@supplier.com', '+380993456789'),
('supplier2', '$2y$10$FkQjQbZ1IBw4QlgQbfYOsOVFYJ1qnHquHWFVTvL89nVeu48qcFTZO', 'supplier', 'ТОВ "Спеції Плюс"', 'spices@supplier.com', '+380994567890');
-- Пароль для всіх користувачів: password123

-- Тестові дані: сировина
INSERT INTO raw_materials (name, description, unit, price_per_unit, min_stock, supplier_id) VALUES
('Свинина охолоджена', 'Вищий сорт', 'кг', 95.50, 100.0, 3),
('Яловичина охолоджена', 'Вищий сорт', 'кг', 120.75, 80.0, 3),
('Сіль', 'Харчова', 'кг', 12.50, 50.0, 4),
('Перець чорний мелений', 'Вищий сорт', 'кг', 250.00, 5.0, 4),
('Часник свіжий', 'Вищий сорт', 'кг', 80.00, 10.0, 4),
('Кишки свинячі', 'Натуральна оболонка', 'м', 35.80, 200.0, 3);

-- Тестові дані: запаси
INSERT INTO inventory (raw_material_id, quantity, warehouse_manager_id) VALUES
(1, 150.0, 2),
(2, 100.0, 2),
(3, 80.0, 2),
(4, 8.0, 2),
(5, 15.0, 2),
(6, 300.0, 2);

-- Тестові дані: рецепти
INSERT INTO recipes (name, description, created_by) VALUES
('Ковбаса "Домашня"', 'Класична домашня ковбаса з часником та перцем', 1),
('Ковбаса "Краківська"', 'Традиційна польська ковбаса з яловичиною', 1);

-- Тестові дані: інгредієнти рецептів
INSERT INTO recipe_ingredients (recipe_id, raw_material_id, quantity) VALUES
(1, 1, 0.8), -- 0.8 кг свинини на 1 кг ковбаси "Домашня"
(1, 3, 0.02), -- 20 г солі на 1 кг ковбаси "Домашня"
(1, 4, 0.005), -- 5 г перцю на 1 кг ковбаси "Домашня"
(1, 5, 0.01), -- 10 г часнику на 1 кг ковбаси "Домашня"
(1, 6, 0.2), -- 0.2 м кишок на 1 кг ковбаси "Домашня"
(2, 1, 0.5), -- 0.5 кг свинини на 1 кг ковбаси "Краківська"
(2, 2, 0.5), -- 0.5 кг яловичини на 1 кг ковбаси "Краківська"
(2, 3, 0.02), -- 20 г солі на 1 кг ковбаси "Краківська"
(2, 4, 0.003), -- 3 г перцю на 1 кг ковбаси "Краківська"
(2, 6, 0.2); -- 0.2 м кишок на 1 кг ковбаси "Краківська"

-- Тестові дані: продукція
INSERT INTO products (name, description, recipe_id, weight, price) VALUES
('Ковбаса "Домашня"', 'Традиційна українська ковбаса з ароматом часнику та перцю', 1, 1.0, 180.0),
('Ковбаса "Краківська"', 'Пікантна ковбаса з яловичиною та свининою', 2, 1.0, 210.0);

-- Тестові дані: виробничі процеси
INSERT INTO production_processes (product_id, quantity, started_at, completed_at, status, manager_id, notes) VALUES
(1, 50.0, '2025-05-10 08:00:00', '2025-05-10 16:00:00', 'completed', 2, 'Партія #24534'),
(2, 30.0, '2025-05-11 08:00:00', '2025-05-11 15:00:00', 'completed', 2, 'Партія #24535'),
(1, 80.0, '2025-05-15 08:00:00', NULL, 'in_progress', 2, 'Партія #24536');

-- Тестові дані: замовлення
INSERT INTO orders (supplier_id, ordered_by, status, created_at, delivery_date, total_amount, notes) VALUES
(3, 1, 'delivered', '2025-05-01 10:00:00', '2025-05-05', 19550.0, 'Терміново'),
(4, 1, 'delivered', '2025-05-02 11:00:00', '2025-05-06', 2250.0, ''),
(3, 1, 'accepted', '2025-05-14 09:00:00', '2025-05-19', 23900.0, 'Для нової партії');

-- Тестові дані: деталі замовлень
INSERT INTO order_items (order_id, raw_material_id, quantity, price_per_unit) VALUES
(1, 1, 150.0, 95.50), -- Свинина для першого замовлення
(1, 2, 50.0, 120.75), -- Яловичина для першого замовлення
(2, 3, 80.0, 12.50), -- Сіль для другого замовлення
(2, 4, 5.0, 250.00), -- Перець для другого замовлення
(3, 1, 200.0, 95.50), -- Свинина для третього замовлення
(3, 2, 30.0, 120.75); -- Яловичина для третього замовлення

-- Тестові дані: повідомлення
INSERT INTO messages (sender_id, receiver_id, subject, message, is_read, created_at) VALUES
(1, 3, 'Щодо замовлення #3', 'Чи можете ви прискорити доставку замовлення #3?', 0, '2025-05-14 14:30:00'),
(3, 1, 'Відповідь щодо замовлення #3', 'На жаль, раніше 19 травня доставити не зможемо через логістичні проблеми.', 0, '2025-05-14 15:45:00');

-- Тестові дані: відео нагляд
INSERT INTO video_surveillance (name, url, location, status) VALUES
('Камера 1', 'rtsp://camera1.local:554/stream', 'Виробничий цех 1', 'active'),
('Камера 2', 'rtsp://camera2.local:554/stream', 'Виробничий цех 2', 'active'),
('Камера 3', 'rtsp://camera3.local:554/stream', 'Склад сировини', 'active'),
('Камера 4', 'rtsp://camera4.local:554/stream', 'Склад готової продукції', 'active');


-- Обновления базы данных для добавления роли технолога

-- 1. Добавляем новую роль 'technologist' в существующий ENUM
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'warehouse_manager', 'supplier', 'technologist') NOT NULL;

-- 2. Создаем таблицу для проверок качества
CREATE TABLE quality_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    technologist_id INT NOT NULL,
    check_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    notes TEXT,
    temperature DECIMAL(5,2) NULL COMMENT 'Температура сырья',
    ph_level DECIMAL(4,2) NULL COMMENT 'Уровень pH',
    moisture_content DECIMAL(5,2) NULL COMMENT 'Влажность %',
    visual_assessment TEXT COMMENT 'Визуальная оценка',
    smell_assessment TEXT COMMENT 'Оценка запаха',
    texture_assessment TEXT COMMENT 'Оценка текстуры',
    overall_grade ENUM('excellent', 'good', 'satisfactory', 'unsatisfactory') NULL,
    rejection_reason TEXT NULL COMMENT 'Причина отклонения',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (technologist_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Создаем таблицу для проверок качества по отдельным материалам
CREATE TABLE quality_check_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quality_check_id INT NOT NULL,
    raw_material_id INT NOT NULL,
    quantity_checked DECIMAL(10,2) NOT NULL,
    status ENUM('approved', 'rejected', 'conditional') NOT NULL,
    notes TEXT,
    defects_found TEXT COMMENT 'Обнаруженные дефекты',
    grade ENUM('A', 'B', 'C', 'D') COMMENT 'Сорт сырья',
    FOREIGN KEY (quality_check_id) REFERENCES quality_checks(id) ON DELETE CASCADE,
    FOREIGN KEY (raw_material_id) REFERENCES raw_materials(id) ON DELETE CASCADE
);

-- 4. Добавляем поле quality_check_required в таблицу orders
ALTER TABLE orders ADD COLUMN quality_check_required BOOLEAN DEFAULT TRUE COMMENT 'Требуется ли проверка качества';
ALTER TABLE orders ADD COLUMN quality_status ENUM('not_checked', 'pending', 'approved', 'rejected') DEFAULT 'not_checked';

-- 5. Создаем таблицу стандартов качества для разных типов сырья
CREATE TABLE quality_standards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    raw_material_id INT NOT NULL,
    parameter_name VARCHAR(100) NOT NULL COMMENT 'Название параметра',
    min_value DECIMAL(10,3) NULL COMMENT 'Минимальное значение',
    max_value DECIMAL(10,3) NULL COMMENT 'Максимальное значение',
    unit VARCHAR(20) NULL COMMENT 'Единица измерения',
    description TEXT COMMENT 'Описание стандарта',
    is_critical BOOLEAN DEFAULT FALSE COMMENT 'Критический параметр',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (raw_material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    UNIQUE KEY unique_standard (raw_material_id, parameter_name)
);

-- 6. Добавляем тестового технолога
INSERT INTO users (username, password, role, name, email, phone) VALUES
('technologist', '$2y$10$FkQjQbZ1IBw4QlgQbfYOsOVFYJ1qnHquHWFVTvL89nVeu48qcFTZO', 'technologist', 'Технолог Іван Петрович', 'technologist@sausage.com', '+380995678901');

-- 7. Добавляем стандарты качества для существующих материалов
INSERT INTO quality_standards (raw_material_id, parameter_name, min_value, max_value, unit, description, is_critical) VALUES
(1, 'Температура', -2, 4, '°C', 'Температура охлажденной свинины', true),
(1, 'pH', 5.3, 6.2, '', 'Кислотность мяса', true),
(1, 'Влажность', 70, 78, '%', 'Содержание влаги', false),
(2, 'Температура', -2, 4, '°C', 'Температура охлажденной говядины', true),
(2, 'pH', 5.4, 6.0, '', 'Кислотность мяса', true),
(2, 'Влажность', 72, 76, '%', 'Содержание влаги', false),
(3, 'Влажность', 0, 5, '%', 'Содержание влаги в соли', false),
(3, 'Чистота', 99, 100, '%', 'Процент чистоты соли', true);

-- 8. Обновляем существующие заказы для совместимости
UPDATE orders SET quality_check_required = TRUE, quality_status = 'not_checked' WHERE status IN ('shipped', 'delivered');