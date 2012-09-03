<?php  //$Id: upgrade.php,v 1.1 2012-07-05 21:18:53 vf Exp $

/**
* This file keeps track of upgrades to 
* the techproject module
*
* Sometimes, changes between versions involve
* alterations to database structures and other
* major things that may break installations.
*
* The commands in here will all be database-neutral,
* using the functions defined in lib/ddllib.php
*
* @package mod-techproject
* @subpackage framework
* @category mod
* @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
* @date 2008/03/03
* @version phase1
* @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

/**
* The upgrade function in this file will attempt
* to perform all the necessary actions to upgrade
* your older installtion to the current version.
*
* If there's something it cannot do itself, it
* will tell you what you need to do.
*/
function xmldb_techproject_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

	/// Moodle 1.9 break

    return $result;
}

?>