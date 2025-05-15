<?php
class Production {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі виробничі процеси
    public function getAll() {
        $sql = "SELECT pp.*, p.name as product_name, u.name as manager_name
                FROM production_processes pp 
                JOIN products p ON pp.product_id = p.id
                JOIN users u ON pp.manager_id = u.id
                ORDER BY pp.started_at DESC";
        return $this->db->resultSet($sql);
    }
    
    // Отримати виробничий процес за ID
    public function getById($id) {
        $sql = "SELECT pp.*, p.name as product_name, r.id as recipe_id, u.name as manager_name
                FROM production_processes pp 
                JOIN products p ON pp.product_id = p.id
                JOIN recipes r ON p.recipe_id = r.id
                JOIN users u ON pp.manager_id = u.id
                WHERE pp.id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Отримати активні виробничі процеси
    public function getActive() {
        $sql = "SELECT pp.*, p.name as product_name, u.name as manager_name
                FROM production_processes pp 
                JOIN products p ON pp.product_id = p.id
                JOIN users u ON pp.manager_id = u.id
                WHERE pp.status IN ('planned', 'in_progress')
                ORDER BY pp.started_at";
        return $this->db->resultSet($sql);
    }
    
    // Запланувати новий виробничий процес
    public function plan($product_id, $quantity, $started_at, $manager_id, $notes) {
        $sql = "INSERT INTO production_processes (product_id, quantity, started_at, manager_id, notes, status) 
                VALUES (?, ?, ?, ?, ?, 'planned')";
                
        if ($this->db->query($sql, [$product_id, $quantity, $started_at, $manager_id, $notes])) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Розпочати виробничий процес
    public function start($id, $inventory) {
        // Отримуємо інформацію про процес
        $process = $this->getById($id);
        
        if ($process && $process['status'] === 'planned') {
            // Списуємо сировину
            if ($inventory->useForProduction($process['recipe_id'], $process['quantity'], $process['manager_id'])) {
                $sql = "UPDATE production_processes 
                        SET status = 'in_progress' 
                        WHERE id = ?";
                        
                return $this->db->query($sql, [$id]);
            }
        }
        
        return false;
    }
    
    // Завершити виробничий процес
    public function complete($id) {
        $sql = "UPDATE production_processes 
                SET status = 'completed', completed_at = NOW() 
                WHERE id = ?";
                
        return $this->db->query($sql, [$id]);
    }
    
    // Скасувати виробничий процес
    public function cancel($id) {
        $sql = "UPDATE production_processes 
                SET status = 'canceled' 
                WHERE id = ?";
                
        return $this->db->query($sql, [$id]);
    }
    
    // Оновити виробничий процес
    public function update($id, $product_id, $quantity, $started_at, $notes) {
        $sql = "UPDATE production_processes 
                SET product_id = ?, quantity = ?, started_at = ?, notes = ? 
                WHERE id = ? AND status = 'planned'";
                
        return $this->db->query($sql, [$product_id, $quantity, $started_at, $notes, $id]);
    }
    
    // Отримати статистику виробництва за період
    public function getStatsByPeriod($start_date, $end_date) {
        $sql = "SELECT 
                    DATE(pp.completed_at) as date,
                    SUM(pp.quantity) as total_quantity,
                    COUNT(pp.id) as processes_count
                FROM production_processes pp
                WHERE pp.status = 'completed'
                AND pp.completed_at BETWEEN ? AND ?
                GROUP BY DATE(pp.completed_at)
                ORDER BY date";
                
        return $this->db->resultSet($sql, [$start_date, $end_date]);
    }
    
    // Отримати деталізовану статистику виробництва за період
    public function getDetailedStatsByPeriod($start_date, $end_date) {
        $sql = "SELECT 
                    p.name as product_name,
                    SUM(pp.quantity) as total_quantity,
                    COUNT(pp.id) as processes_count,
                    AVG(TIMESTAMPDIFF(HOUR, pp.started_at, pp.completed_at)) as avg_production_time
                FROM production_processes pp
                JOIN products p ON pp.product_id = p.id
                WHERE pp.status = 'completed'
                AND pp.completed_at BETWEEN ? AND ?
                GROUP BY p.id, p.name
                ORDER BY total_quantity DESC";
                
        return $this->db->resultSet($sql, [$start_date, $end_date]);
    }
}