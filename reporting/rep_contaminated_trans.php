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

function get_contaminated_transactions($date_from, $date_to)
{
    $sql = "SELECT g.type, g.type_no, g.tran_date, g.account, g.amount,
            m.account_name, t.name as type_name,
            MIN(g.tran_date) as min_date, MAX(g.tran_date) as max_date,
            COUNT(DISTINCT g.tran_date) as date_count
            FROM " . TB_PREF . "gl_trans g
            INNER JOIN " . TB_PREF . "chart_master m ON g.account = m.account_code
            INNER JOIN " . TB_PREF . "systypes t ON g.type = t.type_id
            WHERE g.tran_date >= " . db_escape($date_from) . "
            AND g.tran_date <= " . db_escape($date_to) . "
            GROUP BY g.type, g.type_no, g.account, m.account_name, t.name
            HAVING COUNT(DISTINCT g.tran_date) > 1
            ORDER BY g.type, g.type_no, g.tran_date";
    return db_query($sql, "Could not retrieve contaminated transactions");
}

function print_contaminated_transactions()
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

    $cols = array(0, 40, 80, 140, 200, 260, 320, 380);
    $headers = array(_('Type'), _('Ref'), _('Min Date'), _('Max Date'), _('Account'), _('Amount'), _('Dates'));
    $aligns = array('left', 'left', 'left', 'left', 'left', 'right', 'center');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Date From'), 'from' => $rep_date_from, 'to' => ''),
        2 => array('text' => _('Date To'), 'from' => $rep_date_to, 'to' => '')
    );

    $rep = new FrontReport(_('Contaminated Transactions'), "ContaminatedTrans", user_pagesize());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $data = get_contaminated_transactions(date2sql($rep_date_from), date2sql($rep_date_to));

    while ($row = db_fetch($data))
    {
        $rep->NewLine();
        $rep->TextCol(0, 1, $row['type_name'] ?? 'N/A');
        $rep->TextCol(1, 2, $row['type_no']);
        $rep->TextCol(2, 3, $row['min_date']);
        $rep->TextCol(3, 4, $row['max_date']);
        $rep->TextCol(4, 5, $row['account']);
        $rep->AmountCol(5, 6, $row['amount'], 2);
        $rep->TextCol(6, 7, $row['date_count'] . ' dates');
    }

    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
}

print_contaminated_transactions();
