<?php
// views/technologist/reports.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-chart-bar me-2"></i>Звіти технолога</h1>
        <a href="<?= BASE_URL ?>/technologist" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>На головну
        </a>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-clipboard-check fa-4x text-primary mb-3"></i>
                    <h5 class="card-title">Звіт по якості сировини</h5>
                    <p class="card-text">Статистика перевірок якості за період</p>
                    <a href="<?= BASE_URL ?>/technologist/qualityReport" class="btn btn-primary">
                        <i class="fas fa-eye me-1"></i>Переглянути
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-4x text-success mb-3"></i>
                    <h5 class="card-title">Статистика за місяць</h5>
                    <p class="card-text">Показники якості за поточний місяць</p>
                    <a href="<?= BASE_URL ?>/technologist/qualityReport?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>" class="btn btn-success">
                        <i class="fas fa-calendar-alt me-1"></i>Цей місяць
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                    <h5 class="card-title">Експорт звітів</h5>
                    <p class="card-text">Генерація PDF звітів для друку</p>
                    <a href="<?= BASE_URL ?>/technologist/generateQualityReportPdf" target="_blank" class="btn btn-danger">
                        <i class="fas fa-download me-1"></i>PDF звіт
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Швидкі звіти</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>/technologist/qualityReport?start_date=<?= date('Y-m-d', strtotime('-7 days')) ?>&end_date=<?= date('Y-m-d') ?>" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-week me-2"></i>Звіт за останній тиждень
                    </a>
                    <a href="<?= BASE_URL ?>/technologist/qualityReport?start_date=<?= date('Y-m-01', strtotime('-1 month')) ?>&end_date=<?= date('Y-m-t', strtotime('-1 month')) ?>" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-history me-2"></i>Звіт за минулий місяць
                    </a>
                    <a href="<?= BASE_URL ?>/technologist/qualityReport?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-12-31') ?>" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar me-2"></i>Звіт за поточний рік
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Спеціальні звіти</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>/technologist/qualityChecks?status=rejected" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-times-circle me-2 text-danger"></i>Відхилені перевірки
                    </a>
                    <a href="<?= BASE_URL ?>/technologist/qualityChecks?status=pending" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-hourglass-half me-2 text-warning"></i>Очікуючі перевірки
                    </a>
                    <a href="<?= BASE_URL ?>/technologist/qualityStandards" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-clipboard-list me-2"></i>Стандарти якості
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Налаштування звітів</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/technologist/qualityReport" method="get">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="start_date">Початкова дата:</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?= date('Y-m-01') ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="end_date">Кінцева дата:</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Сформувати звіт
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Валидация дат
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    function validateDates() {
        if (startDate.value > endDate.value) {
            endDate.setCustomValidity('Кінцева дата повинна бути пізніше початкової');
        } else {
            endDate.setCustomValidity('');
        }
    }
    
    startDate.addEventListener('change', validateDates);
    endDate.addEventListener('change', validateDates);
});
</script>