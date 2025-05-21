<?php
// views/supplier/reports.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-chart-bar me-2"></i>Звіти</h1>
    </div>

    <div class="row">
        <!-- Звіт по замовленням -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>Звіт по замовленням
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Детальний звіт про всі замовлення за вказаний період. Включає статистику по матеріалах, 
                        статусах замовлень та загальну фінансову інформацію.
                    </p>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="orders_start_date" class="form-label">Дата початку:</label>
                            <input type="date" class="form-control" id="orders_start_date" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="orders_end_date" class="form-label">Дата закінчення:</label>
                            <input type="date" class="form-control" id="orders_end_date" value="<?= date('Y-m-t') ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button onclick="viewOrdersReport()" class="btn btn-primary">
                            <i class="fas fa-eye me-1"></i>Переглянути звіт
                        </button>
                        <button onclick="downloadOrdersReport()" class="btn btn-outline-primary">
                            <i class="fas fa-download me-1"></i>Завантажити PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Звіт по матеріалах -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cubes me-2"></i>Звіт по матеріалах
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Звіт про вашу сировину: список матеріалів, ціни, статистика замовлень та 
                        аналіз попиту на кожен вид сировини.
                    </p>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="materials_start_date" class="form-label">Дата початку:</label>
                            <input type="date" class="form-control" id="materials_start_date" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="materials_end_date" class="form-label">Дата закінчення:</label>
                            <input type="date" class="form-control" id="materials_end_date" value="<?= date('Y-m-t') ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button onclick="viewMaterialsReport()" class="btn btn-success">
                            <i class="fas fa-eye me-1"></i>Переглянути звіт
                        </button>
                        <button onclick="downloadMaterialsReport()" class="btn btn-outline-success">
                            <i class="fas fa-download me-1"></i>Завантажити PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Швидка статистика -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Швидка статистика
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3">
                                <h4 class="text-primary mb-2">
                                    <i class="fas fa-shopping-cart"></i>
                                </h4>
                                <h5 id="total-orders">-</h5>
                                <small class="text-muted">Всього замовлень</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3">
                                <h4 class="text-success mb-2">
                                    <i class="fas fa-check-circle"></i>
                                </h4>
                                <h5 id="completed-orders">-</h5>
                                <small class="text-muted">Виконано замовлень</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3">
                                <h4 class="text-warning mb-2">
                                    <i class="fas fa-cubes"></i>
                                </h4>
                                <h5 id="total-materials">-</h5>
                                <small class="text-muted">Видів сировини</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3">
                                <h4 class="text-info mb-2">
                                    <i class="fas fa-hryvnia"></i>
                                </h4>
                                <h5 id="total-revenue">-</h5>
                                <small class="text-muted">Загальний дохід</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Графіки -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-area me-2"></i>Динаміка замовлень
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Статуси замовлень
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Функции для работы с отчетами
function viewOrdersReport() {
    const startDate = document.getElementById('orders_start_date').value;
    const endDate = document.getElementById('orders_end_date').value;
    
    if (!startDate || !endDate) {
        alert('Будь ласка, оберіть період для звіту');
        return;
    }
    
    window.open(`<?= BASE_URL ?>/supplier/ordersReport?start_date=${startDate}&end_date=${endDate}`, '_blank');
}

function downloadOrdersReport() {
    const startDate = document.getElementById('orders_start_date').value;
    const endDate = document.getElementById('orders_end_date').value;
    
    if (!startDate || !endDate) {
        alert('Будь ласка, оберіть період для звіту');
        return;
    }
    
    window.open(`<?= BASE_URL ?>/supplier/generateOrdersPdf?start_date=${startDate}&end_date=${endDate}`, '_blank');
}

function viewMaterialsReport() {
    const startDate = document.getElementById('materials_start_date').value;
    const endDate = document.getElementById('materials_end_date').value;
    
    if (!startDate || !endDate) {
        alert('Будь ласка, оберіть період для звіту');
        return;
    }
    
    window.open(`<?= BASE_URL ?>/supplier/materialsReport?start_date=${startDate}&end_date=${endDate}`, '_blank');
}

function downloadMaterialsReport() {
    const startDate = document.getElementById('materials_start_date').value;
    const endDate = document.getElementById('materials_end_date').value;
    
    if (!startDate || !endDate) {
        alert('Будь ласка, оберіть periode для звіту');
        return;
    }
    
    window.open(`<?= BASE_URL ?>/supplier/generateMaterialsPdf?start_date=${startDate}&end_date=${endDate}`, '_blank');
}

// Загрузка статистики при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    loadQuickStats();
    loadCharts();
});

function loadQuickStats() {
    // Здесь можно добавить AJAX запрос для получения быстрой статистики
    // Пока используем заглушки
    document.getElementById('total-orders').textContent = '0';
    document.getElementById('completed-orders').textContent = '0';
    document.getElementById('total-materials').textContent = '0';
    document.getElementById('total-revenue').textContent = '0 грн';
}

function loadCharts() {
    // Инициализация графиков
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    
    // График заказов (заглушка)
    new Chart(ordersCtx, {
        type: 'line',
        data: {
            labels: ['Січ', 'Лют', 'Бер', 'Кві', 'Тра', 'Чер'],
            datasets: [{
                label: 'Кількість замовлень',
                data: [0, 0, 0, 0, 0, 0],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // График статусов (заглушка)
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Очікує', 'Прийнято', 'Відправлено', 'Доставлено', 'Скасовано'],
            datasets: [{
                data: [0, 0, 0, 0, 0],
                backgroundColor: [
                    '#ffc107',
                    '#17a2b8',
                    '#007bff',
                    '#28a745',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
</script>

<!-- Подключение Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>