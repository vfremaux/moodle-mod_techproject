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
 * @package    mod
 * @subpackage techproject
 * @copyright  2010 onwards Valery Fremaux {valery.fremaux@club-internet.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_techproject_activity_task
 */

/**
 * Define the complete label structure for backup, with file and id annotations
 */
class backup_techproject_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $techproject = new backup_nested_element('techproject', array('id'), array(
            'name', 'intro', 'introformat', 'projectstart', 'assessmentstart', 'projectend', 
            'timemodified', 'allowdeletewhenassigned', 'timeunit', 'costunit', 'guestsallowed', 
            'guestscanuse', 'ungroupedsees', 'grade', 'teacherusescriteria', 'allownotifications', 
            'autogradingenabled', 'autogradingweight', 'enablecvs', 'useriskcorrection', 'projectusesrequs', 
            'projectusesspecs', 'projectusesdelivs', 'projectusesvalidations', 'xslfilter', 'cssfilter'));

        $globaldomains = new backup_nested_element('globaldomains');
        $globalqualifier = new backup_nested_element('globalqualifier', array('id'), array(
            'domain', 'code', 'label', 'description'));

        $heading = new backup_nested_element('heading', array('id'), array(
            'groupid', 'title', 'abstract', 'rationale', 'environment', 'organisation', 'department'));

        $requirements = new backup_nested_element('requirements');
        $requirement = new backup_nested_element('requirement', array('id'), array(
            'fatherid', 'ordering', 'groupid', 'userid', 'created', 
            'modified', 'lastuserid', 'abstract', 'description', 'format', 'strength', 'heavyness'));

        $specifications = new backup_nested_element('specifications');
        $specification = new backup_nested_element('specification', array('id'), array(
            'fatherid', 'ordering', 'groupid', 'userid', 
            'created', 'modified', 'lastuserid', 'abstract', 'description', 'descriptionformat', 
            'priority', 'severity', 'complexity'));

        $tasks = new backup_nested_element('tasks');
        $task = new backup_nested_element('task', array('id'), array(
            'fatherid', 'ordering', 'owner', 'assignee', 'groupid', 'userid', 
            'created', 'modified', 'lastuserid', 'abstract', 'description', 
            'descriptionformat', 'worktype', 'status', 'costrate', 'planned', 'done', 
            'used', 'quoted', 'spent', 'risk', 'milestoneid', 'taskstartenable', 
            'taskstart', 'taskendenable', 'taskend'));

        $milestones = new backup_nested_element('milestones');
        $milestone = new backup_nested_element('milestone', array('id'), array(
            'ordering', 'groupid', 'userid', 'created', 'modified', 'lastuserid', 
            'abstract', 'description', 'descriptionformat', 'covered', 'cost', 'timetocomplete', 'deadline', 
            'deadlineenable'));

        $deliverables = new backup_nested_element('deliverables');
        $deliverable = new backup_nested_element('deliverable', array('id'), array(
            'fatherid', 'ordering', 'groupid', 'userid', 'created', 'modified', 
            'lastuserid', 'abstract', 'description', 'descriptionformat', 'status', 'milestoneid', 
            'localfile', 'url'));

        $validations = new backup_nested_element('validations');
        $validationsession = new backup_nested_element('validationsession', array('id'), array(
            'groupid', 'datecreated', 'dateclosed', 'createdby', 'untracked', 
            'missing', 'buggy', 'toenhance', 'refused', 'accepted'));

        $validationsessions = new backup_nested_element('validationsessions');
        $validationsession = new backup_nested_element('validationsession', array('id'), array(
        	'groupid', 'datecreated', 'dateclosed', 'createdby', 'untracked', 'missing', 'buggy', 'toenhance', 'refused', 'accepted'));
        $validationresults = new backup_nested_element('validationresults');
        $validationresult = new backup_nested_element('validationresult', array('id'), array(
            'groupid', 'reqid', 'validatorid', 'validationsessionid', 'lastchangedate', 'status', 'comment'));

        $links = new backup_nested_element('links');
        $spectoreqs = new backup_nested_element('spectoreqs');
        $spectoreq = new backup_nested_element('spectoreq', array('id'), array(
            'groupid', 'specid', 'reqid'));
        $tasktospecs = new backup_nested_element('tasktospecs');
        $tasktospec = new backup_nested_element('tasktospec', array('id'), array(
            'groupid', 'taskid', 'specid'));
        $tasktodelivs = new backup_nested_element('tasktodelivs');
        $tasktodeliv = new backup_nested_element('tasktodeliv', array('id'), array(
            'groupid', 'taskid', 'delivid'));
        $taskdeps = new backup_nested_element('taskdeps');
        $taskdep = new backup_nested_element('taskdep', array('id'), array(
            'groupid', 'master', 'slave'));

        $domains = new backup_nested_element('domains');
        $qualifier = new backup_nested_element('qualifier', array('id'), array(
            'domain', 'code', 'label', 'description'));

        $assessments = new backup_nested_element('assessments');
        $assessment = new backup_nested_element('assessment', array('id'), array(
            'groupid', 'userid', 'itemid', 'itemclass', 'criterion', 'grade'));

        $criteria = new backup_nested_element('criteria');
        $criterion = new backup_nested_element('criterion', array('id'), array(
            'criterion', 'label', 'weight', 'isfree'));

        // Build the tree
        // (love this)

        $techproject->add_child($globaldomains);
        $globaldomains->add_child($globalqualifier);

        $techproject->add_child($heading);

        $techproject->add_child($requirements);
        $requirements->add_child($requirement);

        $techproject->add_child($specifications);
        $specifications->add_child($specification);

        $techproject->add_child($tasks);
        $tasks->add_child($task);

        $techproject->add_child($milestones);
        $milestones->add_child($milestone);

        $techproject->add_child($delivs);
        $delivs->add_child($delivs);

        $techproject->add_child($validationsessions);
        $validationsessions->add_child($validationsession);
        $validationsession->add_child($validationresults);
        $validationresults->add_child($validationresult);

        $techproject->add_child($links);
        $links->add_child($spectoreqs);
        $spectoreqs->add_child($spectoreq);

        $links->add_child($spectoreqs);
        $spectoreqs->add_child($spectoreq);

        $links->add_child($tasktospecs);
        $tasktospecs->add_child($tasktospec);

        $links->add_child($tasktodelivs);
        $tasktodelivs->add_child($tasktodeliv);

        $techproject->add_child($assessments);
        $assessments->add_child($assessment);

        $techproject->add_child($criteria);
        $criteria->add_child($criterion);

        // Define sources
        $techproject->set_source_table('techproject', array('id' => backup::VAR_ACTIVITYID));
        $globalqualifier->set_source_table('techproject_qualifier', array('projectid' => 0));

        if ($userinfo) {
            $heading->set_source_table('techproject_heading', array('projectid' => backup::VAR_ACTIVITYID));
            $requirement->set_source_table('techproject_requirement', array('projectid' => backup::VAR_ACTIVITYID));
            $specification->set_source_table('techproject_specification', array('projectid' => backup::VAR_ACTIVITYID));
            $task->set_source_table('techproject_task', array('projectid' => backup::VAR_ACTIVITYID));
            $milestone->set_source_table('techproject_milestone', array('projectid' => backup::VAR_ACTIVITYID));
            $deliverable->set_source_table('techproject_deliverable', array('projectid' => backup::VAR_ACTIVITYID));

            $spectoreq->set_source_table('techproject_spec_to_req', array('projectid' => backup::VAR_ACTIVITYID));
            $tasktospec->set_source_table('techproject_task_to_spec', array('projectid' => backup::VAR_ACTIVITYID));
            $tasktodeliv->set_source_table('techproject_task_to_deliv', array('projectid' => backup::VAR_ACTIVITYID));
            $tasktdep->set_source_table('techproject_task_dependency', array('projectid' => backup::VAR_ACTIVITYID));

            $assessment->set_source_table('techproject_assessment', array('projectid' => backup::VAR_ACTIVITYID));

            $validationsession->set_source_table('techproject_valid_session', array('projectid' => backup::VAR_ACTIVITYID));
            $validationresult->set_source_table('techproject_valid_state', array('projectid' => backup::VAR_ACTIVITYID, 'validationsessionid' => backup::VAR_PARENTID));

			// we need take default and local qualifiers.
			$sql = "
				SELECT
					*
				FROM
					{techproject_qualifier}
				WHERE
					projectid = ? OR
					projectid = 0
			"; 
        	$qualifier->set_source_sql($sql, array(backup::VAR_ACTIVITYID)));

        } else {
        	$qualifier->set_source_table('techproject_qualifier', array('projectid' => 0));
        }

        // Define id annotations
        // (none)
        $requirement->annotate_ids('user', 'userid');
        $requirement->annotate_ids('user', 'lastuserid');
        $specification->annotate_ids('user', 'userid');
        $specification->annotate_ids('user', 'lastuserid');
        $task->annotate_ids('user', 'owner');
        $task->annotate_ids('user', 'assignee');
        $task->annotate_ids('user', 'userid');
        $task->annotate_ids('user', 'lastuserid');
        $milestone->annotate_ids('user', 'userid');
        $milestone->annotate_ids('user', 'lastuserid');
        $deliverable->annotate_ids('user', 'userid');
        $assessment->annotate_ids('user', 'userid');
        $validationresult->annotate_ids('user', 'validatorid');

        $requirement->annotate_ids('group', 'groupid');
        $specification->annotate_ids('group', 'groupid');
        $task->annotate_ids('group', 'groupid');
        $milestone->annotate_ids('group', 'groupid');
        $deliverable->annotate_ids('group', 'groupid');
        $spectoreq->annotate_ids('group', 'groupid');
        $tasktospec->annotate_ids('group', 'groupid');
        $tasktodeliv->annotate_ids('group', 'groupid');
        $taskdep->annotate_ids('group', 'groupid');
		$validationsession->annotate_ids('group', 'groupid');
		$validationresult->annotate_ids('group', 'groupid');
        $assessment->annotate_ids('group', 'groupid');
		
        // Define file annotations
        $techproject->annotate_files('mod_techproject', 'intro', null); // This file area hasn't itemid
        $techproject->annotate_files('mod_techproject', 'abstract', null); // This file area hasn't itemid
        $techproject->annotate_files('mod_techproject', 'rationale', null); // This file area hasn't itemid
        $techproject->annotate_files('mod_techproject', 'environment', null); // This file area hasn't itemid
        $requirement->annotate_files('mod_techproject', 'requirementdescription', 'id'); 
        $specification->annotate_files('mod_techproject', 'specificationdescription', 'id'); 
        $task->annotate_files('mod_techproject', 'taskdescription', 'id'); 
        $milestone->annotate_files('mod_techproject', 'milestonedescription', 'id'); 
        $deliverable->annotate_files('mod_techproject', 'deliverabledescription', 'id'); 
        $techproject->annotate_files('mod_techproject', 'localfile', null); 
        $techproject->annotate_files('mod_techproject', 'xslfilter', null); // This file area hasn't itemid
        $techproject->annotate_files('mod_techproject', 'cssfilter', null); // This file area hasn't itemid

        // Return the root element (techproject), wrapped into standard activity structure
        return $this->prepare_activity_structure($techproject);
    }
}
