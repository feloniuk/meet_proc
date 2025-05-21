<?php
class TechnologistController {
    private $qualityCheckModel;
    private $orderModel;
    private $rawMaterialModel;
    private $messageModel;
    
    public function __construct() {
        // Перевірка на авторизацію та роль
        if (!Auth::isLoggedIn() || !Auth::hasRole('technologist')) {
            Util::redirect(BASE_URL . '/home');
        }
        
        $this->qualityCheckModel = new QualityCheck();
        $this->orderModel = new Order();
        $this->rawMaterialModel = new RawMaterial();
        $this->messageModel = new Message();
    }
    
    // Головна панель технолога
    public function index() {
        // Отримуємо замовлення, що потребують перевірки
        $pending_checks = $this->qualityCheckModel->getPendingChecks();
        $completed_checks = $this->qualityCheckModel->getRecentChecks(Auth::getCurrentUserId(), 5);
        
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
    
    // Створення нової перевірки якості
    public function createQualityCheck() {
        // Отримуємо замовлення, які потребують перевірки
        $orders = $this->orderModel->getOrdersForQualityCheck();
        
        $errors = [];
        
        if (Util::isPost()) {
            $order_id = Util::sanitize($_POST['order_id']);
            $notes = Util::sanitize($_POST['notes']);
            
            // Валідація
            if (empty($order_id)) {
                $errors['order_id'] = 'Виберіть замовлення';
            }
            
            // Перевіряємо, чи не існує вже перевірка для цього замовлення
            if ($this->qualityCheckModel->getByOrderId($order_id)) {
                $errors['order_id'] = 'Для цього замовлення вже створена перевірка якості';
            }
            
            if (empty($errors)) {
                $check_id = $this->qualityCheckModel->create($order_id, Auth::getCurrentUserId(), $notes);
                
                if ($check_id) {
                    $_SESSION['success'] = 'Перевірка якості створена успішно';
                    Util::redirect(BASE_URL . '/technologist/editQualityCheck/' . $check_id);
                } else {
                    $_SESSION['error'] = 'Помилка при створенні перевірки якості';
                }
            }
        }
        
        $data = [
            'title' => 'Створення перевірки якості',
            'orders' => $orders,
            'errors' => $errors
        ];
        
        include VIEWS_PATH . '/technologist/create_quality_check.php';
    }
    
    // Редагування перевірки якості
    public function editQualityCheck($id) {
        $check = $this->qualityCheckModel->getById($id);
        
        if (!$check) {
            $_SESSION['error'] = 'Перевірку якості не знайдено';
            Util::redirect(BASE_URL . '/technologist/qualityChecks');
        }
        
        // Отримуємо позиції замовлення та стандарти якості
        $order_items = $this->orderModel->getItems($check['order_id']);
        $check_items = $this->qualityCheckModel->getCheckItems($id);
        $standards = $this->qualityCheckModel->getStandardsForOrder($check['order_id']);
        
        $errors = [];
        
        if (Util::isPost()) {
            $temperature = Util::sanitize($_POST['temperature']);
            $ph_level = Util::sanitize($_POST['ph_level']);
            $moisture_content = Util::sanitize($_POST['moisture_content']);
            $visual_assessment = Util::sanitize($_POST['visual_assessment']);
            $smell_assessment = Util::sanitize($_POST['smell_assessment']);
            $texture_assessment = Util::sanitize($_POST['texture_assessment']);
            $overall_grade = Util::sanitize($_POST['overall_grade']);
            $status = Util::sanitize($_POST['status']);
            $notes = Util::sanitize($_POST['notes']);
            $rejection_reason = Util::sanitize($_POST['rejection_reason']);
            
            // Валідація
            if (empty($status) || !in_array($status, ['pending', 'approved', 'rejected'])) {
                $errors['status'] = 'Виберіть статус перевірки';
            }
            
            if ($status === 'rejected' && empty($rejection_reason)) {
                $errors['rejection_reason'] = 'Вкажіть причину відхилення';
            }
            
            if (!empty($temperature) && (!is_numeric($temperature) || $temperature < -50 || $temperature > 50)) {
                $errors['temperature'] = 'Некоректне значення температури';
            }
            
            if (!empty($ph_level) && (!is_numeric($ph_level) || $ph_level < 0 || $ph_level > 14)) {
                $errors['ph_level'] = 'Некоректне значення pH (0-14)';
            }
            
            if (!empty($moisture_content) && (!is_numeric($moisture_content) || $moisture_content < 0 || $moisture_content > 100)) {
                $errors['moisture_content'] = 'Некоректне значення вологості (0-100%)';
            }
            
            if (empty($errors)) {
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
                    // Оновлюємо статус якості в замовленні
                    $this->orderModel->updateQualityStatus($check['order_id'], $status);
                    
                    // Відправляємо повідомлення відповідальним особам
                    $this->sendQualityCheckNotification($check, $status);
                    
                    $_SESSION['success'] = 'Перевірку якості оновлено успішно';
                    Util::redirect(BASE_URL . '/technologist/viewQualityCheck/' . $id);
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні перевірки якості';
                }
            }
        }
        
        $data = [
            'title' => 'Редагування перевірки якості',
            'check' => $check,
            'order_items' => $order_items,
            'check_items' => $check_items,
            'standards' => $standards,
            'errors' => $errors
        ];
        
        include VIEWS_PATH . '/technologist/edit_quality_check.php';
    }
    
