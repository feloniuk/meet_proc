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
}