<?php
// views/admin/reports.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-chart-bar me-2"></i>Звіти</h1>
    </div>

    <div class="row">
        <!-- Звіт по запасам -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-boxes fa-4x text-primary"></i>
                    </div>
                    <h5 class="card-title">Звіт по запасам</h5>
                    <p class="card-text">Перегляд поточних запасів сировини, їх вартості та статусу.</p>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/admin/inventoryReport" class="btn btn-primary">
                            <i class="fas fa-eye me-1"></i>Переглянути звіт
                        </a>
                        <a href="<?= BASE_URL ?>/admin/generateInventoryPdf" class="btn btn-outline-secondary" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>Завантажити PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Звіт по виробництву -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-industry fa-4x text-success"></i>
                    </div>
                    <h5 class="card-title">Звіт по виробництву</h5>
                    <p class="card-text">Статистика виробництва продукції за обраний період.</p>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/admin/productionReport" class="btn btn-success">
                            <i class="fas fa-eye me-1"></i>Переглянути звіт
                        </a>
                        <a href="<?= BASE_URL ?>/admin/generateProductionPdf" class="btn btn-outline-secondary" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>Завантажити PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Звіт по замовленнях -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-shopping-cart fa-4x text-info"></i>
                    </div>
                    <h5 class="card-title">Звіт по замовленнях</h5>
                    <p class="card-text">Інформація про замовлення сировини та постачальників.</p>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/admin/ordersReport" class="btn btn-info text-white">
                            <i class="fas fa-eye me-1"></i>Переглянути звіт
                        </a>
                        <a href="<?= BASE_URL ?>/admin/generateOrdersPdf" class="btn btn-outline-secondary" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>Завантажити PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h4 mb-3 mt-2"><i class="fas fa-calendar-alt me-2"></i>Швидкі звіти за період</h2>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/customReport" method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="report_type" class="form-label">Тип звіту</label>
                            <select name="report_type" id="report_type" class="form-select">
                                <option value="production">Виробництво</option>
                                <option value="orders">Замовлення</option>
                                <option value="materials">Використання сировини</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Дата початку</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">Дата кінця</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Сформувати
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-6">
            <!-- Популярні періоди для швидкого доступу -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Популярні періоди</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h6>Виробництво:</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <a href="<?= BASE_URL ?>/admin/productionReport?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-d') ?>">
                                        <i class="fas fa-calendar-day me-1"></i>Поточний місяць
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= BASE_URL ?>/admin/productionReport?start_date=<?= date('Y-m-01', strtotime('-1 month')) ?>&end_date=<?= date('Y-m-t', strtotime('-1 month')) ?>">
                                        <i class="fas fa-calendar-week me-1"></i>Минулий місяць
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= BASE_URL ?>/admin/productionReport?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-m-d') ?>">
                                        <i class="fas fa-calendar me-1"></i>Поточний рік
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="col-6">
                            <h6>Замовлення:</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <a href="<?= BASE_URL ?>/admin/ordersReport?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-d') ?>">
                                        <i class="fas fa-calendar-day me-1"></i>Поточний місяць
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= BASE_URL ?>/admin/ordersReport?start_date=<?= date('Y-m-01', strtotime('-1 month')) ?>&end_date=<?= date('Y-m-t', strtotime('-1 month')) ?>">
                                        <i class="fas fa-calendar-week me-1"></i>Минулий місяць
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= BASE_URL ?>/admin/ordersReport?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-m-d') ?>">
                                        <i class="fas fa-calendar me-1"></i>Поточний рік
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <!-- Підказки та інформація -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Підказки</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Формат PDF</h6>
                        <p class="small">Завантажені звіти у форматі PDF можна роздрукувати або зберегти для подальшого використання.</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Періоди звітів</h6>
                        <p class="small">Для більш детального аналізу рекомендуємо використовувати порівняння звітів за різні періоди.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
