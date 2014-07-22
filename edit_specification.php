<?php

/**
 *
 *
 *
 */

require_once($CFG->dirroot.'/mod/techproject/forms/form_specification.class.php');

$specid = optional_param('specid', '', PARAM_INT);

$mode = ($specid) ? 'update' : 'add' ;

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

    // editors pre save processing
    $draftid_editor = file_get_submitted_draft_itemid('description_editor');
    $data->description = file_save_draft_area_files($draftid_editor, $context->id, 'mod_techproject', 'specificationdescription', $data->id, array('subdirs' => true), $data->description);
    $data = file_postupdate_standard_editor($data, 'description', $mform->descriptionoptions, $context, 'mod_techproject', 'specificationdescription', $data->id);

    if ($data->specid) {
        $data->id = $data->specid; // id is course module id
        $DB->update_record('techproject_specification', $data);
        add_to_log($course->id, 'techproject', 'changespecification', "view.php?id=$cm->id&view=specifications&group={$currentgroupid}", 'update', $cm->id);

        $spectoreq = optional_param_array('spectoreq', null, PARAM_INT);
        if (count($spectoreq) > 0) {
            // removes previous mapping
            $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'specid' => $data->id));
            // stores new mapping
            foreach ($spectoreq as $aRequ) {
                $amap->id = 0;
                $amap->projectid = $project->id;
                $amap->groupid = $currentgroupid;
                $amap->reqid = $aRequ;
                $amap->specid = $data->id;
                $res = $DB->insert_record('techproject_spec_to_req', $amap);
            }
        }
    } else {
        $data->created = time();
        $data->ordering = techproject_tree_get_max_ordering($project->id, $currentgroupid, 'techproject_specification', true, $data->fatherid) + 1;
        unset($data->id); // id is course module id
        $data->id = $DB->insert_record('techproject_specification', $data);
        add_to_log($course->id, 'techproject', 'addspecification', "view.php?id=$cm->id&view=specifications&group={$currentgroupid}", 'add', $cm->id);

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
        print_error('errorspecification','techproject');
    }
    $specification->specid = $specification->id;
    $specification->id = $cm->id;
    
    echo $OUTPUT->heading(get_string('updatespec','techproject'));
}

$mform->set_data($specification);
$mform->display();
