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

defined('MOODLE_INTERNAL') || die();

/**
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

if ($work == 'dodelete') {
    $milestoneid = required_param('milestoneid', PARAM_INT);
    $oldRecord = $DB->get_record('techproject_milestone', array('id' => $milestoneid));
    techproject_tree_delete($milestoneid, 'techproject_milestone', 0); // uses list option switch

    // cleans up any assigned task.
    $query = "
       UPDATE
          {techproject_task}
       SET
          milestoneid = NULL
       WHERE
          milestoneid = $milestoneid
    ";
    $DB->execute($query);

    // cleans up any assigned deliverable.
    $query = "
       UPDATE
          {techproject_deliverable}
       SET
          milestoneid = NULL
       WHERE
          milestoneid = $milestoneid
    ";
    $DB->execute($query);
    // add_to_log($course->id, 'techproject', 'changemilestone', "view.php?id=$cm->id&view=milestone&group={$currentgroupid}", 'delete', $cm->id);
    $event = \mod_techproject\event\milestone_deleted::create_from_milestone($project, $context, $oldRecord, $currentgroupid);
    $event->trigger();

} elseif ($work == 'doclearall') {

    // Delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_milestone', array('projectid' => $project->id));

    // do reset all milestone assignation in project
    $query = "
       UPDATE
          {techproject_task}
       SET
          milestoneid = NULL
       WHERE
          projectid = {$project->id} AND
          groupid = {$currentgroupid}
    ";
    $DB->execute($query);

    // do reset all milestone assignation in project
    $query = "
       UPDATE
          {techproject_deliverable}
       SET
          milestoneid = NULL
       WHERE
          projectid = {$project->id} AND
          groupid = {$currentgroupid}
    ";
    $DB->execute($query);
    // add_to_log($course->id, 'techproject', 'changemilestones', "view.php?id=$cm->id&view=milestone&group={$currentgroupid}", 'clear', $cm->id);
    $event = \mod_techproject\event\milestone_cleared::create_for_group($project, $context, $currentgroupid);
    $event->trigger();

} elseif ($work == 'up') {

    $milestoneid = required_param('milestoneid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid,$milestoneid, 'techproject_milestone', 0);

} elseif ($work == 'down') {

    $milestoneid = required_param('milestoneid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid,$milestoneid, 'techproject_milestone', 0);

} elseif ($work == 'sortbydate') {

    $milestones = array_values($DB->get_records_select('techproject_milestone', "projectid = {$project->id} AND groupid = {$currentgroupid}"));

    function sortByDate($a, $b) {
        if ($a->deadline == $b->deadline) {
            return 0;
        }
        return ($a->deadline > $b->deadline) ? 1 : -1 ; 
    }

    usort($milestones, 'sortByDate');
    // Reorders in memory and stores back.
    $ordering = 1;
    foreach ($milestones as $aMilestone) {
        $aMilestone->ordering = $ordering;
        $DB->update_record('techproject_milestone', $aMilestone);
        $ordering++;
    }
}
