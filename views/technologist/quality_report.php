<?php
// views/technologist/quality_report.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-chart-bar me-2"></i>Звіт по якості сировини</h1>
        <div>
            <a href="<?= BASE_URL ?>/technologist/generateQualityReportPdf?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
               target="_blank" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf me-1"></i>Експорт в PDF
            </a>
            <a href="<?= BASE_URL ?>/technologist" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i>Назад
            </a>
        </div>
    </div>

    <!-- Выбор периода -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Вибір періоду</h5>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>/technologist/qualityReport" method="get" class="row">
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

    <!-- Общая статистика -->
    <div class="row mb-4">
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Загальна статистика за період <?= date('d.m.Y', strtotime($start_date)) ?> - <?= date('d.m.Y', strtotime($end_date)) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <div class="h1"><?= $stats['total_checks'] ?: 0 ?></div>
                            <div class="text-muted">Всього перевірок</div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h1 text-success"><?= $stats['approved'] ?: 0 ?></div>
                            <div class="text-muted">Схвалено</div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h1 text-danger"><?= $stats['rejected'] ?: 0 ?></div>
                            <div class="text-muted">Відхилено</div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h1 text-primary"><?= round($stats['approval_rate'] ?: 0, 1) ?>%</div>
                            <div class="text-muted">Відсоток схвалення</div>
                        </div>
                    </div>
                    
                    <?php if ($stats['total_checks'] > 0): ?>
                        <div class="progress mt-3" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?= $stats['approval_rate'] ?>%"
                                 aria-valuenow="<?= $stats['approval_rate'] ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?= round($stats['approval_rate'], 1) ?>% схвалено
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar" 
                                 style="width: <?= $stats['rejection_rate'] ?>%"
                                 aria-valuenow="<?= $stats['rejection_rate'] ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?= round($stats['rejection_rate'], 1) ?>% відхилено
                            </div>
                        </div>
                    <?php endif; ?>
                    
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
                        <a href="<?= BASE_URL ?>/technologist/qualityReport?start_date=<?= date('Y-m-d', strtotime('-7 days')) ?>&end_date=<?= date('Y-m-d') ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-week me-2"></i>Останній тиждень
                        </a>
                        <a href="<?= BASE_URL ?>/technologist/qualityReport?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt me-2"></i>Поточний місяць
                        </a>
                        <a href="<?= BASE_URL ?>/technologist/qualityReport?start_date=<?= date('Y-m-01', strtotime('-1 month')) ?>&end_date=<?= date('Y-m-t', strtotime('-1 month')) ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt me-2"></i>Минулий місяць
                        </a>
                        <a href="<?= BASE_URL ?>/technologist/qualityReport?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-12-31') ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar me-2"></i>Поточний рік
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Рекомендації</h5>
                </div>
                <div class="card-body">
                    <?php if ($stats['approval_rate'] >= 90): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Відмінний показник якості! Рівень схвалення вище 90%.
                        </div>
                    <?php elseif ($stats['approval_rate'] >= 70): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Показники якості в нормі, але є можливості для покращення.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Низький рівень схвалення. Необхідно звернути увагу на якість постачань.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика по материалам и распределение оценок -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Статистика по матеріалах</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Матеріал</th>
                                    <th>Перевірок</th>
                                    <th>Схвалено</th>
                                    <th>Відхилено</th>
                                    <th>% схвалення</th>
                                    <th>Тренд</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($material_stats)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3">Немає даних по матеріалах</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($material_stats as $material): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($material['material_name']) ?></td>
                                            <td><?= $material['total_checks'] ?></td>
                                            <td class="text-success"><?= $material['approved'] ?></td>
                                            <td class="text-danger"><?= $material['rejected'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $material['approval_rate'] >= 90 ? 'success' :
                                                    ($material['approval_rate'] >= 70 ? 'warning' : 'danger')
                                                ?>">
                                                    <?= round($material['approval_rate'], 1) ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($material['approval_rate'] >= 90): ?>
                                                    <i class="fas fa-arrow-up text-success"></i>
                                                <?php elseif ($material['approval_rate'] >= 70): ?>
                                                    <i class="fas fa-minus text-warning"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-arrow-down text-danger"></i>
                                                <?php endif; ?>
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
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Розподіл оцінок</h5>
                </div>
                <div class="card-body">
                    <canvas id="gradeChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Детальная таблица всех проверок -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Детальна інформація по перевіркам</h5>
            <div class="input-group" style="width: 300px;">
                <input type="text" class="form-control" id="searchChecks" placeholder="Пошук...">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="checksTable">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Замовлення</th>
                            <th>Постачальник</th>
                            <th>Матеріали</th>
                            <th>Статус</th>
                            <th>Оцінка</th>
                            <th>Температура</th>
                            <th>pH</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Получаем детальные данные по проверкам за период
                        $qualityCheckModel = new QualityCheck();
                        $detailed_checks = $qualityCheckModel->getAll('', $start_date, $end_date);
                        ?>
                        
                        <?php if (empty($detailed_checks)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-3">Немає перевірок за вказаний період</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($detailed_checks as $check): ?>
                                <tr class="check-row" data-supplier="<?= htmlspecialchars(strtolower($check['supplier_name'])) ?>">
                                    <td><?= date('d.m.Y', strtotime($check['check_date'])) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/technologist/viewQualityCheck/<?= $check['id'] ?>" class="text-decoration-none">
                                            #<?= $check['order_number'] ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($check['supplier_name']) ?></td>
                                    <td>
                                        <?php
                                        $orderModel = new Order();
                                        $items = $orderModel->getItems($check['order_id']);
                                        $materials = array_slice(array_column($items, 'material_name'), 0, 2);
                                        echo htmlspecialchars(implode(', ', $materials));
                                        if (count($items) > 2) {
                                            echo ' та ще ' . (count($items) - 2);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $check['status'] === 'pending' ? 'warning' : 
                                            ($check['status'] === 'approved' ? 'success' : 'danger') 
                                        ?>">
                                            <?= Util::getQualityStatusName($check['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($check['overall_grade']): ?>
                                            <span class="badge bg-<?= 
                                                $check['overall_grade'] === 'excellent' ? 'success' :
                                                ($check['overall_grade'] === 'good' ? 'primary' :
                                                ($check['overall_grade'] === 'satisfactory' ? 'warning' : 'danger'))
                                            ?>">
                                                <?= Util::getOverallGradeName($check['overall_grade']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $check['temperature'] ? 
                                            Util::formatQualityParameter($check['temperature'], '°C') : 
                                            '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td>
                                        <?= $check['ph_level'] ? 
                                            Util::formatQualityParameter($check['ph_level']) : 
                                            '<span class="text-muted">-</span>' ?>
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
    const searchInput = document.getElementById('searchChecks');
    const checkRows = document.querySelectorAll('.check-row');
    
    function filterRows() {
        const searchTerm = searchInput.value.toLowerCase();
        
        checkRows.forEach(row => {
            const supplier = row.getAttribute('data-supplier');
            const text = row.textContent.toLowerCase();
            
            row.style.display = (supplier.includes(searchTerm) || text.includes(searchTerm)) ? '' : 'none';
        });
    }
    
    searchInput.addEventListener('input', filterRows);
    
    // Получаем данные для графиков
    const dailyData = <?= json_encode($daily_checks ?? []) ?>;
    const gradeData = <?= json_encode($grade_distribution ?? []) ?>;
    
    // График ежедневной статистики (если есть данные)
    if (dailyData && dailyData.length > 0) {
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('uk-UA');
                }),
                datasets: [{
                    label: 'Схвалено',
                    data: dailyData.map(item => item.approved || 0),
                    borderColor: 'rgba(46, 204, 113, 1)',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Відхилено',
                    data: dailyData.map(item => item.rejected || 0),
                    borderColor: 'rgba(231, 76, 60, 1)',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Щоденна статистика перевірок якості'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // График распределения оценок
    if (gradeData && gradeData.length > 0) {
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        const gradeChart = new Chart(gradeCtx, {
            type: 'doughnut',
            data: {
                labels: gradeData.map(item => {
                    const grades = {
                        'excellent': 'Відмінно',
                        'good': 'Добре',
                        'satisfactory': 'Задовільно',
                        'unsatisfactory': 'Незадовільно'
                    };
                    return grades[item.overall_grade] || item.overall_grade;
                }),
                datasets: [{
                    data: gradeData.map(item => item.count),
                    backgroundColor: [
                        '#2ecc71', // excellent - зеленый
                        '#3498db', // good - синий
                        '#f39c12', // satisfactory - оранжевый
                        '#e74c3c'  // unsatisfactory - красный
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
});
</script>