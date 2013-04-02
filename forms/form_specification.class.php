<?php

require_once($CFG->libdir.'/formslib.php');

class Specification_Form extends moodleform {

	var $mode;
	var $project;
	var $current;
	var $descriptionoptions;

	function __construct($action, &$project, $mode, $specid){
		global $DB;
		
		$this->project = $project;
		$this->mode = $mode;
		if ($specid){
			$this->current = $DB->get_record('techproject_requirement', array('id' => $specid));
		}
		parent::__construct($action);
	}
    	
	function definition(){
		global $COURSE, $DB;

    	$mform = $this->_form;
    	
    	$currentGroup = 0 + groups_get_course_group($COURSE);

    	$modcontext = context_module::instance($this->project->cmid);

		$maxfiles = 99;                // TODO: add some setting
		$maxbytes = $COURSE->maxbytes; // TODO: add some setting	
		$this->descriptionoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);    	

    	$mform->addElement('hidden', 'id');
    	$mform->addElement('hidden', 'fatherid');
    	$mform->addElement('hidden', 'specid');
    	$mform->addElement('hidden', 'work');
    	$mform->setDefault('work', $this->mode);
    	
    	$mform->addElement('text', 'abstract', get_string('spectitle', 'techproject'), array('size' => "100%"));

        $severities = techproject_get_options('severity', $this->project->id);
        $severityoptions = array();
        foreach($severities as $aSeverity){
            $severityoptions[$aSeverity->code] = '['. $aSeverity->code . '] ' . $aSeverity->label;
        }
    	$mform->addElement('select', 'severity', get_string('severity', 'techproject'), $severityoptions);
		$mform->addHelpButton('severity', 'severity', 'techproject');

        $priorities = techproject_get_options('priority', $this->project->id);
        $priorityoptions = array();
        foreach($priorities as $aPriority){
            $priorityoptions[$aPriority->code] = '['. $aPriority->code . '] ' . $aPriority->label;
        }
    	$mform->addElement('select', 'priority', get_string('priority', 'techproject'), $priorityoptions);
		$mform->addHelpButton('priority', 'priority', 'techproject');

        $complexities = techproject_get_options('complexity', $this->project->id);
        $complexityoptions = array();
        foreach($complexities as $aComplexity){
            $complexityoptions[$aComplexity->code] = '['. $aComplexity->code . '] ' . $aComplexity->label;
        }
    	$mform->addElement('select', 'complexity', get_string('complexity', 'techproject'), $complexityoptions);
		$mform->addHelpButton('complexity', 'complexity', 'techproject');

    	$mform->addElement('editor', 'description_editor', get_string('description', 'techproject'), null,  $this->descriptionoptions);		    	

		if ($this->project->projectusesrequs && $this->mode == 'update'){
	        $requirements = techproject_get_tree_options('techproject_requirement', $this->project->id, $currentGroup);
	        $selection = $DB->get_records_select_menu('techproject_spec_to_req', "specid = {$this->current->id}", array(), 'reqid, specid');
	        $reqs = array();
	        if (!empty($requirements)){
	            foreach($requirements as $aRequirement){
	                $reqs[$aRequirement->id] = $aRequirement->ordering .' - '.shorten_text(format_string($aRequirement->abstract), 90);
	            }
	        }
			$select = &$mform->addElement('select', 'spectoreq', get_string('spectoreq', 'techproject'), $reqs, array('size' => 8));
			$select->setMultiple(true);
			$mform->addHelpButton('spectoreq', 'spec_to_reqs', 'techproject');
		}
		
		$this->add_action_buttons(true);
    }
    
    function set_data($defaults){

		$context = context_module::instance($this->project->cmid);

		$draftid_editor = file_get_submitted_draft_itemid('description_editor');
		$currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_techproject', 'description_editor', $defaults->id, array('subdirs' => true), $defaults->description);
		$defaults = file_prepare_standard_editor($defaults, 'description', $this->descriptionoptions, $context, 'mod_techproject', 'specificationdescription', $defaults->id);
		$defaults->description = array('text' => $currenttext, 'format' => $defaults->descriptionformat, 'itemid' => $draftid_editor);

    	parent::set_data($defaults);
    }
}
