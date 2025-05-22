<?php
class TechnologistController {
    private $qualityCheckModel;
    private $orderModel;
    private $rawMaterialModel;
    private $messageModel;
    private $inventoryModel;
    
    public function __construct() {
        // Перевірка на авторизацію та роль
        if (!Auth::isLoggedIn() || !Auth::hasRole('technologist')) {
            Util::redirect(BASE_URL . '/home');
        }
        
        $this->qualityCheckModel = new QualityCheck();
        $this->orderModel = new Order();
        $this->rawMaterialModel = new RawMaterial();
        $this->messageModel = new Message();
        $this->inventoryModel = new Inventory();
    }
    
    // Головна панель технолога
    public function index() {
        // Отримуємо замовлення, що потребують перевірки
        $pending_checks = $this->qualityCheckModel->getPendingChecks();
        $completed_checks = $this->qualityCheckModel->getRecentChecks(Auth::getCurrentUserId(), 5);
        
        // Автоматично створюємо перевірки для нових замовлень
        $this->createAutoQualityChecks();
        
        // Повідомлення
        $messages = $this->messageModel->getLatest(Auth::getCurrentUserId(), 5);
        $unread_messages = $this->messageModel->countUnread(Auth::getCurrentUserId());
        
        $data = [
            'title' => 'Панель технолога',
            'pending_checks' => $pending_checks,
            'completed_checks' => $completed_checks,
            'messages' => $messages,
            'unread_messages' => $unread_messages
        ];
        
        include VIEWS_PATH . '/technologist/dashboard.php';
    }
    
    // Автоматичне створення перевірок для нових замовлень
    private function createAutoQualityChecks() {
        // Знаходимо замовлення зі статусом 'shipped' без перевірки якості
        $sql = "SELECT DISTINCT o.id 
                FROM orders o 
                WHERE o.status = 'shipped' 
                AND o.quality_status = 'not_checked'
                AND NOT EXISTS (
                    SELECT 1 FROM quality_checks qc WHERE qc.order_id = o.id
                )";
        
        $db = Database::getInstance();
        $orders = $db->resultSet($sql);
        
        foreach ($orders as $order) {
            $this->qualityCheckModel->create(
                $order['id'], 
                Auth::getCurrentUserId(), 
                'Автоматично створена перевірка при доставці'
            );
            
            // Оновлюємо статус замовлення
            $this->orderModel->updateQualityStatus($order['id'], 'pending');
        }
    }
    
