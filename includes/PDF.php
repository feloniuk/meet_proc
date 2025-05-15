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