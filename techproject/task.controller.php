<?php

   	if ($work == 'new') {
   		$task->groupid = $currentGroupId;
   		$task->projectid = $project->id;
        $task->abstract = required_param('abstract', PARAM_TEXT);
        $task->description = addslashes(required_param('description', PARAM_CLEANHTML));
        $task->format = required_param('format', PARAM_INT);
        $task->fatherid = required_param('fatherid', PARAM_INT);
        $task->worktype = required_param('worktype', PARAM_ALPHA);
        $task->assignee = optional_param('assignee', 0, PARAM_INT);
        $task->owner = required_param('owner', PARAM_INT);
        $task->status = required_param('status', PARAM_ALPHA);
        $task->costrate = optional_param('costrate', '0', PARAM_INT);
        $task->planned = 0 + required_param('planned', PARAM_INT);
        $task->quoted = (float)$task->costrate * (float)$task->planned;
        $task->used = 0 + required_param('used', PARAM_INT);
        $task->done = 0 + required_param('done', PARAM_INT);
        $task->spent = (float)$task->costrate * (float)$task->used;
        $task->risk = 0 + required_param('risk', PARAM_INT);
        $task->milestoneid = optional_param('milestoneid', 0, PARAM_INT);
        $task->taskstartenable = optional_param('taskstartenable', '0', PARAM_INT);
        $taskstartyear = optional_param('taskstartyear', 0, PARAM_INT);
        $taskstartmonth = optional_param('taskstartmonth', 0, PARAM_INT);
        $taskstartday = optional_param('taskstartday', 0, PARAM_INT);
        $taskstarthour = optional_param('taskstarthour', 0, PARAM_INT);
        $taskstartminute = optional_param('taskstartminute', 0, PARAM_INT);
        $task->taskstart = 0 + make_timestamp($taskstartyear, 
                                              $taskstartmonth, 
                                              $taskstartday, 
                                              $taskstarthour, 
                                              $taskstartminute);
        if ($task->taskstart == 0) $task->taskstart = time();
        $taskendyear = optional_param('taskendyear', 0, PARAM_INT);
        $taskendmonth = optional_param('taskendmonth', 0, PARAM_INT);
        $taskendday = optional_param('taskendday', 0, PARAM_INT);
        $taskendhour = optional_param('taskendhour', 0, PARAM_INT);
        $taskendminute = optional_param('taskendminute', 0, PARAM_INT);
        $task->taskendenable = optional_param('taskendenable', '0', PARAM_INT);
        $task->taskend = 0 + make_timestamp($taskendyear, 
                                            $taskendmonth, 
                                            $taskendday, 
                                            $taskendhour, 
                                            $taskendminute);
        if ($task->taskend == 0){
             $task->taskend = $project->projectend;
        }
   		$task->userid = $USER->id;
   		$task->created = time();
   		$task->modified = time();
   		$task->lastuserid = $USER->id;
           
        // perform insertion
        $controls = checkConstraints($project, $task);
        if (!$controls){
            if (!empty($task->abstract)){
               	$lastordering = techproject_tree_get_max_ordering($project->id, $currentGroupId, 'techproject_task', true, $task->fatherid);
               	$task->ordering = $lastordering + 1;
           		$task->id = insert_record('techproject_task', $task);
                add_to_log($course->id, 'techproject', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'add', $cm->id);
           		
           		// if subtask, force dependency on father
           		if ($task->fatherid != 0){
                    $aDependency->id = 0;
           		    $aDependency->projectid = $project->id;
           		    $aDependency->groupid = $currentGroupId;
           		    $aDependency->slave = $task->fatherid;
           		    $aDependency->master = $task->id;
           		    insert_record('techproject_task_dependency', $aDependency);
           		}
           		
           		// if subtask, calculate branch propagation
           		if ($task->fatherid != 0){
           		    techproject_tree_propagate_up('techproject_task', 'done', $task->id, '~');
           		    techproject_tree_propagate_up('techproject_task', 'planned', $task->id, '+');
           		    techproject_tree_propagate_up('techproject_task', 'used', $task->id, '+');
           		    techproject_tree_propagate_up('techproject_task', 'quoted', $task->id, '+');
           		    techproject_tree_propagate_up('techproject_task', 'spent', $task->id, '+');
           		}

           		// if notifications allowed and assignee set notify assignee
           		if( $project->allownotifications && !empty($task->assignee)){
           		    techproject_notify_task_assign($project, $task, $currentGroupId);
               	}
           	}
        } else {
              $work = 'add';
        }
/** ********************** **/
   	} elseif ($work == 'doupdate') {
   		$task->id = required_param('taskid', PARAM_INT);
   		$task->fatherid = required_param('fatherid', PARAM_INT);
   		$task->abstract = required_param('abstract', PARAM_TEXT);
   		$task->description = addslashes(required_param('description', PARAM_CLEANHTML));
        $task->format = required_param('format', PARAM_INT);
   		$task->assignee = optional_param('assignee', 0, PARAM_INT);
   		$task->owner = required_param('owner', PARAM_INT);
   		$task->done = required_param('done', PARAM_INT);
   		$task->status = required_param('status', PARAM_ALPHA);
   		$task->worktype = required_param('worktype', PARAM_ALPHA);
   		$task->costrate = required_param('costrate', PARAM_INT);
   		$task->planned = required_param('planned', PARAM_INT);
   		$task->quoted = (float)$task->costrate * (float)$task->planned;
   		$task->used = required_param('used', PARAM_INT);
   		$task->spent = (float)$task->costrate * (float)$task->used;
        $task->risk = required_param('risk', PARAM_INT);
        $task->milestoneid = optional_param('milestoneid', 0, PARAM_INT);
        $task->taskstartenable = optional_param('taskstartenable', '0', PARAM_INT);
        $taskstartyear = optional_param('taskstartyear', 0, PARAM_INT);
        $taskstartmonth = optional_param('taskstartmonth', 0, PARAM_INT);
        $taskstartday = optional_param('taskstartday', 0, PARAM_INT);
        $taskstarthour = optional_param('taskstarthour', 0, PARAM_INT);
        $taskstartminute = optional_param('taskstartminute', 0, PARAM_INT);
        $task->taskstart = 0 + make_timestamp($taskstartyear, 
                                              $taskstartmonth, 
                                              $taskstartday, 
                                              $taskstarthour, 
                                              $taskstartminute);
        if ($task->taskstart == 0) unset($task->taskstart);
        $task->taskendenable = optional_param('taskendenable', '0', PARAM_INT);
        $taskendyear = optional_param('taskendyear', 0, PARAM_INT);
        $taskendmonth = optional_param('taskendmonth', 0, PARAM_INT);
        $taskendday = optional_param('taskendday', 0, PARAM_INT);
        $taskendhour = optional_param('taskendhour', 0, PARAM_INT);
        $taskendminute = optional_param('taskendminute', 0, PARAM_INT);
        $task->taskend = 0 + make_timestamp($taskendyear, 
                                            $taskendmonth, 
                                            $taskendday, 
                                            $taskendhour, 
                                            $taskendminute);
        if ($task->taskend == 0) unset($task->taskend);
   		$task->modified = time();
   		$task->lastuserid = $USER->id;
   
        $controls = checkConstraints($project, $task);
        if (!$controls){
            if (!empty($task->abstract)){
           		$oldAssigneeId = get_field('techproject_task', 'assignee', 'id', $task->id);
       		    $res = update_record('techproject_task', $task);
                add_to_log($course->id, 'techproject', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'update', $cm->id);
       
           		// storing mapping task to spec
           		$tasktospec = optional_param('tasktospec', null, PARAM_INT);
           		if (count($tasktospec) > 0){
           		    // removes previous mapping
           		    delete_records('techproject_task_to_spec', 'projectid', $project->id, 'groupid', $currentGroupId, 'taskid', $task->id);
           
           		    // stores new mapping
               		foreach($tasktospec as $aSpec){
               		    $amap->id = 0;
               		    $amap->projectid = $project->id;
               		    $amap->groupid = $currentGroupId;
               		    $amap->specid = $aSpec;
               		    $amap->taskid = $task->id;
               		    $res = insert_record('techproject_task_to_spec', $amap);
               		}
               	}
           
           		// storing mapping task to task (dependencies)
           		$taskdependency = optional_param('taskdependency', null, PARAM_INT);
           		if (count($taskdependency) > 0){
           		    // removes previous mapping
           		    delete_records('techproject_task_dependency', 'projectid', $project->id, 'groupid', $currentGroupId, 'slave', $task->id);
           
           		    // stores new mapping
               		// if subtask, force dependency on father
               		if ($task->fatherid != 0){
               		    $aDependancy->id = 0;
               		    $aDependancy->projectid = $project->id;
               		    $aDependancy->groupid = $currentGroupId;
               		    $aDependancy->slave = $task->id;
               		    $aDependancy->master = $task->fatherid;
               		    insert_record('techproject_task_to_task', $aDependancy);
                       }
                       // store other mappings
               		foreach($taskdependency as $aMaster){
               		    $amap->id = 0;
               		    $amap->projectid = $project->id;
               		    $amap->groupid = $currentGroupId;
              		        $amap->master = $aMaster;
               		    $amap->slave = $task->id;
               		    $res = insert_record('techproject_task_dependency', $amap);
               		}
               	}
           
           		// storing mapping task to deliv
           		$tasktodeliv = optional_param('tasktodeliv', null, PARAM_INT);
           		if (count($tasktodeliv) > 0){
           		    // removes previous mapping
           		    delete_records('techproject_task_to_deliv', 'projectid', $project->id, 'groupid', $currentGroupId, 'taskid', $task->id);
           
           		    // stores new mapping
               		foreach($tasktodeliv as $aDeliv){
               		    $amap->id = 0;
               		    $amap->projectid = $project->id;
               		    $amap->groupid = $currentGroupId;
               		    $amap->delivid = $aDeliv;
               		    $amap->taskid = $task->id;
               		    $res = insert_record('techproject_task_to_deliv', $amap);
               		}
               	}
           
           		// if subtask, calculate branch propagation
           		if ($task->fatherid != 0){
           		    techproject_tree_propagate_up('techproject_task', 'done', $task->id, '~', false);
           		    techproject_tree_propagate_up('techproject_task', 'planned', $task->id, '+', false);
           		    techproject_tree_propagate_up('techproject_task', 'quoted', $task->id, '+', false);
           		    techproject_tree_propagate_up('techproject_task', 'used', $task->id, '+', false);
           		    techproject_tree_propagate_up('techproject_task', 'spent', $task->id, '+', false);
           		}
           		
           		// if notifications allowed and previous assignee exists (and is not the new assignee) notify previous assignee
           		if( $project->allownotifications && !empty($oldAssigneeId) && $task->assignee != $oldAssigneeId){
                    techproject_notify_task_unassign($project, $task, $oldAssigneeId, $currentGroupId);
               	}
           
           		// if notifications allowed and assignee set notify assignee
           		if( $project->allownotifications && !empty($task->assignee)){
           		    techproject_notify_task_assign($project, $task, $currentGroupId);
               	}
            }
        } else {
           $work = 'update';
        }
/** ********************** **/
   	} elseif ($work == 'dodelete') {
        $taskid = required_param('taskid', PARAM_INT);
   	    
   	    // save record for further cleanups
   	    $oldtask = get_record('techproject_task', 'id', $taskid);
   
        // delete all related records
   		techproject_tree_delete($taskid, 'techproject_task');
   
        add_to_log($course->id, 'techproject', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'delete', $cm->id);
   
        //reset indicators 
        $oldtask->done      = 0;
        $oldtask->planned   = 0;
        $oldtask->quoted    = 0;
        $oldtask->spent     = 0;
        $oldtask->used      = 0;
        update_record('techproject_task', addslashes_recursive($oldtask));
   
   		// if was subtask, update branch annulation
   		if ($oldtask->fatherid != 0){
   		    techproject_tree_propagate_up('techproject_task', 'done', $oldtask->id, '~');
   		    techproject_tree_propagate_up('techproject_task', 'planned', $oldtask->id, '+');
   		    techproject_tree_propagate_up('techproject_task', 'quoted', $oldtask->id, '+');
   		    techproject_tree_propagate_up('techproject_task', 'used', $oldtask->id, '+');
   		    techproject_tree_propagate_up('techproject_task', 'spent', $oldtask->id, '+');
   		}
   
           // now can delete records
   		delete_records('techproject_task_to_spec', 'projectid', $project->id, 'groupid', $currentGroupId, 'taskid', $taskid);
   		delete_records('techproject_task_dependency', 'projectid', $project->id, 'groupid', $currentGroupId, 'master', $taskid);
   		delete_records('techproject_task_dependency', 'projectid', $project->id, 'groupid', $currentGroupId, 'slave', $taskid);   
/** ********************** **/
   	} elseif ($work == 'domarkasdone') {    	
        // just completes a task with 100% done indicator.
   		$ids = required_param('ids', PARAM_INT);
   		if (is_array($ids)){
       		foreach($ids as $anItem){
       		    unset($object);
       		    $object->id = $anItem;
       		    $object->done = 100;
                   update_record('techproject_task', $object);
       		}
       	}
   	}
