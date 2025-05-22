<?php
class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Все существующие методы остаются без изменений...
    // Добавляем новые методы для работы с проверками качества
    
    // Обновить статус качества замовлення
    public function updateQualityStatus($order_id, $quality_status) {
        $sql = "UPDATE orders 
                SET quality_status = ? 
                WHERE id = ?";
                
        return $this->db->query($sql, [$quality_status, $order_id]);
    }
    
    // Отримати замовлення, що потребують перевірки якості
    public function getOrdersForQualityCheck() {
        $sql = "SELECT DISTINCT o.id, o.supplier_id, o.delivery_date, o.total_amount,
                us.name as supplier_name,
                uo.name as ordered_by_name
                FROM orders o 
                JOIN users us ON o.supplier_id = us.id
                JOIN users uo ON o.ordered_by = uo.id
                WHERE o.status = 'shipped' 
                AND o.quality_status IN ('not_checked')
                AND NOT EXISTS (
                    SELECT 1 FROM quality_checks qc 
                    WHERE qc.order_id = o.id
                )
                ORDER BY o.delivery_date ASC";
                
        return $this->db->resultSet($sql);
    }
    
    // Получить заказы с проблемами качества
    public function getQualityIssues() {
        $sql = "SELECT o.*, 
                us.name as supplier_name,
                qc.rejection_reason,
                qc.check_date,
                u_tech.name as technologist_name
                FROM orders o 
                JOIN users us ON o.supplier_id = us.id
                JOIN quality_checks qc ON o.id = qc.order_id
                JOIN users u_tech ON qc.technologist_id = u_tech.id
                WHERE o.quality_status = 'rejected'
                AND qc.status = 'rejected'
                ORDER BY qc.check_date DESC";
                
        return $this->db->resultSet($sql);
    }
    
    // Підтвердити отримання замовлення з урахуванням перевірки якості
    public function deliverWithQualityCheck($order_id, $inventory) {
        $order = $this->getById($order_id);
        
        if (!$order) {
            return false;
        }
        
        // Проверяем статус качества
        if ($order['quality_status'] !== 'approved') {
            return false; // Нельзя принимать без одобрения технолога
        }
        
        // Получаем элементы заказа
        $items = $this->getItems($order_id);
        
        if ($items) {
            // Обновляем инвентаризацию
            $this->db->beginTransaction();
            
            try {
                foreach ($items as $item) {
                    $inventory->addQuantity($item['raw_material_id'], $item['quantity'], Auth::getCurrentUserId());
                }
                
                // Обновляем статус заказа
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
    
    // Автоматичне встановлення потреби в перевірці якості
    public function ship($order_id) {
        // Сначала обновляем статус на 'shipped'
        $result = $this->updateStatus($order_id, 'shipped');
        
        if ($result) {
            // Устанавливаем потребность в проверке качества
            $this->updateQualityStatus($order_id, 'not_checked');
            
            // Автоматически создаем проверку качества
            $this->createQualityCheckForOrder($order_id);
            
            // Отправляем уведомления технологу
            $this->notifyTechnologistAboutDelivery($order_id);
        }
        
        return $result;
    }

    private function createQualityCheckForOrder($order_id) {
        // Находим технологов в системе
        $sql = "SELECT id FROM users WHERE role = 'technologist' AND id IS NOT NULL LIMIT 1";
        $technologist = $this->db->single($sql);
        
        if ($technologist) {
            // Создаем проверку качества
            $qualityCheckModel = new QualityCheck();
            $qualityCheckModel->create(
                $order_id, 
                $technologist['id'], 
                'Автоматично створена перевірка при доставці замовлення'
            );
        }
    }
    
    // Повідомлення технологу про доставку
    private function notifyTechnologistAboutDelivery($order_id) {
        $order = $this->getById($order_id);
        
        if ($order) {
            // Находим всех технологов
            $sql = "SELECT id, name FROM users WHERE role = 'technologist'";
            $technologists = $this->db->resultSet($sql);
            
            if (!empty($technologists)) {
                $messageModel = new Message();
                
                foreach ($technologists as $technologist) {
                    $messageModel->send(
                        $order['supplier_id'], // От поставщика
                        $technologist['id'],
                        'Нова сировина для перевірки - Замовлення №' . $order_id,
                        'Доставлена сировина по замовленню №' . $order_id . ' від постачальника "' . $order['supplier_name'] . '". ' .
                        'Загальна сума: ' . Util::formatMoney($order['total_amount']) . '. ' .
                        'Потрібна перевірка якості перед прийманням на склад.'
                    );
                }
            }
        }
    }
    
    // Статистика по якості замовлень
    public function getQualityStatsByPeriod($start_date, $end_date) {
        $sql = "SELECT 
                    COUNT(o.id) as total_orders,
                    SUM(CASE WHEN o.quality_status = 'approved' THEN 1 ELSE 0 END) as approved_orders,
                    SUM(CASE WHEN o.quality_status = 'rejected' THEN 1 ELSE 0 END) as rejected_orders,
                    SUM(CASE WHEN o.quality_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN o.quality_status = 'not_checked' THEN 1 ELSE 0 END) as not_checked_orders,
                    ROUND(SUM(CASE WHEN o.quality_status = 'approved' THEN 1 ELSE 0 END) * 100.0 / COUNT(o.id), 2) as approval_rate
                FROM orders o
                WHERE o.created_at BETWEEN ? AND ?
                AND o.status IN ('shipped', 'delivered')";
                
        return $this->db->single($sql, [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    }
    
    // Получить проблемных поставщиков по качеству
    public function getProblematicSuppliers($start_date, $end_date) {
        $sql = "SELECT 
                    u.id,
                    u.name as supplier_name,
                    COUNT(o.id) as total_orders,
                    SUM(CASE WHEN o.quality_status = 'rejected' THEN 1 ELSE 0 END) as rejected_orders,
                    ROUND(SUM(CASE WHEN o.quality_status = 'rejected' THEN 1 ELSE 0 END) * 100.0 / COUNT(o.id), 2) as rejection_rate,
                    GROUP_CONCAT(DISTINCT qc.rejection_reason SEPARATOR '; ') as common_issues
                FROM users u
                JOIN orders o ON u.id = o.supplier_id
                LEFT JOIN quality_checks qc ON o.id = qc.order_id AND qc.status = 'rejected'
                WHERE u.role = 'supplier'
                AND o.created_at BETWEEN ? AND ?
                AND o.status IN ('shipped', 'delivered')
                GROUP BY u.id, u.name
                HAVING rejection_rate > 10
                ORDER BY rejection_rate DESC";
                
        return $this->db->resultSet($sql, [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    }
    
    // Всі існуючі методи залишаються незмінними...
    
    // Отримати всі замовлення
    public function getAll() {
        $sql = "SELECT o.*, 
                us.name as supplier_name, 
                uo.name as ordered_by_name,
                CASE 
                    WHEN o.quality_status = 'approved' THEN 'Схвалено'
                    WHEN o.quality_status = 'rejected' THEN 'Відхилено'
                    WHEN o.quality_status = 'pending' THEN 'На перевірці'
                    ELSE 'Не перевірялось'
                END as quality_status_name,
                qc.id as quality_check_id,
                qc.technologist_id,
                u_tech.name as technologist_name
                FROM orders o 
                JOIN users us ON o.supplier_id = us.id
                JOIN users uo ON o.ordered_by = uo.id
                LEFT JOIN quality_checks qc ON o.id = qc.order_id
                LEFT JOIN users u_tech ON qc.technologist_id = u_tech.id
                ORDER BY o.created_at DESC";
        return $this->db->resultSet($sql);
    }

    public function setupQualityChecksForExistingOrders() {
        // Находим заказы со статусом 'delivered' без статуса качества
        $sql = "UPDATE orders 
                SET quality_check_required = TRUE, 
                    quality_status = 'approved' 
                WHERE status = 'delivered' 
                AND (quality_status IS NULL OR quality_status = 'not_checked')";
        
        $this->db->query($sql);
        
        // Находим заказы со статусом 'shipped' без проверок качества
        $sql = "UPDATE orders 
                SET quality_check_required = TRUE, 
                    quality_status = 'not_checked' 
                WHERE status = 'shipped' 
                AND (quality_status IS NULL OR quality_status = 'not_checked')";
        
        $this->db->query($sql);
        
        return true;
    }
    
    // Обновленный метод получения заказа по ID с информацией о качестве
    public function getById($id) {
        $sql = "SELECT o.*, 
                us.name as supplier_name, us.email as supplier_email, us.phone as supplier_phone,
                uo.name as ordered_by_name,
                CASE 
                    WHEN o.quality_status = 'approved' THEN 'Схвалено'
                    WHEN o.quality_status = 'rejected' THEN 'Відхилено'
                    WHEN o.quality_status = 'pending' THEN 'На перевірці'
                    ELSE 'Не перевірялось'
                END as quality_status_name,
                qc.id as quality_check_id,
                qc.technologist_id,
                u_tech.name as technologist_name
                FROM orders o 
                JOIN users us ON o.supplier_id = us.id
                JOIN users uo ON o.ordered_by = uo.id
                LEFT JOIN quality_checks qc ON o.id = qc.order_id
                LEFT JOIN users u_tech ON qc.technologist_id = u_tech.id
                WHERE o.id = ?";
        return $this->db->single($sql, [$id]);
    }
    
    // Отримати замовлення за постачальником
    public function getBySupplier($supplier_id) {
        $sql = "SELECT o.*, uo.name as ordered_by_name,
                CASE 
                    WHEN o.quality_status = 'approved' THEN 'Схвалено'
                    WHEN o.quality_status = 'rejected' THEN 'Відхилено'
                    WHEN o.quality_status = 'pending' THEN 'На перевірці'
                    ELSE 'Не перевірялось'
                END as quality_status_name
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
                uo.name as ordered_by_name,
                CASE 
                    WHEN o.quality_status = 'approved' THEN 'Схвалено'
                    WHEN o.quality_status = 'rejected' THEN 'Відхилено'
                    WHEN o.quality_status = 'pending' THEN 'На перевірці'
                    ELSE 'Не перевірялось'
                END as quality_status_name
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
        $sql = "INSERT INTO orders (supplier_id, ordered_by, status, delivery_date, total_amount, notes, quality_check_required, quality_status) 
                VALUES (?, ?, 'pending', ?, 0, ?, 1, 'not_checked')";
                
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
                SET status = ?, updated_at = NOW() 
                WHERE id = ?";
                
        return $this->db->query($sql, [$status, $order_id]);
    }
    
    // Прийняти замовлення (для постачальника)
    public function accept($order_id) {
        return $this->updateStatus($order_id, 'accepted');
    }
    
    // Скасувати замовлення
    public function cancel($order_id) {
        return $this->updateStatus($order_id, 'canceled');
    }
    
    // Підтвердити отримання замовлення (тільки після схвалення якості)
    public function deliver($order_id, $inventory) {
        return $this->deliverWithQualityCheck($order_id, $inventory);
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