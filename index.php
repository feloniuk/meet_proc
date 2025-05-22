<?php
// Подключение файла настроек
require_once 'config/config.php';

// Включаем отображение ошибок для отладки (убрать в продакшене)
if (defined('DEBUG') && DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Получение URL-адреса запроса
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

// Обработка адреса
$segments = explode('/', $url);

// Определение контроллера
$controller = !empty($segments[0]) ? ucfirst($segments[0]) . 'Controller' : 'HomeController';

// Определение метода
$method = !empty($segments[1]) ? $segments[1] : 'index';

// Определение параметров
$params = array_slice($segments, 2);

// Специальная обработка для роли технолога
if (!empty($segments[0]) && $segments[0] === 'technologist') {
    $controller = 'TechnologistController';
}

// Логирование для отладки
if (Auth::isLoggedIn()) {
    $user_role = Auth::getCurrentUserRole();
    $user_id = Auth::getCurrentUserId();
    Util::log("Request: $url, Controller: $controller, Method: $method, User ID: $user_id, Role: $user_role");
} else {
    Util::log("Request: $url, Controller: $controller, Method: $method, User: Not logged in");
}

// Поиск контроллера
$controller_file = CONTROLLERS_PATH . '/' . $controller . '.php';

// Проверка наличия контроллера
if (!file_exists($controller_file)) {
    // Перенаправление на авторизацию если контроллер не найден
    if ($controller !== 'HomeController' && $controller !== 'AuthController') {
        Util::log("Controller not found: $controller, redirecting to login", 'error');
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    } elseif ($controller !== 'AuthController') {
        $controller = 'AuthController';
        $method = 'login';
        $params = [];
        $controller_file = CONTROLLERS_PATH . '/' . $controller . '.php';
    } else {
        die('Контроллер не знайдено: ' . $controller);
    }
}

// Подключение контроллера
require_once $controller_file;

// Проверка существования класса контроллера
if (!class_exists($controller)) {
    Util::log("Controller class not found: $controller", 'error');
    die('Клас контроллера не знайдено: ' . $controller);
}

try {
    // Создание экземпляра контроллера
    $controllerInstance = new $controller();
    
    // Проверка существования метода
    if (!method_exists($controllerInstance, $method)) {
        // Используем метод index если метод не найден
        if ($method !== 'index') {
            Util::log("Method not found: $method in controller: $controller, redirecting to index", 'warning');
            header('Location: ' . BASE_URL . '/' . strtolower(str_replace('Controller', '', $controller)));
            exit;
        } else {
            throw new Exception('Метод не знайдено: ' . $method);
        }
    }
    
    // Создаем буфер вывода
    ob_start();
    
    // Вызов метода контроллера с параметрами
    call_user_func_array([$controllerInstance, $method], $params);
    
    // Получаем содержимое буфера
    $content = ob_get_clean();
    
} catch (Exception $e) {
    // Логируем ошибку
    Util::log('Controller error: ' . $e->getMessage(), 'error');
    
    // Очищаем буфер если есть
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Если пользователь не авторизован - перенаправляем на логин
    if (!Auth::isLoggedIn()) {
        $_SESSION['error'] = 'Необхідна авторизація для доступу до системи';
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }
    
    // Иначе показываем ошибку
    $_SESSION['error'] = 'Помилка при виконанні запиту: ' . $e->getMessage();
    $content = '<div class="alert alert-danger">Помилка при виконанні запиту. Зверніться до адміністратора.</div>';
}

// Определяем вид шаблона в зависимости от контроллера
if ($controller === 'AuthController') {
    // Для страниц авторизации используем auth_layout.php
    include VIEWS_PATH . '/layouts/auth_layout.php';
    
} else {
    // Подготовка данных для шаблона
    $user_role = Auth::getCurrentUserRole();
    $user_name = Auth::getCurrentUserName();
    $user_id = Auth::getCurrentUserId();
    
    // Проверяем корректность роли
    if (Auth::isLoggedIn() && !in_array($user_role, ['admin', 'warehouse_manager', 'supplier', 'technologist'])) {
        Util::log("Invalid user role detected: $user_role for user ID: $user_id", 'error');
        $_SESSION['error'] = 'Некоректна роль користувача. Зверніться до адміністратора.';
        Auth::logout();
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }
    
    // Подключение главного шаблона
    include VIEWS_PATH . '/layouts/main.php';
}
?>