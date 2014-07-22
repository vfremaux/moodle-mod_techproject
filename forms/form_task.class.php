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

require_once($CFG->libdir.'/formslib.php');

class Task_Form extends moodleform {

    var $mode;
    var $project;
    var $current;
    var $descriptionoptions;

    public function __construct($action, &$project, $mode, $taskid) {
        global $DB;

        $this->mode = $mode;
        $this->project = $project;
        if ($taskid){
            $this->current = $DB->get_record('techproject_task', array('id' => $taskid));
        }

        parent::__construct($action);
    }

    public function definition() {
        global $COURSE, $DB, $USER, $OUTPUT;

        $mform = $this->_form;

        $currentGroup = 0 + groups_get_course_group($COURSE);

        $modcontext = context_module::instance($this->project->cmid);

        $maxfiles = 99;                // TODO: add some setting
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting
        $this->descriptionoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'fatherid');
        $mform->setType('fatherid', PARAM_INT);

        $mform->addElement('hidden', 'taskid');
        $mform->setType('taskid', PARAM_INT);

        $mform->addElement('hidden', 'work');
        $mform->setType('work', PARAM_TEXT);
        $mform->setDefault('work', $this->mode);

        $mform->addElement('text', 'abstract', get_string('tasktitle', 'techproject'), array('size' => "100%"));
        $mform->setType('abstract', PARAM_CLEANHTML);

        $mform->addElement('editor', 'description_editor', get_string('description', 'techproject'), null, $this->descriptionoptions);                

        $milestones = $DB->get_records_select('techproject_milestone', "projectid = ? AND groupid = ? ", array($this->project->id, $currentGroup), 'ordering ASC', 'id, abstract, ordering');
        $milestonesoptions = array();
        $milestonesoptions[0] = get_string('nomilestone', 'techproject');
        if ($milestones) {
            foreach ($milestones as $aMilestone) {
                $milestonesoptions[$aMilestone->id] = $aMilestone->abstract;
            }
        }
        $mform->addElement('select', 'milestone', get_string('milestone', 'techproject'), $milestonesoptions);
        $mform->setType('milestone', PARAM_INT);
        $mform->addHelpButton('milestone', 'task_to_miles', 'techproject');

        $ownerstr = $USER->lastname . " " . $USER->firstname . " ";
        $ownerstr .= $OUTPUT->user_picture($USER); 

        $mform->addElement('static', 'owner_st', get_string('owner', 'techproject'), $ownerstr);
        $mform->addElement('hidden', 'owner');
        $mform->setType('owner', PARAM_INT);
        $mform->setDefault('owner', $USER->id);

        $assignees = techproject_get_group_users($this->project->course, $this->project->cm, $currentGroup);
        if ($assignees) {
            $assignoptions = array();
            foreach ($assignees as $anAssignee) {
                $assignoptions[$anAssignee->id] = $anAssignee->lastname . ' ' . $anAssignee->firstname;
            }
            $mform->addElement('select', 'assignee', get_string('assignee', 'techproject'), $assignoptions);
            $mform->setType('assignee', PARAM_INT);
            // $mform->addHelpButton('assignee', 'assignee', 'techproject');
        } else {
            $mform->addElement('static', 'assignee', get_string('assignee', 'techproject'), get_string('noassignees', 'techproject'));
        }

        $mform->addElement('date_time_selector', 'taskstart', get_string('from'), array('optional' => true));
        $mform->addElement('date_time_selector', 'taskend', get_string('to'), array('optional' => true));

        $worktypes = techproject_get_options('worktype', $this->project->id);
        $worktypeoptions = array();
        foreach ($worktypes as $aWorktype) {
            $worktypeoptions[$aWorktype->code] = '['. $aWorktype->code . '] ' . $aWorktype->label;
        }
        $mform->addElement('select', 'worktype', get_string('worktype', 'techproject'), $worktypeoptions);
        $mform->setType('worktype', PARAM_TEXT);
        $mform->addHelpButton('worktype', 'worktype', 'techproject');

        $statusses = techproject_get_options('taskstatus', $this->project->id);
        $statussesoptions = array();
        foreach ($statusses as $aStatus) {
            $statussesoptions[$aStatus->code] = '['. $aStatus->code . '] ' . $aStatus->label;
        }
        $mform->addElement('select', 'status', get_string('status', 'techproject'), $statussesoptions);
        $mform->setType('status', PARAM_TEXT);
        $mform->addHelpButton('status', 'status', 'techproject');

        $mform->addElement('text', 'costrate', get_string('costrate', 'techproject'), array('size' => 6, 'onchange' => " task_update('quoted');task_update('spent') "));
        $mform->setType('costrate', PARAM_TEXT);
        $mform->addElement('text', 'planned', get_string('planned', 'techproject'), array('size' => 6, 'onchange' => " task_update('quoted') "));
        $mform->setType('planned', PARAM_INT);

