<?php
// Инициализируем переменные если они не переданы
$dashboard_stats = $dashboard_stats ?? [];
$pending_checks = $pending_checks ?? [];
$recent_checks = $recent_checks ?? [];
$monthly_stats = $monthly_stats ?? [];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="fas fa-microscope me-2"></i>Панель технолога
        </h1>
        <div class="text-muted">
            <?= Util::formatDate(date('Y-m-d H:i:s'), 'd.m.Y H:i') ?>
        </div>
    </div>

    <!-- Приветствие -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-user-circle fa-3x"></i>
                    </div>
                    <div>
                        <h5 class="mb-1">Вітаємо, <?= Auth::getCurrentUserName() ?>!</h5>
                        <p class="mb-0">
                            Контроль якості сировини та забезпечення стандартів виробництва
                        </p>
                        <small class="text-muted">
                            Сьогодні <?= Util::formatDate(date('Y-m-d'), 'd.m.Y') ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистические карточки -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h4 class="text-warning"><?= $dashboard_stats['pending_checks'] ?? 0 ?></h4>
                    <h6 class="card-title">Очікують перевірки</h6>
                    <a href="<?= BASE_URL ?>/technologist/qualityChecks?status=pending" class="btn btn-warning btn-sm">
                        Переглянути
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h4 class="text-success"><?= $dashboard_stats['approved_checks'] ?? 0 ?></h4>
                    <h6 class="card-title">Схвалено</h6>
                    <small class="text-muted">Всього перевірок</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                    <h4 class="text-danger"><?= $dashboard_stats['rejected_checks'] ?? 0 ?></h4>
                    <h6 class="card-title">Відхилено</h6>
                    <small class="text-muted">Всього перевірок</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-calendar-check fa-2x text-info mb-2"></i>
                    <h4 class="text-info"><?= $dashboard_stats['today_checks'] ?? 0 ?></h4>
                    <h6 class="card-title">Сьогодні</h6>
                    <small class="text-muted">Перевірок проведено</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Быстрые действия -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-bolt me-2"></i>Швидкі дії
            </h4>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-clipboard-check fa-2x text-primary mb-2"></i>
                    <h6 class="card-title">Перевірки якості</h6>
                    <p class="card-text small">Перегляд та проведення перевірок</p>
                    <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-primary btn-sm">Перейти</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-cog fa-2x text-success mb-2"></i>
                    <h6 class="card-title">Стандарти якості</h6>
                    <p class="card-text small">Управління стандартами</p>
                    <a href="<?= BASE_URL ?>/technologist/standards" class="btn btn-success btn-sm">Перейти</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-chart-area fa-2x text-info mb-2"></i>
                    <h6 class="card-title">Звіти якості</h6>
                    <p class="card-text small">Аналіз та статистика</p>
                    <a href="<?= BASE_URL ?>/technologist/reports" class="btn btn-info btn-sm">Перейти</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-plus fa-2x text-warning mb-2"></i>
                    <h6 class="card-title">Новий стандарт</h6>
                    <p class="card-text small">Додати стандарт якості</p>
                    <a href="<?= BASE_URL ?>/technologist/addStandard" class="btn btn-warning btn-sm">Додати</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Ожидающие проверки -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-hourglass-half me-2"></i>Очікують перевірки
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_checks)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">Всі перевірки виконано!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Замовлення</th>
                                        <th>Постачальник</th>
                                        <th>Дата</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($pending_checks, 0, 5) as $check): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= BASE_URL ?>/technologist/viewCheck/<?= $check['id'] ?>" class="text-decoration-none">
                                                    #<?= $check['order_number'] ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($check['supplier_name']) ?></td>
                                            <td><?= Util::formatDate($check['check_date'], 'd.m.Y') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($pending_checks) > 5): ?>
                            <div class="text-center mt-2">
                                <a href="<?= BASE_URL ?>/technologist/qualityChecks?status=pending" class="btn btn-outline-primary btn-sm">
                                    Переглянути всі (<?= count($pending_checks) ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Последние проверки -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>Останні перевірки
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_checks)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Перевірок ще не проводилось</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Замовлення</th>
                                        <th>Статус</th>
                                        <th>Дата</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_checks as $check): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= BASE_URL ?>/technologist/viewCheck/<?= $check['id'] ?>" class="text-decoration-none">
                                                    #<?= $check['order_number'] ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= Util::getQualityStatusClass($check['status']) ?>">
                                                    <?= Util::getQualityStatusName($check['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= Util::formatDate($check['check_date'], 'd.m H:i') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-2">
                            <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-outline-info btn-sm">
                                Переглянути всі перевірки
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика за месяц -->
    <?php if (!empty($monthly_stats)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Статистика за останні 30 днів
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <div class="h4 text-primary"><?= $monthly_stats['total_checks'] ?? 0 ?></div>
                                <small class="text-muted">Всього перевірок</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <div class="h4 text-success"><?= $monthly_stats['approval_rate'] ?? 0 ?>%</div>
                                <small class="text-muted">Рівень схвалення</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <div class="h4 text-danger"><?= $monthly_stats['rejection_rate'] ?? 0 ?>%</div>
                                <small class="text-muted">Рівень відхилення</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <div class="h4 text-warning"><?= $monthly_stats['pending'] ?? 0 ?></div>
                                <small class="text-muted">Очікують рішення</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.h-100 {
    height: 100%!important;
}

.table-sm td {
    padding: 0.5rem 0.25rem;
}
</style>