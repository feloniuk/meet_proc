<?php
// Налаштування часового поясу
date_default_timezone_set('Europe/Kiev');

// Налаштування підключення до бази даних
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sausage_production_db');

// Шляхи до директорій
define('BASE_PATH', dirname(__DIR__));
define('CONTROLLERS_PATH', BASE_PATH . '/controllers');
define('MODELS_PATH', BASE_PATH . '/models');
define('VIEWS_PATH', BASE_PATH . '/views');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('BASE_NAME', 'Виробництво ковбасної продукції');

// URL сайту - замените на свой домен
define('BASE_URL', 'http://meet_proc.loc');

// Налаштування сесії
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Змінити на 1 для HTTPS
session_start();

// Налаштування для PDF
define('PDF_FONT', 'dejavusans');
define('PDF_FONT_SIZE', 10);

// Функція автозавантаження класів
spl_autoload_register(function ($class_name) {
    // Конвертуємо ім'я класу в шлях до файлу
    $parts = explode('\\', $class_name);
    $class = end($parts);
    
    // Пошук в директорії моделей
    if (file_exists(MODELS_PATH . '/' . $class . '.php')) {
        require_once MODELS_PATH . '/' . $class . '.php';
        return;
    }
    
    // Пошук в директорії контролерів
    if (file_exists(CONTROLLERS_PATH . '/' . $class . '.php')) {
        require_once CONTROLLERS_PATH . '/' . $class . '.php';
        return;
    }
    
    // Пошук в директорії включень
    if (file_exists(INCLUDES_PATH . '/' . $class . '.php')) {
        require_once INCLUDES_PATH . '/' . $class . '.php';
        return;
    }
});