<?php
// views/warehouse/view_order.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/warehouse/orders" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-shopping-cart me-2"></i>Перегляд замовлення #<?= $order['id'] ?></h1>
        <div class="ms-auto">
            <?php if ($order['status'] === 'pending'): ?>
                <a href="<?= BASE_URL ?>/warehouse/editOrder/<?= $order['id'] ?>" class="btn btn-primary me-2">
                    <i class="fas fa-edit me-1"></i>Редагувати
                </a>
            <?php endif; ?>

            <?php if ($order['status'] === 'shipped' && (isset($order['quality_status']) && ($order['quality_status'] === 'approved' || $order['quality_status'] === 'not_checked'))): ?>
                <a href="<?= BASE_URL ?>/warehouse/deliverOrder/<?= $order['id'] ?>" 
                   class="btn btn-success me-2" 
                   onclick="return confirm('Ви впевнені, що хочете підтвердити отримання замовлення?');">
                    <i class="fas fa-truck me-1"></i>Підтвердити отримання
                </a>
            <?php elseif ($order['status'] === 'shipped' && isset($order['quality_status']) && $order['quality_status'] === 'pending'): ?>
                <div class="alert alert-warning me-2 mb-0 p-2 d-inline-block">
                    <i class="fas fa-clock me-1"></i>Очікується перевірка якості
                </div>
            <?php elseif ($order['status'] === 'shipped' && isset($order['quality_status']) && $order['quality_status'] === 'rejected'): ?>
                <div class="alert alert-danger me-2 mb-0 p-2 d-inline-block">
                    <i class="fas fa-times me-1"></i>Сировину відхилено технологом
                </div>
            <?php endif; ?>

            <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'canceled'): ?>
                <a href="<?= BASE_URL ?>/warehouse/cancelOrder/<?= $order['id'] ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('Ви впевнені, що хочете скасувати замовлення?');">
                    <i class="fas fa-times me-1"></i>Скасувати
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Статус замовлення -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title mb-1">Статус замовлення</h5>
                            <p class="text-muted mb-0">Замовлення від <?= Util::formatDate($order['created_at'], 'd.m.Y') ?></p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-<?= 
                                $order['status'] === 'delivered' ? 'success' : 
                                ($order['status'] === 'canceled' ? 'danger' : 
                                    ($order['status'] === 'shipped' ? 'primary' : 
                                        ($order['status'] === 'accepted' ? 'info' : 'warning'))) 
                            ?> p-2 fs-6 mb-2">
                                <?= Util::getOrderStatusName($order['status']) ?>
                            </span>
                            
                            <?php if (isset($order['quality_status']) && $order['status'] === 'shipped'): ?>
                                <br>
                                <small class="text-muted">Якість:</small>
                                <span class="badge bg-<?= 
                                    $order['quality_status'] === 'approved' ? 'success' : 
                                    ($order['quality_status'] === 'rejected' ? 'danger' : 
                                        ($order['quality_status'] === 'pending' ? 'warning' : 'secondary')) 
                                ?> p-2">
                                    <?php
                                    switch ($order['quality_status'] ?? 'not_checked') {
                                        case 'approved': echo 'Схвалено'; break;
                                        case 'rejected': echo 'Відхилено'; break;
                                        case 'pending': echo 'На перевірці'; break;
                                        default: echo 'Не перевірено';
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Прогрес-бар статусу замовлення -->
                    <div class="mt-4">
                        <div class="position-relative mb-4">
                            <div class="progress" style="height: 3px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= 
                                    $order['status'] === 'delivered' ? '100%' : 
                                    ($order['status'] === 'shipped' ? '75%' : 
                                        ($order['status'] === 'accepted' ? '50%' : 
                                            ($order['status'] === 'pending' ? '25%' : '0%'))) 
                                ?>;" aria-valuenow="<?= 
                                    $order['status'] === 'delivered' ? '100' : 
                                    ($order['status'] === 'shipped' ? '75' : 
                                        ($order['status'] === 'accepted' ? '50' : 
                                            ($order['status'] === 'pending' ? '25' : '0'))) 
                                ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between position-absolute" style="top: -10px; width: 100%;">
                                <div class="text-center" style="width: 25%;">
                                    <div class="<?= in_array($order['status'], ['pending', 'accepted', 'shipped', 'delivered']) ? 'bg-primary' : 'bg-secondary' ?> rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 25px; height: 25px;">
                                        <i class="fas fa-file-alt text-white small"></i>
                                    </div>
                                    <p class="small mt-2">Створено</p>
                                </div>
                                <div class="text-center" style="width: 25%;">
                                    <div class="<?= in_array($order['status'], ['accepted', 'shipped', 'delivered']) ? 'bg-primary' : 'bg-secondary' ?> rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 25px; height: 25px;">
                                        <i class="fas fa-check text-white small"></i>
                                    </div>
                                    <p class="small mt-2">Прийнято</p>
                                </div>
                                <div class="text-center" style="width: 25%;">
                                    <div class="<?= in_array($order['status'], ['shipped', 'delivered']) ? 'bg-primary' : 'bg-secondary' ?> rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 25px; height: 25px;">
                                        <i class="fas fa-truck text-white small"></i>
                                    </div>
                                    <p class="small mt-2">Відправлено</p>
                                </div>
                                <div class="text-center" style="width: 25%;">
                                    <div class="<?= $order['status'] === 'delivered' ? 'bg-primary' : 'bg-secondary' ?> rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 25px; height: 25px;">
                                        <i class="fas fa-box-open text-white small"></i>
                                    </div>
                                    <p class="small mt-2">Доставлено</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Позиції замовлення -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Позиції замовлення</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Сировина</th>
                                    <th>Кількість</th>
                                    <th>Ціна за од.</th>
                                    <th>Загальна сума</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">Немає доданих позицій</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['material_name']) ?></td>
                                            <td><?= Util::formatQuantity($item['quantity'], $item['unit']) ?></td>
                                            <td><?= Util::formatMoney($item['price_per_unit']) ?></td>
                                            <td><?= Util::formatMoney($item['quantity'] * $item['price_per_unit']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- Загальна сума -->
                                    <tr class="table-primary">
                                        <th colspan="3" class="text-end">Загальна сума:</th>
                                        <th><?= Util::formatMoney($order['total_amount']) ?></th>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Примітки -->
            <?php if (!empty($order['notes'])): ?>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Примітки до замовлення</h5>
                    </div>
                    <div class="card-body">
                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Інформація про постачальника -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Інформація про постачальника</h5>
                </div>
                <div class="card-body">
                    <h5><?= htmlspecialchars($order['supplier_name']) ?></h5>
                    <p><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($order['supplier_email']) ?></p>
                    
                    <?php if (!empty($order['supplier_phone'])): ?>
                        <p><i class="fas fa-phone me-2"></i><?= htmlspecialchars($order['supplier_phone']) ?></p>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/home/newMessage?receiver_id=<?= $order['supplier_id'] ?>&subject=Замовлення #<?= $order['id'] ?>" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-1"></i>Надіслати повідомлення
                        </a>
                    </div>
                </div>
            </div>

            <!-- Історія замовлення -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Історія замовлення</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 fw-bold">Створено</p>
                                <p class="text-muted mb-0">Замовлення створено директором</p>
                            </div>
                            <span class="text-muted"><?= Util::formatDate($order['created_at'], 'd.m.Y H:i') ?></span>
                        </li>
                        
                        <?php if ($order['status'] === 'accepted' || $order['status'] === 'shipped' || $order['status'] === 'delivered'): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-0 fw-bold">Прийнято</p>
                                    <p class="text-muted mb-0">Замовлення прийняте постачальником</p>
                                </div>
                                <span class="text-muted"><?= Util::formatDate($order['updated_at'], 'd.m.Y H:i') ?></span>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'shipped' || $order['status'] === 'delivered'): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-0 fw-bold">Відправлено</p>
                                    <p class="text-muted mb-0">Замовлення відправлено постачальником</p>
                                </div>
                                <span class="text-muted"><?= Util::formatDate($order['updated_at'], 'd.m.Y H:i') ?></span>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'delivered'): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-0 fw-bold">Доставлено</p>
                                    <p class="text-muted mb-0">Замовлення доставлено та прийняте</p>
                                </div>
                                <span class="text-muted"><?= Util::formatDate($order['updated_at'], 'd.m.Y H:i') ?></span>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'canceled'): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                <div>
                                    <p class="mb-0 fw-bold text-danger">Скасовано</p>
                                    <p class="text-muted mb-0">Замовлення скасоване</p>
                                </div>
                                <span class="text-muted"><?= Util::formatDate($order['updated_at'], 'd.m.Y H:i') ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Дії -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Дії</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/warehouse/printOrder/<?= $order['id'] ?>" 
                           class="btn btn-outline-dark" 
                           target="_blank">
                            <i class="fas fa-print me-1"></i>Роздрукувати замовлення
                        </a>
                        
                        <?php if ($order['status'] === 'delivered'): ?>
                            <a href="<?= BASE_URL ?>/warehouse/inventoryReport" 
                               class="btn btn-outline-info">
                                <i class="fas fa-boxes me-1"></i>Перевірити інвентаризацію
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>