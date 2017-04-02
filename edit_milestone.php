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

require_once($CFG->dirroot.'/mod/techproject/forms/form_milestone.class.php');

$mileid = optional_param('milestoneid', '', PARAM_INT);

$mode = ($mileid) ? 'update' : 'add';

$url = new moodle_url('/mod/techproject/view.php', array('id' => $id));
$mform = new Milestone_Form($url, $project, $mode, $mileid);

if ($mform->is_cancelled()) {
    redirect($url);
}

if ($data = $mform->get_data()) {
    $data->groupid = $currentgroupid;
    $data->projectid = $project->id;
    $data->userid = $USER->id;
    $data->modified = time();
    $data->covered = 0;
    $data->cost = 0;
    $data->timetocomplete = 0;
    $data->descriptionformat = $data->description_editor['format'];
    $data->description = $data->description_editor['text'];
    $data->lastuserid = $USER->id;
    $data->deadlineenable = ($data->deadline) ? 1 : 0;

    // Editors pre save processing.
    $draftideditor = file_get_submitted_draft_itemid('description_editor');
    $data->description = file_save_draft_area_files($draftideditor, $context->id, 'mod_techproject', 'milestonedescription',
                                                    $data->id, array('subdirs' => true), $data->description);
    $data = file_postupdate_standard_editor($data, 'description', $mform->descriptionoptions, $context, 'mod_techproject',
                                            'milestonedescription', $data->id);

    if ($data->milestoneid) {
        $data->id = $data->milestoneid; // Id is course module id.
        $DB->update_record('techproject_milestone', $data);
        $event = \mod_techproject\event\milestone_updated::create_from_milestone($project, $context, $data, $currentgroupid);
        $event->trigger();

    } else {
        $data->created = time();
        $data->ordering = techproject_tree_get_max_ordering($project->id, $currentgroupid, 'techproject_milestone', false) + 1;
        unset($data->id); // Id is course module id.
        $data->id = $DB->insert_record('techproject_milestone', $data);
        $event = \mod_techproject\event\milestone_created::create_from_milestone($project, $context, $data, $currentgroupid);
        $event->trigger();

        if ($project->allownotifications) {
            techproject_notify_new_milestone($project, $cm->id, $data, $currentgroupid);
        }
    }
    redirect($url);
}

echo $pagebuffer;
if ($mode == 'add') {
    echo $OUTPUT->heading(get_string('addmilestone', 'techproject'));
    $milestone = new StdClass();
    $milestone->id = $cm->id;
    $milestone->projectid = $project->id;
    $milestone->descriptionformat = FORMAT_HTML;
    $milestone->description = '';
} else {
    if (!$milestone = $DB->get_record('techproject_milestone', array('id' => $mileid))) {
        print_error('errormilestone', 'techproject');
    }
    $milestone->milestoneid = $milestone->id;
    $milestone->id = $cm->id;

    echo $OUTPUT->heading(get_string('updatemilestone', 'techproject'));
}

$mform->set_data($milestone);
$mform->display();

