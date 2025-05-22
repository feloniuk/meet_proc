<?php

class ErrorHandler {
    
    public static function init() {
        // Устанавливаем обработчики ошибок
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
        
        // Настройки отображения ошибок
        if (defined('DEBUG') && DEBUG) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
        }
    }
    
    public static function handleError($severity, $message, $file, $line) {
        // Игнорируем ошибки, которые подавлены оператором @
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $error = [
            'type' => 'Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => debug_backtrace()
        ];
        
        self::logError($error);
        
        // В продакшене показываем пользователю дружелюбное сообщение
        if (!defined('DEBUG') || !DEBUG) {
            self::showUserFriendlyError();
            return true;
        }
        
        // В режиме отладки показываем детальную информацию
        self::showDetailedError($error);
        return true;
    }
    
    public static function handleException($exception) {
        $error = [
            'type' => 'Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ];
        
        self::logError($error);
        
        // В продакшене показываем пользователю дружелюбное сообщение
        if (!defined('DEBUG') || !DEBUG) {
            self::showUserFriendlyError();
            return;
        }
        
        // В режиме отладки показываем детальную информацию
        self::showDetailedError($error);
    }
    
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $errorInfo = [
                'type' => 'Fatal Error',
                'severity' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'trace' => []
            ];
            
            self::logError($errorInfo);
            
            if (!defined('DEBUG') || !DEBUG) {
                self::showUserFriendlyError();
            } else {
                self::showDetailedError($errorInfo);
            }
        }
    }
    
    private static function logError($error) {
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        error_log($logMessage);
        
        // Дополнительно записываем в файл проекта
        $logFile = BASE_PATH . '/logs/error.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    private static function showUserFriendlyError() {
        // Проверяем, не были ли уже отправлены заголовки
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        
        echo '<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Помилка сервера</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .error-container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-icon { text-align: center; font-size: 64px; color: #e74c3c; margin-bottom: 20px; }
        .error-title { text-align: center; color: #2c3e50; margin-bottom: 20px; }
        .error-message { text-align: center; color: #7f8c8d; margin-bottom: 30px; }
        .error-actions { text-align: center; }
        .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin: 0 5px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">Виникла технічна помилка</h1>
        <p class="error-message">Вибачте, сталася непередбачена помилка. Ми вже працюємо над її вирішенням.</p>
        <div class="error-actions">
            <a href="javascript:history.back()" class="btn">Повернутися назад</a>
            <a href="' . BASE_URL . '" class="btn">На головну</a>
        </div>
    </div>
</body>
</html>';
        exit;
    }
    
    private static function showDetailedError($error) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        
        echo '<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Помилка - ' . htmlspecialchars($error['type']) . '</title>
    <style>
        body { font-family: monospace; margin: 0; padding: 20px; background: #2c3e50; color: #ecf0f1; }
        .error-container { max-width: 1000px; margin: 0 auto; }
        .error-header { background: #e74c3c; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
        .error-title { margin: 0; font-size: 24px; }
        .error-details { background: #34495e; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
        .error-trace { background: #34495e; padding: 20px; border-radius: 4px; }
        .trace-item { margin-bottom: 10px; padding: 10px; background: #2c3e50; border-radius: 4px; }
        .file-path { color: #f39c12; }
        .line-number { color: #e67e22; }
        .function-name { color: #3498db; }
        pre { white-space: pre-wrap; word-wrap: break-word; margin: 0; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <h1 class="error-title">' . htmlspecialchars($error['type']) . '</h1>
        </div>
        
        <div class="error-details">
            <h2>Детали помилки:</h2>
            <p><strong>Повідомлення:</strong> ' . htmlspecialchars($error['message']) . '</p>
            <p><strong>Файл:</strong> <span class="file-path">' . htmlspecialchars($error['file']) . '</span></p>
            <p><strong>Рядок:</strong> <span class="line-number">' . $error['line'] . '</span></p>
        </div>';
        
        if (!empty($error['trace'])) {
            echo '<div class="error-trace">
                <h2>Стек викликів:</h2>';
            
            foreach ($error['trace'] as $index => $trace) {
                echo '<div class="trace-item">
                    <strong>#' . $index . '</strong> ';
                
                if (isset($trace['file'])) {
                    echo '<span class="file-path">' . htmlspecialchars($trace['file']) . '</span>';
                    if (isset($trace['line'])) {
                        echo ':<span class="line-number">' . $trace['line'] . '</span>';
                    }
                    echo ' ';
                }
                
                if (isset($trace['class'])) {
                    echo '<span class="function-name">' . htmlspecialchars($trace['class'] . $trace['type'] . $trace['function']) . '()</span>';
                } elseif (isset($trace['function'])) {
                    echo '<span class="function-name">' . htmlspecialchars($trace['function']) . '()</span>';
                }
                
                echo '</div>';
            }
            echo '</div>';
        }
        
        echo '</div>
</body>
</html>';
        exit;
    }
}