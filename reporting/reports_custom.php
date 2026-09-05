<?php
/**
 * ksf_FA_Rep_Audit - Reports Customization
 *
 * Registers all audit and data integrity reports:
 * - Bad GL Transaction Balances
 * - Amount Mismatches (GL vs Bank)
 * - Contaminated Transactions
 * - Orphaned Transactions
 */

global $reports, $dim;

define('RC_AUDIT', 9);

$reports->addReportClass(_('Audit'), RC_AUDIT);

$reports->addReport(RC_AUDIT, '_bad_gltransbal', _('Bad GL Transaction Balances'),
    array(
        _('Date') => 'DATE',
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_AUDIT, '_amount_mismatches', _('GL vs Bank Amount Mismatches'),
    array(
        _('Date From') => 'DATEBEGIN',
        _('Date To') => 'DATEENDM',
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_AUDIT, '_contaminated_trans', _('Contaminated Transactions'),
    array(
        _('Date From') => 'DATEBEGIN',
        _('Date To') => 'DATEENDM',
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_AUDIT, '_orphaned_trans', _('Orphaned Transactions'),
    array(
        _('Date From') => 'DATEBEGIN',
        _('Date To') => 'DATEENDM',
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));
