<?php
// views/admin/edit_user.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/users" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-user-edit me-2"></i>Редагування користувача</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/editUser/<?= $user['id'] ?>" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Логін користувача</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           value="<?= htmlspecialchars($user['username']) ?>" 
                                           disabled>
                                    <small class="form-text text-muted">Логін не можна змінити</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role">Роль</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="role" 
                                           value="<?= Util::getUserRoleName($user['role']) ?>" 
                                           disabled>
                                    <small class="form-text text-muted">Роль не можна змінити</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name">Ім'я / Назва організації</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'name') ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($user['name']) ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'name') ?>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" 
                                           class="form-control <?= Util::getErrorClass($errors, 'email') ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" 
                                           required>
                                    <?= Util::getErrorMessage($errors, 'email') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Телефон</label>
                                    <input type="text" 
                                           class="form-control <?= Util::getErrorClass($errors, 'phone') ?>" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?= htmlspecialchars($user['phone'] ?: '') ?>">
                                    <?= Util::getErrorMessage($errors, 'phone') ?>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5>Зміна пароля</h5>
                        <p class="text-muted small">Залиште це поле порожнім, якщо не хочете змінювати пароль.</p>

                        <div class="form-group mb-3">
                            <label for="new_password">Новий пароль</label>
                            <input type="password" 
                                   class="form-control <?= Util::getErrorClass($errors, 'new_password') ?>" 
                                   id="new_password" 
                                   name="new_password">
                            <?= Util::getErrorMessage($errors, 'new_password') ?>
                            <small class="form-text text-muted">Мінімум 6 символів</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/users" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Зберегти зміни
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Інформація про користувача</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?= $user['id'] ?></p>
                    <p><strong>Створений:</strong> <?= Util::formatDate($user['created_at'], 'd.m.Y H:i') ?></p>
                    
                    <!-- Додаткова інформація залежно від ролі -->
                    <?php if ($user['role'] === 'supplier'): ?>
                        <hr>
                        <h6>Сировина постачальника</h6>
                        <?php
                            $rawMaterialModel = new RawMaterial();
                            $materials = $rawMaterialModel->getBySupplier($user['id']);
                        ?>
                        <?php if (empty($materials)): ?>
                            <p class="text-muted small">Цей постачальник ще не додав жодної сировини.</p>
                        <?php else: ?>
                            <ul class="small">
                                <?php foreach ($materials as $material): ?>
                                    <li><?= htmlspecialchars($material['name']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>