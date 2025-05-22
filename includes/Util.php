<?php
// Дополнения к классу Util для поддержки технолога

class Util {
    // Существующие методы остаются без изменений...
    
    // Обновленный метод для получения названия роли пользователя
    public static function getUserRoleName($role) {
        $roles = [
            'admin' => 'Адміністратор',
            'warehouse_manager' => 'Начальник складу',
            'supplier' => 'Постачальник',
            'technologist' => 'Технолог'
        ];
        
        return isset($roles[$role]) ? $roles[$role] : 'Невідома роль';
    }
    
    // Получение названия статуса проверки качества
    public static function getQualityStatusName($status) {
        $statuses = [
            'not_checked' => 'Не перевірялось',
            'pending' => 'На перевірці',
            'approved' => 'Схвалено',
            'rejected' => 'Відхилено'
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    // Получение названия общей оценки качества
    public static function getOverallGradeName($grade) {
        $grades = [
            'excellent' => 'Відмінно',
            'good' => 'Добре',
            'satisfactory' => 'Задовільно',
            'unsatisfactory' => 'Незадовільно'
        ];
        
        return isset($grades[$grade]) ? $grades[$grade] : $grade;
    }
    
    // Получение CSS класса для статуса качества
    public static function getQualityStatusClass($status) {
        $classes = [
            'not_checked' => 'bg-secondary',
            'pending' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger'
        ];
        
        return isset($classes[$status]) ? $classes[$status] : 'bg-secondary';
    }
    
    // Проверка, требует ли заказ проверки качества
    public static function requiresQualityCheck($order_status, $quality_status) {
        return $order_status === 'shipped' && 
               in_array($quality_status, ['not_checked', 'pending']);
    }
    
    // Получение иконки для статуса качества
    public static function getQualityStatusIcon($status) {
        $icons = [
            'not_checked' => 'fas fa-question-circle',
            'pending' => 'fas fa-hourglass-half',
            'approved' => 'fas fa-check-circle',
            'rejected' => 'fas fa-times-circle'
        ];
        
        return isset($icons[$status]) ? $icons[$status] : 'fas fa-question-circle';
    }
    
    // Форматирование параметров качества
    public static function formatQualityParameter($value, $unit = '') {
        if (empty($value)) {
            return '-';
        }
        
        if (is_numeric($value)) {
            return number_format($value, 2) . ($unit ? ' ' . $unit : '');
        }
        
        return htmlspecialchars($value);
    }
    
    // Проверка, находится ли значение в допустимых пределах
    public static function isValueInRange($value, $min, $max) {
        if (empty($value) || !is_numeric($value)) {
            return null; // Значение не задано
        }
        
        $value = floatval($value);
        
        if ($min !== null && $value < $min) {
            return false; // Ниже минимума
        }
        
        if ($max !== null && $value > $max) {
            return false; // Выше максимума
        }
        
        return true; // В пределах нормы
    }
    
    // Получение рекомендаций по улучшению качества
    public static function getQualityRecommendations($temperature, $ph_level, $visual_grade, $smell_grade) {
        $recommendations = [];
        
        // Рекомендации по температуре
        if (!empty($temperature)) {
            if ($temperature > 4) {
                $recommendations[] = 'Зниження температури зберігання до 0-4°C';
            } elseif ($temperature < 0) {
                $recommendations[] = 'Уникнення заморожування сировини';
            }
        }
        
        // Рекомендации по pH
        if (!empty($ph_level)) {
            if ($ph_level > 6.5) {
                $recommendations[] = 'Перевірка кислотності - можливе псування';
            } elseif ($ph_level < 5.5) {
                $recommendations[] = 'Занадто висока кислотність - перевірка якості';
            }
        }
        
        // Рекомендации по внешнему виду
        if (!empty($visual_grade) && $visual_grade < 3) {
            $recommendations[] = 'Покращення умов транспортування та зберігання';
        }
        
        // Рекомендации по запаху
        if (!empty($smell_grade) && $smell_grade < 3) {
            $recommendations[] = 'Перевірка термінів зберігання та умов транспортування';
        }
        
        return $recommendations;
    }
    
    // Все остальные существующие методы остаются без изменений...
    
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
}