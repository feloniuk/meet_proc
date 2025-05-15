<?php
// views/admin/production_report.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?= BASE_URL ?>/admin/reports" class="btn btn-outline-primary me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="h3 mb-0"><i class="fas fa-industry me-2"></i>Звіт по виробництву</h1>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/generateProductionPdf?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Завантажити PDF
            </a>
        </div>
    </div>

    <!-- Вибір періоду -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="<?= BASE_URL ?>/admin/productionReport" method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Дата початку</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Дата кінця</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Застосувати
                    </button>
                    <button type="button" class="btn btn-outline-dark" onclick="window.print();">
                        <i class="fas fa-print me-1"></i>Друк
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Загальна статистика -->
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php
                        // Розрахунок загальної статистики
                        $totalProcesses = count($stats);
                        $totalQuantity = array_sum(array_column($stats, 'total_quantity'));
                        $totalValue = array_sum(array_column($products_stats, 'total_value'));
                        
                        // Середній час виробництва
                        $avgProductionTime = 0;
                        $processesCount = 0;
                        foreach ($stats as $stat) {
                            $avgProductionTime += $stat['avg_production_time'] * $stat['processes_count'];
                            $processesCount += $stat['processes_count'];
                        }
                        $avgProductionTime = $processesCount > 0 ? $avgProductionTime / $processesCount : 0;
                    ?>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="bg-light p-3 rounded text-center h-100">
                                <h6 class="text-muted">Загальна кількість</h6>
                                <h3 class="mb-0"><?= number_format($totalQuantity, 2) ?> <small class="text-muted">кг</small></h3>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded text-center h-100">
                                <h6 class="text-muted">Кількість виробничих циклів</h6>
                                <h3 class="mb-0"><?= $processesCount ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded text-center h-100">
                                <h6 class="text-muted">Загальна вартість</h6>
                                <h3 class="mb-0"><?= Util::formatMoney($totalValue) ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded text-center h-100">
                                <h6 class="text-muted">Середній час виробництва</h6>
                                <h3 class="mb-0"><?= number_format($avgProductionTime, 1) ?> <small class="text-muted">год</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Графік виробництва за період -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Динаміка виробництва за період</h5>
                </div>
                <div class="card-body">
                    <canvas id="productionChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Розподіл виробництва за продуктами -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Розподіл за продуктами</h5>
                </div>
                <div class="card-body">
                    <canvas id="productsChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Таблиця деталізації за продуктами -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Деталізація за продуктами</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Продукт</th>
                                    <th>Кількість (кг)</th>
                                    <th>Циклів виробництва</th>
                                    <th>Середній час виробництва (год)</th>
                                    <th>Ціна за кг</th>
                                    <th>Загальна вартість</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stats)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3">Немає даних про виробництво за вказаний період</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($stats as $stat): ?>
                                        <?php
                                            // Знаходимо відповідні дані про продукт
                                            $productStat = null;
                                            foreach ($products_stats as $ps) {
                                                if ($ps['name'] === $stat['product_name']) {
                                                    $productStat = $ps;
                                                    break;
                                                }
                                            }
                                            $pricePerKg = $productStat ? $productStat['price'] : 0;
                                            $totalValue = $productStat ? $productStat['total_value'] : 0;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($stat['product_name']) ?></td>
                                            <td><?= number_format($stat['total_quantity'], 2) ?></td>
                                            <td><?= $stat['processes_count'] ?></td>
                                            <td><?= number_format($stat['avg_production_time'], 1) ?></td>
                                            <td><?= Util::formatMoney($pricePerKg) ?></td>
                                            <td><?= Util::formatMoney($totalValue) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Підключення Chart.js для графіків -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Дані для графіку динаміки виробництва
    <?php
        // Підготовка даних для графіку
        $dates = array_map(function($item) {
            return date('d.m', strtotime($item['date']));
        }, $daily_stats);
        
        $quantities = array_map(function($item) {
            return $item['total_quantity'];
        }, $daily_stats);
    ?>
    
    const productionChart = new Chart(document.getElementById('productionChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Виробництво (кг)',
                data: <?= json_encode($quantities) ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('uk-UA') + ' кг';
                        }
                    }
                }
            }
        }
    });
    
    // Дані для графіку розподілу за продуктами
    <?php
        // Підготовка даних про продукти
        $productNames = array_map(function($item) {
            return $item['product_name'];
        }, $stats);
        
        $productQuantities = array_map(function($item) {
            return $item['total_quantity'];
        }, $stats);
        
        // Генерація кольорів для графіку
        $backgroundColors = [];
        for ($i = 0; $i < count($stats); $i++) {
            $hue = ($i * 137) % 360; // Золотий кут для рівномірного розподілу кольорів
            $backgroundColors[] = "hsla($hue, 70%, 60%, 0.7)";
        }
    ?>
    
    const productsChart = new Chart(document.getElementById('productsChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($productNames) ?>,
            datasets: [{
                data: <?= json_encode($productQuantities) ?>,
                backgroundColor: <?= json_encode($backgroundColors) ?>,
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
});
</script>
