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