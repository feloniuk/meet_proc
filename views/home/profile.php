<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Инициализируем переменные, если они не установлены
$user = $user ?? [];
$errors = $errors ?? [];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-id-card me-2"></i>Мій профіль</h1>
    </div>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Інформація про користувача</h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/home/profile" method="post">
                    <div class="row mb-3">
                        <label for="username" class="col-sm-3 col-form-label">Логін:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control-plaintext" id="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="role" class="col-sm-3 col-form-label">Роль:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control-plaintext" id="role" value="<?= Util::getUserRoleName($user['role'] ?? '') ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="created_at" class="col-sm-3 col-form-label">Дата реєстрації:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control-plaintext" id="created_at" value="<?= Util::formatDate($user['created_at'] ?? date('Y-m-d H:i:s')) ?>" readonly>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row mb-3">
                        <label for="name" class="col-sm-3 col-form-label">Ім'я / Назва:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                  id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="email" class="col-sm-3 col-form-label">Email:</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                  id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= $errors['email'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="phone" class="col-sm-3 col-form-label">Телефон:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="phone" name="phone" 
                                  value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Зберегти зміни
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Змінити пароль</h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/home/profile" method="post">
                    <!-- Скрытые поля для сохранения текущих значений -->
                    <input type="hidden" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    
                    <div class="row mb-3">
                        <label for="old_password" class="col-sm-3 col-form-label">Поточний пароль:</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control <?= isset($errors['old_password']) ? 'is-invalid' : '' ?>" 
                                  id="old_password" name="old_password">
                            <?php if (isset($errors['old_password'])): ?>
                                <div class="invalid-feedback"><?= $errors['old_password'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="new_password" class="col-sm-3 col-form-label">Новий пароль:</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" 
                                  id="new_password" name="new_password">
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="invalid-feedback"><?= $errors['new_password'] ?></div>
                            <?php else: ?>
                                <div class="form-text">Мінімум 6 символів</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="confirm_password" class="col-sm-3 col-form-label">Підтвердження:</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                  id="confirm_password" name="confirm_password">
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?= $errors['confirm_password'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-1"></i>Змінити пароль
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="alert alert-info mt-4">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-info-circle fa-2x"></i>
                </div>
                <div>
                    <h5>Інформація</h5>
                    <p class="mb-0">Для оновлення основної інформації заповніть поля та натисніть "Зберегти зміни". Для зміни пароля введіть поточний пароль та новий пароль двічі, потім натисніть "Змінити пароль".</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Добавление подсветки надежности пароля
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Длина пароля
        if (password.length >= 6) strength += 1;
        if (password.length >= 10) strength += 1;
        
        // Проверка на наличие букв нижнего и верхнего регистра
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
        
        // Проверка на наличие цифр
        if (/\d/.test(password)) strength += 1;
        
        // Проверка на наличие специальных символов
        if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
        
        // Обновление UI в зависимости от сложности пароля
        if (password.length === 0) {
            this.classList.remove('is-valid', 'is-warning', 'is-invalid');
        } else if (strength < 3) {
            this.classList.remove('is-valid', 'is-warning');
            this.classList.add('is-invalid');
            this.nextElementSibling.innerHTML = 'Слабкий пароль';
        } else if (strength < 5) {
            this.classList.remove('is-valid', 'is-invalid');
            this.classList.add('is-warning');
            this.nextElementSibling.innerHTML = 'Середній пароль';
        } else {
            this.classList.remove('is-warning', 'is-invalid');
            this.classList.add('is-valid');
            this.nextElementSibling.innerHTML = 'Надійний пароль';
        }
    });
    
    // Проверка совпадения паролей
    confirmPasswordInput.addEventListener('input', function() {
        const password = newPasswordInput.value;
        const confirmPassword = this.value;
        
        if (confirmPassword.length === 0) {
            this.classList.remove('is-valid', 'is-invalid');
        } else if (password === confirmPassword) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
            this.nextElementSibling.innerHTML = 'Паролі не співпадають';
        }
    });
});
</script>