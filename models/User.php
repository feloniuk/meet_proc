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
    
    // ИСПРАВЛЕНО: Получить пользователей по роли с проверкой
    public function getByRole($role) {
        $sql = "SELECT * FROM users WHERE role = ? ORDER BY name";
        $result = $this->db->resultSet($sql, [$role]);
        
        // Возвращаем пустой массив вместо null если результата нет
        return $result ?: [];
    }
    
    // ИСПРАВЛЕНО: Получить поставщиков с дополнительной проверкой
    public function getSuppliers() {
        $sql = "SELECT id, username, name, email, phone, created_at 
                FROM users 
                WHERE role = 'supplier' 
                ORDER BY name";
        
        $result = $this->db->resultSet($sql);
        
        // Возвращаем пустой массив вместо null если поставщиков нет
        return $result ?: [];
    }
    
    // ИСПРАВЛЕНО: Получить всех технологов
    public function getTechnologists() {
        $sql = "SELECT id, username, name, email, phone, created_at 
                FROM users 
                WHERE role = 'technologist' 
                ORDER BY name";
        
        $result = $this->db->resultSet($sql);
        return $result ?: [];
    }
    
    // ИСПРАВЛЕНО: Получить менеджеров склада
    public function getWarehouseManagers() {
        $sql = "SELECT id, username, name, email, phone, created_at 
                FROM users 
                WHERE role = 'warehouse_manager' 
                ORDER BY name";
        
        $result = $this->db->resultSet($sql);
        return $result ?: [];
    }
    
    // ИСПРАВЛЕНО: Получить администраторов
    public function getAdmins() {
        $sql = "SELECT id, username, name, email, phone, created_at 
                FROM users 
                WHERE role = 'admin' 
                ORDER BY name";
        
        $result = $this->db->resultSet($sql);
        return $result ?: [];
    }
    
    // ИСПРАВЛЕНО: Проверка существования пользователя
    public function isUserExist($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $result = $this->db->single($sql, [$username, $email]);
        return $result !== false && $result !== null;
    }
    
    // ИСПРАВЛЕНО: Добавить нового пользователя с валидацией
    public function add($username, $password, $role, $name, $email, $phone) {
        // Проверяем корректность роли
        $validRoles = ['admin', 'warehouse_manager', 'supplier', 'technologist'];
        if (!in_array($role, $validRoles)) {
            return false;
        }
        
        // Проверяем существование пользователя
        if ($this->isUserExist($username, $email)) {
            return false;
        }
        
        // Хешування пароля
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password, role, name, email, phone) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        try {
            $result = $this->db->query($sql, [$username, $hashed_password, $role, $name, $email, $phone]);
            return $result ? $this->db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("Error adding user: " . $e->getMessage());
            return false;
        }
    }
    
    // ИСПРАВЛЕНО: Обновить данные пользователя
    public function update($id, $name, $email, $phone) {
        // Проверяем, что пользователь существует
        $existingUser = $this->getById($id);
        if (!$existingUser) {
            return false;
        }
        
        // Проверяем, не занят ли email другим пользователем
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $emailTaken = $this->db->single($sql, [$email, $id]);
        
        if ($emailTaken) {
            return false;
        }
        
        $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        
        try {
            return $this->db->query($sql, [$name, $email, $phone, $id]);
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }
    
    // ИСПРАВЛЕНО: Изменить пароль пользователя
    public function changePassword($id, $password) {
        // Проверяем, что пользователь существует
        $existingUser = $this->getById($id);
        if (!$existingUser) {
            return false;
        }
        
        // Хешування пароля
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        
        try {
            return $this->db->query($sql, [$hashed_password, $id]);
        } catch (Exception $e) {
            error_log("Error changing password: " . $e->getMessage());
            return false;
        }
    }
    
    // ИСПРАВЛЕНО: Удалить пользователя с проверками
    public function delete($id) {
        // Проверяем, что пользователь существует
        $user = $this->getById($id);
        if (!$user) {
            return false;
        }
        
        // Нельзя удалить последнего администратора
        if ($user['role'] === 'admin') {
            $adminCount = count($this->getAdmins());
            if ($adminCount <= 1) {
                return false; // Нельзя удалить последнего администратора
            }
        }
        
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();
            
            // Удаляем связанные данные в зависимости от роли
            switch ($user['role']) {
                case 'supplier':
                    // Удаляем материалы поставщика
                    $this->db->query("DELETE FROM raw_materials WHERE supplier_id = ?", [$id]);
                    break;
                    
                case 'warehouse_manager':
                    // Обнуляем ссылки на менеджера склада
                    $this->db->query("UPDATE inventory SET warehouse_manager_id = NULL WHERE warehouse_manager_id = ?", [$id]);
                    $this->db->query("UPDATE production_processes SET manager_id = NULL WHERE manager_id = ?", [$id]);
                    break;
                    
                case 'technologist':
                    // Обнуляем ссылки на технолога
                    $this->db->query("UPDATE quality_checks SET technologist_id = NULL WHERE technologist_id = ?", [$id]);
                    break;
            }
            
            // Удаляем сообщения пользователя
            $this->db->query("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?", [$id, $id]);
            
            // Удаляем самого пользователя
            $sql = "DELETE FROM users WHERE id = ?";
            $result = $this->db->query($sql, [$id]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }
    
    // ИСПРАВЛЕНО: Получить количество пользователей по роли
    public function countByRole($role) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE role = ?";
        $result = $this->db->single($sql, [$role]);
        return $result ? $result['count'] : 0;
    }
    
    // ИСПРАВЛЕНО: Получить статистику по пользователям
    public function getUserStats() {
        $sql = "SELECT 
                    role,
                    COUNT(*) as count
                FROM users 
                GROUP BY role 
                ORDER BY role";
        
        $result = $this->db->resultSet($sql);
        
        $stats = [
            'admin' => 0,
            'warehouse_manager' => 0,
            'supplier' => 0,
            'technologist' => 0
        ];
        
        foreach ($result as $row) {
            $stats[$row['role']] = $row['count'];
        }
        
        return $stats;
    }
    
    // ИСПРАВЛЕНО: Поиск пользователей
    public function search($query, $role = null) {
        $sql = "SELECT * FROM users WHERE (name LIKE ? OR email LIKE ? OR username LIKE ?)";
        $params = ["%$query%", "%$query%", "%$query%"];
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY name";
        
        $result = $this->db->resultSet($sql, $params);
        return $result ?: [];
    }
}