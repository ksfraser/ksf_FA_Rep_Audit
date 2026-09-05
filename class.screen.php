<?php
/**
 * OrphanedTransactionsScreen
 * 
 * Renders orphaned transactions report as HTML with interactive table,
 * correction options, and bulk actions.
 */

class OrphanedTransactionsScreen {
    
    private $query;
    private $corrections;
    private $rows;
    private $filters;
    
    public function __construct($query_obj, $corrections_obj) {
        $this->query = $query_obj;
        $this->corrections = $corrections_obj;
        $this->rows = array();
        $this->filters = array();
    }
    
    /**
     * Set filters and execute query
     * 
     * @param array $filters
     */
    public function setFiltersAndExecute($filters) {
        $this->filters = $filters;
        $this->rows = $this->query->execute($filters);
    }
    
    /**
     * Render full report screen
     */
    public function render() {
        ?>
        <div class="reporting-wrapper">
            <div class="page-head">
                <div class="right">
                    <a href="#" onclick="window.print();" class="btn" title="Print">
                        <span>Print</span>
                    </a>
                    <a href="#" onclick="exportToPDF();" class="btn" title="Export to PDF">
                        <span>Export PDF</span>
                    </a>
                    <a href="#" onclick="exportToCSV();" class="btn" title="Export to CSV">
                        <span>Export CSV</span>
                    </a>
                </div>
                <h1><?php echo _('Orphaned GL Transactions Report'); ?></h1>
            </div>
            
            <?php $this->renderSummary(); ?>
            
            <div class="report-filters">
                <h3><?php echo _('Filters'); ?></h3>
                <?php $this->renderFilterForm(); ?>
            </div>
            
            <?php $this->renderResultsTable(); ?>
            
            <?php $this->renderCorrectionOptions(); ?>
        </div>
        <?php
    }
    
