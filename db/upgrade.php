<?php  //$Id: upgrade.php,v 1.1.1.1 2012-08-01 10:16:18 vf Exp $

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
    global $DB;

    $dbman = $DB->get_manager();

	/// Moodle 1.9 => 2.0 conversion

    $table = new xmldb_table('techproject');
    $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null);
    if (!$dbman->field_exists($table, $field)) {

    	$field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        $dbman->rename_field($table, $field, 'intro', false);

	    $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'intro');
	    if (!$dbman->field_exists($table, $field)) {
	        $dbman->add_field($table, $field);
	    }
	
	    $table = new xmldb_table('techproject_heading');
	
	    $field = new xmldb_field('abstractformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'abstract');
	    if (!$dbman->field_exists($table, $field)) {
	        $dbman->add_field($table, $field);
	    }
	
	    $field = new xmldb_field('rationaleformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'rationale');
	    if (!$dbman->field_exists($table, $field)) {
	        $dbman->add_field($table, $field);
	    }
	
	    $field = new xmldb_field('environmentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'environment');
	    if (!$dbman->field_exists($table, $field)) {
	        $dbman->add_field($table, $field);
	    }
	}

    if ($oldversion < 2013100200) {
    /// Define field accesskey to be added to techproject
        $table = new xmldb_table('techproject');
        $field = new xmldb_field('accesskey');
        $field->set_attributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, 'cssfilter');

    /// Launch add field cssfilter
    	if (!$dbman->field_exists($table, $field)) {
        	$dbman->add_field($table, $field);
        }
	}

    return true;
}

?>