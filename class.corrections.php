<?php
/**
 * OrphanedTransactionsCorrections
 * 
 * Handles correction actions for orphaned GL transactions:
 * - Insert missing bank_trans entries
 * - Void transactions
 * - Correction logging and audit trail
 */

class OrphanedTransactionsCorrections {
    
    private $db;
    private $tb_pref;
    
    public function __construct() {
        global $db, $company_config;
        $this->db = $db;
        $this->tb_pref = $company_config['tbprefix'];
    }
    
    /**
     * Insert missing bank_trans for orphaned GL transaction
     * 
     * @param int $type_no GL transaction type_no
     * @param string $reason Reason for correction
     * @param int $user_id User performing correction
     * @return array Result: ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function insertMissingBankTrans($type_no, $reason = '', $user_id = null) {
        try {
            $this->db->begin_transaction();
            
            // Fetch GL transaction details
            $gl_sql = "
                SELECT 
                  gt.type_no, 
                  gt.type, 
                  gt.tran_date, 
                  SUM(gt.amount) as total_amount,
                  GROUP_CONCAT(DISTINCT gt.reference) as references,
                  GROUP_CONCAT(DISTINCT gt.debtor_creditor_id) as contacts
                FROM {$this->tb_pref}gl_trans gt
                WHERE gt.type_no = " . (int)$type_no . "
                GROUP BY gt.type_no, gt.type, gt.tran_date;
            ";
            
            $gl_result = $this->db->query($gl_sql);
            if (!$gl_result || $this->db->num_rows($gl_result) === 0) {
                throw new Exception(_('GL transaction not found'));
            }
            
            $gl_trans = $this->db->fetch_assoc($gl_result);
            
            // Insert bank_trans record
            $bank_trans_sql = "
                INSERT INTO {$this->tb_pref}bank_trans 
                (trans_no, type, tran_date, amount, reference, person_id, memo)
                VALUES (
                  " . (int)$type_no . ",
                  " . (int)$gl_trans['type'] . ",
                  '" . $this->db->escape($gl_trans['tran_date']) . "',
                  " . (float)$gl_trans['total_amount'] . ",
                  '" . $this->db->escape($gl_trans['references']) . "',
                  " . ($gl_trans['contacts'] ? (int)$gl_trans['contacts'] : 0) . ",
                  '" . $this->db->escape('Auto-correction: ' . $reason) . "'
                );
            ";
            
            if (!$this->db->query($bank_trans_sql)) {
                throw new Exception(_('Failed to insert bank_trans: ') . $this->db->last_error());
            }
            
            // Log correction
            $this->logCorrection('insert_bank_trans', $type_no, $gl_trans, array(
                'trans_no' => $type_no,
                'amount' => $gl_trans['total_amount']
            ), $reason, $user_id);
            
            $this->db->commit();
            
            return array(
                'success' => true,
                'message' => sprintf(_('Successfully inserted bank_trans for transaction %s'), $type_no),
                'data' => $gl_trans
            );
            
        } catch (Exception $e) {
            $this->db->rollback();
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            );
        }
    }
    
    /**
     * Void orphaned GL transaction
     * 
     * @param int $type_no GL transaction type_no
     * @param string $reason Reason for void
     * @param int $user_id User performing void
     * @return array Result
     */
    public function voidTransaction($type_no, $reason = '', $user_id = null) {
        try {
            $this->db->begin_transaction();
            
            // Get GL transaction details for snapshot
            $gl_sql = "
                SELECT * FROM {$this->tb_pref}gl_trans
                WHERE type_no = " . (int)$type_no . ";
            ";
            
            $result = $this->db->query($gl_sql);
            $gl_entries = array();
            while ($row = $this->db->fetch_assoc($result)) {
                $gl_entries[] = $row;
            }
            
            // Delete GL entries (or mark as void)
            $void_sql = "
                DELETE FROM {$this->tb_pref}gl_trans
                WHERE type_no = " . (int)$type_no . ";
            ";
            
            if (!$this->db->query($void_sql)) {
                throw new Exception(_('Failed to void GL entries: ') . $this->db->last_error());
            }
            
            // Log correction
            $this->logCorrection('void', $type_no, array(
                'gl_entries_count' => count($gl_entries),
                'total_amount' => array_reduce($gl_entries, function($carry, $item) {
                    return $carry + abs($item['amount']);
                }, 0)
            ), array(), $reason, $user_id);
            
            $this->db->commit();
            
            return array(
                'success' => true,
                'message' => sprintf(_('Successfully voided %d GL entries for transaction %s'), count($gl_entries), $type_no),
                'data' => array('voided_entries' => count($gl_entries))
            );
            
        } catch (Exception $e) {
            $this->db->rollback();
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            );
        }
    }
    
    /**
     * Get transaction details for inspection
     * 
     * @param int $type_no
     * @return array Transaction details with GL entries
     */
    public function inspectTransaction($type_no) {
        $sql = "
            SELECT 
              gt.seq_no,
              gt.counter,
              gt.account,
              cm.account_name,
              gt.memo,
              gt.amount,
              gt.tran_date
            FROM {$this->tb_pref}gl_trans gt
            LEFT JOIN {$this->tb_pref}chart_master cm ON cm.account_code = gt.account
            WHERE gt.type_no = " . (int)$type_no . "
            ORDER BY gt.counter ASC;
        ";
        
        $result = $this->db->query($sql);
        $entries = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $entries[] = $row;
            }
        }
        
        return $entries;
    }
    
    /**
     * Log correction action
     * 
     * @param string $correction_type Type of correction
     * @param int $type_no Original transaction type_no
     * @param array $before_snapshot Before state
     * @param array $after_snapshot After state
     * @param string $reason User-provided reason
     * @param int $user_id User ID
     * @return bool
     */
    private function logCorrection($correction_type, $type_no, $before_snapshot, $after_snapshot, $reason, $user_id = null) {
        global $current_user;
        
        if (is_null($user_id)) {
            $user_id = isset($current_user) ? $current_user['id'] : 0;
        }
        
        $log_sql = "
            INSERT INTO {$this->tb_pref}bank_import_corrections
            (trans_id, correction_type, before_snapshot, after_snapshot, reason, created_by, created_at)
            VALUES (
              " . (int)$type_no . ",
              '" . $this->db->escape($correction_type) . "',
              '" . $this->db->escape(json_encode($before_snapshot)) . "',
              '" . $this->db->escape(json_encode($after_snapshot)) . "',
              '" . $this->db->escape($reason) . "',
              " . (int)$user_id . ",
              NOW()
            );
        ";
        
        return (bool)$this->db->query($log_sql);
    }
    
    /**
     * Get correction history for transaction
     * 
     * @param int $type_no
     * @return array Correction records
     */
    public function getCorrectionHistory($type_no) {
        $sql = "
            SELECT 
              id,
              correction_type,
              reason,
              created_by,
              created_at,
              before_snapshot,
              after_snapshot
            FROM {$this->tb_pref}bank_import_corrections
            WHERE trans_id = " . (int)$type_no . "
            ORDER BY created_at DESC;
        ";
        
        $result = $this->db->query($sql);
        $history = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $row['before_snapshot'] = json_decode($row['before_snapshot'], true);
                $row['after_snapshot'] = json_decode($row['after_snapshot'], true);
                $history[] = $row;
            }
        }
        
        return $history;
    }
}
?>
