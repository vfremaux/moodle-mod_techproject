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

if ($work == 'dodelete') {
    $milestoneid = required_param('milestoneid', PARAM_INT);
    $oldrecord = $DB->get_record('techproject_milestone', array('id' => $milestoneid));
    techproject_tree_delete($milestoneid, 'techproject_milestone', 0); // Uses list option switch.

    // Cleans up any assigned task.
    $query = "
       UPDATE
          {techproject_task}
       SET
          milestoneid = NULL
       WHERE
          milestoneid = $milestoneid
    ";
    $DB->execute($query);

    // Cleans up any assigned deliverable.
    $query = "
       UPDATE
          {techproject_deliverable}
       SET
          milestoneid = NULL
       WHERE
          milestoneid = $milestoneid
    ";
    $DB->execute($query);
    $event = \mod_techproject\event\milestone_deleted::create_from_milestone($project, $context, $oldrecord, $currentgroupid);
    $event->trigger();

} else if ($work == 'doclearall') {

    // Delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_milestone', array('projectid' => $project->id));

    // Do reset all milestone assignation in project.
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

    // Do reset all milestone assignation in project.
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
    $event = \mod_techproject\event\milestone_cleared::create_for_group($project, $context, $currentgroupid);
    $event->trigger();

} else if ($work == 'up') {

    $milestoneid = required_param('milestoneid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid, $milestoneid, 'techproject_milestone', 0);

} else if ($work == 'down') {

    $milestoneid = required_param('milestoneid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid, $milestoneid, 'techproject_milestone', 0);

} else if ($work == 'sortbydate') {

    $select = "projectid = ? AND groupid = ?";
    $milestones = array_values($DB->get_records_select('techproject_milestone', $select, array($project->id, $currentgroupid)));

    function sort_by_date($a, $b) {
        if ($a->deadline == $b->deadline) {
            return 0;
        }
        return ($a->deadline > $b->deadline) ? 1 : -1;
    }

    usort($milestones, 'sort_by_date');
    // Reorders in memory and stores back.
    $ordering = 1;
    foreach ($milestones as $amilestone) {
        $amilestone->ordering = $ordering;
        $DB->update_record('techproject_milestone', $amilestone);
        $ordering++;
    }
}
