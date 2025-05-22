<?php
// Налаштування часового поясу
date_default_timezone_set('Europe/Kiev');

// ДОБАВЛЕНО: Режим отладки
define('DEBUG', true); // Установите false в продакшене

// Налаштування підключення до бази даних
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'prod_meet');

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

// ДОБАВЛЕНО: Инициализация обработчика ошибок
require_once INCLUDES_PATH . '/ErrorHandler.php';
ErrorHandler::init();

// Налаштування сесії
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Змінити на 1 для HTTPS
session_start();

// Налаштування для PDF
define('PDF_FONT', 'dejavusans');
define('PDF_FONT_SIZE', 10);

// ИСПРАВЛЕНО: Функція автозавантаження класів
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

// Подключение необходимых классов
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';
require_once INCLUDES_PATH . '/Util.php';

// Проверяем существование PDF класса
if (file_exists(INCLUDES_PATH . '/PDF.php')) {
    require_once INCLUDES_PATH . '/PDF.php';
}

// ИСПРАВЛЕНО: Подключение необходимых классов с проверкой
$requiredFiles = [
    INCLUDES_PATH . '/Database.php',
    INCLUDES_PATH . '/Auth.php',
    INCLUDES_PATH . '/Util.php',
    INCLUDES_PATH . '/PDF.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("Required file not found: " . $file);
        if (DEBUG) {
            die("Required file not found: " . $file);
        }
    }
}

// ДОБАВЛЕНО: Проверка подключения к базе данных
try {
    $db = Database::getInstance();
    if (!$db->isConnected()) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    if (DEBUG) {
        die("Database error: " . $e->getMessage());
    } else {
        die("Помилка підключення до бази даних");
    }
}

// ДОБАВЛЕНО: Проверка существования необходимых таблиц
$requiredTables = [
    'users', 'raw_materials', 'inventory', 'recipes', 
    'recipe_ingredients', 'products', 'production_processes', 
    'orders', 'order_items', 'messages', 'video_surveillance'
];

foreach ($requiredTables as $table) {
    if (!$db->tableExists($table)) {
        error_log("Required table missing: " . $table);
        if (DEBUG) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Warning:</strong> Required table '{$table}' is missing from database.";
            echo "</div>";
        }
    }
}

// Проверка наличия TCPDF
if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
    // Если TCPDF не найден, попробуйте альтернативные пути
    $tcpdf_paths = [
        BASE_PATH . '/tcpdf/tcpdf.php',
        BASE_PATH . '/lib/tcpdf/tcpdf.php',
        BASE_PATH . '/libraries/tcpdf/tcpdf.php'
    ];
    
    $tcpdf_found = false;
    foreach ($tcpdf_paths as $path) {
        if (file_exists($path)) {
            define('TCPDF_PATH', dirname($path));
            $tcpdf_found = true;
            break;
        }
    }
    
    if (!$tcpdf_found) {
        // Логируем ошибку если TCPDF не найден
        error_log('TCPDF library not found. Please install TCPDF to enable PDF generation.');
        if (DEBUG) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin: 10px; border: 1px solid #ffeaa7; border-radius: 4px;'>";
            echo "<strong>Notice:</strong> TCPDF library not found. PDF generation will be disabled.";
            echo "</div>";
        }
    }
}

// ДОБАВЛЕНО: Функция для безопасного получения переменных окружения
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

// ДОБАВЛЕНО: Настройки кэширования
define('CACHE_ENABLED', env('CACHE_ENABLED', false));
define('CACHE_TTL', env('CACHE_TTL', 3600)); // 1 час

// ДОБАВЛЕНО: Настройки логирования
define('LOG_LEVEL', env('LOG_LEVEL', 'ERROR'));
define('LOG_FILE', BASE_PATH . '/logs/app.log');

// Создаем директорию для логов если её нет
$logDir = dirname(LOG_FILE);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}