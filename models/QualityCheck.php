<?php
class QualityCheck {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі перевірки якості з фільтрами
    public function getAll($status = '', $date_from = '', $date_to = '') {
        $sql = "SELECT qc.*, 
                o.id as order_number,
                o.supplier_id,
                u_supplier.name as supplier_name,
                u_tech.name as technologist_name,
                o.delivery_date,
                o.total_amount
                FROM quality_checks qc
                JOIN orders o ON qc.order_id = o.id
                JOIN users u_supplier ON o.supplier_id = u_supplier.id
                JOIN users u_tech ON qc.technologist_id = u_tech.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($status)) {
            $sql .= " AND qc.status = ?";
            $params[] = $status;
        }
        
        if (!empty($date_from)) {
            $sql .= " AND DATE(qc.check_date) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $sql .= " AND DATE(qc.check_date) <= ?";
            $params[] = $date_to;
        }
        
        $sql .= " ORDER BY qc.check_date DESC";
        
        return $this->db->resultSet($sql, $params);
    }
    
    // Отримати перевірки, що очікують на виконання
    public function getPendingChecks() {
        $sql = "SELECT qc.*, 
                o.id as order_number,
                u_supplier.name as supplier_name,
                o.delivery_date
                FROM quality_checks qc
                JOIN orders o ON qc.order_id = o.id
                JOIN users u_supplier ON o.supplier_id = u_supplier.id
                WHERE qc.status = 'pending'
                ORDER BY qc.check_date ASC";
        
        return $this->db->resultSet($sql);
    }
    
    // Отримати останні перевірки технолога
    public function getRecentChecks($technologist_id, $limit = 10) {
        $sql = "SELECT qc.*, 
                o.id as order_number,
                u_supplier.name as supplier_name
                FROM quality_checks qc
                JOIN orders o ON qc.order_id = o.id
                JOIN users u_supplier ON o.supplier_id = u_supplier.id
                WHERE qc.technologist_id = ?
                ORDER BY qc.check_date DESC
                LIMIT ?";
        
        return $this->db->resultSet($sql, [$technologist_id, $limit]);
    }
    
    // Отримати перевірку за ID
    public function getById($id) {
        $sql = "SELECT qc.*, 
                o.id as order_number,
                o.supplier_id,
                u_supplier.name as supplier_name,
                u_tech.name as technologist_name,
                o.delivery_date,
                o.total_amount,
                o.notes as order_notes
                FROM quality_checks qc
                JOIN orders o ON qc.order_id = o.id
                JOIN users u_supplier ON o.supplier_id = u_supplier.id
                JOIN users u_tech ON qc.technologist_id = u_tech.id
                WHERE qc.id = ?";
        
        return $this->db->single($sql, [$id]);
    }
    
    // Отримати перевірку за ID замовлення
    public function getByOrderId($order_id) {
        $sql = "SELECT * FROM quality_checks WHERE order_id = ?";
        return $this->db->single($sql, [$order_id]);
    }
    
    // Створити нову перевірку якості
    public function create($order_id, $technologist_id, $notes = '') {
        $sql = "INSERT INTO quality_checks (order_id, technologist_id, notes, status) 
                VALUES (?, ?, ?, 'pending')";
        
        if ($this->db->query($sql, [$order_id, $technologist_id, $notes])) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Оновити перевірку якості
    public function update($id, $data) {
        $setParts = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $setParts[] = "$field = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        
        $sql = "UPDATE quality_checks SET " . implode(', ', $setParts) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    // Отримати елементи перевірки
    public function getCheckItems($check_id) {
        $sql = "SELECT qci.*, rm.name as material_name, rm.unit
                FROM quality_check_items qci
                JOIN raw_materials rm ON qci.raw_material_id = rm.id
                WHERE qci.quality_check_id = ?
                ORDER BY qci.id";
        
        return $this->db->resultSet($sql, [$check_id]);
    }
    
    // Додати елемент перевірки
    public function addCheckItem($check_id, $raw_material_id, $quantity_checked, $status, $notes = '', $defects_found = '', $grade = null) {
        $sql = "INSERT INTO quality_check_items 
                (quality_check_id, raw_material_id, quantity_checked, status, notes, defects_found, grade) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->query($sql, [$check_id, $raw_material_id, $quantity_checked, $status, $notes, $defects_found, $grade]);
    }
    
    // Отримати стандарти якості для замовлення
    public function getStandardsForOrder($order_id) {
        $sql = "SELECT qs.*, rm.name as material_name, rm.unit as material_unit
                FROM quality_standards qs
                JOIN raw_materials rm ON qs.raw_material_id = rm.id
                JOIN order_items oi ON rm.id = oi.raw_material_id
                WHERE oi.order_id = ?
                ORDER BY rm.name, qs.parameter_name";
        
        return $this->db->resultSet($sql, [$order_id]);
    }
    
    // Отримати всі стандарти якості
    public function getAllStandards() {
        $sql = "SELECT qs.*, rm.name as material_name
                FROM quality_standards qs
                JOIN raw_materials rm ON qs.raw_material_id = rm.id
                ORDER BY rm.name, qs.parameter_name";
        
        return $this->db->resultSet($sql);
    }
    
    // Додати стандарт якості
    public function addStandard($raw_material_id, $parameter_name, $min_value, $max_value, $unit, $description, $is_critical) {
        $sql = "INSERT INTO quality_standards 
                (raw_material_id, parameter_name, min_value, max_value, unit, description, is_critical) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->query($sql, [
            $raw_material_id, 
            $parameter_name, 
            !empty($min_value) ? $min_value : null,
            !empty($max_value) ? $max_value : null,
            $unit, 
            $description, 
            $is_critical
        ]);
    }
    
    // Оновити стандарт якості
    public function updateStandard($id, $parameter_name, $min_value, $max_value, $unit, $description, $is_critical) {
        $sql = "UPDATE quality_standards 
                SET parameter_name = ?, min_value = ?, max_value = ?, unit = ?, description = ?, is_critical = ?
                WHERE id = ?";
        
        return $this->db->query($sql, [
            $parameter_name,
            !empty($min_value) ? $min_value : null,
            !empty($max_value) ? $max_value : null,
            $unit,
            $description,
            $is_critical,
            $id
        ]);
    }
    
    // Видалити стандарт якості
    public function deleteStandard($id) {
        $sql = "DELETE FROM quality_standards WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Отримати статистику за період
    public function getStatsByPeriod($start_date, $end_date) {
        $sql = "SELECT 
                    COUNT(*) as total_checks,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    ROUND(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as approval_rate,
                    ROUND(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as rejection_rate
                FROM quality_checks 
                WHERE DATE(check_date) BETWEEN ? AND ?";
        
        return $this->db->single($sql, [$start_date, $end_date]);
    }
    
    // Отримати статистику по матеріалах за період
    public function getMaterialStatsByPeriod($start_date, $end_date) {
        $sql = "SELECT 
                    rm.name as material_name,
                    COUNT(qc.id) as total_checks,
                    SUM(CASE WHEN qc.status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN qc.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    ROUND(SUM(CASE WHEN qc.status = 'approved' THEN 1 ELSE 0 END) * 100.0 / COUNT(qc.id), 2) as approval_rate
                FROM quality_checks qc
                JOIN orders o ON qc.order_id = o.id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN raw_materials rm ON oi.raw_material_id = rm.id
                WHERE DATE(qc.check_date) BETWEEN ? AND ?
                GROUP BY rm.id, rm.name
                ORDER BY total_checks DESC";
        
        return $this->db->resultSet($sql, [$start_date, $end_date]);
    }
    
    // Отримати розподіл оцінок за період
    public function getGradeDistribution($start_date, $end_date) {
        $sql = "SELECT 
                    overall_grade,
                    COUNT(*) as count
                FROM quality_checks 
                WHERE DATE(check_date) BETWEEN ? AND ?
                AND overall_grade IS NOT NULL
                GROUP BY overall_grade
                ORDER BY 
                    CASE overall_grade 
                        WHEN 'excellent' THEN 1 
                        WHEN 'good' THEN 2 
                        WHEN 'satisfactory' THEN 3 
                        WHEN 'unsatisfactory' THEN 4 
                    END";
        
        return $this->db->resultSet($sql, [$start_date, $end_date]);
    }
    
    // Перевірити, чи відповідає значення стандарту
    public function checkValueAgainstStandard($value, $standard) {
        if (empty($value) || $value === null) {
            return null; // Значення не задано
        }
        
        $value = floatval($value);
        $min = $standard['min_value'] !== null ? floatval($standard['min_value']) : null;
        $max = $standard['max_value'] !== null ? floatval($standard['max_value']) : null;
        
        if ($min !== null && $value < $min) {
            return false; // Нижче мінімуму
        }
        
        if ($max !== null && $value > $max) {
            return false; // Вище максимуму
        }
        
        return true; // В межах норми
    }
    
    // Отримати рекомендації на основі перевірки
    public function getRecommendations($check_id) {
        $check = $this->getById($check_id);
        $standards = $this->getStandardsForOrder($check['order_id']);
        
        $recommendations = [];
        
        // Перевіряємо температуру
        if ($check['temperature'] !== null) {
            $tempStandards = array_filter($standards, function($s) {
                return strtolower($s['parameter_name']) === 'температура';
            });
            
            foreach ($tempStandards as $standard) {
                if (!$this->checkValueAgainstStandard($check['temperature'], $standard)) {
                    $recommendations[] = "Температура сировини ({$check['temperature']}°C) не відповідає стандарту для {$standard['material_name']} ({$standard['min_value']}-{$standard['max_value']}°C)";
                }
            }
        }
        
        // Перевіряємо pH
        if ($check['ph_level'] !== null) {
            $phStandards = array_filter($standards, function($s) {
                return strtolower($s['parameter_name']) === 'ph';
            });
            
            foreach ($phStandards as $standard) {
                if (!$this->checkValueAgainstStandard($check['ph_level'], $standard)) {
                    $recommendations[] = "Рівень pH ({$check['ph_level']}) не відповідає стандарту для {$standard['material_name']} ({$standard['min_value']}-{$standard['max_value']})";
                }
            }
        }
        
        // Перевіряємо вологість
        if ($check['moisture_content'] !== null) {
            $moistureStandards = array_filter($standards, function($s) {
                return strtolower($s['parameter_name']) === 'влажность' || strtolower($s['parameter_name']) === 'вологість';
            });
            
            foreach ($moistureStandards as $standard) {
                if (!$this->checkValueAgainstStandard($check['moisture_content'], $standard)) {
                    $recommendations[] = "Вологість ({$check['moisture_content']}%) не відповідає стандарту для {$standard['material_name']} ({$standard['min_value']}-{$standard['max_value']}%)";
                }
            }
        }
        
        return $recommendations;
    }
}