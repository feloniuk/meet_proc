<?php
class Inventory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі записи інвентаризації
    public function getAll() {
        $sql = "SELECT i.*, r.name as material_name, r.unit, u.name as manager_name
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                ORDER BY r.name";
        return $this->db->resultSet($sql);
    }
    
    // Отримати запис інвентаризації за ID сировини
    public function getByMaterialId($material_id) {
        $sql = "SELECT i.*, r.name as material_name, r.unit, r.min_stock, u.name as manager_name
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                WHERE i.raw_material_id = ?";
        return $this->db->single($sql, [$material_id]);
    }
    
    // Оновити кількість в інвентаризації
    public function updateQuantity($material_id, $quantity, $manager_id) {
        $sql = "UPDATE inventory 
                SET quantity = ?, warehouse_manager_id = ?, last_updated = NOW() 
                WHERE raw_material_id = ?";
                
        return $this->db->query($sql, [$quantity, $manager_id, $material_id]);
    }
    
    // Додати кількість до інвентаризації
    public function addQuantity($material_id, $quantity, $manager_id) {
        $sql = "UPDATE inventory 
                SET quantity = quantity + ?, warehouse_manager_id = ?, last_updated = NOW() 
                WHERE raw_material_id = ?";
                
        return $this->db->query($sql, [$quantity, $manager_id, $material_id]);
    }
    
    // Відняти кількість з інвентаризації
    public function subtractQuantity($material_id, $quantity, $manager_id) {
        // Перевіряємо, чи є достатньо сировини на складі
        $current = $this->getByMaterialId($material_id);
        
        if ($current && $current['quantity'] >= $quantity) {
            $sql = "UPDATE inventory 
                    SET quantity = quantity - ?, warehouse_manager_id = ?, last_updated = NOW() 
                    WHERE raw_material_id = ?";
                    
            return $this->db->query($sql, [$quantity, $manager_id, $material_id]);
        }
        
        return false;
    }
    
    // Перевірити наявність достатньої кількості сировини для рецепту
    public function checkRecipeAvailability($recipe_id, $production_quantity) {
        $sql = "SELECT ri.raw_material_id, ri.quantity * ? as required_quantity, i.quantity as available_quantity, 
                r.name as material_name, r.unit
                FROM recipe_ingredients ri
                JOIN raw_materials r ON ri.raw_material_id = r.id
                JOIN inventory i ON ri.raw_material_id = i.raw_material_id
                WHERE ri.recipe_id = ?
                HAVING required_quantity > available_quantity";
                
        $missing = $this->db->resultSet($sql, [$production_quantity, $recipe_id]);
        
        return empty($missing) ? true : $missing;
    }
    
    // Списати сировину для виробництва
    public function useForProduction($recipe_id, $production_quantity, $manager_id) {
        // Перевіряємо наявність сировини
        $availability = $this->checkRecipeAvailability($recipe_id, $production_quantity);
        
        if ($availability === true) {
            // Отримуємо список необхідної сировини
            $sql = "SELECT ri.raw_material_id, ri.quantity * ? as required_quantity
                    FROM recipe_ingredients ri
                    WHERE ri.recipe_id = ?";
                    
            $ingredients = $this->db->resultSet($sql, [$production_quantity, $recipe_id]);
            
            // Списуємо сировину
            $this->db->beginTransaction();
            
            try {
                foreach ($ingredients as $ingredient) {
                    $sql = "UPDATE inventory 
                            SET quantity = quantity - ?, warehouse_manager_id = ?, last_updated = NOW() 
                            WHERE raw_material_id = ?";
                            
                    $this->db->query($sql, [$ingredient['required_quantity'], $manager_id, $ingredient['raw_material_id']]);
                }
                
                $this->db->commit();
                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                return false;
            }
        }
        
        return false;
    }
    
    // Отримати всі матеріали з критично низьким запасом
    public function getCriticalLowStock() {
        $sql = "SELECT i.*, r.name as material_name, r.unit, r.min_stock, 
                (i.quantity / r.min_stock * 100) as stock_percentage,
                u.name as supplier_name
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON r.supplier_id = u.id
                WHERE i.quantity < r.min_stock
                ORDER BY stock_percentage";
        return $this->db->resultSet($sql);
    }
    
    // Отримати звіт про поточні запаси
    public function getStockReport() {
        $sql = "SELECT i.raw_material_id, i.quantity, r.name, r.unit, r.min_stock, 
                r.price_per_unit, (i.quantity * r.price_per_unit) as total_value,
                (CASE WHEN i.quantity < r.min_stock THEN 'low' 
                      WHEN i.quantity < r.min_stock * 2 THEN 'medium' 
                      ELSE 'good' END) as status
                FROM inventory i
                JOIN raw_materials r ON i.raw_material_id = r.id
                ORDER BY status, r.name";
        return $this->db->resultSet($sql);
    }
}