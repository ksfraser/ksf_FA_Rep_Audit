<?php
/**
 * FA_REP_AMOUNT_MISMATCHES - Amount Mismatch Report Module
 * Hooks registration
 */

global $hook_events;
$hook_events['session_create'][] = 'fa_rep_amount_mismatches_init';

function fa_rep_amount_mismatches_init() {
    global $user;
    add_menu_item(
        _('GL vs Bank Amount Mismatches'),
        'banking_import/reports/amount_mismatches.php',
        'banking',
        7
    );
}

$installed_extensions[] = array(
    'name' => 'FA_REP_AMOUNT_MISMATCHES',
    'title' => 'Amount Mismatch Report',
    'description' => 'Identifies GL total vs bank_trans amount discrepancies',
    'version' => '1.0.0',
    'type' => 'report',
    'active' => 1
);
?>
