<?php
// controllers/BaseController.php

abstract class BaseController {
    
    /**
     * Универсальный метод для рендеринга представлений
     * 
     * @param string $view Путь к файлу представления относительно VIEWS_PATH
     * @param array $data Данные для передачи в представление
     * @param string $layout Шаблон для использования (по умолчанию 'main')
     */
    protected function render($view, $data = [], $layout = 'main') {
        // Извлекаем переменные для использования в представлении
        extract($data);
        
        // Начинаем буферизацию вывода
        ob_start();
        
        // Подключаем файл представления
        $viewFile = VIEWS_PATH . '/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("View file not found: " . $viewFile);
        }
        
        // Получаем содержимое буфера
        $content = ob_get_clean();
        
        // Если используется layout
        if ($layout) {
            // Извлекаем переменные для layout
            extract($data);
            
            // Подключаем layout
            $layoutFile = VIEWS_PATH . '/layouts/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                include $layoutFile;
            } else {
                // Если layout не найден, выводим только контент
                echo $content;
            }
        } else {
            // Без layout выводим только контент
            echo $content;
        }
    }
    
    /**
     * Метод для рендеринга JSON ответа
     * 
     * @param mixed $data Данные для вывода в JSON
     * @param int $statusCode HTTP код ответа
     */
    protected function renderJson($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Метод для рендеринга страниц авторизации
     * 
     * @param string $view Путь к файлу представления
     * @param array $data Данные для передачи
     */
    protected function renderAuth($view, $data = []) {
        // Используем специальный layout для авторизации
        $this->render($view, $data, 'auth_layout');
    }
    
    /**
     * Проверка прав доступа
     * 
     * @param string|array $roles Роль или массив ролей
     * @param string $redirectTo URL для перенаправления при отсутствии прав
     */
    protected function requireRole($roles, $redirectTo = null) {
        if (!Auth::isLoggedIn()) {
            Util::redirect($redirectTo ?: BASE_URL . '/auth/login');
        }
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        $hasRole = false;
        foreach ($roles as $role) {
            if (Auth::hasRole($role)) {
                $hasRole = true;
                break;
            }
        }
        
        if (!$hasRole) {
            $_SESSION['error'] = 'У вас немає прав доступу до цієї сторінки';
            Util::redirect($redirectTo ?: BASE_URL . '/home');
        }
    }
    
    /**
     * Загрузка модели с кешированием
     * 
     * @param string $modelName Имя модели
     * @return object Экземпляр модели
     */
    protected function loadModel($modelName) {
        static $models = [];
        
        if (!isset($models[$modelName])) {
            $modelClass = ucfirst($modelName);
            if (class_exists($modelClass)) {
                $models[$modelName] = new $modelClass();
            } else {
                throw new Exception("Model not found: " . $modelClass);
            }
        }
        
        return $models[$modelName];
    }
    
    /**
     * Логирование действий пользователя
     * 
     * @param string $action Описание действия
     * @param array $data Дополнительные данные
     */
    protected function logAction($action, $data = []) {
        $logData = [
            'user_id' => Auth::getCurrentUserId(),
            'user_name' => Auth::getCurrentUserName(),
            'action' => $action,
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        error_log(json_encode($logData, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Валидация данных
     * 
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @return array Массив ошибок (пустой, если ошибок нет)
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule => $ruleValue) {
                switch ($rule) {
                    case 'required':
                        if ($ruleValue && empty($value)) {
                            $errors[$field] = "Поле обов'язкове для заповнення";
                        }
                        break;
                        
                    case 'min_length':
                        if (strlen($value) < $ruleValue) {
                            $errors[$field] = "Мінімальна довжина: {$ruleValue} символів";
                        }
                        break;
                        
                    case 'max_length':
                        if (strlen($value) > $ruleValue) {
                            $errors[$field] = "Максимальна довжина: {$ruleValue} символів";
                        }
                        break;
                        
                    case 'email':
                        if ($ruleValue && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Некоректний формат email";
                        }
                        break;
                        
                    case 'numeric':
                        if ($ruleValue && !is_numeric($value)) {
                            $errors[$field] = "Значення повинно бути числом";
                        }
                        break;
                        
                    case 'min':
                        if (is_numeric($value) && $value < $ruleValue) {
                            $errors[$field] = "Мінімальне значення: {$ruleValue}";
                        }
                        break;
                        
                    case 'max':
                        if (is_numeric($value) && $value > $ruleValue) {
                            $errors[$field] = "Максимальне значення: {$ruleValue}";
                        }
                        break;
                        
                    case 'in':
                        if (!in_array($value, $ruleValue)) {
                            $errors[$field] = "Недопустиме значення";
                        }
                        break;
                }
                
                // Если есть ошибка, прекращаем проверку этого поля
                if (isset($errors[$field])) {
                    break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Получение данных из POST с санитизацией
     * 
     * @param array $fields Список полей для получения
     * @return array Санитизированные данные
     */
    protected function getPostData($fields) {
        $data = [];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = Util::sanitize($_POST[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Установка флеш-сообщения
     * 
     * @param string $type Тип сообщения (success, error, warning, info)
     * @param string $message Текст сообщения
     */
    protected function setFlash($type, $message) {
        $_SESSION[$type] = $message;
    }
    
    /**
     * Проверка CSRF токена
     * 
     * @return bool
     */
    protected function validateCsrf() {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return $_POST['csrf_token'] === $_SESSION['csrf_token'];
    }
    
    /**
     * Генерация CSRF токена
     * 
     * @return string
     */
    protected function generateCsrf() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
}