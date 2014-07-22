<?php

/**
 * This task controller addresses all group commands including deletion.
 * @see edit_task.php for single record operations.
 *
 */

if ($work == 'dodelete') {
    $taskid = required_param('taskid', PARAM_INT);

    // Save record for further cleanups
    if ($oldtask = $DB->get_record('techproject_task', array('id' => $taskid))) {
        // delete all related records
        techproject_tree_delete($taskid, 'techproject_task');
        add_to_log($course->id, 'techproject', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentgroupid}", 'delete', $cm->id);
        //reset indicators 
        $oldtask->done = 0;
        $oldtask->planned = 0;
        $oldtask->quoted = 0;
        $oldtask->spent = 0;
        $oldtask->used = 0;
        $DB->update_record('techproject_task', $oldtask);
        // if was subtask, update branch annulation
        if ($oldtask->fatherid != 0) {
            techproject_tree_propagate_up('techproject_task', 'done', $oldtask->id, '~');
            techproject_tree_propagate_up('techproject_task', 'planned', $oldtask->id, '+');
            techproject_tree_propagate_up('techproject_task', 'quoted', $oldtask->id, '+');
            techproject_tree_propagate_up('techproject_task', 'used', $oldtask->id, '+');
            techproject_tree_propagate_up('techproject_task', 'spent', $oldtask->id, '+');
        }
    }
    // Now can delete records.
    $DB->delete_records('techproject_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'taskid' => $taskid));
    $DB->delete_records('techproject_task_dependency', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'master' => $taskid));
    $DB->delete_records('techproject_task_dependency', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'slave' => $taskid));   

/************************ Mark as 100% done ************************/

} elseif ($work == 'domarkasdone') {
    // Just completes a task with 100% done indicator..
    $ids = required_param_array('ids', PARAM_INT);
    if (is_array($ids)) {
       foreach ($ids as $anItem) {
           unset($object);
           $object->id = $anItem;
           $object->done = 100;
           $DB->update_record('techproject_task', $object);
       }
   }
}

/************************ Recalculate all indicators *****************/

// full fills a task with planned values and 100% done indicator.
elseif ($work == 'recalc') {
    techproject_tree_propagate_down($project, 'techproject_task', 'done', 0, '~');
    techproject_tree_propagate_down($project, 'techproject_task', 'planned', 0, '+');
    techproject_tree_propagate_down($project, 'techproject_task', 'quoted', 0, '+');
    techproject_tree_propagate_down($project, 'techproject_task', 'used', 0, '+');
    techproject_tree_propagate_down($project, 'techproject_task', 'spent', 0, '+');

/************************ Fullfills a task *****************/

} elseif ($work == 'fullfill') {
    $ids = required_param_array('ids', PARAM_INT);

    if (is_array($ids)) {
        $task = $DB->get_record('techproject_task', array('id' => $anItem));
        foreach ($ids as $anItem) {
            unset($object);
            $object->id     = $task->id;
            $object->done   = 100;
            $object->quoted = $task->planned * $task->costrate;
            $object->used   = $task->planned;
            $object->spent  = $task->used * $task->costrate;
            $DB->update_record('techproject_task', $object);
        }
    }
}

/************************ Move and Copy ********************/

