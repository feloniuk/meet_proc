<?php
// fix_passwords.php - поместите в корень проекта
require_once 'config/config.php';

// Только для отладки!
if (!defined('DEBUG') || !DEBUG) {
    die('Debug mode disabled');
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Исправление паролей</title>";
echo "<style>body{font-family: Arial, sans-serif; margin: 20px;} .success{color: green;} .error{color: red;} .info{color: blue;}</style></head><body>";
echo "<h1>Исправление паролей в базе данных</h1>";

$db = Database::getInstance();

// Проверяем текущие пароли
echo "<h2>Текущее состояние паролей:</h2>";
$users = $db->resultSet("SELECT id, username, password, role FROM users ORDER BY id");

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Password Hash</th><th>Hash Length</th></tr>";

foreach ($users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "<td>" . substr($user['password'], 0, 50) . "...</td>";
    echo "<td>" . strlen($user['password']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Исправляем пароли
if (isset($_POST['fix_passwords'])) {
    echo "<h2>Исправление паролей:</h2>";
    
    $default_password = 'password123';
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
    
    echo "<p class='info'>Новый хеш пароля: " . substr($hashed_password, 0, 50) . "...</p>";
    echo "<p class='info'>Длина хеша: " . strlen($hashed_password) . "</p>";
    
    $users_to_update = [
        'admin' => 'password123',
        'warehouse' => 'password123', 
        'supplier1' => 'password123',
        'supplier2' => 'password123',
        'technologist' => 'password123'
    ];
    
    foreach ($users_to_update as $username => $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE username = ?";
        
        if ($db->query($sql, [$hashed, $username])) {
            echo "<p class='success'>✓ Пароль для '$username' обновлен</p>";
        } else {
            echo "<p class='error'>✗ Ошибка обновления пароля для '$username'</p>";
        }
    }
    
    echo "<h3>Тестирование обновленных паролей:</h3>";
    
    foreach ($users_to_update as $username => $password) {
        $user = $db->single("SELECT * FROM users WHERE username = ?", [$username]);
        if ($user) {
            $verify_result = password_verify($password, $user['password']);
            $status = $verify_result ? 'success' : 'error';
            $icon = $verify_result ? '✓' : '✗';
            echo "<p class='$status'>$icon $username ($password): " . ($verify_result ? 'OK' : 'FAIL') . "</p>";
        }
    }
    
    echo "<p><a href='debug_auth.php'>Перейти к тестированию авторизации</a></p>";
}

// Тест конкретного пароля
if (isset($_POST['test_specific'])) {
    echo "<h2>Тест конкретного пароля:</h2>";
    
    $test_username = $_POST['test_username'];
    $test_password = $_POST['test_password'];
    
    $user = $db->single("SELECT * FROM users WHERE username = ?", [$test_username]);
    
    if ($user) {
        echo "<p>Пользователь найден: {$user['username']} (ID: {$user['id']})</p>";
        echo "<p>Хеш в БД: " . substr($user['password'], 0, 50) . "...</p>";
        echo "<p>Длина хеша: " . strlen($user['password']) . "</p>";
        
        $verify_result = password_verify($test_password, $user['password']);
        $status = $verify_result ? 'success' : 'error';
        $icon = $verify_result ? '✓' : '✗';
        
        echo "<p class='$status'>$icon Проверка пароля: " . ($verify_result ? 'УСПЕШНО' : 'НЕУДАЧНО') . "</p>";
        
        // Дополнительная отладка
        echo "<h3>Дополнительная отладка:</h3>";
        echo "<p>Введенный пароль: '$test_password'</p>";
        echo "<p>Длина пароля: " . strlen($test_password) . "</p>";
        
        // Создаем новый хеш для сравнения
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "<p>Новый хеш для этого пароля: " . substr($new_hash, 0, 50) . "...</p>";
        echo "<p>Проверка нового хеша: " . (password_verify($test_password, $new_hash) ? 'OK' : 'FAIL') . "</p>";
        
    } else {
        echo "<p class='error'>Пользователь '$test_username' не найден</p>";
    }
}

?>

<h2>Действия:</h2>

<form method="post" style="margin: 20px 0;">
    <button type="submit" name="fix_passwords" style="background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer;">
        Исправить все пароли (установить password123)
    </button>
</form>

<form method="post" style="margin: 20px 0; border: 1px solid #ccc; padding: 15px;">
    <h3>Тест конкретного пароля:</h3>
    <p>
        <label>Username:</label><br>
        <input type="text" name="test_username" value="admin" required>
    </p>
    <p>
        <label>Password:</label><br>
        <input type="text" name="test_password" value="password123" required>
    </p>
    <button type="submit" name="test_specific">Тестировать пароль</button>
</form>

<h2>SQL команды для ручного исправления:</h2>
<textarea readonly style="width: 100%; height: 200px;">
-- Обновление всех паролей на password123
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'warehouse';
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'supplier1';
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'supplier2';
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'technologist';

-- Проверка обновленных паролей
SELECT id, username, role, LENGTH(password) as password_length FROM users;
</textarea>

<p><a href="debug_auth.php">← Вернуться к отладке авторизации</a></p>

</body></html>