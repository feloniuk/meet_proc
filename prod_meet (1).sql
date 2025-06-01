-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2025 at 12:15 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `prod_meet`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateOrderStatusOnDelivery` (IN `order_id` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Обновляем статус заказа
    UPDATE orders 
    SET status = 'delivered', 
        updated_at = NOW(),
        quality_status = 'not_checked'
    WHERE id = order_id;
    
    -- Создаем запись для проверки качества
    INSERT INTO quality_checks (order_id, technologist_id, notes, status)
    SELECT order_id, 8, 'Автоматично створена перевірка при доставці', 'pending'
    WHERE NOT EXISTS (SELECT 1 FROM quality_checks WHERE order_id = order_id);
    
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UseRawMaterialsForProduction` (IN `production_id` INT, IN `recipe_id` INT, IN `production_quantity` DECIMAL(10,2), IN `manager_id` INT)   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE material_id INT;
    DECLARE required_quantity DECIMAL(10,2);
    DECLARE available_quantity DECIMAL(10,2);
    
    DECLARE cur CURSOR FOR 
        SELECT ri.raw_material_id, ri.quantity * production_quantity
        FROM recipe_ingredients ri
        WHERE ri.recipe_id = recipe_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Проверяем наличие всех материалов
    OPEN cur;
    check_loop: LOOP
        FETCH cur INTO material_id, required_quantity;
        IF done THEN
            LEAVE check_loop;
        END IF;
        
        SELECT quantity INTO available_quantity
        FROM inventory
        WHERE raw_material_id = material_id;
        
        IF available_quantity < required_quantity THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Недостатньо сировини на складі';
        END IF;
    END LOOP;
    CLOSE cur;
    
    -- Если все проверки прошли, списываем материалы
    SET done = FALSE;
    OPEN cur;
    update_loop: LOOP
        FETCH cur INTO material_id, required_quantity;
        IF done THEN
            LEAVE update_loop;
        END IF;
        
        UPDATE inventory 
        SET quantity = quantity - required_quantity,
            warehouse_manager_id = manager_id,
            last_updated = NOW()
        WHERE raw_material_id = material_id;
    END LOOP;
    CLOSE cur;
    
    -- Обновляем статус производственного процесса
    UPDATE production_processes
    SET status = 'in_progress'
    WHERE id = production_id;
    
    COMMIT;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `CalculateRecipeCost` (`recipe_id` INT) RETURNS DECIMAL(10,2) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE total_cost DECIMAL(10,2) DEFAULT 0;
    
    SELECT SUM(ri.quantity * rm.price_per_unit) INTO total_cost
    FROM recipe_ingredients ri
    JOIN raw_materials rm ON ri.raw_material_id = rm.id
    WHERE ri.recipe_id = recipe_id;
    
    RETURN IFNULL(total_cost, 0);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `warehouse_manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `raw_material_id`, `quantity`, `last_updated`, `warehouse_manager_id`) VALUES
(1, 1, '150.00', '2025-05-22 10:45:29', 2),
(2, 2, '95.00', '2025-05-22 10:45:29', 2),
(3, 3, '75.00', '2025-05-22 10:45:29', 2),
(4, 4, '8.50', '2025-05-22 10:45:29', 2),
(5, 5, '12.00', '2025-05-22 10:45:29', 2),
(6, 6, '285.00', '2025-05-22 10:45:29', 2),
(7, 7, '35.00', '2025-05-22 10:45:29', 2),
(8, 8, '4.50', '2025-05-22 10:45:29', 2),
(9, 9, '2.80', '2025-05-22 10:45:29', 2),
(10, 10, '1.50', '2025-05-22 10:45:29', 2),
(11, 11, '65.00', '2025-05-22 10:45:29', 2),
(12, 12, '25.00', '2025-05-22 10:45:29', 2);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 1, 'Новий прайс-лист на м\'ясо', 'Доброго дня! Надсилаємо оновлений прайс-лист на м\'ясну продукцію. Ціни діють з 1 червня 2025 року. З повагою, ТОВ \"М\'ясопостач Україна\".', 1, '2025-05-14 06:30:00'),
(2, 5, 1, 'Знижка на великі партії спецій', 'Пропонуємо знижку 5% при замовленні спецій на суму понад 5000 грн. Акція діє до кінця місяця.', 0, '2025-05-15 11:20:00'),
(3, 6, 1, 'Затримка постачання оболонок', 'На жаль, через технічні проблеми на виробництві, поставка оболонок затримується на 2 дні. Вибачаємось за незручності.', 1, '2025-05-16 08:45:00'),
(4, 1, 8, 'Нові стандарти якості', 'Додано нові стандарти якості для молочних продуктів. Перегляньте, будь ласка, оновлені вимоги в системі.', 0, '2025-05-17 05:15:00'),
(5, 1, 2, 'Планування виробництва на наступний тиждень', 'Підготуйте, будь ласка, план виробництва на наступний тиждень з урахуванням наявних запасів сировини.', 1, '2025-05-18 13:30:00'),
(6, 2, 3, 'Інвентаризація молочних продуктів', 'Потрібно провести додаткову перевірку запасів сухого молока. Чи можете допомогти завтра зранку?', 1, '2025-05-19 09:00:00'),
(7, 3, 2, 'Re: Інвентаризація молочних продуктів', 'Звичайно, допоможу. Зустрінемося о 8:00 на складі.', 1, '2025-05-19 10:15:00'),
(8, 8, 1, 'Рекомендації щодо постачальника №4', 'Після перевірки останньої партії м\'яса рекомендую продовжити співпрацю з ТОВ \"М\'ясопостач Україна\". Якість стабільно висока.', 1, '2025-05-20 07:45:00'),
(9, 9, 2, 'Проблеми з якістю часнику', 'Виявлено підвищену вологість у партії часнику. Рекомендую використати в першу чергу.', 1, '2025-05-21 06:20:00'),
(10, 1, 4, 'Підтвердження замовлення №4', 'Замовлення №4 підтверджено. Очікуємо поставку 20 травня 2025 року.', 1, '2025-05-15 14:00:00'),
(11, 1, 7, 'Новий договір постачання', 'Пропонуємо укласти договір на постачання молочних продуктів. Зв\'яжіться з нами для обговорення умов.', 0, '2025-05-18 11:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `ordered_by` int(11) NOT NULL,
  `status` enum('pending','accepted','shipped','delivered','canceled') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `quality_check_required` tinyint(1) DEFAULT 1,
  `quality_status` enum('not_checked','pending','approved','rejected') DEFAULT 'not_checked'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `supplier_id`, `ordered_by`, `status`, `created_at`, `updated_at`, `delivery_date`, `total_amount`, `notes`, `quality_check_required`, `quality_status`) VALUES
(1, 4, 1, 'delivered', '2025-05-01 06:00:00', '2025-05-22 10:45:29', '2025-05-05', '22550.00', 'Терміновий заказ для початку тижня', 1, 'approved'),
(2, 5, 1, 'delivered', '2025-05-02 07:30:00', '2025-05-22 10:45:29', '2025-05-06', '2890.00', 'Спеції для нової партії', 1, 'approved'),
(3, 6, 1, 'delivered', '2025-05-03 11:00:00', '2025-05-22 10:45:29', '2025-05-07', '1925.00', 'Оболонки для ковбас', 1, 'approved'),
(4, 4, 1, 'shipped', '2025-05-15 05:00:00', '2025-05-22 10:45:29', '2025-05-20', '18750.00', 'Плановий заказ м\'яса', 1, 'pending'),
(5, 7, 1, 'accepted', '2025-05-16 08:00:00', '2025-05-22 10:45:29', '2025-05-22', '5075.00', 'Молочні продукти', 1, 'not_checked'),
(6, 5, 1, 'pending', '2025-05-20 12:30:00', '2025-05-22 10:45:29', '2025-05-25', '1840.00', 'Додаткові спеції', 1, 'not_checked'),
(7, 6, 1, 'pending', '2025-05-22 16:55:39', '2025-05-22 19:43:07', '2025-05-29', '3811.50', 'Треба багало кишки для ковбас - 100Кг', 1, 'not_checked'),
(8, 7, 1, 'pending', '2025-05-22 21:05:28', '2025-05-22 21:05:43', '2025-05-30', '290.00', '123', 1, 'not_checked');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `raw_material_id`, `quantity`, `price_per_unit`) VALUES
(1, 1, 1, '180.00', '95.50'),
(2, 1, 2, '80.00', '125.75'),
(3, 2, 3, '60.00', '12.50'),
(4, 2, 4, '8.00', '280.00'),
(5, 2, 5, '10.00', '85.00'),
(6, 3, 6, '50.00', '38.50'),
(7, 4, 1, '150.00', '95.50'),
(8, 4, 11, '80.00', '45.00'),
(9, 5, 7, '35.00', '145.00'),
(10, 6, 8, '5.00', '320.00'),
(11, 6, 9, '3.00', '180.00'),
(12, 7, 6, '12.00', '38.50'),
(13, 7, 6, '12.00', '38.50'),
(14, 7, 6, '12.00', '38.50'),
(15, 7, 6, '12.00', '38.50'),
(16, 7, 6, '12.00', '38.50'),
(17, 7, 6, '12.00', '38.50'),
(18, 7, 6, '12.00', '38.50'),
(19, 7, 6, '12.00', '38.50'),
(20, 7, 6, '1.00', '38.50'),
(21, 7, 6, '1.00', '38.50'),
(22, 7, 6, '1.00', '38.50'),
(23, 8, 7, '1.00', '145.00'),
(24, 8, 7, '1.00', '145.00');

-- --------------------------------------------------------

--
-- Table structure for table `production_processes`
--

CREATE TABLE `production_processes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('planned','in_progress','completed','canceled') NOT NULL,
  `manager_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_processes`
