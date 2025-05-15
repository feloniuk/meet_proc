<?php
// views/admin/add_camera.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/cameras" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-plus me-2"></i>Додавання камери</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/addCamera" method="post">
                        <div class="form-group mb-3">
                            <label for="name">Назва камери</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'name') ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'name') ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="url">URL камери</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'url') ?>" 
                                   id="url" 
                                   name="url" 
                                   value="<?= isset($_POST['url']) ? htmlspecialchars($_POST['url']) : '' ?>" 
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
                                            <?= isset($_POST['location']) && $_POST['location'] === $location ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($location) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="new-location" 
                                        <?= isset($_POST['location']) && !in_array($_POST['location'], $locations) && $_POST['location'] !== '' ? 'selected' : '' ?>>
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
                                   value="<?= isset($_POST['new_location']) ? htmlspecialchars($_POST['new_location']) : '' ?>">
                            <small class="form-text text-muted">
                                Назва нового розташування, наприклад: "Виробничий цех 3", "Склад готової продукції"
                            </small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/cameras" class="btn btn-secondary me-2">Скасувати</a>
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
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Підказки</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>URL камери</h6>
                        <p class="small">Підтримуються різні типи URL в залежності від типу камери:</p>
                        <ul class="small">
                            <li>RTSP: rtsp://username:password@camera-ip:port/stream</li>
                            <li>HTTP: http://camera-ip/video.mjpg</li>
                            <li>WebRTC: webrtc://camera-ip/video</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Розташування</h6>
                        <p class="small">Групування камер за розташуванням дозволяє швидко знаходити потрібні камери. Використовуйте існуючі розташування або створюйте нові для консистентності.</p>
                    </div>
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