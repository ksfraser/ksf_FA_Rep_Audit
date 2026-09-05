<?php
/**
 * ksf_FA_Rep_Audit - Audit and Data Integrity Reports
 *
 * @BABOK Related: FR-AUDIT-001
 */

define('SS_KSF_FA_REP_AUDIT', 146 << 8);

class hooks_ksf_FA_Rep_Audit extends hooks
{
    var $module_name = 'ksf_FA_Rep_Audit';
    var $version = '2.4.19-1.0.0';

    function install_access()
    {
        $security_sections[SS_KSF_FA_REP_AUDIT] = _('KSF Audit Reports');

        $security_types['SA_KSF_REPAUDIT'] = _('View Audit Reports');

        return array($security_sections, $security_types);
    }

    function activate_extension($check_only = false)
    {
        global $db_connections;

        $updates = array(
            'sql/install.sql' => array(),
        );

        if (!$check_only) {
            $this->register_extension('ksf_FA_Rep_Audit', $updates);
        }

        return $this->verify_updates($updates, $check_only);
    }

    function deactivate_extension($check_only = false)
    {
        return $this->remove_extension($check_only);
    }

    function getModuleConstants(&$data, $opts = array())
    {
        $data['SS_KSF_FA_REP_AUDIT'] = SS_KSF_FA_REP_AUDIT;
    }
}
