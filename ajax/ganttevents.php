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
 * receives event from gant grid and update tasks
 *
 */

require('../../../config.php');
require_once($CFG->dirroot.'/mod/techproject/locallib.php');
require_once($CFG->dirroot.'/mod/techproject/treelib.php');

$id = required_param('id', PARAM_INT);
$projectid = required_param('projectid', PARAM_INT);
$taskid = required_param('taskid', PARAM_INT);
$event = required_param('event', PARAM_TEXT);
$arg1 = optional_param('arg1', '', PARAM_TEXT);
$arg2 = optional_param('arg2', '', PARAM_TEXT);
$arg3 = optional_param('arg3', '', PARAM_TEXT);
$arg4 = optional_param('arg4', '', PARAM_TEXT);
$arg5 = optional_param('arg5', '', PARAM_TEXT);

// A bit of security.

if (!$course = $DB->get_record('course', array('id' => $id))) {
    die("Error : Invalid Course ID");
}

require_login($course);

// Context.

$cm = get_coursemodule_from_instance('techproject', $projectid, $id);

// Check current group and change, for anyone who could.

if (!$groupmode = groups_get_activity_groupmode($cm, $course)) {
    // Groups are being used ?
    $currentgroupid = 0;
} else {
    if (isguest()) {
        // For guests, use session.
        $currentgroupid = 0 + @$_SESSION['guestgroup'];
    } else {
        // For normal users, change current group.
        $currentgroupid = 0 + groups_get_activity_group($cm, true);
    }
}

// New task comming from gantt.

// We insert by renovating the reserved task record.
if ($event == 'taskinsert') {

    $task = new StdClass;
    $task->abstract = $arg1;
    $task->description = '';
    $task->format = FORMAT_MOODLE;

    list($y, $m, $d) = explode(',', $arg2);
    $m++;
    $task->taskstart = mktime(12, 00, 00, $m, $d, $y);
    $task->taskstartenable = 1;

    $durationindays = floor($arg3 / 8);
    $remainder = $arg3 - ($durationindays * 8);

    $task->taskend = $task->taskstart + $durationindays * DAYSECS + $remainder * HOURSECS;
    $task->taskendenable = 1;
    $task->done = $arg4;
    $arg5 = 0 + $arg5;
    $task->fatherid = $arg5;
    $task->lastuserid = $USER->id;
    $task->userid = $USER->id;
    $task->owner = $USER->id;
    $task->groupid = $currentgroupid;
    $task->milestoneid = 0;
    $task->assignee = 0;
    $task->created = time();
    $task->modified = time();
    $task->projectid = $projectid;

    $select = " projectid = ? AND groupid = ? AND fatherid = ? ";
    $params = array($projectid, $currentgroupid, $arg5);
    $ordering = $DB->get_field_select('techproject_task', 'MAX(ordering)', $select, $params);
    $task->ordering = ++$ordering;

    if (!$taskid = $DB->insert_record('techproject_task', $task)) {
        echo "Error : Failed to insert new task.";
    }

    die;
} 

$task = $DB->get_record('techproject_task', array('id' => $taskid, 'projectid' => $projectid));
$taskduration = $task->taskend - $task->taskstart; // task duration in seconds

if ($event == 'taskchangebounds') {

    list($y, $m, $d) = explode(',', $arg1);
    $m++;

    $taskstartdate = getdate($task->taskstart);
    $Y = $taskstartdate['year'] = $y;
    $M = $taskstartdate['mon'] = $m;
    $D = $taskstartdate['day'] = $d;
    $H = $taskstartdate['hours'];
    $I = $taskstartdate['minutes'];
    $S = $taskstartdate['seconds'];
    $newstart = mktime($H, $I, $S, $M, $D, $Y);

    $task->taskstart = $newstart;
    $task->taskstartenable = 1;

    list($y, $m, $d) = explode(',', $arg2);
    $m++;

    $taskenddate = getdate($task->taskend);
    $Y = $taskenddate['year'] = $y;
    $M = $taskenddate['mon'] = $m;
    $D = $taskenddate['day'] = $d;
    $H = $taskenddate['hours'];
    $I = $taskenddate['minutes'];
    $S = $taskenddate['seconds'];
    $newend = mktime($H, $I, $S, $M, $D, $Y);

    $task->taskend = $newend;
    $task->taskendenable = 1;

    $DB->update_record('techproject_task', $task);
} elseif ($event == 'taskupdateattributes') {
    $task->abstract = $arg1;
    $task->done = $arg2;
    $DB->update_record('techproject_task', $task);
    if ($task->fatherid) {
        techproject_tree_propagate_up('techproject_task', 'done', $task->id, '~', false);
    }
} elseif ($event == 'taskdelete') {
    // Propagate 0 load for this task.
    if ($task->fatherid) {
        $task->done = 0;
        $task->quoted = 0;
        $task->planned = 0;
        $task->used = 0;
        $task->spent = 0;
        $DB->update_record('techproject_task', $task);
        techproject_tree_propagate_up('techproject_task', 'done', $task->id, '~', false);
        techproject_tree_propagate_up('techproject_task', 'quoted', $task->id, '+', false);
        techproject_tree_propagate_up('techproject_task', 'planned', $task->id, '+', false);
        techproject_tree_propagate_up('techproject_task', 'used', $task->id, '+', false);
        techproject_tree_propagate_up('techproject_task', 'spent', $task->id, '+', false);
    }

    $DB->delete_records('techproject_task', array('id' => $task->id));
    $DB->delete_records('techproject_task_to_deliv', array('taskid' => $task->id));
    $DB->delete_records('techproject_task_to_spec', array('taskid' => $task->id));
    $DB->delete_records('techproject_task_dependency', array('master' => $task->id));
    $DB->delete_records('techproject_task_dependency', array('slave' => $task->id));
} elseif ($event == 'taskget') {
    // Provides an interpretable JSON serialized object.
    echo "var obj = ".json_encode($task);
}
