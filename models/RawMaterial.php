<?php
class RawMaterial {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі сировинні матеріали
    public function getAll() {
        $sql = "SELECT r.*, u.name as supplier_name 
                FROM raw_materials r 
                LEFT JOIN users u ON r.supplier_id = u.id 
                ORDER BY r.name";
        return $this->db->resultSet($sql);
    }
    
    // Отримати сировинний матеріал за ID
    public function getById($id) {
        $sql = "SELECT r.*, u.name as supplier_name 
                FROM raw_materials r 
                LEFT JOIN users u ON r.supplier_id = u.id 
                WHERE r.id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Отримати сировинні матеріали за постачальником
    public function getBySupplier($supplier_id) {
        $sql = "SELECT * FROM raw_materials WHERE supplier_id = ? ORDER BY name";
        return $this->db->resultSet($sql, [$supplier_id]);
    }
    
    // Отримати сировинні матеріали з низьким запасом
    public function getLowStock() {
        $sql = "SELECT r.*, i.quantity, u.name as supplier_name
                FROM raw_materials r
                LEFT JOIN inventory i ON r.id = i.raw_material_id
                LEFT JOIN users u ON r.supplier_id = u.id
                WHERE i.quantity <= r.min_stock
                ORDER BY (i.quantity / r.min_stock)";
        return $this->db->resultSet($sql);
    }
    
    // Додати новий сировинний матеріал
    public function add($name, $description, $unit, $price_per_unit, $min_stock, $supplier_id) {
        $sql = "INSERT INTO raw_materials (name, description, unit, price_per_unit, min_stock, supplier_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        if ($this->db->query($sql, [$name, $description, $unit, $price_per_unit, $min_stock, $supplier_id])) {
            $material_id = $this->db->lastInsertId();
            
            // Додаємо початковий запис в інвентаризацію з нульовою кількістю
            $sql = "INSERT INTO inventory (raw_material_id, quantity, warehouse_manager_id) 
                    VALUES (?, 0, ?)";
            $this->db->query($sql, [$material_id, Auth::getCurrentUserId()]);
            
            return $material_id;
        }
        
        return false;
    }
    
    // Оновити сировинний матеріал
    public function update($id, $name, $description, $unit, $price_per_unit, $min_stock, $supplier_id) {
        $sql = "UPDATE raw_materials 
                SET name = ?, description = ?, unit = ?, price_per_unit = ?, min_stock = ?, supplier_id = ? 
                WHERE id = ?";
                
        return $this->db->query($sql, [$name, $description, $unit, $price_per_unit, $min_stock, $supplier_id, $id]);
    }
    
    // Видалити сировинний матеріал
    public function delete($id) {
        // Спочатку видаляємо пов'язані записи з інвентаризації
        $sql = "DELETE FROM inventory WHERE raw_material_id = ?";
        $this->db->query($sql, [$id]);
        
        // Тепер видаляємо сам матеріал
        $sql = "DELETE FROM raw_materials WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Отримати статистику використання сировини
    public function getUsageStats($start_date, $end_date) {
        $sql = "SELECT r.id, r.name, r.unit, 
                SUM(ri.quantity * pp.quantity) as total_used,
                (SELECT SUM(quantity) FROM inventory WHERE raw_material_id = r.id) as current_stock
                FROM raw_materials r
                JOIN recipe_ingredients ri ON r.id = ri.raw_material_id
                JOIN recipes rec ON ri.recipe_id = rec.id
                JOIN products p ON rec.id = p.recipe_id
                JOIN production_processes pp ON p.id = pp.product_id
                WHERE pp.completed_at BETWEEN ? AND ?
                GROUP BY r.id, r.name
                ORDER BY total_used DESC";
                
        return $this->db->resultSet($sql, [$start_date, $end_date]);
    }
}