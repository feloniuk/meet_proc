<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всіх користувачів
    public function getAll() {
        $sql = "SELECT * FROM users ORDER BY name";
        return $this->db->resultSet($sql);
    }
    
    // Отримати користувача за ID
    public function getById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Отримати користувачів за роллю
    public function getByRole($role) {
        $sql = "SELECT * FROM users WHERE role = ? ORDER BY name";
        return $this->db->resultSet($sql, [$role]);
    }
    
    // Отримати постачальників
    public function getSuppliers() {
        return $this->getByRole('supplier');
    }
    
    // Перевірити, чи існує користувач з таким ім'ям або email
    public function isUserExist($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        return (bool) $this->db->single($sql, [$username, $email]);
    }
    
    // Додати нового користувача
    public function add($username, $password, $role, $name, $email, $phone) {
        // Хешування пароля
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password, role, name, email, phone) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        return $this->db->query($sql, [$username, $hashed_password, $role, $name, $email, $phone]);
    }
    
    // Оновити дані користувача
    public function update($id, $name, $email, $phone) {
        $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        return $this->db->query($sql, [$name, $email, $phone, $id]);
    }
    
    // Змінити пароль користувача
    public function changePassword($id, $password) {
        // Хешування пароля
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        return $this->db->query($sql, [$hashed_password, $id]);
    }
    
    // Видалити користувача
    public function delete($id) {
        $sql = "DELETE FROM users WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
}