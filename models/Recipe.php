<?php
class Recipe {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі рецепти
    public function getAll() {
        $sql = "SELECT r.*, u.name as creator_name
                FROM recipes r 
                LEFT JOIN users u ON r.created_by = u.id 
                ORDER BY r.name";
        return $this->db->resultSet($sql);
    }
    
    // Отримати рецепт за ID
    public function getById($id) {
        $sql = "SELECT r.*, u.name as creator_name
                FROM recipes r 
                LEFT JOIN users u ON r.created_by = u.id 
                WHERE r.id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Отримати інгредієнти рецепту
    public function getIngredients($recipe_id) {
        $sql = "SELECT ri.*, r.name as material_name, r.unit
                FROM recipe_ingredients ri 
                JOIN raw_materials r ON ri.raw_material_id = r.id
                WHERE ri.recipe_id = ?
                ORDER BY ri.id";
        return $this->db->resultSet($sql, [$recipe_id]);
    }
    
    // Додати новий рецепт
    public function add($name, $description, $created_by) {
        $sql = "INSERT INTO recipes (name, description, created_by) 
                VALUES (?, ?, ?)";
                
        if ($this->db->query($sql, [$name, $description, $created_by])) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Оновити рецепт
    public function update($id, $name, $description) {
        $sql = "UPDATE recipes 
                SET name = ?, description = ? 
                WHERE id = ?";
                
        return $this->db->query($sql, [$name, $description, $id]);
    }
    
    // Додати інгредієнт до рецепту
    public function addIngredient($recipe_id, $raw_material_id, $quantity) {
        $sql = "INSERT INTO recipe_ingredients (recipe_id, raw_material_id, quantity) 
                VALUES (?, ?, ?)";
                
        return $this->db->query($sql, [$recipe_id, $raw_material_id, $quantity]);
    }
    
    // Оновити кількість інгредієнта
    public function updateIngredient($id, $quantity) {
        $sql = "UPDATE recipe_ingredients 
                SET quantity = ? 
                WHERE id = ?";
                
        return $this->db->query($sql, [$quantity, $id]);
    }
    
    // Видалити інгредієнт з рецепту
    public function deleteIngredient($id) {
        $sql = "DELETE FROM recipe_ingredients WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Видалити рецепт
    public function delete($id) {
        // Видаляємо всі інгредієнти рецепту
        $sql = "DELETE FROM recipe_ingredients WHERE recipe_id = ?";
        $this->db->query($sql, [$id]);
        
        // Видаляємо сам рецепт
        $sql = "DELETE FROM recipes WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Розрахувати собівартість рецепту
    public function calculateCost($recipe_id) {
        $sql = "SELECT SUM(ri.quantity * r.price_per_unit) as total_cost
                FROM recipe_ingredients ri
                JOIN raw_materials r ON ri.raw_material_id = r.id
                WHERE ri.recipe_id = ?";
                
        $result = $this->db->single($sql, [$recipe_id]);
        
        return $result ? $result['total_cost'] : 0;
    }
}