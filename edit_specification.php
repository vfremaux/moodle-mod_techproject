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

require_once($CFG->dirroot.'/mod/techproject/forms/form_specification.class.php');

$specid = optional_param('specid', '', PARAM_INT);

$mode = ($specid) ? 'update' : 'add';

$url = new moodle_url('/mod/techproject/view.php', array('id' => $id));
$mform = new Specification_Form($url, $project, $mode, $specid);

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

    // Editors pre save processing.
    $draftideditor = file_get_submitted_draft_itemid('description_editor');
    $data->description = file_save_draft_area_files($draftideditor, $context->id, 'mod_techproject', 'specificationdescription',
                                                    $data->id, array('subdirs' => true), $data->description);
    $data = file_postupdate_standard_editor($data, 'description', $mform->descriptionoptions, $context, 'mod_techproject',
                                            'specificationdescription', $data->id);

    if ($data->specid) {
        $data->id = $data->specid; // Id is course module id.
        $DB->update_record('techproject_specification', $data);
        $event = \mod_techproject\event\specification_updated::create_from_specification($project, $context, $data, $currentgroupid);
        $event->trigger();

        if (!empty($data->spectoreq)) {
            // Removes previous mapping.
            $params = array('projectid' => $project->id, 'groupid' => $currentgroupid, 'specid' => $data->id);
            $DB->delete_records('techproject_spec_to_req', $params);
            // Stores new mapping.
            foreach ($data->spectoreq as $arequ) {
                $amap = new StdClass;
                $amap->id = 0;
                $amap->projectid = $project->id;
                $amap->groupid = $currentgroupid;
                $amap->reqid = $arequ;
                $amap->specid = $data->id;
                $res = $DB->insert_record('techproject_spec_to_req', $amap);
            }
        }
    } else {
        $data->created = time();
        $data->ordering = techproject_tree_get_max_ordering($project->id, $currentgroupid, 'techproject_specification', true, $data->fatherid) + 1;
        unset($data->id); // Id is course module id.
        $data->id = $DB->insert_record('techproject_specification', $data);
        $event = \mod_techproject\event\specification_created::create_from_specification($project, $context, $data, $currentgroupid);
        $event->trigger();

        if ($project->allownotifications) {
            techproject_notify_new_specification($project, $cm->id, $data, $currentgroupid);
        }
        if ($data->fatherid) {
            techproject_tree_updateordering($data->fatherid, 'techproject_specification', true);
        }
    }
    redirect($url);
}

echo $pagebuffer;
if ($mode == 'add') {
    $specification = new StdClass();
    $specification->fatherid = required_param('fatherid', PARAM_INT);
    $spectitle = ($specification->fatherid) ? 'addsubspec' : 'addspec';
    echo $OUTPUT->heading(get_string($spectitle, 'techproject'));
    $specification->id = $cm->id;
    $specification->projectid = $project->id;
    $specification->descriptionformat = FORMAT_HTML;
    $specification->description = '';
} else {
    if (!$specification = $DB->get_record('techproject_specification', array('id' => $specid))) {
        print_error('errorspecification', 'techproject');
    }
    $specification->specid = $specification->id;
    $specification->id = $cm->id;

    echo $OUTPUT->heading(get_string('updatespec', 'techproject'));
}

$mform->set_data($specification);
$mform->display();
