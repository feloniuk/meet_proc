<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Проверяем, определена ли переменная сообщения
if (!isset($message) || empty($message)) {
    echo '<div class="alert alert-danger">Сообщение не найдено</div>';
    exit;
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-envelope-open me-2"></i>Перегляд повідомлення</h1>
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/home/messages" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад до списку
            </a>
            <?php if ($message['sender_id'] != Auth::getCurrentUserId()): ?>
                <a href="<?= BASE_URL ?>/home/newMessage?reply_to=<?= $message['id'] ?>" class="btn btn-outline-primary">
                    <i class="fas fa-reply me-1"></i>Відповісти
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/home/deleteMessage/<?= $message['id'] ?>" class="btn btn-outline-danger"
               onclick="return confirm('Ви впевнені, що хочете видалити це повідомлення?');">
                <i class="fas fa-trash me-1"></i>Видалити
            </a>
        </div>
    </div>
<div class="card shadow-sm mb-4">
    <div class="card-header message-header">
        <div class="row">
            <div class="col-md-6">
                <strong>Відправник:</strong> <?= htmlspecialchars($message['sender_name']) ?>
            </div>
            <div class="col-md-6 text-md-end">
                <strong>Дата:</strong> <?= Util::formatDate($message['created_at']) ?>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-6">
                <strong>Одержувач:</strong> <?= htmlspecialchars($message['receiver_name']) ?>
            </div>
            <div class="col-md-6 text-md-end">
                <strong>Статус:</strong> 
                <?php if ($message['is_read']): ?>
                    <span class="text-success">Прочитано</span>
                <?php else: ?>
                    <span class="text-primary">Нове</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Тема:</strong> <?= htmlspecialchars($message['subject']) ?>
            </div>
        </div>
    </div>
    <div class="card-body message-body">
        <div class="message-content">
            <?= nl2br(htmlspecialchars($message['message'])) ?>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between">
    <a href="<?= BASE_URL ?>/home/messages" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Назад до списку
    </a>
    <?php if ($message['sender_id'] != Auth::getCurrentUserId()): ?>
        <a href="<?= BASE_URL ?>/home/newMessage?reply_to=<?= $message['id'] ?>" class="btn btn-primary">
            <i class="fas fa-reply me-1"></i>Відповісти
        </a>
    <?php endif; ?>
</div>
</div>