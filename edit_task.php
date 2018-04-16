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
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/techproject/forms/form_task.class.php');
$PAGE->requires->js('/mod/techproject/js/js.js');

$taskid = optional_param('taskid', '', PARAM_INT);

$mode = ($taskid) ? 'update' : 'add';

$url = new moodle_url('/mod/techproject/view.php', array('id' => $id)).'#node'.$taskid;
$project->cm = $cm;
$mform = new Task_Form($url, $project, $mode, $taskid);

if ($mform->is_cancelled()) {
    redirect($url);
}

if ($data = $mform->get_data()) {
    $data->groupid = $currentgroupid;
    $data->projectid = $project->id;
    $data->userid = $USER->id;
    $data->modified = time();
    $data->descriptionformat = $data->description_editor['format'];
    $data->description = $data->description_editor['text'];
    $data->lastuserid = $USER->id;

    if (!isset($data->quoted)) {
        $data->quoted = 0;
    }
    if (!isset($data->spent)) {
        $data->spent = 0;
    }
    if (!isset($data->risk)) {
        $data->risk = 0;
    }
    if (!isset($data->milestoneid)) {
        $data->milestoneid = 0;
    }
    if (!isset($data->taskstartenable)) {
        $data->taskstartenable = 0;
    }
    if (!isset($data->taskendenable)) {
        $data->taskendenable = 0;
    }

    // Editors pre save processing.
    $draftideditor = file_get_submitted_draft_itemid('description_editor');
    $data->description = file_save_draft_area_files($draftideditor, $context->id, 'mod_techproject', 'taskdescription',
                                                    $data->id, array('subdirs' => true), $data->description);
    $data = file_postupdate_standard_editor($data, 'description', $mform->editoroptions, $context, 'mod_techproject',
                                            'taskdescription', $data->id);

    if ($data->taskid) {
        // Id is course module id.
        $data->id = $data->taskid;
        $oldassigneeid = $DB->get_field('techproject_task', 'assignee', array('id' => $data->id));
        $DB->update_record('techproject_task', $data);

        // Get back complete task record for event snapshots.
        $data = $DB->get_record('techproject_task', array('id' => $data->id));

        $event = \mod_techproject\event\task_updated::create_from_task($project, $context, $data, $currentgroupid);
        $event->trigger();

        if (!empty($data->tasktospec)) {
            // Removes previous mapping.
            $params = array('projectid' => $project->id, 'groupid' => $currentgroupid, 'taskid' => $data->id);
            $DB->delete_records('techproject_task_to_spec', $params);

            // Stores new mapping.
            foreach ($data->tasktospec as $aspec) {
                $amap = new StdClass();
                $amap->id = 0;
                $amap->projectid = $project->id;
                $amap->groupid = $currentgroupid;
                $amap->specid = $aspec;
                $amap->taskid = $data->id;
                $res = $DB->insert_record('techproject_task_to_spec', $amap);
            }
        }

        // Todo a function ?
        if (!empty($data->tasktodeliv)) {
            // Removes previous mapping.
            $params = array('projectid' => $project->id, 'groupid' => $currentgroupid, 'taskid' => $data->id);
            $DB->delete_records('techproject_task_to_deliv', $params);
            // Stores new mapping.
            foreach ($data->tasktodeliv as $mappedid) {
                $amap = new StdClass();
                $amap->id = 0;
                $amap->projectid = $project->id;
                $amap->groupid = $currentgroupid;
                $amap->delivid = $mappedid;
                $amap->taskid = $data->id;
                $res = $DB->insert_record('techproject_task_to_deliv', $amap);
            }
        }

        if (!empty($data->taskdependency)) {
            // Removes previous mapping.
            $params = array('projectid' => $project->id, 'groupid' => $currentgroupid, 'slave' => $data->id);
            $DB->delete_records('techproject_task_dependency', $params);
            // Stores new mapping.
            foreach ($data->taskdependency as $mappedid) {
                $amap = new StdClass();
                $amap->id = 0;
                $amap->projectid = $project->id;
                $amap->groupid = $currentgroupid;
                $amap->master = $mappedid;
                $amap->slave = $data->id;
                $res = $DB->insert_record('techproject_task_dependency', $amap);
            }

            // If being reassigned, log this special event.
            if (!empty($oldassigneeid) && ($data->assignee != $oldassigneeid)) {
                $event = \mod_techproject\event\task_reassigned::create_from_task($project, $context, $data,
                                                                                  $currentgroupid, $data->assignee);
                $event->trigger();
            }

            // If notifications allowed and previous assignee exists (and is not the new assignee) notify previous assignee.
            if ($project->allownotifications && !empty($oldassigneeid) &&
                    ($data->assignee != $oldassigneeid)) {
                techproject_notify_task_unassign($project, $data, $oldassigneeid, $currentgroupid);
            }
        }
    } else {
        $data->created = time();
        $data->ordering = techproject_tree_get_max_ordering($project->id, $currentgroupid, 'techproject_task', true,
                                                            $data->fatherid) + 1;
        unset($data->id); // Id is course module id.

        $data->id = $DB->insert_record('techproject_task', $data);

        $event = \mod_techproject\event\task_created::create_from_task($project, $context, $data, $currentgroupid);
        $event->trigger();

        if ($project->allownotifications) {
            techproject_notify_new_task($project, $cm->id, $data, $currentgroupid);
        }
        if ($data->fatherid) {
            techproject_tree_updateordering($data->fatherid, 'techproject_task', true);
        }
    }

    // If subtask, force dependency upon father.
    if ($data->fatherid != 0) {
        $adependency = new StdClass();
        $adependency->id = 0;
        $adependency->projectid = $project->id;
        $adependency->groupid = $currentgroupid;
        $adependency->slave = $data->fatherid;
        $adependency->master = $data->id;
        $params = array('projectid' => $project->id, 'slave' => $data->fatherid, 'master' => $data->id);
        if (!$DB->record_exists('techproject_task_dependency', $params)) {
            $DB->insert_record('techproject_task_dependency', $adependency);
        }
    }

    // If subtask, calculate branch propagation.
    if ($data->fatherid != 0) {
        techproject_tree_propagate_up('techproject_task', 'done', $data->id, '~');
        techproject_tree_propagate_up('techproject_task', 'planned', $data->id, '+');
        techproject_tree_propagate_up('techproject_task', 'used', $data->id, '+');
        techproject_tree_propagate_up('techproject_task', 'quoted', $data->id, '+');
        techproject_tree_propagate_up('techproject_task', 'spent', $data->id, '+');
    }

    // If notifications allowed and assignee set notify assignee.
    if ($project->allownotifications && !empty($data->assignee)) {
        techproject_notify_task_assign($project, $data, $currentgroupid);
    }

    redirect($url);
}

echo $pagebuffer;

if ($mode == 'add') {
    $task = new StdClass();
    $task->fatherid = required_param('fatherid', PARAM_INT);
    $tasktitle = ($task->fatherid) ? 'addsubtask' : 'addtask';
    $task->id = $cm->id;
    $task->projectid = $project->id;
    $task->descriptionformat = FORMAT_HTML;
    $task->description = '';

    echo $OUTPUT->heading(get_string($tasktitle, 'techproject'));
} else {
    if (!$task = $DB->get_record('techproject_task', array('id' => $taskid))) {
        print_error('errortask', 'techproject');
    }
    $task->taskid = $task->id;
    $task->id = $cm->id;

    echo $OUTPUT->heading(get_string('updatetask', 'techproject'));
}

$mform->set_data($task);
$mform->display();
