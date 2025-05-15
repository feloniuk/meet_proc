<?php
// views/admin/users.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-users me-2"></i>Управління користувачами</h1>
        <a href="<?= BASE_URL ?>/admin/addUser" class="btn btn-success">
            <i class="fas fa-user-plus me-1"></i>Додати користувача
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логін</th>
                            <th>Роль</th>
                            <th>Ім'я</th>
                            <th>Email</th>
                            <th>Телефон</th>
                            <th>Дата створення</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-3">Немає користувачів</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'warehouse_manager' ? 'primary' : 'success') ?>">
                                            <?= Util::getUserRoleName($user['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['phone'] ?: '-') ?></td>
                                    <td><?= Util::formatDate($user['created_at'], 'd.m.Y') ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/admin/editUser/<?= $user['id'] ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['id'] != Auth::getCurrentUserId()): ?>
                                                <a href="<?= BASE_URL ?>/admin/deleteUser/<?= $user['id'] ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Ви впевнені, що хочете видалити цього користувача?');">
                                                    <i class="fas fa-trash"></i>
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
