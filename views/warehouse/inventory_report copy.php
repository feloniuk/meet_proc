<?php
// views/admin/inventory_report.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?= BASE_URL ?>/admin/reports" class="btn btn-outline-primary me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="h3 mb-0"><i class="fas fa-boxes me-2"></i>Звіт по запасам</h1>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/generateInventoryPdf" class="btn btn-primary" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Завантажити PDF
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Загальна статистика -->
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php
                        // Розрахунок загальної вартості запасів та статистики
                        $totalValue = array_sum(array_column($inventory, 'total_value'));
                        $lowStockCount = count(array_filter($inventory, function($item) { return $item['status'] === 'low'; }));
                        $mediumStockCount = count(array_filter($inventory, function($item) { return $item['status'] === 'medium'; }));
                        $goodStockCount = count(array_filter($inventory, function($item) { return $item['status'] === 'good'; }));
                    ?>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="bg-light p-3 rounded text-center h-100">
                                <h6 class="text-muted">Загальна вартість запасів</h6>
                                <h3 class="mb-0"><?= Util::formatMoney($totalValue) ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded text-center h-100">
                                <h6 class="text-muted">Критичний запас</h6>
                                <h3 class="mb-0"><?= $lowStockCount ?> <small class="text-muted">позицій</small></h3>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded text-center h-100">
                                <h6 class="text-muted">Середній запас</h6>
                                <h3 class="mb-0"><?= $mediumStockCount ?> <small class="text-muted">позицій</small></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded text-center h-100">
                                <h6 class="text-muted">Достатній запас</h6>
                                <h3 class="mb-0"><?= $goodStockCount ?> <small class="text-muted">позицій</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фільтри для таблиці -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Фільтри</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <input type="text" id="materialFilter" class="form-control" placeholder="Пошук за назвою...">
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <select id="statusFilter" class="form-select">
                        <option value="">Всі статуси</option>
                        <option value="low">Критичний запас</option>
                        <option value="medium">Середній запас</option>
                        <option value="good">Достатній запас</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <button id="applyFilter" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Фільтрувати
                    </button>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <button id="resetFilter" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-undo me-1"></i>Скинути
                    </button>
                </div>
                <div class="col-md-1">
                    <button id="printReport" class="btn btn-outline-dark w-100" onclick="window.print();">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблиця запасів -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Назва</th>
                            <th>Кількість</th>
                            <th>Мін. запас</th>
                            <th>Одиниця</th>
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
                                <tr class="status-<?= $item['status'] ?>-row" data-status="<?= $item['status'] ?>">
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= number_format($item['quantity'], 2) ?></td>
                                    <td><?= number_format($item['min_stock'], 2) ?></td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td><?= Util::formatMoney($item['price_per_unit']) ?></td>
                                    <td><?= Util::formatMoney($item['total_value']) ?></td>
                                    <td>
                                        <span class="badge stock-<?= $item['status'] ?> bg-<?= 
                                            $item['status'] === 'low' ? 'danger' : ($item['status'] === 'medium' ? 'warning' : 'success') 
                                        ?>">
                                            <?= $item['status'] === 'low' ? 'Критичний' : ($item['status'] === 'medium' ? 'Середній' : 'Достатній') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Графік розподілу запасів по категоріях -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Розподіл запасів за статусом</h5>
                </div>
                <div class="card-body">
                    <canvas id="stockStatusChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Топ-5 найцінніших позицій</h5>
                </div>
                <div class="card-body">
                    <canvas id="topMaterialsChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Підключення Chart.js для графіків -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Фільтрація таблиці
    const materialFilter = document.getElementById('materialFilter');
    const statusFilter = document.getElementById('statusFilter');
    const applyFilterBtn = document.getElementById('applyFilter');
    const resetFilterBtn = document.getElementById('resetFilter');
    const inventoryTable = document.getElementById('inventoryTable');
    const rows = inventoryTable.querySelectorAll('tbody tr');
    
    function applyFilters() {
        const materialText = materialFilter.value.toLowerCase();
        const statusValue = statusFilter.value;
        
        rows.forEach(row => {
            const material = row.cells[0].textContent.toLowerCase();
            const status = row.getAttribute('data-status');
            
            const matchesMaterial = material.includes(materialText);
            const matchesStatus = statusValue === '' || status === statusValue;
            
            if (matchesMaterial && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    function resetFilters() {
        materialFilter.value = '';
        statusFilter.value = '';
        rows.forEach(row => {
            row.style.display = '';
        });
    }
    
    applyFilterBtn.addEventListener('click', applyFilters);
    resetFilterBtn.addEventListener('click', resetFilters);
    
    // Графік статусів запасів
    const statusCounts = {
        low: <?= $lowStockCount ?>,
        medium: <?= $mediumStockCount ?>,
        good: <?= $goodStockCount ?>
    };
    
    const statusChart = new Chart(document.getElementById('stockStatusChart'), {
        type: 'pie',
        data: {
            labels: ['Критичний запас', 'Середній запас', 'Достатній запас'],
            datasets: [{
                data: [statusCounts.low, statusCounts.medium, statusCounts.good],
                backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Графік топ-5 найцінніших матеріалів
    <?php
        // Сортування інвентарю за вартістю
        usort($inventory, function($a, $b) {
            return $b['total_value'] <=> $a['total_value'];
        });
        
        // Вибір топ-5 найцінніших позицій
        $topMaterials = array_slice($inventory, 0, 5);
        
        $topMaterialNames = array_map(function($item) {
            return $item['name'];
        }, $topMaterials);
        
        $topMaterialValues = array_map(function($item) {
            return $item['total_value'];
        }, $topMaterials);
    ?>
    
    const topMaterialsChart = new Chart(document.getElementById('topMaterialsChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($topMaterialNames) ?>,
            datasets: [{
                label: 'Вартість запасів (грн)',
                data: <?= json_encode($topMaterialValues) ?>,
                backgroundColor: '#3498db',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('uk-UA') + ' грн';
                        }
                    }
                }
            }
        }
    });
});
</script>
