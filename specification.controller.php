<?php

/// Controller
    
	if ($work == 'new') {
		$specification->groupid = $currentGroupId;
		$specification->projectid = $project->id;
		$specification->abstract = required_param('abstract', PARAM_TEXT);
		$specification->description = addslashes(required_param('description', PARAM_CLEANHTML));
        $specification->format = required_param('format', PARAM_INT);
		$specification->severity = required_param('severity', PARAM_CLEAN);
		$specification->priority = required_param('priority', PARAM_CLEAN);
		$specification->complexity = required_param('complexity', PARAM_CLEAN);
		$specification->fatherid = required_param('fatherid', PARAM_CLEAN);
		$specification->userid = $USER->id;
		$specification->created = time();
		$specification->modified = time();
		$specification->lastuserid = $USER->id;

        if (!empty($specification->abstract)){
        	$specification->ordering = techproject_tree_get_max_ordering($project->id, $currentGroupId, 'techproject_specification', true, $specification->fatherid) + 1;
    		$specification->id = insert_record('techproject_specification', $specification);
            add_to_log($course->id, 'techproject', 'addspecification', "view.php?id=$cm->id&view=specifications&group={$currentGroupId}", 'add', $cm->id);

       		// if notifications allowed notify project managers
       		if( $project->allownotifications){
       		    techproject_notify_new_specification($project, $cm->id, $specification, $currentGroupId);
           	}
        }

	}
/** ********************** **/
	elseif ($work == 'doupdate') {
		$specification->id = required_param('specid', PARAM_INT);
		$specification->abstract = required_param('abstract', PARAM_TEXT);
		$specification->description = addslashes(required_param('description', PARAM_CLEANHTML));
        $specification->format = required_param('format', PARAM_INT);
		$specification->severity = required_param('severity', PARAM_CLEAN);
		$specification->priority = required_param('priority', PARAM_CLEAN);
		$specification->complexity = required_param('complexity', PARAM_CLEAN);
		$specification->modified = time();
		$specification->lastuserid = $USER->id;

        if (!empty($specification->abstract)){
    		$res = update_record('techproject_specification', $specification);
            add_to_log($course->id, 'techproject', 'changespecification', "view.php?id=$cm->id&view=specifications&group={$currentGroupId}", 'update', $cm->id);
    		
    		// storing mapping spec to req
    		$spectoreq = optional_param('spectoreq', null, PARAM_INT);
    		if (count($spectoreq) > 0){
    		    // removes previous mapping
    		    delete_records('techproject_spec_to_req', 'projectid', $project->id, 'groupid', $currentGroupId, 'specid', $specification->id);
    
    		    // stores new mapping
        		foreach($spectoreq as $aRequ){
        		    $amap->id = 0;
        		    $amap->projectid = $project->id;
        		    $amap->groupid = $currentGroupId;
        		    $amap->reqid = $aRequ;
        		    $amap->specid = $specification->id;
        		    $res = insert_record('techproject_spec_to_req', $amap);
        		}
        	}
        }
	}
/** ********************** **/
	elseif ($work == 'dodelete') {
		$specid = required_param('specid', PARAM_INT);
		techproject_tree_delete($specid, 'techproject_specification');

        // delete related records
		delete_records('techproject_spec_to_req', 'specid', $specid);
        add_to_log($course->id, 'techproject', 'changespecification', "view.php?id=$cm->id&amp;view=specifications&amp;group={$currentGroupId}", 'delete', $cm->id);
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
		    case 'requswb' : 
		    	$table2 = 'techproject_requirement'; 
		    	$redir = 'requirement'; 
		    	$autobind = true; 
		    	$bindtable = 'techproject_spec_to_req';
		    	break;
		    case 'specs' : 
		    	$table2 = 'techproject_specification'; 
		    	$redir = 'specification'; 
		    	break;
		    case 'tasks' : 
		    	$table2 = 'techproject_task'; 
		    	$redir = 'task';
		    	break;
		    case 'taskswb' : 
		    	$table2 = 'techproject_task'; 
		    	$redir = 'task'; 
		    	$autobind = true ; 
		    	$bindtable = 'techproject_task_to_spec';
		    	break;
		    case 'deliv' : 
		    	$table2 = 'techproject_deliverable'; 
		    	$redir = 'deliverable'; 
		    	break;
		}
		techproject_tree_copy_set($ids, 'techproject_specification', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
        add_to_log($course->id, 'techproject', "change{$redir}", "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentGroupId}", 'copy/move', $cm->id);
		if ($work == 'domove'){
		    // bounce to deleteitems
		    $work = 'dodeleteitems';
		    $withredirect = 1;
		}
		else{
		    redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
	    }
	}
/** ********************** **/
   	elseif ($work == 'domarkastemplate') {
   		$specid = required_param('specid', PARAM_INT);
   		$SESSION->techproject->spectemplateid = $specid;
   	}
