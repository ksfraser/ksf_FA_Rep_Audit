<?php
/**
 * OrphanedTransactionsQuery
 * 
 * Executes diagnostic query to identify GL transactions without matching bank_trans entries.
 * Includes filtering by date range, transaction type, and amount.
 */

class OrphanedTransactionsQuery {
    
    private $db;
    private $tb_pref;
    
    public function __construct() {
        global $db, $company_config;
        $this->db = $db;
        $this->tb_pref = $company_config['tbprefix'];
    }
    
    /**
     * Query orphaned GL transactions
     * 
     * @param array $filters {
     *     'date_from' => string (YYYY-MM-DD),
     *     'date_to' => string (YYYY-MM-DD),
     *     'trans_type' => int|null (BT_BANK_PAYMENT=1, BT_BANK_DEPOSIT=2, BT_BANK_TRANSFER=3, BT_JOURNAL=0),
     *     'min_amount' => float|null,
     *     'max_amount' => float|null
     * }
     * @return array Result rows or empty array
     */
    public function execute($filters = array()) {
        $sql = "
            SELECT
              gt.type_no,
              gt.type,
              gt.tran_date,
              gt.reference,
              COUNT(*) as gl_line_count,
              SUM(gt.amount) as total_amount,
              bt.id as has_bank_trans,
              CASE 
                WHEN bt.id IS NULL THEN 'NO_BANK_TRANS'
                ELSE 'HAS_BANK_TRANS'
              END as orphan_status
            FROM {$this->tb_pref}gl_trans gt
            LEFT JOIN {$this->tb_pref}bank_trans bt ON bt.trans_no = gt.type_no
            LEFT JOIN {$this->tb_pref}chart_master cm ON cm.account_code = gt.account
            WHERE (
              gt.type IN (1, 2, 3)  -- BT_BANK_PAYMENT, BT_BANK_DEPOSIT, BT_BANK_TRANSFER
              OR
              (gt.type = 0 AND cm.account_type LIKE 'BANK%')  -- BT_JOURNAL touching bank GL
            )
            AND bt.id IS NULL
        ";
        
        // Add date range filter
        if (!empty($filters['date_from'])) {
            $date_from = $this->db->escape($filters['date_from']);
            $sql .= " AND gt.tran_date >= '{$date_from}'";
        }
        
        if (!empty($filters['date_to'])) {
            $date_to = $this->db->escape($filters['date_to']);
            $sql .= " AND gt.tran_date <= '{$date_to}'";
        }
        
        // Add transaction type filter
        if (isset($filters['trans_type']) && is_numeric($filters['trans_type'])) {
            $trans_type = (int)$filters['trans_type'];
            $sql .= " AND gt.type = {$trans_type}";
        }
        
        // Add amount filters
        if (!empty($filters['min_amount'])) {
            $min_amt = (float)$filters['min_amount'];
            $sql .= " AND ABS(SUM(gt.amount)) >= {$min_amt}";
        }
        
        if (!empty($filters['max_amount'])) {
            $max_amt = (float)$filters['max_amount'];
            $sql .= " AND ABS(SUM(gt.amount)) <= {$max_amt}";
        }
        
        $sql .= " GROUP BY gt.type_no, gt.type, gt.tran_date
                  ORDER BY gt.tran_date DESC;";
        
        $result = $this->db->query($sql);
        
        $rows = array();
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        
        return $rows;
    }
    
    /**
     * Get summary statistics
     * 
     * @return array Summary counts
     */
    public function getSummary() {
        $sql = "
            SELECT
              COUNT(DISTINCT gt.type_no) as total_orphaned,
              SUM(COUNT(*)) as total_gl_lines,
              SUM(ABS(gt.amount)) as total_amount,
              COUNT(DISTINCT gt.type) as transaction_types
            FROM {$this->tb_pref}gl_trans gt
            LEFT JOIN {$this->tb_pref}bank_trans bt ON bt.trans_no = gt.type_no
            LEFT JOIN {$this->tb_pref}chart_master cm ON cm.account_code = gt.account
            WHERE (
              gt.type IN (1, 2, 3)
              OR
              (gt.type = 0 AND cm.account_type LIKE 'BANK%')
            )
            AND bt.id IS NULL
            GROUP BY 1;
        ";
        
        $result = $this->db->query($sql);
        return $result ? $this->db->fetch_assoc($result) : array();
    }
}
?>
