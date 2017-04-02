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

// This generator may be included directly by an include call, or invoked
// through an HTTP request.

/**
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

function techproject_resolve_group($course, $cm) {
    if (!$groupmode = groups_get_activity_groupmode($cm, $course)) {
        // Groups are being used ?
        $currentgroupid = 0;
    } else {
        $changegroup = isset($_GET['group']) ? $_GET['group'] : -1;
        // Group change requested ?
        if (isguestuser()) {
            // For guests, use session.
            if ($changegroup >= 0) {
                $_SESSION['guestgroup'] = $changegroup;
            }
            $currentgroupid = 0 + @$_SESSION['guestgroup'];
        } else {
            // For normal users, change current group.
            $currentgroupid = 0 + get_and_set_current_group($course, $groupmode, $changegroup);
        }
    }

    return $currentgroupid;
}