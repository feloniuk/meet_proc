<?php
// Проверка и инициализация переменных, если они не определены
if (!isset($start_date)) $start_date = date('Y-m-01');
if (!isset($end_date)) $end_date = date('Y-m-t');
if (!isset($daily_stats)) $daily_stats = [];
if (!isset($supplier_stats)) $supplier_stats = [];
if (!isset($material_stats)) $material_stats = [];

// Расчет итоговых сумм
$total_orders = isset($supplier_stats) ? array_sum(array_column($supplier_stats, 'orders_count')) : 0;
$total_amount = isset($supplier_stats) ? array_sum(array_column($supplier_stats, 'total_amount')) : 0;

// Синхронизируем имена переменных для совместимости
$totalAmount = $total_amount;
?>
<?php
// Проверка и инициализация переменных, если они не определены
if (!isset($start_date)) $start_date = date('Y-m-01');
if (!isset($end_date)) $end_date = date('Y-m-t');
if (!isset($daily_stats)) $daily_stats = [];
if (!isset($supplier_stats)) $supplier_stats = [];
if (!isset($material_stats)) $material_stats = [];
?>
<?php
// views/admin/orders_report.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?= BASE_URL ?>/admin/reports" class="btn btn-outline-primary me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="h3 mb-0"><i class="fas fa-shopping-cart me-2"></i>Звіт по замовленнях</h1>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/generateOrdersPdf?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Завантажити PDF
            </a>
        </div>
    </div>

    <!-- Вибір періоду -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="<?= BASE_URL ?>/admin/ordersReport" method="get" class="row g-3">
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
                        $total_orders = isset($supplier_stats) ? array_sum(array_column($supplier_stats, 'orders_count')) : 0;
                        $total_amount = isset($supplier_stats) ? array_sum(array_column($supplier_stats, 'total_amount')) : 0;
                        
                        // Статистика по статусам
                        $orderStatusCounts = [
                            'pending' => 0,
                            'accepted' => 0,
                            'shipped' => 0,
                            'delivered' => 0,
                            'canceled' => 0
                        ];
                        
                        // Отримуємо всі замовлення за період
                        $sql = "SELECT status, COUNT(*) as count 
                                FROM orders 
                                WHERE created_at BETWEEN ? AND ? 
                                GROUP BY status";
                        $db = Database::getInstance();
                        $statusStats = $db->resultSet($sql, [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                        
                        foreach ($statusStats as $stat) {
                            $orderStatusCounts[$stat['status']] = $stat['count'];
                        }
                    ?>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="bg-light p-3 rounded text-center h-100">
                                <h6 class="text-muted">Загальна сума замовлень</h6>
                                <h3 class="mb-0"><?= Util::formatMoney($totalAmount) ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded text-center h-100">
                                <h6 class="text-muted">Кількість замовлень</h6>
                                <h3 class="mb-0"><?= $ordersCount ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded text-center h-100">
                                <h6 class="text-muted">Доставлено</h6>
                                <h3 class="mb-0"><?= $orderStatusCounts['delivered'] ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="bg-danger bg-opacity-10 p-3 rounded text-center h-100">
                                <h6 class="text-muted">Скасовано</h6>
                                <h3 class="mb-0"><?= $orderStatusCounts['canceled'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Графік замовлень за період -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Динаміка замовлень за період</h5>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Розподіл замовлень за постачальниками -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Розподіл за постачальниками</h5>
                </div>
                <div class="card-body">
                    <canvas id="suppliersChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Таблиця статистики за постачальниками -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Статистика за постачальниками</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Постачальник</th>
                                    <th>Кількість замовлень</th>
                                    <th>Загальна сума</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($supplier_stats)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3">Немає даних за вказаний період</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($supplier_stats as $stat): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($stat['supplier_name']) ?></td>
                                            <td><?= $stat['orders_count'] ?></td>
                                            <td><?= Util::formatMoney($stat['total_amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Таблиця статистики за матеріалами -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Топ матеріалів за сумою замовлень</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Матеріал</th>
                                    <th>Кількість</th>
                                    <th>Одиниця</th>
                                    <th>Загальна сума</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($material_stats)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">Немає даних за вказаний період</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($material_stats as $stat): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($stat['material_name']) ?></td>
                                            <td><?= number_format($stat['total_quantity'], 2) ?></td>
                                            <td><?= htmlspecialchars($stat['unit']) ?></td>
                                            <td><?= isset($totalAmount) ? number_format($totalAmount, 2) : number_format($total_amount, 2) ?> грн</td>
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
    // Дані для графіку динаміки замовлень
    <?php
        // Підготовка даних для графіку
        $dates = array_map(function($item) {
            return date('d.m', strtotime($item['date']));
        }, $daily_stats);
        
        $ordersData = array_map(function($item) {
            return $item['orders_count'];
        }, $daily_stats);
        
        $amountsData = array_map(function($item) {
            return $item['total_amount'];
        }, $daily_stats);
    ?>
    
    const ordersChart = new Chart(document.getElementById('ordersChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [
                {
                    label: 'Кількість замовлень',
                    data: <?= json_encode($ordersData) ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    yAxisID: 'y',
                    fill: true
                },
                {
                    label: 'Сума замовлень (грн)',
                    data: <?= json_encode($amountsData) ?>,
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    yAxisID: 'y1',
                    fill: true
                }
            ]
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
                    type: 'linear',
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Кількість'
                    }
                },
                y1: {
                    beginAtZero: true,
                    type: 'linear',
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Сума (грн)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
    
    // Дані для графіку розподілу за постачальниками
    <?php
        // Підготовка даних про постачальників
        $supplierNames = array_map(function($item) {
            return $item['supplier_name'];
        }, $supplier_stats);
        
        $supplierAmounts = array_map(function($item) {
            return $item['total_amount'];
        }, $supplier_stats);
        
        // Генерація кольорів для графіку
        $backgroundColors = [];
        for ($i = 0; $i < count($supplier_stats); $i++) {
            $hue = ($i * 137) % 360; // Золотий кут для рівномірного розподілу кольорів
            $backgroundColors[] = "hsla($hue, 70%, 60%, 0.7)";
        }
    ?>
    
    const suppliersChart = new Chart(document.getElementById('suppliersChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($supplierNames) ?>,
            datasets: [{
                data: <?= json_encode($supplierAmounts) ?>,
                backgroundColor: <?= json_encode($backgroundColors) ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += new Intl.NumberFormat('uk-UA', { style: 'currency', currency: 'UAH' }).format(context.raw);
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>
