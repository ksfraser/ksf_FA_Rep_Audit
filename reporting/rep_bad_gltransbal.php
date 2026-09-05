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
$page_security = 'SA_KSF_REPAUDIT';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Stock Check Sheet
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");

//----------------------------------------------------------------------------------------------------

set_time_limit( 90 );
print_bad_allocations();

//function getTransactions($category, $location, $rep_date)
function getTransactions($rep_date)
{
	//From webERP z_checkGLTransBalance.php
	//Having to translate tables/columns, this might not be quite right!!
	/*
		SELECT gltrans.type,
			gltrans.trandate,
			systypes.typename,
			gltrans.typeno,
			periodno,
			SUM(amount) AS nettot
		FROM gltrans
			INNER JOIN chartmaster ON
			gltrans.account=chartmaster.accountcode
			INNER JOIN systypes ON gltrans.type = systypes.typeid
		GROUP BY gltrans.type,
			systypes.typename,
			typeno,
			periodno
		HAVING ABS(SUM(amount))>= " . 1/pow(10,$_SESSION['CompanyRecord']['decimalplaces']) . "
		ORDER BY gltrans.counterindex
	*/

	$dec = 2;
	$dec2 = $_SESSION["wa_current_user"]->prefs->price_dec();
	if( $dec2 )
	{
		$dec = $dec2;
	}
	$sql = "SELECT g.`type` as type,  sum(g.amount) as amount, t.name as name, YEAR( g.tran_date) as year, f.closed, YEAR( f.end ) as fyear
                FROM `1_gl_trans` g
                        INNER JOIN 1_chart_master m ON g.account=m.account_code
                        INNER JOIN  1_chart_types t ON m.account_type=t.id
                        JOIN 1_fiscal_year f on YEAR( g.tran_date)=year(f.end)
                GROUP BY year, g.type, name
                HAVING ABS(SUM(g.amount)) >= 1/pow(10,2)
                order by g.tran_date";

/**************************
	$sql = "SELECT g.`type` as type, g.`type_no` as type_no, g.`tran_date` as trans_date, sum(g.amount) as amount, t.name as name 
		FROM `1_gl_trans` g
			INNER JOIN 1_chart_master m ON g.account=m.account_code
			INNER JOIN  1_chart_types t ON m.account_type=t.id
    		GROUP BY g.type, name, g.type_no, tran_date
    		HAVING ABS(SUM(g.amount)) >= 1/pow(10," . $dec . ")
    		order by g.counter";

--Shows a daily summary by account code.
SELECT g.`type` as type, g.`type_no` as type_no, g.`tran_date` as trans_date, sum(g.amount) as amount, t.name as name
                FROM `1_gl_trans` g
                        INNER JOIN 1_chart_master m ON g.account=m.account_code
                        INNER JOIN  1_chart_types t ON m.account_type=t.id
                GROUP BY tran_date, g.type, name
                HAVING ABS(SUM(g.amount)) >= 1/pow(10,2)
                order by g.counter


--Shows End of Year Balance with extraneous info
SELECT g.`type` as type, g.`type_no` as type_no, g.`tran_date` as trans_date, sum(g.amount) as amount, t.name as name, YEAR( g.tran_date) as year
                FROM `1_gl_trans` g
                        INNER JOIN 1_chart_master m ON g.account=m.account_code
                        INNER JOIN  1_chart_types t ON m.account_type=t.id
                GROUP BY year, name
                HAVING ABS(SUM(g.amount)) >= 1/pow(10,2)
                order by g.tran_date

--Shows EOY Balances.  If the year is closed, the year shouldn't have a balance!
SELECT  sum(g.amount) as amount, t.name as name, YEAR( g.tran_date) as year
                FROM `1_gl_trans` g
                        INNER JOIN 1_chart_master m ON g.account=m.account_code
                        INNER JOIN  1_chart_types t ON m.account_type=t.id
                GROUP BY year, name
                HAVING ABS(SUM(g.amount)) >= 1/pow(10,2)
                order by g.tran_date


*/


    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_bad_allocations()
{
    global $comp_path, $path_to_root, $pic_height;

	$rep_date = $_POST['PARAM_0'];
    	$comments = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];

    	//$category = $_POST['PARAM_1'];
    	//$location = $_POST['PARAM_2'];
    	//$comments = $_POST['PARAM_3'];
	//$destination = $_POST['PARAM_4'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
/****
	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);

	if ($location == ALL_TEXT)
		$location = 'all';
	if ($location == 'all')
		$loc = _('All');
	else
		$loc = get_location_name($location);
***/
		$cat = _('All');
		$loc = _('All');
		
	$cols = array(0, 25, 50, 100, 150, 225, 1100);
	$headers = array(_('Type'), _('Trans'), _('Date'), _('Amount'), _('Account Type Name'), _('Link to view'));
	$aligns = array('left',	'left', 'left',	'left', 'left', 'left');


    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				    2 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
						2 => array('text' => _('Date'), 'from' => $rep_date, 'to' => '')
    				  );

	$user_comp = "";

    $rep = new FrontReport(_('GL Transaction Balances with issues'), "GLTransBalance", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$res = getTransactions(date2sql($rep_date));
	//$res = getTransactions($category, $location,date2sql($rep_date));
	$catt = '';
	while ($trans=db_fetch($res))
	{
/*
		if ($location == 'all')
			$loc_code = "";
		else
			$loc_code = $location;
*/

/*
		if ($catt != $trans['cat_description'])
		{
			if ($catt != '')
			{
				$rep->Line($rep->row - 2);
				$rep->NewLine(2, 3);
			}
			$rep->TextCol(0, 1, $trans['category_id']);
			$rep->TextCol(1, 2, $trans['cat_description']);
			$catt = $trans['cat_description'];
			$rep->NewLine();
		}
*/
		$rep->NewLine();
		//$dec = get_qty_dec($trans['stock_id']);
		$dec = 5;
		$rep->TextCol(0, 1, $trans['type']);
		//$rep->TextCol(1, 2, $trans['type_no']);
		//$rep->TextCol(2, 3, $trans['trans_date']);
		$rep->AmountCol(3, 4, $trans['amount'], $dec);
		$rep->TextCol(4, 5, $trans['name']);
		//$rep->TextCol(5, 6, "http://fhsws002/fhs/frontaccounting/gl/view/gl_trans_view.php?type_id=" . $trans['type'] . "&trans_no=" . $trans['type_no'] );
		
	}
	$rep->Line($rep->row - 4);
	$rep->NewLine();
    $rep->End();
}

?>
