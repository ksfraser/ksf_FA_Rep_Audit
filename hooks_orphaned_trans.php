<?php
/**
 * FA_REP_ORPHANED_TRANS - Orphaned Transactions Report Module
 * 
 * Hooks registration for FrontAccounting module.
 * Registers menu item and initializes report functionality.
 */

global $hook_events;

$hook_events['session_create'][] = 'fa_rep_orphaned_trans_init';

/**
 * Initialize Orphaned Transactions Report module
 */
function fa_rep_orphaned_trans_init() {
    global $user;
    
    // Register menu item in Banking menu
    add_menu_item(
        _('Orphaned GL Transactions'),
        'banking_import/reports/orphaned_transactions.php',
        'banking',
        5
    );
}

/**
 * Register module with FA
 */
$installed_extensions[] = array(
    'name' => 'FA_REP_ORPHANED_TRANS',
    'title' => 'Orphaned Transactions Report',
    'description' => 'Identifies and displays GL transactions with missing bank_trans entries',
    'version' => '1.0.0',
    'type' => 'report',
    'active' => 1
);
?>
