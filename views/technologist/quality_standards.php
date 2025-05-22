<?php
// views/technologist/quality_standards.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-clipboard-list me-2"></i>Стандарти якості</h1>
        <a href="<?= BASE_URL ?>/technologist/addQualityStandard" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Додати стандарт
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?= count($standards) ?></h3>
                    <p class="text-muted mb-0">Всього стандартів</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-danger"><?= count(array_filter($standards, function($s) { return $s['is_critical']; })) ?></h3>
                    <p class="text-muted mb-0">Критичних параметрів</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-info"><?= count(array_unique(array_column($standards, 'raw_material_id'))) ?></h3>
                    <p class="text-muted mb-0">Матеріалів охоплено</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Список стандартів</h5>
            <div class="input-group" style="width: 300px;">
                <input type="text" class="form-control" id="searchStandards" placeholder="Пошук матеріалу...">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="standardsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Матеріал</th>
                            <th>Параметр</th>
                            <th>Мінімум</th>
                            <th>Максимум</th>
                            <th>Одиниця</th>
                            <th>Критичний</th>
                            <th>Опис</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($standards)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-3">Немає стандартів якості</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            // Группируем стандарты по материалам
                            $grouped_standards = [];
                            foreach ($standards as $standard) {
                                $grouped_standards[$standard['material_name']][] = $standard;
                            }
                            ?>
                            <?php foreach ($grouped_standards as $material_name => $material_standards): ?>
                                <?php $first = true; ?>
                                <?php foreach ($material_standards as $standard): ?>
                                    <tr class="standard-row" data-material="<?= htmlspecialchars(strtolower($material_name)) ?>">
                                        <td><?= $standard['id'] ?></td>
                                        <td>
                                            <?php if ($first): ?>
                                                <strong><?= htmlspecialchars($material_name) ?></strong>
                                                <?php $first = false; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($standard['parameter_name']) ?>
                                            <?php if ($standard['is_critical']): ?>
                                                <span class="badge bg-danger ms-1">Критичний</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $standard['min_value'] !== null ? number_format($standard['min_value'], 2) : '-' ?>
                                        </td>
                                        <td>
                                            <?= $standard['max_value'] !== null ? number_format($standard['max_value'], 2) : '-' ?>
                                        </td>
                                        <td><?= htmlspecialchars($standard['unit']) ?></td>
                                        <td>
                                            <?= $standard['is_critical'] ? 
                                                '<span class="badge bg-danger">Так</span>' : 
                                                '<span class="badge bg-secondary">Ні</span>' ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(mb_substr($standard['description'], 0, 50)) ?>
                                            <?= mb_strlen($standard['description']) > 50 ? '...' : '' ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>/technologist/editQualityStandard/<?= $standard['id'] ?>" 
                                                   class="btn btn-outline-primary" title="Редагувати">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/technologist/deleteQualityStandard/<?= $standard['id'] ?>" 
                                                   class="btn btn-outline-danger" title="Видалити"
                                                   onclick="return confirm('Ви впевнені, що хочете видалити цей стандарт?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Помощь по стандартам -->
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="mb-0">Довідка по стандартах якості</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Рекомендовані параметри для м'ясної сировини:</h6>
                    <ul>
                        <li><strong>Температура:</strong> 0-4°C для охолодженого м'яса</li>
                        <li><strong>pH:</strong> 5.3-6.2 для свинини, 5.4-6.0 для яловичини</li>
                        <li><strong>Вологість:</strong> 70-78% для м'яса</li>
                        <li><strong>Чистота солі:</strong> не менше 99%</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Критичні параметри:</h6>
                    <p>Параметри, позначені як "критичні", є обов'язковими для перевірки та можуть бути підставою для автоматичного відхилення сировини при невідповідності нормам.</p>
                    
                    <h6>Одиниці виміру:</h6>
                    <ul>
                        <li>°C - градуси Цельсія</li>
                        <li>% - відсотки</li>
                        <li>pH - показник кислотності</li>
                        <li>мг/кг - міліграми на кілограм</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchStandards');
    const standardRows = document.querySelectorAll('.standard-row');
    
    // Функция фильтрации
    function filterRows() {
        const searchTerm = searchInput.value.toLowerCase();
        
        standardRows.forEach(row => {
            const materialName = row.getAttribute('data-material');
            
            // Показываем или скрываем строку
            row.style.display = materialName.includes(searchTerm) ? '' : 'none';
        });
    }
    
    // Привязка событий
    searchInput.addEventListener('input', filterRows);
});
</script>