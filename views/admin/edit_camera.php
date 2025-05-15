<?php
// views/admin/edit_camera.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/cameras" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>Редагування камери</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/editCamera/<?= $camera['id'] ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="name">Назва камери</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'name') ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($camera['name']) ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'name') ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="url">URL камери</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'url') ?>" 
                                   id="url" 
                                   name="url" 
                                   value="<?= htmlspecialchars($camera['url']) ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'url') ?>
                            <small class="form-text text-muted">
                                Наприклад: rtsp://camera.local:554/stream, http://camera.local/video
                            </small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="location">Розташування</label>
                            <select class="form-select <?= Util::getErrorClass($errors, 'location') ?>" 
                                   id="location" 
                                   name="location" 
                                   required>
                                <option value="">Виберіть розташування</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= htmlspecialchars($location) ?>" 
                                            <?= $camera['location'] === $location ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($location) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="new-location" 
                                        <?= !in_array($camera['location'], $locations) ? 'selected' : '' ?>>
                                    Нове розташування
                                </option>
                            </select>
                            <?= Util::getErrorMessage($errors, 'location') ?>
                        </div>

                        <div id="new-location-container" class="form-group mb-3" style="display: none;">
                            <label for="new_location">Нове розташування</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="new_location" 
                                   name="new_location" 
                                   value="<?= !in_array($camera['location'], $locations) ? htmlspecialchars($camera['location']) : '' ?>">
                            <small class="form-text text-muted">
                                Назва нового розташування, наприклад: "Виробничий цех 3", "Склад готової продукції"
                            </small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="status">Статус</label>
                            <select class="form-select <?= Util::getErrorClass($errors, 'status') ?>" 
                                   id="status" 
                                   name="status" 
                                   required>
                                <option value="active" <?= $camera['status'] === 'active' ? 'selected' : '' ?>>Активна</option>
                                <option value="inactive" <?= $camera['status'] === 'inactive' ? 'selected' : '' ?>>Неактивна</option>
                            </select>
                            <?= Util::getErrorMessage($errors, 'status') ?>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/cameras" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Зберегти зміни
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Інформація про камеру</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?= $camera['id'] ?></p>
                    <p><strong>Дата додавання:</strong> <?= Util::formatDate($camera['created_at'], 'd.m.Y H:i') ?></p>
                    <p>
                        <strong>Статус:</strong>
                        <span class="badge bg-<?= $camera['status'] === 'active' ? 'success' : 'danger' ?>">
                            <?= $camera['status'] === 'active' ? 'Активна' : 'Неактивна' ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Перегляд камери</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Превью камери -->
                    <div style="height: 180px; background-color: #000;" class="d-flex justify-content-center align-items-center">
                        <?php if ($camera['status'] === 'active'): ?>
                            <div class="text-center text-light">
                                <i class="fas fa-camera fa-2x mb-2"></i>
                                <p class="mb-0 small">Превью камери</p>
                                <p class="mb-0 small text-muted"><?= htmlspecialchars($camera['name']) ?></p>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-light">
                                <i class="fas fa-camera-slash fa-2x mb-2"></i>
                                <p class="mb-0 small">Камера неактивна</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?= BASE_URL ?>/admin/videoSurveillance" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-video me-1"></i>Відкрити відеоспостереження
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationSelect = document.getElementById('location');
    const newLocationContainer = document.getElementById('new-location-container');
    const newLocationInput = document.getElementById('new_location');
    
    function toggleNewLocation() {
        if (locationSelect.value === 'new-location') {
            newLocationContainer.style.display = 'block';
            newLocationInput.setAttribute('required', 'required');
        } else {
            newLocationContainer.style.display = 'none';
            newLocationInput.removeAttribute('required');
        }
    }
    
    locationSelect.addEventListener('change', toggleNewLocation);
    toggleNewLocation(); // Ініціалізація при завантаженні сторінки
});
</script>