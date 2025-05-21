<?php
// views/admin/video_surveillance.php - Оновлена версія

?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-video me-2"></i>Відеоспостереження</h1>
        <div>
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
                                                <span id="camera-time-<?= $camera['id'] ?>"><?= date('H:i:s') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <div>
                                <button class="btn btn-sm btn-outline-secondary me-1" onclick="toggleFullScreen(this)">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="fas fa-volume-mute"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" id="pauseBtn-<?= $camera['id'] ?>" onclick="togglePause(<?= $camera['id'] ?>)">
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
    const timeElement = document.getElementById('camera-time-<?= $camera['id'] ?>');
    if (timeElement && !timeElement.dataset.paused) {
        timeElement.textContent = timeStr;
    }
    <?php endforeach; ?>
}

// Перемикання режиму паузи
function togglePause(cameraId) {
    const timeElement = document.getElementById('camera-time-' + cameraId);
    const pauseBtn = document.getElementById('pauseBtn-' + cameraId);
    
    if (timeElement.dataset.paused) {
        // Відновлення відео
        delete timeElement.dataset.paused;
        pauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
    } else {
        // Пауза відео
        timeElement.dataset.paused = "true";
        pauseBtn.innerHTML = '<i class="fas fa-play"></i>';
    }
}

// Повноекранний режим для відео
function toggleFullScreen(button) {
    const videoContainer = button.closest('.card').querySelector('.video-container');
    
    if (!document.fullscreenElement) {
        if (videoContainer.requestFullscreen) {
            videoContainer.requestFullscreen();
        } else if (videoContainer.webkitRequestFullscreen) { /* Safari */
            videoContainer.webkitRequestFullscreen();
        } else if (videoContainer.msRequestFullscreen) { /* IE11 */
            videoContainer.msRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) { /* Safari */
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) { /* IE11 */
            document.msExitFullscreen();
        }
    }
}

// Оновлення часу кожну секунду
setInterval(updateCameraTime, 1000);
updateCameraTime(); // Початкове оновлення
</script>