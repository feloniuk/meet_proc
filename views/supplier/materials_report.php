<?php
// views/supplier/materials_report.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-cubes me-2"></i>Звіт по матеріалах</h1>
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/supplier/reports" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад
            </a>
            <a href="<?= BASE_URL ?>/supplier/generateMaterialsPdf?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
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

    <!-- Общая статистика -->
    <div class="row mb-4">
        <?php 
        $total_materials = count($materials_stats);
        $total_orders = array_sum(array_column($materials_stats, 'orders_count'));
        $total_quantity = array_sum(array_column($materials_stats, 'total_ordered'));
        $total_revenue = array_sum(array_column($materials_stats, 'total_amount'));
        ?>
        
        <div class="col-md-3 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-cubes fa-2x text-primary mb-2"></i>
                    <h4 class="text-primary"><?= $total_materials ?></h4>
                    <p class="text-muted mb-0">Видів матеріалів</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-2x text-success mb-2"></i>
                    <h4 class="text-success"><?= $total_orders ?></h4>
                    <p class="text-muted mb-0">Всього замовлень</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-weight-hanging fa-2x text-warning mb-2"></i>
                    <h4 class="text-warning"><?= number_format($total_quantity, 2) ?></h4>
                    <p class="text-muted mb-0">Загальна кількість</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-hryvnia fa-2x text-info mb-2"></i>
                    <h4 class="text-info"><?= Util::formatMoney($total_revenue) ?></h4>
                    <p class="text-muted mb-0">Загальний дохід</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Детальная статистика по материалам -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Детальна статистика по матеріалах</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Назва матеріалу</th>
                            <th>Одиниця виміру</th>
                            <th>Ціна за од.</th>
                            <th>Мін. запас</th>
                            <th>Кількість замовлень</th>
                            <th>Загальна кількість</th>
                            <th>Загальна сума</th>
                            <th>Популярність</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materials_stats)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-3">Немає даних за вказаний період</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($materials_stats as $index => $material): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($material['name']) ?></strong>
                                        <?php if ($index < 3): ?>
                                            <span class="badge bg-warning ms-2">ТОП-<?= $index + 1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($material['unit']) ?></td>
                                    <td><?= Util::formatMoney($material['price_per_unit']) ?></td>
                                    <td><?= $material['min_stock'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $material['orders_count'] > 0 ? 'success' : 'secondary' ?>">
                                            <?= $material['orders_count'] ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($material['total_ordered'], 2) ?> <?= htmlspecialchars($material['unit']) ?></td>
                                    <td><?= Util::formatMoney($material['total_amount']) ?></td>
                                    <td>
                                        <?php 
                                        $popularity = $total_orders > 0 ? ($material['orders_count'] / $total_orders) * 100 : 0;
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= $popularity ?>%;" 
                                                 aria-valuenow="<?= $popularity ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?= number_format($popularity, 1) ?>%
                                            </div>
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

    <!-- Анализ и рекомендации -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Найпопулярніші матеріали</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $top_materials = array_slice($materials_stats, 0, 3);
                    if (!empty($top_materials)):
                    ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_materials as $index => $material): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($material['name']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= $material['orders_count'] ?> замовлень</small>
                                    </div>
                                    <span class="badge bg-success rounded-pill">
                                        <?= Util::formatMoney($material['total_amount']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Немає даних для відображення</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Рекомендації</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php if ($total_orders == 0): ?>
                            <div class="list-group-item">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                <strong>Немає замовлень</strong>
                                <br>
                                <small>Розгляньте можливість активної реклами ваших матеріалів</small>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($top_materials)): ?>
                                <div class="list-group-item">
                                    <i class="fas fa-star text-warning me-2"></i>
                                    <strong>Популярні матеріали</strong>
                                    <br>
                                    <small>Підтримуйте достатній запас матеріалу "<?= htmlspecialchars($top_materials[0]['name']) ?>"</small>
                                </div>
                            <?php endif; ?>
                            
                            <?php 
                            $unused_materials = array_filter($materials_stats, function($m) { return $m['orders_count'] == 0; });
                            if (!empty($unused_materials)):
                            ?>
                                <div class="list-group-item">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    <strong>Невикористані матеріали</strong>
                                    <br>
                                    <small><?= count($unused_materials) ?> матеріалів без замовлень. Розгляньте зміну цін або маркетингових стратегій</small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="list-group-item">
                                <i class="fas fa-chart-line text-success me-2"></i>
                                <strong>Середня сума замовлення</strong>
                                <br>
                                <small><?= $total_orders > 0 ? Util::formatMoney($total_revenue / $total_orders) : '0 грн' ?> на замовлення</small>
                            </div>
                        <?php endif; ?>
                    </div>
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