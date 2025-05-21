<?php
// views/technologist/dashboard.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-microscope me-2"></i>Панель технолога</h1>
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/technologist/reports" class="btn btn-outline-primary">
                <i class="fas fa-chart-bar me-1"></i>Звіти
            </a>
            <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-outline-primary">
                <i class="fas fa-clipboard-check me-1"></i>Перевірки якості
            </a>
        </div>
    </div>
    
    <!-- Основні показники -->
    <div class="row dashboard-stats mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-0">Очікують перевірки</h6>
                            <h2 class="mt-2 mb-0"><?= count($pending_checks) ?></h2>
                        </div>
                        <div class="card-icon text-warning">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="<?= BASE_URL ?>/technologist/qualityChecks?status=pending" class="text-decoration-none">
                            Деталі <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-0">Завершено сьогодні</h6>
                            <h2 class="mt-2 mb-0">
                                <?= count(array_filter($completed_checks, function($check) {
                                    return date('Y-m-d', strtotime($check['check_date'])) === date('Y-m-d');
                                })) ?>
                            </h2>
                        </div>
                        <div class="card-icon text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="text-decoration-none">
                            Деталі <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-0">Повідомлення</h6>
                            <h2 class="mt-2 mb-0"><?= $unread_messages ?></h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="<?= BASE_URL ?>/home/messages" class="text-decoration-none">
                            Деталі <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-0">Стандарти якості</h6>
                            <h2 class="mt-2 mb-0">
                                <?php
                                    // Підрахунок кількості стандартів
                                    $qualityCheckModel = new QualityCheck();
                                    $standards = $qualityCheckModel->getAllStandards();
                                    echo count($standards);
                                ?>
                            </h2>
                        </div>
                        <div class="card-icon text-info">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="<?= BASE_URL ?>/technologist/qualityStandards" class="text-decoration-none">
                            Деталі <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Замовлення, що очікують перевірки -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-hourglass-half me-2"></i>Очікують перевірки</h5>
                    <a href="<?= BASE_URL ?>/technologist/createQualityCheck" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Нова перевірка
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Замовлення</th>
                                    <th>Постачальник</th>
                                    <th>Дата доставки</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending_checks)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">Немає замовлень для перевірки</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pending_checks as $check): ?>
                                        <tr>
                                            <td>#<?= $check['order_number'] ?></td>
                                            <td><?= htmlspecialchars($check['supplier_name']) ?></td>
                                            <td><?= $check['delivery_date'] ? date('d.m.Y', strtotime($check['delivery_date'])) : '-' ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>/technologist/editQualityCheck/<?= $check['id'] ?>" 
                                                       class="btn btn-outline-primary">
                                                        <i class="fas fa-microscope"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/technologist/quickAction/<?= $check['id'] ?>/approve" 
                                                       class="btn btn-outline-success" 
                                                       onclick="return confirm('Схвалити сировину без детальної перевірки?');">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/technologist/quickAction/<?= $check['id'] ?>/reject" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Відхилити сировину?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
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
        
        <!-- Останні завершені перевірки -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Останні перевірки</h5>
                    <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-sm btn-outline-primary">
                        Всі перевірки
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Замовлення</th>
                                    <th>Постачальник</th>
                                    <th>Статус</th>
                                    <th>Дата</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($completed_checks)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3">Немає завершених перевірок</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($completed_checks as $check): ?>
                                        <tr>
                                            <td>#<?= $check['order_number'] ?></td>
                                            <td><?= htmlspecialchars($check['supplier_name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $check['status'] === 'approved' ? 'success' : 
                                                    ($check['status'] === 'rejected' ? 'danger' : 'warning') 
                                                ?>">
                                                    <?= $check['status'] === 'approved' ? 'Схвалено' : 
                                                        ($check['status'] === 'rejected' ? 'Відхилено' : 'Очікує') ?>
                                                </span>
                                            </td>
                                            <td><?= date('d.m.Y', strtotime($check['check_date'])) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/technologist/viewQualityCheck/<?= $check['id'] ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
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
    </div>
    
    <div class="row">
        <!-- Останні повідомлення -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Останні повідомлення</h5>
                    <a href="<?= BASE_URL ?>/home/messages" class="btn btn-sm btn-outline-primary">
                        Всі повідомлення
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Відправник</th>
                                    <th>Тема</th>
                                    <th>Дата</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">Немає повідомлень</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="<?= $message['is_read'] ? '' : 'message-unread' ?>">
                                            <td><?= htmlspecialchars($message['sender_name']) ?></td>
                                            <td><?= htmlspecialchars($message['subject']) ?></td>
                                            <td><?= Util::formatDate($message['created_at']) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/home/viewMessage/<?= $message['id'] ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
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
        
        <!-- Швидка статистика якості -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Статистика якості (поточний місяць)</h5>
                </div>
                <div class="card-body">
                    <?php
                        // Отримуємо статистику за поточний місяць
                        $qualityCheckModel = new QualityCheck();
                        $start_date = date('Y-m-01');
                        $end_date = date('Y-m-t');
                        $stats = $qualityCheckModel->getStatsByPeriod($start_date, $end_date);
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h4 class="text-primary"><?= $stats['total_checks'] ?: 0 ?></h4>
                                <small class="text-muted">Всього перевірок</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h4 class="text-success"><?= $stats['approved'] ?: 0 ?></h4>
                                <small class="text-muted">Схвалено</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h4 class="text-danger"><?= $stats['rejected'] ?: 0 ?></h4>
                                <small class="text-muted">Відхилено</small>
                            </div>
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
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="<?= BASE_URL ?>/technologist/qualityReport" class="btn btn-outline-primary">
                            <i class="fas fa-chart-bar me-1"></i>Детальний звіт
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>