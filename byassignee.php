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
 *
 * This screen show tasks plan by assignee. Unassigned tasks are shown 
 * below assigned tasks
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/techproject/classes/output/renderer_views.php');

$viewrenderer = $PAGE->get_renderer('techproject', 'views');

echo $pagebuffer;

$haveassignedtasks = false;
if (!groups_get_activity_groupmode($cm, $project->course)) {
    $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',u.email, u.picture,'.user_picture::fields();
    $groupusers = get_users_by_capability($context, 'mod/techproject:beassignedtasks', $fields, 'u.lastname');
} else {
    if ($currentgroupid) {
        $groupusers = groups_get_members($currentgroupid);
    } else {
        // We could not rely on the legacy function.
        $groupusers = techproject_get_users_not_in_group($project->course);
    }
}
if (!isset($groupusers) || count($groupusers) == 0 || empty($groupusers)) {
    echo $OUTPUT->box(get_string('noassignee','techproject'), 'center');
} else {
    echo $OUTPUT->heading(get_string('assignedtasks','techproject'));
    echo '<br/>';
    echo $OUTPUT->box_start('center', '100%');
    foreach ($groupusers as $auser) {
        techproject_complete_user($auser);

        $sql = "
           SELECT
              SUM(planned) as planned,
              SUM(done) as done,
              SUM(spent) as spent,
              COUNT(*) as count
           FROM
              {techproject_task} as t
           WHERE
              t.projectid = ? AND
              t.groupid = ? AND
              t.assignee = ?
           GROUP BY
              t.assignee
        ";
        $res = $DB->get_record_sql($sql, array($project->id, $currentgroupid, $auser->id));

        echo $viewrenderer->assignee($project, $res, $auser);

        $sql = "
           SELECT
              t.*,
              qu.label as statuslabel,
              COUNT(tts.specid) as specs
           FROM
              {techproject_qualifier} as qu,
              {techproject_task} as t
           LEFT JOIN
              {techproject_task_to_spec} as tts
           ON
              tts.taskid = t.id
           WHERE
              t.projectid = ? AND
              t.groupid = ? AND
              qu.domain = 'taskstatus' AND
              qu.code = t.status AND
              t.assignee = ?
           GROUP BY
              t.id
        ";
        $tasks = $DB->get_records_sql($sql, array($project->id, $currentgroupid, $auser->id));
        echo $viewrenderer->tasks($project, $cm, $currentgroupid, $tasks, $auser);
    }
    echo $OUTPUT->box_end();
}

// Get unassigned tasks.

$sql = "
   SELECT
      *
   FROM
      {techproject_task}
   WHERE
      projectid = ? AND
      groupid = ? AND
      assignee = 0
";
$unassignedtasks = $DB->get_records_sql($sql, array($project->id, $currentgroupid));
echo $OUTPUT->heading(get_string('unassignedtasks','techproject'));

echo $OUTPUT->box_start('center', '100%');
echo $viewrenderer->unassignedtasks($cm, $unassignedtasks);
echo $OUTPUT->box_end();
