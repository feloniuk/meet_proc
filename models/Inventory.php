<?php
// models/Inventory.php - UPDATED VERSION WITH NEW FIELDS

class Inventory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // UPDATED: Отримати всі записи інвентаризації з новими полями
    public function getAll() {
        $sql = "SELECT i.*, r.name as material_name, r.unit, u.name as manager_name,
                i.actual_quantity, i.barcode
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                ORDER BY r.name";
        return $this->db->resultSet($sql);
    }
    
    // UPDATED: Отримати запис інвентаризації за ID сировини з новими полями
    public function getByMaterialId($material_id) {
        $sql = "SELECT i.*, r.name as material_name, r.unit, r.min_stock, u.name as manager_name,
                i.actual_quantity, i.barcode
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                WHERE i.raw_material_id = ?";
        return $this->db->single($sql, [$material_id]);
    }
    
    // NEW: Оновити фактичну кількість
    public function updateActualQuantity($material_id, $actual_quantity, $manager_id) {
        $sql = "UPDATE inventory 
                SET actual_quantity = ?, warehouse_manager_id = ?, last_updated = NOW() 
                WHERE raw_material_id = ?";
                
        return $this->db->query($sql, [$actual_quantity, $manager_id, $material_id]);
    }
    
    // NEW: Оновити штрих-код
    public function updateBarcode($material_id, $barcode, $manager_id) {
        $sql = "UPDATE inventory 
                SET barcode = ?, warehouse_manager_id = ?, last_updated = NOW() 
                WHERE raw_material_id = ?";
                
        return $this->db->query($sql, [$barcode, $manager_id, $material_id]);
    }
    
    // UPDATED: Оновити кількість в інвентаризації (також обновляет actual_quantity)
    public function updateQuantity($material_id, $quantity, $manager_id) {
        $sql = "UPDATE inventory 
                SET quantity = ?, actual_quantity = ?, warehouse_manager_id = ?, last_updated = NOW() 
                WHERE raw_material_id = ?";
                
        return $this->db->query($sql, [$quantity, $quantity, $manager_id, $material_id]);
    }
    
    // NEW: Отримати звіт про розбіжності між плановою та фактичною кількістю
    public function getDiscrepancyReport() {
        $sql = "SELECT i.*, r.name as material_name, r.unit, 
                (i.actual_quantity - i.quantity) as difference,
                CASE 
                    WHEN i.actual_quantity > i.quantity THEN 'surplus'
                    WHEN i.actual_quantity < i.quantity THEN 'shortage'
                    ELSE 'match'
                END as discrepancy_type
                FROM inventory i
                JOIN raw_materials r ON i.raw_material_id = r.id
                WHERE i.actual_quantity != i.quantity OR i.actual_quantity IS NULL
                ORDER BY ABS(i.actual_quantity - i.quantity) DESC";
        return $this->db->resultSet($sql);
    }
    
    // NEW: Генерація штрих-коду для матеріалу
    public function generateBarcode($material_id) {
        // Простий генератор штрих-кода: BC + ID материала (6 цифр)
        return 'BC' . str_pad($material_id, 6, '0', STR_PAD_LEFT);
    }
    
    // NEW: Пошук за штрих-кодом
    public function getByBarcode($barcode) {
        $sql = "SELECT i.*, r.name as material_name, r.unit
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                WHERE i.barcode = ?";
        return $this->db->single($sql, [$barcode]);
    }
    
    // Остальные существующие методы остаются без изменений...
    
    public function addQuantity($material_id, $quantity, $manager_id) {
        $sql = "UPDATE inventory 
                SET quantity = quantity + ?, warehouse_manager_id = ?, last_updated = NOW() 
                WHERE raw_material_id = ?";
                
        return $this->db->query($sql, [$quantity, $manager_id, $material_id]);
    }
    
    public function subtractQuantity($material_id, $quantity, $manager_id) {
        $current = $this->getByMaterialId($material_id);
        
        if ($current && $current['quantity'] >= $quantity) {
            $sql = "UPDATE inventory 
                    SET quantity = quantity - ?, warehouse_manager_id = ?, last_updated = NOW() 
                    WHERE raw_material_id = ?";
                    
            return $this->db->query($sql, [$quantity, $manager_id, $material_id]);
        }
        
        return false;
    }
    
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
    
    public function useForProduction($recipe_id, $production_quantity, $manager_id) {
        $availability = $this->checkRecipeAvailability($recipe_id, $production_quantity);
        
        if ($availability === true) {
            $sql = "SELECT ri.raw_material_id, ri.quantity * ? as required_quantity
                    FROM recipe_ingredients ri
                    WHERE ri.recipe_id = ?";
                    
            $ingredients = $this->db->resultSet($sql, [$production_quantity, $recipe_id]);
            
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
    
    // UPDATED: Отримати звіт про поточні запаси з новими полями
    public function getStockReport() {
        $sql = "SELECT i.raw_material_id, i.quantity, i.actual_quantity, i.barcode,
                r.name, r.unit, r.min_stock, 
                r.price_per_unit, (i.quantity * r.price_per_unit) as total_value,
                (CASE WHEN i.quantity < r.min_stock THEN 'low' 
                      WHEN i.quantity < r.min_stock * 2 THEN 'medium' 
                      ELSE 'good' END) as status,
                (i.actual_quantity - i.quantity) as difference
                FROM inventory i
                JOIN raw_materials r ON i.raw_material_id = r.id
                ORDER BY status, r.name";
        return $this->db->resultSet($sql);
    }
}