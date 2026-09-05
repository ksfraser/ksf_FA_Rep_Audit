<?php
/**
 * FA_REP_CONTAMINATED_TRANS - Contaminated Transactions Report Module
 * Hooks registration
 */

global $hook_events;
$hook_events['session_create'][] = 'fa_rep_contaminated_trans_init';

function fa_rep_contaminated_trans_init() {
    global $user;
    add_menu_item(
        _('Cross-Contaminated GL Lines'),
        'banking_import/reports/contaminated_transactions.php',
        'banking',
        6
    );
}

$installed_extensions[] = array(
    'name' => 'FA_REP_CONTAMINATED_TRANS',
    'title' => 'Contaminated Transactions Report',
    'description' => 'Identifies GL transactions with line items from multiple unrelated transactions',
    'version' => '1.0.0',
    'type' => 'report',
    'active' => 1
);
?>
