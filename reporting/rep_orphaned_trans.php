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
include_once($path_to_root . "/banking/includes/bank_db.inc");

function get_orphaned_transactions($date_from, $date_to)
{
    $sql = "SELECT g.type, g.type_no, g.tran_date, g.account, g.amount,
            m.account_name, t.name as type_name
            FROM " . TB_PREF . "gl_trans g
            INNER JOIN " . TB_PREF . "chart_master m ON g.account = m.account_code
            INNER JOIN " . TB_PREF . "systypes t ON g.type = t.type_id
            WHERE g.tran_date >= " . db_escape($date_from) . "
            AND g.tran_date <= " . db_escape($date_to) . "
            AND NOT EXISTS (
                SELECT 1 FROM " . TB_PREF . "bank_trans b
                WHERE b.type = g.type AND b.type_no = g.type_no
            )
            ORDER BY g.tran_date, g.type, g.type_no";
    return db_query($sql, "Could not retrieve orphaned transactions");
}

function print_orphaned_transactions()
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
    $headers = array(_('Type'), _('Ref'), _('Date'), _('Account'), _('Amount'), _('Account Name'), _('Link'));
    $aligns = array('left', 'left', 'left', 'left', 'right', 'left', 'left');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Date From'), 'from' => $rep_date_from, 'to' => ''),
        2 => array('text' => _('Date To'), 'from' => $rep_date_to, 'to' => '')
    );

    $rep = new FrontReport(_('Orphaned Transactions'), "OrphanedTrans", user_pagesize());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $data = get_orphaned_transactions(date2sql($rep_date_from), date2sql($rep_date_to));

    while ($row = db_fetch($data))
    {
        $rep->NewLine();
        $rep->TextCol(0, 1, $row['type_name'] ?? 'N/A');
        $rep->TextCol(1, 2, $row['type_no']);
        $rep->TextCol(2, 3, $row['tran_date']);
        $rep->TextCol(3, 4, $row['account']);
        $rep->AmountCol(4, 5, $row['amount'], 2);
        $rep->TextCol(5, 6, $row['account_name'] ?? '');
    }

    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
}

print_orphaned_transactions();
