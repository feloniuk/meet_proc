<div class="auth-container">
    <div class="card auth-card shadow">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <i class="fas fa-user-plus auth-icon"></i>
                <h3 class="card-title">Реєстрація постачальника</h3>
                <p class="text-muted">Створіть обліковий запис для вашої організації</p>
            </div>
            
            <form action="<?= BASE_URL ?>/auth/register" method="post">
                <div class="form-group mb-3">
                    <label for="username">Ім'я користувача</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                              id="username" name="username" placeholder="Введіть ім'я користувача" 
                              value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback"><?= $errors['username'] ?></div>
                        <?php endif; ?>
                    </div>
                    <small class="form-text text-muted">Мінімум 3 символи, буде використовуватись для входу.</small>
                </div>
                
                <div class="form-group mb-3">
                    <label for="password">Пароль</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                              id="password" name="password" placeholder="Введіть пароль">
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= $errors['password'] ?></div>
                        <?php endif; ?>
                    </div>
                    <small class="form-text text-muted">Мінімум 6 символів.</small>
                </div>
                
                <div class="form-group mb-3">
                    <label for="confirm_password">Підтвердження пароля</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                              id="confirm_password" name="confirm_password" placeholder="Повторіть пароль">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback"><?= $errors['confirm_password'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="name">Назва організації</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                              id="name" name="name" placeholder="Введіть назву організації" 
                              value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= $errors['name'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="email">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                              id="email" name="email" placeholder="Введіть email" 
                              value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= $errors['email'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group mb-4">
                    <label for="phone">Телефон</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" id="phone" name="phone" 
                              placeholder="Введіть номер телефону" 
                              value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Зареєструватися
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <p>Вже маєте обліковий запис?</p>
                <a href="<?= BASE_URL ?>/auth/login" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-in-alt me-2"></i>Увійти
                </a>
            </div>
        </div>
    </div>
</div>