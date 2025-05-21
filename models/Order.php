<?php
class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Отримати всі замовлення
    public function getAll() {
        $sql = "SELECT o.*, 
                us.name as supplier_name, 
                uo.name as ordered_by_name
                FROM orders o 
                JOIN users us ON o.supplier_id = us.id
                JOIN users uo ON o.ordered_by = uo.id
                ORDER BY o.created_at DESC";
        return $this->db->resultSet($sql);
    }
    
    // Отримати замовлення за ID
    public function getById($id) {
        $sql = "SELECT o.*, 
                us.name as supplier_name, us.email as supplier_email, us.phone as supplier_phone,
                uo.name as ordered_by_name
                FROM orders o 
                JOIN users us ON o.supplier_id = us.id
                JOIN users uo ON o.ordered_by = uo.id
                WHERE o.id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Отримати замовлення за постачальником
    public function getBySupplier($supplier_id) {
        $sql = "SELECT o.*, uo.name as ordered_by_name
                FROM orders o 
                JOIN users uo ON o.ordered_by = uo.id
                WHERE o.supplier_id = ?
                ORDER BY o.created_at DESC";
        return $this->db->resultSet($sql, [$supplier_id]);
    }
    
    // Отримати активні замовлення
    public function getActive() {
        $sql = "SELECT o.*, 
                us.name as supplier_name, 
                uo.name as ordered_by_name
                FROM orders o 
                JOIN users us ON o.supplier_id = us.id
                JOIN users uo ON o.ordered_by = uo.id
                WHERE o.status IN ('pending', 'accepted', 'shipped')
                ORDER BY o.delivery_date, o.created_at";
        return $this->db->resultSet($sql);
    }
    
    // Отримати елементи замовлення
    public function getItems($order_id) {
        $sql = "SELECT oi.*, r.name as material_name, r.unit
                FROM order_items oi 
                JOIN raw_materials r ON oi.raw_material_id = r.id
                WHERE oi.order_id = ?
                ORDER BY oi.id";
        return $this->db->resultSet($sql, [$order_id]);
    }
    
    // Створити нове замовлення
    public function create($supplier_id, $ordered_by, $delivery_date, $notes) {
        $sql = "INSERT INTO orders (supplier_id, ordered_by, status, delivery_date, total_amount, notes) 
                VALUES (?, ?, 'pending', ?, 0, ?)";
                
        if ($this->db->query($sql, [$supplier_id, $ordered_by, $delivery_date, $notes])) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Додати елемент до замовлення
    public function addItem($order_id, $raw_material_id, $quantity, $price_per_unit) {
        $sql = "INSERT INTO order_items (order_id, raw_material_id, quantity, price_per_unit) 
                VALUES (?, ?, ?, ?)";
                
        if ($this->db->query($sql, [$order_id, $raw_material_id, $quantity, $price_per_unit])) {
            // Оновлюємо загальну суму замовлення
            $this->updateTotalAmount($order_id);
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Оновити елемент замовлення
    public function updateItem($item_id, $quantity, $price_per_unit) {
        // Отримуємо ID замовлення для оновлення загальної суми
        $sql = "SELECT order_id FROM order_items WHERE id = ?";
        $item = $this->db->single($sql, [$item_id]);
        
        if ($item) {
            $sql = "UPDATE order_items 
                    SET quantity = ?, price_per_unit = ? 
                    WHERE id = ?";
                    
            if ($this->db->query($sql, [$quantity, $price_per_unit, $item_id])) {
                // Оновлюємо загальну суму замовлення
                $this->updateTotalAmount($item['order_id']);
                return true;
            }
        }
        
        return false;
    }
    
    // Видалити елемент замовлення
    public function deleteItem($item_id) {
        // Отримуємо ID замовлення для оновлення загальної суми
        $sql = "SELECT order_id FROM order_items WHERE id = ?";
        $item = $this->db->single($sql, [$item_id]);
        
        if ($item) {
            $sql = "DELETE FROM order_items WHERE id = ?";
            
            if ($this->db->query($sql, [$item_id])) {
                // Оновлюємо загальну суму замовлення
                $this->updateTotalAmount($item['order_id']);
                return true;
            }
        }
        
        return false;
    }
    
    // Оновити загальну суму замовлення
    private function updateTotalAmount($order_id) {
        $sql = "UPDATE orders 
                SET total_amount = (
                    SELECT SUM(quantity * price_per_unit) 
                    FROM order_items 
                    WHERE order_id = ?
                ) 
                WHERE id = ?";
                
        return $this->db->query($sql, [$order_id, $order_id]);
    }
    
    // Оновити статус замовлення
    public function updateStatus($order_id, $status) {
        $sql = "UPDATE orders 
                SET status = ? 
                WHERE id = ?";
                
        return $this->db->query($sql, [$status, $order_id]);
    }
    
    // Прийняти замовлення (для постачальника)
    public function accept($order_id) {
        return $this->updateStatus($order_id, 'accepted');
    }
    
    // Відправити замовлення (для постачальника)
    public function ship($order_id) {
        return $this->updateStatus($order_id, 'shipped');
    }
    
    // Підтвердити отримання замовлення (для адміністратора)
    public function deliver($order_id, $inventory) {
        // Отримуємо елементи замовлення
        $items = $this->getItems($order_id);
        
        if ($items) {
            // Оновлюємо інвентаризацію
            $this->db->beginTransaction();
            
            try {
                foreach ($items as $item) {
                    $inventory->addQuantity($item['raw_material_id'], $item['quantity'], Auth::getCurrentUserId());
                }
                
                // Оновлюємо статус замовлення
                $this->updateStatus($order_id, 'delivered');
                
                $this->db->commit();
                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                return false;
            }
        }
        
        return false;
    }
    
    // Скасувати замовлення
    public function cancel($order_id) {
        return $this->updateStatus($order_id, 'canceled');
    }
    
    // Отримати статистику замовлень за період
    public function getStatsByPeriod($start_date, $end_date) {
        $sql = "SELECT 
                    DATE(o.created_at) as date,
                    COUNT(o.id) as orders_count,
                    SUM(o.total_amount) as total_amount
                FROM orders o
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY DATE(o.created_at)
                ORDER BY date";
                
        return $this->db->resultSet($sql, [$start_date, $end_date]);
    }
    
    // Отримати статистику за постачальниками
    public function getStatsBySupplier($start_date, $end_date) {
        $sql = "SELECT 
                    u.name as supplier_name,
                    COUNT(o.id) as orders_count,
                    SUM(o.total_amount) as total_amount
                FROM orders o
                JOIN users u ON o.supplier_id = u.id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY o.supplier_id, u.name
                ORDER BY total_amount DESC";
                
        return $this->db->resultSet($sql, [$start_date, $end_date]);
    }

    public function getStatsByMaterial($start_date, $end_date) {
        $sql = "SELECT 
                    r.name as material_name,
                    r.unit,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price_per_unit) as total_amount
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN raw_materials r ON oi.raw_material_id = r.id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY oi.raw_material_id, r.name, r.unit
                ORDER BY total_amount DESC";
                
        return $this->db->resultSet($sql, [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    }
}