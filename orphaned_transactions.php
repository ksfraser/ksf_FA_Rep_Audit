<?php
/**
 * Orphaned Transactions Report Search Form
 * 
 * Entry point for the report - displays filter options and triggers report generation
 */

// Include necessary FA files
$path_to_root = "../../../";
include($path_to_root . "includes/session.inc");
include($path_to_root . "includes/ui/ui_tailored.inc");

// Include module classes
require_once(dirname(__FILE__) . "/class.query.php");
require_once(dirname(__FILE__) . "/class.screen.php");
require_once(dirname(__FILE__) . "/class.pdf.php");
require_once(dirname(__FILE__) . "/class.corrections.php");

page(_('Orphaned GL Transactions Report'));

// Initialize
$query = new OrphanedTransactionsQuery();
$corrections = new OrphanedTransactionsCorrections();
$screen = new OrphanedTransactionsScreen($query, $corrections);

// Handle actions
$action = $_GET['action'] ?? '';

if ($action === 'export' && $_GET['format'] === 'pdf') {
    // Generate PDF
    $filters = array(
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'trans_type' => $_GET['trans_type'] ?? null,
        'min_amount' => $_GET['min_amount'] ?? null,
        'max_amount' => $_GET['max_amount'] ?? null
    );
    
    $data = $query->execute($filters);
    $summary = $query->getSummary();
    
    global $company_config;
    $company_info = array('name' => $company_config['coy_name']);
    
    generateOrphanedTransactionsPDF($data, $summary, $company_info);
    exit;
    
} elseif ($action === 'correct' && isset($_GET['type_no'])) {
    // Render correction form
    $type_no = (int)$_GET['type_no'];
    $entries = $corrections->inspectTransaction($type_no);
    $history = $corrections->getCorrectionHistory($type_no);
    
    // TODO: Render correction form with actions
    
} elseif ($action === 'inspect' && isset($_GET['type_no'])) {
    // Render inspection view
    $type_no = (int)$_GET['type_no'];
    $entries = $corrections->inspectTransaction($type_no);
    
    // TODO: Render inspection details
}

// Apply filters and render report
$filters = array(
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'trans_type' => $_GET['trans_type'] ?? null,
    'min_amount' => $_GET['min_amount'] ?? null,
    'max_amount' => $_GET['max_amount'] ?? null
);

$screen->setFiltersAndExecute($filters);
$screen->render();

end_page();
?>
