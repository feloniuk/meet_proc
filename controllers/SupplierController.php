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

    // Главная панель поставщика
    public function index() {
        // Получаем активные заказы
        $active_orders = $this->orderModel->getBySupplier(Auth::getCurrentUserId());
        
        // Получаем материалы поставщика
        $materials = $this->rawMaterialModel->getBySupplier(Auth::getCurrentUserId());
        
        // Получаем последние сообщения
        $messageModel = new Message();
        $messages = $messageModel->getLatest(Auth::getCurrentUserId(), 5);
        $unread_messages = $messageModel->countUnread(Auth::getCurrentUserId());
        
        $data = [
            'title' => 'Панель постачальника',
            'active_orders' => $active_orders,
            'materials' => $materials,
            'messages' => $messages,
            'unread_messages' => $unread_messages
        ];
        
        include VIEWS_PATH . '/supplier/dashboard.php';
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
        
        // Створюємо PDF звіт
        $pdf = new PDF('Звіт по замовленнях');
        $pdf->addTitle('Звіт по замовленнях за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
        
        // Додаємо загальну статистику
        $pdf->addText('Загальна статистика:');
        $pdf->addText('Кількість замовлень: ' . $summary['orders_count']);
        $pdf->addText('Сума доставлених замовлень: ' . number_format($summary['total_delivered'], 2) . ' грн');
        $pdf->addText('Сума скасованих замовлень: ' . number_format($summary['total_canceled'], 2) . ' грн');
        $pdf->addText('Загальна сума замовлень: ' . number_format($summary['total_amount'], 2) . ' грн');
        $pdf->addText('');
        
        // Підготовка даних для таблиці замовлень
        if (!empty($orders)) {
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
        }
        
        // Підготовка даних для таблиці статистики по матеріалах
        if (!empty($materials_stats)) {
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
        }
        
        $pdf->addDateAndSignature();
        $pdf->output('orders_report_' . date('Y-m-d') . '.pdf');
    }

    // Метод печати заказа
    public function printOrder($id) {
        $order = $this->orderModel->getById($id);
        
        // Перевірка на доступ до замовлення
        if (!$order || $order['supplier_id'] != Auth::getCurrentUserId()) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/supplier/orders');
        }
        
        $items = $this->orderModel->getItems($id);
        
        // Створюємо PDF документ
        $pdf = new PDF('Замовлення №' . $order['id']);
        $pdf->addTitle('ЗАМОВЛЕННЯ №' . $order['id'], 'Дата: ' . date('d.m.Y', strtotime($order['created_at'])));
        
        // Інформація про замовлення
        $pdf->addText('Інформація про замовлення:');
        $pdf->addText('Замовник: ' . $order['ordered_by_name']);
        $pdf->addText('Постачальник: ' . Auth::getCurrentUserName());
        $pdf->addText('Статус: ' . Util::getOrderStatusName($order['status']));
        $pdf->addText('Дата замовлення: ' . date('d.m.Y H:i', strtotime($order['created_at'])));
        
        if (!empty($order['delivery_date'])) {
            $pdf->addText('Планована доставка: ' . date('d.m.Y', strtotime($order['delivery_date'])));
        }
        
        $pdf->addText('');
        
        // Позиції замовлення
        if (!empty($items)) {
            $header = ['№', 'Назва', 'Кількість', 'Ціна за од.', 'Сума'];
            $data = [];
            
            $counter = 1;
            foreach ($items as $item) {
                $data[] = [
                    $counter++,
                    $item['material_name'],
                    number_format($item['quantity'], 2) . ' ' . $item['unit'],
                    number_format($item['price_per_unit'], 2) . ' грн',
                    number_format($item['quantity'] * $item['price_per_unit'], 2) . ' грн'
                ];
            }
            
            // Додаємо рядок з загальною сумою
            $data[] = [
                '', '', '', 'ВСЬОГО:', 
                number_format($order['total_amount'], 2) . ' грн'
            ];
            
            $pdf->addText('Позиції замовлення:');
            $pdf->addTable($header, $data);
        }
        
        // Примітки
        if (!empty($order['notes'])) {
            $pdf->addText('Примітки:');
            $pdf->addText($order['notes']);
            $pdf->addText('');
        }
        
        // Підписи
        $pdf->addText('');
        $pdf->addText('Підпис постачальника: ________________');
        $pdf->addText('');
        $pdf->addText('Підпис замовника: ________________');
        
        $pdf->addDateAndSignature();
        $pdf->output('order_' . $order['id'] . '.pdf');
    }
    
    // Звіт по матеріалах
    public function materialsReport() {
        // Параметри періоду
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        // Отримуємо матеріали постачальника
        $materials = $this->rawMaterialModel->getBySupplier(Auth::getCurrentUserId());
        
        // Отримуємо статистику замовлень по матеріалах
        $sql = "SELECT 
                    r.id,
                    r.name,
                    r.unit,
                    r.price_per_unit,
                    r.min_stock,
                    COUNT(oi.id) as orders_count,
                    SUM(oi.quantity) as total_ordered,
                    SUM(oi.quantity * oi.price_per_unit) as total_amount
                FROM raw_materials r
                LEFT JOIN order_items oi ON r.id = oi.raw_material_id
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE r.supplier_id = ?
                AND (o.created_at BETWEEN ? AND ? OR o.created_at IS NULL)
                GROUP BY r.id, r.name, r.unit, r.price_per_unit, r.min_stock
                ORDER BY total_amount DESC";
                
        $db = Database::getInstance();
        $materials_stats = $db->resultSet($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        
        $data = [
            'title' => 'Звіт по матеріалах',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'materials' => $materials,
            'materials_stats' => $materials_stats
        ];
        
        include VIEWS_PATH . '/supplier/materials_report.php';
    }
    
    // Генерація PDF звіту по матеріалах
    public function generateMaterialsPdf() {
        // Параметри періоду
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        // Отримуємо статистику по матеріалах
        $sql = "SELECT 
                    r.id,
                    r.name,
                    r.unit,
                    r.price_per_unit,
                    r.min_stock,
                    COUNT(oi.id) as orders_count,
                    COALESCE(SUM(oi.quantity), 0) as total_ordered,
                    COALESCE(SUM(oi.quantity * oi.price_per_unit), 0) as total_amount
                FROM raw_materials r
                LEFT JOIN order_items oi ON r.id = oi.raw_material_id
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE r.supplier_id = ?
                AND (o.created_at BETWEEN ? AND ? OR o.created_at IS NULL)
                GROUP BY r.id, r.name, r.unit, r.price_per_unit, r.min_stock
                ORDER BY total_amount DESC";
                
        $db = Database::getInstance();
        $materials_stats = $db->resultSet($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        
        // Створюємо PDF звіт
        $pdf = new PDF('Звіт по матеріалах');
        $pdf->addTitle('Звіт по матеріалах за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
        
        // Додаємо інформацію про постачальника
        $pdf->addText('Постачальник: ' . Auth::getCurrentUserName());
        $pdf->addText('Дата формування: ' . date('d.m.Y H:i'));
        $pdf->addText('');
        
        // Загальна статистика
        $total_materials = count($materials_stats);
        $total_orders = array_sum(array_column($materials_stats, 'orders_count'));
        $total_revenue = array_sum(array_column($materials_stats, 'total_amount'));
        
        $pdf->addText('Загальна статистика:');
        $pdf->addText('Кількість видів матеріалів: ' . $total_materials);
        $pdf->addText('Загальна кількість замовлень: ' . $total_orders);
        $pdf->addText('Загальний дохід: ' . number_format($total_revenue, 2) . ' грн');
        $pdf->addText('');
        
        // Підготовка даних для таблиці
        if (!empty($materials_stats)) {
            $header = ['Назва', 'Од.', 'Ціна', 'Мін.запас', 'Замовлень', 'Кількість', 'Сума'];
            $data = [];
            
            foreach ($materials_stats as $material) {
                $data[] = [
                    $material['name'],
                    $material['unit'],
                    number_format($material['price_per_unit'], 2),
                    number_format($material['min_stock'], 2),
                    $material['orders_count'],
                    number_format($material['total_ordered'], 2),
                    number_format($material['total_amount'], 2) . ' грн'
                ];
            }
            
            $pdf->addText('Детальна статистика по матеріалах:');
            $pdf->addTable($header, $data);
        } else {
            $pdf->addText('Немає даних за вказаний період.');
        }
        
        $pdf->addDateAndSignature();
        $pdf->output('materials_report_' . date('Y-m-d') . '.pdf');
    }
}