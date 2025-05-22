<?php
class AuthController {
    
    public function index() {
        // Если пользователь уже авторизован, перенаправляем на главную
        if (Auth::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/home');
            exit;
        }
        
        // Показываем страницу авторизации
        $this->login();
    }
    
    public function login() {
        // Если пользователь уже авторизован, перенаправляем на главную
        if (Auth::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/home');
            exit;
        }
        
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Валидация
            if (empty($username)) {
                $errors['username'] = 'Логін не може бути порожнім';
            }
            
            if (empty($password)) {
                $errors['password'] = 'Пароль не може бути порожнім';
            }
            
            // Если нет ошибок валидации, пытаемся авторизовать
            if (empty($errors)) {
                if (Auth::login($username, $password)) {
                    $_SESSION['success'] = 'Ви успішно увійшли в систему';
                    
                    // Перенаправляем на главную страницу
                    header('Location: ' . BASE_URL . '/home');
                    exit;
                } else {
                    $errors['login'] = 'Невірний логін або пароль';
                }
            }
        }
        
        // Показываем форму входа
        $data = [
            'title' => 'Вхід в систему',
            'errors' => $errors
        ];
        
        $this->renderAuth('login', $data);
    }
    
    public function register() {
        // Если пользователь уже авторизован, перенаправляем на главную
        if (Auth::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/home');
            exit;
        }
        
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            // Валидация
            if (empty($username)) {
                $errors['username'] = 'Логін не може бути порожнім';
            } elseif (strlen($username) < 3) {
                $errors['username'] = 'Логін повинен містити мінімум 3 символи';
            }
            
            if (empty($password)) {
                $errors['password'] = 'Пароль не може бути порожнім';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Пароль повинен містити мінімум 6 символів';
            }
            
            if ($password !== $confirm_password) {
                $errors['confirm_password'] = 'Паролі не співпадають';
            }
            
            if (empty($role) || !in_array($role, ['warehouse_manager', 'supplier', 'technologist'])) {
                $errors['role'] = 'Виберіть коректну роль';
            }
            
            if (empty($name)) {
                $errors['name'] = 'Ім\'я не може бути порожнім';
            }
            
            if (empty($email)) {
                $errors['email'] = 'Email не може бути порожнім';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Некоректний формат email';
            }
            
            // Проверка уникальности
            if (empty($errors['username']) || empty($errors['email'])) {
                $userModel = new User();
                if ($userModel->isUserExist($username, $email)) {
                    $errors['username'] = 'Користувач з таким логіном або email вже існує';
                }
            }
            
            // Если нет ошибок, создаем пользователя
            if (empty($errors)) {
                $userModel = new User();
                if ($userModel->add($username, $password, $role, $name, $email, $phone)) {
                    $_SESSION['success'] = 'Реєстрація пройшла успішно. Тепер ви можете увійти в систему';
                    header('Location: ' . BASE_URL . '/auth/login');
                    exit;
                } else {
                    $errors['register'] = 'Помилка при реєстрації. Спробуйте ще раз';
                }
            }
        }
        
        // Показываем форму регистрации
        $data = [
            'title' => 'Реєстрація',
            'errors' => $errors
        ];
        
        $this->renderAuth('register', $data);
    }
    
    public function logout() {
        Auth::logout();
        $_SESSION['success'] = 'Ви успішно вийшли з системи';
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }
    
    private function renderAuth($view, $data = []) {
        // Подключаем файл представления
        $viewFile = VIEWS_PATH . '/auth/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            // Извлекаем переменные из массива $data
            extract($data);
            
            // Начинаем буферизацию вывода
            ob_start();
            include $viewFile;
            $content = ob_get_clean();
            
            // Подключаем layout для неавторизованных пользователей
            include VIEWS_PATH . '/layouts/auth_layout.php';
        } else {
            // Если файл представления не найден
            $_SESSION['error'] = 'Сторінка не знайдена';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    }
}