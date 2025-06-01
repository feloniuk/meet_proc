<?php
class WarehouseController {
    private $inventoryModel;
    private $rawMaterialModel;
    private $productionModel;
    private $productModel;
    private $orderModel;
    private $userModel;
    
    public function __construct() {
        // Перевірка на авторизацію та роль
        if (!Auth::isLoggedIn() || !Auth::hasRole('warehouse_manager')) {
            Util::redirect(BASE_URL . '/home');
        }
        
        $this->inventoryModel = new Inventory();
        $this->rawMaterialModel = new RawMaterial();
        $this->productionModel = new Production();
        $this->productModel = new Product();
        $this->orderModel = new Order(); 
        $this->userModel = new User();
    }
    
    // Управління інвентаризацією (обновленный метод)
    public function inventory() {
        // Получаем параметры фильтрации
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        $inventory = $this->inventoryModel->getAll();
        
        // Применяем фильтры
        if (!empty($search)) {
            $inventory = array_filter($inventory, function($item) use ($search) {
                return stripos($item['material_name'], $search) !== false;
            });
        }
        
        if (!empty($barcode)) {
            $inventory = array_filter($inventory, function($item) use ($barcode) {
                return !empty($item['barcode']) && stripos($item['barcode'], $barcode) !== false;
            });
        }
        
        if (!empty($status)) {
            $inventory = array_filter($inventory, function($item) use ($status) {
                $item_status = 'good';
                if ($item['quantity'] < $item['min_stock']) {
                    $item_status = 'low';
                } elseif ($item['quantity'] < $item['min_stock'] * 2) {
                    $item_status = 'medium';
                }
                return $item_status === $status;
            });
        }
        
        $data = [
            'title' => 'Інвентаризація',
            'inventory' => $inventory
        ];
        
        require VIEWS_PATH . '/warehouse/inventory.php';
    }
    
    // AJAX метод для получения данных товара
    public function getInventoryItem($material_id) {
        header('Content-Type: application/json');
        
        $item = $this->inventoryModel->getByMaterialId($material_id);
        
        if ($item) {
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Товар не знайдено']);
        }
        exit;
    }
    
