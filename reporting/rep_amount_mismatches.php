<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
    Released under the terms of the GNU General Public License, GPL,
    as published by the Free Software Foundation, either version 3
    of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
 = 'SA_KSF_REPAUDIT';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

require_once(dirname(__FILE__) . "/../class.query.php");
require_once(dirname(__FILE__) . "/../class.corrections.php");

function get_amount_mismatches($date_from, $date_to)
{
    $query = new AmountMismatchesQuery();
    $filters = array(
        'date_from' => $date_from,
        'date_to' => $date_to,
        'variance_threshold_pct' => 0.01
    );
    return $query->execute($filters);
}

function print_amount_mismatches()
{
    global $path_to_root;

    $date_from = $_POST['PARAM_0'];
    $date_to = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
    $destination = $_POST['PARAM_3'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $rep_date_from = $date_from ? $date_from : Today();
    $rep_date_to = $date_to ? $date_to : Today();

    $cols = array(0, 50, 100, 150, 200, 250, 300, 350);
    $headers = array(_('Type'), _('Reference'), _('GL Amount'), _('Bank Amount'), _('Variance'), _('Date'), _('Link'));
    $aligns = array('left', 'left', 'right', 'right', 'right', 'left', 'left');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Date From'), 'from' => $rep_date_from, 'to' => ''),
        2 => array('text' => _('Date To'), 'from' => $rep_date_to, 'to' => '')
    );

    $rep = new FrontReport(_('GL vs Bank Amount Mismatches'), "AmountMismatches", user_pagesize());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $data = get_amount_mismatches(date2sql($rep_date_from), date2sql($rep_date_to));

    while ($row = db_fetch($data))
    {
        $rep->NewLine();
        $rep->TextCol(0, 1, $row['type'] ?? 'N/A');
        $rep->TextCol(1, 2, $row['reference'] ?? 'N/A');
        $rep->AmountCol(2, 3, $row['gl_amount'] ?? 0, 2);
        $rep->AmountCol(3, 4, $row['bank_amount'] ?? 0, 2);
        $rep->AmountCol(4, 5, $row['variance'] ?? 0, 2);
        $rep->TextCol(5, 6, $row['tran_date'] ?? '');
    }

    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
}

print_amount_mismatches();