    /**
     * Render summary statistics
     */
    private function renderSummary() {
        $summary = $this->query->getSummary();
        
        if (empty($summary)) {
            echo '<div class="info-box">' . _('No orphaned transactions found.') . '</div>';
            return;
        }
        
        ?>
        <div class="summary-section">
            <h3><?php echo _('Summary'); ?></h3>
            <table class="summary-table">
                <tr>
                    <td><?php echo _('Total Orphaned Transactions:'); ?></td>
                    <td><strong><?php echo $summary['total_orphaned']; ?></strong></td>
                </tr>
                <tr>
                    <td><?php echo _('Total GL Lines:'); ?></td>
                    <td><strong><?php echo $summary['total_gl_lines']; ?></strong></td>
                </tr>
                <tr>
                    <td><?php echo _('Total Amount:'); ?></td>
                    <td><strong><?php echo number_format($summary['total_amount'], 2); ?></strong></td>
                </tr>
                <tr>
                    <td><?php echo _('Transaction Types:'); ?></td>
                    <td><strong><?php echo $summary['transaction_types']; ?></strong></td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render filter form
     */
    private function renderFilterForm() {
        ?>
        <form method="get" id="filterForm">
            <input type="hidden" name="action" value="filter">
            
            <div class="form-group">
                <label><?php echo _('Date From:'); ?></label>
                <input type="date" name="date_from" 
                       value="<?php echo isset($this->filters['date_from']) ? $this->filters['date_from'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label><?php echo _('Date To:'); ?></label>
                <input type="date" name="date_to" 
                       value="<?php echo isset($this->filters['date_to']) ? $this->filters['date_to'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label><?php echo _('Transaction Type:'); ?></label>
                <select name="trans_type">
                    <option value=""><?php echo _('-- All Types --'); ?></option>
                    <option value="0" <?php echo isset($this->filters['trans_type']) && $this->filters['trans_type'] == 0 ? 'selected' : ''; ?>>
                        <?php echo _('Journal'); ?>
                    </option>
                    <option value="1" <?php echo isset($this->filters['trans_type']) && $this->filters['trans_type'] == 1 ? 'selected' : ''; ?>>
                        <?php echo _('Bank Payment'); ?>
                    </option>
                    <option value="2" <?php echo isset($this->filters['trans_type']) && $this->filters['trans_type'] == 2 ? 'selected' : ''; ?>>
                        <?php echo _('Bank Deposit'); ?>
                    </option>
                    <option value="3" <?php echo isset($this->filters['trans_type']) && $this->filters['trans_type'] == 3 ? 'selected' : ''; ?>>
                        <?php echo _('Bank Transfer'); ?>
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label><?php echo _('Minimum Amount:'); ?></label>
                <input type="number" step="0.01" name="min_amount" 
                       value="<?php echo isset($this->filters['min_amount']) ? $this->filters['min_amount'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label><?php echo _('Maximum Amount:'); ?></label>
                <input type="number" step="0.01" name="max_amount" 
                       value="<?php echo isset($this->filters['max_amount']) ? $this->filters['max_amount'] : ''; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary"><?php echo _('Apply Filters'); ?></button>
            <button type="reset" class="btn btn-secondary"><?php echo _('Clear'); ?></button>
        </form>
        <?php
    }
    
    /**
     * Render results table
     */
    private function renderResultsTable() {
        if (empty($this->rows)) {
            return;
        }
        
        ?>
        <div class="results-section">
            <h3><?php echo _('Orphaned Transactions'); ?></h3>
            <table class="results-table">
                <thead>
                    <tr>
                        <th><?php echo _('Trans Type#'); ?></th>
                        <th><?php echo _('Type'); ?></th>
                        <th><?php echo _('Date'); ?></th>
                        <th><?php echo _('Reference'); ?></th>
                        <th><?php echo _('GL Lines'); ?></th>
                        <th><?php echo _('Amount'); ?></th>
                        <th><?php echo _('Status'); ?></th>
                        <th><?php echo _('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->rows as $row): ?>
                    <tr>
                        <td><?php echo $row['type_no']; ?></td>
                        <td><?php echo $this->getTypeName($row['type']); ?></td>
                        <td><?php echo $row['tran_date']; ?></td>
                        <td><?php echo $row['reference']; ?></td>
                        <td><?php echo $row['gl_line_count']; ?></td>
                        <td><?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>
                            <span class="badge badge-danger"><?php echo $row['orphan_status']; ?></span>
                        </td>
                        <td>
                            <a href="#" class="btn-sm inspect" 
                               onclick="inspectTransaction(<?php echo $row['type_no']; ?>); return false;">
                                <?php echo _('Inspect'); ?>
                            </a>
                            <a href="#" class="btn-sm correct" 
                               onclick="correctTransaction(<?php echo $row['type_no']; ?>); return false;">
                                <?php echo _('Correct'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render correction options
     */
    private function renderCorrectionOptions() {
        ?>
        <div class="corrections-section">
            <h3><?php echo _('Correction Options'); ?></h3>
            <p><?php echo _('Select one or more transactions and choose a correction action:'); ?></p>
            
            <div class="bulk-actions">
                <button class="btn btn-primary" onclick="bulkCorrect('insert_bank_trans')">
                    <?php echo _('Insert Missing bank_trans'); ?>
                </button>
                <button class="btn btn-warning" onclick="bulkCorrect('void')">
                    <?php echo _('Void Transactions'); ?>
                </button>
                <button class="btn btn-info" onclick="bulkCorrect('inspect')">
                    <?php echo _('Inspect All'); ?>
                </button>
            </div>
        </div>
        
        <script type="text/javascript">
            function inspectTransaction(typeNo) {
                window.location.href = '?action=inspect&type_no=' + typeNo;
            }
            
            function correctTransaction(typeNo) {
                window.location.href = '?action=correct&type_no=' + typeNo;
            }
            
            function bulkCorrect(action) {
                alert('Bulk action: ' + action);
                // Implement bulk action logic
            }
            
            function exportToPDF() {
                window.location.href = '?action=export&format=pdf';
            }
            
            function exportToCSV() {
                window.location.href = '?action=export&format=csv';
            }
        </script>
        <?php
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
}
?>
