<?php
// views/supplier/orders_report.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-chart-bar me-2"></i>Звіт по замовленням</h1>
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/supplier/reports" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад
            </a>
            <a href="<?= BASE_URL ?>/supplier/generateOrdersPdf?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
               class="btn btn-outline-primary" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Завантажити PDF
            </a>
        </div>
    </div>

    <!-- Период отчета -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6><i class="fas fa-calendar me-2"></i>Період звіту:</h6>
                    <p class="mb-0">з <?= date('d.m.Y', strtotime($start_date)) ?> по <?= date('d.m.Y', strtotime($end_date)) ?></p>
                </div>
                <div class="col-md-4">
                    <h6><i class="fas fa-clock me-2"></i>Дата формування:</h6>
                    <p class="mb-0"><?= date('d.m.Y H:i') ?></p>
                </div>
                <div class="col-md-4">
                    <h6><i class="fas fa-user me-2"></i>Постачальник:</h6>
                    <p class="mb-0"><?= Auth::getCurrentUserName() ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Основные показатели -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                    <h4 class="text-primary"><?= $summary['orders_count'] ?></h4>
                    <p class="text-muted mb-0">Всього замовлень</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h4 class="text-success"><?= Util::formatMoney($summary['total_delivered']) ?></h4>
                    <p class="text-muted mb-0">Доставлено</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                    <h4 class="text-danger"><?= Util::formatMoney($summary['total_canceled']) ?></h4>
                    <p class="text-muted mb-0">Скасовано</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-hryvnia fa-2x text-info mb-2"></i>
                    <h4 class="text-info"><?= Util::formatMoney($summary['total_amount']) ?></h4>
                    <p class="text-muted mb-0">Загальна сума</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Список замовлень -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Список замовлень</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Дата</th>
                            <th>Замовник</th>
                            <th>Статус</th>
                            <th>Дата доставки</th>
                            <th>Сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-3">Немає замовлень за вказаний період</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($order['ordered_by_name']) ?></td>
                                    <td>
                                        <span class="badge status-<?= $order['status'] ?>">
                                            <?= Util::getOrderStatusName($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $order['delivery_date'] ? date('d.m.Y', strtotime($order['delivery_date'])) : '-' ?></td>
                                    <td><?= Util::formatMoney($order['total_amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Статистика по материалам -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-cubes me-2"></i>Статистика по матеріалах</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Матеріал</th>
                            <th>Одиниця виміру</th>
                            <th>Загальна кількість</th>
                            <th>Загальна сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materials_stats)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-3">Немає даних по матеріалах</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($materials_stats as $material): ?>
                                <tr>
                                    <td><?= htmlspecialchars($material['material_name']) ?></td>
                                    <td><?= htmlspecialchars($material['unit']) ?></td>
                                    <td><?= number_format($material['total_quantity'], 2, ',', ' ') ?></td>
                                    <td><?= Util::formatMoney($material['total_amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Выводы и рекомендации -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Висновки та рекомендації</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Аналіз ефективності:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>
                            <?php 
                            $delivery_rate = $summary['orders_count'] > 0 ? 
                                round(($summary['total_delivered'] / $summary['total_amount']) * 100, 1) : 0;
                            ?>
                            Рівень виконання: <?= $delivery_rate ?>%
                        </li>
                        <li><i class="fas fa-chart-line text-info me-2"></i>
                            Середня сума замовлення: 
                            <?= $summary['orders_count'] > 0 ? 
                                Util::formatMoney($summary['total_amount'] / $summary['orders_count']) : '0 грн' ?>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Рекомендації:</h6>
                    <ul class="list-unstyled">
                        <?php if ($delivery_rate < 80): ?>
                            <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Рекомендується покращити процес виконання замовлень
                            </li>
                        <?php endif; ?>
                        <?php if ($summary['total_canceled'] > 0): ?>
                            <li><i class="fas fa-info-circle text-info me-2"></i>
                                Проаналізуйте причини скасування замовлень
                            </li>
                        <?php endif; ?>
                        <li><i class="fas fa-handshake text-success me-2"></i>
                            Розглядайте можливості розширення асортименту
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn-group, .btn {
            display: none !important;
        }
        
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        
        .container-fluid {
            padding: 0 !important;
        }
    }
</style>