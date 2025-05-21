<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Инициализируем переменные, если они не установлены
$stats = $stats ?? [];
$daily_stats = $daily_stats ?? [];
$start_date = $start_date ?? date('Y-m-01');
$end_date = $end_date ?? date('Y-m-t');

// Расчет итогов
$totalQuantity = 0;
$totalProcesses = 0;
$avgProductionTime = 0;

foreach ($stats as $item) {
    $totalQuantity += $item['total_quantity'];
    $totalProcesses += $item['processes_count'];
}

if (count($stats) > 0) {
    $avgProductionTime = array_sum(array_column($stats, 'avg_production_time')) / count($stats);
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-industry me-2"></i>Звіт по виробництву</h1>
        <div>
            <a href="<?= BASE_URL ?>/warehouse/generateProductionPdf?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
               target="_blank" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf me-1"></i>Експорт в PDF
            </a>
            <a href="<?= BASE_URL ?>/warehouse/reports" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i>Назад до звітів
            </a>
        </div>
    </div>
<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0">Вибір періоду</h5>
    </div>
    <div class="card-body">
        <form action="<?= BASE_URL ?>/warehouse/productionReport" method="get" class="row">
            <div class="col-md-4 mb-3">
                <label for="start_date">Початкова дата:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="end_date">Кінцева дата:</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Застосувати
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-9">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Статистика виробництва за період <?= date('d.m.Y', strtotime($start_date)) ?> - <?= date('d.m.Y', strtotime($end_date)) ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <div class="h1"><?= number_format($totalQuantity, 2) ?> кг</div>
                        <div class="text-muted">Всього виготовлено</div>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="h1"><?= $totalProcesses ?></div>
                        <div class="text-muted">Виробничих процесів</div>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="h1"><?= round($avgProductionTime, 1) ?> год</div>
                        <div class="text-muted">Середній час виробництва</div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <canvas id="dailyChart" width="800" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Швидкі періоди</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="<?= BASE_URL ?>/warehouse/productionReport?start_date=<?= date('Y-m-d', strtotime('-7 days')) ?>&end_date=<?= date('Y-m-d') ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-week me-2"></i>Останній тиждень
                    </a>
                    <a href="<?= BASE_URL ?>/warehouse/productionReport?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2"></i>Поточний місяць
                    </a>
                    <a href="<?= BASE_URL ?>/warehouse/productionReport?start_date=<?= date('Y-m-01', strtotime('-1 month')) ?>&end_date=<?= date('Y-m-t', strtotime('-1 month')) ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2"></i>Минулий місяць
                    </a>
                    <a href="<?= BASE_URL ?>/warehouse/productionReport?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-12-31') ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar me-2"></i>Поточний рік
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Експорт даних</h5>
            </div>
            <div class="card-body">
                <a href="<?= BASE_URL ?>/warehouse/generateProductionPdf?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" target="_blank" class="btn btn-danger w-100 mb-2">
                    <i class="fas fa-file-pdf me-1"></i>Експорт в PDF
                </a>
                <a href="<?= BASE_URL ?>/warehouse/exportProductionCsv?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" target="_blank" class="btn btn-success w-100">
                    <i class="fas fa-file-csv me-1"></i>Експорт в CSV
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Статистика по продуктах</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Продукт</th>
                                <th>Кількість</th>
                                <th>Процесів</th>
                                <th>Сер. час</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stats)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">Немає даних про виробництво</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stats as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= number_format($item['total_quantity'], 2) ?> кг</td>
                                        <td><?= $item['processes_count'] ?></td>
                                        <td><?= round($item['avg_production_time'], 1) ?> год</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Розподіл виробництва</h5>
            </div>
            <div class="card-body">
                <canvas id="productChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Щоденна статистика</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Кількість виробленої продукції</th>
                        <th>Кількість виробничих процесів</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_stats)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-3">Немає даних про щоденне виробництво</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daily_stats as $item): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($item['date'])) ?></td>
                                <td><?= number_format($item['total_quantity'], 2) ?> кг</td>
                                <td><?= $item['processes_count'] ?></td>
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
        // Данные для графиков
        const dailyData = <?= json_encode(array_map(function($item) {
            return [
                'date' => date('d.m.Y', strtotime($item['date'])),
                'quantity' => $item['total_quantity'],
                'processes' => $item['processes_count']
            ];
        }, $daily_stats)) ?>;
        
        const productData = <?= json_encode(array_map(function($item) {
            return [
                'name' => $item['product_name'],
                'quantity' => $item['total_quantity']
            ];
        }, $stats)) ?>;
        
        // График дневной статистики
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: dailyData.map(item => item.date),
                datasets: [{
                    label: 'Кількість продукції (кг)',
                    data: dailyData.map(item => item.quantity),
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Кількість процесів',
                    data: dailyData.map(item => item.processes),
                    type: 'line',
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Щоденна статистика виробництва'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Кількість продукції (кг)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Кількість процесів'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        
        // График распределения по продуктам
        const productCtx = document.getElementById('productChart').getContext('2d');
        const productChart = new Chart(productCtx, {
            type: 'pie',
            data: {
                labels: productData.map(item => item.name),
                datasets: [{
                    data: productData.map(item => item.quantity),
                    backgroundColor: [
                        '#3498db',
                        '#2ecc71',
                        '#f1c40f',
                        '#e74c3c',
                        '#9b59b6',
                        '#1abc9c',
                        '#34495e',
                        '#e67e22'
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
                        text: 'Розподіл виробництва за продуктами (кг)'
                    }
                }
            }
        });
    });
</script>