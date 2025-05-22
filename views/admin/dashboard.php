<?php 
// views/admin/dashboard.php
// Теперь все переменные доступны напрямую благодаря BaseController::render()
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-tachometer-alt me-2"></i>Панель управління</h1>
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/admin/reports" class="btn btn-outline-primary">
                <i class="fas fa-chart-bar me-1"></i>Звіти
            </a>
            <a href="<?= BASE_URL ?>/admin/orders" class="btn btn-outline-primary">
                <i class="fas fa-shopping-cart me-1"></i>Замовлення
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
                            <h6 class="card-title text-muted mb-0">Активні замовлення</h6>
                            <h2 class="mt-2 mb-0"><?= count($active_orders ?? []) ?></h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="<?= BASE_URL ?>/admin/orders" class="text-decoration-none">
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
                            <h6 class="card-title text-muted mb-0">Активні виробництва</h6>
                            <h2 class="mt-2 mb-0"><?= count($active_production ?? []) ?></h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-industry"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="<?= BASE_URL ?>/warehouse/production" class="text-decoration-none">
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
                            <h2 class="mt-2 mb-0"><?= $unread_messages ?? 0 ?></h2>
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
                            <h6 class="card-title text-muted mb-0">Критичні запаси</h6>
                            <h2 class="mt-2 mb-0"><?= count($low_stock ?? []) ?></h2>
                        </div>
                        <div class="card-icon text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="#lowStockModal" data-bs-toggle="modal" class="text-decoration-none">
                            Деталі <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Контроль якості -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-microscope me-2"></i>Контроль якості сировини</h5>
                </div>
                <div class="card-body">
                    <?php 
                        // Получение статистики по качеству
                        try {
                            $orderModel = new Order();
                            $start_date = date('Y-m-01');
                            $end_date = date('Y-m-t');
                            $quality_stats = $orderModel->getQualityStatsByPeriod($start_date, $end_date);
                            
                            if (!$quality_stats) {
                                $quality_stats = [
                                    'total_orders' => 0,
                                    'approved_orders' => 0,
                                    'rejected_orders' => 0,
                                    'pending_orders' => 0,
                                    'approval_rate' => 0
                                ];
                            }
                            
                            $orders_for_check = $orderModel->getOrdersForQualityCheck() ?: [];
                            $problematic_suppliers = $orderModel->getProblematicSuppliers($start_date, $end_date) ?: [];
                        } catch (Exception $e) {
                            $quality_stats = [
                                'total_orders' => 0,
                                'approved_orders' => 0,
                                'rejected_orders' => 0,
                                'pending_orders' => 0,
                                'approval_rate' => 0
                            ];
                            $orders_for_check = [];
                            $problematic_suppliers = [];
                        }
                    ?>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-primary"><?= $quality_stats['total_orders'] ?></h3>
                                <small class="text-muted">Всього замовлень</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-success"><?= $quality_stats['approved_orders'] ?></h3>
                                <small class="text-muted">Схвалено</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-danger"><?= $quality_stats['rejected_orders'] ?></h3>
                                <small class="text-muted">Відхилено</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-warning"><?= count($orders_for_check) ?></h3>
                                <small class="text-muted">Очікують перевірки</small>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($quality_stats['total_orders'] > 0): ?>
                        <div class="progress mt-3" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?= $quality_stats['approval_rate'] ?>%"
                                 aria-valuenow="<?= $quality_stats['approval_rate'] ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?= round($quality_stats['approval_rate'], 1) ?>% схвалено
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($problematic_suppliers)): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Увага!</strong>
                            Виявлено <?= count($problematic_suppliers) ?> постачальників з високим рівнем відхилення якості.
                            <a href="#problematicSuppliersModal" data-bs-toggle="modal" class="alert-link">Переглянути деталі</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Останні замовлення -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Останні замовлення</h5>
                    <a href="<?= BASE_URL ?>/admin/orders" class="btn btn-sm btn-outline-primary">
                        Всі замовлення
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Постачальник</th>
                                    <th>Статус</th>
                                    <th>Сума</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($active_orders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3">Немає активних замовлень</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($active_orders, 0, 5) as $order): ?>
                                        <tr>
                                            <td><?= $order['id'] ?></td>
                                            <td><?= htmlspecialchars($order['supplier_name'] ?? 'Не вказано') ?></td>
                                            <td>
                                                <span class="badge status-<?= $order['status'] ?>">
                                                    <?= Util::getOrderStatusName($order['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= Util::formatMoney($order['total_amount'] ?? 0) ?></td>
                                            <td><?= Util::formatDate($order['created_at'] ?? date('Y-m-d H:i:s'), 'd.m.Y') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
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
                                            <td><?= htmlspecialchars($message['sender_name'] ?? 'Невідомий') ?></td>
                                            <td><?= htmlspecialchars($message['subject'] ?? 'Без теми') ?></td>
                                            <td><?= Util::formatDate($message['created_at'] ?? date('Y-m-d H:i:s')) ?></td>
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
    </div>
    
    <!-- Активні виробничі процеси -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-industry me-2"></i>Активні виробничі процеси</h5>
                    <a href="<?= BASE_URL ?>/warehouse/production" class="btn btn-sm btn-outline-primary">
                        Всі процеси
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Продукт</th>
                                    <th>Кількість</th>
                                    <th>Статус</th>
                                    <th>Дата початку</th>
                                    <th>Відповідальний</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($active_production)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3">Немає активних виробничих процесів</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($active_production, 0, 5) as $process): ?>
                                        <tr>
                                            <td><?= $process['id'] ?></td>
                                            <td><?= htmlspecialchars($process['product_name'] ?? 'Не вказано') ?></td>
                                            <td><?= $process['quantity'] ?? 0 ?></td>
                                            <td>
                                                <span class="badge status-<?= $process['status'] ?>">
                                                    <?= Util::getProductionStatusName($process['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= Util::formatDate($process['started_at'] ?? date('Y-m-d H:i:s')) ?></td>
                                            <td><?= htmlspecialchars($process['manager_name'] ?? 'Не вказано') ?></td>
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

<!-- Модальне вікно з критичними запасами -->
<div class="modal fade" id="lowStockModal" tabindex="-1" aria-labelledby="lowStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lowStockModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Матеріали з критичним запасом
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($low_stock)): ?>
                    <p class="text-center py-3">Всі матеріали мають достатній запас</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Матеріал</th>
                                    <th>Поточний запас</th>
                                    <th>Мінімальний запас</th>
                                    <th>Постачальник</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['material_name'] ?? 'Не вказано') ?></td>
                                        <td class="text-danger"><?= Util::formatQuantity($item['quantity'] ?? 0, $item['unit'] ?? '') ?></td>
                                        <td><?= Util::formatQuantity($item['min_stock'] ?? 0, $item['unit'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($item['supplier_name'] ?? 'Не вказано') ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/createOrder" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-shopping-cart me-1"></i>Замовити
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
                <a href="<?= BASE_URL ?>/admin/createOrder" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Створити замовлення
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-stats .card {
    transition: transform 0.2s ease-in-out;
}

.dashboard-stats .card:hover {
    transform: translateY(-2px);
}

.card-icon {
    font-size: 2rem;
    color: #6c757d;
}

.message-unread {
    background-color: #f8f9fa;
    font-weight: bold;
}

.status-pending {
    background-color: #ffc107;
    color: #000;
}

.status-accepted {
    background-color: #17a2b8;
    color: #fff;
}

.status-shipped {
    background-color: #007bff;
    color: #fff;
}

.status-delivered {
    background-color: #28a745;
    color: #fff;
}

.status-canceled {
    background-color: #dc3545;
    color: #fff;
}

.status-planned {
    background-color: #6c757d;
    color: #fff;
}

.status-in_progress {
    background-color: #fd7e14;
    color: #fff;
}

.status-completed {
    background-color: #28a745;
    color: #fff;
}
</style>