<?php
class SupplierController {
    private $orderModel;
    private $rawMaterialModel;
    
    public function __construct() {
        // Перевірка на авторизацію та роль
        if (!Auth::isLoggedIn() || !Auth::hasRole('supplier')) {
            Util::redirect(BASE_URL . '/home');
        }
        
        $this->orderModel = new Order();
        $this->rawMaterialModel = new RawMaterial();
    }
    
    // Управління замовленнями
    public function orders() {
        $data = [
            'title' => 'Мої замовлення',
            'orders' => $this->orderModel->getBySupplier(Auth::getCurrentUserId())
        ];
        
        include VIEWS_PATH . '/supplier/orders.php';
    }
    
    // Перегляд замовлення
    public function viewOrder($id) {
        $order = $this->orderModel->getById($id);
        
        // Перевірка на доступ до замовлення
        if (!$order || $order['supplier_id'] != Auth::getCurrentUserId()) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/supplier/orders');
        }
        
        $data = [
            'title' => 'Перегляд замовлення',
            'order' => $order,
            'items' => $this->orderModel->getItems($id)
        ];
        
        include VIEWS_PATH . '/supplier/view_order.php';
    }
    
    // Прийняття замовлення
    public function acceptOrder($id) {
        $order = $this->orderModel->getById($id);
        
        // Перевірка на доступ до замовлення
        if (!$order || $order['supplier_id'] != Auth::getCurrentUserId()) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/supplier/orders');
        }
        
        // Перевірка, чи можна прийняти замовлення
        if ($order['status'] !== 'pending') {
            $_SESSION['error'] = 'Можна прийняти тільки замовлення в статусі "Очікує підтвердження"';
            Util::redirect(BASE_URL . '/supplier/viewOrder/' . $id);
        }
        
        if ($this->orderModel->accept($id)) {
            // Відправляємо повідомлення адміністратору
            $messageModel = new Message();
            $messageModel->send(
                Auth::getCurrentUserId(),
                $order['ordered_by'],
                'Замовлення №' . $id . ' прийнято',
                'Повідомляємо, що замовлення №' . $id . ' прийнято до виконання. Планований термін доставки: ' . date('d.m.Y', strtotime($order['delivery_date'])) . '.'
            );
            
            $_SESSION['success'] = 'Замовлення успішно прийнято';
        } else {
            $_SESSION['error'] = 'Помилка при прийнятті замовлення';
        }
        
        Util::redirect(BASE_URL . '/supplier/viewOrder/' . $id);
    }
    
    // Відправлення замовлення
    public function shipOrder($id) {
        $order = $this->orderModel->getById($id);
        
        // Перевірка на доступ до замовлення
        if (!$order || $order['supplier_id'] != Auth::getCurrentUserId()) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/supplier/orders');
        }
        
        // Перевірка, чи можна відправити замовлення
        if ($order['status'] !== 'accepted') {
            $_SESSION['error'] = 'Можна відправити тільки прийняті замовлення';
            Util::redirect(BASE_URL . '/supplier/viewOrder/' . $id);
        }
        
        if ($this->orderModel->ship($id)) {
            // Відправляємо повідомлення адміністратору
            $messageModel = new Message();
            $messageModel->send(
                Auth::getCurrentUserId(),
                $order['ordered_by'],
                'Замовлення №' . $id . ' відправлено',
                'Повідомляємо, що замовлення №' . $id . ' відправлено. Очікуйте доставку найближчим часом.'
            );
            
            $_SESSION['success'] = 'Замовлення успішно відправлено';
        } else {
            $_SESSION['error'] = 'Помилка при відправленні замовлення';
        }
        
        Util::redirect(BASE_URL . '/supplier/viewOrder/' . $id);
    }
    
    // Скасування замовлення
    public function cancelOrder($id) {
        $order = $this->orderModel->getById($id);
        
        // Перевірка на доступ до замовлення
        if (!$order || $order['supplier_id'] != Auth::getCurrentUserId()) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/supplier/orders');
        }
        
        // Перевірка, чи можна скасувати замовлення
        if ($order['status'] === 'delivered' || $order['status'] === 'canceled') {
            $_SESSION['error'] = 'Неможливо скасувати замовлення в статусі "Доставлено" або "Скасовано"';
            Util::redirect(BASE_URL . '/supplier/viewOrder/' . $id);
        }
        
        if ($this->orderModel->cancel($id)) {
            // Відправляємо повідомлення адміністратору
            $messageModel = new Message();
            $messageModel->send(
                Auth::getCurrentUserId(),
                $order['ordered_by'],
                'Замовлення №' . $id . ' скасовано',
                'Нажаль, замовлення №' . $id . ' було скасовано з нашого боку. Просимо вибачення за незручності.'
            );
            
            $_SESSION['success'] = 'Замовлення успішно скасовано';
        } else {
            $_SESSION['error'] = 'Помилка при скасуванні замовлення';
        }
        
        Util::redirect(BASE_URL . '/supplier/viewOrder/' . $id);
    }
    
    // Управління сировиною
    public function materials() {
        $data = [
            'title' => 'Моя сировина',
            'materials' => $this->rawMaterialModel->getBySupplier(Auth::getCurrentUserId())
        ];
        
        include VIEWS_PATH . '/supplier/materials.php';
    }
    
    // Додавання сировини
    public function addMaterial() {
        $errors = [];
        
        // Обробка форми додавання сировини
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $description = Util::sanitize($_POST['description']);
            $unit = Util::sanitize($_POST['unit']);
            $price_per_unit = Util::sanitize($_POST['price_per_unit']);
            $min_stock = Util::sanitize($_POST['min_stock']);
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Назва сировини не може бути порожньою';
            }
            
            if (empty($unit)) {
                $errors['unit'] = 'Одиниця виміру не може бути порожньою';
            }
            
            if (empty($price_per_unit) || !is_numeric($price_per_unit) || $price_per_unit <= 0) {
                $errors['price_per_unit'] = 'Ціна повинна бути більше нуля';
            }
            
            if (empty($min_stock) || !is_numeric($min_stock) || $min_stock <= 0) {
                $errors['min_stock'] = 'Мінімальний запас повинен бути більше нуля';
            }
            
            // Якщо помилок немає, додаємо сировину
            if (empty($errors)) {
                if ($this->rawMaterialModel->add($name, $description, $unit, $price_per_unit, $min_stock, Auth::getCurrentUserId())) {
                    $_SESSION['success'] = 'Сировину успішно додано';
                    Util::redirect(BASE_URL . '/supplier/materials');
                } else {
                    $_SESSION['error'] = 'Помилка при додаванні сировини';
                }
            }
        }
        
        $data = [
            'title' => 'Додавання сировини',
            'errors' => $errors
        ];
        
        include VIEWS_PATH . '/supplier/add_material.php';
    }
    
    // Редагування сировини
    public function editMaterial($id) {
        $material = $this->rawMaterialModel->getById($id);
        
        // Перевірка на доступ до сировини
        if (!$material || $material['supplier_id'] != Auth::getCurrentUserId()) {
            $_SESSION['error'] = 'Сировину не знайдено';
            Util::redirect(BASE_URL . '/supplier/materials');
        }
        
        $errors = [];
        
        // Обробка форми редагування сировини
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $description = Util::sanitize($_POST['description']);
            $unit = Util::sanitize($_POST['unit']);
            $price_per_unit = Util::sanitize($_POST['price_per_unit']);
            $min_stock = Util::sanitize($_POST['min_stock']);
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Назва сировини не може бути порожньою';
            }
            
            if (empty($unit)) {
                $errors['unit'] = 'Одиниця виміру не може бути порожньою';
            }
            
            if (empty($price_per_unit) || !is_numeric($price_per_unit) || $price_per_unit <= 0) {
                $errors['price_per_unit'] = 'Ціна повинна бути більше нуля';
            }
            
            if (empty($min_stock) || !is_numeric($min_stock) || $min_stock <= 0) {
                $errors['min_stock'] = 'Мінімальний запас повинен бути більше нуля';
            }
            
            // Якщо помилок немає, оновлюємо сировину
            if (empty($errors)) {
                if ($this->rawMaterialModel->update($id, $name, $description, $unit, $price_per_unit, $min_stock, Auth::getCurrentUserId())) {
                    $_SESSION['success'] = 'Сировину успішно оновлено';
                    Util::redirect(BASE_URL . '/supplier/materials');
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні сировини';
                }
            }
        }
        
        $data = [
            'title' => 'Редагування сировини',
            'material' => $material,
            'errors' => $errors
        ];
        
        include VIEWS_PATH . '/supplier/edit_material.php';
    }
    
    // Видалення сировини
    public function deleteMaterial($id) {
        $material = $this->rawMaterialModel->getById($id);
        
        // Перевірка на доступ до сировини
        if (!$material || $material['supplier_id'] != Auth::getCurrentUserId()) {
            $_SESSION['error'] = 'Сировину не знайдено';
            Util::redirect(BASE_URL . '/supplier/materials');
        }
        
        if ($this->rawMaterialModel->delete($id)) {
            $_SESSION['success'] = 'Сировину успішно видалено';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні сировини';
        }
        
        Util::redirect(BASE_URL . '/supplier/materials');
    }
    
    // Звіти
    public function reports() {
        $data = [
            'title' => 'Звіти'
        ];
        
        include VIEWS_PATH . '/supplier/reports.php';
    }
    
    // Звіт по замовленнях
    public function ordersReport() {
        // Параметри періоду (за замовчуванням - поточний місяць)
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        // Отримуємо замовлення за період
        $sql = "SELECT o.*, 
                u.name as ordered_by_name
                FROM orders o
                JOIN users u ON o.ordered_by = u.id
                WHERE o.supplier_id = ? 
                AND o.created_at BETWEEN ? AND ?
                ORDER BY o.created_at DESC";
                
        $db = Database::getInstance();
        $orders = $db->resultSet($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        
        // Отримуємо статистику по матеріалах
        $sql = "SELECT 
                    r.name as material_name,
                    r.unit,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price_per_unit) as total_amount
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN raw_materials r ON oi.raw_material_id = r.id
                WHERE o.supplier_id = ?
                AND o.created_at BETWEEN ? AND ?
                GROUP BY oi.raw_material_id, r.name, r.unit
                ORDER BY total_amount DESC";
                
        $materials_stats = $db->resultSet($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        
        // Отримуємо загальну статистику
        $sql = "SELECT 
                    COUNT(o.id) as orders_count,
                    SUM(CASE WHEN o.status = 'delivered' THEN o.total_amount ELSE 0 END) as total_delivered,
                    SUM(CASE WHEN o.status = 'canceled' THEN o.total_amount ELSE 0 END) as total_canceled,
                    SUM(o.total_amount) as total_amount
                FROM orders o
                WHERE o.supplier_id = ?
                AND o.created_at BETWEEN ? AND ?";
                
        $summary = $db->single($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        
        $data = [
            'title' => 'Звіт по замовленнях',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'orders' => $orders,
            'materials_stats' => $materials_stats,
            'summary' => $summary
        ];
        
        include VIEWS_PATH . '/supplier/orders_report.php';
    }
    
    // Генерація PDF звіту по замовленнях
    public function generateOrdersPdf() {
        // Параметри періоду
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        // Отримуємо замовлення за період
        $sql = "SELECT o.*, 
                u.name as ordered_by_name
                FROM orders o
                JOIN users u ON o.ordered_by = u.id
                WHERE o.supplier_id = ? 
                AND o.created_at BETWEEN ? AND ?
                ORDER BY o.created_at DESC";
                
        $db = Database::getInstance();
        $orders = $db->resultSet($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        
        // Отримуємо статистику по матеріалах
        $sql = "SELECT 
                    r.name as material_name,
                    r.unit,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price_per_unit) as total_amount
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN raw_materials r ON oi.raw_material_id = r.id
                WHERE o.supplier_id = ?
                AND o.created_at BETWEEN ? AND ?
                GROUP BY oi.raw_material_id, r.name, r.unit
                ORDER BY total_amount DESC";
                
        $materials_stats = $db->resultSet($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        
        // Отримуємо загальну статистику
        $sql = "SELECT 
                    COUNT(o.id) as orders_count,
                    SUM(CASE WHEN o.status = 'delivered' THEN o.total_amount ELSE 0 END) as total_delivered,
                    SUM(CASE WHEN o.status = 'canceled' THEN o.total_amount ELSE 0 END) as total_canceled,
                    SUM(o.total_amount) as total_amount
                FROM orders o
                WHERE o.supplier_id = ?
                AND o.created_at BETWEEN ? AND ?";
                
        $summary = $db->single($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        
        $pdf = new PDF('Звіт по замовленнях');
        $pdf->addTitle('Звіт по замовленнях за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
        
        // Підготовка даних для таблиці замовлень
        $header = ['№', 'Дата', 'Замовник', 'Статус', 'Сума'];
        $data = [];
        
        foreach ($orders as $order) {
            $data[] = [
                $order['id'],
                date('d.m.Y', strtotime($order['created_at'])),
                $order['ordered_by_name'],
                Util::getOrderStatusName($order['status']),
                number_format($order['total_amount'], 2) . ' грн'
            ];
        }
        
        $pdf->addText('Список замовлень:');
        $pdf->addTable($header, $data);
        
        // Підготовка даних для таблиці статистики по матеріалах
        $header = ['Матеріал', 'Одиниці', 'Кількість', 'Загальна сума'];
        $data = [];
        
        foreach ($materials_stats as $item) {
            $data[] = [
                $item['material_name'],
                $item['unit'],
                number_format($item['total_quantity'], 2),
                number_format($item['total_amount'], 2) . ' грн'
            ];
        }
        
        $pdf->addText('Статистика по матеріалах:');
        $pdf->addTable($header, $data);
        
        // Загальна статистика
        $pdf->addText('Загальна статистика:');
        $pdf->addText('Кількість замовлень: ' . $summary['orders_count']);
        $pdf->addText('Сума доставлених замовлень: ' . number_format($summary['total_delivered'], 2) . ' грн');
        $pdf->addText('Сума скасованих замовлень: ' . number_format($summary['total_canceled'], 2) . ' грн');
        $pdf->addText('Загальна сума замовлень: ' . number_format($summary['total_amount'], 2) . ' грн');
        
        $pdf->addDateAndSignature();
        $pdf->output('orders_report_' . date('Y-m-d') . '.pdf');
    }
}