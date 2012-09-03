<?php

require_once($CFG->libdir.'/formslib.php');

class Milestone_Form extends moodleform {

	var $mode;
	var $current;
	var $project;

	function __construct($action, &$project, $mode, $mileid){
		global $DB;
		
		$this->mode = $mode;
		$this->project = $project;
		if ($mileid){
			$this->current = $DB->get_record('techproject_task', array('id' => $mileid));
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
    	$mform->addElement('hidden', 'milestoneid');
    	$mform->addElement('hidden', 'work');
    	$mform->setDefault('work', $this->mode);
    	
    	$mform->addElement('text', 'abstract', get_string('milestonetitle', 'techproject'), array('size' => "100%"));

    	$mform->addElement('editor', 'description_editor', get_string('description', 'techproject'), null, $this->descriptionoptions);		    	

    	$mform->addElement('date_time_selector', 'deadline', get_string('deadline', 'techproject'), array('optional' => true));
		
		$this->add_action_buttons(true);
    }

    function set_data($defaults){

		$context = context_module::instance($this->project->cmid);

		$draftid_editor = file_get_submitted_draft_itemid('description_editor');
		$currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_techproject', 'description_editor', $defaults->id, array('subdirs' => true), $defaults->description);
		$defaults = file_prepare_standard_editor($defaults, 'description', $this->descriptionoptions, $context, 'mod_techproject', 'milestonedescription', $defaults->id);
		$defaults->description = array('text' => $currenttext, 'format' => $defaults->descriptionformat, 'itemid' => $draftid_editor);

    	parent::set_data($defaults);
    }
}
