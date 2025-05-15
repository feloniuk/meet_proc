<?php
// Підключення файлу налаштувань
require_once 'config/config.php';

// Отримання URL-адреси запиту
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

// Обробка адреси
$segments = explode('/', $url);

// Визначення контролера
$controller = !empty($segments[0]) ? ucfirst($segments[0]) . 'Controller' : 'HomeController';

// Визначення методу
$method = !empty($segments[1]) ? $segments[1] : 'index';

// Визначення параметрів
$params = array_slice($segments, 2);

// Пошук контролера
$controller_file = CONTROLLERS_PATH . '/' . $controller . '.php';

// Визначаємо змінні для шаблону
$view_file = null;
$title = 'Автоматизація забезпечення виробництва ковбасної продукції';
$data = [];

// Перевірка наявності контролера
if (!file_exists($controller_file)) {
    // Перенаправлення на авторизацію якщо контролер не знайдено
    if ($controller !== 'HomeController' && $controller !== 'AuthController') {
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    } elseif ($controller !== 'AuthController') {
        $controller = 'AuthController';
        $method = 'login';
        $params = [];
        $controller_file = CONTROLLERS_PATH . '/' . $controller . '.php';
    } else {
        die('Контролер не знайдено: ' . $controller);
    }
}

// Підключення контролера
require_once $controller_file;

// Для відладки (можна видалити в продакшені)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Створення екземпляру контролера
$controllerInstance = new $controller();

// Перевірка існування методу
if (!method_exists($controllerInstance, $method)) {
    // Використовуємо метод index якщо метод не знайдено
    if ($method !== 'index') {
        header('Location: ' . BASE_URL . '/' . strtolower(str_replace('Controller', '', $controller)));
        exit;
    } else {
        die('Метод не знайдено: ' . $method);
    }
}

// Створюємо буфер виводу
ob_start();

// Виклик методу контролера з параметрами
call_user_func_array([$controllerInstance, $method], $params);

// Отримуємо вміст буфера
$content = ob_get_clean();

// Визначаємо вид шаблону в залежності від контролера
if ($controller === 'AuthController') {
    // Для сторінок авторизації не використовуємо головний шаблон
    echo $content;
} else {
    // Підготовка даних для шаблону
    $user_role = Auth::getCurrentUserRole();
    
    // Підключення головного шаблону
    include VIEWS_PATH . '/layouts/main.php';
}