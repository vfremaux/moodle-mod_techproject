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
 * Project : Technical Project Manager (IEEE like)
 *
 * Gant chart for the project.
 *
 * @package mod-techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @date 2008/03/03
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once('ganttlib.php');

// we cannot use require_js because of the parameters

echo $pagebuffer;

echo "<script type=\"text/javascript\" src=\"{$CFG->wwwroot}/mod/techproject/js/ganttevents.php?id={$course->id}&projectid={$project->id}\"></script>";

echo "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$CFG->wwwroot}/mod/techproject/js/dhtmlxGantt/codebase/dhtmlxgantt.css\">";
    
echo $OUTPUT->heading(get_string('ganttchart', 'techproject'));

$sortedTasks = array();
$unscheduledTasks = array();
$assignees = array();
$leadtasks = array();
$gantt = false;
$parent = null;

// Delayed printing, allows evaluate if there is somethin inside.
gantt_print_all_tasks($parent, $project, $currentgroupid, $unscheduledTasks, $assignees, $leadtasks, $str);

if (!empty($leadtasks)) {
    $gantt = true;
    gantt_print_init('GantDiv');
    echo $str;
    gantt_print_control(array_keys($leadtasks));
    gantt_print_end();
} else {
    echo '<center>';
    echo $OUTPUT->box(get_string('notasks', 'techproject'));
    echo '</center>';
    return;
}

?>
<br/>
<table width="100%">
<?php
if ($unscheduledTasks) {
    echo $OUTPUT->heading_block(get_string('unscheduledtasks','techproject'));
    echo $OUTPUT->box_start('center', $labelWidth + $timeXWidth);
    foreach ($unscheduledTasks as $atask) {
        techproject_print_single_task($atask, $project, $currentgroupid, $cm->id, count($unscheduledTasks), false, $style='NOEDIT');
    }
    echo $OUTPUT->box_end();
}
?>
</table>
</center>

<?php
if ($gantt){
gantt_render('GantDiv');
}