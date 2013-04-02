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
 * @package techproject
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Valery Fremaux (valery.freamux@club-internet.fr)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_url_activity_task
 */

/**
 * Structure step to restore one techproject activity
 */
class restore_techproject_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $techproject = new restore_path_element('techproject', '/activity/techproject');
        $paths[] = $techproject;
        
        if ($userinfo){
	        $paths[] = new restore_path_element('techproject_globalqualifiers', '/activity/techproject/globalqualifiers/globalqualifier');
	        $paths[] = new restore_path_element('techproject_qualifiers', '/activity/techproject/qualifiers/qualifier');
	        $paths[] = new restore_path_element('techproject_criterion', '/activity/techproject/criteria/criterion');
	        $paths[] = new restore_path_element('techproject_requirement', '/activity/techproject/requirements/requirement');
	        $paths[] = new restore_path_element('techproject_specification', '/activity/techproject/specifications/specification');
	        $paths[] = new restore_path_element('techproject_task', '/activity/techproject/tasks/task');
	        $paths[] = new restore_path_element('techproject_milestone', '/activity/techproject/milestones/milestone');
	        $paths[] = new restore_path_element('techproject_deliverable', '/activity/techproject/deliverables/deliverable');
	        $paths[] = new restore_path_element('techproject_assessment', '/activity/techproject/assessments/assessment');
	        $paths[] = new restore_path_element('techproject_spectoreq', '/activity/techproject/spectoreqs/spectoreq');
	        $paths[] = new restore_path_element('techproject_tasktospec', '/activity/techproject/tasktospecs/tasktospec');
	        $paths[] = new restore_path_element('techproject_tasktodeliv', '/activity/techproject/tasktodelivs/tasktodeliv');
	        $paths[] = new restore_path_element('techproject_taskdependency', '/activity/techproject/taskdeps/taskdep');
	        $paths[] = new restore_path_element('techproject_validationsession', '/activity/techproject/validationsessions/validationsession');
	        $paths[] = new restore_path_element('techproject_validationresult', '/activity/techproject/validationsessions/validationsession/validationresults/validationresult');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_techproject($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->projectstart = $this->apply_date_offset($data->projectstart);
        $data->assessmentstart = $this->apply_date_offset($data->assesmentstart);
        $data->projectend = $this->apply_date_offset($data->projectend);

        // insert the label record
        $newitemid = $DB->insert_record('techproject', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_techproject_requirement($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_requirement', $data);
        $this->set_mapping('techproject_requirement', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_specification($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_specification', $data);
        $this->set_mapping('techproject_specification', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_task($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->assignee = $this->get_mappingid('user', $data->assignee);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_task', $data);
        $this->set_mapping('techproject_task', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_milestone($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);
        if ($data->deadlineenabled){
	        $data->deadline = $this->apply_date_offset($data->deadline);
	    }

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_milestone', $data);
        $this->set_mapping('techproject_milestone', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_deliverable($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);
        $data->milestoneid = $this->get_mappingid('techproject_milestone', $data->milestoneid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_deliverable', $data);
        $this->set_mapping('techproject_deliverable', $oldid, $newitemid, false); // Has no related files

        $this->add_related_files('mod_techproject', 'deliverable', 'localfile');
    }

    protected function process_techproject_spectoreq($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->specid = $this->get_mappingid('techproject_specification', $data->specid);
        $data->reqid = $this->get_mappingid('techproject_requirement', $data->reqid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_spec_to_req', $data);
        $this->set_mapping('techproject_spec_to_req', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_tasktospec($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->specid = $this->get_mappingid('techproject_specification', $data->specid);
        $data->taskid = $this->get_mappingid('techproject_task', $data->taskid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_task_to_spec', $data);
        $this->set_mapping('techproject_task_to_spec', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_tasktodeliv($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->delivid = $this->get_mappingid('techproject_deliverable', $data->delivid);
        $data->taskid = $this->get_mappingid('techproject_task', $data->taskid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_task_to_deliv', $data);
        $this->set_mapping('techproject_task_to_deliv', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_taskdependency($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->master = $this->get_mappingid('techproject_task', $data->master);
        $data->slave = $this->get_mappingid('techproject_task', $data->slave);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_task_dependency', $data);
        $this->set_mapping('techproject_task_dependency', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_criterion($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_criterion', $data);
        $this->set_mapping('techproject_criterion', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_assessment($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->criterion = $this->get_mappingid('group', $data->criterion);
        if ($data->itemclass == 'milestone'){
        	$data->itemid = $this->get_mappingid('techproject_milestone', $data->itemid);
        } elseif ($data->itemclass == 'task'){
        	$data->itemid = $this->get_mappingid('techproject_task', $data->itemid);
        } elseif ($data->itemclass == 'deliverable'){
        	$data->itemid = $this->get_mappingid('techproject_deliverable', $data->itemid);
        }


        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_task_dependency', $data);
        $this->set_mapping('techproject_task_dependency', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_validationsession($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->datecreated = $this->apply_date_offset($data->datecreated);
        $data->dateclosed = $this->apply_date_offset($data->dateclosed);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_valid_session', $data);
        $this->set_mapping('techproject_valid_session', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_validationresult($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->reqid = $this->get_mappingid('techproject_requirement', $data->reqid);
        $data->validationsessionid = $this->get_mappingid('techproject_valid_session', $data->validationsessionid);
        $data->lastchangeddate = $this->apply_date_offset($data->lastchangeddate);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_valid_session', $data);
        $this->set_mapping('techproject_valid_session', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_techproject_globalqualifier($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = 0;
        
        // do not averride pre-existing global qualifiers
        if (!$DB->exist_record('techproject_qualifier', array('domain', $data->domain, 'code' => $data->code))){
	        // The data is actually inserted into the database later in inform_new_usage_id.
	        $newitemid = $DB->insert_record('techproject_qualifier', $data);
	        $this->set_mapping('techproject_qualifier', $oldid, $newitemid, false); // Has no related files
	    }
    }

    protected function process_techproject_qualifier($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('techproject');
        
        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('techproject_qualifier', $data);
        $this->set_mapping('techproject_qualifier', $oldid, $newitemid, false); // Has no related files
    }

    protected function after_execute() {
        // Add techproject related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_techproject', 'intro', null);
        $this->add_related_files('mod_techproject', 'xslfilter', null);
        $this->add_related_files('mod_techproject', 'cssfilter', null);
        $this->add_related_files('mod_techproject', 'localfile', 'deliverable');
        
        // Remap all fatherid tree
        $this->remap_tree('requirement', 'fatherid', $this->task->get_activityid());
        $this->remap_tree('specification', 'fatherid', $this->task->get_activityid());
        $this->remap_tree('task', 'fatherid', $this->task->get_activityid());
        $this->remap_tree('deliverable', 'fatherid', $this->task->get_activityid());
    }

	/**
	* Post remaps tree dependencies in a single entity once all records renumbered. 
	*
	*/
	protected function remap_tree($entity, $treekey, $techprojectid){
		global $DB;
		
		if ($entities = $DB->get_records('techproject_'.$entity, array('id' => $techprojectid))){
			foreach ($entities as $rec){
				$newtreeid = $this->get_mappingid('techproject_'.$entity, $rec->$treekey);
				$DB->set_field('techproject_'.$entity, $treekey, $newtreeid, array('id', $rec->id));
			}
		}
	}
}