elseif ($work == 'domove' || $work == 'docopy') {
    $ids = required_param_array('ids', PARAM_INT);
    $to = required_param('to', PARAM_ALPHA);
    $autobind = false;
    $bindtable = '';
    switch($to){
        case 'requs' : 
            $table2 = 'techproject_requirement'; 
            $redir = 'requirement'; 
            break;
        case 'specs' : 
            $table2 = 'techproject_specification'; 
            $redir = 'specification'; 
            break;
        case 'specswb' : 
            $table2 = 'techproject_specification'; 
            $redir = 'specification' ; 
            $autobind = true ; 
            $bindtable = 'techproject_spec_to_task';
            break;
        // case 'tasks' : { $table2 = 'techproject_task'; $redir = 'task'; } break;
        case 'deliv' : 
            $table2 = 'techproject_deliverable'; 
            $redir = 'deliverable'; 
            break;
        case 'delivwb' : 
            $table2 = 'techproject_deliverable'; 
            $redir = 'deliverable'; 
            $autobind = true ; 
            $bindtable = 'techproject_task_to_deliv';
            break;
    }
    techproject_tree_copy_set($ids, 'techproject_task', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
       add_to_log($course->id, 'techproject', 'change{$redir}', "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentgroupid}", 'copy/move', $cm->id);
    if ($work == 'domove'){
        // bounce to deleteitems
        $work = 'dodeleteitems';
        $withredirect = 1;
    } else {
        redirect(new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s')), get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }

/************************ Mark this task as template ******************/

} elseif ($work == 'domarkastemplate') {
    $taskid = required_param('taskid', PARAM_INT);
    $SESSION->techproject->tasktemplateid = $taskid;

/************************ Apply template *********************/

} elseif ($work == 'doapplytemplate') {
    $taskids = required_param_array('ids', PARAM_INT);
    $templateid = $SESSION->techproject->tasktemplateid;
    $ignoreroot = !optional_param('applyroot', false, PARAM_BOOL);

    foreach($taskids as $taskid){
        tree_copy_rec('task', $templateid, $taskid, $ignoreroot);
    }
}

/************************ Delete multiple items ******************/

if ($work == 'dodeleteitems') {
    $ids = required_param_array('ids', PARAM_INT);
    foreach ($ids as $anItem) {
        // save record for further cleanups and propagation
        $oldtask = $DB->get_record('techproject_task', array('id' => $anItem));
        $childs = $DB->get_records('techproject_task', array('fatherid' => $anItem));
        // update fatherid in childs 
        $query = "
            UPDATE
                {techproject_task}
            SET
                fatherid = $oldtask->fatherid
            WHERE
                fatherid = $anItem
        ";
        $DB->execute($query);
        //reset indicators
        $oldtask->done    = 0;
        $oldtask->planned = 0;
        $oldtask->quoted  = 0;
        $oldtask->used    = 0;
        $oldtask->spent   = 0;
        $DB->update_record('techproject_task', $oldtask);

        // If was subtask, update branch propagation.
        if ($oldtask->fatherid != 0) {
           techproject_tree_propagate_up('techproject_task', 'done', $oldtask->id, '~');
           techproject_tree_propagate_up('techproject_task', 'planned', $oldtask->id, '+');
           techproject_tree_propagate_up('techproject_task', 'quoted', $oldtask->id, '+');
           techproject_tree_propagate_up('techproject_task', 'used', $oldtask->id, '+');
           techproject_tree_propagate_up('techproject_task', 'spent', $oldtask->id, '+');
        }
        // Delete record for this item.
        $DB->delete_records('techproject_task', array('id' => $anItem));
        add_to_log($course->id, 'techproject', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentgroupid}", 'deleteItems', $cm->id);

        // Delete all related records.
        $DB->delete_records('techproject_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'taskid' => $anItem));
        $DB->delete_records('techproject_task_dependency', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'master' => $anItem));
        $DB->delete_records('techproject_task_dependency', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'slave' => $anItem));
        // Must rebind child dependencies to father.
        if ($oldtask->fatherid != 0 && $childs) {
            foreach ($childs as $aChild) {
                $aDependency->id        = 0;
                $aDependency->projectid = $project->id;
                $aDependency->groupid   = $currentgroupid;
                $aDependency->master    = $oldtask->fatherid;
                $aDependency->slave     = $aChild->id;
                $DB->insert_record('techproject_task_dependency', $aDependency);
            }
        }
    }
    if (isset($withredirect) && $withredirect) {
        redirect(new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s')), get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }

/************************ clear all **********************/

} elseif ($work == 'doclearall') {
    // delete all related records. POWERFUL AND DANGEROUS COMMAND.
    // deletes for the current group. 
    $DB->delete_records('techproject_task', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $DB->delete_records('techproject_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $DB->delete_records('techproject_task_to_deliv', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $DB->delete_records('techproject_task_dependency', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    add_to_log($course->id, 'techproject', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentgroupid}", 'clear', $cm->id);
} elseif ($work == 'doexport') {
    $ids = required_param_array('ids', PARAM_INT);
    $idlist = implode("','", $ids);
    $select = "
       id IN ('$idlist')
    ";
    $tasks = $DB->get_records_select('techproject_task', $select);
    $worktypes = $DB->get_records('techproject_worktype', array('projectid' => $project->id));
    if (empty($worktypes)) {
        $worktypes = $DB->get_records('techproject_worktype', array('projectid' => 0));
    }
    $taskstatusses = $DB->get_records_select('techproject_qualifier', " projectid = $project->id AND domain = 'taskstatus' ");
    if (empty($taskstatusses)) {
        $taskstatusses = $DB->get_records('techproject_qualifier', null);
    }
    include "xmllib.php";
    $xmlworktypes = recordstoxml($worktypes, 'worktype_option', '', false, 'techproject');
    $xmltaskstatusses = recordstoxml($taskstatusses, 'task_status_option', '', false, 'techproject');
    $xml = recordstoxml($tasks, 'task', $xmlworktypes.$xmltaskstatusses, true, null);
    $escaped = str_replace('<', '&lt;', $xml);
    $escaped = str_replace('>', '&gt;', $escaped);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    print_simple_box("<pre>$escaped</pre>");
    add_to_log($course->id, 'techproject', 'readtask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentgroupid}", 'export', $cm->id);
    echo $OUTPUT->continue_button("view.php?view=tasks&amp;id=$cm->id");
    return;

/************************ Raises up in level ********************/

} elseif ($work == 'up') {
    $taskid = required_param('taskid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid, $taskid, 'techproject_task');

/************************ Lowers down in levzel ***********************/

} elseif ($work == 'down') {
    $taskid = required_param('taskid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid, $taskid, 'techproject_task');

/************************ Raising one level up  *********************/

} elseif ($work == 'left') {
    $taskid = required_param('taskid', PARAM_INT);
    techproject_tree_left($project, $currentgroupid, $taskid, 'techproject_task');
    techproject_tree_propagate_up('techproject_task', 'done', $taskid, '~');
    techproject_tree_propagate_up('techproject_task', 'planned', $taskid, '+');
    techproject_tree_propagate_up('techproject_task', 'quoted', $taskid, '+');
    techproject_tree_propagate_up('techproject_task', 'used', $taskid, '+');
    techproject_tree_propagate_up('techproject_task', 'spent', $taskid, '+');

/************************ Diving one level down ***************************/

} elseif ($work == 'right') {
    $taskid = required_param('taskid', PARAM_INT);
    techproject_tree_right($project, $currentgroupid, $taskid, 'techproject_task');
}
