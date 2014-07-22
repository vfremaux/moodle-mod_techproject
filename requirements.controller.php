<?php

if ($work == 'dodelete') {
    $requid = required_param('requid', PARAM_INT);
    techproject_tree_delete($requid, 'techproject_requirement');

    // delete all related records
    $DB->delete_records('techproject_spec_to_req', array('reqid' => $requid));
    add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentgroupid}", 'delete', $cm->id);
}
elseif ($work == 'domove' || $work == 'docopy') {
    $ids = required_param_array('ids', PARAM_INT);
    $to = required_param('to', PARAM_ALPHA);
    $autobind = false;
    $bindtable = '';
    switch($to){
        case 'specs' :
            $table2 = 'techproject_specification'; 
            $redir = 'specification'; 
            $autobind = false;
            break;
        case 'specswb' :
            $table2 = 'techproject_specification'; 
            $redir = 'specification'; 
            $autobind = true;
            $bindtable = 'techproject_spec_to_req';
            break;
        case 'tasks' : 
            $table2 = 'techproject_task'; 
            $redir = 'task'; 
            break;
        case 'deliv' : 
            $table2 = 'techproject_deliverable'; 
            $redir = 'deliverable'; 
            break;
        default:
            error('Bad copy case', $CFG->wwwroot."/mod/techproject/view.php?id=$cm->id");
    }
    techproject_tree_copy_set($ids, 'techproject_requirement', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
    add_to_log($course->id, 'techproject', "change{$redir}", "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentgroupid}", 'delete', $cm->id);
    if ($work == 'domove'){
        // bounce to deleteitems
        $work = 'dodeleteitems';
        $withredirect = 1;
    } else {
        redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }
}
if ($work == 'dodeleteitems') {
    $ids = required_param_array('ids', PARAM_INT);
    foreach($ids as $anItem){

        // save record for further cleanups and propagation
        $oldRecord = $DB->get_record('techproject_requirement', array('id' => $anItem));
        $childs = $DB->get_records('techproject_requirement', array('fatherid' => $anItem));
        // update fatherid in childs 
        $query = "
            UPDATE
                {techproject_requirement}
            SET
                fatherid = $oldRecord->fatherid
            WHERE
                fatherid = $anItem
        ";
        $DB->execute($query);

        // delete record for this item
        $DB->delete_records('techproject_requirement', array('id' => $anItem));
        // delete all related records for this item
        $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'reqid' => $anItem));
    }
    add_to_log($course->id, 'techproject', 'deleterequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentgroupid}", 'deleteItems', $cm->id);
    if (isset($withredirect) && $withredirect){
        redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }
}
elseif ($work == 'doclearall') {
    // delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_requirement', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentgroupid}", 'clear', $cm->id);
}
elseif ($work == 'doexport') {
    $ids = required_param_array('ids', PARAM_INT);
    $idlist = implode("','", $ids);
    $select = "
       id IN ('$idlist')
    ";
    $requirements = $DB->get_records_select('techproject_requirement', $select);
    $strengthes = $DB->get_records_select('techproject_qualifier', " projectid = $project->id AND domain = 'strength' ");
    if (empty($strenghes)){
        $strengthes = $DB->get_records_select('techproject_qualifier', " projectid = 0 AND domain = 'strength' ");
    }
    include "xmllib.php";
    $xmlstrengthes = recordstoxml($strengthes, 'strength', '', false, 'techproject');
    $xml = recordstoxml($requirements, 'requirement', $xmlstrengthes);
    $escaped = str_replace('<', '&lt;', $xml);
    $escaped = str_replace('>', '&gt;', $escaped);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    print_simple_box("<pre>$escaped</pre>");
    add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentgroupid}", 'export', $cm->id);
    echo $OUTPUT->continue_button("view.php?view=requirements&amp;id=$cm->id");
    return;
}
elseif ($work == 'up') {
    $requid = required_param('requid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid, $requid, 'techproject_requirement');
}
elseif ($work == 'down') {
    $requid = required_param('requid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid, $requid, 'techproject_requirement');
}
elseif ($work == 'left') {
    $requid = required_param('requid', PARAM_INT);
    techproject_tree_left($project, $currentgroupid, $requid, 'techproject_requirement');
}
elseif ($work == 'right') {
    $requid = required_param('requid', PARAM_INT);
    techproject_tree_right($project, $currentgroupid, $requid, 'techproject_requirement');
}
