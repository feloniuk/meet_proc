<?php
// views/technologist/quality_checks.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-clipboard-check me-2"></i>Перевірки якості</h1>
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/technologist/createQualityCheck" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Створити перевірку
            </a>
            <a href="<?= BASE_URL ?>/technologist/qualityReport" class="btn btn-outline-primary">
                <i class="fas fa-chart-bar me-1"></i>Звіт
            </a>
            <a href="<?= BASE_URL ?>/technologist/generateQualityReportPdf<?= 
                !empty($status) ? '?status=' . $status : '' ?><?= 
                !empty($date_from) ? (!empty($status) ? '&' : '?') . 'date_from=' . $date_from : '' ?><?= 
                !empty($date_to) ? (!empty($status) || !empty($date_from) ? '&' : '?') . 'date_to=' . $date_to : '' 
            ?>" target="_blank" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf me-1"></i>PDF
            </a>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Фільтри</h5>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>/technologist/qualityChecks" method="get" class="row">
                <div class="col-md-3 mb-3">
                    <label for="status">Статус:</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">Всі статуси</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Очікує</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Схвалено</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Відхилено</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="date_from">Дата з:</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="date_to">Дата до:</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Застосувати
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?= count(array_filter($checks, function($c) { return $c['status'] === 'pending'; })) ?></h3>
                    <p class="text-muted mb-0">Очікують</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success"><?= count(array_filter($checks, function($c) { return $c['status'] === 'approved'; })) ?></h3>
                    <p class="text-muted mb-0">Схвалено</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-danger"><?= count(array_filter($checks, function($c) { return $c['status'] === 'rejected'; })) ?></h3>
                    <p class="text-muted mb-0">Відхилено</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?= count($checks) ?></h3>
                    <p class="text-muted mb-0">Всього</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Список проверок -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Замовлення</th>
                            <th>Постачальник</th>
                            <th>Дата перевірки</th>
                            <th>Статус</th>
                            <th>Оцінка</th>
                            <th>Технолог</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($checks)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-3">Немає перевірок якості</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($checks as $check): ?>
                                <tr>
                                    <td><?= $check['id'] ?></td>
                                    <td>#<?= $check['order_number'] ?></td>
                                    <td><?= htmlspecialchars($check['supplier_name']) ?></td>
                                    <td><?= Util::formatDate($check['check_date']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $check['status'] === 'pending' ? 'warning' : 
                                            ($check['status'] === 'approved' ? 'success' : 'danger') 
                                        ?>">
                                            <?= Util::getQualityStatusName($check['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($check['overall_grade']): ?>
                                            <span class="badge bg-<?= 
                                                $check['overall_grade'] === 'excellent' ? 'success' :
                                                ($check['overall_grade'] === 'good' ? 'primary' :
                                                ($check['overall_grade'] === 'satisfactory' ? 'warning' : 'danger'))
                                            ?>">
                                                <?= Util::getOverallGradeName($check['overall_grade']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($check['technologist_name']) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/technologist/viewQualityCheck/<?= $check['id'] ?>" 
                                               class="btn btn-outline-info" title="Переглянути">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($check['status'] === 'pending'): ?>
                                                <a href="<?= BASE_URL ?>/technologist/editQualityCheck/<?= $check['id'] ?>" 
                                                   class="btn btn-outline-primary" title="Редагувати">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/technologist/quickApproval/<?= $check['id'] ?>" 
                                                   class="btn btn-outline-warning" title="Швидка перевірка">
                                                    <i class="fas fa-bolt"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($check['status'] === 'approved'): ?>
                                                <a href="<?= BASE_URL ?>/technologist/generateQualityCertificate/<?= $check['id'] ?>" 
                                                   target="_blank" class="btn btn-outline-success" title="Сертифікат">
                                                    <i class="fas fa-certificate"></i>
                                                </a>
                                            <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Автообновление страницы каждые 5 минут для отслеживания новых заказов
    setInterval(function() {
        if (document.hidden === false) {
            // Проверяем, есть ли новые pending проверки
            fetch('<?= BASE_URL ?>/technologist/getReportsData?type=pending_count')
                .then(response => response.json())
                .then(data => {
                    // Если есть новые, можно показать уведомление
                    if (data.new_pending > 0) {
                        // Можно добавить push-уведомление или просто обновить бейдж
                        console.log('Нові перевірки: ' + data.new_pending);
                    }
                });
        }
    }, 300000); // 5 минут
});
</script>