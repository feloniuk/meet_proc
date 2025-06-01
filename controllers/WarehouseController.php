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
        }
        
        // Перевіряємо, чи можна редагувати замовлення
        if ($order['status'] !== 'pending') {
            $_SESSION['error'] = 'Можна редагувати тільки замовлення в статусі "Очікує підтвердження"';
            Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $id);
        }
        
        $data = [
            'title' => 'Редагування замовлення',
            'order' => $order,
            'items' => $this->orderModel->getItems($id),
            'materials' => $this->rawMaterialModel->getBySupplier($order['supplier_id'])
        ];
        
        require VIEWS_PATH . '/warehouse/edit_order.php';
    }
    
    // Перегляд замовлення для начальника склада
    public function viewOrder($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            $_SESSION['error'] = 'Замовлення не знайдено';
            Util::redirect(BASE_URL . '/warehouse/orders');
        }
        
        $data = [
            'title' => 'Перегляд замовлення',
            'order' => $order,
            'items' => $this->orderModel->getItems($id)
        ];
        
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $raw_material_id = isset($_POST['raw_material_id']) ? trim($_POST['raw_material_id']) : '';
            $quantity = isset($_POST['quantity']) ? trim($_POST['quantity']) : '';
            $price_per_unit = isset($_POST['price_per_unit']) ? trim($_POST['price_per_unit']) : '';
            
            // Валідація
            if (empty($raw_material_id)) {
                $errors['raw_material_id'] = 'Виберіть сировину';
            }
            
            if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
                $errors['quantity'] = 'Кількість повинна бути більше нуля';
            }
            
            if (empty($price_per_unit) || !is_numeric($price_per_unit) || $price_per_unit <= 0) {
                $errors['price_per_unit'] = 'Ціна повинна бути більше нуля';
            }
            
            // Якщо помилок немає, додаємо елемент
            if (empty($errors)) {
                if ($this->orderModel->addItem($order_id, $raw_material_id, $quantity, $price_per_unit)) {
                    $_SESSION['success'] = 'Елемент замовлення успішно додано';
                    Util::redirect(BASE_URL . '/warehouse/editOrder/' . $order_id);
                    return;
                } else {
                    $_SESSION['error'] = 'Помилка при додаванні елемента замовлення';
                }
            }
        }
        
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
        
        // Перевіряємо статус якості
        if ($order['quality_status'] !== 'approved') {
            $_SESSION['error'] = 'Неможливо прийняти замовлення без схвалення технолога';
            Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $id);
        }
        
        if ($this->orderModel->deliver($id, $this->inventoryModel)) {
            $_SESSION['success'] = 'Отримання замовлення успішно підтверджено';
        } else {
            $_SESSION['error'] = 'Помилка при підтвердженні отримання замовлення';
        }
        
        Util::redirect(BASE_URL . '/warehouse/viewOrder/' . $id);
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
}