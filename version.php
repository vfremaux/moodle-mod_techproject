<?php

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

defined('MOODLE_INTERNAL') || die;

$plugin->version  = 2014010600;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2013111800;  // Requires this Moodle version
$plugin->component = 'mod_techproject';   // Full name of the plugin (used for diagnostics)
$plugin->cron     = 0;           // Period for cron to check this module (secs)
$plugin->maturity = MATURITY_RC;
$plugin->release = '2.6.0 (Build 2014010600)';

