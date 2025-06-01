<?php
class HomeController extends BaseController {
    private $userModel;
    private $messageModel;
    private $inventoryModel;
    private $rawMaterialModel;
    private $orderModel;
    private $productionModel;
    
    public function __construct() {
        // Проверка на авторизацию
        if (!Auth::isLoggedIn()) {
            Util::redirect(BASE_URL . '/auth/login');
        }
        
        // Загрузка моделей
        $this->userModel = $this->loadModel('User');
        $this->messageModel = $this->loadModel('Message');
        $this->inventoryModel = $this->loadModel('Inventory');
        $this->rawMaterialModel = $this->loadModel('RawMaterial');
        $this->orderModel = $this->loadModel('Order');
        $this->productionModel = $this->loadModel('Production');
    }
    
    // Главная страница
    public function index() {
        $user_id = Auth::getCurrentUserId();
        $user_role = Auth::getCurrentUserRole();
        
        // Базовые данные для всех ролей
        $data = [
            'title' => 'Головна панель',
            'unread_messages' => $this->messageModel->countUnread($user_id),
            'messages' => $this->messageModel->getLatest($user_id, 5)
        ];
        
        // Данные в зависимости от роли
        switch ($user_role) {
            case 'admin':
                $data['low_stock'] = $this->inventoryModel->getCriticalLowStock();
                $data['active_orders'] = $this->orderModel->getActive();
                $data['active_production'] = $this->productionModel->getActive();
                
                // Статистика производства за последний месяц
                $end_date = date('Y-m-d');
                $start_date = date('Y-m-d', strtotime('-30 days'));
                $data['production_stats'] = $this->productionModel->getStatsByPeriod($start_date, $end_date);
                $data['materials_stats'] = $this->rawMaterialModel->getUsageStats($start_date, $end_date);
                
                $this->render('admin/dashboard', $data);
                break;
                
            case 'warehouse_manager':
                $data['low_stock'] = $this->inventoryModel->getCriticalLowStock();
                $data['active_production'] = $this->productionModel->getActive();
                $data['inventory'] = $this->inventoryModel->getAll();
                
                $this->render('warehouse/dashboard', $data);
                break;
                
            case 'supplier':
                $data['active_orders'] = $this->orderModel->getBySupplier($user_id);
                $data['materials'] = $this->rawMaterialModel->getBySupplier($user_id);
                
                $this->render('supplier/dashboard', $data);
                break;
                
            case 'technologist':
                // Загружаем модель QualityCheck
                $qualityCheckModel = $this->loadModel('QualityCheck');
                
                // Автоматически создаем проверки для новых заказов
                $this->createAutoQualityChecks();
                
                $data['pending_checks'] = $qualityCheckModel->getPendingChecks();
                $data['completed_checks'] = $qualityCheckModel->getRecentChecks($user_id, 5);
                
                $this->render('technologist/dashboard', $data);
                break;
                
            default:
                $this->render('home/index', $data);
        }
    }
    
    // Автоматическое создание проверок качества для новых заказов
    private function createAutoQualityChecks() {
        // Находим заказы со статусом 'shipped' без проверки качества
        $sql = "SELECT DISTINCT o.id 
                FROM orders o 
                WHERE o.status = 'shipped' 
                AND o.quality_status = 'not_checked'
                AND NOT EXISTS (
                    SELECT 1 FROM quality_checks qc WHERE qc.order_id = o.id
                )";
        
        $db = Database::getInstance();
        $orders = $db->resultSet($sql);
        
        $qualityCheckModel = $this->loadModel('QualityCheck');
        
        foreach ($orders as $order) {
            $qualityCheckModel->create(
                $order['id'], 
                Auth::getCurrentUserId(), 
                'Автоматично створена перевірка при доставці'
            );
            
            // Обновляем статус заказа
            $this->orderModel->updateQualityStatus($order['id'], 'pending');
        }
    }
    
    // Страница профиля
    public function profile() {
        $user_id = Auth::getCurrentUserId();
        $user = $this->userModel->getById($user_id);
        
        $errors = [];
        
        // Обработка формы обновления профиля
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $email = Util::sanitize($_POST['email']);
            $phone = Util::sanitize($_POST['phone']);
            $old_password = isset($_POST['old_password']) ? $_POST['old_password'] : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            // Валидация
            if (empty($name)) {
                $errors['name'] = 'Ім\'я не може бути порожнім';
            }
            
            if (empty($email)) {
                $errors['email'] = 'Email не може бути порожнім';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Некоректний формат email';
            }
            
            // Если указан старый пароль, проверяем и меняем пароль
            if (!empty($old_password)) {
                if (empty($new_password)) {
                    $errors['new_password'] = 'Новий пароль не може бути порожнім';
                } elseif (strlen($new_password) < 6) {
                    $errors['new_password'] = 'Новий пароль повинен містити не менше 6 символів';
                } elseif ($new_password !== $confirm_password) {
                    $errors['confirm_password'] = 'Паролі не співпадають';
                } else {
                    $auth = new Auth();
                    if (!$auth->changePassword($user_id, $old_password, $new_password)) {
                        $errors['old_password'] = 'Неправильний старий пароль';
                    }
                }
            }
            
            // Если ошибок нет, обновляем профиль
            if (empty($errors)) {
                if ($this->userModel->update($user_id, $name, $email, $phone)) {
                    $this->setFlash('success', 'Профіль успішно оновлено');
                    Util::redirect(BASE_URL . '/home/profile');
                } else {
                    $this->setFlash('error', 'Помилка при оновленні профілю');
                }
            }
        }
        
