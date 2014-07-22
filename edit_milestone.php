<?php

/**
 *
 *
 *
 */

require_once($CFG->dirroot.'/mod/techproject/forms/form_milestone.class.php');

$mileid = optional_param('milestoneid', '', PARAM_INT);

$mode = ($mileid) ? 'update' : 'add' ;

$url = $CFG->wwwroot.'/mod/techproject/view.php?id='.$id;
$mform = new Milestone_Form($url, $project, $mode, $mileid);

if ($mform->is_cancelled()){
    redirect($url);
}

if ($data = $mform->get_data()){
    $data->groupid = $currentgroupid;
    $data->projectid = $project->id;    
    $data->userid = $USER->id;
    $data->modified = time();
    $data->descriptionformat = $data->description_editor['format'];
    $data->description = $data->description_editor['text'];
    $data->lastuserid = $USER->id;
    $data->deadlineenable = ($data->deadline) ? 1 : 0;

    // editors pre save processing
    $draftid_editor = file_get_submitted_draft_itemid('description_editor');
    $data->description = file_save_draft_area_files($draftid_editor, $context->id, 'mod_techproject', 'milestonedescription', $data->id, array('subdirs' => true), $data->description);
    $data = file_postupdate_standard_editor($data, 'description', $mform->descriptionoptions, $context, 'mod_techproject', 'milestonedescription', $data->id);

    if ($data->milestoneid) {
        $data->id = $data->milestoneid; // id is course module id
        $DB->update_record('techproject_milestone', $data);
        add_to_log($course->id, 'techproject', 'changemilestone', "view.php?id=$cm->id&view=milestones&group={$currentgroupid}", 'update', $cm->id);

    } else {
        $data->created = time();
        $data->ordering = techproject_tree_get_max_ordering($project->id, $currentgroupid, 'techproject_milestone', false) + 1;
        unset($data->id); // id is course module id
        $data->id = $DB->insert_record('techproject_milestone', $data);
        add_to_log($course->id, 'techproject', 'addmile', "view.php?id=$cm->id&view=milestones&group={$currentgroupid}", 'add', $cm->id);

        if ($project->allownotifications) {
            techproject_notify_new_milestone($project, $cm->id, $data, $currentgroupid);
        }
    }
    redirect($url);
}

echo $pagebuffer;
if ($mode == 'add'){
    echo $OUTPUT->heading(get_string('addmilestone', 'techproject'));
    $milestone = new StdClass();
    $milestone->id = $cm->id;
    $milestone->projectid = $project->id;
    $milestone->descriptionformat = FORMAT_HTML;
    $milestone->description = '';
} else {
    if (!$milestone = $DB->get_record('techproject_milestone', array('id' => $mileid))) {
        print_error('errormilestone','techproject');
    }

    $milestone->milestoneid = $milestone->id;
    $milestone->id = $cm->id;

    echo $OUTPUT->heading(get_string('updatemilestone','techproject'));
}

$mform->set_data($milestone);
$mform->display();
