<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-paper-plane me-2"></i>Нове повідомлення</h1>
        <a href="<?= BASE_URL ?>/home/messages" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Назад до списку
        </a>
    </div>
<div class="card shadow-sm">
    <div class="card-body">
        <form action="<?= BASE_URL ?>/home/newMessage" method="post">
            <div class="form-group mb-3">
                <label for="receiver_id">Одержувач</label>
                <select class="form-control <?= isset($errors['receiver_id']) ? 'is-invalid' : '' ?>" 
                       id="receiver_id" name="receiver_id" required>
                    <option value="">Виберіть одержувача</option>
                    <?php foreach ($receivers as $receiver): ?>
                        <?php if ($receiver['id'] != Auth::getCurrentUserId()): ?>
                            <option value="<?= $receiver['id'] ?>" 
                                   <?= isset($_POST['receiver_id']) && $_POST['receiver_id'] == $receiver['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($receiver['name']) ?> 
                                (<?= Util::getUserRoleName($receiver['role']) ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['receiver_id'])): ?>
                    <div class="invalid-feedback"><?= $errors['receiver_id'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group mb-3">
                <label for="subject">Тема</label>
                <input type="text" class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" 
                      id="subject" name="subject" 
                      value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>" 
                      required>
                <?php if (isset($errors['subject'])): ?>
                    <div class="invalid-feedback"><?= $errors['subject'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group mb-4">
                <label for="message">Повідомлення</label>
                <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" 
                         id="message" name="message" rows="6" required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                <?php if (isset($errors['message'])): ?>
                    <div class="invalid-feedback"><?= $errors['message'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="<?= BASE_URL ?>/home/messages" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Скасувати
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i>Надіслати
                </button>
            </div>
        </form>
    </div>
</div>
</div>