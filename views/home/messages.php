<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Инициализируем переменные, если они не установлены
$inbox = $inbox ?? [];
$sent = $sent ?? [];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-envelope me-2"></i>Повідомлення</h1>
        <a href="<?= BASE_URL ?>/home/newMessage" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Нове повідомлення
        </a>
    </div>
<!-- Вкладки для переключения между входящими и отправленными сообщениями -->
<ul class="nav nav-tabs mb-4" id="messagesTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab" aria-controls="inbox" aria-selected="true">
            <i class="fas fa-inbox me-1"></i>Вхідні
            <?php 
            $unread_count = 0;
            if (is_array($inbox)) {
                $unread_count = count(array_filter($inbox, function($m) { return !$m['is_read']; }));
            }
            if ($unread_count > 0): 
            ?>
                <span class="badge bg-danger">
                    <?= $unread_count ?>
                </span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab" aria-controls="sent" aria-selected="false">
            <i class="fas fa-paper-plane me-1"></i>Надіслані
        </button>
    </li>
</ul>

<div class="tab-content" id="messagesTabContent">
    <!-- Входящие сообщения -->
    <div class="tab-pane fade show active" id="inbox" role="tabpanel" aria-labelledby="inbox-tab">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="5%"></th>
                                <th width="20%">Відправник</th>
                                <th width="45%">Тема</th>
                                <th width="20%">Дата</th>
                                <th width="10%">Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inbox)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">Немає вхідних повідомлень</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inbox as $message): ?>
                                    <tr class="<?= $message['is_read'] ? '' : 'message-unread' ?>">
                                        <td class="text-center">
                                            <?php if (!$message['is_read']): ?>
                                                <i class="fas fa-circle text-primary" style="font-size: 0.7rem;" title="Нове повідомлення"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($message['sender_name']) ?></td>
                                        <td><?= htmlspecialchars($message['subject']) ?></td>
                                        <td><?= Util::formatDate($message['created_at']) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>/home/viewMessage/<?= $message['id'] ?>" 
                                                   class="btn btn-outline-info" title="Переглянути">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/home/deleteMessage/<?= $message['id'] ?>" 
                                                   class="btn btn-outline-danger" title="Видалити"
                                                   onclick="return confirm('Ви впевнені, що хочете видалити це повідомлення?');">
                                                    <i class="fas fa-trash"></i>
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
    
    <!-- Отправленные сообщения -->
    <div class="tab-pane fade" id="sent" role="tabpanel" aria-labelledby="sent-tab">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="20%">Одержувач</th>
                                <th width="50%">Тема</th>
                                <th width="20%">Дата</th>
                                <th width="10%">Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sent)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">Немає надісланих повідомлень</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sent as $message): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($message['receiver_name']) ?></td>
                                        <td><?= htmlspecialchars($message['subject']) ?></td>
                                        <td><?= Util::formatDate($message['created_at']) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>/home/viewMessage/<?= $message['id'] ?>" 
                                                   class="btn btn-outline-info" title="Переглянути">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/home/deleteMessage/<?= $message['id'] ?>" 
                                                   class="btn btn-outline-danger" title="Видалити"
                                                   onclick="return confirm('Ви впевнені, що хочете видалити це повідомлення?');">
                                                    <i class="fas fa-trash"></i>
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
</div>
</div>
<!-- Для отладки -->
<div class="container-fluid mt-5" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h5>Отладочная информация</h5>
        </div>
        <div class="card-body">
            <h6>Входящие сообщения:</h6>
            <pre><?php var_dump($inbox); ?></pre>
        <h6>Отправленные сообщения:</h6>
        <pre><?php var_dump($sent); ?></pre>
    </div>
</div>
</div>