--

INSERT INTO `production_processes` (`id`, `product_id`, `quantity`, `started_at`, `completed_at`, `status`, `manager_id`, `notes`) VALUES
(1, 1, '50.00', '2025-05-10 05:00:00', '2025-05-10 13:00:00', 'completed', 2, 'Партія №24534 - Домашня класична'),
(2, 2, '30.00', '2025-05-11 05:00:00', '2025-05-11 12:00:00', 'completed', 2, 'Партія №24535 - Краківська'),
(3, 5, '40.00', '2025-05-12 06:00:00', '2025-05-12 11:00:00', 'completed', 3, 'Партія №24536 - Молочні сосиски'),
(4, 1, '75.00', '2025-05-15 05:00:00', NULL, 'in_progress', 2, 'Партія №24537 - Домашня класична'),
(5, 3, '60.00', '2025-05-16 07:00:00', NULL, 'in_progress', 3, 'Партія №24538 - Селянська з салом'),
(6, 2, '80.00', '2025-05-22 05:00:00', NULL, 'planned', 2, 'Партія №24539 - Краківська оригінальна'),
(7, 4, '45.00', '2025-05-23 06:00:00', NULL, 'planned', 3, 'Партія №24540 - Угорська з папрікою'),
(8, 5, '100.00', '2025-05-24 05:00:00', NULL, 'planned', 2, 'Партія №24541 - Молочні сосиски (велика партія)');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `recipe_id` int(11) NOT NULL,
  `weight` decimal(10,2) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `recipe_id`, `weight`, `price`, `created_at`) VALUES
