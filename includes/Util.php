<?php
class Util {
    // Метод для перенаправлення на іншу сторінку
    public static function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    // Метод для перевірки запиту POST
    public static function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    // Метод для очищення вхідних даних
    public static function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Метод для генерації випадкового рядка
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    // Метод для форматування дати
    public static function formatDate($date, $format = 'd.m.Y H:i') {
        if (empty($date)) {
            return '';
        }
        
        $datetime = new DateTime($date);
        return $datetime->format($format);
    }
    
    // Метод для форматування числа як грошової суми
    public static function formatMoney($amount, $decimals = 2) {
        return number_format($amount, $decimals, '.', ' ') . ' грн';
    }
    
    // Метод для форматування кількості
    public static function formatQuantity($quantity, $unit) {
        return number_format($quantity, 2, '.', ' ') . ' ' . $unit;
    }
    
    // Метод для отримання назви статусу замовлення
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
    
    // Метод для отримання назви статусу виробничого процесу
    public static function getProductionStatusName($status) {
        $statuses = [
            'planned' => 'Заплановано',
            'in_progress' => 'У процесі',
            'completed' => 'Завершено',
            'canceled' => 'Скасовано'
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    // Метод для отримання назви ролі користувача
    public static function getUserRoleName($role) {
        $roles = [
            'admin' => 'Адміністратор',
            'warehouse_manager' => 'Начальник складу',
            'supplier' => 'Постачальник'
        ];
        
        return isset($roles[$role]) ? $roles[$role] : $role;
    }
    
    // Метод для перевірки наявності помилок валідації
    public static function hasErrors($errors) {
        return !empty($errors);
    }
    
    // Метод для отримання класу помилки
    public static function getErrorClass($errors, $field) {
        return isset($errors[$field]) ? 'is-invalid' : '';
    }
    
    // Метод для отримання повідомлення про помилку
    public static function getErrorMessage($errors, $field) {
        return isset($errors[$field]) ? '<div class="invalid-feedback">' . $errors[$field] . '</div>' : '';
    }
    
    // Метод для отримання обраного значення для select
    public static function getSelected($current, $value) {
        return $current == $value ? 'selected' : '';
    }
    
    // Метод для отримання поточної дати і часу в форматі MySQL
    public static function getCurrentDateTime() {
        return date('Y-m-d H:i:s');
    }
    
    // Метод для отримання поточної дати в форматі MySQL
    public static function getCurrentDate() {
        return date('Y-m-d');
    }
}