    // AJAX метод для обновления записи инвентаризации
    public function updateInventoryItem() {
        header('Content-Type: application/json');
        
        if (!Util::isPost()) {
            echo json_encode(['success' => false, 'message' => 'Неправильний метод запиту']);
            exit;
        }
        
        $material_id = Util::sanitize($_POST['material_id']);
        $quantity = Util::sanitize($_POST['quantity']);
        $actual_quantity = !empty($_POST['actual_quantity']) ? Util::sanitize($_POST['actual_quantity']) : null;
        $barcode = !empty($_POST['barcode']) ? Util::sanitize($_POST['barcode']) : null;
        
        // Валидация
        if (empty($material_id) || empty($quantity)) {
            echo json_encode(['success' => false, 'message' => 'Заповніть обов\'язкові поля']);
            exit;
        }
        
        if (!is_numeric($quantity) || $quantity < 0) {
            echo json_encode(['success' => false, 'message' => 'Кількість повинна бути невід\'ємним числом']);
            exit;
        }
        
        if ($actual_quantity !== null && (!is_numeric($actual_quantity) || $actual_quantity < 0)) {
            echo json_encode(['success' => false, 'message' => 'Фактична кількість повинна бути невід\'ємним числом']);
            exit;
        }
        
        // Проверяем уникальность штрих-кода
        if (!empty($barcode) && !$this->inventoryModel->isBarcodeUnique($barcode, $material_id)) {
            echo json_encode(['success' => false, 'message' => 'Штрих-код вже використовується']);
            exit;
        }
        
        // Обновляем запись
        if ($this->inventoryModel->updateQuantity($material_id, $quantity, $actual_quantity, $barcode, Auth::getCurrentUserId())) {
            echo json_encode(['success' => true, 'message' => 'Дані успішно оновлено']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Помилка при оновленні']);
        }
        exit;
    }
    
    // Генерация штрих-кода для товара
    public function generateBarcode($material_id) {
        header('Content-Type: application/json');
        
        $barcode = $this->inventoryModel->generateBarcode($material_id);
        
        // Проверяем уникальность
        if (!$this->inventoryModel->isBarcodeUnique($barcode, $material_id)) {
            // Если не уникален, добавляем случайную цифру
            $barcode .= rand(0, 9);
        }
        
        // Сохраняем штрих-код
        if ($this->inventoryModel->updateBarcode($material_id, $barcode, Auth::getCurrentUserId())) {
            echo json_encode(['success' => true, 'barcode' => $barcode]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Помилка при генерації штрих-коду']);
        }
        exit;
    }
    
    // Генерация штрих-кодов для всех товаров
    public function generateAllBarcodes() {
        header('Content-Type: application/json');
        
        if (!Util::isPost()) {
            echo json_encode(['success' => false, 'message' => 'Неправильний метод запиту']);
            exit;
        }
        
        $inventory = $this->inventoryModel->getAll();
        $generated = 0;
        
        foreach ($inventory as $item) {
            if (empty($item['barcode'])) {
                $barcode = $this->inventoryModel->generateBarcode($item['raw_material_id']);
                
                // Обеспечиваем уникальность
                $counter = 0;
                while (!$this->inventoryModel->isBarcodeUnique($barcode . ($counter > 0 ? $counter : ''), $item['raw_material_id'])) {
                    $counter++;
                }
                
                $final_barcode = $barcode . ($counter > 0 ? $counter : '');
                
                if ($this->inventoryModel->updateBarcode($item['raw_material_id'], $final_barcode, Auth::getCurrentUserId())) {
                    $generated++;
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => "Згенеровано штрих-кодів: $generated"]);
        exit;
    }
    
    // Массовое обновление инвентаризации
    public function bulkUpdateInventory() {
        header('Content-Type: application/json');
        
        if (!Util::isPost()) {
            echo json_encode(['success' => false, 'message' => 'Неправильний метод запиту']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['data']) || empty($input['data'])) {
            echo json_encode(['success' => false, 'message' => 'Немає даних для обробки']);
            exit;
        }
        
        $lines = explode("\n", trim($input['data']));
        $updated = 0;
        $errors = [];
        
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line) || $line_num == 0) continue; // Пропускаем заголовок
            
            $parts = explode(';', $line);
            if (count($parts) < 3) {
                $errors[] = "Рядок " . ($line_num + 1) . ": Неправильний формат";
                continue;
            }
            
            $barcode = trim($parts[0]);
            $quantity = trim($parts[1]);
            $actual_quantity = trim($parts[2]);
            
            // Проверяем данные
            if (empty($barcode) || !is_numeric($quantity) || !is_numeric($actual_quantity)) {
                $errors[] = "Рядок " . ($line_num + 1) . ": Неправильні дані";
                continue;
            }
            
            // Ищем товар по штрих-коду
            $item = $this->inventoryModel->getByBarcode($barcode);
            if (!$item) {
                $errors[] = "Рядок " . ($line_num + 1) . ": Товар з штрих-кодом $barcode не знайдено";
                continue;
            }
            
            // Обновляем данные
            if ($this->inventoryModel->updateQuantity(
                $item['raw_material_id'], 
                $quantity, 
                $actual_quantity, 
                $barcode, 
                Auth::getCurrentUserId()
            )) {
                $updated++;
            } else {
                $errors[] = "Рядок " . ($line_num + 1) . ": Помилка при оновленні";
            }
        }
        
        $message = "Оновлено записів: $updated";
        if (!empty($errors)) {
            $message .= "\nПомилки:\n" . implode("\n", $errors);
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
        exit;
    }
    
    // Обновленный метод для генерации PDF отчета по инвентаризации
    public function generateInventoryPdf() {
        $inventory = $this->inventoryModel->getInventoryReport();
        
        // Проверяем наличие PDF класса
        if (class_exists('PDF')) {
            $pdf = new PDF('Звіт інвентаризації');
        } else {
            // Используем HTML для печати
            $this->generateInventoryHtml($inventory);
            return;
        }
        
        $pdf->addTitle('Звіт інвентаризації складу на ' . date('d.m.Y H:i'));
        
        // Подготовка данных для таблицы
        $header = [
            'Назва', 'Штрих-код', 'Кількість (облік)', 'Кількість (факт)', 
            'Розбіжність', 'Од.', 'Вартість розбіжності'
        ];
        $data = [];
        
        $total_discrepancy_value = 0;
        
        foreach ($inventory as $item) {
            $difference = $item['quantity_difference'] ?? 0;
            $value_diff = $item['value_difference'] ?? 0;
            $total_discrepancy_value += abs($value_diff);
            
            $data[] = [
                $item['material_name'],
                $item['barcode'] ?: 'Немає',
                number_format($item['quantity'], 2),
                $item['actual_quantity'] !== null ? number_format($item['actual_quantity'], 2) : 'Н/Д',
                number_format($difference, 2),
                $item['unit'],
                number_format($value_diff, 2) . ' грн'
            ];
        }
        
        $pdf->addTable($header, $data);
        
        // Общая статистика
        $total_items = count($inventory);
        $items_with_discrepancies = count(array_filter($inventory, function($item) {
            return abs($item['quantity_difference'] ?? 0) > 0.01;
        }));
        
        $pdf->addText('');
        $pdf->addText('ПІДСУМКИ ІНВЕНТАРИЗАЦІЇ:');
        $pdf->addText('Всього позицій: ' . $total_items);
        $pdf->addText('Позицій з розбіжностями: ' . $items_with_discrepancies);
        $pdf->addText('Загальна вартість розбіжностей: ' . number_format($total_discrepancy_value, 2) . ' грн');
        
        $pdf->addDateAndSignature();
        $pdf->output('inventory_report_' . date('Y-m-d') . '.pdf');
    }
    
    // HTML версия отчета по инвентаризации
    private function generateInventoryHtml($inventory) {
        $total_discrepancy_value = 0;
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Звіт інвентаризації</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .summary { background: #f9f9f9; padding: 15px; margin: 20px 0; }
                .signature { margin-top: 50px; }
                .discrepancy { background-color: #fff3cd; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ЗВІТ ІНВЕНТАРИЗАЦІЇ СКЛАДУ</h1>
                <p>Дата: ' . date('d.m.Y H:i') . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Назва</th>
                        <th>Штрих-код</th>
                        <th>Кількість (облік)</th>
                        <th>Кількість (факт)</th>
                        <th>Розбіжність</th>
                        <th>Од.</th>
                        <th>Вартість розбіжності</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($inventory as $item) {
            $difference = $item['quantity_difference'] ?? 0;
            $value_diff = $item['value_difference'] ?? 0;
            $total_discrepancy_value += abs($value_diff);
            
            $row_class = abs($difference) > 0.01 ? 'discrepancy' : '';
            
            $html .= '<tr class="' . $row_class . '">
                <td>' . htmlspecialchars($item['material_name']) . '</td>
                <td>' . htmlspecialchars($item['barcode'] ?: 'Немає') . '</td>
                <td>' . number_format($item['quantity'], 2) . '</td>
                <td>' . ($item['actual_quantity'] !== null ? number_format($item['actual_quantity'], 2) : 'Н/Д') . '</td>
                <td>' . number_format($difference, 2) . '</td>
                <td>' . htmlspecialchars($item['unit']) . '</td>
                <td>' . number_format($value_diff, 2) . ' грн</td>
            </tr>';
        }
        
        $total_items = count($inventory);
        $items_with_discrepancies = count(array_filter($inventory, function($item) {
            return abs($item['quantity_difference'] ?? 0) > 0.01;
        }));
        
        $html .= '</tbody>
            </table>
            
            <div class="summary">
                <h3>ПІДСУМКИ ІНВЕНТАРИЗАЦІЇ:</h3>
                <p><strong>Всього позицій:</strong> ' . $total_items . '</p>
                <p><strong>Позицій з розбіжностями:</strong> ' . $items_with_discrepancies . '</p>
                <p><strong>Загальна вартість розбіжностей:</strong> ' . number_format($total_discrepancy_value, 2) . ' грн</p>
            </div>
            
            <div class="signature">
                <p>Дата складання: ' . date('d.m.Y') . '</p>
                <p>Підпис відповідального: ________________</p>
                <p>Підпис керівника: ________________</p>
            </div>
            
            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
        </body>
        </html>';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
    
    // Остальные существующие методы остаются без изменений...
    
    // Оновлення кількості сировини (старый метод для совместимости)
    public function updateQuantity($material_id) {
        $material = $this->rawMaterialModel->getById($material_id);
        $inventory = $this->inventoryModel->getByMaterialId($material_id);
        
        if (!$material || !$inventory) {
            $_SESSION['error'] = 'Сировину не знайдено';
            Util::redirect(BASE_URL . '/warehouse/inventory');
        }
        
        $errors = [];
        
        // Обробка форми оновлення кількості
        if (Util::isPost()) {
            $quantity = Util::sanitize($_POST['quantity']);
            $actual_quantity = !empty($_POST['actual_quantity']) ? Util::sanitize($_POST['actual_quantity']) : null;
            $barcode = !empty($_POST['barcode']) ? Util::sanitize($_POST['barcode']) : null;
            
            // Валідація
            if (!is_numeric($quantity) || $quantity < 0) {
                $errors['quantity'] = 'Кількість повинна бути невід\'ємним числом';
            }
            
            if ($actual_quantity !== null && (!is_numeric($actual_quantity) || $actual_quantity < 0)) {
                $errors['actual_quantity'] = 'Фактична кількість повинна бути невід\'ємним числом';
            }
            
            // Якщо помилок немає, оновлюємо кількість
            if (empty($errors)) {
                if ($this->inventoryModel->updateQuantity($material_id, $quantity, $actual_quantity, $barcode, Auth::getCurrentUserId())) {
                    $_SESSION['success'] = 'Кількість успішно оновлено';
                    Util::redirect(BASE_URL . '/warehouse/inventory');
                } else {
                    $_SESSION['error'] = 'Помилка при оновленні кількості';
                }
            }
        }
        
        $data = [
            'title' => 'Оновлення кількості',
            'material' => $material,
            'inventory' => $inventory,
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/warehouse/update_quantity.php';
    }
    
    // Управління виробничими процесами
    public function production() {
        $data = [
            'title' => 'Процеси виробництва',
            'active_processes' => $this->productionModel->getActive(),
            'all_processes' => $this->productionModel->getAll()
        ];
        
        require VIEWS_PATH . '/warehouse/production.php';
    }
    
    // Планування нового виробничого процесу
    public function planProduction() {
        $errors = [];
        
        // Обробка форми планування виробництва
        if (Util::isPost()) {
            $product_id = Util::sanitize($_POST['product_id']);
            $quantity = Util::sanitize($_POST['quantity']);
            $started_at = Util::sanitize($_POST['started_at']);
            $notes = Util::sanitize($_POST['notes']);
            
            // Валідація
            if (empty($product_id)) {
                $errors['product_id'] = 'Виберіть продукт';
            }
            
            if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
                $errors['quantity'] = 'Кількість повинна бути більше нуля';
            }
            
            if (empty($started_at)) {
                $errors['started_at'] = 'Виберіть дату і час початку';
            }
            
            // Якщо помилок немає, планування виробництва
            if (empty($errors)) {
                if ($this->productionModel->plan($product_id, $quantity, $started_at, Auth::getCurrentUserId(), $notes)) {
                    $_SESSION['success'] = 'Виробництво успішно заплановано';
                    Util::redirect(BASE_URL . '/warehouse/production');
                } else {
                    $_SESSION['error'] = 'Помилка при плануванні виробництва';
                }
            }
        }
        
        $data = [
            'title' => 'Планування виробництва',
            'products' => $this->productModel->getAll(),
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/warehouse/plan_production.php';
    }
    
    // Розпочати виробничий процес
    public function startProduction($id) {
        $process = $this->productionModel->getById($id);
        
        if (!$process) {
            $_SESSION['error'] = 'Процес не знайдено';
            Util::redirect(BASE_URL . '/warehouse/production');
        }
        
        // Перевіряємо, чи можна розпочати процес
        if ($process['status'] !== 'planned') {
            $_SESSION['error'] = 'Можна розпочати тільки заплановані процеси';
            Util::redirect(BASE_URL . '/warehouse/production');
        }
        
        // Перевіряємо наявність необхідної сировини
        $availability = $this->inventoryModel->checkRecipeAvailability($process['recipe_id'], $process['quantity']);
        
        if ($availability !== true) {
            $_SESSION['error'] = 'Недостатньо сировини для початку виробництва';
            Util::redirect(BASE_URL . '/warehouse/production');
        }
        
        if ($this->productionModel->start($id, $this->inventoryModel)) {
            $_SESSION['success'] = 'Виробничий процес успішно розпочато';
        } else {
            $_SESSION['error'] = 'Помилка при розпочинанні виробничого процесу';
        }
        
        Util::redirect(BASE_URL . '/warehouse/production');
    }
    
    // Завершити виробничий процес
    public function completeProduction($id) {
        $process = $this->productionModel->getById($id);
        
        if (!$process) {
            $_SESSION['error'] = 'Процес не знайдено';
            Util::redirect(BASE_URL . '/warehouse/production');
        }
        
        // Перевіряємо, чи можна завершити процес
        if ($process['status'] !== 'in_progress') {
            $_SESSION['error'] = 'Можна завершити тільки процеси в стадії виконання';
            Util::redirect(BASE_URL . '/warehouse/production');
        }
        
        if ($this->productionModel->complete($id)) {
            $_SESSION['success'] = 'Виробничий процес успішно завершено';
        } else {
            $_SESSION['error'] = 'Помилка при завершенні виробничого процесу';
        }
        
        Util::redirect(BASE_URL . '/warehouse/production');
    }
    
    // Скасувати виробничий процес
    public function cancelProduction($id) {
        $process = $this->productionModel->getById($id);
        
        if (!$process) {
            $_SESSION['error'] = 'Процес не знайдено';
            Util::redirect(BASE_URL . '/warehouse/production');
        }
        
        // Перевіряємо, чи можна скасувати процес
        if ($process['status'] === 'completed' || $process['status'] === 'canceled') {
            $_SESSION['error'] = 'Неможливо скасувати завершені або вже скасовані процеси';
            Util::redirect(BASE_URL . '/warehouse/production');
        }
        
        if ($this->productionModel->cancel($id)) {
            $_SESSION['success'] = 'Виробничий процес успішно скасовано';
        } else {
            $_SESSION['error'] = 'Помилка при скасуванні виробничого процесу';
        }
        
        Util::redirect(BASE_URL . '/warehouse/production');
    }
    
    // Перегляд деталей виробничого процесу
    public function viewProduction($id) {
        $process = $this->productionModel->getById($id);
        
        if (!$process) {
            $_SESSION['error'] = 'Процес не знайдено';
            Util::redirect(BASE_URL . '/warehouse/production');
        }
        
        // Отримуємо інформацію про рецепт та інгредієнти
        $recipeModel = new Recipe();
        $ingredients = $recipeModel->getIngredients($process['recipe_id']);
        
        $data = [
            'title' => 'Перегляд виробничого процесу',
            'process' => $process,
            'ingredients' => $ingredients
        ];
        
        require VIEWS_PATH . '/warehouse/view_production.php';
    }
    
    // Звіти
    public function reports() {
        $data = [
            'title' => 'Звіти'
        ];
        
        require VIEWS_PATH . '/warehouse/reports.php';
    }
    
    // Звіт по запасам
    public function inventoryReport() {
        $data = [
            'title' => 'Звіт по запасам',
            'inventory' => $this->inventoryModel->getStockReport()
        ];
        
        require VIEWS_PATH . '/warehouse/inventory_report.php';
    }
    
    // Звіт по виробництву
    public function productionReport() {
        // Параметри періоду (за замовчуванням - поточний місяць)
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        $data = [
            'title' => 'Звіт по виробництву',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'stats' => $this->productionModel->getDetailedStatsByPeriod($start_date, $end_date),
            'daily_stats' => $this->productionModel->getStatsByPeriod($start_date, $end_date)
        ];
        
        require VIEWS_PATH . '/warehouse/production_report.php';
    }
    
    // Генерація PDF звіту по виробництву
    public function generateProductionPdf() {
        // Параметри періоду
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        $stats = $this->productionModel->getDetailedStatsByPeriod($start_date, $end_date);
        $products_stats = $this->productModel->getProductionStats($start_date, $end_date);
        
        $pdf = new PDF('Звіт по виробництву');
        $pdf->addTitle('Звіт по виробництву за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
        
        // Підготовка даних для таблиці статистики по продуктах
        $header = ['Продукт', 'Кількість', 'Кількість циклів', 'Сер. час виробництва'];
        $data = [];
        
        foreach ($stats as $item) {
            $data[] = [
                $item['product_name'],
                number_format($item['total_quantity'], 2),
                $item['processes_count'],
                round($item['avg_production_time'], 1) . ' год'
            ];
        }
        
        $pdf->addText('Статистика виробництва по продуктах:');
        $pdf->addTable($header, $data);
        
        // Підготовка даних для таблиці вартості продукції
        $header = ['Продукт', 'Кількість', 'Ціна', 'Загальна вартість'];
        $data = [];
        
        foreach ($products_stats as $item) {
            $data[] = [
                $item['name'],
                number_format($item['total_produced'], 2),
                number_format($item['price'], 2) . ' грн',
                number_format($item['total_value'], 2) . ' грн'
            ];
        }
        
        $pdf->addText('Вартість виробленої продукції:');
        $pdf->addTable($header, $data);
        
        // Загальна вартість
        $total_value = array_sum(array_column($products_stats, 'total_value'));
        $pdf->addText('Загальна вартість виробленої продукції: ' . number_format($total_value, 2) . ' грн');
        
        $pdf->addDateAndSignature();
        $pdf->output('production_report_' . date('Y-m-d') . '.pdf');
    }


    // Перевіряємо права доступу до адміністративних функцій
    private function checkAdminAccess() {
        $user_role = Auth::getCurrentUserRole();
        if ($user_role !== 'warehouse_manager' && $user_role !== 'admin') {
            $_SESSION['error'] = 'У вас немає доступу до цієї функції';
            Util::redirect(BASE_URL . '/warehouse');
        }
    }
    
    // Доступ до управління замовленнями для начальника складу
    public function orders() {
        $data = [
            'title' => 'Замовлення сировини',
            'orders' => $this->orderModel->getAll()
        ];
        
        require VIEWS_PATH . '/warehouse/orders.php';
    }
    
    // Створення замовлення для начальника склада
    public function createOrder() {
        $errors = [];
        
        // Обробка форми створення замовлення
        if (Util::isPost()) {
            $supplier_id = Util::sanitize($_POST['supplier_id']);
            $delivery_date = Util::sanitize($_POST['delivery_date']);
            $notes = Util::sanitize($_POST['notes']);
            
            // Валідація
            if (empty($supplier_id)) {
                $errors['supplier_id'] = 'Виберіть постачальника';
            }
            
            if (empty($delivery_date)) {
                $errors['delivery_date'] = 'Виберіть дату доставки';
            } elseif (strtotime($delivery_date) < strtotime(date('Y-m-d'))) {
                $errors['delivery_date'] = 'Дата доставки не може бути в минулому';
            }
            
            // Якщо помилок немає, створюємо замовлення
            if (empty($errors)) {
                $order_id = $this->orderModel->create($supplier_id, Auth::getCurrentUserId(), $delivery_date, $notes);
                
                if ($order_id) {
                    $_SESSION['success'] = 'Замовлення успішно створено';
                    Util::redirect(BASE_URL . '/warehouse/editOrder/' . $order_id);
                } else {
                    $_SESSION['error'] = 'Помилка при створенні замовлення';
                }
            }
        }
        
        $data = [
            'title' => 'Створення замовлення',
            'suppliers' => $this->userModel->getSuppliers(),
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/warehouse/create_order.php';
    }
    
    // Редагування замовлення для начальника склада
    public function editOrder($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/warehouse/orders');
            return;
        }
        
        // Перевіряємо, чи можна редагувати замовлення
        if ($order['status'] !== 'pending') {
            $_SESSION['error'] = 'Можна редагувати тільки замовлення в статусі "Очікує підтвердження"';
            Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $id);
            return;
        }
        
        // Получаем позиции заказа
        $items = $this->orderModel->getItems($id);
        
        // Получаем материалы поставщика
        $materials = [];
        if (!empty($order['supplier_id'])) {
            try {
                $materials = $this->rawMaterialModel->getBySupplier($order['supplier_id']);
                
                // Если материалов нет, логируем это
                if (empty($materials)) {
                    error_log("No materials found for supplier ID: " . $order['supplier_id']);
                }
            } catch (Exception $e) {
                error_log("Error getting materials for supplier: " . $e->getMessage());
                $materials = [];
            }
        }
        
        $data = [
            'title' => 'Редагування замовлення',
            'order' => $order,
            'items' => $items ?: [],
            'materials' => $materials ?: []
        ];
        
        require VIEWS_PATH . '/warehouse/edit_order.php';
    }
    

public function viewOrder($id) {
    $order = $this->orderModel->getById($id);
    
    if (!$order) {
        $_SESSION['error'] = 'Замовлення не знайдено';
        Util::redirect(BASE_URL . '/warehouse/orders');
        return;
    }
    
    $items = $this->orderModel->getItems($id);
    
    // Подготавливаем все необходимые переменные для представления
    $data = [
        'title' => 'Перегляд замовлення #' . $order['id'],
        'order' => $order,
        'items' => $items
    ];
    
    // Извлекаем переменные для использования в представлении
    extract($data);
    
    // Подключаем представление
    require VIEWS_PATH . '/warehouse/view_order.php';
}
    
    // Додавання елемента до замовлення для начальника склада
    public function addOrderItem($order_id) {
        $order = $this->orderModel->getById($order_id);
        
        if (!$order) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/warehouse/orders');
            return;
        }
        
        // Перевіряємо, чи можна редагувати замовлення
        if ($order['status'] !== 'pending') {
            $_SESSION['error'] = 'Можна редагувати тільки замовлення в статусі "Очікує підтвердження"';
            Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $order_id);
            return;
        }
        
        $errors = [];
        
        // Обробка форми додавання елемента
        if (Util::isPost()) {
            $raw_material_id = isset($_POST['raw_material_id']) ? (int)Util::sanitize($_POST['raw_material_id']) : 0;
            $quantity = isset($_POST['quantity']) ? (float)Util::sanitize($_POST['quantity']) : 0;
            $price_per_unit = isset($_POST['price_per_unit']) ? (float)Util::sanitize($_POST['price_per_unit']) : 0;
            
            // Валідація
            if (empty($raw_material_id)) {
                $errors['raw_material_id'] = 'Виберіть сировину';
            }
            
            if ($quantity <= 0) {
                $errors['quantity'] = 'Кількість повинна бути більше нуля';
            }
            
            if ($price_per_unit <= 0) {
                $errors['price_per_unit'] = 'Ціна повинна бути більше нуля';
            }
            
            // Перевіряємо, чи не існує вже така позиція в замовленні
            if (empty($errors)) {
                $db = Database::getInstance();
                $existingItem = $db->single(
                    "SELECT id FROM order_items WHERE order_id = ? AND raw_material_id = ?", 
                    [$order_id, $raw_material_id]
                );
                
                if ($existingItem) {
                    $errors['raw_material_id'] = 'Ця сировина вже додана до замовлення. Відредагуйте існуючу позицію.';
                }
            }
            
            // Якщо помилок немає, додаємо елемент
            if (empty($errors)) {
                try {
                    $db = Database::getInstance();
                    
                    // Додаємо елемент замовлення
                    $sql = "INSERT INTO order_items (order_id, raw_material_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)";
                    $result = $db->query($sql, [$order_id, $raw_material_id, $quantity, $price_per_unit]);
                    
                    if ($result) {
                        // Оновлюємо загальну суму замовлення
                        $this->orderModel->updateTotalAmount($order_id);
                        
                        $_SESSION['success'] = 'Елемент замовлення успішно додано';
                        Util::redirect(BASE_URL . '/warehouse/editOrder/' . $order_id);
                        return;
                    } else {
                        $_SESSION['error'] = 'Помилка при додаванні елемента замовлення';
                    }
                } catch (Exception $e) {
                    error_log("Error adding order item: " . $e->getMessage());
                    $_SESSION['error'] = 'Помилка бази даних при додаванні елемента замовлення';
                }
            }
        }
        
        // Отримуємо матеріали постачальника
        $materials = $this->rawMaterialModel->getBySupplier($order['supplier_id']);
        
        // Якщо є material_id в GET параметрах, автоматично вибираємо матеріал
        if (isset($_GET['material_id']) && !isset($_POST['raw_material_id'])) {
            $_POST['raw_material_id'] = $_GET['material_id'];
            
            // Автоматично заповнюємо ціну
            $material = $this->rawMaterialModel->getById($_GET['material_id']);
            if ($material) {
                $_POST['price_per_unit'] = $material['price_per_unit'];
            }
        }
        
        $data = [
            'title' => 'Додавання елемента замовлення',
            'order' => $order,
            'materials' => $materials,
            'errors' => $errors
        ];
        
        require VIEWS_PATH . '/warehouse/add_order_item.php';
    }
    
    // Также добавьте метод updateTotalAmount в OrderModel, если его нет:
    // В models/Order.php добавьте этот метод:
    
    public function updateTotalAmount($order_id) {
        try {
            $db = Database::getInstance();
            
            // Вычисляем общую сумму заказа
            $sql = "SELECT SUM(quantity * price_per_unit) as total FROM order_items WHERE order_id = ?";
            $result = $db->single($sql, [$order_id]);
            
            $total_amount = $result['total'] ?? 0;
            
            // Обновляем общую сумму в заказе
            $updateSql = "UPDATE orders SET total_amount = ? WHERE id = ?";
            return $db->query($updateSql, [$total_amount, $order_id]);
            
        } catch (Exception $e) {
            error_log("Error updating total amount: " . $e->getMessage());
            return false;
        }
    }
    
    // Метод addItem в OrderModel должен выглядеть так:
    public function addItem($order_id, $raw_material_id, $quantity, $price_per_unit) {
        try {
            $db = Database::getInstance();
            
            // Проверяем, существует ли уже такая позиция
            $existing = $db->single(
                "SELECT id FROM order_items WHERE order_id = ? AND raw_material_id = ?", 
                [$order_id, $raw_material_id]
            );
            
            if ($existing) {
                return false; // Позиция уже существует
            }
            
            $sql = "INSERT INTO order_items (order_id, raw_material_id, quantity, price_per_unit) 
                    VALUES (?, ?, ?, ?)";
            
            $result = $db->query($sql, [$order_id, $raw_material_id, $quantity, $price_per_unit]);
            
            if ($result) {
                // Обновляем общую сумму заказа
                $this->updateTotalAmount($order_id);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error in addItem: " . $e->getMessage());
            return false;
        }
    }
    
    // Підтвердження отримання замовлення для начальника склада
    public function deliverOrder($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/warehouse/orders');
        }
        
        // Перевіряємо, чи можна підтвердити отримання
        if ($order['status'] !== 'shipped') {
            $_SESSION['error'] = 'Можна підтвердити отримання тільки для замовлень в статусі "Відправлено"';
            Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $id);
        }
        
        // Перевіряємо статус якості (якщо система перевірки якості активна)
        if (isset($order['quality_status'])) {
            if ($order['quality_status'] === 'rejected') {
                $_SESSION['error'] = 'Неможливо прийняти замовлення - сировину відхилено технологом';
                Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $id);
                return;
            }
            
            if ($order['quality_status'] === 'pending') {
                $_SESSION['warning'] = 'Увага! Якість сировини ще не перевірена технологом. Замовлення буде прийнято, але рекомендується дочекатися перевірки якості.';
            }
        }
        
        try {
            // Подтверждаем получение заказа
            if ($this->orderModel->deliver($id, $this->inventoryModel)) {
                $_SESSION['success'] = 'Отримання замовлення успішно підтверджено';
                
                // Отправляем уведомления
                $this->sendDeliveryNotifications($order);
            } else {
                $_SESSION['error'] = 'Помилка при підтвердженні отримання замовлення';
            }
        } catch (Exception $e) {
            error_log("Error delivering order: " . $e->getMessage());
            $_SESSION['error'] = 'Помилка при підтвердженні отримання замовлення';
        }
        
        Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $id);
    }
    
