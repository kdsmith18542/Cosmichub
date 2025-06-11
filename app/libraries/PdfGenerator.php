<?php

class PdfGenerator {
    private $tcpdf;
    
    public function __construct() {
        // Use TCPDF for PDF generation
        require_once __DIR__ . '/../../vendor/autoload.php';
    }
    /**
     * Generate PDF from HTML content
     * @param string $html HTML content to convert
     * @param string $filename Output filename
     * @param string $orientation Page orientation (P=Portrait, L=Landscape)
     * @return void (outputs PDF directly)
     */
    public function generateFromHtml($html, $filename = 'report.pdf', $orientation = 'P') {
        $pdf = new \TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('CosmicHub');
        $pdf->SetAuthor('CosmicHub');
        $pdf->SetTitle('Astrology Report');
        $pdf->SetSubject('Cosmic Blueprint');
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filename, 'D'); // Force download
        exit;
    }
    // Optionally, keep the prepareHtmlForPdf method for sanitizing HTML
    private function prepareHtmlForPdf($html) {
        $html = strip_tags($html, '<h1><h2><h3><h4><h5><h6><p><br><strong><em><ul><ol><li><table><tr><td><th>');
        return $html;
    }
    public static function setupTcpdf() {
        $tcpdfPath = __DIR__ . '/../../vendor/tcpdf';
        if (!file_exists($tcpdfPath)) {
            return [
                'status' => 'error',
                'message' => 'TCPDF not found. Please run: composer install',
                'instructions' => [
                    '1. Install Composer from https://getcomposer.org/',
                    '2. Run: composer install',
                    '3. TCPDF will be automatically installed'
                ]
            ];
        }
        return ['status' => 'success', 'message' => 'TCPDF is ready'];
    }
}