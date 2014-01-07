<?php

require_once($CFG->libdir.'/formslib.php');

class Deliverable_Form extends moodleform {

	var $mode;
	var $project;
	var $current;
	var $descriptionoptions;

	function __construct($action, $mode, &$project, $delivid){
		global $DB;
		
		$this->mode = $mode;
		$this->project = $project;
		if ($delivid){
			$this->current = $DB->get_record('techproject_deliverable', array('id' => $delivid));
		}
		parent::__construct($action);
	}
    	
	function definition(){
		global $COURSE, $DB;

    	$mform = $this->_form;
    	
    	$modcontext = context_module::instance($this->project->cmid);

		$maxfiles = 99;                // TODO: add some setting
		$maxbytes = $COURSE->maxbytes; // TODO: add some setting	
		$this->descriptionoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);
		$this->attachmentoptions = array('subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes);
    	
    	$currentGroup = 0 + groups_get_course_group($COURSE);

    	$mform->addElement('hidden', 'id');
    	$mform->setType('id', PARAM_INT);
    	$mform->addElement('hidden', 'fatherid');
    	$mform->setType('fatherid', PARAM_INT);
    	$mform->addElement('hidden', 'delivid');
    	$mform->setType('delivid', PARAM_INT);
    	$mform->addElement('hidden', 'work');
    	$mform->setType('work', PARAM_TEXT);
    	$mform->setDefault('work', $this->mode);
    	
    	$mform->addElement('text', 'abstract', get_string('delivtitle', 'techproject'), array('size' => "100%"));
    	$mform->setType('abstract', PARAM_CLEANHTML);

        $statusses = techproject_get_options('delivstatus', $this->project->id);
        $deliverystatusses = array();
        foreach($statusses as $aStatus){
            $deliverystatusses[$aStatus->code] = '['. $aStatus->code . '] ' . $aStatus->label;
        }
    	$mform->addElement('select', 'status', get_string('status', 'techproject'), $deliverystatusses);
		$mform->addHelpButton('status', 'deliv_status', 'techproject');

		if ($this->mode == 'update'){

	        $query = "
	           SELECT
	              id,
	              abstract,
	              ordering
	           FROM
	              {techproject_milestone}
	           WHERE
	              projectid = {$this->project->id} AND
	              groupid = {$currentGroup}
	           ORDER BY
	              ordering
	        ";
	        $milestones = $DB->get_records_sql($query);
	        $milestonesoptions = array();
	        foreach($milestones as $aMilestone){
	            $milestonesoptions[$aMilestone->id] = format_string($aMilestone->abstract);
	        }
    		$mform->addElement('select', 'milestoneid', get_string('milestone', 'techproject'), $milestonesoptions);		    	
		}

    	$mform->addElement('editor', 'description_editor', get_string('description', 'techproject'), null, $this->descriptionoptions);		    	
        $mform->setType('decription_editor', PARAM_RAW);

    	$mform->addElement('header', 'headerupload', get_string('delivered', 'techproject'));		    	

		if ($this->mode == 'update'){
	        if (!empty($this->current->url)) {
	            $mform->addElement('static', 'uploaded', get_string('deliverable', 'techproject'), "<a href=\"{$deliverable->url}\" target=\"_blank\">{$deliverable->url}</a>");
	        } else if ($this->current->localfile) {
	        	// TODO : using file API give access to locally stored file
	        } else {
	            $mform->addElement('static', 'uploaded', print_string('notsubmittedyet','techproject'));
	        }
	    }

        $mform->addElement('text', 'url', get_string('url','techproject'));
    	$mform->setType('url', PARAM_URL);
        $mform->addElement('static', 'or', '', get_string('oruploadfile','techproject'));
        $mform->addElement('filemanager', 'localfile_filemanager', get_string('uploadfile', 'techproject'), null, $this->attachmentoptions);

 		$this->add_action_buttons(true);
    }
    
    function set_data($defaults){
		
		$context = context_module::instance($this->project->cmid);

		$draftid_editor = file_get_submitted_draft_itemid('description_editor');
		$currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_techproject', 'description_editor', $defaults->id, array('subdirs' => true), $defaults->description);
		$defaults = file_prepare_standard_editor($defaults, 'description', $this->descriptionoptions, $context, 'mod_techproject', 'deliverabledescription', $defaults->id);
		$defaults = file_prepare_standard_filemanager($defaults, 'localfile', $this->attachmentoptions, $context, 'mod_techproject', 'deliverablelocalfile', $defaults->id);
		$defaults->description = array('text' => $currenttext, 'format' => $defaults->descriptionformat, 'itemid' => $draftid_editor);

    	parent::set_data($defaults);
    }
}