(1, 'Ковбаса \"Домашня класична\"', 'Традиційна українська ковбаса з ароматом часнику та чорного перцю', 1, '1.00', '195.00', '2025-05-22 10:45:29'),
(2, 'Ковбаса \"Краківська оригінальна\"', 'Пікантна ковбаса з яловичиною та свининою за польським рецептом', 2, '1.00', '225.00', '2025-05-22 10:45:29'),
(3, 'Ковбаса \"Селянська з салом\"', 'Домашня ковбаса з додаванням сала-шпика, особливо соковита', 3, '1.00', '185.00', '2025-05-22 10:45:29'),
(4, 'Ковбаса \"Угорська з папрікою\"', 'Ароматна ковбаса з папрікою та кмином, має пікантний смак', 4, '1.00', '210.00', '2025-05-22 10:45:29'),
(5, 'Сосиски \"Молочні\"', 'Ніжні сосиски для дітей та дорослих з натуральним молоком', 5, '0.50', '125.00', '2025-05-22 10:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `quality_checks`
--

CREATE TABLE `quality_checks` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `technologist_id` int(11) NOT NULL,
  `check_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `ph_level` decimal(4,2) DEFAULT NULL,
  `moisture_content` decimal(5,2) DEFAULT NULL,
  `visual_assessment` text DEFAULT NULL,
  `smell_assessment` text DEFAULT NULL,
  `texture_assessment` text DEFAULT NULL,
  `overall_grade` enum('excellent','good','satisfactory','unsatisfactory') DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quality_checks`
--

INSERT INTO `quality_checks` (`id`, `order_id`, `technologist_id`, `check_date`, `status`, `notes`, `temperature`, `ph_level`, `moisture_content`, `visual_assessment`, `smell_assessment`, `texture_assessment`, `overall_grade`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 8, '2025-05-05 07:30:00', 'approved', 'М\'ясо відповідає всім стандартам якості', '2.50', '5.80', '74.20', 'Колір рожево-червоний, без пошкоджень', 'Свіжий м\'ясний запах', 'Пружна консистенція', 'excellent', NULL, '2025-05-22 10:45:29', '2025-05-22 10:45:29'),
(2, 2, 8, '2025-05-06 08:15:00', 'approved', 'Спеції високої якості, зберігання належне', NULL, NULL, '8.50', 'Колір насичений, без сторонніх включень', 'Характерний ароматний запах', 'Однорідна структура', 'good', NULL, '2025-05-22 10:45:29', '2025-05-22 10:45:29'),
(3, 3, 9, '2025-05-07 06:45:00', 'approved', 'Оболонки відповідають ДСТУ', NULL, NULL, NULL, 'Прозорі, без механічних пошкоджень', 'Без стороннього запаху', 'Еластичні, міцні', 'excellent', NULL, '2025-05-22 10:45:29', '2025-05-22 10:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `quality_check_items`
--

CREATE TABLE `quality_check_items` (
  `id` int(11) NOT NULL,
  `quality_check_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `quantity_checked` decimal(10,2) NOT NULL,
  `status` enum('approved','rejected','conditional') NOT NULL,
  `notes` text DEFAULT NULL,
  `defects_found` text DEFAULT NULL,
  `grade` enum('A','B','C','D') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quality_check_items`
--

INSERT INTO `quality_check_items` (`id`, `quality_check_id`, `raw_material_id`, `quantity_checked`, `status`, `notes`, `defects_found`, `grade`) VALUES
(1, 1, 1, '180.00', 'approved', 'Свинина відповідає стандартам', '', 'A'),
(2, 1, 2, '80.00', 'approved', 'Яловичина першого сорту', '', 'A'),
(3, 2, 3, '60.00', 'approved', 'Сіль екстра класу', '', 'A'),
(4, 2, 4, '8.00', 'approved', 'Перець ароматний', '', 'A'),
(5, 2, 5, '10.00', 'approved', 'Часник свіжий', '', 'B'),
(6, 3, 6, '50.00', 'approved', 'Оболонки натуральні', '', 'A');

-- --------------------------------------------------------

--
-- Table structure for table `quality_standards`
--

CREATE TABLE `quality_standards` (
  `id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `parameter_name` varchar(100) NOT NULL,
  `min_value` decimal(10,3) DEFAULT NULL,
  `max_value` decimal(10,3) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_critical` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quality_standards`
--

INSERT INTO `quality_standards` (`id`, `raw_material_id`, `parameter_name`, `min_value`, `max_value`, `unit`, `description`, `is_critical`, `created_at`) VALUES
(1, 1, 'Температура', '-2.000', '4.000', '°C', 'Температура охолодженої свинини при прийманні', 1, '2025-05-22 10:45:29'),
(2, 1, 'pH', '5.300', '6.200', '', 'Кислотність м\'яса свиняче', 1, '2025-05-22 10:45:29'),
(3, 1, 'Вологість', '70.000', '78.000', '%', 'Вміст вологи в м\'ясі', 0, '2025-05-22 10:45:29'),
(4, 1, 'Жирність', '15.000', '35.000', '%', 'Вміст жиру в м\'ясі', 0, '2025-05-22 10:45:29'),
(5, 2, 'Температура', '-2.000', '4.000', '°C', 'Температура охолодженої яловичини при прийманні', 1, '2025-05-22 10:45:29'),
(6, 2, 'pH', '5.400', '6.000', '', 'Кислотність м\'яса яловиче', 1, '2025-05-22 10:45:29'),
(7, 2, 'Вологість', '72.000', '76.000', '%', 'Вміст вологи в м\'ясі', 0, '2025-05-22 10:45:29'),
(8, 2, 'Білок', '18.000', '22.000', '%', 'Вміст білка в м\'ясі', 0, '2025-05-22 10:45:29'),
(9, 3, 'Вологість', '0.000', '5.000', '%', 'Вміст вологи в солі', 0, '2025-05-22 10:45:29'),
(10, 3, 'Чистота', '99.000', '100.000', '%', 'Процент чистоти солі', 1, '2025-05-22 10:45:29'),
(11, 3, 'Розмір кристалів', '0.200', '0.800', 'мм', 'Розмір кристалів солі', 0, '2025-05-22 10:45:29'),
(12, 4, 'Вологість', '0.000', '12.000', '%', 'Вміст вологи в перці', 0, '2025-05-22 10:45:29'),
(13, 4, 'Ефірні олії', '1.000', '4.000', '%', 'Вміст ефірних олій', 1, '2025-05-22 10:45:29'),
(14, 5, 'Вологість', '60.000', '70.000', '%', 'Вміст вологи в часнику', 0, '2025-05-22 10:45:29'),
(15, 5, 'Сухі речовини', '30.000', '40.000', '%', 'Вміст сухих речовин', 0, '2025-05-22 10:45:29'),
(16, 6, 'Товщина стінки', '0.050', '0.150', 'мм', 'Товщина стінки оболонки', 1, '2025-05-22 10:45:29'),
(17, 6, 'Калібр', '32.000', '35.000', 'мм', 'Діаметр оболонки', 1, '2025-05-22 10:45:29'),
(18, 7, 'Вологість', '0.000', '5.000', '%', 'Вміст вологи в сухому молоці', 1, '2025-05-22 10:45:29'),
(19, 7, 'Білок', '32.000', '36.000', '%', 'Вміст білка в сухому молоці', 1, '2025-05-22 10:45:29'),
(20, 7, 'Жир', '0.000', '1.500', '%', 'Вміст жиру (знежирене)', 0, '2025-05-22 10:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `raw_materials`
--

CREATE TABLE `raw_materials` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `min_stock` decimal(10,2) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `raw_materials`
--

INSERT INTO `raw_materials` (`id`, `name`, `description`, `unit`, `price_per_unit`, `min_stock`, `supplier_id`, `created_at`) VALUES
(1, 'Свинина охолоджена', 'М\'ясо свиняче охолоджене, вищий сорт, з лопаткової частини', 'кг', '95.50', '100.00', 4, '2025-05-22 10:45:29'),
(2, 'Яловичина охолоджена', 'М\'ясо яловиче охолоджене, вищий сорт, з грудної частини', 'кг', '125.75', '80.00', 4, '2025-05-22 10:45:29'),
(3, 'Сіль харчова', 'Сіль кухонна екстра, помол №0', 'кг', '12.50', '50.00', 5, '2025-05-22 10:45:29'),
(4, 'Перець чорний мелений', 'Перець чорний мелений, вищий сорт', 'кг', '280.00', '5.00', 5, '2025-05-22 10:45:29'),
(5, 'Часник свіжий', 'Часник свіжий, перший сорт', 'кг', '85.00', '10.00', 5, '2025-05-22 10:45:29'),
(6, 'Кишки свинячі', 'Оболонка натуральна свиняча, калібр 32-35 мм', 'м', '38.50', '200.00', 6, '2025-05-22 10:45:29'),
(7, 'Молоко сухе знежирене', 'Молоко сухе знежирене, білковість 34%', 'кг', '145.00', '25.00', 7, '2025-05-22 10:45:29'),
(8, 'Папріка солодка', 'Папріка солодка мелена, вищий сорт', 'кг', '320.00', '3.00', 5, '2025-05-22 10:45:29'),
(9, 'Кмин', 'Кмин цілий, вищий сорт', 'кг', '180.00', '2.00', 5, '2025-05-22 10:45:29'),
(10, 'Селітра харчова', 'Селітра харчова (нітрит натрію)', 'кг', '450.00', '1.00', 5, '2025-05-22 10:45:29'),
(11, 'Сало-шпик', 'Сало-шпик свиняче, твердий', 'кг', '45.00', '50.00', 4, '2025-05-22 10:45:29'),
(12, 'Цукор', 'Цукор білий кристалічний', 'кг', '22.00', '20.00', 5, '2025-05-22 10:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `name`, `description`, `created_by`, `created_at`) VALUES
(1, 'Ковбаса \"Домашня класична\"', 'Традиційна українська ковбаса з часником та перцем, рецепт ДСТУ', 1, '2025-05-22 10:45:29'),
(2, 'Ковбаса \"Краківська оригінальна\"', 'Польська ковбаса з яловичиною та свининою за традиційним рецептом', 1, '2025-05-22 10:45:29'),
(3, 'Ковбаса \"Селянська з салом\"', 'Домашня ковбаса з додаванням сала-шпика та часнику', 1, '2025-05-22 10:45:29'),
(4, 'Ковбаса \"Угорська з папрікою\"', 'Пікантна ковбаса з папрікою та кмином', 1, '2025-05-22 10:45:29'),
(5, 'Сосиски \"Молочні\"', 'Ніжні сосиски з додаванням сухого молока', 1, '2025-05-22 10:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_ingredients`
--

CREATE TABLE `recipe_ingredients` (
  `id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipe_ingredients`
--

INSERT INTO `recipe_ingredients` (`id`, `recipe_id`, `raw_material_id`, `quantity`) VALUES
(1, 1, 1, '0.75'),
(2, 1, 3, '0.03'),
(3, 1, 4, '0.01'),
(4, 1, 5, '0.02'),
(5, 1, 6, '0.25'),
(6, 1, 10, '0.00'),
(7, 2, 1, '0.45'),
(8, 2, 2, '0.45'),
(9, 2, 3, '0.02'),
(10, 2, 4, '0.01'),
(11, 2, 6, '0.25'),
(12, 2, 10, '0.00'),
(13, 3, 1, '0.60'),
(14, 3, 11, '0.25'),
(15, 3, 3, '0.02'),
(16, 3, 4, '0.01'),
(17, 3, 5, '0.02'),
(18, 3, 6, '0.25'),
(19, 3, 10, '0.00'),
(20, 4, 1, '0.70'),
(21, 4, 2, '0.20'),
(22, 4, 3, '0.02'),
(23, 4, 8, '0.02'),
(24, 4, 9, '0.01'),
(25, 4, 6, '0.25'),
(26, 4, 10, '0.00'),
(27, 5, 1, '0.65'),
(28, 5, 7, '0.08'),
(29, 5, 3, '0.02'),
(30, 5, 12, '0.01'),
(31, 5, 6, '0.30'),
(32, 5, 10, '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','warehouse_manager','supplier','technologist') NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `email`, `phone`, `created_at`) VALUES
(1, 'admin', '$2y$10$GGtluqS9L743SiLKH42yCOEbv51yEIFbWVpa6iATFy69UtLMJym4m', 'admin', 'Адміністратор системи', 'admin@sausage.ua', '+380671234567', '2025-05-22 10:45:29'),
(2, 'warehouse1', '$2y$10$HUDlVxB6fFY.Y7jM2S7DNu.Cf9arn5.Qg.A7U9yS2DdbLWNr6/B4K', 'warehouse_manager', 'Петренко Олександр Іванович', 'warehouse@sausage.ua', '+380672345678', '2025-05-22 10:45:29'),
(3, 'warehouse2', '$2y$10$HUDlVxB6fFY.Y7jM2S7DNu.Cf9arn5.Qg.A7U9yS2DdbLWNr6/B4K', 'warehouse_manager', 'Іваненко Марія Петрівна', 'warehouse2@sausage.ua', '+380673456789', '2025-05-22 10:45:29'),
(4, 'supplier1', '$2y$10$XI38l1h6ZYV0hH/ngtAZOeAt96hYekwZGoX.s1bLcW8n1G4ARt3na', 'supplier', 'ТОВ \"М\'ясопостач Україна\"', 'meat@supplier.ua', '+380674567890', '2025-05-22 10:45:29'),
(5, 'supplier2', '$2y$10$vL6nVlFucxcY6/ncFJWBXO81axL5xvPdy2kXgbEQFhuUgT2yKSDHi', 'supplier', 'ТОВ \"Спеції та приправи\"', 'spices@supplier.ua', '+380675678901', '2025-05-22 10:45:29'),
(6, 'supplier3', '$2y$10$vL6nVlFucxcY6/ncFJWBXO81axL5xvPdy2kXgbEQFhuUgT2yKSDHi', 'supplier', 'ПП \"Натуральні оболонки\"', 'casings@supplier.ua', '+380676789012', '2025-05-22 10:45:29'),
(7, 'supplier4', '$2y$10$vL6nVlFucxcY6/ncFJWBXO81axL5xvPdy2kXgbEQFhuUgT2yKSDHi', 'supplier', 'ТОВ \"Молочні продукти Київ\"', 'dairy@supplier.ua', '+380677890123', '2025-05-22 10:45:29'),
(8, 'technologist1', '$2y$10$C8m8WN0YnruV/H8T1CXTFuc6fOHKIpRBAeWXnAVaKYl6Bx1LRKXn2', 'technologist', 'Кравченко Іван Сергійович', 'technologist@sausage.ua', '+380678901234', '2025-05-22 10:45:29'),
(9, 'technologist2', '$2y$10$C8m8WN0YnruV/H8T1CXTFuc6fOHKIpRBAeWXnAVaKYl6Bx1LRKXn2', 'technologist', 'Мельник Олена Володимирівна', 'technologist2@sausage.ua', '+380679012345', '2025-05-22 10:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `video_surveillance`
--

CREATE TABLE `video_surveillance` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `location` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `video_surveillance`
--

INSERT INTO `video_surveillance` (`id`, `name`, `url`, `location`, `status`, `created_at`) VALUES
(1, 'Камера виробничого цеху №1', 'rtsp://192.168.1.101:554/stream1', 'Виробничий цех №1 (основне відділення)', 'active', '2025-05-22 10:45:29'),
(2, 'Камера виробничого цеху №2', 'rtsp://192.168.1.102:554/stream1', 'Виробничий цех №2 (пакування)', 'active', '2025-05-22 10:45:29'),
(3, 'Камера складу сировини', 'rtsp://192.168.1.103:554/stream1', 'Склад сировини (основний зал)', 'active', '2025-05-22 10:45:29'),
(4, 'Камера холодильної камери', 'rtsp://192.168.1.104:554/stream1', 'Холодильна камера для м\'яса', 'active', '2025-05-22 10:45:29'),
(5, 'Камера складу готової продукції', 'rtsp://192.168.1.105:554/stream1', 'Склад готової продукції', 'active', '2025-05-22 10:45:29'),
(6, 'Камера зони завантаження', 'rtsp://192.168.1.106:554/stream1', 'Зона завантаження/розвантаження', 'active', '2025-05-22 10:45:29'),
(7, 'Камера адміністративної зони', 'rtsp://192.168.1.107:554/stream1', 'Адміністративні приміщення', 'active', '2025-05-22 10:45:29'),
(8, 'Камера лабораторії якості', 'rtsp://192.168.1.108:554/stream1', 'Лабораторія контролю якості', 'active', '2025-05-22 10:45:29'),
(9, 'Камера коридору №1', 'rtsp://192.168.1.109:554/stream1', 'Головний коридор', 'inactive', '2025-05-22 10:45:29'),
(10, 'Камера території заводу', 'rtsp://192.168.1.110:554/stream1', 'Зовнішня територія підприємства', 'active', '2025-05-22 10:45:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `raw_material_id` (`raw_material_id`),
  ADD KEY `warehouse_manager_id` (`warehouse_manager_id`),
  ADD KEY `idx_inventory_quantity` (`quantity`),
  ADD KEY `idx_inventory_last_updated` (`last_updated`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_receiver_read` (`receiver_id`,`is_read`),
  ADD KEY `idx_messages_sender_date` (`sender_id`,`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordered_by` (`ordered_by`),
  ADD KEY `idx_orders_supplier_status` (`supplier_id`,`status`),
  ADD KEY `idx_orders_delivery_date` (`delivery_date`),
  ADD KEY `idx_orders_quality_status` (`quality_status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `raw_material_id` (`raw_material_id`);

--
-- Indexes for table `production_processes`
--
ALTER TABLE `production_processes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `manager_id` (`manager_id`),
  ADD KEY `idx_production_status_date` (`status`,`started_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- Indexes for table `quality_checks`
--
ALTER TABLE `quality_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_quality_checks_status_date` (`status`,`check_date`),
  ADD KEY `idx_quality_checks_technologist` (`technologist_id`,`check_date`);

--
-- Indexes for table `quality_check_items`
--
ALTER TABLE `quality_check_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quality_check_id` (`quality_check_id`),
  ADD KEY `raw_material_id` (`raw_material_id`);

--
-- Indexes for table `quality_standards`
--
ALTER TABLE `quality_standards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_standard` (`raw_material_id`,`parameter_name`);

--
-- Indexes for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`),
  ADD KEY `raw_material_id` (`raw_material_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `video_surveillance`
--
ALTER TABLE `video_surveillance`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `production_processes`
--
ALTER TABLE `production_processes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quality_checks`
--
ALTER TABLE `quality_checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quality_check_items`
--
ALTER TABLE `quality_check_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quality_standards`
--
ALTER TABLE `quality_standards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `video_surveillance`
--
ALTER TABLE `video_surveillance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`),
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`warehouse_manager_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`ordered_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`);

--
-- Constraints for table `production_processes`
--
ALTER TABLE `production_processes`
  ADD CONSTRAINT `production_processes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `production_processes_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`);

--
-- Constraints for table `quality_checks`
--
ALTER TABLE `quality_checks`
  ADD CONSTRAINT `quality_checks_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quality_checks_ibfk_2` FOREIGN KEY (`technologist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quality_check_items`
--
ALTER TABLE `quality_check_items`
  ADD CONSTRAINT `quality_check_items_ibfk_1` FOREIGN KEY (`quality_check_id`) REFERENCES `quality_checks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quality_check_items_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quality_standards`
--
ALTER TABLE `quality_standards`
  ADD CONSTRAINT `quality_standards_ibfk_1` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD CONSTRAINT `raw_materials_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD CONSTRAINT `recipe_ingredients_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipe_ingredients_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- 1. Добавляем поле "Кількість по факту"
ALTER TABLE inventory ADD COLUMN actual_quantity DECIMAL(10,2) DEFAULT NULL COMMENT 'Фактическая количество при инвентаризации';

-- 2. Добавляем поле штрих-кода
ALTER TABLE inventory ADD COLUMN barcode VARCHAR(100) DEFAULT NULL COMMENT 'Штрих-код товара';

-- 3. Добавляем индекс для штрих-кода для быстрого поиска
ALTER TABLE inventory ADD INDEX idx_inventory_barcode (barcode);

-- 4. Обновляем существующие записи - устанавливаем фактическое количество равным текущему
UPDATE inventory SET actual_quantity = quantity WHERE actual_quantity IS NULL;

-- 5. Добавляем тестовые штрих-коды для существующих записей
UPDATE inventory SET barcode = CONCAT('BC', LPAD(raw_material_id, 6, '0')) WHERE barcode IS NULL;