<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Ajax receptor for updating collapse status.
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
    if (! $cm = get_record('course_modules', "id", $id)) {
        error('Course Module ID was incorrect');
    }
    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }
    if (! $techproject = get_record('techproject', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

    require_login($course->id, false, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        
    if ($state){
        $collapse->userid = $USER->id;
        $collapse->projectid = $techproject->id;
        $collapse->entryid = $entryid;
        $collapse->entity = $entity;
        $collapse->collapsed = 1;
        insert_record('techproject_collapse', $collapse);
    } else {
        delete_records('techproject_collapse', 'userid', $USER->id, 'entryid', $entryid, 'entity', $entity);
		// prepare for showing branch
	    if ($CFG->enableajax && $CFG->enablecourseajax){
	    	$printfuncname = "techproject_print_{$entity}";
	    	$printfuncname($project, $group, $entryid, $cm->id);
	    }
    }
    

?>