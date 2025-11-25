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
 * Gant chart for the project.
 *
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @date 2008/03/03
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/techproject/classes/output/renderer_gantt.php');
$ganttrenderer = $PAGE->get_renderer('techproject', 'gantt');

echo $pagebuffer;

echo "<script type=\"text/javascript\" src=\"{$CFG->wwwroot}/mod/techproject/js/ganttevents.php?id={$course->id}&projectid={$project->id}\"></script>";

echo $OUTPUT->heading(get_string('ganttchart', 'techproject'));

$unscheduledtasks = array();
$assignees = array();
$leadtasks = array();
$gantt = false;
$parent = null;

// Delayed printing, allows evaluate if there is somethin inside.
$js = $ganttrenderer->all_tasks($parent, $project, $currentgroupid, $unscheduledtasks, $assignees, $leadtasks);

if (!empty($leadtasks)) {
    $gantt = true;
    echo $ganttrenderer->init('GantDiv');
    echo $js;
    echo $ganttrenderer->control(array_keys($leadtasks));
    echo $ganttrenderer->finish();
} else {
    echo '<center>';
    echo $OUTPUT->box(get_string('notasks', 'techproject'));
    echo '</center>';
    return;
}

echo '<br/>';
echo '<table width="100%">';

if ($unscheduledtasks) {
    echo $OUTPUT->heading_block(get_string('unscheduledtasks', 'techproject'));
    echo $OUTPUT->box_start('center', $labelwidth + $timexwidth);
    foreach ($unscheduledtasks as $atask) {
        techproject_print_single_task($atask, $project, $currentgroupid, $cm->id, count($unscheduledtasks), false, 'NOEDIT');
    }
    echo $OUTPUT->box_end();
}

echo '</table>';
echo '</center>';

if ($gantt) {
    echo $ganttrenderer->gantt('GantDiv');
}