    // Отправка уведомлений о доставке
    private function sendDeliveryNotifications($order) {
        try {
            $messageModel = new Message();
            $userModel = new User();
            
            // Уведомление администратору
            $admins = $userModel->getByRole('admin');
            foreach ($admins as $admin) {
                $messageModel->send(
                    Auth::getCurrentUserId(),
                    $admin['id'],
                    'Замовлення №' . $order['id'] . ' доставлено',
                    'Замовлення №' . $order['id'] . ' від постачальника "' . $order['supplier_name'] . '" успішно доставлено та прийнято на склад.'
                );
            }
            
            // Уведомление поставщику
            $messageModel->send(
                Auth::getCurrentUserId(),
                $order['supplier_id'],
                'Замовлення №' . $order['id'] . ' доставлено',
                'Ваше замовлення №' . $order['id'] . ' успішно доставлено та прийнято на склад. Дякуємо за співпрацю!'
            );
            
        } catch (Exception $e) {
            error_log("Error sending delivery notifications: " . $e->getMessage());
        }
    }
    
    // Скасування замовлення для начальника склада
    public function cancelOrder($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/warehouse/orders');
        }
        
        // Перевіряємо, чи можна скасувати замовлення
        if ($order['status'] === 'delivered' || $order['status'] === 'canceled') {
            $_SESSION['error'] = 'Неможливо скасувати замовлення в статусі "Доставлено" або "Скасовано"';
            Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $id);
        }
        
        if ($this->orderModel->cancel($id)) {
            $_SESSION['success'] = 'Замовлення успішно скасовано';
        } else {
            $_SESSION['error'] = 'Помилка при скасуванні замовлення';
        }
        
        Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $id);
    }

    // AJAX метод для обновления полей инвентаризации
    public function updateInventoryField() {
        header('Content-Type: application/json');
        
        if (!Util::isPost()) {
            echo json_encode(['success' => false, 'message' => 'Недопустимий метод запиту']);
            exit;
        }
        
        $material_id = Util::sanitize($_POST['material_id']);
        $field = Util::sanitize($_POST['field']);
        $value = Util::sanitize($_POST['value']);
        
        // Валідація
        if (empty($material_id) || empty($field)) {
            echo json_encode(['success' => false, 'message' => 'Недостатньо даних']);
            exit;
        }
        
        $success = false;
        $manager_id = Auth::getCurrentUserId();
        
        switch ($field) {
            case 'quantity_actual':
                if (!is_numeric($value) || $value < 0) {
                    echo json_encode(['success' => false, 'message' => 'Кількість повинна бути невід\'ємним числом']);
                    exit;
                }
                $success = $this->inventoryModel->updateActualQuantity($material_id, $value, $manager_id);
                break;
                
            case 'barcode':
                // Перевіряємо унікальність штрих-коду
                if (!empty($value)) {
                    $existing = $this->inventoryModel->getByBarcode($value);
                    if ($existing && $existing['raw_material_id'] != $material_id) {
                        echo json_encode(['success' => false, 'message' => 'Штрих-код вже використовується']);
                        exit;
                    }
                }
                $success = $this->inventoryModel->updateBarcode($material_id, $value, $manager_id);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Невідоме поле']);
                exit;
        }
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Дані успішно оновлено']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Помилка при оновленні даних']);
        }
        exit;
    }
    
    // AJAX метод для вирівнювання кількості
    public function adjustQuantity() {
        header('Content-Type: application/json');
        
        if (!Util::isPost()) {
            echo json_encode(['success' => false, 'message' => 'Недопустимий метод запиту']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $material_id = $input['material_id'] ?? null;
        
        if (empty($material_id)) {
            echo json_encode(['success' => false, 'message' => 'Недостатньо даних']);
            exit;
        }
        
        // Отримуємо поточні дані
        $inventory = $this->inventoryModel->getByMaterialId($material_id);
        
        if (!$inventory) {
            echo json_encode(['success' => false, 'message' => 'Запис не знайдено']);
            exit;
        }
        
        $quantity_actual = $inventory['quantity_actual'] ?? $inventory['quantity'];
        $manager_id = Auth::getCurrentUserId();
        
        // Вирівнюємо планову кількість з фактичною
        $success = $this->inventoryModel->updateQuantity($material_id, $quantity_actual, $manager_id, $quantity_actual);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Кількість успішно вирівняно']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Помилка при вирівнюванні кількості']);
        }
        exit;
    }
    
    // Звіт про розбіжності в інвентаризації
    public function discrepanciesReport() {
        $discrepancies = $this->inventoryModel->getDiscrepancies();
        
        $data = [
            'title' => 'Звіт про розбіжності в інвентаризації',
            'discrepancies' => $discrepancies
        ];
        
        require VIEWS_PATH . '/warehouse/discrepancies_report.php';
    }
    
    // Пошук по штрих-коду
    public function searchByBarcode() {
        $barcode = isset($_GET['barcode']) ? Util::sanitize($_GET['barcode']) : '';
        
        if (empty($barcode)) {
            $_SESSION['error'] = 'Введіть штрих-код для пошуку';
            Util::redirect(BASE_URL . '/warehouse/inventory');
        }
        
        $item = $this->inventoryModel->getByBarcode($barcode);
        
        if ($item) {
            $data = [
                'title' => 'Результат пошуку по штрих-коду',
                'item' => $item,
                'barcode' => $barcode
            ];
            
            require VIEWS_PATH . '/warehouse/barcode_search_result.php';
        } else {
            $_SESSION['error'] = 'Матеріал з штрих-кодом "' . $barcode . '" не знайдено';
            Util::redirect(BASE_URL . '/warehouse/inventory');
        }
    }

    public function ajaxGetOrderItem() {
        header('Content-Type: application/json');
        
        $item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$item_id) {
            echo json_encode(['success' => false, 'message' => 'Невірний ID позиції']);
            exit;
        }
        
        try {
            $sql = "SELECT oi.*, rm.name as material_name, rm.unit 
                    FROM order_items oi 
                    JOIN raw_materials rm ON oi.raw_material_id = rm.id 
                    WHERE oi.id = ?";
            
            $db = Database::getInstance();
            $item = $db->single($sql, [$item_id]);
            
            if ($item) {
                echo json_encode(['success' => true, 'item' => $item]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Позицію не знайдено']);
            }
        } catch (Exception $e) {
            error_log("Error in ajaxGetOrderItem: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Помилка бази даних']);
        }
        
        exit;
    }
    
    // AJAX метод для обновления позиции заказа
    public function ajaxUpdateOrderItem() {
        header('Content-Type: application/json');
        
        if (!Util::isPost()) {
            echo json_encode(['success' => false, 'message' => 'Неправильний метод запиту']);
            exit;
        }
        
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
        $price_per_unit = isset($_POST['price_per_unit']) ? floatval($_POST['price_per_unit']) : 0;
        
        // Валидация
        if (!$item_id) {
            echo json_encode(['success' => false, 'message' => 'Невірний ID позиції']);
            exit;
        }
        
        if ($quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Кількість повинна бути більше нуля']);
            exit;
        }
        
        if ($price_per_unit <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ціна повинна бути більше нуля']);
            exit;
        }
        
        try {
            $db = Database::getInstance();
            
            // Проверяем статус заказа
            $sql = "SELECT o.status, o.id as order_id 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE oi.id = ?";
            $result = $db->single($sql, [$item_id]);
            
            if (!$result) {
                echo json_encode(['success' => false, 'message' => 'Позицію не знайдено']);
                exit;
            }
            
            if ($result['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Можна редагувати тільки замовлення в статусі "Очікує підтвердження"']);
                exit;
            }
            
            // Обновляем позицию
            $updateSql = "UPDATE order_items SET quantity = ?, price_per_unit = ? WHERE id = ?";
            $updateResult = $db->query($updateSql, [$quantity, $price_per_unit, $item_id]);
            
            if ($updateResult) {
                // Обновляем общую сумму заказа
                $this->orderModel->updateTotalAmount($result['order_id']);
                
                // Получаем обновленную информацию о позиции
                $itemSql = "SELECT oi.*, rm.name as material_name, rm.unit 
                            FROM order_items oi 
                            JOIN raw_materials rm ON oi.raw_material_id = rm.id 
                            WHERE oi.id = ?";
                $updatedItem = $db->single($itemSql, [$item_id]);
                
                // Получаем обновленную общую сумму заказа
                $orderSql = "SELECT total_amount FROM orders WHERE id = ?";
                $order = $db->single($orderSql, [$result['order_id']]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Позицію успішно оновлено',
                    'item' => $updatedItem,
                    'totalAmount' => $order['total_amount']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Помилка при оновленні позиції']);
            }
            
        } catch (Exception $e) {
            error_log("Error in ajaxUpdateOrderItem: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Помилка бази даних']);
        }
        
        exit;
    }
    
    // AJAX метод для удаления позиции заказа
    public function ajaxDeleteOrderItem() {
        header('Content-Type: application/json');
        
        if (!Util::isPost()) {
            echo json_encode(['success' => false, 'message' => 'Неправильний метод запиту']);
            exit;
        }
        
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        
        if (!$item_id) {
            echo json_encode(['success' => false, 'message' => 'Невірний ID позиції']);
            exit;
        }
        
        try {
            $db = Database::getInstance();
            
            // Получаем информацию о позиции и заказе
            $sql = "SELECT oi.order_id, o.status 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE oi.id = ?";
            $result = $db->single($sql, [$item_id]);
            
            if (!$result) {
                echo json_encode(['success' => false, 'message' => 'Позицію не знайдено']);
                exit;
            }
            
            if ($result['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Можна видаляти позиції тільки з замовлень в статусі "Очікує підтвердження"']);
                exit;
            }
            
            // Удаляем позицию
            $deleteSql = "DELETE FROM order_items WHERE id = ?";
            $deleteResult = $db->query($deleteSql, [$item_id]);
            
            if ($deleteResult) {
                // Обновляем общую сумму заказа
                $this->orderModel->updateTotalAmount($result['order_id']);
                
                echo json_encode(['success' => true, 'message' => 'Позицію успішно видалено']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Помилка при видаленні позиції']);
            }
            
        } catch (Exception $e) {
            error_log("Error in ajaxDeleteOrderItem: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Помилка бази даних']);
        }
        
        exit;
    }
    
    // Добавьте также метод для удаления позиции заказа (не AJAX)
    public function deleteOrderItem($id) {
        try {
            $db = Database::getInstance();
            
            // Получаем ID заказа для перенаправления
            $sql = "SELECT oi.order_id, o.status 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE oi.id = ?";
            $result = $db->single($sql, [$id]);
            
            if (!$result) {
                $_SESSION['error'] = 'Елемент замовлення не знайдено';
                Util::redirect(BASE_URL . '/warehouse/orders');
                return;
            }
            
            $order_id = $result['order_id'];
            
            // Проверяем статус заказа
            if ($result['status'] !== 'pending') {
                $_SESSION['error'] = 'Можна видаляти позиції тільки з замовлень в статусі "Очікує підтвердження"';
                Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $order_id);
                return;
            }
            
            // Удаляем позицию
            $deleteSql = "DELETE FROM order_items WHERE id = ?";
            if ($db->query($deleteSql, [$id])) {
                // Обновляем общую сумму заказа
                $this->orderModel->updateTotalAmount($order_id);
                
                $_SESSION['success'] = 'Позицію замовлення успішно видалено';
            } else {
                $_SESSION['error'] = 'Помилка при видаленні позиції замовлення';
            }
            
            Util::redirect(BASE_URL . '/warehouse/editOrder/' . $order_id);
            
        } catch (Exception $e) {
            error_log("Error in deleteOrderItem: " . $e->getMessage());
            $_SESSION['error'] = 'Помилка бази даних при видаленні позиції';
            Util::redirect(BASE_URL . '/warehouse/orders');
        }
    }

    public function printOrder($id) {
        try {
            $order = $this->orderModel->getById($id);
            
            if (!$order) {
                $_SESSION['error'] = 'Замовлення не знайдено';
                Util::redirect(BASE_URL . '/warehouse/orders');
                return;
            }
            
            $items = $this->orderModel->getItems($id);
            
            // Проверяем, есть ли TCPDF
            if (class_exists('PDF')) {
                $this->printOrderWithTCPDF($order, $items);
            } else {
                $this->printOrderAsHtml($order, $items);
            }
            
        } catch (Exception $e) {
            error_log('Print order error: ' . $e->getMessage());
            $_SESSION['error'] = 'Помилка при генерації документу';
            Util::redirect(BASE_URL . '/warehouse/orders');
        }
    }
    
    private function printOrderWithTCPDF($order, $items) {
        try {
            $pdf = new PDF('Замовлення №' . $order['id']);
            $pdf->addTitle('ЗАМОВЛЕННЯ №' . $order['id'], 'Дата: ' . date('d.m.Y', strtotime($order['created_at'])));
            
            // Информация о заказе
            $pdf->addText('Замовник: ' . Auth::getCurrentUserName());
            $pdf->addText('Постачальник: ' . $order['supplier_name']);
            $pdf->addText('Статус: ' . Util::getOrderStatusName($order['status']));
            $pdf->addText('Дата замовлення: ' . date('d.m.Y H:i', strtotime($order['created_at'])));
            
            if (!empty($order['delivery_date'])) {
                $pdf->addText('Планована доставка: ' . date('d.m.Y', strtotime($order['delivery_date'])));
            }
            
            $pdf->addText('');
            
            // Позиции заказа
            if (!empty($items)) {
                $header = ['№', 'Назва', 'Кількість', 'Ціна за од.', 'Сума'];
                $data = [];
                
                $counter = 1;
                foreach ($items as $item) {
                    $data[] = [
                        $counter++,
                        $item['material_name'],
                        number_format($item['quantity'], 2) . ' ' . $item['unit'],
                        number_format($item['price_per_unit'], 2) . ' грн',
                        number_format($item['quantity'] * $item['price_per_unit'], 2) . ' грн'
                    ];
                }
                
                // Итоговая сумма
                $data[] = ['', '', '', 'ВСЬОГО:', number_format($order['total_amount'], 2) . ' грн'];
                
                $pdf->addTable($header, $data);
            }
            
            // Примечания
            if (!empty($order['notes'])) {
                $pdf->addText('');
                $pdf->addText('Примітки: ' . $order['notes']);
            }
            
            $pdf->addDateAndSignature();
            $pdf->output('order_' . $order['id'] . '.pdf');
            
        } catch (Exception $e) {
            error_log('PDF generation error: ' . $e->getMessage());
            $this->printOrderAsHtml($order, $items);
        }
    }
    
    private function printOrderAsHtml($order, $items) {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Замовлення №' . $order['id'] . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .info-table { width: 100%; margin-bottom: 20px; }
                .info-table td { padding: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .total { font-weight: bold; }
                .signature { margin-top: 50px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ЗАМОВЛЕННЯ №' . $order['id'] . '</h1>
                <p>Дата: ' . date('d.m.Y', strtotime($order['created_at'])) . '</p>
            </div>
            
            <table class="info-table">
                <tr>
                    <td><strong>Замовник:</strong></td>
                    <td>' . htmlspecialchars(Auth::getCurrentUserName()) . '</td>
                </tr>
                <tr>
                    <td><strong>Постачальник:</strong></td>
                    <td>' . htmlspecialchars($order['supplier_name']) . '</td>
                </tr>
                <tr>
                    <td><strong>Статус:</strong></td>
                    <td>' . Util::getOrderStatusName($order['status']) . '</td>
                </tr>';
        
        if (!empty($order['delivery_date'])) {
            $html .= '<tr>
                <td><strong>Дата доставки:</strong></td>
                <td>' . date('d.m.Y', strtotime($order['delivery_date'])) . '</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        if (!empty($items)) {
            $html .= '<h3>Позиції замовлення:</h3>
            <table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Назва</th>
                        <th>Кількість</th>
                        <th>Ціна за од.</th>
                        <th>Сума</th>
                    </tr>
                </thead>
                <tbody>';
            
            $counter = 1;
            foreach ($items as $item) {
                $html .= '<tr>
                    <td>' . $counter++ . '</td>
                    <td>' . htmlspecialchars($item['material_name']) . '</td>
                    <td>' . number_format($item['quantity'], 2) . ' ' . $item['unit'] . '</td>
                    <td>' . number_format($item['price_per_unit'], 2) . ' грн</td>
                    <td>' . number_format($item['quantity'] * $item['price_per_unit'], 2) . ' грн</td>
                </tr>';
            }
            
            $html .= '<tr class="total">
                <td colspan="4">ВСЬОГО:</td>
                <td>' . number_format($order['total_amount'], 2) . ' грн</td>
            </tr>
            </tbody>
            </table>';
        }
        
        if (!empty($order['notes'])) {
            $html .= '<h3>Примітки:</h3>
            <p>' . nl2br(htmlspecialchars($order['notes'])) . '</p>';
        }
        
        $html .= '<div class="signature">
                <p>Дата: ' . date('d.m.Y') . '</p>
                <p>Підпис замовника: ________________</p>
                <p>Підпис постачальника: ________________</p>
            </div>
            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
        </body>
        </html>';
        
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
}