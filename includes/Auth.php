<?php
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Метод для аутентифікації користувача
    public function login($username, $password) {
        // Перевірка наявності користувача
        $sql = "SELECT * FROM users WHERE username = ?";
        $user = $this->db->single($sql, [$username]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Зберігаємо інформацію про користувача в сесії
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            
            return true;
        }
        
        return false;
    }
    
    // Метод для виходу з системи
    public function logout() {
        // Очищаємо всі дані сесії
        $_SESSION = array();
        
        // Знищуємо сесійний файл cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Знищуємо сесію
        session_destroy();
        
        return true;
    }
    
    // Метод для реєстрації нового постачальника
    public function registerSupplier($username, $password, $name, $email, $phone) {
        // Перевірка, чи існує вже користувач з таким ім'ям
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $result = $this->db->single($sql, [$username, $email]);
        
        if ($result) {
            return false; // Користувач вже існує
        }
        
        // Хешування пароля
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Додавання нового користувача
        $sql = "INSERT INTO users (username, password, role, name, email, phone) 
                VALUES (?, ?, 'supplier', ?, ?, ?)";
                
        if ($this->db->query($sql, [$username, $hashed_password, $name, $email, $phone])) {
            return true;
        }
        
        return false;
    }
    
    // Метод для перевірки, чи користувач авторизований
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Метод для перевірки ролі користувача
    public static function hasRole($role) {
        return self::isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    // Метод для перевірки, чи користувач має одну з ролей
    public static function hasAnyRole($roles) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        if (is_array($roles)) {
            return in_array($_SESSION['role'], $roles);
        }
        
        return $_SESSION['role'] === $roles;
    }
    
    // Метод для отримання ID поточного користувача
    public static function getCurrentUserId() {
        return self::isLoggedIn() ? $_SESSION['user_id'] : null;
    }
    
    // Метод для отримання ролі поточного користувача
    public static function getCurrentUserRole() {
        return self::isLoggedIn() ? $_SESSION['role'] : null;
    }
    
    // Метод для отримання імені поточного користувача
    public static function getCurrentUserName() {
        return self::isLoggedIn() ? $_SESSION['name'] : null;
    }
    
    // Метод для зміни пароля користувача
    public function changePassword($user_id, $old_password, $new_password) {
        // Перевірка старого пароля
        $sql = "SELECT password FROM users WHERE id = ?";
        $user = $this->db->single($sql, [$user_id]);
        
        if ($user && password_verify($old_password, $user['password'])) {
            // Хешування нового пароля
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Оновлення пароля
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            if ($this->db->query($sql, [$hashed_password, $user_id])) {
                return true;
            }
        }
        
        return false;
    }
}