<div class="auth-container">
    <div class="card auth-card shadow">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <i class="fas fa-utensils auth-icon"></i>
                <h3 class="card-title">Вхід до системи</h3>
                <p class="text-muted">Введіть ваші облікові дані</p>
            </div>
            
            <form action="<?= BASE_URL ?>/auth/login" method="post">
                <?php if (isset($errors['auth'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $errors['auth'] ?>
                    </div>
                <?php endif; ?>
                
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
                </div>
                
                <div class="form-group mb-4">
                    <label for="password">Пароль</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                              id="password" name="password" placeholder="Введіть пароль">
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= $errors['password'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Увійти
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <p>Ви постачальник і ще не маєте облікового запису?</p>
                <a href="<?= BASE_URL ?>/auth/register" class="btn btn-outline-secondary">
                    <i class="fas fa-user-plus me-2"></i>Зареєструватися
                </a>
            </div>
        </div>
    </div>
</div>