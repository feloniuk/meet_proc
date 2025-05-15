<?php
class VideoSurveillance {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі камери відеоспостереження
    public function getAll() {
        $sql = "SELECT * FROM video_surveillance ORDER BY name";
        return $this->db->resultSet($sql);
    }
    
    // Отримати активні камери відеоспостереження
    public function getActive() {
        // Перевірка наявності камер в БД та їх статусів
        $checkSql = "SELECT id, name, status FROM video_surveillance";
        $allCameras = $this->db->resultSet($checkSql);
        
        // Якщо немає камер, повертаємо пустий масив
        if (empty($allCameras)) {
            return [];
        }
        
        // Додаємо тестові дані, якщо в БД немає камер або всі неактивні
        $active = array_filter($allCameras, function($camera) {
            return $camera['status'] === 'active';
        });
        
        if (empty($active)) {
            // Якщо немає активних камер, додамо одну тестову камеру в БД і активуємо всі існуючі
            if (empty($allCameras)) {
                // Додаємо тестову камеру
                $insertSql = "INSERT INTO video_surveillance (name, url, location, status) 
                              VALUES ('Тестова камера 1', 'rtsp://test1.local:554/stream', 'Виробничий цех 1', 'active')";
                $this->db->query($insertSql);
                
                $insertSql = "INSERT INTO video_surveillance (name, url, location, status) 
                              VALUES ('Тестова камера 2', 'rtsp://test2.local:554/stream', 'Склад сировини', 'active')";
                $this->db->query($insertSql);
            } else {
                // Активуємо всі існуючі камери
                $updateSql = "UPDATE video_surveillance SET status = 'active'";
                $this->db->query($updateSql);
            }
        }
        
        // Отримуємо активні камери
        $sql = "SELECT * FROM video_surveillance WHERE status = 'active' ORDER BY name";
        return $this->db->resultSet($sql);
    }
    
    // Отримати камеру за ID
    public function getById($id) {
        $sql = "SELECT * FROM video_surveillance WHERE id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Додати нову камеру
    public function add($name, $url, $location) {
        $sql = "INSERT INTO video_surveillance (name, url, location) 
                VALUES (?, ?, ?)";
                
        if ($this->db->query($sql, [$name, $url, $location])) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Оновити камеру
    public function update($id, $name, $url, $location, $status) {
        $sql = "UPDATE video_surveillance 
                SET name = ?, url = ?, location = ?, status = ? 
                WHERE id = ?";
                
        return $this->db->query($sql, [$name, $url, $location, $status, $id]);
    }
    
    // Змінити статус камери
    public function setStatus($id, $status) {
        $sql = "UPDATE video_surveillance 
                SET status = ? 
                WHERE id = ?";
                
        return $this->db->query($sql, [$status, $id]);
    }
    
    // Видалити камеру
    public function delete($id) {
        $sql = "DELETE FROM video_surveillance WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Отримати камери за локацією
    public function getByLocation($location) {
        $sql = "SELECT * FROM video_surveillance WHERE location = ? ORDER BY name";
        return $this->db->resultSet($sql, [$location]);
    }
    
    // Отримати унікальні локації
    public function getLocations() {
        $sql = "SELECT DISTINCT location FROM video_surveillance ORDER BY location";
        $result = $this->db->resultSet($sql);
        
        $locations = [];
        foreach ($result as $row) {
            $locations[] = $row['location'];
        }
        
        return $locations;
    }
}