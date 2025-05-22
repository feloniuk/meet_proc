<?php
// Подключение файла настроек
require_once 'config/config.php';

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

// Поиск контроллера
$controller_file = CONTROLLERS_PATH . '/' . $controller . '.php';

// Проверка наличия контроллера
if (!file_exists($controller_file)) {
    // Перенаправление на авторизацию если контроллер не найден
    if ($controller !== 'HomeController' && $controller !== 'AuthController') {
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    } elseif ($controller !== 'AuthController') {
        $controller = 'AuthController';
        $method = 'login';
        $params = [];
        $controller_file = CONTROLLERS_PATH . '/' . $controller . '.php';
    } else {
        die('Контроллер не найдено: ' . $controller);
    }
}

// Подключение контроллера
require_once $controller_file;

// Создание экземпляра контроллера
$controllerInstance = new $controller();

// Проверка существования метода
if (!method_exists($controllerInstance, $method)) {
    // Используем метод index если метод не найден
    if ($method !== 'index') {
        header('Location: ' . BASE_URL . '/' . strtolower(str_replace('Controller', '', $controller)));
        exit;
    } else {
        die('Метод не найдено: ' . $method);
    }
}

// Создаем буфер вывода
ob_start();

// Вызов метода контроллера с параметрами
call_user_func_array([$controllerInstance, $method], $params);

// Получаем содержимое буфера
$content = ob_get_clean();

// Определяем вид шаблона в зависимости от контроллера
if ($controller === 'AuthController') {
    // Для страниц авторизации используем auth_layout.php
    include VIEWS_PATH . '/layouts/auth_layout.php';
} else {
    // Подготовка данных для шаблона
    $user_role = Auth::getCurrentUserRole();
    
    // Подключение главного шаблона
    include VIEWS_PATH . '/layouts/main.php';
}
?>