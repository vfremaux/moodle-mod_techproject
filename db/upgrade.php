<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This screen allows remote code repository setup and control.
 *
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_techproject_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    // Moodle 1.9 => 2.0 conversion.

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
        // Define field accesskey to be added to techproject.
        $table = new xmldb_table('techproject');
        $field = new xmldb_field('accesskey');
        $field->set_attributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, 'cssfilter');

        // Launch add field cssfilter.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    return true;
}
