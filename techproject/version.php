<?php // $Id: version.php,v 1.3 2011-12-22 23:23:16 vf Exp $

/**
* Project : Technical Project Manager (IEEE like)
*
* @package mod-techproject
* @subpackage framework
* @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
* @date 2008/03/03
* @version phase1
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of project
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2011122300;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007021510;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>