/** ********************** **/
   	// full fills a task with planned values and 100% done indicator.
   	elseif ($work == 'recalc') {
	    techproject_tree_propagate_down($project, 'techproject_task', 'done', 0, '~');
	    techproject_tree_propagate_down($project, 'techproject_task', 'planned', 0, '+');
	    techproject_tree_propagate_down($project, 'techproject_task', 'quoted', 0, '+');
	    techproject_tree_propagate_down($project, 'techproject_task', 'used', 0, '+');
	    techproject_tree_propagate_down($project, 'techproject_task', 'spent', 0, '+');
/** ********************** **/
   	} elseif ($work == 'fullfill') {
   		$ids = required_param('ids', PARAM_INT);
   		if (is_array($ids)){
   		    $task = get_record('techproject_task', 'id', $anItem);
       		foreach($ids as $anItem){
       		    unset($object);
       		    $object->id     = $task->id;
       		    $object->done   = 100;
       		    $object->quoted = $task->planned * $task->costrate;
       		    $object->used   = $task->planned;
       		    $object->spent  = $task->used * $task->costrate;
                update_record('techproject_task', $object);
       		}
       	}
   	}
/** ********************** **/
   	elseif ($work == 'domove' || $work == 'docopy') {
   		$ids = required_param('ids', PARAM_INT);
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
           add_to_log($course->id, 'techproject', 'change{$redir}', "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentGroupId}", 'copy/move', $cm->id);
   		if ($work == 'domove'){
   		    // bounce to deleteitems
   		    $work = 'dodeleteitems';
   		    $withredirect = 1;
   		} else {
   		    redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
   	    }
/** ********************** **/
   	} elseif ($work == 'domarkastemplate') {
   		$taskid = required_param('taskid', PARAM_INT);
   		$SESSION->techproject->tasktemplateid = $taskid;
/** ********************** **/
   	} elseif ($work == 'doapplytemplate') {
   		$taskids = required_param('ids', PARAM_INT);
   		$templateid = $SESSION->techproject->tasktemplateid;
   		$ignoreroot = ! optional_param('applyroot', false, PARAM_BOOL);

   		foreach($taskids as $taskid){
   			tree_copy_rec('task', $templateid, $taskid, $ignoreroot);
   		}
   	}
