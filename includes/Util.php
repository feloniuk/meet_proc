<?php
class Util {
    // Проверка метода запроса POST
    public static function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    // Очистка входных данных
    public static function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    
    // Перенаправление
    public static function redirect($url) {
        header('Location: ' . $url);
        exit();
    }
    
    // Форматирование даты
    public static function formatDate($date, $format = 'd.m.Y H:i') {
        if (empty($date)) return '-';
        return date($format, strtotime($date));
    }
    
    // Форматирование денег
    public static function formatMoney($amount) {
        return number_format($amount, 2, ',', ' ') . ' грн';
    }
    
    // Форматирование количества
    public static function formatQuantity($quantity, $unit) {
        return number_format($quantity, 2, ',', ' ') . ' ' . $unit;
    }
    
    // Получение названия роли пользователя
    public static function getUserRoleName($role) {
        $roles = [
            'admin' => 'Адміністратор',
            'warehouse_manager' => 'Начальник складу',
            'supplier' => 'Постачальник'
        ];
        
        return isset($roles[$role]) ? $roles[$role] : 'Невідома роль';
    }
    
    // Получение названия статуса заказа
    public static function getOrderStatusName($status) {
        $statuses = [
            'pending' => 'Очікує підтвердження',
            'accepted' => 'Прийнято',
            'shipped' => 'Відправлено',
            'delivered' => 'Доставлено',
            'canceled' => 'Скасовано'
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    // Получение названия статуса производства
    public static function getProductionStatusName($status) {
        $statuses = [
            'planned' => 'Заплановано',
            'in_progress' => 'В процесі',
            'completed' => 'Завершено',
            'canceled' => 'Скасовано'
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    // Получение CSS класса для ошибок
    public static function getErrorClass($errors, $field) {
        return isset($errors[$field]) ? 'is-invalid' : '';
    }
    
    // Получение сообщения об ошибке
    public static function getErrorMessage($errors, $field) {
        if (isset($errors[$field])) {
            return '<div class="invalid-feedback">' . $errors[$field] . '</div>';
        }
        return '';
    }
    
    // Генерация случайной строки
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    // Проверка валидности email
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // Проверка валидности телефона
    public static function isValidPhone($phone) {
        return preg_match('/^[\+]?[0-9\s\-\(\)]{7,15}$/', $phone);
    }
    
    // Обрезка текста
    public static function truncateText($text, $length = 100, $ending = '...') {
        if (mb_strlen($text) > $length) {
            return mb_substr($text, 0, $length) . $ending;
        }
        return $text;
    }
    
    // Получение IP адреса пользователя
    public static function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    // Логирование
    public static function log($message, $type = 'info') {
        $logFile = BASE_PATH . '/logs/' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    // Проверка прав доступа к файлу
    public static function checkFilePermissions($file) {
        if (!file_exists($file)) {
            return false;
        }
        
        return is_readable($file) && is_writable($file);
    }
    
    // Создание резервной копии файла
    public static function backupFile($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $backupPath = $filePath . '.backup.' . date('Y-m-d_H-i-s');
        return copy($filePath, $backupPath);
    }
    
    // Получение размера файла в удобном формате
    public static function formatFileSize($size) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    // Валидация загружаемых файлов
    public static function validateUploadedFile($file, $allowedTypes = [], $maxSize = 0) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Помилка завантаження файлу';
            return $errors;
        }
        
        if (!empty($allowedTypes)) {
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = 'Неприпустимий тип файлу';
            }
        }
        
        if ($maxSize > 0 && $file['size'] > $maxSize) {
            $errors[] = 'Файл занадто великий';
        }
        
        return $errors;
    }
    
    // Очистка HTML
    public static function cleanHTML($html) {
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><a><img>';
        return strip_tags($html, $allowedTags);
    }
    
    // Проверка на мобильное устройство
    public static function isMobile() {
        return preg_match('/Mobile|Android|iPhone|iPad/', $_SERVER['HTTP_USER_AGENT']);
    }
}