<?php
class Auth {
    
    public static function login($username, $password) {
        $db = Database::getInstance();
        
        // Ищем пользователя по имени пользователя
        $sql = "SELECT * FROM users WHERE username = ?";
        $user = $db->single($sql, [$username]);
        
        // Проверяем, найден ли пользователь и правильный ли пароль
        if ($user && password_verify($password, $user['password'])) {
            // Сохраняем информацию о пользователе в сессии
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            
            return true;
        }
        
        return false;
    }
    
    public static function logout() {
        // Очищаем все данные сессии
        session_unset();
        session_destroy();
        
        // Запускаем новую сессию для flash-сообщений
        session_start();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public static function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    public static function getCurrentUserName() {
        return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Гость';
    }
    
    public static function getCurrentUserRole() {
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    }
    
    
    public static function hasRole($role) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $userRole = self::getCurrentUserRole();
        
        // Админ имеет доступ ко всему
        if ($userRole === 'admin') {
            return true;
        }
        
        // Проверяем конкретную роль
        return $userRole === $role;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            $_SESSION['error'] = 'Для доступу до цієї сторінки необхідно увійти в систему';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    }
    
    public static function requireRole($role) {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            $_SESSION['error'] = 'У вас немає доступу до цієї сторінки';
            header('Location: ' . BASE_URL . '/home');
            exit;
        }
    }
    
    public static function checkPermission($controller, $method) {
        // Проверяем, требует ли страница авторизации
        $publicPages = [
            'AuthController' => ['login', 'register', 'index'],
            'HomeController' => ['index'] // Только если это главная страница для неавторизованных
        ];
        
        // Если страница публичная, пропускаем проверку
        if (isset($publicPages[$controller]) && in_array($method, $publicPages[$controller])) {
            return true;
        }
        
        // Для всех остальных страниц требуется авторизация
        self::requireLogin();
        
        // Проверяем права доступа по ролям
        $permissions = [
            'AdminController' => ['admin'],
            'WarehouseController' => ['admin', 'warehouse_manager'],
            'SupplierController' => ['admin', 'supplier'],
            'TechnologistController' => ['admin', 'technologist']
        ];
        
        // Особые разрешения для начальника склада на функции заказов
        if ($controller === 'AdminController' && in_array($method, ['orders', 'createOrder', 'editOrder', 'viewOrder', 'addOrderItem', 'deliverOrder', 'cancelOrder'])) {
            $userRole = self::getCurrentUserRole();
            if ($userRole === 'warehouse_manager') {
                return true; // Разрешаем начальнику склада доступ к заказам
            }
        }
        
        if (isset($permissions[$controller])) {
            $userRole = self::getCurrentUserRole();
            
            if (!in_array($userRole, $permissions[$controller])) {
                $_SESSION['error'] = 'У вас немає доступу до цієї сторінки';
                header('Location: ' . BASE_URL . '/home');
                exit;
            }
        }
        
        return true;
    }
    
    // Метод для получения данных текущего пользователя
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE id = ?";
        return $db->single($sql, [self::getCurrentUserId()]);
    }
    
    // Метод для обновления данных пользователя в сессии
    public static function updateSessionUser($userData) {
        if (self::isLoggedIn() && isset($userData['id']) && $userData['id'] == self::getCurrentUserId()) {
            $_SESSION['user_name'] = $userData['name'];
            $_SESSION['username'] = $userData['username'];
            // Роль обычно не меняется, но на всякий случай
            if (isset($userData['role'])) {
                $_SESSION['user_role'] = $userData['role'];
            }
        }
    }
    
    // Метод для проверки текущего пароля
    public static function verifyCurrentPassword($password) {
        $user = self::getCurrentUser();
        return $user && password_verify($password, $user['password']);
    }
}