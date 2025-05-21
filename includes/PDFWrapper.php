<?php
// Обертка для PDF класса с автоматическим переключением
class PDFWrapper {
    private $pdf_instance;
    private $use_tcpdf;
    
    public function __construct($title = 'Звіт', $orientation = 'P', $unit = 'mm', $format = 'A4') {
        // Проверяем доступность TCPDF
        $this->use_tcpdf = $this->isTcpdfAvailable();
        
        if ($this->use_tcpdf) {
            // Используем оригинальный класс PDF с TCPDF
            $this->pdf_instance = new PDF($title, $orientation, $unit, $format);
        } else {
            // Используем SimplePDF
            require_once INCLUDES_PATH . '/SimplePDF.php';
            $this->pdf_instance = new SimplePDF($title);
        }
    }
    
    private function isTcpdfAvailable() {
        $tcpdf_paths = [
            BASE_PATH . '/vendor/tcpdf/tcpdf.php',
            BASE_PATH . '/tcpdf/tcpdf.php',
            BASE_PATH . '/lib/tcpdf/tcpdf.php',
            BASE_PATH . '/libraries/tcpdf/tcpdf.php'
        ];
        
        foreach ($tcpdf_paths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }
        
        return false;
    }
    
    // Проксируем все методы к соответствующему классу
    public function addTitle($title, $subtitle = '') {
        return $this->pdf_instance->addTitle($title, $subtitle);
    }
    
    public function addText($text) {
        return $this->pdf_instance->addText($text);
    }
    
    public function addTable($header, $data) {
        return $this->pdf_instance->addTable($header, $data);
    }
    
    public function addDateAndSignature() {
        return $this->pdf_instance->addDateAndSignature();
    }
    
    public function output($name = 'report.pdf', $dest = 'I') {
        return $this->pdf_instance->output($name, $dest);
    }
    
    // Дополнительные методы из оригинального класса PDF
    public function createInventoryReport($inventory) {
        if ($this->use_tcpdf && method_exists($this->pdf_instance, 'createInventoryReport')) {
            return $this->pdf_instance->createInventoryReport($inventory);
        }
        
        // Альтернативная реализация для SimplePDF
        $this->addTitle('Звіт по запасам на ' . date('d.m.Y'));
        
        $header = ['Назва', 'Кількість', 'Одиниці', 'Мін. запас', 'Ціна за од.', 'Загальна вартість', 'Статус'];
        $data = [];
        
        foreach ($inventory as $item) {
            $status = '';
            switch ($item['status']) {
                case 'low': $status = 'Критично'; break;
                case 'medium': $status = 'Середньо'; break;
                case 'good': $status = 'Достатньо'; break;
            }
            
            $data[] = [
                $item['name'],
                number_format($item['quantity'], 2),
                $item['unit'],
                number_format($item['min_stock'], 2),
                number_format($item['price_per_unit'], 2) . ' грн',
                number_format($item['total_value'], 2) . ' грн',
                $status
            ];
        }
        
        $this->addTable($header, $data);
        
        $total_value = array_sum(array_column($inventory, 'total_value'));
        $this->addText('Загальна вартість запасів: ' . number_format($total_value, 2) . ' грн');
        
        $this->addDateAndSignature();
    }
    
