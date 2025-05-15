<?php
class AuthController {
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    // Сторінка входу
    public function login() {
        // Якщо користувач вже авторизований, перенаправляємо на головну
        if (Auth::isLoggedIn()) {
            Util::redirect(BASE_URL . '/home');
        }
        
        $errors = [];
        
        // Обробка форми входу
        if (Util::isPost()) {
            $username = Util::sanitize($_POST['username']);
            $password = $_POST['password'];
            
            // Валідація
            if (empty($username)) {
                $errors['username'] = 'Ім\'я користувача не може бути порожнім';
            }
            
            if (empty($password)) {
                $errors['password'] = 'Пароль не може бути порожнім';
            }
            
            // Якщо помилок немає, виконуємо вхід
            if (empty($errors)) {
                if ($this->auth->login($username, $password)) {
                    // Перенаправляємо на головну сторінку
                    Util::redirect(BASE_URL . '/home');
                } else {
                    $errors['auth'] = 'Неправильне ім\'я користувача або пароль';
                }
            }
        }
        
        $data = [
            'title' => 'Вхід до системи',
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/auth/login.php';
    }
    
    // Вихід з системи
    public function logout() {
        $this->auth->logout();
        Util::redirect(BASE_URL . '/auth/login');
    }
    
    // Сторінка реєстрації (тільки для постачальників)
    public function register() {
        // Якщо користувач вже авторизований, перенаправляємо на головну
        if (Auth::isLoggedIn()) {
            Util::redirect(BASE_URL . '/home');
        }
        
        $errors = [];
        
        // Обробка форми реєстрації
        if (Util::isPost()) {
            $username = Util::sanitize($_POST['username']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $name = Util::sanitize($_POST['name']);
            $email = Util::sanitize($_POST['email']);
            $phone = Util::sanitize($_POST['phone']);
            
            // Валідація
            if (empty($username)) {
                $errors['username'] = 'Ім\'я користувача не може бути порожнім';
            } elseif (strlen($username) < 3) {
                $errors['username'] = 'Ім\'я користувача повинно містити не менше 3 символів';
            }
            
            if (empty($password)) {
                $errors['password'] = 'Пароль не може бути порожнім';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Пароль повинен містити не менше 6 символів';
            }
            
            if ($password !== $confirm_password) {
                $errors['confirm_password'] = 'Паролі не співпадають';
            }
            
            if (empty($name)) {
                $errors['name'] = 'Назва організації не може бути порожньою';
            }
            
            if (empty($email)) {
                $errors['email'] = 'Email не може бути порожнім';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Некоректний формат email';
            }
            
            // Перевірка, чи існує користувач з таким ім'ям або email
            $userModel = new User();
            if ($userModel->isUserExist($username, $email)) {
                $errors['username'] = 'Користувач з таким ім\'ям або email вже існує';
            }
            
            // Якщо помилок немає, реєструємо користувача
            if (empty($errors)) {
                if ($this->auth->registerSupplier($username, $password, $name, $email, $phone)) {
                    $_SESSION['success'] = 'Реєстрація успішна. Тепер ви можете увійти до системи.';
                    Util::redirect(BASE_URL . '/auth/login');
                } else {
                    $_SESSION['error'] = 'Помилка при реєстрації. Спробуйте ще раз.';
                }
            }
        }
        
        $data = [
            'title' => 'Реєстрація постачальника',
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/auth/register.php';
    }
}