<?php
/**
 * Contaminated Transactions Report Entry Point
 */

$path_to_root = "../../../";
include($path_to_root . "includes/session.inc");
include($path_to_root . "includes/ui/ui_tailored.inc");

require_once(dirname(__FILE__) . "/class.query.php");
require_once(dirname(__FILE__) . "/class.screen.php");
require_once(dirname(__FILE__) . "/class.pdf.php");
require_once(dirname(__FILE__) . "/class.corrections.php");

page(_('Cross-Contaminated GL Transactions Report'));

$query = new ContaminatedTransactionsQuery();
$corrections = new ContaminatedTransactionsCorrections();
$screen = new ContaminatedTransactionsScreen($query, $corrections);

$action = $_GET['action'] ?? '';

if ($action === 'export' && $_GET['format'] === 'pdf') {
    $filters = array(
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'day_span_threshold' => $_GET['day_span_threshold'] ?? 1
    );
    
    $data = $query->execute($filters);
    $summary = $query->getSummary();
    global $company_config;
    
    generateContaminatedTransactionsPDF($data, $summary, array('name' => $company_config['coy_name']));
    exit;
}

$filters = array(
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'day_span_threshold' => $_GET['day_span_threshold'] ?? 1
);

$screen->setFiltersAndExecute($filters);
$screen->render();

end_page();
?>
