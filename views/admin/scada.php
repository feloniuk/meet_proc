<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA - Моніторинг виробництва</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .scada-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .parameter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #007bff;
        }
        .parameter-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .parameter-name {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .parameter-time {
            color: #28a745;
            font-size: 0.9rem;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
        }
        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            animation: pulse 2s infinite;
        }
        .status-normal { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-critical { background-color: #dc3545; }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        .data-table {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">

<?php
// Подключение к базе данных
require_once 'config/config.php';

// Проверка доступа админа
if (!Auth::isLoggedIn() || !Auth::hasRole('admin')) {
    header('Location: ' . BASE_URL . '/auth/login');
    exit;
}

// Получение данных из базы
$db = Database::getInstance();

// Получение последних данных для каждого параметра
$latestDataSql = "SELECT d1.* FROM data d1 
                  INNER JOIN (
                      SELECT Name, MAX(ID) as MaxID 
                      FROM data 
                      GROUP BY Name
                  ) d2 ON d1.Name = d2.Name AND d1.ID = d2.MaxID
                  ORDER BY d1.Name";

$latestData = $db->resultSet($latestDataSql);

// Получение данных для графика (последние 50 записей для каждого параметра)
$chartDataSql = "SELECT * FROM (
                    SELECT * FROM data 
                    WHERE Name = 'Температура у камері копчення ковбаси'
                    ORDER BY ID DESC 
                    LIMIT 50
                 ) AS subquery 
                 ORDER BY ID ASC";

$chartData = $db->resultSet($chartDataSql);

// Получение всех уникальных параметров
$parametersSql = "SELECT DISTINCT Name FROM data ORDER BY Name";
$parameters = $db->resultSet($parametersSql);

// Обработка AJAX запросов
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] === 'latest') {
        echo json_encode($latestData);
        exit;
    }
    
    if ($_GET['ajax'] === 'chart' && isset($_GET['parameter'])) {
        $parameter = $_GET['parameter'];
        $chartSql = "SELECT * FROM (
                        SELECT * FROM data 
                        WHERE Name = ?
                        ORDER BY ID DESC 
                        LIMIT 50
                     ) AS subquery 
                     ORDER BY ID ASC";
        
        $data = $db->resultSet($chartSql, [$parameter]);
        echo json_encode($data);
        exit;
    }
}

// Функция для определения статуса параметра
function getParameterStatus($name, $value) {
    if (strpos($name, 'Температура') !== false) {
        if ($value >= 45 && $value <= 55) return 'normal';
        if ($value >= 40 && $value <= 60) return 'warning';
        return 'critical';
    }
    return 'normal';
}

// Функция для форматирования даты и времени
function formatDateTime($date, $time) {
    return date('d.m.Y H:i:s', strtotime($date . ' ' . $time));
}
?>

