<?php
class Product {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі продукти
    public function getAll() {
        $sql = "SELECT p.*, r.name as recipe_name
                FROM products p 
                JOIN recipes r ON p.recipe_id = r.id 
                ORDER BY p.name";
        return $this->db->resultSet($sql);
    }
    
    // Отримати продукт за ID
    public function getById($id) {
        $sql = "SELECT p.*, r.name as recipe_name, r.description as recipe_description
                FROM products p 
                JOIN recipes r ON p.recipe_id = r.id 
                WHERE p.id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Додати новий продукт
    public function add($name, $description, $recipe_id, $weight, $price) {
        $sql = "INSERT INTO products (name, description, recipe_id, weight, price) 
                VALUES (?, ?, ?, ?, ?)";
                
        if ($this->db->query($sql, [$name, $description, $recipe_id, $weight, $price])) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Оновити продукт
    public function update($id, $name, $description, $recipe_id, $weight, $price) {
        $sql = "UPDATE products 
                SET name = ?, description = ?, recipe_id = ?, weight = ?, price = ? 
                WHERE id = ?";
                
        return $this->db->query($sql, [$name, $description, $recipe_id, $weight, $price, $id]);
    }
    
    // Видалити продукт
    public function delete($id) {
        $sql = "DELETE FROM products WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Отримати продукти за рецептом
    public function getByRecipe($recipe_id) {
        $sql = "SELECT * FROM products WHERE recipe_id = ? ORDER BY name";
        return $this->db->resultSet($sql, [$recipe_id]);
    }
    
    // Розрахувати прибуток від продукту
    public function calculateProfit($product_id) {
        $sql = "SELECT p.price, 
                (SELECT SUM(ri.quantity * rm.price_per_unit) 
                 FROM recipe_ingredients ri 
                 JOIN raw_materials rm ON ri.raw_material_id = rm.id 
                 WHERE ri.recipe_id = p.recipe_id) as cost
                FROM products p
                WHERE p.id = ?";
                
        $result = $this->db->single($sql, [$product_id]);
        
        if ($result) {
            return ['price' => $result['price'], 'cost' => $result['cost'], 'profit' => $result['price'] - $result['cost']];
        }
        
        return false;
    }
    
    // Отримати статистику виробництва продукції
    public function getProductionStats($start_date, $end_date) {
        $sql = "SELECT p.id, p.name, SUM(pp.quantity) as total_produced, 
                p.price, (SUM(pp.quantity) * p.price) as total_value
                FROM products p
                JOIN production_processes pp ON p.id = pp.product_id
                WHERE pp.status = 'completed'
                AND pp.completed_at BETWEEN ? AND ?
                GROUP BY p.id, p.name
                ORDER BY total_value DESC";
                
        return $this->db->resultSet($sql, [$start_date, $end_date]);
    }
}