<?php
class ApiController {
    private $orderModel;
    
    public function __construct() {
        // Перевірка на авторизацію
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $this->orderModel = new Order();
    }
    
    // Оновлення позиції замовлення через AJAX
    public function updateOrderItem() {
        // Перевірка ролі
        if (!Auth::hasRole('admin')) {
            $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }
        
        // Отримуємо JSON дані
        $input = json_decode(file_get_contents('php://input'), true);
        
        $item_id = $input['item_id'] ?? null;
        $quantity = $input['quantity'] ?? null;
        $price_per_unit = $input['price_per_unit'] ?? null;
        
        // Валідація
        if (!$item_id || !$quantity || !$price_per_unit) {
            $this->jsonResponse(['success' => false, 'message' => 'Невірні дані']);
        }
        
        // Перевірка статусу замовлення
        $sql = "SELECT o.status FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE oi.id = ?";
        $db = Database::getInstance();
        $result = $db->single($sql, [$item_id]);
        
        if (!$result || $result['status'] !== 'pending') {
            $this->jsonResponse(['success' => false, 'message' => 'Замовлення не можна редагувати']);
        }
        
        // Оновлення
        if ($this->orderModel->updateItem($item_id, $quantity, $price_per_unit)) {
            $this->jsonResponse(['success' => true, 'message' => 'Позицію оновлено']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Помилка оновлення']);
        }
    }
    
    private function jsonResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}