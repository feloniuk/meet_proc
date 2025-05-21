<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Инициализируем переменные, если они не установлены
$inventory = $inventory ?? [];

// Расчет итогов
$totalValue = 0;
$lowStockCount = 0;
$mediumStockCount = 0;
$goodStockCount = 0;

foreach ($inventory as $item) {
    $totalValue += $item['total_value'];
    
    if ($item['status'] === 'low') {
        $lowStockCount++;
    } elseif ($item['status'] === 'medium') {
        $mediumStockCount++;
    } else {
        $goodStockCount++;
    }
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-boxes me-2"></i>Звіт по запасам</h1>
        <div>
            <a href="<?= BASE_URL ?>/warehouse/generateInventoryPdf<?= isset($_GET['filter']) ? '?filter=' . htmlspecialchars($_GET['filter']) : '' ?>" 
               target="_blank" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf me-1"></i>Експорт в PDF
            </a>
            <a href="<?= BASE_URL ?>/warehouse/reports" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i>Назад до звітів
            </a>
        </div>
    </div>
<div class="row mb-4">
    <div class="col-md-9">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Статистика запасів на <?= date('d.m.Y') ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        <div class="h1"><?= count($inventory) ?></div>
                        <div class="text-muted">Найменувань</div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="h1"><?= Util::formatMoney($totalValue) ?></div>
                        <div class="text-muted">Загальна вартість</div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="h1 text-danger"><?= $lowStockCount ?></div>
                        <div class="text-muted">Критичний запас</div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="h1 text-warning"><?= $mediumStockCount ?></div>
                        <div class="text-muted">Середній запас</div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <canvas id="statusChart" width="400" height="300"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="valueChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Фільтри</h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/warehouse/inventoryReport" method="get">
                    <div class="form-group mb-3">
                        <label for="filter">Фільтр за статусом:</label>
                        <select class="form-control" id="filter" name="filter" onchange="this.form.submit()">
                            <option value="">Всі запаси</option>
                            <option value="low" <?= isset($_GET['filter']) && $_GET['filter'] === 'low' ? 'selected' : '' ?>>Критичний запас</option>
                            <option value="medium" <?= isset($_GET['filter']) && $_GET['filter'] === 'medium' ? 'selected' : '' ?>>Середній запас</option>
                            <option value="good" <?= isset($_GET['filter']) && $_GET['filter'] === 'good' ? 'selected' : '' ?>>Достатній запас</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="sort">Сортування:</label>
                        <select class="form-control" id="sort" name="sort" onchange="this.form.submit()">
                            <option value="name" <?= isset($_GET['sort']) && $_GET['sort'] === 'name' ? 'selected' : '' ?>>За назвою</option>
                            <option value="quantity" <?= isset($_GET['sort']) && $_GET['sort'] === 'quantity' ? 'selected' : '' ?>>За кількістю</option>
                            <option value="value" <?= isset($_GET['sort']) && $_GET['sort'] === 'value' ? 'selected' : '' ?>>За вартістю</option>
                            <option value="status" <?= isset($_GET['sort']) && $_GET['sort'] === 'status' ? 'selected' : '' ?>>За статусом</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Застосувати
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Рекомендації</h5>
            </div>
            <div class="card-body">
                <?php if ($lowStockCount > 0): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong><?= $lowStockCount ?></strong> позицій мають критичний запас. Рекомендується замовити додаткову сировину.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Всі позиції мають достатній запас.
                    </div>
                <?php endif; ?>
                
                <?php if ($mediumStockCount > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong><?= $mediumStockCount ?></strong> позицій мають середній запас. Рекомендується планувати закупівлю.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Деталі запасів</h5>
        <div class="input-group" style="width: 300px;">
            <input type="text" class="form-control" id="searchInventory" placeholder="Пошук...">
            <button class="btn btn-outline-secondary" type="button" id="searchButton">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="inventoryTable">
                <thead>
                    <tr>
                        <th>Назва</th>
                        <th>Кількість</th>
                        <th>Одиниця виміру</th>
                        <th>Мін. запас</th>
                        <th>Ціна за од.</th>
                        <th>Загальна вартість</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-3">Немає даних про запаси</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <tr class="inventory-row" data-name="<?= htmlspecialchars(strtolower($item['name'])) ?>">
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= number_format($item['quantity'], 2) ?></td>
                                <td><?= htmlspecialchars($item['unit']) ?></td>
                                <td><?= number_format($item['min_stock'], 2) ?></td>
                                <td><?= Util::formatMoney($item['price_per_unit']) ?></td>
                                <td><?= Util::formatMoney($item['total_value']) ?></td>
                                <td>
                                    <?php 
                                        if ($item['status'] === 'low') {
                                            echo '<span class="badge bg-danger">Критично</span>';
                                        } elseif ($item['status'] === 'medium') {
                                            echo '<span class="badge bg-warning">Середньо</span>';
                                        } else {
                                            echo '<span class="badge bg-success">Достатньо</span>';
                                        }
                                    ?>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Поиск по таблице
        const searchInput = document.getElementById('searchInventory');
        const inventoryRows = document.querySelectorAll('.inventory-row');
        
        // Функция фильтрации
        function filterRows() {
            const searchTerm = searchInput.value.toLowerCase();
            
            inventoryRows.forEach(row => {
                const name = row.getAttribute('data-name');
                
                // Показываем или скрываем строку
                row.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        }
        
        // Привязка событий
        searchInput.addEventListener('input', filterRows);
        document.getElementById('searchButton').addEventListener('click', filterRows);
        
        // Графики
        // Статусы запасов
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Критичний запас', 'Середній запас', 'Достатній запас'],
                datasets: [{
                    data: [<?= $lowStockCount ?>, <?= $mediumStockCount ?>, <?= $goodStockCount ?>],
                    backgroundColor: [
                        '#e74c3c',
                        '#f39c12',
                        '#2ecc71'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Розподіл запасів за статусом'
                    }
                }
            }
        });
        
        // Топ позиций по стоимости
        const valueData = <?= json_encode(array_map(function($item) {
            return [
                'name' => $item['name'],
                'value' => $item['total_value']
            ];
        }, array_slice(array_filter($inventory, function($a) {
            return $a['total_value'] > 0;
        }), 0, 5))) ?>;
        
        valueData.sort((a, b) => b.value - a.value);
        
        const valueCtx = document.getElementById('valueChart').getContext('2d');
        const valueChart = new Chart(valueCtx, {
            type: 'bar',
            data: {
                labels: valueData.map(item => item.name),
                datasets: [{
                    label: 'Вартість',
                    data: valueData.map(item => item.value),
                    backgroundColor: '#3498db',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Топ 5 позицій за вартістю'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(2) + ' грн';
                            }
                        }
                    }
                }
            }
        });
    });
</script>