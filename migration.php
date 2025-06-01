<?php
// migration.php - Скрипт для обновления проекта
require_once 'config/config.php';

// Проверяем права доступа
if (!Auth::isLoggedIn() || !Auth::hasRole('admin')) {
    die('Доступ заборонено. Тільки адміністратор може виконувати міграції.');
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Міграція бази даних</title>";
echo "<style>body{font-family: Arial, sans-serif; margin: 20px;} .success{color: green;} .error{color: red;} .info{color: blue;}</style></head><body>";
echo "<h1>Міграція бази даних - Оновлення проекту</h1>";

$db = Database::getInstance();
$errors = [];
$success = [];

try {
    // Начинаем транзакцию
    $db->beginTransaction();
    
    echo "<h2>Крок 1: Додавання нових полів до таблиці inventory</h2>";
    
    // Проверяем, существуют ли уже поля
    $columns = $db->resultSet("SHOW COLUMNS FROM inventory");
    $existing_columns = array_column($columns, 'Field');
    
    // Добавляем поле actual_quantity если его нет
    if (!in_array('actual_quantity', $existing_columns)) {
        $sql = "ALTER TABLE inventory ADD COLUMN actual_quantity DECIMAL(10,2) DEFAULT NULL COMMENT 'Фактичне количество при інвентаризації'";
        if ($db->query($sql)) {
            $success[] = "✓ Додано поле 'actual_quantity' до таблиці inventory";
        } else {
            $errors[] = "✗ Помилка додавання поля 'actual_quantity'";
        }
    } else {
        $success[] = "✓ Поле 'actual_quantity' вже існує";
    }
    
    // Добавляем поле barcode если его нет
    if (!in_array('barcode', $existing_columns)) {
        $sql = "ALTER TABLE inventory ADD COLUMN barcode VARCHAR(100) DEFAULT NULL COMMENT 'Штрих-код товару'";
        if ($db->query($sql)) {
            $success[] = "✓ Додано поле 'barcode' до таблиці inventory";
        } else {
            $errors[] = "✗ Помилка додавання поля 'barcode'";
        }
    } else {
        $success[] = "✓ Поле 'barcode' вже існує";
    }
    
    echo "<h2>Крок 2: Додавання індексів</h2>";
    
    // Проверяем существующие индексы
    $indexes = $db->resultSet("SHOW INDEX FROM inventory");
    $existing_indexes = array_column($indexes, 'Key_name');
    
    // Добавляем индекс для штрих-кода
    if (!in_array('idx_inventory_barcode', $existing_indexes)) {
        $sql = "ALTER TABLE inventory ADD INDEX idx_inventory_barcode (barcode)";
        if ($db->query($sql)) {
            $success[] = "✓ Додано індекс для поля 'barcode'";
        } else {
            $errors[] = "✗ Помилка додавання індексу для barcode";
        }
    } else {
        $success[] = "✓ Індекс для barcode вже існує";
    }
    
    echo "<h2>Крок 3: Оновлення існуючих записів</h2>";
    
    // Устанавливаем фактическое количество равным текущему для всех записей где оно NULL
    $sql = "UPDATE inventory SET actual_quantity = quantity WHERE actual_quantity IS NULL";
    $affected = $db->query($sql) ? $db->rowCount("SELECT * FROM inventory WHERE actual_quantity IS NOT NULL") : 0;
    $success[] = "✓ Оновлено записів з фактичною кількістю: $affected";
    
    echo "<h2>Крок 4: Генерація тестових штрих-кодів</h2>";
    
    // Генерируем штрих-коды для записей без них
    $items_without_barcode = $db->resultSet("SELECT raw_material_id FROM inventory WHERE barcode IS NULL OR barcode = ''");
    $generated_barcodes = 0;
    
    foreach ($items_without_barcode as $item) {
        $barcode = 'BC' . str_pad($item['raw_material_id'], 6, '0', STR_PAD_LEFT);
        
        // Добавляем контрольную цифру
        $check_digit = calculateCheckDigit(str_pad($item['raw_material_id'], 6, '0', STR_PAD_LEFT));
        $barcode .= $check_digit;
        
        $sql = "UPDATE inventory SET barcode = ? WHERE raw_material_id = ?";
        if ($db->query($sql, [$barcode, $item['raw_material_id']])) {
            $generated_barcodes++;
        }
    }
    
    $success[] = "✓ Згенеровано штрих-кодів: $generated_barcodes";
    
    echo "<h2>Крок 5: Перевірка цілісності даних</h2>";
    
    // Проверяем целостность данных
    $total_items = $db->single("SELECT COUNT(*) as count FROM inventory")['count'];
    $items_with_barcode = $db->single("SELECT COUNT(*) as count FROM inventory WHERE barcode IS NOT NULL AND barcode != ''")['count'];
    $items_with_actual_qty = $db->single("SELECT COUNT(*) as count FROM inventory WHERE actual_quantity IS NOT NULL")['count'];
    
    $success[] = "✓ Всього записів в інвентарі: $total_items";
    $success[] = "✓ Записів зі штрих-кодами: $items_with_barcode";
    $success[] = "✓ Записів з фактичною кількістю: $items_with_actual_qty";
    
    // Если все прошло успешно, фиксируем транзакцию
    if (empty($errors)) {
        $db->commit();
        echo "<h2 class='success'>✓ Міграція завершена успішно!</h2>";
    } else {
        $db->rollBack();
        echo "<h2 class='error'>✗ Міграція скасована через помилки</h2>";
    }
    
} catch (Exception $e) {
    $db->rollBack();
    $errors[] = "✗ Критична помилка: " . $e->getMessage();
    echo "<h2 class='error'>✗ Міграція скасована через критичну помилку</h2>";
}

// Выводим результаты
if (!empty($success)) {
    echo "<h3 class='success'>Успішні операції:</h3>";
    echo "<ul>";
    foreach ($success as $msg) {
        echo "<li class='success'>$msg</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h3 class='error'>Помилки:</h3>";
    echo "<ul>";
    foreach ($errors as $msg) {
        echo "<li class='error'>$msg</li>";
    }
    echo "</ul>";
}

echo "<h2>Крок 6: Рекомендації після міграції</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>Що потрібно зробити далі:</h4>";
echo "<ol>";
echo "<li><strong>Оновіть файли проекту:</strong> Замініть старі файли на нові версії контролерів та представлень</li>";
echo "<li><strong>Перевірте права доступу:</strong> Переконайтеся, що начальник складу має доступ до розділу 'Замовлення'</li>";
echo "<li><strong>Протестуйте інвентаризацію:</strong> Перевірте роботу нових полів 'Кількість по факту' та 'Штрих-код'</li>";
echo "<li><strong>Очистіть кеш:</strong> Якщо використовується кешування, очистіть його</li>";
echo "<li><strong>Видаліть цей файл:</strong> Після успішної міграції видаліть migration.php з сервера</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin-top: 30px;'>";
echo "<a href='" . BASE_URL . "/warehouse/inventory' class='btn btn-primary' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Перейти до інвентаризації</a> ";
echo "<a href='" . BASE_URL . "/home' class='btn btn-secondary' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-left: 10px;'>На головну</a>";
echo "</div>";

echo "</body></html>";

// Функция для вычисления контрольной цифры штрих-кода
function calculateCheckDigit($code) {
    $sum = 0;
    $length = strlen($code);
    
    for ($i = 0; $i < $length; $i++) {
        $digit = intval($code[$i]);
        if ($i % 2 == 0) {
            $sum += $digit;
        } else {
            $sum += $digit * 3;
        }
    }
    
    $remainder = $sum % 10;
    return $remainder == 0 ? 0 : 10 - $remainder;
}
?>