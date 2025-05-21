<?php
// Простой класс для создания PDF (если TCPDF недоступен)
class SimplePDF {
    private $html;
    private $title;
    
    public function __construct($title = 'Звіт') {
        $this->title = $title;
        $this->html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($title) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .info-table { width: 100%; margin-bottom: 20px; }
                .info-table td { padding: 5px; }
                .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .data-table th, .data-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                .data-table th { background-color: #f5f5f5; font-weight: bold; }
                .total { font-weight: bold; }
                .signature { margin-top: 50px; }
                .text-block { margin-bottom: 10px; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>';
    }
    
    public function addTitle($title, $subtitle = '') {
        $this->html .= '<div class="header">';
        $this->html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        if (!empty($subtitle)) {
            $this->html .= '<p><em>' . htmlspecialchars($subtitle) . '</em></p>';
        }
        $this->html .= '</div>';
    }
    
    public function addText($text) {
        $this->html .= '<div class="text-block">' . nl2br(htmlspecialchars($text)) . '</div>';
    }
    
    public function addTable($header, $data) {
        $this->html .= '<table class="data-table">';
        
        // Заголовок
        $this->html .= '<thead><tr>';
        foreach ($header as $col) {
            $this->html .= '<th>' . htmlspecialchars($col) . '</th>';
        }
        $this->html .= '</tr></thead>';
        
        // Данные
        $this->html .= '<tbody>';
        foreach ($data as $row) {
            $this->html .= '<tr>';
            foreach ($row as $col) {
                $this->html .= '<td>' . htmlspecialchars($col) . '</td>';
            }
            $this->html .= '</tr>';
        }
        $this->html .= '</tbody></table>';
    }
    
    public function addDateAndSignature() {
        $this->html .= '<div class="signature">';
        $this->html .= '<table style="width: 100%;">';
        $this->html .= '<tr>';
        $this->html .= '<td>Дата: ' . date('d.m.Y') . '</td>';
        $this->html .= '<td style="text-align: right;">Підпис: _________________</td>';
        $this->html .= '</tr>';
        $this->html .= '</table>';
        $this->html .= '</div>';
    }
    
    public function output($filename = 'report.pdf', $mode = 'I') {
        $this->html .= '</body></html>';
        
        // Отправляем заголовки для PDF
        if ($mode === 'I') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        
        // Если доступен wkhtmltopdf или подобные инструменты
        if ($this->canUsePdfConverter()) {
            $this->convertToPdf();
        } else {
            // Иначе отправляем как HTML с инструкцией печати
            header('Content-Type: text/html; charset=utf-8');
            echo $this->html;
            echo '<script>window.print();</script>';
        }
    }
    
    private function canUsePdfConverter() {
        // Проверяем наличие конвертеров
        return false; // Пока отключено
    }
    
    private function convertToPdf() {
        // Здесь можно добавить конвертацию через внешние инструменты
        // Например, wkhtmltopdf, dompdf и т.д.
    }
}