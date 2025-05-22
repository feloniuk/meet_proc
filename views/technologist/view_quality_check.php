<?php
// views/technologist/view_quality_check.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-microscope me-2"></i>Перевірка якості #<?= $check['id'] ?></h1>
        
        <div class="ms-auto">
            <?php if ($check['status'] === 'pending'): ?>
                <a href="<?= BASE_URL ?>/technologist/editQualityCheck/<?= $check['id'] ?>" class="btn btn-primary me-2">
                    <i class="fas fa-edit me-1"></i>Редагувати
                </a>
                <a href="<?= BASE_URL ?>/technologist/quickApproval/<?= $check['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-bolt me-1"></i>Швидка перевірка
                </a>
            <?php elseif ($check['status'] === 'approved'): ?>
                <a href="<?= BASE_URL ?>/technologist/generateQualityCertificate/<?= $check['id'] ?>" 
                   target="_blank" class="btn btn-success">
                    <i class="fas fa-certificate me-1"></i>Сертифікат якості
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Информация о заказе -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Інформація про замовлення</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Замовлення:</th>
                            <td>#<?= $check['order_number'] ?></td>
                        </tr>
                        <tr>
                            <th>Постачальник:</th>
                            <td><?= htmlspecialchars($check['supplier_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Дата доставки:</th>
                            <td><?= $check['delivery_date'] ? date('d.m.Y', strtotime($check['delivery_date'])) : '-' ?></td>
                        </tr>
                        <tr>
                            <th>Загальна сума:</th>
                            <td><?= Util::formatMoney($check['total_amount']) ?></td>
                        </tr>
                        <tr>
                            <th>Статус перевірки:</th>
                            <td>
                                <span class="badge bg-<?= 
                                    $check['status'] === 'pending' ? 'warning' : 
                                    ($check['status'] === 'approved' ? 'success' : 'danger') 
                                ?> fs-6">
                                    <?= Util::getQualityStatusName($check['status']) ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Позиции заказа -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Позиції замовлення</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Сировина</th>
                                    <th>Кількість</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['material_name']) ?></td>
                                        <td><?= number_format($item['quantity'], 2) ?> <?= $item['unit'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Результаты проверки -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Результати перевірки</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Дата перевірки:</th>
                            <td><?= Util::formatDate($check['check_date']) ?></td>
                        </tr>
                        <tr>
                            <th>Технолог:</th>
                            <td><?= htmlspecialchars($check['technologist_name']) ?></td>
                        </tr>
                        <?php if ($check['temperature']): ?>
                        <tr>
                            <th>Температура:</th>
                            <td>
                                <?= Util::formatQualityParameter($check['temperature'], '°C') ?>
                                <?php if ($check['temperature'] >= 0 && $check['temperature'] <= 4): ?>
                                    <i class="fas fa-check-circle text-success ms-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle text-warning ms-1"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($check['ph_level']): ?>
                        <tr>
                            <th>Рівень pH:</th>
                            <td>
                                <?= Util::formatQualityParameter($check['ph_level']) ?>
                                <?php if ($check['ph_level'] >= 5.5 && $check['ph_level'] <= 6.5): ?>
                                    <i class="fas fa-check-circle text-success ms-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle text-warning ms-1"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($check['moisture_content']): ?>
                        <tr>
                            <th>Вологість:</th>
                            <td><?= Util::formatQualityParameter($check['moisture_content'], '%') ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($check['visual_assessment']): ?>
                        <tr>
                            <th>Візуальна оцінка:</th>
                            <td><?= $check['visual_assessment'] ?>/5</td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($check['smell_assessment']): ?>
                        <tr>
                            <th>Оцінка запаху:</th>
                            <td><?= $check['smell_assessment'] ?>/5</td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($check['overall_grade']): ?>
                        <tr>
                            <th>Загальна оцінка:</th>
                            <td>
                                <span class="badge bg-<?= 
                                    $check['overall_grade'] === 'excellent' ? 'success' :
                                    ($check['overall_grade'] === 'good' ? 'primary' :
                                    ($check['overall_grade'] === 'satisfactory' ? 'warning' : 'danger'))
                                ?>">
                                    <?= Util::getOverallGradeName($check['overall_grade']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <?php if ($check['notes']): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Примітки</h5>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($check['notes'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($check['rejection_reason']): ?>
            <div class="card shadow-sm mt-3 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Причина відхилення</h5>
                </div>
                <div class="card-body">
                    <p class="text-danger"><?= nl2br(htmlspecialchars($check['rejection_reason'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Стандарты и рекомендации -->
        <div class="col-md-4 mb-4">
            <?php if (!empty($standards)): ?>
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Стандарти якості</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Матеріал</th>
                                    <th>Параметр</th>
                                    <th>Норма</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($standards as $standard): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($standard['material_name']) ?></td>
                                        <td><?= htmlspecialchars($standard['parameter_name']) ?></td>
                                        <td>
                                            <?php if ($standard['min_value'] && $standard['max_value']): ?>
                                                <?= $standard['min_value'] ?> - <?= $standard['max_value'] ?> <?= $standard['unit'] ?>
                                            <?php elseif ($standard['min_value']): ?>
                                                ≥ <?= $standard['min_value'] ?> <?= $standard['unit'] ?>
                                            <?php elseif ($standard['max_value']): ?>
                                                ≤ <?= $standard['max_value'] ?> <?= $standard['unit'] ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                                $value = null;
                                                if (strtolower($standard['parameter_name']) === 'температура') {
                                                    $value = $check['temperature'];
                                                } elseif (strtolower($standard['parameter_name']) === 'ph') {
                                                    $value = $check['ph_level'];
                                                } elseif (strpos(strtolower($standard['parameter_name']), 'влажность') !== false) {
                                                    $value = $check['moisture_content'];
                                                }
                                                
                                                if ($value !== null) {
                                                    $qualityCheckModel = new QualityCheck();
                                                    $result = $qualityCheckModel->checkValueAgainstStandard($value, $standard);
                                                    if ($result === true) {
                                                        echo '<i class="fas fa-check-circle text-success"></i>';
                                                    } elseif ($result === false) {
                                                        echo '<i class="fas fa-times-circle text-danger"></i>';
                                                    } else {
                                                        echo '<i class="fas fa-question-circle text-muted"></i>';
                                                    }
                                                } else {
                                                    echo '<i class="fas fa-question-circle text-muted"></i>';
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
            <?php endif; ?>

            <?php if (!empty($recommendations)): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Рекомендації</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <?php foreach ($recommendations as $recommendation): ?>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                <?= htmlspecialchars($recommendation) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($check['status'] === 'approved'): ?>
            <div class="card shadow-sm mt-3 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Результат</h5>
                </div>
                <div class="card-body">
                    <p class="text-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Сировина схвалена для використання у виробництві
                    </p>
                </div>
            </div>
            <?php elseif ($check['status'] === 'rejected'): ?>
            <div class="card shadow-sm mt-3 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Результат</h5>
                </div>
                <div class="card-body">
                    <p class="text-danger mb-0">
                        <i class="fas fa-times-circle me-2"></i>
                        Сировина відхилена і не може використовуватися у виробництві
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>