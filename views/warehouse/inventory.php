<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Инициализируем переменные, если они не установлены
$inventory = $inventory ?? [];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-boxes me-2"></i>Інвентаризація складу</h1>
        <div>
            <a href="<?= BASE_URL ?>/warehouse/inventoryReport" class="btn btn-outline-primary me-2">
                <i class="fas fa-chart-bar me-1"></i>Звіт по запасам
            </a>
            <a href="<?= BASE_URL ?>/warehouse/generateInventoryPdf" target="_blank" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf me-1"></i>Експорт в PDF
            </a>
        </div>
    </div>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Статистика по запасам</h5>
                <?php
                    // Подсчитываем статистику
                    $totalItems = count($inventory);
                    $totalValue = 0;
                    $lowStock = 0;
                    
                    foreach ($inventory as $item) {
                        // Проверяем наличие нужных индексов перед вычислением
                        $price_per_unit = isset($item['price_per_unit']) ? $item['price_per_unit'] : 0;
                        $quantity = isset($item['quantity']) ? $item['quantity'] : 0;
                        $min_stock = isset($item['min_stock']) ? $item['min_stock'] : 0;
                        
                        $totalValue += $quantity * $price_per_unit;
                        if ($quantity < $min_stock) {
                            $lowStock++;
                        }
                    }
                ?>
                <div class="row mt-3">
                    <div class="col-md-4 text-center">
                        <h2><?= $totalItems ?></h2>
                        <p class="text-muted">Найменувань</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <h2><?= Util::formatMoney($totalValue) ?></h2>
                        <p class="text-muted">Загальна вартість</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <h2 class="<?= $lowStock > 0 ? 'text-danger' : 'text-success' ?>"><?= $lowStock ?></h2>
                        <p class="text-muted">Критичний запас</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Пошук</h5>
                <div class="input-group mt-3">
                    <input type="text" class="form-control" id="searchInventory" placeholder="Введіть назву сировини...">
                    <button class="btn btn-outline-secondary" type="button" id="searchButton">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="showLowStock">
                    <label class="form-check-label" for="showLowStock">
                        Показати тільки позиції з критичним запасом
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="inventoryTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Назва</th>
                        <th>Кількість</th>
                        <th>Одиниця виміру</th>
                        <th>Мін. запас</th>
                        <th>Ціна за од.</th>
                        <th>Загальна вартість</th>
                        <th>Статус</th>
                        <th>Останнє оновлення</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-3">Немає даних про запаси</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <?php
                                // Получаем значения с проверкой на существование ключей
                                $raw_material_id = isset($item['raw_material_id']) ? $item['raw_material_id'] : 0;
                                $material_name = isset($item['material_name']) ? $item['material_name'] : '';
                                $quantity = isset($item['quantity']) ? $item['quantity'] : 0;
                                $unit = isset($item['unit']) ? $item['unit'] : '';
                                $min_stock = isset($item['min_stock']) ? $item['min_stock'] : 0;
                                $price_per_unit = isset($item['price_per_unit']) ? $item['price_per_unit'] : 0;
                                $last_updated = isset($item['last_updated']) ? $item['last_updated'] : date('Y-m-d H:i:s');
                                
                                // Определяем статус запаса
                                if ($quantity < $min_stock) {
                                    $statusClass = 'stock-low';
                                    $statusText = 'Критично';
                                } elseif ($quantity < $min_stock * 2) {
                                    $statusClass = 'stock-medium';
                                    $statusText = 'Середньо';
                                } else {
                                    $statusClass = 'stock-good';
                                    $statusText = 'Достатньо';
                                }
                            ?>
                            <tr class="inventory-row <?= $quantity < $min_stock ? 'table-danger' : '' ?>" 
                                data-name="<?= htmlspecialchars(strtolower($material_name)) ?>" 
                                data-low="<?= $quantity < $min_stock ? '1' : '0' ?>">
                                <td><?= $raw_material_id ?></td>
                                <td><?= htmlspecialchars($material_name) ?></td>
                                <td><?= number_format($quantity, 2) ?></td>
                                <td><?= htmlspecialchars($unit) ?></td>
                                <td><?= number_format($min_stock, 2) ?></td>
                                <td><?= Util::formatMoney($price_per_unit) ?></td>
                                <td><?= Util::formatMoney($quantity * $price_per_unit) ?></td>
                                <td><span class="<?= $statusClass ?>"><?= $statusText ?></span></td>
                                <td><?= Util::formatDate($last_updated) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/warehouse/updateQuantity/<?= $raw_material_id ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Оновити
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInventory');
        const lowStockCheckbox = document.getElementById('showLowStock');
        const inventoryRows = document.querySelectorAll('.inventory-row');
        
        // Функция фильтрации
        function filterRows() {
            const searchTerm = searchInput.value.toLowerCase();
            const lowStockOnly = lowStockCheckbox.checked;
            
            inventoryRows.forEach(row => {
                const name = row.getAttribute('data-name');
                const isLow = row.getAttribute('data-low') === '1';
                
                // Проверяем условия фильтрации
                const matchesSearch = name.includes(searchTerm);
                const matchesLowStock = !lowStockOnly || isLow;
                
                // Показываем или скрываем строку
                row.style.display = matchesSearch && matchesLowStock ? '' : 'none';
            });
        }
        
        // Привязка событий
        searchInput.addEventListener('input', filterRows);
        lowStockCheckbox.addEventListener('change', filterRows);
        document.getElementById('searchButton').addEventListener('click', filterRows);
    });
</script>