    public function createProductionReport($stats, $products_stats, $start_date, $end_date) {
        if ($this->use_tcpdf && method_exists($this->pdf_instance, 'createProductionReport')) {
            return $this->pdf_instance->createProductionReport($stats, $products_stats, $start_date, $end_date);
        }
        
        // Альтернативная реализация
        $this->addTitle('Звіт по виробництву за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
        
        if (!empty($stats)) {
            $header = ['Продукт', 'Кількість', 'Кількість циклів', 'Сер. час виробництва'];
            $data = [];
            
            foreach ($stats as $item) {
                $data[] = [
                    $item['product_name'],
                    number_format($item['total_quantity'], 2),
                    $item['processes_count'],
                    round($item['avg_production_time'], 1) . ' год'
                ];
            }
            
            $this->addText('Статистика виробництва по продуктах:');
            $this->addTable($header, $data);
        }
        
        if (!empty($products_stats)) {
            $header = ['Продукт', 'Кількість', 'Ціна', 'Загальна вартість'];
            $data = [];
            
            foreach ($products_stats as $item) {
                $data[] = [
                    $item['name'],
                    number_format($item['total_produced'], 2),
                    number_format($item['price'], 2) . ' грн',
                    number_format($item['total_value'], 2) . ' грн'
                ];
            }
            
            $this->addText('Вартість виробленої продукції:');
            $this->addTable($header, $data);
            
            $total_value = array_sum(array_column($products_stats, 'total_value'));
            $this->addText('Загальна вартість виробленої продукції: ' . number_format($total_value, 2) . ' грн');
        }
        
        $this->addDateAndSignature();
    }
    
    public function createOrdersReport($supplier_stats, $material_stats, $start_date, $end_date) {
        if ($this->use_tcpdf && method_exists($this->pdf_instance, 'createOrdersReport')) {
            return $this->pdf_instance->createOrdersReport($supplier_stats, $material_stats, $start_date, $end_date);
        }
        
        // Альтернативная реализация
        $this->addTitle('Звіт по замовленнях за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
        
        if (!empty($supplier_stats)) {
            $header = ['Постачальник', 'Кількість замовлень', 'Загальна сума'];
            $data = [];
            
            foreach ($supplier_stats as $item) {
                $data[] = [
                    $item['supplier_name'],
                    $item['orders_count'],
                    number_format($item['total_amount'], 2) . ' грн'
                ];
            }
            
            $this->addText('Статистика замовлень по постачальниках:');
            $this->addTable($header, $data);
        }
        
        if (!empty($material_stats)) {
            $header = ['Матеріал', 'Одиниці', 'Кількість', 'Загальна сума'];
            $data = [];
            
            foreach ($material_stats as $item) {
                $data[] = [
                    $item['material_name'],
                    $item['unit'],
                    number_format($item['total_quantity'], 2),
                    number_format($item['total_amount'], 2) . ' грн'
                ];
            }
            
            $this->addText('Статистика замовлень по матеріалах:');
            $this->addTable($header, $data);
        }
        
        if (!empty($supplier_stats)) {
            $total_amount = array_sum(array_column($supplier_stats, 'total_amount'));
            $this->addText('Загальна сума замовлень: ' . number_format($total_amount, 2) . ' грн');
        }
        
        $this->addDateAndSignature();
    }

    // Временная отладочная версия generateOrdersPdf
    public function generateOrdersPdf() {
        try {
            // Параметры периода
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
            
            // Проверяем доступность классов
            if (!class_exists('Database')) {
                die('Database class not found');
            }
            
            if (!class_exists('Auth')) {
                die('Auth class not found');
            }
            
            // Получаем данные
            $db = Database::getInstance();
            
            // Проверяем подключение к БД
            if (!$db) {
                die('Database connection failed');
            }
            
            $sql = "SELECT o.*, u.name as ordered_by_name
                    FROM orders o
                    JOIN users u ON o.ordered_by = u.id
                    WHERE o.supplier_id = ? 
                    AND o.created_at BETWEEN ? AND ?
                    ORDER BY o.created_at DESC";
                    
            $orders = $db->resultSet($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            
            // Проверяем, есть ли TCPDF
            $tcpdf_available = false;
            $tcpdf_paths = [
                BASE_PATH . '/vendor/tcpdf/tcpdf.php',
                BASE_PATH . '/tcpdf/tcpdf.php',
                BASE_PATH . '/lib/tcpdf/tcpdf.php',
                BASE_PATH . '/libraries/tcpdf/tcpdf.php'
            ];
            
            foreach ($tcpdf_paths as $path) {
                if (file_exists($path)) {
                    $tcpdf_available = true;
                    break;
                }
            }
            
            if ($tcpdf_available && class_exists('PDF')) {
                // Используем ваш оригинальный класс PDF
                $this->generatePdfWithTCPDF($orders, $start_date, $end_date);
            } else {
                // Используем простой HTML
                $this->generatePdfAsHtml($orders, $start_date, $end_date);
            }
            
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }
    
    private function generatePdfWithTCPDF($orders, $start_date, $end_date) {
        try {
            $pdf = new PDF('Звіт по замовленнях');
            $pdf->addTitle('Звіт по замовленнях за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
            
            // Добавляем информацию о поставщике
            $pdf->addText('Постачальник: ' . Auth::getCurrentUserName());
            $pdf->addText('Дата формування: ' . date('d.m.Y H:i'));
            $pdf->addText('');
            
            if (!empty($orders)) {
                $header = ['№', 'Дата', 'Замовник', 'Статус', 'Сума'];
                $data = [];
                
                foreach ($orders as $order) {
                    $data[] = [
                        $order['id'],
                        date('d.m.Y', strtotime($order['created_at'])),
                        $order['ordered_by_name'],
                        Util::getOrderStatusName($order['status']),
                        number_format($order['total_amount'], 2) . ' грн'
                    ];
                }
                
                $pdf->addText('Список замовлень:');
                $pdf->addTable($header, $data);
            } else {
                $pdf->addText('Немає замовлень за вказаний період.');
            }
            
            $pdf->addDateAndSignature();
            $pdf->output('orders_report_' . date('Y-m-d') . '.pdf');
            
        } catch (Exception $e) {
            die('PDF generation error: ' . $e->getMessage());
        }
    }
    
    private function generatePdfAsHtml($orders, $start_date, $end_date) {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Звіт по замовленнях</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .signature { margin-top: 50px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ЗВІТ ПО ЗАМОВЛЕННЯХ</h1>
                <p>Період: з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)) . '</p>
                <p>Постачальник: ' . Auth::getCurrentUserName() . '</p>
                <p>Дата формування: ' . date('d.m.Y H:i') . '</p>
            </div>';
        
        if (!empty($orders)) {
            $html .= '<table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Дата</th>
                        <th>Замовник</th>
                        <th>Статус</th>
                        <th>Сума</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($orders as $order) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($order['id']) . '</td>
                    <td>' . date('d.m.Y', strtotime($order['created_at'])) . '</td>
                    <td>' . htmlspecialchars($order['ordered_by_name']) . '</td>
                    <td>' . htmlspecialchars(Util::getOrderStatusName($order['status'])) . '</td>
                    <td>' . number_format($order['total_amount'], 2) . ' грн</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>Немає замовлень за вказаний період.</p>';
        }
        
        $html .= '<div class="signature">
                <p>Дата: ' . date('d.m.Y') . '</p>
                <p>Підпис: _________________</p>
            </div>
            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
        </body>
        </html>';
        
        // Отправляем HTML с заголовком для PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="orders_report_' . date('Y-m-d') . '.html"');
        echo $html;
    }

    // Временная отладочная версия generateMaterialsPdf
    public function generateMaterialsPdf() {
        try {
            // Параметры периода
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
            
            // Получаем данные
            $db = Database::getInstance();
            
            $sql = "SELECT 
                        r.id,
                        r.name,
                        r.unit,
                        r.price_per_unit,
                        r.min_stock,
                        COUNT(oi.id) as orders_count,
                        COALESCE(SUM(oi.quantity), 0) as total_ordered,
                        COALESCE(SUM(oi.quantity * oi.price_per_unit), 0) as total_amount
                    FROM raw_materials r
                    LEFT JOIN order_items oi ON r.id = oi.raw_material_id
                    LEFT JOIN orders o ON oi.order_id = o.id
                    WHERE r.supplier_id = ?
                    AND (o.created_at BETWEEN ? AND ? OR o.created_at IS NULL)
                    GROUP BY r.id, r.name, r.unit, r.price_per_unit, r.min_stock
                    ORDER BY total_amount DESC";
                    
            $materials_stats = $db->resultSet($sql, [Auth::getCurrentUserId(), $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            
            // Проверяем, есть ли TCPDF
            $tcpdf_available = class_exists('PDF');
            
            if ($tcpdf_available) {
                $this->generateMaterialsPdfWithTCPDF($materials_stats, $start_date, $end_date);
            } else {
                $this->generateMaterialsPdfAsHtml($materials_stats, $start_date, $end_date);
            }
            
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }
    
    private function generateMaterialsPdfWithTCPDF($materials_stats, $start_date, $end_date) {
        try {
            $pdf = new PDF('Звіт по матеріалах');
            $pdf->addTitle('Звіт по матеріалах за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
            
            $pdf->addText('Постачальник: ' . Auth::getCurrentUserName());
            $pdf->addText('Дата формування: ' . date('d.m.Y H:i'));
            $pdf->addText('');
            
            if (!empty($materials_stats)) {
                $header = ['Назва', 'Од.', 'Ціна', 'Мін.запас', 'Замовлень', 'Кількість', 'Сума'];
                $data = [];
                
                foreach ($materials_stats as $material) {
                    $data[] = [
                        $material['name'],
                        $material['unit'],
                        number_format($material['price_per_unit'], 2),
                        number_format($material['min_stock'], 2),
                        $material['orders_count'],
                        number_format($material['total_ordered'], 2),
                        number_format($material['total_amount'], 2) . ' грн'
                    ];
                }
                
                $pdf->addText('Детальна статистика по матеріалах:');
                $pdf->addTable($header, $data);
            } else {
                $pdf->addText('Немає даних за вказаний період.');
            }
            
            $pdf->addDateAndSignature();
            $pdf->output('materials_report_' . date('Y-m-d') . '.pdf');
            
        } catch (Exception $e) {
            die('PDF generation error: ' . $e->getMessage());
        }
    }
    
    private function generateMaterialsPdfAsHtml($materials_stats, $start_date, $end_date) {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Звіт по матеріалах</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .signature { margin-top: 50px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ЗВІТ ПО МАТЕРІАЛАХ</h1>
                <p>Період: з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)) . '</p>
                <p>Постачальник: ' . Auth::getCurrentUserName() . '</p>
                <p>Дата формування: ' . date('d.m.Y H:i') . '</p>
            </div>';
        
        if (!empty($materials_stats)) {
            $html .= '<table>
                <thead>
                    <tr>
                        <th>Назва матеріалу</th>
                        <th>Одиниця</th>
                        <th>Ціна за од.</th>
                        <th>Мін. запас</th>
                        <th>Кількість замовлень</th>
                        <th>Загальна кількість</th>
                        <th>Загальна сума</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($materials_stats as $material) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($material['name']) . '</td>
                    <td>' . htmlspecialchars($material['unit']) . '</td>
                    <td>' . number_format($material['price_per_unit'], 2) . ' грн</td>
                    <td>' . number_format($material['min_stock'], 2) . '</td>
                    <td>' . $material['orders_count'] . '</td>
                    <td>' . number_format($material['total_ordered'], 2) . '</td>
                    <td>' . number_format($material['total_amount'], 2) . ' грн</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>Немає даних за вказаний період.</p>';
        }
        
        $html .= '<div class="signature">
                <p>Дата: ' . date('d.m.Y') . '</p>
                <p>Підпис: _________________</p>
            </div>
            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
        </body>
        </html>';
        
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="materials_report_' . date('Y-m-d') . '.html"');
        echo $html;
    }
}