/** ********************** **/
   	if ($work == 'dodeleteitems') {
   		$ids = required_param('ids', PARAM_INT);
   		foreach($ids as $anItem){		    
       	    // save record for further cleanups and propagation
       	    $oldtask = get_record('techproject_task', 'id', $anItem);
   		    $childs = get_records('techproject_task', 'fatherid', $anItem);
   		    
   		    // update fatherid in childs 
   		    $query = "
   		        UPDATE
   		            {$CFG->prefix}techproject_task
   		        SET
   		            fatherid = $oldtask->fatherid
   		        WHERE
   		            fatherid = $anItem
   		    ";
   		    execute_sql($query);
   
               //reset indicators 
               $oldtask->done    = 0;
               $oldtask->planned = 0;
               $oldtask->quoted  = 0;
               $oldtask->used    = 0;
               $oldtask->spent   = 0;
               update_record('techproject_task', addslashes_recursive($oldtask));
       
       		// if was subtask, update branch propagation
       		if ($oldtask->fatherid != 0){
       		    techproject_tree_propagate_up('techproject_task', 'done', $oldtask->id, '~');
       		    techproject_tree_propagate_up('techproject_task', 'planned', $oldtask->id, '+');
       		    techproject_tree_propagate_up('techproject_task', 'quoted', $oldtask->id, '+');
       		    techproject_tree_propagate_up('techproject_task', 'used', $oldtask->id, '+');
       		    techproject_tree_propagate_up('techproject_task', 'spent', $oldtask->id, '+');
       		}
   
               // delete record for this item
       		delete_records('techproject_task', 'id', $anItem);
            add_to_log($course->id, 'techproject', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'deleteItems', $cm->id);

               // delete all related records
       		delete_records('techproject_task_to_spec', 'projectid', $project->id, 'groupid', $currentGroupId, 'taskid', $anItem);
       		delete_records('techproject_task_dependency', 'projectid', $project->id, 'groupid', $currentGroupId, 'master', $anItem);
       		delete_records('techproject_task_dependency', 'projectid', $project->id, 'groupid', $currentGroupId, 'slave', $anItem);
       		
       		// must rebind child dependencies to father 
       		if ($oldtask->fatherid != 0 && $childs){
           		foreach($childs as $aChild){
           		    $aDependency->id        = 0;
           		    $aDependency->projectid = $project->id;
           		    $aDependency->groupid   = $currentGroupId;
           		    $aDependency->master    = $oldtask->fatherid;
           		    $aDependency->slave     = $aChild->id;
           		    insert_record('techproject_task_dependency', $aDependency);
           		}
           	}   
       	}
       	if (isset($withredirect) && $withredirect){
   		    redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
   		}
/** ********************** **/
   	} elseif ($work == 'doclearall') {
           // delete all related records. POWERFUL AND DANGEROUS COMMAND.
           // deletes for the current group. 
   		delete_records('techproject_task', 'projectid', $project->id, 'groupid', $currentGroupId);
   		delete_records('techproject_task_to_spec', 'projectid', $project->id, 'groupid', $currentGroupId);
   		delete_records('techproject_task_to_deliv', 'projectid', $project->id, 'groupid', $currentGroupId);
   		delete_records('techproject_task_dependency', 'projectid', $project->id, 'groupid', $currentGroupId);
           add_to_log($course->id, 'techproject', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'clear', $cm->id);
   	} elseif ($work == 'doexport') {
   	    $ids = required_param('ids', PARAM_INT);
   	    $idlist = implode("','", $ids);
   	    $select = "
   	       id IN ('$idlist')	       
   	    ";
   	    $tasks = get_records_select('techproject_task', $select);
   	    $worktypes = get_records('techproject_worktype', 'projectid', $project->id);
   	    if (empty($worktypes)){
   	        $worktypes = get_records('techproject_worktype', 'projectid', 0);
   	    }
   	    $taskstatusses = get_records_select('techproject_qualifier', " projectid = $project->id AND domain = 'taskstatus' ");
   	    if (empty($taskstatusses)){
   	        $taskstatusses = get_records('techproject_qualifier', " projectid = 0 AND domain = 'taskstatus' ");
   	    }
   	    include "xmllib.php";
   	    $xmlworktypes = recordstoxml($worktypes, 'worktype_option', '', false, 'techproject');
   	    $xmltaskstatusses = recordstoxml($taskstatusses, 'task_status_option', '', false, 'techproject');
   	    $xml = recordstoxml($tasks, 'task', $xmlworktypes.$xmltaskstatusses, true, null);
   	    $escaped = str_replace('<', '&lt;', $xml);
   	    $escaped = str_replace('>', '&gt;', $escaped);
   	    print_heading(get_string('xmlexport', 'techproject'));
   	    print_simple_box("<pre>$escaped</pre>");
           add_to_log($course->id, 'techproject', 'readtask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'export', $cm->id);
           print_continue("view.php?view=tasks&amp;id=$cm->id");
           return;
/** ********************** **/
   	} elseif ($work == 'up') {
   	    $taskid = required_param('taskid', PARAM_INT);
   		techproject_tree_up($project, $currentGroupId, $taskid, 'techproject_task');
/** ********************** **/
   	} elseif ($work == 'down') {
   	    $taskid = required_param('taskid', PARAM_INT);
   		techproject_tree_down($project, $currentGroupId, $taskid, 'techproject_task');
/** ********************** **/
   	} elseif ($work == 'left') {
   	    $taskid = required_param('taskid', PARAM_INT);
   		techproject_tree_left($project, $currentGroupId, $taskid, 'techproject_task');
   	    techproject_tree_propagate_up('techproject_task', 'done', $taskid, '~');
   	    techproject_tree_propagate_up('techproject_task', 'planned', $taskid, '+');
   	    techproject_tree_propagate_up('techproject_task', 'quoted', $taskid, '+');
   	    techproject_tree_propagate_up('techproject_task', 'used', $taskid, '+');
   	    techproject_tree_propagate_up('techproject_task', 'spent', $taskid, '+');
/** ********************** **/
   	} elseif ($work == 'right') {
   	    $taskid = required_param('taskid', PARAM_INT);
   		techproject_tree_right($project, $currentGroupId, $taskid, 'techproject_task');
/** ********************** **/
   	} elseif ($work == 'showcost') {
   		$SESSION->techproject_taskshow = 'cost';
   	} elseif ($work == 'showrisk') {
   		$SESSION->techproject_taskshow = 'risk';
   	} elseif ($work == 'hideall') {
   		$SESSION->techproject_taskshow = '';
   	}

?>