        $data = [
            'title' => 'Мій профіль',
            'user' => $user,
            'errors' => $errors
        ];
        
        $this->render('home/profile', $data);
    }
    
    // Страница сообщений
    public function messages() {
        $user_id = Auth::getCurrentUserId();
        
        $data = [
            'title' => 'Повідомлення',
            'inbox' => $this->messageModel->getInbox($user_id),
            'sent' => $this->messageModel->getSent($user_id)
        ];
        
        $this->render('home/messages', $data);
    }
    
    // Просмотр сообщения
    public function viewMessage($id) {
        $user_id = Auth::getCurrentUserId();
        $message = $this->messageModel->getById($id);
        
        // Проверка доступа
        if (!$message || ($message['sender_id'] !== $user_id && $message['receiver_id'] !== $user_id)) {
            $this->setFlash('error', 'Повідомлення не знайдено');
            Util::redirect(BASE_URL . '/home/messages');
        }
        
        // Отмечаем как прочитанное, если это входящее сообщение
        if ($message['receiver_id'] === $user_id && !$message['is_read']) {
            $this->messageModel->markAsRead($id);
        }
        
        $data = [
            'title' => 'Перегляд повідомлення',
            'message' => $message
        ];
        
        $this->render('home/view_message', $data);
    }
    
    // Создание нового сообщения
    public function newMessage() {
        $user_id = Auth::getCurrentUserId();
        $errors = [];
        
        // Получаем список получателей в зависимости от роли
        $receivers = [];
        $user_role = Auth::getCurrentUserRole();
        
        if ($user_role === 'admin') {
            // Администратор может писать всем
            $receivers = $this->userModel->getAll();
        } elseif ($user_role === 'warehouse_manager') {
            // Начальник склада может писать администраторам и поставщикам
            $admins = $this->userModel->getByRole('admin');
            $suppliers = $this->userModel->getByRole('supplier');
            $receivers = array_merge($admins, $suppliers);
        } elseif ($user_role === 'supplier') {
            // Поставщик может писать только администраторам
            $receivers = $this->userModel->getByRole('admin');
        } elseif ($user_role === 'technologist') {
            // Технолог может писать администраторам и складу
            $admins = $this->userModel->getByRole('admin');
            $warehouse = $this->userModel->getByRole('warehouse_manager');
            $receivers = array_merge($admins, $warehouse);
        }
        
        // Обработка формы создания сообщения
        if (Util::isPost()) {
            $receiver_id = Util::sanitize($_POST['receiver_id']);
            $subject = Util::sanitize($_POST['subject']);
            $message = Util::sanitize($_POST['message']);
            
            // Валидация
            if (empty($receiver_id)) {
                $errors['receiver_id'] = 'Виберіть одержувача';
            }
            
            if (empty($subject)) {
                $errors['subject'] = 'Тема не може бути порожньою';
            }
            
            if (empty($message)) {
                $errors['message'] = 'Повідомлення не може бути порожнім';
            }
            
            // Если ошибок нет, отправляем сообщение
            if (empty($errors)) {
                if ($this->messageModel->send($user_id, $receiver_id, $subject, $message)) {
                    $this->setFlash('success', 'Повідомлення успішно відправлено');
                    Util::redirect(BASE_URL . '/home/messages');
                } else {
                    $this->setFlash('error', 'Помилка при відправці повідомлення');
                }
            }
        }
        
        $data = [
            'title' => 'Нове повідомлення',
            'receivers' => $receivers,
            'errors' => $errors
        ];
        
        $this->render('home/new_message', $data);
    }
    
    // Удаление сообщения
    public function deleteMessage($id) {
        $user_id = Auth::getCurrentUserId();
        $message = $this->messageModel->getById($id);
        
        // Проверка доступа
        if (!$message || ($message['sender_id'] !== $user_id && $message['receiver_id'] !== $user_id)) {
            $this->setFlash('error', 'Повідомлення не знайдено');
            Util::redirect(BASE_URL . '/home/messages');
        }
        
        if ($this->messageModel->delete($id)) {
            $this->setFlash('success', 'Повідомлення успішно видалено');
        } else {
            $this->setFlash('error', 'Помилка при видаленні повідомлення');
        }
        
        Util::redirect(BASE_URL . '/home/messages');
    }
}