<?php
// views/admin/add_user.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/users" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-user-plus me-2"></i>Додавання користувача</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/addUser" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Логін користувача</label>
                                    <input type="text" 
                                           class="form-control <?= Util::getErrorClass($errors, 'username') ?>" 
                                           id="username" 
                                           name="username" 
                                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                                           required>
                                    <?= Util::getErrorMessage($errors, 'username') ?>
                                    <small class="form-text text-muted">Мінімум 3 символи, буде використовуватись для входу</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Пароль</label>
                                    <input type="password" 
                                           class="form-control <?= Util::getErrorClass($errors, 'password') ?>" 
                                           id="password" 
                                           name="password" 
                                           required>
                                    <?= Util::getErrorMessage($errors, 'password') ?>
                                    <small class="form-text text-muted">Мінімум 6 символів</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="role">Роль</label>
                            <select name="role" 
                                    id="role" 
                                    class="form-select <?= Util::getErrorClass($errors, 'role') ?>" 
                                    required>
                                <option value="">Виберіть роль</option>
                                <option value="admin" <?= isset($_POST['role']) && $_POST['role'] === 'admin' ? 'selected' : '' ?>>
                                    Адміністратор
                                </option>
                                <option value="warehouse_manager" <?= isset($_POST['role']) && $_POST['role'] === 'warehouse_manager' ? 'selected' : '' ?>>
                                    Начальник складу
                                </option>
                                <option value="supplier" <?= isset($_POST['role']) && $_POST['role'] === 'supplier' ? 'selected' : '' ?>>
                                    Постачальник
                                </option>
                            </select>
                            <?= Util::getErrorMessage($errors, 'role') ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name">Ім'я / Назва організації</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'name') ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" 
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
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
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
                                           value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                                    <?= Util::getErrorMessage($errors, 'phone') ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/users" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Зберегти
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Інформація про ролі</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><span class="badge bg-danger">Адміністратор</span></h6>
                        <p class="small">Має повний доступ до системи. Управляє користувачами, рецептами, продуктами, замовленнями та відеоспостереженням.</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6><span class="badge bg-primary">Начальник складу</span></h6>
                        <p class="small">Управляє інвентаризацією та виробничими процесами, формує звіти по запасам та виробництву.</p>
                    </div>
                    
                    <div>
                        <h6><span class="badge bg-success">Постачальник</span></h6>
                        <p class="small">Управляє власною сировиною, обробляє замовлення, комунікує з адміністратором, формує звіти.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>