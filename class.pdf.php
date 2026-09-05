<?php
/**
 * OrphanedTransactionsPDF
 * 
 * Generates PDF export of orphaned transactions report using TCPDF.
 * Includes header/footer, summary, and detailed transaction list.
 */

require_once(dirname(__FILE__) . '/../../includes/tcpdf/include/tcpdf_configs.php');
require_once(dirname(__FILE__) . '/../../includes/tcpdf/tcpdf.php');

class OrphanedTransactionsPDF extends TCPDF {
    
    public $header_data = array();
    public $company_info = array();
    
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->SetMargins(15, 30, 15);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);
        $this->SetFont('helvetica', '', 10);
        $this->setTextColor(0, 0, 0);
    }
    
    public function Header() {
        if ($this->page == 1) {
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 10, _('Orphaned GL Transactions Report'), 0, 1, 'C');
            
            $this->SetFont('helvetica', '', 9);
            $this->Cell(0, 5, _('Report Date: ') . date('Y-m-d H:i:s'), 0, 1, 'C');
            
            if (!empty($this->company_info)) {
                $this->Cell(0, 5, $this->company_info['name'], 0, 1, 'C');
            }
            
            $this->Ln(5);
        }
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, _('Page ') . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
    
    /**
     * Add summary section to PDF
     * 
     * @param array $summary
     */
    public function addSummary($summary) {
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, _('Summary'), 0, 1);
        
        $this->SetFont('helvetica', '', 10);
        $this->SetFillColor(240, 240, 240);
        
        $width = 100;
        $this->Cell($width, 6, _('Total Orphaned Transactions:'), 0, 0, 'L', true);
        $this->Cell(0, 6, $summary['total_orphaned'] ?? 0, 0, 1, 'R', true);
        
        $this->Cell($width, 6, _('Total GL Lines:'), 0, 0, 'L', true);
        $this->Cell(0, 6, $summary['total_gl_lines'] ?? 0, 0, 1, 'R', true);
        
        $this->Cell($width, 6, _('Total Amount:'), 0, 0, 'L', true);
        $this->Cell(0, 6, number_format($summary['total_amount'] ?? 0, 2), 0, 1, 'R', true);
        
        $this->Ln(5);
    }
    
    /**
     * Add transactions table to PDF
     * 
     * @param array $rows
     */
    public function addTransactionsTable($rows) {
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(0, 8, _('Orphaned Transactions'), 0, 1);
        
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(200, 200, 200);
        
        // Table header
        $col_widths = array(20, 15, 20, 20, 15, 25, 25, 20);
        $headers = array(
            _('Type#'),
            _('Type'),
            _('Date'),
            _('Reference'),
            _('Lines'),
            _('Amount'),
            _('Status'),
            _('Bank Trans')
        );
        
        foreach ($headers as $i => $header) {
            $this->Cell($col_widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Table data
        $this->SetFont('helvetica', '', 8);
        $this->SetFillColor(245, 245, 245);
        $fill = false;
        
        foreach ($rows as $row) {
            $this->Cell($col_widths[0], 6, $row['type_no'], 1, 0, 'C', $fill);
            $this->Cell($col_widths[1], 6, $this->getTypeName($row['type']), 1, 0, 'L', $fill);
            $this->Cell($col_widths[2], 6, $row['tran_date'], 1, 0, 'C', $fill);
            $this->Cell($col_widths[3], 6, substr($row['reference'], 0, 15), 1, 0, 'L', $fill);
            $this->Cell($col_widths[4], 6, $row['gl_line_count'], 1, 0, 'C', $fill);
            $this->Cell($col_widths[5], 6, number_format($row['total_amount'], 2), 1, 0, 'R', $fill);
            $this->Cell($col_widths[6], 6, $row['orphan_status'], 1, 0, 'C', $fill);
            $this->Cell($col_widths[7], 6, $row['has_bank_trans'] ?? 'N/A', 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
    }
    
    /**
     * Get transaction type name
     * 
     * @param int $type
     * @return string
     */
    private function getTypeName($type) {
        $types = array(
            0 => _('Journal'),
            1 => _('Bank Payment'),
            2 => _('Bank Deposit'),
            3 => _('Bank Transfer')
        );
        return isset($types[$type]) ? $types[$type] : 'Unknown';
    }
    
    /**
     * Add legend/notes section
     */
    public function addNotes() {
        $this->SetFont('helvetica', 'I', 8);
        $this->Ln(10);
        $this->SetFillColor(255, 255, 200);
        $this->MultiCell(0, 5, 
            _('Note: Orphaned transactions are GL entries without corresponding bank_trans entries. These may indicate incomplete import processes or data corruption. Use the correction actions to insert missing bank_trans entries or void the transactions as appropriate.'),
            0, 'L', true);
    }
}

/**
 * Helper function to generate PDF
 * 
 * @param array $data Query result rows
 * @param array $summary Summary statistics
 * @param array $company_info Company information
 * @return void
 */
function generateOrphanedTransactionsPDF($data, $summary, $company_info = array()) {
    $pdf = new OrphanedTransactionsPDF();
    $pdf->company_info = $company_info;
    $pdf->AddPage();
    $pdf->addSummary($summary);
    $pdf->addTransactionsTable($data);
    $pdf->addNotes();
    
    // Output to browser
    $filename = 'orphaned_transactions_' . date('Y-m-d_His') . '.pdf';
    $pdf->Output($filename, 'D');
}
?>
