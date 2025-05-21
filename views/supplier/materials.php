<?php
// views/supplier/materials.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-cubes me-2"></i>Моя сировина</h1>
        <a href="<?= BASE_URL ?>/supplier/addMaterial" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Додати сировину
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва</th>
                            <th>Опис</th>
                            <th>Одиниця виміру</th>
                            <th>Ціна за одиницю</th>
                            <th>Мінімальний запас</th>
                            <th>Дата додавання</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materials)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-3">У вас ще немає доданої сировини</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td><?= $material['id'] ?></td>
                                    <td><?= htmlspecialchars($material['name']) ?></td>
                                    <td><?= htmlspecialchars(mb_substr($material['description'], 0, 50)) . (mb_strlen($material['description']) > 50 ? '...' : '') ?></td>
                                    <td><?= htmlspecialchars($material['unit']) ?></td>
                                    <td><?= Util::formatMoney($material['price_per_unit']) ?></td>
                                    <td><?= $material['min_stock'] ?> <?= htmlspecialchars($material['unit']) ?></td>
                                    <td><?= Util::formatDate($material['created_at'], 'd.m.Y') ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/supplier/editMaterial/<?= $material['id'] ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/supplier/deleteMaterial/<?= $material['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Ви впевнені, що хочете видалити цю сировину?');">
                                                <i class="fas fa-trash"></i>
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