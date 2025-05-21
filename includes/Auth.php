<?php
class Auth {
    // Проверка авторизации
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    // Получение ID текущего пользователя
    public static function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    // Получение имени текущего пользователя
    public static function getCurrentUserName() {
        return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null;
    }
    
    // Получение роли текущего пользователя
    public static function getCurrentUserRole() {
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    }
    
    // Получение данных текущего пользователя
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => self::getCurrentUserId(),
            'name' => self::getCurrentUserName(),
            'role' => self::getCurrentUserRole(),
            'username' => isset($_SESSION['username']) ? $_SESSION['username'] : null,
            'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null
        ];
    }
    
    // Проверка роли пользователя
    public static function hasRole($role) {
        return self::getCurrentUserRole() === $role;
    }
    
    // Проверка прав доступа (пользователь имеет одну из указанных ролей)
    public static function hasAnyRole($roles) {
        $currentRole = self::getCurrentUserRole();
        return in_array($currentRole, $roles);
    }
    
    // Вход пользователя в систему
    public static function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        
        // Логирование входа
        Util::log('User login: ' . $user['username'] . ' (ID: ' . $user['id'] . ')');
        
        return true;
    }
    
    // Выход пользователя из системы
    public static function logout() {
        // Логирование выхода
        if (self::isLoggedIn()) {
            Util::log('User logout: ' . self::getCurrentUserName() . ' (ID: ' . self::getCurrentUserId() . ')');
        }
        
        // Очистка сессии
        session_unset();
        session_destroy();
        
        // Создание новой сессии
        session_start();
        
        return true;
    }
    
    // Проверка пароля
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Хеширование пароля
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Проверка силы пароля
    public static function isStrongPassword($password) {
        // Минимум 6 символов
        if (strlen($password) < 6) {
            return false;
        }
        
        // Содержит буквы и цифры
        if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    // Получение пользователя по ID
    public static function getUserById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE id = ?";
        return $db->single($sql, [$id]);
    }
    
    // Получение пользователя по имени пользователя
    public static function getUserByUsername($username) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE username = ?";
        return $db->single($sql, [$username]);
    }
    
    // Получение пользователя по email
    public static function getUserByEmail($email) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE email = ?";
        return $db->single($sql, [$email]);
    }
    
    // Аутентификация пользователя
    public static function authenticate($username, $password) {
        $user = self::getUserByUsername($username);
        
        if ($user && self::verifyPassword($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    // Регистрация нового пользователя
    public static function register($username, $password, $role, $name, $email, $phone = null) {
        $db = Database::getInstance();
        
        // Проверка уникальности имени пользователя и email
        if (self::getUserByUsername($username)) {
            return ['error' => 'Користувач з таким ім\'ям вже існує'];
        }
        
        if (self::getUserByEmail($email)) {
            return ['error' => 'Користувач з таким email вже існує'];
        }
        
        // Проверка силы пароля
        if (!self::isStrongPassword($password)) {
            return ['error' => 'Пароль повинен містити мінімум 6 символів, включаючи букви та цифри'];
        }
        
        // Хеширование пароля
        $hashedPassword = self::hashPassword($password);
        
        // Создание пользователя
        $sql = "INSERT INTO users (username, password, role, name, email, phone) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        if ($db->query($sql, [$username, $hashedPassword, $role, $name, $email, $phone])) {
            $userId = $db->lastInsertId();
            Util::log('New user registered: ' . $username . ' (ID: ' . $userId . ')');
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['error' => 'Помилка при реєстрації користувача'];
    }
    
    // Обновление профиля пользователя
    public static function updateProfile($userId, $name, $email, $phone = null) {
        $db = Database::getInstance();
        
        // Проверка уникальности email (исключая текущего пользователя)
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $existingUser = $db->single($sql, [$email, $userId]);
        
        if ($existingUser) {
            return ['error' => 'Користувач з таким email вже існує'];
        }
        
        // Обновление данных
        $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        
        if ($db->query($sql, [$name, $email, $phone, $userId])) {
            // Обновление данных в сессии
            if (self::getCurrentUserId() == $userId) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
            }
            
            Util::log('User profile updated: ID ' . $userId);
            return ['success' => true];
        }
        
        return ['error' => 'Помилка при оновленні профілю'];
    }
    
    // Изменение пароля
    public static function changePassword($userId, $oldPassword, $newPassword) {
        $db = Database::getInstance();
        
        // Получение текущего пароля
        $user = self::getUserById($userId);
        if (!$user) {
            return ['error' => 'Користувач не знайдений'];
        }
        
        // Проверка старого пароля
        if (!self::verifyPassword($oldPassword, $user['password'])) {
            return ['error' => 'Неправильний поточний пароль'];
        }
        
        // Проверка силы нового пароля
        if (!self::isStrongPassword($newPassword)) {
            return ['error' => 'Новий пароль повинен містити мінімум 6 символів, включаючи букви та цифри'];
        }
        
        // Хеширование нового пароля
        $hashedPassword = self::hashPassword($newPassword);
        
        // Обновление пароля
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        
        if ($db->query($sql, [$hashedPassword, $userId])) {
            Util::log('Password changed for user ID: ' . $userId);
            return ['success' => true];
        }
        
        return ['error' => 'Помилка при зміні пароля'];
    }
    
    // Генерация токена для восстановления пароля
    public static function generateResetToken($email) {
        $db = Database::getInstance();
        
        $user = self::getUserByEmail($email);
        if (!$user) {
            return ['error' => 'Користувач з таким email не знайдений'];
        }
        
        // Генерация токена
        $token = Util::generateRandomString(32);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Сохранение токена в базе данных (нужно создать таблицу reset_tokens)
        $sql = "INSERT INTO reset_tokens (user_id, token, expires_at) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?";
                
        if ($db->query($sql, [$user['id'], $token, $expires, $token, $expires])) {
            return ['success' => true, 'token' => $token];
        }
        
        return ['error' => 'Помилка при генерації токену'];
    }
    
    // Проверка прав доступа к контроллеру
    public static function checkAccess($requiredRole = null, $requiredRoles = null) {
        if (!self::isLoggedIn()) {
            $_SESSION['error'] = 'Для доступу до цієї сторінки необхідно увійти в систему';
            Util::redirect(BASE_URL . '/auth/login');
        }
        
        if ($requiredRole && !self::hasRole($requiredRole)) {
            $_SESSION['error'] = 'У вас немає прав доступу до цієї сторінки';
            Util::redirect(BASE_URL . '/home');
        }
        
        if ($requiredRoles && !self::hasAnyRole($requiredRoles)) {
            $_SESSION['error'] = 'У вас немає прав доступу до цієї сторінки';
            Util::redirect(BASE_URL . '/home');
        }
    }
    
    // Проверка времени последней активности
    public static function checkSessionTimeout($timeout = 3600) { // 1 час по умолчанию
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $timeout) {
                self::logout(); 
                $_SESSION['error'] = 'Сесія закінчилася через неактивність';
                Util::redirect(BASE_URL . '/auth/login');
            }
        }
        
        $_SESSION['last_activity'] = time();
    }
}