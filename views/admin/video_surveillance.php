<?php
// views/admin/video_surveillance.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-video me-2"></i>Відеоспостереження</h1>
        <div>
            <a href="<?= BASE_URL ?>/admin/cameras" class="btn btn-outline-primary me-2">
                <i class="fas fa-cog me-1"></i>Управління камерами
            </a>
            <a href="<?= BASE_URL ?>/admin/addCamera" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Додати камеру
            </a>
        </div>
    </div>

    <?php if (empty($cameras)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Немає доступних активних камер. <a href="<?= BASE_URL ?>/admin/addCamera">Додайте нову камеру</a> для початку відеоспостереження.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($cameras as $camera): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($camera['name']) ?></h5>
                            <span class="badge bg-primary"><?= htmlspecialchars($camera['location']) ?></span>
                        </div>
                        <div class="card-body p-0">
                            <!-- Контейнер для відеопотоку -->
                            <div class="video-container">
                                <!-- 
                                    Тут реалізація відео залежить від типу камер та протоколу.
                                    Для тестової демонстрації використовуємо зображення-плейсхолдер.
                                    В реальному проекті тут може бути підключення до RTSP, WebRTC та ін.
                                -->
                                <div class="position-relative" style="height: 300px; background-color: #000;">
                                    <!-- Плейсхолдер для відео -->
                                    <div class="d-flex justify-content-center align-items-center h-100">
                                        <div class="text-center text-light">
                                            <i class="fas fa-camera fa-3x mb-3"></i>
                                            <p class="mb-0">Відеопотік: <?= htmlspecialchars($camera['name']) ?></p>
                                            <p class="small text-muted"><?= htmlspecialchars($camera['url']) ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Інформація про відео -->
                                    <div class="position-absolute bottom-0 start-0 p-2 bg-dark bg-opacity-50 text-white" style="width: 100%;">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <i class="fas fa-circle text-danger me-1"></i>
                                                <span class="small">LIVE</span>
                                            </div>
                                            <div class="small">
                                                <i class="fas fa-clock me-1"></i>
                                                <span id="camera-time-<?= $camera['id'] ?>"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <div>
                                <button class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="fas fa-volume-mute"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-pause"></i>
                                </button>
                            </div>
                            <div>
                                <a href="<?= BASE_URL ?>/admin/editCamera/<?= $camera['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-cog me-1"></i>Налаштування
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Оновлення часу для кожної камери
function updateCameraTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString();
    
    <?php foreach ($cameras as $camera): ?>
    document.getElementById('camera-time-<?= $camera['id'] ?>').textContent = timeStr;
    <?php endforeach; ?>
}

// Оновлення часу кожну секунду
setInterval(updateCameraTime, 1000);
updateCameraTime(); // Початкове оновлення
</script>
