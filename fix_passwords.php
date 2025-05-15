<?php
// Подключаем конфигурацию
require_once 'config/config.php';

// Получаем соединение с базой данных
$db = Database::getInstance();

// Пароль, который должен быть у всех тестовых пользователей
$password = 'password123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "Сгенерированный хеш: " . $hashed_password . "<br>";

// Обновляем пароли для всех пользователей
$sql = "UPDATE users SET password = ?";
$result = $db->query($sql, [$hashed_password]);

if ($result) {
    echo "Пароли успешно обновлены. Теперь вы можете войти в систему с логином 'admin', 'warehouse', 'supplier1' или 'supplier2' и паролем 'password123'.";
} else {
    echo "Произошла ошибка при обновлении паролей.";
}