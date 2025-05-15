<?php
// views/admin/cameras.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-cctv me-2"></i>Управління камерами</h1>
        <div>
            <a href="<?= BASE_URL ?>/admin/videoSurveillance" class="btn btn-outline-primary me-2">
                <i class="fas fa-video me-1"></i>Відеоспостереження
            </a>
            <a href="<?= BASE_URL ?>/admin/addCamera" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Додати камеру
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва</th>
                            <th>URL</th>
                            <th>Розташування</th>
                            <th>Статус</th>
                            <th>Дата додавання</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cameras)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">Немає доданих камер</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cameras as $camera): ?>
                                <tr>
                                    <td><?= $camera['id'] ?></td>
                                    <td><?= htmlspecialchars($camera['name']) ?></td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;">
                                            <?= htmlspecialchars($camera['url']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($camera['location']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $camera['status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= $camera['status'] === 'active' ? 'Активна' : 'Неактивна' ?>
                                        </span>
                                    </td>
                                    <td><?= Util::formatDate($camera['created_at'], 'd.m.Y') ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($camera['status'] === 'active'): ?>
                                                <a href="<?= BASE_URL ?>/admin/setCameraStatus/<?= $camera['id'] ?>/inactive" 
                                                   class="btn btn-outline-warning" 
                                                   title="Деактивувати"
                                                   data-bs-toggle="tooltip">
                                                    <i class="fas fa-pause"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/admin/setCameraStatus/<?= $camera['id'] ?>/active" 
                                                   class="btn btn-outline-success" 
                                                   title="Активувати"
                                                   data-bs-toggle="tooltip">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="<?= BASE_URL ?>/admin/editCamera/<?= $camera['id'] ?>" 
                                               class="btn btn-outline-primary"
                                               title="Редагувати"
                                               data-bs-toggle="tooltip">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <a href="<?= BASE_URL ?>/admin/deleteCamera/<?= $camera['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               title="Видалити"
                                               data-bs-toggle="tooltip"
                                               onclick="return confirm('Ви впевнені, що хочете видалити цю камеру?');">
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
