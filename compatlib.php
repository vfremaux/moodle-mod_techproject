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
 * All version dependant code
 *
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
namespace mod_techproject;

defined('MOODLE_INTERNAL') || die();

class compat {

    public static function get_fields_for_get_cap() {
        global $CFG;

        if ($CFG->branch >= 400) {
            $fields = \core_user\fields::for_name()->with_userpic()->excluding('id')->get_required_fields();
            $fields = 'u.id,'.implode(',', $fields);
        } else {
            $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',u.username, u.email, picture, mailformat';
        }

        return $fields;
    }

    public static function get_fields_for_user_recs() {
        global $CFG;

        if ($CFG->branch >= 400) {
            $fields = \core_user\fields::for_name()->with_userpic()->get_required_fields();
            $fields = implode(',', $fields);
        } else {
            $fields = get_all_user_name_fields(true);
        }

        return $fields;
    }

    public static function page_init($cm, $project) {
        global $CFG, $PAGE;

        if ($CFG->branch >= 400) {
            $PAGE->set_cm($cm);
            $PAGE->set_activity_record($project);
        }
    }
}