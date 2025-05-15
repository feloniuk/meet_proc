<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-tachometer-alt me-2"></i>Панель начальника складу</h1>
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/warehouse/reports" class="btn btn-outline-primary">
                <i class="fas fa-chart-bar me-1"></i>Звіти
            </a>
            <a href="<?= BASE_URL ?>/warehouse/inventory" class="btn btn-outline-primary">
                <i class="fas fa-boxes me-1"></i>Інвентаризація
            </a>
        </div>
    </div>
    
    <!-- Основні показники -->
    <div class="row dashboard-stats mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-0">Активні виробництва</h6>
                            <h2 class="mt-2 mb-0"><?= count($active_production) ?></h2>
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
        
        <div class="col-md-4 mb-3">
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
        
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-0">Критичні запаси</h6>
                            <h2 class="mt-2 mb-0"><?= count($low_stock) ?></h2>
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
    
    <div class="row">
        <!-- Активні виробничі процеси -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-industry me-2"></i>Активні виробничі процеси</h5>
                    <div>
                        <a href="<?= BASE_URL ?>/warehouse/planProduction" class="btn btn-sm btn-success me-2">
                            <i class="fas fa-plus me-1"></i>Запланувати
                        </a>
                        <a href="<?= BASE_URL ?>/warehouse/production" class="btn btn-sm btn-outline-primary">
                            Всі процеси
                        </a>
                    </div>
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
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($active_production)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3">Немає активних виробничих процесів</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($active_production as $process): ?>
                                        <tr>
                                            <td><?= $process['id'] ?></td>
                                            <td><?= htmlspecialchars($process['product_name']) ?></td>
                                            <td><?= $process['quantity'] ?></td>
                                            <td>
                                                <span class="badge status-<?= $process['status'] ?>">
                                                    <?= Util::getProductionStatusName($process['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= Util::formatDate($process['started_at']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>/warehouse/viewProduction/<?= $process['id'] ?>" 
                                                       class="btn btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($process['status'] === 'planned'): ?>
                                                        <a href="<?= BASE_URL ?>/warehouse/startProduction/<?= $process['id'] ?>" 
                                                           class="btn btn-outline-primary" 
                                                           onclick="return confirm('Ви впевнені, що хочете розпочати виробничий процес?');">
                                                            <i class="fas fa-play"></i>
                                                        </a>
                                                    <?php elseif ($process['status'] === 'in_progress'): ?>
                                                        <a href="<?= BASE_URL ?>/warehouse/completeProduction/<?= $process['id'] ?>" 
                                                           class="btn btn-outline-success" 
                                                           onclick="return confirm('Ви впевнені, що хочете завершити виробничий процес?');">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($process['status'] !== 'completed' && $process['status'] !== 'canceled'): ?>
                                                        <a href="<?= BASE_URL ?>/warehouse/cancelProduction/<?= $process['id'] ?>" 
                                                           class="btn btn-outline-danger" 
                                                           onclick="return confirm('Ви впевнені, що хочете скасувати виробничий процес?');">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
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
        
        <!-- Категорії інвентаризації -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Інвентаризація за категоріями</h5>
                    <a href="<?= BASE_URL ?>/warehouse/inventory" class="btn btn-sm btn-outline-primary">
                        Деталі інвентаризації
                    </a>
                </div>
                <div class="card-body">
                    <?php
                        // Групування інвентаризації за категоріями
                        $categories = [];
                        $total_value = 0;
                        
                        foreach ($inventory as $item) {
                            $category = explode(' ', $item['material_name'])[0];
                            if (!isset($categories[$category])) {
                                $categories[$category] = [
                                    'count' => 0,
                                    'value' => 0
                                ];
                            }
                            
                            $categories[$category]['count']++;
                            $value = $item['quantity'] * $item['price_per_unit'];
                            $categories[$category]['value'] += $value;
                            $total_value += $value;
                        }
                        
                        // Сортування за вартістю (від найбільшої)
                        uasort($categories, function($a, $b) {
                            return $b['value'] <=> $a['value'];
                        });
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Категорія</th>
                                    <th>Кількість найменувань</th>
                                    <th>Вартість</th>
                                    <th>% від загальної</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category => $data): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($category) ?></td>
                                        <td><?= $data['count'] ?></td>
                                        <td><?= Util::formatMoney($data['value']) ?></td>
                                        <td>
                                            <?= number_format($data['value'] / $total_value * 100, 1) ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th>Всього</th>
                                    <th><?= count($inventory) ?></th>
                                    <th><?= Util::formatMoney($total_value) ?></th>
                                    <th>100%</th>
                                </tr>
                            </tfoot>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['material_name']) ?></td>
                                        <td class="text-danger"><?= Util::formatQuantity($item['quantity'], $item['unit']) ?></td>
                                        <td><?= Util::formatQuantity($item['min_stock'], $item['unit']) ?></td>
                                        <td><?= htmlspecialchars($item['supplier_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
                <a href="<?= BASE_URL ?>/home/newMessage" class="btn btn-primary">
                    <i class="fas fa-envelope me-1"></i>Повідомити адміністратора
                </a>
            </div>
        </div>
    </div>
</div>