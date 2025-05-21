<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Проверка на массивы, которые должны существовать
if (!isset($process) || !is_array($process) || !isset($ingredients) || !is_array($ingredients)) {
    echo '<div class="alert alert-danger">Виробничий процес не знайдено</div>';
    echo '<p class="mt-3"><a href="' . BASE_URL . '/warehouse/production" class="btn btn-primary">Повернутися до списку</a></p>';
    exit;
}

// Инициализируем переменные для безопасности
$ingredients_availability = $ingredients_availability ?? true;
?>
<!-- Отладочная информация (скрыта по умолчанию) -->
<div style="display: none;">
    <h4>Отладочная информация:</h4>
    <pre><?php var_dump($process); ?></pre>
    <pre><?php var_dump($ingredients); ?></pre>
</div>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-industry me-2"></i>Перегляд виробничого процесу</h1>
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/warehouse/production" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад до списку
            </a>
        <?php if ($process['status'] === 'planned'): ?>
            <a href="<?= BASE_URL ?>/warehouse/startProduction/<?= $process['id'] ?>" 
               class="btn btn-primary" 
               onclick="return confirm('Ви впевнені, що хочете розпочати виробничий процес?');">
                <i class="fas fa-play me-1"></i>Розпочати
            </a>
        <?php elseif ($process['status'] === 'in_progress'): ?>
            <a href="<?= BASE_URL ?>/warehouse/completeProduction/<?= $process['id'] ?>" 
               class="btn btn-success" 
               onclick="return confirm('Ви впевнені, що хочете завершити виробничий процес?');">
                <i class="fas fa-check me-1"></i>Завершити
            </a>
        <?php endif; ?>
        
        <?php if ($process['status'] !== 'completed' && $process['status'] !== 'canceled'): ?>
            <a href="<?= BASE_URL ?>/warehouse/cancelProduction/<?= $process['id'] ?>" 
               class="btn btn-outline-danger" 
               onclick="return confirm('Ви впевнені, що хочете скасувати виробничий процес?');">
                <i class="fas fa-times me-1"></i>Скасувати
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Інформація про процес</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge p-2 fs-6 <?= 
                        $process['status'] === 'planned' ? 'bg-warning' : 
                        ($process['status'] === 'in_progress' ? 'bg-primary' : 
                        ($process['status'] === 'completed' ? 'bg-success' : 'bg-danger')) 
                    ?>">
                        <?= Util::getProductionStatusName($process['status']) ?>
                    </span>
                </div>
                
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">ID:</th>
                        <td><?= $process['id'] ?></td>
                    </tr>
                    <tr>
                        <th>Продукт:</th>
                        <td><?= htmlspecialchars($process['product_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Кількість:</th>
                        <td><?= number_format($process['quantity'], 2) ?> кг</td>
                    </tr>
                    <tr>
                        <th>Дата початку:</th>
                        <td><?= Util::formatDate($process['started_at']) ?></td>
                    </tr>
                    <?php if ($process['completed_at']): ?>
                    <tr>
                        <th>Дата завершення:</th>
                        <td><?= Util::formatDate($process['completed_at']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Відповідальний:</th>
                        <td><?= htmlspecialchars($process['manager_name']) ?></td>
                    </tr>
                    <?php if ($process['notes']): ?>
                    <tr>
                        <th>Примітки:</th>
                        <td><?= nl2br(htmlspecialchars($process['notes'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Необхідні інгредієнти</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Назва</th>
                                <th>Кількість на одиницю</th>
                                <th>Загальна кількість</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ingredients)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3">Немає інгредієнтів</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ingredients as $ingredient): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ingredient['material_name']) ?></td>
                                        <td><?= number_format($ingredient['quantity'], 3) ?> <?= htmlspecialchars($ingredient['unit']) ?></td>
                                        <td><?= number_format($ingredient['quantity'] * $process['quantity'], 3) ?> <?= htmlspecialchars($ingredient['unit']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if ($process['status'] === 'planned'): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Перевірка наявності інгредієнтів</h5>
            </div>
            <div class="card-body">
                <div class="alert <?= 
                    is_array($ingredients_availability) ? 'alert-danger' : 'alert-success' 
                ?>">
                    <?php if (is_array($ingredients_availability)): ?>
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Увага!</strong> Недостатньо інгредієнтів для початку виробництва:
                        <ul class="mb-0 mt-2">
                            <?php foreach ($ingredients_availability as $item): ?>
                                <li>
                                    <?= htmlspecialchars($item['material_name']) ?>: 
                                    потрібно <?= number_format($item['required_quantity'], 3) ?> <?= htmlspecialchars($item['unit']) ?>, 
                                    наявно <?= number_format($item['available_quantity'], 3) ?> <?= htmlspecialchars($item['unit']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Готово!</strong> Всі необхідні інгредієнти в наявності.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($process['status'] === 'completed'): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Результати виробництва</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Виробництво завершено!</strong> Вироблено <?= number_format($process['quantity'], 2) ?> кг продукту "<?= htmlspecialchars($process['product_name']) ?>".
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3><?= number_format($process['quantity'], 2) ?> кг</h3>
                                <p class="text-muted mb-0">Виготовлено продукції</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3>
                                    <?php
                                        $duration = strtotime($process['completed_at']) - strtotime($process['started_at']);
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        echo "{$hours} год {$minutes} хв";
                                    ?>
                                </h3>
                                <p class="text-muted mb-0">Час виробництва</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3><?= count($ingredients) ?></h3>
                                <p class="text-muted mb-0">Використано інгредієнтів</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>