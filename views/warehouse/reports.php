<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-chart-bar me-2"></i>Звіти</h1>
    </div>
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <i class="fas fa-boxes fa-5x text-primary mb-3"></i>
                <h4 class="card-title">Звіт по запасам</h4>
                <p class="card-text">
                    Перегляд поточного стану запасів на складі, аналіз критичних позицій та загальної вартості сировини.
                </p>
                <div class="mt-4">
                    <a href="<?= BASE_URL ?>/warehouse/inventoryReport" class="btn btn-primary me-2">
                        <i class="fas fa-chart-bar me-1"></i>Переглянути
                    </a>
                    <a href="<?= BASE_URL ?>/warehouse/generateInventoryPdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i>Експорт в PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <i class="fas fa-industry fa-5x text-primary mb-3"></i>
                <h4 class="card-title">Звіт по виробництву</h4>
                <p class="card-text">
                    Аналіз виробничих процесів за обраний період, статистика по виготовленим продуктам та витратам ресурсів.
                </p>
                <div class="mt-4">
                    <a href="<?= BASE_URL ?>/warehouse/productionReport" class="btn btn-primary me-2">
                        <i class="fas fa-chart-bar me-1"></i>Переглянути
                    </a>
                    <a href="<?= BASE_URL ?>/warehouse/generateProductionPdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i>Експорт в PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Генерація спеціальних звітів</h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/warehouse/customReport" method="get" class="row">
                    <div class="col-md-3 mb-3">
                        <label for="report_type">Тип звіту:</label>
                        <select class="form-control" id="report_type" name="report_type">
                            <option value="inventory">Інвентаризація</option>
                            <option value="production">Виробництво</option>
                            <option value="materials">Використання сировини</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="start_date">Початкова дата:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= date('Y-m-01') ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="end_date">Кінцева дата:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= date('Y-m-t') ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="format">Формат:</label>
                        <select class="form-control" id="format" name="format">
                            <option value="html">HTML</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-bar me-1"></i>Згенерувати звіт
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Доступні шаблони звітів</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Назва звіту</th>
                        <th>Опис</th>
                        <th>Період</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Щотижневий звіт по запасам</td>
                        <td>Перегляд динаміки запасів за тиждень</td>
                        <td>Щотижня</td>
                        <td>
                            <a href="<?= BASE_URL ?>/warehouse/inventoryReport?period=week" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-chart-bar me-1"></i>Переглянути
                            </a>
                            <a href="<?= BASE_URL ?>/warehouse/generateInventoryPdf?period=week" target="_blank" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-file-pdf me-1"></i>PDF
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>Щомісячний звіт по виробництву</td>
                        <td>Статистика виробництва за місяць</td>
                        <td>Щомісяця</td>
                        <td>
                            <a href="<?= BASE_URL ?>/warehouse/productionReport?period=month" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-chart-bar me-1"></i>Переглянути
                            </a>
                            <a href="<?= BASE_URL ?>/warehouse/generateProductionPdf?period=month" target="_blank" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-file-pdf me-1"></i>PDF
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>Критичні запаси</td>
                        <td>Сировина з кількістю нижче мінімального запасу</td>
                        <td>Щоденно</td>
                        <td>
                            <a href="<?= BASE_URL ?>/warehouse/inventoryReport?filter=low" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-chart-bar me-1"></i>Переглянути
                            </a>
                            <a href="<?= BASE_URL ?>/warehouse/generateInventoryPdf?filter=low" target="_blank" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-file-pdf me-1"></i>PDF
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>