<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Ajax receptor for updating collapse status.
    * when Moodle enables ajax, will also, when expanding, return all the underlying div structure
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/07/22
    * @version phase2
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */


    include "../../../config.php";
    require_once $CFG->dirroot."/mod/techproject/locallib.php";

    $id = required_param('id', PARAM_INT);   // module id
    $entity = required_param('entity', PARAM_ALPHA);   // module id
    $entryid = required_param('entryid', PARAM_INT);   // module id
    $state = required_param('state', PARAM_INT);   // module id

    // get some useful stuff...
    if (! $cm = get_coursemodule_from_id('techproject', $id)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
    if (! $project = $DB->get_record('techproject', array('id' => $cm->instance))) {
        print_error('invalidtechprojectid', 'techproject');
    }
    
    $group = 0 + groups_get_course_group($course, true);

    require_login($course->id, false, $cm);
    $context = context_module::instance($cm->id);
    if ($state){
        $collapse->userid = $USER->id;
        $collapse->projectid = $techproject->id;
        $collapse->entryid = $entryid;
        $collapse->entity = $entity;
        $collapse->collapsed = 1;
        $DB->insert_record('techproject_collapse', $collapse);

		// prepare for hidden branch / may not bne usefull
		/*
	    if ($CFG->enableajax && $CFG->enablecourseajax){
	    	$printfuncname = "techproject_print_{$entity}s";
	    	$propagated->collapsed = true;
	    	$printfuncname($project, $group, $entryid, $cm->id, $propagated);
	    }
	    */

    } else {
        $DB->delete_records('techproject_collapse', array('userid' => $USER->id, 'entryid' => $entryid, 'entity' => $entity));

		// prepare for showing branch
	    if ($CFG->enableajax && $CFG->enablecourseajax){
	    	$printfuncname = "techproject_print_{$entity}";
	    	$printfuncname($project, $group, $entryid, $cm->id);
	    }

    }

?>