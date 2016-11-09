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
 *
 *
 *
 */

require_once($CFG->dirroot.'/mod/techproject/forms/form_deliverable.class.php');

$delivid = optional_param('delivid', '', PARAM_INT);

$mode = ($delivid) ? 'update' : 'add' ;

$url = new moodle_url('/mod/techproject/view.php', array('id' => $id)).'#node'.$delivid;
$mform = new Deliverable_Form($url, $mode, $project, $delivid);

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
    $data->milestoneid = 0;

    // Editors pre save processing.
    $draftid_editor = file_get_submitted_draft_itemid('description_editor');
    $data->description = file_save_draft_area_files($draftid_editor, $context->id, 'mod_techproject', 'deliverabledescription', $data->id, array('subdirs' => true), $data->description);

    if ($data->delivid) {
        $data->id = $data->delivid; // Id is course module id.
        $DB->update_record('techproject_deliverable', $data);
        $event = \mod_techproject\event\deliverable_updated::create_from_deliverable($project, $context, $data, $currentgroupid);
        $event->trigger();

        if (!empty($data->tasktodeliv)) {
            // Removes previous mapping.
            $DB->delete_records('techproject_task_to_deliv', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'delivid' => $data->id));
            // Stores new mapping.
            foreach ($data->tasktodeliv as $aTask) {
                $amap = new StdClass();
                $amap->id = 0;
                $amap->projectid = $project->id;
                $amap->groupid = $currentgroupid;
                $amap->taskid = $aTask;
                $amap->delivid = $data->id;
                $res = $DB->insert_record('techproject_task_to_deliv', $amap);
            }
        }
    } else {
        $data->created = time();
        $data->ordering = techproject_tree_get_max_ordering($project->id, $currentgroupid, 'techproject_deliverable', true, $data->fatherid) + 1;
        unset($data->id); // Id is course module id.
        $data->id = $DB->insert_record('techproject_deliverable', $data);
        $event = \mod_techproject\event\deliverable_created::create_from_deliverable($project, $context, $data, $currentgroupid);
        $event->trigger();

        if ($project->allownotifications) {
            techproject_notify_new_deliverable($project, $cm->id, $data, $currentgroupid);
        }
        if ($data->fatherid) {
            techproject_tree_updateordering($data->fatherid, 'techproject_deliverable', true);
        }
    }

    $data = file_postupdate_standard_editor($data, 'description', $mform->descriptionoptions, $context, 'mod_techproject', 'deliverabledescription', $data->id);
    $data = file_postupdate_standard_filemanager($data, 'localfile', $mform->attachmentoptions, $context, 'mod_techproject', 'deliverablelocalfile', $data->id);

    redirect($url);
}

echo $pagebuffer;
if ($mode == 'add') {
    $deliverable = new StdClass();
    $deliverable->fatherid = required_param('fatherid', PARAM_INT);
    $delivtitle = ($deliverable->fatherid) ? 'addsubdeliv' : 'adddeliv';
    echo $OUTPUT->heading(get_string($delivtitle, 'techproject'));
    $deliverable->id = $cm->id; // course module
    $deliverable->projectid = $project->id;
    $deliverable->descriptionformat = FORMAT_HTML;
    $deliverable->description = '';
} else {
    if (!$deliverable = $DB->get_record('techproject_deliverable', array('id' => $delivid))) {
        print_error('errordeliverable','techproject');
    }
    $deliverable->delivid = $deliverable->id;
    $deliverable->id = $cm->id;

    echo $OUTPUT->heading(get_string('updatedeliv','techproject'));
}

$mform->set_data($deliverable);
$mform->display();
