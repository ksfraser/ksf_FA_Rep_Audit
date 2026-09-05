<?php
/**
 * Amount Mismatches Report Entry Point
 */

$path_to_root = "../../../";
include($path_to_root . "includes/session.inc");
include($path_to_root . "includes/ui/ui_tailored.inc");

require_once(dirname(__FILE__) . "/class.query.php");
require_once(dirname(__FILE__) . "/class.screen.php");
require_once(dirname(__FILE__) . "/class.pdf.php");
require_once(dirname(__FILE__) . "/class.corrections.php");

page(_('GL vs Bank Amount Mismatches Report'));

$query = new AmountMismatchesQuery();
$corrections = new AmountMismatchesCorrections();
$screen = new AmountMismatchesScreen($query, $corrections);

$action = $_GET['action'] ?? '';

if ($action === 'export' && $_GET['format'] === 'pdf') {
    $filters = array(
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'variance_threshold_pct' => $_GET['variance_threshold_pct'] ?? 0.01
    );
    
    $data = $query->execute($filters);
    $summary = $query->getSummary();
    global $company_config;
    
    generateAmountMismatchesPDF($data, $summary, array('name' => $company_config['coy_name']));
    exit;
}

$filters = array(
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'variance_threshold_pct' => $_GET['variance_threshold_pct'] ?? 0.01
);

$screen->setFiltersAndExecute($filters);
$screen->render();

end_page();
?>