    // Перегляд перевірки якості
    public function viewQualityCheck($id) {
        $check = $this->qualityCheckModel->getById($id);
        
        if (!$check) {
            $_SESSION['error'] = 'Перевірку якості не знайдено';
            Util::redirect(BASE_URL . '/technologist/qualityChecks');
        }
        
        $order_items = $this->orderModel->getItems($check['order_id']);
        $check_items = $this->qualityCheckModel->getCheckItems($id);
        $standards = $this->qualityCheckModel->getStandardsForOrder($check['order_id']);
        
        $data = [
            'title' => 'Перегляд перевірки якості',
            'check' => $check,
            'order_items' => $order_items,
            'check_items' => $check_items,
            'standards' => $standards
        ];
        
        include VIEWS_PATH . '/technologist/view_quality_check.php';
    }
    
    // Швидке схвалення/відхилення
    public function quickAction($id, $action) {
        $check = $this->qualityCheckModel->getById($id);
        
        if (!$check) {
            $_SESSION['error'] = 'Перевірку якості не знайдено';
            Util::redirect(BASE_URL . '/technologist/qualityChecks');
        }
        
        if (!in_array($action, ['approve', 'reject'])) {
            $_SESSION['error'] = 'Некоректна дія';
            Util::redirect(BASE_URL . '/technologist/qualityChecks');
        }
        
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $updateData = ['status' => $status];
        
        if ($action === 'reject') {
            $updateData['rejection_reason'] = 'Швидке відхилення через невідповідність стандартам якості';
        }
        
        if ($this->qualityCheckModel->update($id, $updateData)) {
            $this->orderModel->updateQualityStatus($check['order_id'], $status);
            $this->sendQualityCheckNotification($check, $status);
            
            $_SESSION['success'] = $action === 'approve' ? 'Сировину схвалено' : 'Сировину відхилено';
        } else {
            $_SESSION['error'] = 'Помилка при оновленні статусу';
        }
        
        Util::redirect(BASE_URL . '/technologist/qualityChecks');
    }
    
    // Стандарти якості
    public function qualityStandards() {
        $standards = $this->qualityCheckModel->getAllStandards();
        $materials = $this->rawMaterialModel->getAll();
        
        $data = [
            'title' => 'Стандарти якості',
            'standards' => $standards,
            'materials' => $materials
        ];
        
        include VIEWS_PATH . '/technologist/quality_standards.php';
    }
    
