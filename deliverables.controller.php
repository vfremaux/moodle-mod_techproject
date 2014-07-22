<?php

// Controller.

if ($work == 'new') {
    $deliverable->groupid = $currentgroupid;
    $deliverable->projectid = $project->id;
    $deliverable->abstract = required_param('abstract', PARAM_TEXT);
    $deliverable->description = required_param('description', PARAM_CLEANHTML);
    $deliverable->format = required_param('format', PARAM_INT);
    $deliverable->status = required_param('status', PARAM_INT);
    $deliverable->fatherid = required_param('fatherid', PARAM_INT);
    $deliverable->userid = $USER->id;
    $deliverable->created = time();
    $deliverable->modified = time();
    $deliverable->lastuserid = $USER->id;

    if (!empty($deliverable->abstract)){
        $deliverable->ordering = techproject_tree_get_max_ordering($project->id, $currentgroupid, 'techproject_deliverable', true, $deliverable->fatherid) + 1;
        $returnid = $DB->insert_record('techproject_deliverable', $deliverable);
        add_to_log($course->id, 'techproject', 'changedeliverable', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentgroupid}", 'add', $cm->id);
    }

    // if notifications allowed notify project managers
    if( $project->allownotifications){
        $class = get_string('deliverables', 'techproject');
           $status = $DB->get_record('techproject_qualifier', array('domain' => 'delivstatus', 'code' => $deliverable->status));
           if (!$status) $status->label = "N.Q.";
           $qualifiers[] = get_string('status', 'techproject').': '.$status->label;
           $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
           $message = techproject_compile_mail_template('newentrynotify', array(
               'PROJECT' => $projectheading->title,
               'CLASS' => $class,
               'USER' => fullname($USER),
               'ENTRYNODE' => implode(".", techproject_tree_get_upper_branch('techproject_deliverable', $returnid, true, true)),
               'ENTRYABSTRACT' => stripslashes($deliverable->abstract),
               'ENTRYDESCRIPTION' => $deliverable->description,
               'QUALIFIERS' => implode('<br/>', $qualifiers),
               'ENTRYLINK' => $CFG->wwwroot."/mod/techproject/view.php?id={$project->id}&view=deliverables&group={$currentgroupid}"
           ), 'techproject');               
           $managers = get_users_by_capability($context, 'mod/techproject/manage', 'u.id, firstname, lastname, email, picture, mailformat');
           if (!empty($managers)){
               foreach($managers as $manager){
                   email_to_user($manager, $USER, $course->shortname .' - '.get_string('notifynewdeliv', 'techproject'), html_to_text($message), $message);
               }
        }
       }
} elseif ($work == 'doupdate') {
    $deliverable->id = required_param('delivid', PARAM_INT);
    $deliverable->abstract = required_param('abstract', PARAM_TEXT);
    $deliverable->description = required_param('description', PARAM_TEXT);
    $deliverable->format = required_param('format', PARAM_INT);
    $deliverable->status = required_param('status', PARAM_ALPHA);
    $deliverable->milestoneid = required_param('milestoneid', PARAM_INT);
    $deliverable->url = optional_param('url', '', PARAM_CLEAN);
    $deliverable->modified = time();
    $deliverable->lastuserid = $USER->id;
    $uploader = new upload_manager('FILE_0', false, false, $course->id, true, 0, true);
    $uploader->preprocess_files();
    $deliverable->localfile = $uploader->get_new_filename();
    if (!empty($deliverable->localfile)){
        $uploader->save_files("{$course->id}/moddata/techproject/{$project->id}/".md5("techproject{$project->id}_{$currentgroupid}"));
        $deliverable->url = '';
        add_to_log($course->id, 'techproject', 'submit', "view.php?id={$cm->id}&amp;view=view_detail&amp;objectId={$deliverable->id}&amp;objectClass=deliverable&amp;group={$currentgroupid}", $project->id, $cm->id);
    }
    if (!empty($deliverable->abstract)){
        $res = $DB->update_record('techproject_deliverable', $deliverable );
        add_to_log($course->id, 'techproject', 'changedeliverable', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentgroupid}", 'update', $cm->id);
    }
} elseif ($work == 'dodelete') {
    $delivid = required_param('delivid', PARAM_INT);
    techproject_tree_delete($delivid, 'techproject_deliverable');
    add_to_log($course->id, 'techproject', 'changedeliverable', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentgroupid}", 'delete', $cm->id);
} elseif ($work == 'domove' || $work == 'docopy') {
    $ids = required_param_array('ids', PARAM_INT);
    $to = required_param('to', PARAM_ALPHA);
    switch($to){
        case 'requs' : { $table2 = 'techproject_requirement'; $redir = 'requirement'; } break;
        case 'specs' : { $table2 = 'techproject_specification'; $redir = 'specification'; } break;
        case 'tasks' : { $table2 = 'techproject_task'; $redir = 'task'; } break;
        case 'deliv' : { $table2 = 'techproject_deliverable'; $redir = 'deliverable'; } break;
    }
    techproject_tree_copy_set($ids, 'techproject_deliverable', $table2);
    add_to_log($course->id, 'techproject', 'change{$redir}', "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentgroupid}", 'copy/move', $cm->id);
    if ($work == 'domove'){
        // bounce to deleteitems
        $work = 'dodeleteitems';
        $withredirect = 1;
    } else {
        redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . get_string($redir, 'techproject'));
    }
}
if ($work == 'dodeleteitems') {
    $ids = required_param_array('ids', PARAM_INT);
    foreach($ids as $anItem){
        // save record for further cleanups and propagation
        $oldRecord = $DB->get_record('techproject_deliverable', array('id' => $anItem));
        $childs = $DB->get_records('techproject_deliverable', array('fatherid' => $anItem));
        // update fatherid in childs 
        $query = "
            UPDATE
                {techproject_deliverable}
            SET
                fatherid = $oldRecord->fatherid
            WHERE
                fatherid = $anItem
        ";
        $DB->execute($query);
        $DB->delete_records('techproject_deliverable', array('id' => $anItem));
        // delete all related records
        $DB->delete_records('techproject_task_to_deliv', array('delivid' => $anItem));
    }
    add_to_log($course->id, 'techproject', 'changedeliverable', "view.php?id={$cm->id}&amp;view=deliverable&amp;group={$currentgroupid}", 'deleteItems', $cm->id);
    if (isset($withredirect) && $withredirect){
        redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }
} elseif ($work == 'doclearall') {
    // delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_deliverable', array('projectid' => $project->id));
} elseif ($work == 'doexport') {
    $ids = required_param_array('ids', PARAM_INT);
    $idlist = implode("','", $ids);
    $select = "
       id IN ('$idlist')
    ";
    $deliverables = $DB->get_records_select('techproject_deliverable', $select);
    $delivstatusses = $DB->get_records_select('techproject_qualifier', " domain = 'delivstatus' AND projectid = $project->id ");
    if (empty($delivstatusses)) {
        $delivstatusses = $DB->get_records_select('techproject_qualifier', " domain = 'delivstatus' AND projectid = 0 ");
    }
    include "xmllib.php";
    $xmldelivstatusses = recordstoxml($delivstatusses, 'deliv_status_option', '', false, 'techproject');
    $xml = recordstoxml($deliverables, 'deliverable', $xmldelivstatusses, true, null);
    $escaped = str_replace('<', '&lt;', $xml);
    $escaped = str_replace('>', '&gt;', $escaped);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    print_simple_box("<pre>$escaped</pre>");
    add_to_log($course->id, 'techproject', 'readdeliverable', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentgroupid}", 'export', $cm->id);
    echo $OUTPUT->continue_button("view.php?view=deliverables&amp;id=$cm->id");
    return;
} elseif ($work == 'up') {
    $delivid = required_param('delivid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid,$delivid, 'techproject_deliverable');
} elseif ($work == 'down') {
    $delivid = required_param('delivid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid,$delivid, 'techproject_deliverable');
} elseif ($work == 'left') {
    $delivid = required_param('delivid', PARAM_INT);
    techproject_tree_left($project, $currentgroupid,$delivid, 'techproject_deliverable');
} elseif ($work == 'right') {
    $delivid = required_param('delivid', PARAM_INT);
    techproject_tree_right($project, $currentgroupid,$delivid, 'techproject_deliverable');
}

