<?php
// Клас для створення PDF звітів з використанням TCPDF
class PDF {
    private $pdf;
    
    public function __construct($title = 'Звіт', $orientation = 'P', $unit = 'mm', $format = 'A4') {
        // Включення TCPDF (потрібно встановити TCPDF через Composer або вручну)
        require_once(BASE_PATH . '/vendor/tcpdf/tcpdf.php');
        
        // Створення нового PDF документа
        $this->pdf = new TCPDF($orientation, $unit, $format, true, 'UTF-8', false);
        
        // Встановлення метаданих документа
        $this->pdf->SetCreator('Автоматизація забезпечення виробництва ковбасної продукції');
        $this->pdf->SetAuthor('Система управління виробництвом');
        $this->pdf->SetTitle($title);
        $this->pdf->SetSubject($title);
        
        // Встановлення налаштувань звіту
        $this->pdf->setPrintHeader(true);
        $this->pdf->setPrintFooter(true);
        
        // Встановлення шрифту
        $this->pdf->SetFont(PDF_FONT, '', PDF_FONT_SIZE);
        
        // Встановлення кольорів
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetFillColor(224, 235, 255);
        $this->pdf->SetTextColor(0, 0, 0);
        
        // Встановлення товщини ліній
        $this->pdf->SetLineWidth(0.3);
        
        // Додавання першої сторінки
        $this->pdf->AddPage();
    }

    // Метод для створення звіту по запасам
public function createInventoryReport($inventory) {
    $this->addTitle('Звіт по запасам на ' . date('d.m.Y'));
    
    // Підготовка даних для таблиці
    $header = ['Назва', 'Кількість', 'Одиниці', 'Мін. запас', 'Ціна за од.', 'Загальна вартість', 'Статус'];
    $data = [];
    
    foreach ($inventory as $item) {
        $status = '';
        switch ($item['status']) {
            case 'low':
                $status = 'Критично';
                break;
            case 'medium':
                $status = 'Середньо';
                break;
            case 'good':
                $status = 'Достатньо';
                break;
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
    
    // Загальна вартість
    $total_value = array_sum(array_column($inventory, 'total_value'));
    $this->addText('Загальна вартість запасів: ' . number_format($total_value, 2) . ' грн');
    
    $this->addDateAndSignature();
}

// Метод для створення звіту по виробництву
public function createProductionReport($stats, $products_stats, $start_date, $end_date) {
    $this->addTitle('Звіт по виробництву за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
    
    // Підготовка даних для таблиці статистики по продуктах
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
    
    // Підготовка даних для таблиці вартості продукції
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
    
    // Загальна вартість
    $total_value = array_sum(array_column($products_stats, 'total_value'));
    $this->addText('Загальна вартість виробленої продукції: ' . number_format($total_value, 2) . ' грн');
    
    $this->addDateAndSignature();
}

// Метод для створення звіту по замовленнях
public function createOrdersReport($supplier_stats, $material_stats, $start_date, $end_date) {
    $this->addTitle('Звіт по замовленнях за період', 'з ' . date('d.m.Y', strtotime($start_date)) . ' по ' . date('d.m.Y', strtotime($end_date)));
    
    // Підготовка даних для таблиці статистики по постачальниках
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
    
    // Підготовка даних для таблиці статистики по матеріалах
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
    
    // Загальна сума
    $total_amount = array_sum(array_column($supplier_stats, 'total_amount'));
    $this->addText('Загальна сума замовлень: ' . number_format($total_amount, 2) . ' грн');
    
    $this->addDateAndSignature();
}
    
    // Метод для додавання заголовка
    public function addTitle($title, $subtitle = '') {
        $this->pdf->SetFont(PDF_FONT, 'B', 16);
        $this->pdf->Cell(0, 10, $title, 0, 1, 'C');
        
        if (!empty($subtitle)) {
            $this->pdf->SetFont(PDF_FONT, 'I', 12);
            $this->pdf->Cell(0, 5, $subtitle, 0, 1, 'C');
        }
        
        $this->pdf->Ln(10);
        $this->pdf->SetFont(PDF_FONT, '', PDF_FONT_SIZE);
    }
    
    // Метод для додавання тексту
    public function addText($text) {
        $this->pdf->Write(0, $text, '', 0, 'L', true);
        $this->pdf->Ln(5);
    }
    
    // Метод для додавання таблиці
    public function addTable($header, $data) {
        // Ширина стовпців
        $width = 190 / count($header);
        
        // Заголовок таблиці
        $this->pdf->SetFont(PDF_FONT, 'B', PDF_FONT_SIZE);
        $this->pdf->SetFillColor(200, 220, 255);
        foreach ($header as $col) {
            $this->pdf->Cell($width, 7, $col, 1, 0, 'C', 1);
        }
        $this->pdf->Ln();
        
        // Дані таблиці
        $this->pdf->SetFont(PDF_FONT, '', PDF_FONT_SIZE);
        $this->pdf->SetFillColor(240, 240, 240);
        $fill = 0;
        foreach ($data as $row) {
            foreach ($row as $col) {
                $this->pdf->Cell($width, 6, $col, 1, 0, 'L', $fill);
            }
            $this->pdf->Ln();
            $fill = !$fill;
        }
        
        $this->pdf->Ln(5);
    }
    
    // Метод для додавання дати та підпису
    public function addDateAndSignature() {
        $this->pdf->Ln(10);
        $this->pdf->Cell(95, 10, 'Дата: ' . date('d.m.Y'), 0, 0, 'L');
        $this->pdf->Cell(95, 10, 'Підпис: _________________', 0, 1, 'R');
    }
    
    // Метод для виведення PDF
    public function output($name = 'report.pdf', $dest = 'I') {
        $this->pdf->Output($name, $dest);
    }
}