    // Додавання стандарту якості
    public function addQualityStandard() {
        $errors = [];
        
        if (Util::isPost()) {
            $raw_material_id = Util::sanitize($_POST['raw_material_id']);
            $parameter_name = Util::sanitize($_POST['parameter_name']);
            $min_value = Util::sanitize($_POST['min_value']);
            $max_value = Util::sanitize($_POST['max_value']);
            $unit = Util::sanitize($_POST['unit']);
            $description = Util::sanitize($_POST['description']);
            $is_critical = isset($_POST['is_critical']) ? 1 : 0;
            
            // Валідація
            if (empty($raw_material_id)) {
                $errors['raw_material_id'] = 'Виберіть сировину';
            }
            
            if (empty($parameter_name)) {
                $errors['parameter_name'] = 'Введіть назву параметра';
            }
            
            if (!empty($min_value) && !is_numeric($min_value)) {
                $errors['min_value'] = 'Мінімальне значення повинно бути числом';
            }
            
            if (!empty($max_value) && !is_numeric($max_value)) {
                $errors['max_value'] = 'Максимальне значення повинно бути числом';
            }
            
            if (!empty($min_value) && !empty($max_value) && $min_value >= $max_value) {
                $errors['max_value'] = 'Максимальне значення повинно бути більше мінімального';
            }
            
            if (empty($errors)) {
                if ($this->qualityCheckModel->addStandard($raw_material_id, $parameter_name, $min_value, $max_value, $unit, $description, $is_critical)) {
                    $_SESSION['success'] = 'Стандарт якості додано успішно';
                    Util::redirect(BASE_URL . '/technologist/qualityStandards');
                } else {
                    $_SESSION['error'] = 'Помилка при додаванні стандарту';
                }
            }
        }
        
        $data = [
            'title' => 'Додавання стандарту якості',
            'materials' => $this->rawMaterialModel->getAll(),
            'errors' => $errors
        ];
        
        include VIEWS_PATH . '/technologist/add_quality_standard.php';
    }
    
    // Звіти
    public function reports() {
        $data = [
            'title' => 'Звіти технолога'
        ];
        
        include VIEWS_PATH . '/technologist/reports.php';
    }
    
    // Звіт по перевіркам якості
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
    
    // Генерація PDF звіту
    public function generateQualityPdf() {
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        $stats = $this->qualityCheckModel->getStatsByPeriod($start_date, $end_date);
        $material_stats = $this->qualityCheckModel->getMaterialStatsByPeriod($start_date, $end_date);
        
        $pdf = new PDF('Звіт по якості сировини');
        $pdf->addTitle('Звіт по якості сировини за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
        
        // Загальна статистика
        $pdf->addText('Загальна статистика:');
        $pdf->addText('Всього перевірок: ' . $stats['total_checks']);
        $pdf->addText('Схвалено: ' . $stats['approved'] . ' (' . round($stats['approval_rate'], 1) . '%)');
        $pdf->addText('Відхилено: ' . $stats['rejected'] . ' (' . round($stats['rejection_rate'], 1) . '%)');
        $pdf->addText('');
        
        // Статистика по матеріалах
        if (!empty($material_stats)) {
            $header = ['Матеріал', 'Перевірок', 'Схвалено', 'Відхилено', '% схвалення'];
            $data = [];
            
            foreach ($material_stats as $material) {
                $data[] = [
                    $material['material_name'],
                    $material['total_checks'],
                    $material['approved'],
                    $material['rejected'],
                    round($material['approval_rate'], 1) . '%'
                ];
            }
            
            $pdf->addText('Статистика по матеріалах:');
            $pdf->addTable($header, $data);
        }
        
        $pdf->addDateAndSignature();
        $pdf->output('quality_report_' . date('Y-m-d') . '.pdf');
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
                    'Технологом проведена перевірка якості сировини по замовленню №' . $order['id'] . '. Результат: СХВАЛЕНО. Сировину можна приймати на склад.'
                );
            }
            
            // Повідомлення постачальнику
            $this->messageModel->send(
                Auth::getCurrentUserId(),
                $order['supplier_id'],
                'Якість сировини схвалена - Замовлення №' . $order['id'],
                'Ваша сировина по замовленню №' . $order['id'] . ' пройшла перевірку якості та схвалена для використання.'
            );
            
        } else {
            // Повідомлення про відхилення
            $admins = $this->getUsersByRole('admin');
            foreach ($admins as $admin) {
                $this->messageModel->send(
                    Auth::getCurrentUserId(),
                    $admin['id'],
                    'УВАГА! Сировину відхилено - Замовлення №' . $order['id'],
                    'Технологом проведена перевірка якості сировини по замовленню №' . $order['id'] . '. Результат: ВІДХИЛЕНО. Причина: ' . $check['rejection_reason']
                );
            }
            
            // Повідомлення постачальнику
            $this->messageModel->send(
                Auth::getCurrentUserId(),
                $order['supplier_id'],
                'Якість сировини не відповідає стандартам - Замовлення №' . $order['id'],
                'На жаль, ваша сировина по замовленню №' . $order['id'] . ' не пройшла перевірку якості. Причина: ' . $check['rejection_reason']
            );
        }
    }
    
    // Допоміжний метод для отримання користувачів за роллю
    private function getUsersByRole($role) {
        $userModel = new User();
        return $userModel->getByRole($role);
    }
}