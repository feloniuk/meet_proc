<?php
class Message {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі повідомлення для користувача
    public function getByUser($user_id) {
        $sql = "SELECT m.*, 
                us.name as sender_name, 
                ur.name as receiver_name
                FROM messages m 
                JOIN users us ON m.sender_id = us.id
                JOIN users ur ON m.receiver_id = ur.id
                WHERE m.sender_id = ? OR m.receiver_id = ?
                ORDER BY m.created_at DESC";
        return $this->db->resultSet($sql, [$user_id, $user_id]);
    }
    
    // Отримати вхідні повідомлення
    public function getInbox($user_id) {
        $sql = "SELECT m.*, 
                u.name as sender_name
                FROM messages m 
                JOIN users u ON m.sender_id = u.id
                WHERE m.receiver_id = ?
                ORDER BY m.created_at DESC";
        return $this->db->resultSet($sql, [$user_id]);
    }
    
    // Отримати надіслані повідомлення
    public function getSent($user_id) {
        $sql = "SELECT m.*, 
                u.name as receiver_name
                FROM messages m 
                JOIN users u ON m.receiver_id = u.id
                WHERE m.sender_id = ?
                ORDER BY m.created_at DESC";
        return $this->db->resultSet($sql, [$user_id]);
    }
    
    // Отримати повідомлення за ID
    public function getById($id) {
        $sql = "SELECT m.*, 
                us.name as sender_name, 
                ur.name as receiver_name
                FROM messages m 
                JOIN users us ON m.sender_id = us.id
                JOIN users ur ON m.receiver_id = ur.id
                WHERE m.id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Відправити нове повідомлення
    public function send($sender_id, $receiver_id, $subject, $message) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, subject, message) 
                VALUES (?, ?, ?, ?)";
                
        if ($this->db->query($sql, [$sender_id, $receiver_id, $subject, $message])) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Позначити повідомлення як прочитане
    public function markAsRead($id) {
        $sql = "UPDATE messages 
                SET is_read = 1 
                WHERE id = ?";
                
        return $this->db->query($sql, [$id]);
    }
    
    // Видалити повідомлення
    public function delete($id) {
        $sql = "DELETE FROM messages WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Перевірити наявність непрочитаних повідомлень
    public function hasUnread($user_id) {
        $sql = "SELECT COUNT(*) as count
                FROM messages
                WHERE receiver_id = ? AND is_read = 0";
                
        $result = $this->db->single($sql, [$user_id]);
        
        return $result && $result['count'] > 0;
    }
    
    // Отримати кількість непрочитаних повідомлень
    public function countUnread($user_id) {
        $sql = "SELECT COUNT(*) as count
                FROM messages
                WHERE receiver_id = ? AND is_read = 0";
                
        $result = $this->db->single($sql, [$user_id]);
        
        return $result ? $result['count'] : 0;
    }
    
    // Отримати останні повідомлення
    public function getLatest($user_id, $limit = 5) {
        $sql = "SELECT m.*, 
                u.name as sender_name
                FROM messages m 
                JOIN users u ON m.sender_id = u.id
                WHERE m.receiver_id = ?
                ORDER BY m.created_at DESC
                LIMIT ?";
        return $this->db->resultSet($sql, [$user_id, $limit]);
    }
}