        $mform->addElement('static', 'quoted', get_string('quoted', 'techproject'), "<span id=\"quoted\">".@$this->current->quoted."</span> ".$this->project->costunit);
        $mform->addHelpButton('quoted', 'quoted', 'techproject'); 

        if (@$this->project->useriskcorrection) {
            $risks = techproject_get_options('risk', $this->project->id);
            $risksesoptions = array();
            foreach ($risks as $aRisk) {
                $risksoptions[$aRisk->code] = '['. $aRisk->code . '] ' . $aRisk->label;
            }
            $mform->addElement('select', 'risk', get_string('risk', 'techproject'), $risksoptions);
            $mform->setType('risk', PARAM_INT);
        }

        $mform->addElement('text', 'done', get_string('done', 'techproject'), array('size' => 6));
        $mform->setType('done', PARAM_INT);

        $mform->addElement('text', 'used', get_string('used', 'techproject'), array('size' => 6, 'onchange' => " task_update('spent') "));
        $mform->setType('used', PARAM_INT);

        $mform->addElement('static', 'spent', get_string('spent', 'techproject'), "<span id=\"spent\">".@$this->current->spent."</span> ".$this->project->costunit);
        // $mform->addHelpButton('spent', 'spent', 'techproject'); 
        $mform->setType('spent', PARAM_INT);

        $tasks = techproject_get_tree_options('techproject_task', $this->project->id, $currentGroup);
        $selection = $DB->get_records_select_menu('techproject_task_dependency', "slave = ? ", array(0 + @$this->current->id), 'master,slave');
        $uptasksoptions = array();
        if (isset($this->current->id)) {
            foreach ($tasks as $atask) {
                if ($atask->id == @$this->current->id) {
                    continue;
                }
                $atask->abstract = format_string($atask->abstract);
                $parentid = $DB->get_field('techproject_task', 'fatherid', array('id' => $this->current->id));
                if ($atask->id == $parentid) {
                    continue;
                }
                if (techproject_check_task_circularity($this->current->id, $atask->id)) {
                    continue;
                }
                $uptasksoptions[$atask->id] = $atask->ordering.' - '.shorten_text($atask->abstract, 90);
            }
            $select = &$mform->addElement('select', 'taskdependency[]', get_string('taskdependency', 'techproject'), $uptasksoptions, array('size' => 8));
            $select->setMultiple(true);
        }

        if ($this->project->projectusesspecs && $this->mode == 'update') {
            $specifications = techproject_get_tree_options('techproject_specification', $this->project->id, $currentGroup);
            $selection = $DB->get_records_select_menu('techproject_task_to_spec', "taskid = ? ", array($this->current->id), 'specid, taskid');
            $specs = array();
            if (!empty($specifications)) {
                foreach ($specifications as $aSpecification) {
                    $specs[$aSpecification->id] = $aSpecification->ordering .' - '.shorten_text(format_string($aSpecification->abstract), 90);
                }
            }
            $select = &$mform->addElement('select', 'tasktospec[]', get_string('tasktospec', 'techproject'), $specs, array('size' => 8));
            $select->setMultiple(true);
            $mform->addHelpButton('tasktospec[]', 'task_to_spec', 'techproject');
        }

        if ($this->project->projectusesdelivs && $this->mode == 'update') {
            $deliverables = techproject_get_tree_options('techproject_deliverable', $this->project->id, $currentGroup);
            $selection = $DB->get_records_select_menu('techproject_task_to_deliv', "taskid = ? ", array($this->current->id), 'delivid, taskid');
            $delivs = array();
            if (!empty($deliverables)) {
                foreach ($deliverables as $aDeliverable) {
                    $delivs[$aDeliverable->id] = $aDeliverable->ordering .' - '.shorten_text(format_string($aDeliverable->abstract), 90);
                }
            }
            $select = &$mform->addElement('select', 'tasktodeliv[]', get_string('tasktodeliv', 'techproject'), $delivs, array('size' => 8));
            $select->setMultiple(true);
            $mform->addHelpButton('tasktodeliv[]', 'task_to_deliv', 'techproject');
        }

        $this->add_action_buttons(true);
    }

    public function set_data($defaults) {

        $context = context_module::instance($this->project->cmid);

        $draftid_editor = file_get_submitted_draft_itemid('description_editor');
        $currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_techproject', 'description_editor', $defaults->id, array('subdirs' => true), $defaults->description);
        $defaults = file_prepare_standard_editor($defaults, 'description', $this->descriptionoptions, $context, 'mod_techproject', 'taskdescription', $defaults->id);
        $defaults->description = array('text' => $currenttext, 'format' => $defaults->descriptionformat, 'itemid' => $draftid_editor);

        parent::set_data($defaults);
    }
}
