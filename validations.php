<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Validations operations.
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    $usehtmleditor = can_use_html_editor();
    $defaultformat = FORMAT_MOODLE;

/// Preconditions

	if (empty($project->projectusesrequs) && empty($project->projectusesspecs)){
		notify('Validation needs either requirements or specifications to be used', $CFG->wwwroot.'/mod/techproject/view.php?id='.$cm->id);
	}

/// Controller

	if ($work == 'new') {
		
		// close all unclosed
		if ($unclosedrecords = get_records_select('techproject_valid_session', " projectid = '$project->id' AND groupid = $currentGroupId AND dateclosed = 0 ")){
			foreach($unclosedrecords as $unclosed){
				$unclosed->dateclosed = time();
				update_record('techproject_valid_session', $unclosed);
			}
		}
				
		$validation->groupid = $currentGroupId;
		$validation->projectid = $project->id;
		$validation->createdby = $USER->id;
		$validation->datecreated = time();
		$validation->dateclosed = 0;

		// pre add validation session record		
		$validation->id = insert_record('techproject_valid_session', $validation);
        add_to_log($course->id, 'techproject', 'validationsession', "view.php?id={$cm->id}&amp;view=validations&amp;group={$currentGroupId}", 'create', $cm->id);

		$validation->untracked = 0;
		$validation->refused = 0;
		$validation->missing = 0;
		$validation->buggy = 0;
		$validation->toenhance = 0;
		$validation->accepted = 0;
		$validation->regressions = 0;

		// check if follow up so we need to copy previous test results as start
		if (optional_param('followup', false, PARAM_BOOL)){
			$lastsessiondate = get_field_select('techproject_valid_session', 'MAX(datecreated)', " projectid = $project->id AND groupid = $currentGroupId ");
			$lastsession = get_record_select('techproject_valid_session', " datecreated = $lastsessiondate AND projectid = $project->id AND groupid = $currentGroupId ");
			
			// copy all states
			if ($states = get_records('techproject_valid_state', 'validationsessionid', $lastsession->id)){
				foreach($states as $state){
					$state->validationsessionid = $validation->id;
					insert_record('techproject_valid_state', $state);
					
					$validation->untracked += ($state->status == 'UNTRACKED') ? 1 : 0 ;
					$validation->refused += ($state->status == 'REFUSED') ? 1 : 0 ;
					$validation->missing += ($state->status == 'MISSING') ? 1 : 0 ;
					$validation->buggy += ($state->status == 'BUGGY') ? 1 : 0 ;
					$validation->toenhance += ($state->status == 'TOENHANCE') ? 1 : 0 ;
					$validation->accepted += ($state->status == 'ACCEPTED') ? 1 : 0 ;
					$validation->regressions += ($state->status == 'REGRESSION') ? 1 : 0 ;
				}
			}			
		} else {
			if (@$project->projectusesrequs){
				$items = count_records_select('techproject_requirement', " projectid = $project->id AND groupid = $currentGroupId ");
			} elseif (@$project->projectusesspecs) {
				$items = count_records_select('techproject_specification', " projectid = $project->id AND groupid = $currentGroupId ");
			} else {
				error("Not possible.");
			}
			$validation->untracked = $items;
		}
		
		// second stage 
		update_record('techproject_valid_session', $validation);
	}
	elseif ($work == 'close') {
		$validation->id = required_param('validid', PARAM_INT);
		$validation->dateclosed = time();

		$res = update_record('techproject_valid_session', $validation);
        add_to_log($course->id, 'techproject', 'validationsession', "view.php?id={$cm->id}&amp;view=validations&amp;group={$currentGroupId}", 'close', $cm->id);
	}
	elseif ($work == 'dodelete') {
		$validid = required_param('validid', PARAM_INT);

        // delete all related records
		delete_records('techproject_valid_state', 'validationsessionid', $validid);
		delete_records('techproject_valid_session', 'id', $validid);
        add_to_log($course->id, 'techproject', 'validationsession', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'delete', $cm->id);
	}

/// view

	techproject_print_validations($project, $currentGroupId, 0, $cm->id);
	
	$createvalidationstr = get_string('createvalidationsession', 'techproject');
	$copyvalidationstr = get_string('copyvalidationsession', 'techproject');
	
	if (has_capability('mod/techproject:managevalidations', get_context_instance(CONTEXT_MODULE, $cm->id))){
		echo '<p><center>';
		echo "<a href=\"{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view=validations&amp;work=new\">$createvalidationstr</a>";
	    echo "- <a href=\"{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view=validations&amp;work=new&amp;followup=1\">$copyvalidationstr</a>";
		echo '</center></p>';
	}

?>