<div class="scada-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-chart-line me-3"></i>
                    SCADA - Система моніторингу виробництва
                </h1>
                <p class="mb-0 mt-2">Контроль технологічних параметрів у реальному часі</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex align-items-center justify-content-end">
                    <span class="status-indicator status-normal"></span>
                    <span>Система активна</span>
                </div>
                <small>Останнє оновлення: <span id="lastUpdate"><?= date('d.m.Y H:i:s') ?></span></small>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Панель з поточними показниками -->
        <div class="col-md-4">
            <h4 class="mb-3">
                <i class="fas fa-tachometer-alt me-2"></i>
                Поточні показники
            </h4>
            
            <div id="parametersContainer">
                <?php foreach ($latestData as $data): ?>
                    <?php $status = getParameterStatus($data['Name'], $data['Parameter']); ?>
                    <div class="parameter-card">
                        <div class="parameter-name">
                            <span class="status-indicator status-<?= $status ?>"></span>
                            <?= htmlspecialchars($data['Name']) ?>
                        </div>
                        <div class="parameter-value">
                            <?= $data['Parameter'] ?>°C
                        </div>
                        <div class="parameter-time">
                            <i class="fas fa-clock me-1"></i>
                            <?= formatDateTime($data['Dates'], $data['Times']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- График -->
        <div class="col-md-8">
            <div class="chart-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-area me-2"></i>
                        Графік зміни параметрів
                    </h4>
                    <div>
                        <select id="parameterSelect" class="form-select" style="width: auto; display: inline-block;">
                            <?php foreach ($parameters as $param): ?>
                                <option value="<?= htmlspecialchars($param['Name']) ?>" 
                                        <?= $param['Name'] === 'Температура у камері копчення ковбаси' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($param['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary btn-sm ms-2" onclick="refreshChart()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="parametersChart"></canvas>
                </div>
            </div>
            
            <!-- Таблица с последними данными -->
            <div class="chart-container">
                <h5 class="mb-3">
                    <i class="fas fa-table me-2"></i>
                    Останні записи
                </h5>
                <div class="data-table">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Параметр</th>
                                <th>Значення</th>
                                <th>Дата</th>
                                <th>Час</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody id="dataTable">
                            <?php 
                            $recentDataSql = "SELECT * FROM data ORDER BY ID DESC LIMIT 20";
                            $recentData = $db->resultSet($recentDataSql);
                            ?>
                            <?php foreach ($recentData as $row): ?>
                                <?php $status = getParameterStatus($row['Name'], $row['Parameter']); ?>
                                <tr>
                                    <td><?= $row['ID'] ?></td>
                                    <td><?= htmlspecialchars($row['Name']) ?></td>
                                    <td><strong><?= $row['Parameter'] ?>°C</strong></td>
                                    <td><?= $row['Dates'] ?></td>
                                    <td><?= $row['Times'] ?></td>
                                    <td>
                                        <span class="status-indicator status-<?= $status ?>"></span>
                                        <?php 
                                        switch($status) {
                                            case 'normal': echo 'Норма'; break;
                                            case 'warning': echo 'Попередження'; break;
                                            case 'critical': echo 'Критично'; break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Кнопка обновления -->
<button class="btn btn-primary btn-lg refresh-btn" onclick="refreshData()" title="Оновити дані">
    <i class="fas fa-sync-alt"></i>
</button>

<script>
let chart;
let refreshInterval;

// Инициализация графика
function initChart() {
    const ctx = document.getElementById('parametersChart').getContext('2d');
    
    const chartData = <?= json_encode($chartData) ?>;
    
    const labels = chartData.map(item => {
        const date = new Date(item.Dates.split('.').reverse().join('-') + ' ' + item.Times);
        return date.toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'});
    });
    
    const data = chartData.map(item => parseFloat(item.Parameter));
    
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Температура (°C)',
                data: data,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Температура (°C)'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Час'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

// Обновление графика
function refreshChart() {
    const selectedParameter = document.getElementById('parameterSelect').value;
    
    fetch(`?ajax=chart&parameter=${encodeURIComponent(selectedParameter)}`)
        .then(response => response.json())
        .then(data => {
            const labels = data.map(item => {
                const date = new Date(item.Dates.split('.').reverse().join('-') + ' ' + item.Times);
                return date.toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'});
            });
            
            const values = data.map(item => parseFloat(item.Parameter));
            
            chart.data.labels = labels;
            chart.data.datasets[0].data = values;
            chart.data.datasets[0].label = selectedParameter.includes('Температура') ? 'Температура (°C)' : selectedParameter;
            chart.update();
        })
        .catch(error => {
            console.error('Помилка при оновленні графіка:', error);
        });
}

// Обновление данных параметров
function refreshData() {
    const refreshBtn = document.querySelector('.refresh-btn i');
    refreshBtn.classList.add('fa-spin');
    
    fetch('?ajax=latest')
        .then(response => response.json())
        .then(data => {
            updateParametersDisplay(data);
            updateLastUpdateTime();
            refreshBtn.classList.remove('fa-spin');
        })
        .catch(error => {
            console.error('Помилка при оновленні даних:', error);
            refreshBtn.classList.remove('fa-spin');
        });
}

// Обновление отображения параметров
function updateParametersDisplay(data) {
    const container = document.getElementById('parametersContainer');
    container.innerHTML = '';
    
    data.forEach(item => {
        const status = getParameterStatusJS(item.Name, item.Parameter);
        const cardHtml = `
            <div class="parameter-card">
                <div class="parameter-name">
                    <span class="status-indicator status-${status}"></span>
                    ${item.Name}
                </div>
                <div class="parameter-value">
                    ${item.Parameter}°C
                </div>
                <div class="parameter-time">
                    <i class="fas fa-clock me-1"></i>
                    ${formatDateTimeJS(item.Dates, item.Times)}
                </div>
            </div>
        `;
        container.innerHTML += cardHtml;
    });
}

// JavaScript версия функции определения статуса
function getParameterStatusJS(name, value) {
    if (name.includes('Температура')) {
        if (value >= 45 && value <= 55) return 'normal';
        if (value >= 40 && value <= 60) return 'warning';
        return 'critical';
    }
    return 'normal';
}

// JavaScript версия форматирования даты
function formatDateTimeJS(date, time) {
    return new Date(date.split('.').reverse().join('-') + ' ' + time)
        .toLocaleString('uk-UA', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
}

// Обновление времени последнего обновления
function updateLastUpdateTime() {
    document.getElementById('lastUpdate').textContent = 
        new Date().toLocaleString('uk-UA', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initChart();
    
    // Автоматическое обновление каждые 30 секунд
    refreshInterval = setInterval(() => {
        refreshData();
        refreshChart();
    }, 30000);
    
    // Обработчик изменения параметра
    document.getElementById('parameterSelect').addEventListener('change', refreshChart);
});

// Очистка интервала при уходе со страницы
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>