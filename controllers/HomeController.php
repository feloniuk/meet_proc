<?php
class HomeController {
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
        
        $this->userModel = new User();
        $this->messageModel = new Message();
        $this->inventoryModel = new Inventory();
        $this->rawMaterialModel = new RawMaterial();
        $this->orderModel = new Order();
        $this->productionModel = new Production();
    }
    
    // Головна сторінка
    public function index() {
        $user_id = Auth::getCurrentUserId();
        $user_role = Auth::getCurrentUserRole();
        
        // Дані для дашборду в залежності від ролі
        $data = [
            'title' => 'Головна панель',
            'unread_messages' => $this->messageModel->countUnread($user_id),
            'messages' => $this->messageModel->getLatest($user_id, 5)
        ];
        
        // Данные для администратора
        if ($user_role === 'admin') {
            $data['low_stock'] = $this->inventoryModel->getCriticalLowStock();
            $data['active_orders'] = $this->orderModel->getActive();
            $data['active_production'] = $this->productionModel->getActive();
            
            // Статистика производства за последний месяц
            $end_date = date('Y-m-d');
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $data['production_stats'] = $this->productionModel->getStatsByPeriod($start_date, $end_date);
            $data['materials_stats'] = $this->rawMaterialModel->getUsageStats($start_date, $end_date);
            
            // Передаем переменные в область видимости представления
            extract($data);
            
            // Отображение шаблона администратора
            include VIEWS_PATH . '/admin/dashboard.php';
        }
        
        // Дані для начальника складу
        else if ($user_role === 'warehouse_manager') {
            $data['low_stock'] = $this->inventoryModel->getCriticalLowStock();
            $data['active_production'] = $this->productionModel->getActive();
            $data['inventory'] = $this->inventoryModel->getAll();
            
            // Відображення шаблону начальника складу
            include VIEWS_PATH . '/warehouse/dashboard.php';
        }
        
        // Дані для постачальника
        else if ($user_role === 'supplier') {
            $data['active_orders'] = $this->orderModel->getBySupplier($user_id);
            $data['materials'] = $this->rawMaterialModel->getBySupplier($user_id);
            
            // Відображення шаблону постачальника
            include VIEWS_PATH . '/supplier/dashboard.php';
        }
        
        // Дані для постачальника
        else if ($user_role === 'technologist') {            
            // Відображення шаблону technologist
            include VIEWS_PATH . '/technologist/dashboard.php';
        }
        
        // Інші ролі (якщо будуть)
        else {
            include VIEWS_PATH . '/home/index.php';
        }
    }
    
    // Сторінка профілю
    public function profile() {
        $user_id = Auth::getCurrentUserId();
        $user = $this->userModel->getById($user_id);
        
        $errors = [];
        
        // Обробка форми оновлення профілю
        if (Util::isPost()) {
            $name = Util::sanitize($_POST['name']);
            $email = Util::sanitize($_POST['email']);
            $phone = Util::sanitize($_POST['phone']);
            $old_password = $_POST['old_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Валідація
            if (empty($name)) {
                $errors['name'] = 'Ім\'я не може бути порожнім';
            }
            
            if (empty($email)) {
                $errors['email'] = 'Email не може бути порожнім';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Некоректний формат email';
            }
            
            // Якщо вказано старий пароль, перевіряємо та змінюємо пароль
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
            
            // Якщо помилок немає, оновлюємо профіль
            if (empty($errors)) {
                if ($this->userModel->update($user_id, $name, $email, $phone)) {
                    $_SESSION['success'] = 'Профіль успішно оновлено';
                    Util::redirect(BASE_URL . '/home/profile');
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні профілю';
                }
            }
        }
        
        $data = [
            'title' => 'Мій профіль',
            'user' => $user,
            'errors' => $errors
        ];
        
        include VIEWS_PATH . '/home/profile.php';
    }
    
    // Інші методи залишаються без змін...
    // Сторінка повідомлень
    public function messages() {
        $user_id = Auth::getCurrentUserId();
        
        $data = [
            'title' => 'Повідомлення',
            'inbox' => $this->messageModel->getInbox($user_id),
            'sent' => $this->messageModel->getSent($user_id)
        ];
        
        include VIEWS_PATH . '/home/messages.php';
    }
    
    // Перегляд повідомлення
    public function viewMessage($id) {
        $user_id = Auth::getCurrentUserId();
        $message = $this->messageModel->getById($id);
        
        // Перевірка доступу
        if (!$message || ($message['sender_id'] !== $user_id && $message['receiver_id'] !== $user_id)) {
            $_SESSION['error'] = 'Повідомлення не знайдено';
            Util::redirect(BASE_URL . '/home/messages');
        }
        
        // Позначаємо як прочитане, якщо це вхідне повідомлення
        if ($message['receiver_id'] === $user_id && !$message['is_read']) {
            $this->messageModel->markAsRead($id);
        }
        
        $data = [
            'title' => 'Перегляд повідомлення',
            'message' => $message
        ];
        
        include VIEWS_PATH . '/home/view_message.php';
    }
    
    // Створення нового повідомлення
    public function newMessage() {
        $user_id = Auth::getCurrentUserId();
        $errors = [];
        
        // Отримуємо список одержувачів в залежності від ролі
        $receivers = [];
        $user_role = Auth::getCurrentUserRole();
        
        if ($user_role === 'admin') {
            // Адміністратор може писати всім
            $receivers = $this->userModel->getAll();
        } elseif ($user_role === 'warehouse_manager') {
            // Начальник складу може писати адміністраторам та постачальникам
            $admins = $this->userModel->getByRole('admin');
            $suppliers = $this->userModel->getByRole('supplier');
            $receivers = array_merge($admins, $suppliers);
        } elseif ($user_role === 'supplier') {
            // Постачальник може писати тільки адміністраторам
            $receivers = $this->userModel->getByRole('admin');
        }
        
        // Обробка форми створення повідомлення
        if (Util::isPost()) {
            $receiver_id = Util::sanitize($_POST['receiver_id']);
            $subject = Util::sanitize($_POST['subject']);
            $message = Util::sanitize($_POST['message']);
            
            // Валідація
            if (empty($receiver_id)) {
                $errors['receiver_id'] = 'Виберіть одержувача';
            }
            
            if (empty($subject)) {
                $errors['subject'] = 'Тема не може бути порожньою';
            }
            
            if (empty($message)) {
                $errors['message'] = 'Повідомлення не може бути порожнім';
            }
            
            // Якщо помилок немає, відправляємо повідомлення
            if (empty($errors)) {
                if ($this->messageModel->send($user_id, $receiver_id, $subject, $message)) {
                    $_SESSION['success'] = 'Повідомлення успішно відправлено';
                    Util::redirect(BASE_URL . '/home/messages');
                } else {
                    $_SESSION['error'] = 'Помилка при відправці повідомлення';
                }
            }
        }
        
        $data = [
            'title' => 'Нове повідомлення',
            'receivers' => $receivers,
            'errors' => $errors
        ];
        
        include VIEWS_PATH . '/home/new_message.php';
    }
    
    // Видалення повідомлення
    public function deleteMessage($id) {
        $user_id = Auth::getCurrentUserId();
        $message = $this->messageModel->getById($id);
        
        // Перевірка доступу
        if (!$message || ($message['sender_id'] !== $user_id && $message['receiver_id'] !== $user_id)) {
            $_SESSION['error'] = 'Повідомлення не знайдено';
            Util::redirect(BASE_URL . '/home/messages');
        }
        
        if ($this->messageModel->delete($id)) {
            $_SESSION['success'] = 'Повідомлення успішно видалено';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні повідомлення';
        }
        
        Util::redirect(BASE_URL . '/home/messages');
    }
}