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
        $sql = "SELECT * FROM video_surveillance WHERE status = 'active' ORDER BY name";
        $activeCameras = $this->db->resultSet($sql);
        
        // Якщо немає активних камер, додамо тестові
        if (empty($activeCameras)) {
            // Перевіримо, чи є в базі камери, але неактивні
            $checkSql = "SELECT COUNT(*) as count FROM video_surveillance";
            $result = $this->db->single($checkSql);
            
            if ($result && $result['count'] > 0) {
                // Якщо є камери, але неактивні, активуємо першу
                $updateSql = "UPDATE video_surveillance SET status = 'active' WHERE id = (SELECT id FROM video_surveillance LIMIT 1)";
                $this->db->query($updateSql);
            } else {
                // Якщо немає камер взагалі, додамо тестові камери
                $this->addDefaultCameras();
            }
            
            // Отримаємо активні камери знову
            return $this->db->resultSet($sql);
        }
        
        return $activeCameras;
    }
    
    // Додати тестові камери за замовчуванням
    private function addDefaultCameras() {
        $cameras = [
            ['Виробничий цех 1', 'rtsp://camera1.example.com:554/stream', 'Виробничий цех 1'],
            ['Склад сировини', 'rtsp://camera2.example.com:554/stream', 'Склад сировини'],
            ['Склад готової продукції', 'rtsp://camera3.example.com:554/stream', 'Склад готової продукції'],
            ['Пакувальний цех', 'rtsp://camera4.example.com:554/stream', 'Пакувальний цех']
        ];
        
        foreach ($cameras as $camera) {
            $sql = "INSERT INTO video_surveillance (name, url, location, status) VALUES (?, ?, ?, 'active')";
            $this->db->query($sql, $camera);
        }
    }
    
    // Отримати камеру за ID
    public function getById($id) {
        $sql = "SELECT * FROM video_surveillance WHERE id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Додати нову камеру
    public function add($name, $url, $location) {
        $sql = "INSERT INTO video_surveillance (name, url, location, status) 
                VALUES (?, ?, ?, 'active')";
                
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
        
        if (empty($result)) {
            return ['Виробничий цех', 'Склад сировини', 'Склад готової продукції', 'Пакувальний цех'];
        }
        
        $locations = [];
        foreach ($result as $row) {
            $locations[] = $row['location'];
        }
        
        return $locations;
    }
}