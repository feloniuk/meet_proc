<?php
class WarehouseController {
    private $inventoryModel;
    private $rawMaterialModel;
    private $productionModel;
    private $productModel;
    
    public function __construct() {
        // Перевірка на авторизацію та роль
        if (!Auth::isLoggedIn() || !Auth::hasRole('warehouse_manager')) {
            Util::redirect(BASE_URL . '/home');
        }
        
        $this->inventoryModel = new Inventory();
        $this->rawMaterialModel = new RawMaterial();
        $this->productionModel = new Production();
        $this->productModel = new Product();
    }
    
    // Управління інвентаризацією
    public function inventory() {
        $data = [
            'title' => 'Інвентаризація',
            'inventory' => $this->inventoryModel->getAll()
        ];
        
        require VIEWS_PATH . '/warehouse/inventory.php';
    }
    
    // Оновлення кількості сировини
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
            
            // Валідація
            if (!is_numeric($quantity) || $quantity < 0) {
                $errors['quantity'] = 'Кількість повинна бути невід\'ємним числом';
            }
            
            // Якщо помилок немає, оновлюємо кількість
            if (empty($errors)) {
                if ($this->inventoryModel->updateQuantity($material_id, $quantity, Auth::getCurrentUserId())) {
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
    
    // Генерація PDF звіту по запасам
    public function generateInventoryPdf() {
        $inventory = $this->inventoryModel->getStockReport();
        
        $pdf = new PDF('Звіт по запасам');
        $pdf->addTitle('Звіт по запасам на ' . date('d.m.Y'));
        
        // Підготовка даних для таблиці
        $header = ['Назва', 'Кількість', 'Одиниці', 'Мін. запас', 'Ціна за од.', 'Загальна вартість', 'Статус'];
        $data = [];
        
        foreach ($inventory as $item) {
            $status = '';
            switch ($item['status']) {
                case 'low':
                    $status = 'Критично';
                    break;
                case 'medium':
                    $status = 'Середньо';
                    break;
                case 'good':
                    $status = 'Достатньо';
                    break;
            }
            
            $data[] = [
                $item['name'],
                number_format($item['quantity'], 2),
                $item['unit'],
                number_format($item['min_stock'], 2),
                number_format($item['price_per_unit'], 2) . ' грн',
                number_format($item['total_value'], 2) . ' грн',
                $status
            ];
        }
        
        $pdf->addTable($header, $data);
        
        // Загальна вартість
        $total_value = array_sum(array_column($inventory, 'total_value'));
        $pdf->addText('Загальна вартість запасів: ' . number_format($total_value, 2) . ' грн');
        
        $pdf->addDateAndSignature();
        $pdf->output('inventory_report_' . date('Y-m-d') . '.pdf');
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
}