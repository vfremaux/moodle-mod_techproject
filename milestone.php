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
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/techproject/js/milestone.js"></script>';

// Controller.

if ($work == 'add' || $work == 'update') {
    include($CFG->dirroot.'/mod/techproject/edit_milestone.php');
    // Clear all *********************************************************.

} else if ($work == 'clearall') {
    echo $pagebuffer;
    echo '<center>';
    echo $OUTPUT->heading(get_string('clearallmilestones', 'techproject'));
    echo $OUTPUT->box(get_string('clearwarning', 'techproject'), 'generalbox');

    echo $renderer->milestone_clear_form($cm);
    echo '</center>';
} else {
    if ($work) {
        include($CFG->dirroot.'/mod/techproject/milestones.controller.php');
    }

    echo $pagebuffer;
    techproject_print_milestones($project, $currentgroupid, null, $cm->id);
    if ($USER->editmode == 'on' && (has_capability('mod/techproject:changemiles', $context))) {
        $linkurl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'work' => 'add'));
        echo '<br/><a href=".$linkurl.">'.get_string('addmilestone', 'techproject').'</a>';
        $linkurl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'work' => 'clearall'));
        echo ' - <a href="'.$linkurl.'">'.get_string('clearall', 'techproject').'</a>';
        $linkurl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'work' => 'sortbydate'));
        echo ' - <a href="'.$linkurl.'">'.get_string('sortbydate', 'techproject').'</a>';
    }
}