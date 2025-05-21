<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Инициализируем переменные, если они не установлены
$active_processes = $active_processes ?? [];
$all_processes = $all_processes ?? [];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-industry me-2"></i>Управління виробництвом</h1>
        <div>
            <a href="<?= BASE_URL ?>/warehouse/planProduction" class="btn btn-success me-2">
                <i class="fas fa-plus me-1"></i>Запланувати виробництво
            </a>
            <a href="<?= BASE_URL ?>/warehouse/productionReport" class="btn btn-outline-primary me-2">
                <i class="fas fa-chart-bar me-1"></i>Звіт по виробництву
            </a>
            <a href="<?= BASE_URL ?>/warehouse/generateProductionPdf" target="_blank" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf me-1"></i>Експорт в PDF
            </a>
        </div>
    </div>
<ul class="nav nav-tabs mb-4" id="productionTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
            <i class="fas fa-play-circle me-1"></i>Активні процеси
            <?php if (count($active_processes) > 0): ?>
            <span class="badge bg-primary"><?= count($active_processes) ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="false">
            <i class="fas fa-list me-1"></i>Всі процеси
        </button>
    </li>
</ul>

<div class="tab-content" id="productionTabContent">
    <!-- Активные процессы -->
    <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Продукт</th>
                                <th>Кількість</th>
                                <th>Статус</th>
                                <th>Дата початку</th>
                                <th>Відповідальний</th>
                                <th>Примітки</th>
                                <th>Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($active_processes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-3">Немає активних виробничих процесів</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($active_processes as $process): ?>
                                    <tr>
                                        <td><?= $process['id'] ?></td>
                                        <td><?= htmlspecialchars($process['product_name']) ?></td>
                                        <td><?= number_format($process['quantity'], 2) ?></td>
                                        <td>
                                            <?php if ($process['status'] === 'planned'): ?>
                                                <span class="badge status-planned">Заплановано</span>
                                            <?php elseif ($process['status'] === 'in_progress'): ?>
                                                <span class="badge status-in_progress">У процесі</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= Util::formatDate($process['started_at']) ?></td>
                                        <td><?= htmlspecialchars($process['manager_name']) ?></td>
                                        <td><?= htmlspecialchars(mb_substr($process['notes'], 0, 30)) . (mb_strlen($process['notes']) > 30 ? '...' : '') ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>/warehouse/viewProduction/<?= $process['id'] ?>" 
                                                   class="btn btn-outline-info" title="Переглянути">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($process['status'] === 'planned'): ?>
                                                    <a href="<?= BASE_URL ?>/warehouse/startProduction/<?= $process['id'] ?>" 
                                                       class="btn btn-outline-primary" title="Розпочати"
                                                       onclick="return confirm('Ви впевнені, що хочете розпочати виробничий процес?');">
                                                        <i class="fas fa-play"></i>
                                                    </a>
                                                <?php elseif ($process['status'] === 'in_progress'): ?>
                                                    <a href="<?= BASE_URL ?>/warehouse/completeProduction/<?= $process['id'] ?>" 
                                                       class="btn btn-outline-success" title="Завершити"
                                                       onclick="return confirm('Ви впевнені, що хочете завершити виробничий процес?');">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="<?= BASE_URL ?>/warehouse/cancelProduction/<?= $process['id'] ?>" 
                                                   class="btn btn-outline-danger" title="Скасувати"
                                                   onclick="return confirm('Ви впевнені, що хочете скасувати виробничий процес?');">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Все процессы -->
    <div class="tab-pane fade" id="all" role="tabpanel" aria-labelledby="all-tab">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Всі виробничі процеси</h5>
                <div class="input-group" style="width: 300px;">
                    <input type="text" class="form-control" id="searchProcess" placeholder="Пошук...">
                    <button class="btn btn-outline-secondary" type="button" id="searchButton">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="allProcessesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Продукт</th>
                                <th>Кількість</th>
                                <th>Статус</th>
                                <th>Дата початку</th>
                                <th>Дата завершення</th>
                                <th>Відповідальний</th>
                                <th>Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_processes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-3">Немає виробничих процесів</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_processes as $process): ?>
                                    <tr class="process-row" data-name="<?= htmlspecialchars(strtolower($process['product_name'])) ?>">
                                        <td><?= $process['id'] ?></td>
                                        <td><?= htmlspecialchars($process['product_name']) ?></td>
                                        <td><?= number_format($process['quantity'], 2) ?></td>
                                        <td>
                                            <?php if ($process['status'] === 'planned'): ?>
                                                <span class="badge status-planned">Заплановано</span>
                                            <?php elseif ($process['status'] === 'in_progress'): ?>
                                                <span class="badge status-in_progress">У процесі</span>
                                            <?php elseif ($process['status'] === 'completed'): ?>
                                                <span class="badge status-completed">Завершено</span>
                                            <?php elseif ($process['status'] === 'canceled'): ?>
                                                <span class="badge status-canceled">Скасовано</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= Util::formatDate($process['started_at']) ?></td>
                                        <td><?= $process['completed_at'] ? Util::formatDate($process['completed_at']) : '-' ?></td>
                                        <td><?= htmlspecialchars($process['manager_name']) ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/warehouse/viewProduction/<?= $process['id'] ?>" 
                                               class="btn btn-sm btn-outline-info" title="Переглянути">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchProcess');
        const processRows = document.querySelectorAll('.process-row');
        
        // Функция фильтрации
        function filterRows() {
            const searchTerm = searchInput.value.toLowerCase();
            
            processRows.forEach(row => {
                const name = row.getAttribute('data-name');
                
                // Показываем или скрываем строку
                row.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        }
        
        // Привязка событий
        searchInput.addEventListener('input', filterRows);
        document.getElementById('searchButton').addEventListener('click', filterRows);
    });
</script>