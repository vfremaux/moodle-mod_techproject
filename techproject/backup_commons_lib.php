<?php

/**
* Project : Technical Project Manager (IEEE like)
*
* used in restorelib.php for restoring entities.
*
* @package mod-techproject
* @subpackage backup/restore
* @category mod
* @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
* @date 2008/03/03
* @version phase1
* @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

/**
*
*/

global $SITE;
$SITE->TECHPROJECT_BACKUP_FIELDS['requirement'] = 'abstract,description,format,strength,heavyness';
$SITE->TECHPROJECT_BACKUP_FIELDS['specification'] = 'abstract,description,format,priority,severity,complexity';
$SITE->TECHPROJECT_BACKUP_FIELDS['task'] = 'owner,assignee,abstract,description,format,worktype,status,costrate,planned,quoted,done,used,spent,risk,,milestoneid,taskstartenable,taskstart,taskendenable,taskend';
$SITE->TECHPROJECT_BACKUP_FIELDS['milestone'] = 'abstract,description,format,deadline,deadlineenable';
$SITE->TECHPROJECT_BACKUP_FIELDS['deliverable'] = 'abstract,description,format,status,milestoneid,localfile,url';

// used in restorelib.php for restoring associations.
$SITE->TECHPROJECT_ASSOC_TABLES['specid'] = 'techproject_specification';
$SITE->TECHPROJECT_ASSOC_TABLES['reqid'] = 'techproject_requirement';
$SITE->TECHPROJECT_ASSOC_TABLES['delivid'] = 'techproject_deliverable';
$SITE->TECHPROJECT_ASSOC_TABLES['taskid'] = 'techproject_task';
$SITE->TECHPROJECT_ASSOC_TABLES['master'] = 'techproject_task';
$SITE->TECHPROJECT_ASSOC_TABLES['slave'] = 'techproject_task';

if (!function_exists('backup_get_new_id')){

    /**
    * an utility function for cleaning restorelib.php code
    * @param restore the restore info structure
    * @return the new integer id
    */
    function backup_get_new_id($restorecode, $tablename, $oldid){
        $status = backup_getid($restorecode, $tablename, $oldid);
        if (is_object($status))
            return $status->new_id;
        return 0;
    }
}
?>