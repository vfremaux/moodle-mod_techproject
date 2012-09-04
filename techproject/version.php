<?php // $Id: version.php,v 1.1 2012-07-05 21:18:49 vf Exp $

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

$module->version  = 2012090400;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2011120500;  // Requires this Moodle version
$module->component = 'mod_techproject';   // Full name of the plugin (used for diagnostics)
$module->cron     = 0;           // Period for cron to check this module (secs)
$module->release  = '2.2.0 (build 2012090400)';           // Period for cron to check this module (secs)
$module->maturiy  = 'MATURITY_BETA';