    // Швидке схвалення/відхилення з детальною перевіркою
    public function quickApproval($check_id) {
        $check = $this->qualityCheckModel->getById($check_id);
        
        if (!$check) {
            $_SESSION['error'] = 'Перевірку якості не знайдено';
            Util::redirect(BASE_URL . '/technologist/qualityChecks');
        }
        
        $errors = [];
        
        if (Util::isPost()) {
            $action = Util::sanitize($_POST['action']);
            $temperature = Util::sanitize($_POST['temperature']);
            $ph_level = Util::sanitize($_POST['ph_level']);
            $visual_grade = Util::sanitize($_POST['visual_grade']);
            $smell_grade = Util::sanitize($_POST['smell_grade']);
            $notes = Util::sanitize($_POST['notes']);
            $rejection_reason = Util::sanitize($_POST['rejection_reason']);
            
            // Валідація базових параметрів
            if (!empty($temperature) && (!is_numeric($temperature) || $temperature < -10 || $temperature > 10)) {
                $errors['temperature'] = 'Температура повинна бути від -10°C до +10°C';
            }
            
            if (!empty($ph_level) && (!is_numeric($ph_level) || $ph_level < 4 || $ph_level > 8)) {
                $errors['ph_level'] = 'pH повинен бути від 4 до 8';
            }
            
            if ($action === 'reject' && empty($rejection_reason)) {
                $errors['rejection_reason'] = 'Вкажіть причину відхилення';
            }
            
            if (empty($errors)) {
                $status = $action === 'approve' ? 'approved' : 'rejected';
                $overall_grade = $this->calculateOverallGrade($visual_grade, $smell_grade, $temperature, $ph_level);
                
                $updateData = [
                    'temperature' => !empty($temperature) ? $temperature : null,
                    'ph_level' => !empty($ph_level) ? $ph_level : null,
                    'visual_assessment' => $visual_grade,
                    'smell_assessment' => $smell_grade,
                    'overall_grade' => $overall_grade,
                    'status' => $status,
                    'notes' => $notes,
                    'rejection_reason' => $rejection_reason
                ];
                
                if ($this->qualityCheckModel->update($check_id, $updateData)) {
                    // Оновлюємо статус якості в замовленні
                    $this->orderModel->updateQualityStatus($check['order_id'], $status);
                    
                    // Відправляємо повідомлення
                    $this->sendQualityCheckNotification($check, $status);
                    
                    // Якщо схвалено - додаємо до інвентарю
                    if ($status === 'approved') {
                        $this->addApprovedMaterialsToInventory($check['order_id']);
                    }
                    
                    $_SESSION['success'] = $status === 'approved' ? 'Сировину схвалено' : 'Сировину відхилено';
                    Util::redirect(BASE_URL . '/technologist/qualityChecks');
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні перевірки якості';
                }
            }
        }
        
        $order_items = $this->orderModel->getItems($check['order_id']);
        $standards = $this->qualityCheckModel->getStandardsForOrder($check['order_id']);
        
        $data = [
            'title' => 'Швидка перевірка якості',
            'check' => $check,
            'order_items' => $order_items,
            'standards' => $standards,
            'errors' => $errors
        ];
        
        include VIEWS_PATH . '/technologist/quick_approval.php';
    }

// Создание новой проверки качества (если нужно создать вручную)
public function createQualityCheck() {
    $errors = [];
    
    // Получаем заказы, которые нуждаются в проверке
    $ordersForCheck = $this->orderModel->getOrdersForQualityCheck();
    
    if (Util::isPost()) {
        $order_id = Util::sanitize($_POST['order_id']);
        $notes = Util::sanitize($_POST['notes']);
        
        // Валидация
        if (empty($order_id)) {
            $errors['order_id'] = 'Виберіть замовлення';
        }
        
        // Проверяем, не существует ли уже проверка для этого заказа
        $existingCheck = $this->qualityCheckModel->getByOrderId($order_id);
        if ($existingCheck) {
            $errors['order_id'] = 'Для цього замовлення вже створена перевірка якості';
        }
        
        if (empty($errors)) {
            $check_id = $this->qualityCheckModel->create($order_id, Auth::getCurrentUserId(), $notes);
            
            if ($check_id) {
                $_SESSION['success'] = 'Перевірку якості успішно створено';
                Util::redirect(BASE_URL . '/technologist/editQualityCheck/' . $check_id);
            } else {
                $_SESSION['error'] = 'Помилка при створенні перевірки якості';
            }
        }
    }
    
    $data = [
        'title' => 'Створення перевірки якості',
        'orders' => $ordersForCheck,
        'errors' => $errors
    ];
    
    include VIEWS_PATH . '/technologist/create_quality_check.php';
}

// Редактирование проверки качества (детальная форма)
public function editQualityCheck($id) {
    $check = $this->qualityCheckModel->getById($id);
    
    if (!$check) {
        $_SESSION['error'] = 'Перевірку якості не знайдено';
        Util::redirect(BASE_URL . '/technologist/qualityChecks');
    }
    
    $errors = [];
    
    if (Util::isPost()) {
        $temperature = Util::sanitize($_POST['temperature']);
        $ph_level = Util::sanitize($_POST['ph_level']);
        $moisture_content = Util::sanitize($_POST['moisture_content']);
        $visual_assessment = Util::sanitize($_POST['visual_assessment']);
        $smell_assessment = Util::sanitize($_POST['smell_assessment']);
        $texture_assessment = Util::sanitize($_POST['texture_assessment']);
        $notes = Util::sanitize($_POST['notes']);
        $status = Util::sanitize($_POST['status']);
        $rejection_reason = Util::sanitize($_POST['rejection_reason']);
        
        // Валидация
        if (!empty($temperature) && (!is_numeric($temperature) || $temperature < -10 || $temperature > 10)) {
            $errors['temperature'] = 'Температура повинна бути від -10°C до +10°C';
        }
        
        if (!empty($ph_level) && (!is_numeric($ph_level) || $ph_level < 4 || $ph_level > 8)) {
            $errors['ph_level'] = 'pH повинен бути від 4 до 8';
        }
        
        if (!empty($moisture_content) && (!is_numeric($moisture_content) || $moisture_content < 0 || $moisture_content > 100)) {
            $errors['moisture_content'] = 'Вологість повинна бути від 0% до 100%';
        }
        
        if ($status === 'rejected' && empty($rejection_reason)) {
            $errors['rejection_reason'] = 'Вкажіть причину відхилення';
        }
        
        if (empty($errors)) {
            $overall_grade = $this->calculateOverallGrade($visual_assessment, $smell_assessment, $temperature, $ph_level);
            
            $updateData = [
                'temperature' => !empty($temperature) ? $temperature : null,
                'ph_level' => !empty($ph_level) ? $ph_level : null,
                'moisture_content' => !empty($moisture_content) ? $moisture_content : null,
                'visual_assessment' => $visual_assessment,
                'smell_assessment' => $smell_assessment,
                'texture_assessment' => $texture_assessment,
                'overall_grade' => $overall_grade,
                'status' => $status,
                'notes' => $notes,
                'rejection_reason' => $rejection_reason
            ];
            
            if ($this->qualityCheckModel->update($id, $updateData)) {
                // Обновляем статус качества в заказе
                $this->orderModel->updateQualityStatus($check['order_id'], $status);
                
                // Отправляем уведомления
                $this->sendQualityCheckNotification($check, $status);
                
                // Если одобрено - добавляем в инвентарь
                if ($status === 'approved') {
                    $this->addApprovedMaterialsToInventory($check['order_id']);
                }
                
                $_SESSION['success'] = 'Перевірку якості успішно оновлено';
                Util::redirect(BASE_URL . '/technologist/viewQualityCheck/' . $id);
            } else {
                $_SESSION['error'] = 'Помилка при оновленні перевірки якості';
            }
        }
    }
    
    $order_items = $this->orderModel->getItems($check['order_id']);
    $standards = $this->qualityCheckModel->getStandardsForOrder($check['order_id']);
    
    $data = [
        'title' => 'Редагування перевірки якості',
        'check' => $check,
        'order_items' => $order_items,
        'standards' => $standards,
        'errors' => $errors
    ];
    
    include VIEWS_PATH . '/technologist/edit_quality_check.php';
}

// Управление стандартами качества
public function qualityStandards() {
    $standards = $this->qualityCheckModel->getAllStandards();
    
    $data = [
        'title' => 'Стандарти якості',
        'standards' => $standards
    ];
    
    include VIEWS_PATH . '/technologist/quality_standards.php';
}

// Добавление стандарта качества
public function addQualityStandard() {
    $errors = [];
    
    // Получаем все материалы для выбора
    $rawMaterialModel = new RawMaterial();
    $materials = $rawMaterialModel->getAll();
    
    if (Util::isPost()) {
        $raw_material_id = Util::sanitize($_POST['raw_material_id']);
        $parameter_name = Util::sanitize($_POST['parameter_name']);
        $min_value = Util::sanitize($_POST['min_value']);
        $max_value = Util::sanitize($_POST['max_value']);
        $unit = Util::sanitize($_POST['unit']);
        $description = Util::sanitize($_POST['description']);
        $is_critical = isset($_POST['is_critical']) ? 1 : 0;
        
        // Валидация
        if (empty($raw_material_id)) {
            $errors['raw_material_id'] = 'Виберіть сировину';
        }
        
        if (empty($parameter_name)) {
            $errors['parameter_name'] = 'Назва параметру не може бути порожньою';
        }
        
        if (!empty($min_value) && !is_numeric($min_value)) {
            $errors['min_value'] = 'Мінімальне значення повинно бути числом';
        }
        
        if (!empty($max_value) && !is_numeric($max_value)) {
            $errors['max_value'] = 'Максимальне значення повинно бути числом';
        }
        
        if (!empty($min_value) && !empty($max_value) && floatval($min_value) >= floatval($max_value)) {
            $errors['max_value'] = 'Максимальне значення повинно бути більше мінімального';
        }
        
        if (empty($errors)) {
            if ($this->qualityCheckModel->addStandard($raw_material_id, $parameter_name, $min_value, $max_value, $unit, $description, $is_critical)) {
                $_SESSION['success'] = 'Стандарт якості успішно додано';
                Util::redirect(BASE_URL . '/technologist/qualityStandards');
            } else {
                $_SESSION['error'] = 'Помилка при додаванні стандарту якості';
            }
        }
    }
    
    $data = [
        'title' => 'Додавання стандарту якості',
        'materials' => $materials,
        'errors' => $errors
    ];
    
    include VIEWS_PATH . '/technologist/add_quality_standard.php';
}

// Редактирование стандарта качества
public function editQualityStandard($id) {
    $sql = "SELECT qs.*, rm.name as material_name 
            FROM quality_standards qs
            JOIN raw_materials rm ON qs.raw_material_id = rm.id
            WHERE qs.id = ?";
    
    $db = Database::getInstance();
    $standard = $db->single($sql, [$id]);
    
    if (!$standard) {
        $_SESSION['error'] = 'Стандарт якості не знайдено';
        Util::redirect(BASE_URL . '/technologist/qualityStandards');
    }
    
    $errors = [];
    
    if (Util::isPost()) {
        $parameter_name = Util::sanitize($_POST['parameter_name']);
        $min_value = Util::sanitize($_POST['min_value']);
        $max_value = Util::sanitize($_POST['max_value']);
        $unit = Util::sanitize($_POST['unit']);
        $description = Util::sanitize($_POST['description']);
        $is_critical = isset($_POST['is_critical']) ? 1 : 0;
        
        // Валидация
        if (empty($parameter_name)) {
            $errors['parameter_name'] = 'Назва параметру не може бути порожньою';
        }
        
        if (!empty($min_value) && !is_numeric($min_value)) {
            $errors['min_value'] = 'Мінімальне значення повинно бути числом';
        }
        
        if (!empty($max_value) && !is_numeric($max_value)) {
            $errors['max_value'] = 'Максимальне значення повинно бути числом';
        }
        
        if (!empty($min_value) && !empty($max_value) && floatval($min_value) >= floatval($max_value)) {
            $errors['max_value'] = 'Максимальне значення повинно бути більше мінімального';
        }
        
        if (empty($errors)) {
            if ($this->qualityCheckModel->updateStandard($id, $parameter_name, $min_value, $max_value, $unit, $description, $is_critical)) {
                $_SESSION['success'] = 'Стандарт якості успішно оновлено';
                Util::redirect(BASE_URL . '/technologist/qualityStandards');
            } else {
                $_SESSION['error'] = 'Помилка при оновленні стандарту якості';
            }
        }
    }
    
    $data = [
        'title' => 'Редагування стандарту якості',
        'standard' => $standard,
        'errors' => $errors
    ];
    
    include VIEWS_PATH . '/technologist/edit_quality_standard.php';
}

// Удаление стандарта качества
public function deleteQualityStandard($id) {
    if ($this->qualityCheckModel->deleteStandard($id)) {
        $_SESSION['success'] = 'Стандарт якості успішно видалено';
    } else {
        $_SESSION['error'] = 'Помилка при видаленні стандарту якості';
    }
    
    Util::redirect(BASE_URL . '/technologist/qualityStandards');
}

// Быстрые действия (approve/reject без детальной проверки)
public function quickAction($check_id, $action) {
    $check = $this->qualityCheckModel->getById($check_id);
    
    if (!$check) {
        $_SESSION['error'] = 'Перевірку якості не знайдено';
        Util::redirect(BASE_URL . '/technologist/qualityChecks');
    }
    
    if ($action === 'approve') {
        $updateData = [
            'status' => 'approved',
            'overall_grade' => 'good',
            'notes' => 'Швидко схвалено технологом'
        ];
        
        if ($this->qualityCheckModel->update($check_id, $updateData)) {
            $this->orderModel->updateQualityStatus($check['order_id'], 'approved');
            $this->sendQualityCheckNotification($check, 'approved');
            $this->addApprovedMaterialsToInventory($check['order_id']);
            
            $_SESSION['success'] = 'Сировину швидко схвалено';
        } else {
            $_SESSION['error'] = 'Помилка при схваленні сировини';
        }
        
    } elseif ($action === 'reject') {
        $updateData = [
            'status' => 'rejected',
            'overall_grade' => 'unsatisfactory',
            'rejection_reason' => 'Відхилено за результатами швидкої перевірки',
            'notes' => 'Швидко відхилено технологом'
        ];
        
        if ($this->qualityCheckModel->update($check_id, $updateData)) {
            $this->orderModel->updateQualityStatus($check['order_id'], 'rejected');
            $this->sendQualityCheckNotification($check, 'rejected');
            
            $_SESSION['success'] = 'Сировину швидко відхилено';
        } else {
            $_SESSION['error'] = 'Помилка при відхиленні сировини';
        }
    }
    
    Util::redirect(BASE_URL . '/technologist');
}

// Генерация PDF отчета по качеству
public function generateQualityReportPdf() {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
    
    $stats = $this->qualityCheckModel->getStatsByPeriod($start_date, $end_date);
    $material_stats = $this->qualityCheckModel->getMaterialStatsByPeriod($start_date, $end_date);
    $grade_distribution = $this->qualityCheckModel->getGradeDistribution($start_date, $end_date);
    
    $pdf = new PDF('Звіт по якості сировини');
    $pdf->addTitle('Звіт по якості сировини за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
    
    // Добавляем общую статистику
    $pdf->addText('Загальна статистика:');
    $pdf->addText('Всього перевірок: ' . ($stats['total_checks'] ?: 0));
    $pdf->addText('Схвалено: ' . ($stats['approved'] ?: 0));
    $pdf->addText('Відхилено: ' . ($stats['rejected'] ?: 0));
    $pdf->addText('Відсоток схвалення: ' . ($stats['approval_rate'] ?: 0) . '%');
    $pdf->addText('');
    
    // Статистика по материалам
    if (!empty($material_stats)) {
        $header = ['Матеріал', 'Перевірок', 'Схвалено', 'Відхилено', '% схвалення'];
        $data = [];
        
        foreach ($material_stats as $item) {
            $data[] = [
                $item['material_name'],
                $item['total_checks'],
                $item['approved'],
                $item['rejected'],
                $item['approval_rate'] . '%'
            ];
        }
        
        $pdf->addText('Статистика по матеріалах:');
        $pdf->addTable($header, $data);
    }
    
    // Распределение оценок
    if (!empty($grade_distribution)) {
        $pdf->addText('Розподіл оцінок:');
        foreach ($grade_distribution as $grade) {
            $grade_name = Util::getOverallGradeName($grade['overall_grade']);
            $pdf->addText('• ' . $grade_name . ': ' . $grade['count'] . ' перевірок');
        }
    }
    
    $pdf->addDateAndSignature();
    $pdf->output('quality_report_' . date('Y-m-d') . '.pdf');
}

// Получение данных для отчетов (JSON для AJAX)
public function getReportsData() {
    $type = isset($_GET['type']) ? $_GET['type'] : 'stats';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
    
    $data = [];
    
    switch ($type) {
        case 'stats':
            $data = $this->qualityCheckModel->getStatsByPeriod($start_date, $end_date);
            break;
        case 'materials':
            $data = $this->qualityCheckModel->getMaterialStatsByPeriod($start_date, $end_date);
            break;
        case 'grades':
            $data = $this->qualityCheckModel->getGradeDistribution($start_date, $end_date);
            break;
        case 'daily':
            $sql = "SELECT DATE(check_date) as date,
                           COUNT(*) as total_checks,
                           SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                           SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM quality_checks 
                    WHERE DATE(check_date) BETWEEN ? AND ?
                    GROUP BY DATE(check_date)
                    ORDER BY date";
            
            $db = Database::getInstance();
            $data = $db->resultSet($sql, [$start_date, $end_date]);
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}




    
    // Розрахунок загальної оцінки
    private function calculateOverallGrade($visual, $smell, $temperature, $ph) {
        $scores = [];
        
        // Візуальна оцінка (1-5)
        if ($visual >= 4) $scores[] = 5;
        elseif ($visual >= 3) $scores[] = 4;
        elseif ($visual >= 2) $scores[] = 3;
        else $scores[] = 2;
        
        // Запах (1-5)
        if ($smell >= 4) $scores[] = 5;
        elseif ($smell >= 3) $scores[] = 4;
        elseif ($smell >= 2) $scores[] = 3;
        else $scores[] = 2;
        
        // Температура (оптимальна 0-4°C)
        if (!empty($temperature)) {
            if ($temperature >= 0 && $temperature <= 4) $scores[] = 5;
            elseif ($temperature >= -2 && $temperature <= 6) $scores[] = 4;
            elseif ($temperature >= -5 && $temperature <= 8) $scores[] = 3;
            else $scores[] = 2;
        }
        
        // pH (оптимальний 5.5-6.5)
        if (!empty($ph)) {
            if ($ph >= 5.5 && $ph <= 6.5) $scores[] = 5;
            elseif ($ph >= 5.0 && $ph <= 7.0) $scores[] = 4;
            elseif ($ph >= 4.5 && $ph <= 7.5) $scores[] = 3;
            else $scores[] = 2;
        }
        
        $average = array_sum($scores) / count($scores);
        
        if ($average >= 4.5) return 'excellent';
        elseif ($average >= 3.5) return 'good';
        elseif ($average >= 2.5) return 'satisfactory';
        else return 'unsatisfactory';
    }
    
    // Додавання схваленої сировини до інвентарю
    private function addApprovedMaterialsToInventory($order_id) {
        $order_items = $this->orderModel->getItems($order_id);
        
        foreach ($order_items as $item) {
            // Додаємо кількість до інвентарю
            $this->inventoryModel->addQuantity(
                $item['raw_material_id'], 
                $item['quantity'], 
                Auth::getCurrentUserId()
            );
        }
        
        // Оновлюємо статус замовлення на 'delivered'
        $this->orderModel->updateStatus($order_id, 'delivered');
    }
    
    // Відправка повідомлень про результати перевірки
    private function sendQualityCheckNotification($check, $status) {
        $order = $this->orderModel->getById($check['order_id']);
        
        if ($status === 'approved') {
            // Повідомлення начальнику складу
            $warehouse_managers = $this->getUsersByRole('warehouse_manager');
            foreach ($warehouse_managers as $manager) {
                $this->messageModel->send(
                    Auth::getCurrentUserId(),
                    $manager['id'],
                    'Сировину схвалено - Замовлення №' . $order['id'],
                    'Технологом проведена перевірка якості сировини по замовленню №' . $order['id'] . '. ' .
                    'Результат: СХВАЛЕНО. Сировину автоматично додано до складських запасів.'
                );
            }
            
            // Повідомлення адміністратору
            $admins = $this->getUsersByRole('admin');
            foreach ($admins as $admin) {
                $this->messageModel->send(
                    Auth::getCurrentUserId(),
                    $admin['id'],
                    'Якість сировини підтверджена - Замовлення №' . $order['id'],
                    'Сировина по замовленню №' . $order['id'] . ' пройшла перевірку якості та додана до складських запасів.'
                );
            }
            
            // Повідомлення постачальнику
            $this->messageModel->send(
                Auth::getCurrentUserId(),
                $order['supplier_id'],
                'Якість сировини схвалена - Замовлення №' . $order['id'],
                'Ваша сировина по замовленню №' . $order['id'] . ' успішно пройшла перевірку якості та прийнята на склад.'
            );
            
        } else {
            // Повідомлення про відхилення
            $admins = $this->getUsersByRole('admin');
            foreach ($admins as $admin) {
                $this->messageModel->send(
                    Auth::getCurrentUserId(),
                    $admin['id'],
                    'УВАГА! Сировину відхилено - Замовлення №' . $order['id'],
                    'Технологом проведена перевірка якості сировини по замовленню №' . $order['id'] . '. ' .
                    'Результат: ВІДХИЛЕНО. Причина: ' . $check['rejection_reason'] . '. ' .
                    'Необхідно вжити заходи щодо повернення або заміни сировини.'
                );
            }
            
            // Повідомлення начальнику складу
            $warehouse_managers = $this->getUsersByRole('warehouse_manager');
            foreach ($warehouse_managers as $manager) {
                $this->messageModel->send(
                    Auth::getCurrentUserId(),
                    $manager['id'],
                    'Сировину відхилено - Замовлення №' . $order['id'],
                    'Сировина по замовленню №' . $order['id'] . ' НЕ пройшла перевірку якості. НЕ приймайте її на склад!'
                );
            }
            
            // Повідомлення постачальнику
            $this->messageModel->send(
                Auth::getCurrentUserId(),
                $order['supplier_id'],
                'Якість сировини не відповідає стандартам - Замовлення №' . $order['id'],
                'На жаль, ваша сировина по замовленню №' . $order['id'] . ' не пройшла перевірку якості. ' .
                'Причина: ' . $check['rejection_reason'] . '. Просимо замінити або повернути сировину.'
            );
        }
    }
    
    // Список перевірок якості
    public function qualityChecks() {
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
        
        $checks = $this->qualityCheckModel->getAll($status, $date_from, $date_to);
        
        $data = [
            'title' => 'Перевірки якості',
            'checks' => $checks,
            'status' => $status,
            'date_from' => $date_from,
            'date_to' => $date_to
        ];
        
        include VIEWS_PATH . '/technologist/quality_checks.php';
    }
    
    // Перегляд деталей перевірки
    public function viewQualityCheck($id) {
        $check = $this->qualityCheckModel->getById($id);
        
        if (!$check) {
            $_SESSION['error'] = 'Перевірку якості не знайдено';
            Util::redirect(BASE_URL . '/technologist/qualityChecks');
        }
        
        $order_items = $this->orderModel->getItems($check['order_id']);
        $standards = $this->qualityCheckModel->getStandardsForOrder($check['order_id']);
        $recommendations = $this->qualityCheckModel->getRecommendations($id);
        
        $data = [
            'title' => 'Перегляд перевірки якості',
            'check' => $check,
            'order_items' => $order_items,
            'standards' => $standards,
            'recommendations' => $recommendations
        ];
        
        include VIEWS_PATH . '/technologist/view_quality_check.php';
    }
    
    // Генерація сертифіката якості
    public function generateQualityCertificate($check_id) {
        $check = $this->qualityCheckModel->getById($check_id);
        
        if (!$check || $check['status'] !== 'approved') {
            $_SESSION['error'] = 'Сертифікат можна створити тільки для схваленої сировини';
            Util::redirect(BASE_URL . '/technologist/qualityChecks');
        }
        
        $order_items = $this->orderModel->getItems($check['order_id']);
        $standards = $this->qualityCheckModel->getStandardsForOrder($check['order_id']);
        
        // Створюємо PDF сертифікат
        $pdf = new PDF('Сертифікат якості сировини');
        $pdf->addTitle('СЕРТИФІКАТ ЯКОСТІ СИРОВИНИ', 'Замовлення №' . $check['order_id']);
        
        $pdf->addText('Дата перевірки: ' . date('d.m.Y', strtotime($check['check_date'])));
        $pdf->addText('Технолог: ' . $check['technologist_name']);
        $pdf->addText('Постачальник: ' . $check['supplier_name']);
        $pdf->addText('');
        
        // Результати перевірки
        $pdf->addText('РЕЗУЛЬТАТИ ПЕРЕВІРКИ:');
        if ($check['temperature']) {
            $pdf->addText('Температура: ' . $check['temperature'] . '°C');
        }
        if ($check['ph_level']) {
            $pdf->addText('Рівень pH: ' . $check['ph_level']);
        }
        if ($check['visual_assessment']) {
            $pdf->addText('Візуальна оцінка: ' . $check['visual_assessment'] . '/5');
        }
        if ($check['smell_assessment']) {
            $pdf->addText('Оцінка запаху: ' . $check['smell_assessment'] . '/5');
        }
        if ($check['overall_grade']) {
            $grades = [
                'excellent' => 'Відмінно',
                'good' => 'Добре', 
                'satisfactory' => 'Задовільно',
                'unsatisfactory' => 'Незадовільно'
            ];
            $pdf->addText('Загальна оцінка: ' . $grades[$check['overall_grade']]);
        }
        
        $pdf->addText('');
        $pdf->addText('ВИСНОВОК: Сировина відповідає стандартам якості та схвалена для використання у виробництві.');
        
        if ($check['notes']) {
            $pdf->addText('');
            $pdf->addText('Додаткові примітки: ' . $check['notes']);
        }
        
        $pdf->addDateAndSignature();
        $pdf->output('quality_certificate_' . $check['order_id'] . '.pdf');
    }
    
    // Допоміжний метод для отримання користувачів за роллю
    private function getUsersByRole($role) {
        $userModel = new User();
        return $userModel->getByRole($role);
    }
    
    // Звіти по якості
    public function qualityReport() {
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        $stats = $this->qualityCheckModel->getStatsByPeriod($start_date, $end_date);
        $material_stats = $this->qualityCheckModel->getMaterialStatsByPeriod($start_date, $end_date);
        $grade_distribution = $this->qualityCheckModel->getGradeDistribution($start_date, $end_date);
        
        $data = [
            'title' => 'Звіт по якості сировини',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'stats' => $stats,
            'material_stats' => $material_stats,
            'grade_distribution' => $grade_distribution
        ];
        
        include VIEWS_PATH . '/technologist/quality_report.php';
    }
}