/** ********************** **/
   	elseif ($work == 'doapplytemplate') {
   		$specids = required_param('ids', PARAM_INT);
   		$templateid = $SESSION->techproject->spectemplateid;
   		$ignoreroot = ! optional_param('applyroot', false, PARAM_BOOL);

   		foreach($specids as $specid){
   			tree_copy_rec('specification', $templateid, $specid, $ignoreroot);
   		}
   	}
/** ********************** **/
	if ($work == 'dodeleteitems') {
		$ids = required_param('ids', PARAM_INT);
		foreach($ids as $anItem){
    	    // save record for further cleanups and propagation
    	    $oldRecord = get_record('techproject_specification', 'id', $anItem);
		    $childs = get_records('techproject_specification', 'fatherid', $anItem);
		    
		    // update fatherid in childs 
		    $query = "
		        UPDATE
		            {$CFG->prefix}techproject_specification
		        SET
		            fatherid = $oldRecord->fatherid
		        WHERE
		            fatherid = $anItem
		    ";
		    execute_sql($query);

    		delete_records('techproject_specification', 'id', $anItem);
    
            // delete all related records
    		delete_records('techproject_spec_to_req', 'projectid', $project->id, 'groupid', $currentGroupId, 'specid', $anItem);
    		delete_records('techproject_task_to_spec', 'projectid', $project->id, 'groupid', $currentGroupId, 'specid', $anItem);
    	}
        add_to_log($course->id, 'techproject', 'deletespecification', "view.php?id={$cm->id}&amp;view=specifications&amp;group={$currentGroupId}", 'deleteItems', $cm->id);
    	if (isset($withredirect) && $withredirect){
		    redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
		}
	}
/** ********************** **/
	elseif ($work == 'doclearall') {
        // delete all records. POWERFUL AND DANGEROUS COMMAND.
		delete_records('techproject_specification', 'projectid', $project->id);
		delete_records('techproject_task_to_spec', 'projectid', $project->id);
		delete_records('techproject_spec_to_req', 'projectid', $project->id);
        add_to_log($course->id, 'techproject', 'changespecification', "view.php?id={$cm->id}&amp;view=specifications&amp;group={$currentGroupId}", 'clear', $cm->id);
	}
/** ********************** **/
	elseif ($work == 'doexport') {
	    $ids = required_param('ids', PARAM_INT);
	    $idlist = implode("','", $ids);
	    $select = "
	       id IN ('$idlist')
	    ";
	    $specifications = get_records_select('techproject_specification', $select);
	    $priorities = get_records('techproject_priority', 'projectid', $project->id);
	    if (empty($priorities)){
	        $priorities = get_records('techproject_priority', 'projectid', 0);
	    }
	    $severities = get_records('techproject_severity', 'projectid', $project->id);
	    if (empty($severities)){
	        $severities = get_records('techproject_severity', 'projectid', 0);
	    }
	    $complexities = get_records('techproject_complexity', 'projectid', $project->id);
	    if (empty($complexities)){
	        $complexities = get_records('techproject_complexity', 'projectid', 0);
	    }
	    include "xmllib.php";
	    $xmlpriorities = recordstoxml($priorities, 'priority_option', '', false, 'techproject');
	    $xmlseverities = recordstoxml($severities, 'severity_option', '', false, 'techproject');
	    $xmlcomplexities = recordstoxml($complexities, 'complexity_option', '', false, 'techproject');
	    $xml = recordstoxml($specifications, 'specification', $xmlpriorities.$xmlseverities.$xmlcomplexities, true, null);
	    $escaped = str_replace('<', '&lt;', $xml);
	    $escaped = str_replace('>', '&gt;', $escaped);
	    print_heading(get_string('xmlexport', 'techproject'));
	    print_simple_box("<pre>$escaped</pre>");
        add_to_log($course->id, 'techproject', 'readspecification', "view.php?id={$cm->id}&amp;view=specifications&amp;group={$currentGroupId}", 'export', $cm->id);
        print_continue("view.php?view=specifications&amp;id=$cm->id");
        return;
	}
/** ********************** **/
	elseif ($work == 'up') {
		$specid = required_param('specid', PARAM_INT);
		techproject_tree_up($project, $currentGroupId,$specid, 'techproject_specification');
	}
/** ********************** **/
	elseif ($work == 'down') {
		$specid = required_param('specid', PARAM_INT);
		techproject_tree_down($project, $currentGroupId,$specid, 'techproject_specification');
	}
/** ********************** **/
	elseif ($work == 'left') {
		$specid = required_param('specid', PARAM_INT);
		techproject_tree_left($project, $currentGroupId,$specid, 'techproject_specification');
	}
/** ********************** **/
	elseif ($work == 'right') {
		$specid = required_param('specid', PARAM_INT);
		techproject_tree_right($project, $currentGroupId,$specid, 'techproject_specification');
	}
