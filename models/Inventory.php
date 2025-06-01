<?php
class Inventory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі записи інвентаризації з новими полями
    public function getAll() {
        $sql = "SELECT i.*, r.name as material_name, r.unit, u.name as manager_name,
                i.actual_quantity, i.barcode,
                (i.quantity - IFNULL(i.actual_quantity, i.quantity)) as quantity_difference
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                ORDER BY r.name";
        return $this->db->resultSet($sql);
    }
    
    // Отримати запис інвентаризації за ID сировини з новими полями
    public function getByMaterialId($material_id) {
        $sql = "SELECT i.*, r.name as material_name, r.unit, r.min_stock, u.name as manager_name,
                i.actual_quantity, i.barcode,
                (i.quantity - IFNULL(i.actual_quantity, i.quantity)) as quantity_difference
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                WHERE i.raw_material_id = ?";
        return $this->db->single($sql, [$material_id]);
    }
    
    // Поиск по штрих-коду
    public function getByBarcode($barcode) {
        $sql = "SELECT i.*, r.name as material_name, r.unit, r.min_stock, u.name as manager_name
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                WHERE i.barcode = ?";
        return $this->db->single($sql, [$barcode]);
    }
    
    // Оновити кількість в інвентаризації (розширена версія)
    public function updateQuantity($material_id, $quantity, $manager_id, $quantity_actual = null) {
        $sql = "UPDATE inventory 
                SET quantity = ?, quantity_actual = IFNULL(?, quantity), warehouse_manager_id = ?, last_updated = NOW() 
                WHERE raw_material_id = ?";
                
        return $this->db->query($sql, [$quantity, $quantity_actual, $manager_id, $material_id]);
    }
    
    // Оновити тільки фактичну кількість
    public function updateActualQuantity($material_id, $actual_quantity, $manager_id) {
        $sql = "UPDATE inventory 
                SET actual_quantity = ?, 
                    warehouse_manager_id = ?, 
                    last_updated = NOW() 
                WHERE raw_material_id = ?";
                
        return $this->db->query($sql, [$actual_quantity, $manager_id, $material_id]);
    }
    
    // Оновити штрих-код
    public function updateBarcode($material_id, $barcode, $manager_id) {
        $sql = "UPDATE inventory 
                SET barcode = ?, 
                    warehouse_manager_id = ?, 
                    last_updated = NOW() 
                WHERE raw_material_id = ?";
                
        return $this->db->query($sql, [$barcode, $manager_id, $material_id]);
    }
    
    // Генерація штрих-кода для товару
    public function generateBarcode($material_id) {
        // Простой алгоритм генерации штрих-кода
        $prefix = 'BC'; // Префикс для внутренних штрих-кодов
        $material_code = str_pad($material_id, 6, '0', STR_PAD_LEFT);
        $check_digit = $this->calculateCheckDigit($material_code);
        
        return $prefix . $material_code . $check_digit;
    }
    
    // Вычисление контрольной цифры для штрих-кода
    private function calculateCheckDigit($code) {
        $sum = 0;
        $length = strlen($code);
        
        for ($i = 0; $i < $length; $i++) {
            $digit = intval($code[$i]);
            if ($i % 2 == 0) {
                $sum += $digit;
            } else {
                $sum += $digit * 3;
            }
        }
        
        $remainder = $sum % 10;
        return $remainder == 0 ? 0 : 10 - $remainder;
    }
    
    // Проверка уникальности штрих-кода
    public function isBarcodeUnique($barcode, $exclude_material_id = null) {
        $sql = "SELECT COUNT(*) as count FROM inventory WHERE barcode = ?";
        $params = [$barcode];
        
        if ($exclude_material_id) {
            $sql .= " AND raw_material_id != ?";
            $params[] = $exclude_material_id;
        }
        
        $result = $this->db->single($sql, $params);
        return $result['count'] == 0;
    }
    
    // Отримати записи з розбіжностями в кількості
    public function getQuantityDiscrepancies() {
        $sql = "SELECT i.*, r.name as material_name, r.unit, u.name as manager_name,
                (i.quantity - IFNULL(i.actual_quantity, i.quantity)) as quantity_difference
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                WHERE i.actual_quantity IS NOT NULL 
                AND i.quantity != i.actual_quantity
                ORDER BY ABS(i.quantity - i.actual_quantity) DESC";
        return $this->db->resultSet($sql);
    }
    
    // Додати кількість до інвентаризації (розширена версія)
    public function addQuantity($material_id, $quantity, $manager_id, $update_actual = true) {
        $sql = "UPDATE inventory 
                SET quantity = quantity + ?, 
                    warehouse_manager_id = ?, 
                    last_updated = NOW()";
        
        $params = [$quantity, $manager_id];
        
        if ($update_actual) {
            $sql .= ", actual_quantity = quantity + ?";
            $params[] = $quantity;
        }
        
        $sql .= " WHERE raw_material_id = ?";
        $params[] = $material_id;
                
        return $this->db->query($sql, $params);
    }
    
    // Відняти кількість з інвентаризації (розширена версія)
    public function subtractQuantity($material_id, $quantity, $manager_id, $update_actual = true) {
        // Перевіряємо, чи є достатньо сировини на складі
        $current = $this->getByMaterialId($material_id);
        
        if ($current && $current['quantity'] >= $quantity) {
            $sql = "UPDATE inventory 
                    SET quantity = quantity - ?, 
                        warehouse_manager_id = ?, 
                        last_updated = NOW()";
            
            $params = [$quantity, $manager_id];
            
            if ($update_actual) {
                $sql .= ", actual_quantity = quantity - ?";
                $params[] = $quantity;
            }
            
            $sql .= " WHERE raw_material_id = ?";
            $params[] = $material_id;
                    
            return $this->db->query($sql, $params);
        }
        
        return false;
    }
    
    // Отримати звіт про поточні запаси з новими полями
    public function getStockReport() {
        $sql = "SELECT i.raw_material_id, i.quantity, i.actual_quantity, i.barcode,
                (i.quantity - IFNULL(i.actual_quantity, i.quantity)) as quantity_difference,
                r.name, r.unit, r.min_stock, r.price_per_unit, 
                (i.quantity * r.price_per_unit) as total_value,
                (CASE WHEN i.quantity < r.min_stock THEN 'low' 
                      WHEN i.quantity < r.min_stock * 2 THEN 'medium' 
                      ELSE 'good' END) as status
                FROM inventory i
                JOIN raw_materials r ON i.raw_material_id = r.id
                ORDER BY status, r.name";
        return $this->db->resultSet($sql);
    }
    
    // Звіт по інвентаризації з розбіжностями
    public function getInventoryReport() {
        $sql = "SELECT i.*, r.name as material_name, r.unit, r.price_per_unit,
                i.actual_quantity, i.barcode,
                (i.quantity - IFNULL(i.actual_quantity, i.quantity)) as quantity_difference,
                (ABS(i.quantity - IFNULL(i.actual_quantity, i.quantity)) * r.price_per_unit) as value_difference,
                u.name as manager_name
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                ORDER BY r.name";
        return $this->db->resultSet($sql);
    }
    
    // Усі інші існуючі методи залишаються без змін...
    
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
                    $this->subtractQuantity(
                        $ingredient['raw_material_id'], 
                        $ingredient['required_quantity'], 
                        $manager_id, 
                        true // Обновляем и фактическое количество
                    );
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
                u.name as supplier_name, i.actual_quantity, i.barcode
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON r.supplier_id = u.id
                WHERE i.quantity < r.min_stock
                ORDER BY stock_percentage";
        return $this->db->resultSet($sql);
    }

    public function getDiscrepancies() {
        $sql = "SELECT i.*, r.name as material_name, r.unit, u.name as manager_name,
                (i.quantity - IFNULL(i.quantity_actual, i.quantity)) as difference
                FROM inventory i 
                JOIN raw_materials r ON i.raw_material_id = r.id
                LEFT JOIN users u ON i.warehouse_manager_id = u.id
                WHERE i.quantity != IFNULL(i.quantity_actual, i.quantity)
                ORDER BY ABS(i.quantity - IFNULL(i.quantity_actual, i.quantity)) DESC";
        return $this->db->resultSet($sql);
    }
}