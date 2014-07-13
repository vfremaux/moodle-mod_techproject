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

/// Preconditions

	if (empty($project->projectusesrequs) && empty($project->projectusesspecs)){
		echo $OUTPUT->notification('Validation needs either requirements or specifications to be used', $CFG->wwwroot.'/mod/techproject/view.php?id='.$cm->id);
	}

/// Controller

	if ($work == 'new') {
		// close all unclosed
		if ($unclosedrecords = $DB->get_records_select('techproject_valid_session', " projectid = '$project->id' AND groupid = $currentgroupid AND dateclosed = 0 ")){
			foreach($unclosedrecords as $unclosed){
				$unclosed->dateclosed = time();
				$DB->update_record('techproject_valid_session', $unclosed);
			}
		}
		$validation = new StdClass;
		$validation->groupid = $currentgroupid;
		$validation->projectid = $project->id;
		$validation->createdby = $USER->id;
		$validation->datecreated = time();
		$validation->dateclosed = 0;

		// pre add validation session record		
		$validation->id = $DB->insert_record('techproject_valid_session', $validation);
        add_to_log($course->id, 'techproject', 'validationsession', "view.php?id={$cm->id}&amp;view=validations&amp;group={$currentgroupid}", 'create', $cm->id);

		$validation->untracked = 0;
		$validation->refused = 0;
		$validation->missing = 0;
		$validation->buggy = 0;
		$validation->toenhance = 0;
		$validation->accepted = 0;
		$validation->regressions = 0;

		// check if follow up so we need to copy previous test results as start
		if (optional_param('followup', false, PARAM_BOOL)){
			$lastsessiondate = $DB->get_field_select('techproject_valid_session', 'MAX(datecreated)', " projectid = ? AND groupid = ? ", array($project->id, $currentgroupid));
			$lastsession = $DB->get_record_select('techproject_valid_session', " datecreated = $lastsessiondate AND projectid = ? AND groupid = ? ", array($project->id, $currentgroupid));
			// copy all states
			if ($states = $DB->get_records('techproject_valid_state', array('validationsessionid' => $lastsession->id))){
				foreach($states as $state){
					$state->validationsessionid = $validation->id;
					$DB->insert_record('techproject_valid_state', $state);
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
				$items = $DB->count_records_select('techproject_requirement', " projectid = ? AND groupid = ? ", array($project->id, $currentgroupid));
			} elseif (@$project->projectusesspecs) {
				$items = $DB->count_records_select('techproject_specification', " projectid = ? AND groupid = ? ", array($project->id, $currentgroupid));
			} else {
				print_error('errornotpossible', 'techproject');
			}
			$validation->untracked = $items;
		}
		// second stage 
		$DB->update_record('techproject_valid_session', $validation);
	}
	elseif ($work == 'close') {
		$validation = new StdClass;
		$validation->id = required_param('validid', PARAM_INT);
		$validation->dateclosed = time();

		$res = $DB->update_record('techproject_valid_session', $validation);
        add_to_log($course->id, 'techproject', 'validationsession', "view.php?id={$cm->id}&amp;view=validations&amp;group={$currentgroupid}", 'close', $cm->id);
	}
	elseif ($work == 'dodelete') {
		$validid = required_param('validid', PARAM_INT);

        // delete all related records
		$DB->delete_records('techproject_valid_state', array('validationsessionid' => $validid));
		$DB->delete_records('techproject_valid_session', array('id' => $validid));
        add_to_log($course->id, 'techproject', 'validationsession', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentgroupid}", 'delete', $cm->id);
	}

/// view

	echo $pagebuffer;
	techproject_print_validations($project, $currentgroupid, 0, $cm->id);
	$createvalidationstr = get_string('createvalidationsession', 'techproject');
	$copyvalidationstr = get_string('copyvalidationsession', 'techproject');
	if (has_capability('mod/techproject:managevalidations', context_module::instance($cm->id))){
		echo '<p><center>';
		echo "<a href=\"{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view=validations&amp;work=new\">$createvalidationstr</a>";
	    echo "- <a href=\"{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view=validations&amp;work=new&amp;followup=1\">$copyvalidationstr</a>";
		echo '</center></p>';
	}

