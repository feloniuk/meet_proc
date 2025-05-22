<?php
class AuthController extends BaseController {
    
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
                    $this->setFlash('success', 'Ви успішно увійшли в систему');
                    
                    // Логируем вход
                    $this->logAction('User login', ['username' => $username]);
                    
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
        
        $this->renderAuth('auth/login', $data);
    }
    
    public function register() {
        // Если пользователь уже авторизован, перенаправляем на главную
        if (Auth::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/home');
            exit;
        }
        
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Получаем данные из POST
            $postData = $this->getPostData([
                'username', 'password', 'confirm_password', 
                'role', 'name', 'email', 'phone'
            ]);
            
            // Правила валидации
            $rules = [
                'username' => ['required' => true, 'min_length' => 3],
                'password' => ['required' => true, 'min_length' => 6],
                'role' => ['required' => true, 'in' => ['warehouse_manager', 'supplier', 'technologist']],
                'name' => ['required' => true],
                'email' => ['required' => true, 'email' => true]
            ];
            
            // Валидация
            $errors = $this->validate($postData, $rules);
            
            // Проверка совпадения паролей
            if ($postData['password'] !== $postData['confirm_password']) {
                $errors['confirm_password'] = 'Паролі не співпадають';
            }
            
            // Проверка уникальности
            if (empty($errors)) {
                $userModel = $this->loadModel('User');
                if ($userModel->isUserExist($postData['username'], $postData['email'])) {
                    $errors['username'] = 'Користувач з таким логіном або email вже існує';
                }
            }
            
            // Если нет ошибок, создаем пользователя
            if (empty($errors)) {
                $userModel = $this->loadModel('User');
                if ($userModel->add(
                    $postData['username'], 
                    $_POST['password'], // Пароль не санитизируем
                    $postData['role'], 
                    $postData['name'], 
                    $postData['email'], 
                    $postData['phone']
                )) {
                    $this->setFlash('success', 'Реєстрація пройшла успішно. Тепер ви можете увійти в систему');
                    $this->logAction('User registration', ['username' => $postData['username']]);
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
        
        $this->renderAuth('auth/register', $data);
    }
    
    public function logout() {
        $username = Auth::getCurrentUserName();
        
        Auth::logout();
        
        $this->setFlash('success', 'Ви успішно вийшли з системи');
        $this->logAction('User logout', ['username' => $username]);
        
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }
}