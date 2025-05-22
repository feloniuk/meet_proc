<?php
// debug_auth.php - поместите этот файл в корень проекта для отладки
require_once 'config/config.php';

// Только для отладки - удалите в продакшене!
if (!defined('DEBUG') || !DEBUG) {
    die('Debug mode disabled');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Отладка авторизации</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .error { color: red; }
        .success { color: green; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Отладка авторизации</h1>
    
    <div class="section">
        <h2>Информация о сессии</h2>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        <p><strong>Пользователь авторизован:</strong> 
            <span class="<?php echo Auth::isLoggedIn() ? 'success' : 'error'; ?>">
                <?php echo Auth::isLoggedIn() ? 'ДА' : 'НЕТ'; ?>
            </span>
        </p>
        
        <?php if (Auth::isLoggedIn()): ?>
            <p><strong>ID пользователя:</strong> <?php echo Auth::getCurrentUserId(); ?></p>
            <p><strong>Имя пользователя:</strong> <?php echo Auth::getCurrentUserName(); ?></p>
            <p><strong>Роль пользователя:</strong> <?php echo Auth::getCurrentUserRole(); ?></p>
        <?php endif; ?>
        
        <h3>Содержимое $_SESSION:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="section">
        <h2>Пользователи в базе данных</h2>
        <?php
        $db = Database::getInstance();
        $users = $db->resultSet("SELECT id, username, role, name, email, created_at FROM users ORDER BY id");
        ?>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Name</th>
                <th>Email</th>
                <th>Created</th>
            </tr>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['role']); ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo $user['created_at']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Тест авторизации</h2>
        
        <?php if (isset($_POST['test_login'])): ?>
            <div style="margin: 10px 0; padding: 10px; background: #f0f0f0;">
                <?php
                $username = $_POST['username'];
                $password = $_POST['password'];
                
                echo "<p><strong>Попытка входа:</strong> $username</p>";
                
                $user = Auth::authenticate($username, $password);
                if ($user) {
                    echo '<p class="success">✓ Аутентификация прошла успешно</p>';
                    echo '<p>Данные пользователя:</p>';
                    echo '<pre>' . print_r($user, true) . '</pre>';
                    
                    // Попробуем войти
                    Auth::login($user);
                    echo '<p class="success">✓ Пользователь авторизован</p>';
                    
                    // Проверим сессию
                    echo '<p>Проверка сессии после авторизации:</p>';
                    echo '<ul>';
                    echo '<li>isLoggedIn(): ' . (Auth::isLoggedIn() ? 'true' : 'false') . '</li>';
                    echo '<li>getCurrentUserId(): ' . Auth::getCurrentUserId() . '</li>';
                    echo '<li>getCurrentUserRole(): ' . Auth::getCurrentUserRole() . '</li>';
                    echo '<li>getCurrentUserName(): ' . Auth::getCurrentUserName() . '</li>';
                    echo '</ul>';
                } else {
                    echo '<p class="error">✗ Ошибка аутентификации</p>';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <p>
                <label>Username:</label><br>
                <input type="text" name="username" value="admin" required>
            </p>
            <p>
                <label>Password:</label><br>
                <input type="password" name="password" value="password123" required>
            </p>
            <button type="submit" name="test_login">Тест входа</button>
        </form>
        
        <?php if (Auth::isLoggedIn()): ?>
            <p><a href="?logout=1">Выйти из системы</a></p>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout'])): ?>
            <?php Auth::logout(); ?>
            <script>window.location.reload();</script>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Информация о конфигурации</h2>
        <p><strong>BASE_URL:</strong> <?php echo BASE_URL; ?></p>
        <p><strong>BASE_PATH:</strong> <?php echo BASE_PATH; ?></p>
        <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
        <p><strong>Session cookie params:</strong></p>
        <pre><?php print_r(session_get_cookie_params()); ?></pre>
    </div>
    
    <div class="section">
        <h2>Действия</h2>
        <p><a href="<?php echo BASE_URL; ?>/auth/login">Перейти на страницу входа</a></p>
        <p><a href="<?php echo BASE_URL; ?>/home">Перейти на главную</a></p>
    </div>
    
</body>
</html>