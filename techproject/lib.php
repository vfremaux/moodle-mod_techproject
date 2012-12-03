<?php  // $Id: lib.php,v 1.2 2012-12-03 18:38:51 vf Exp $

/**
 * Project : Technical Project Manager (IEEE like)
 *
 * Moodle API Library
 *
 * @package mod-techproject
 * @subpackage framework
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 */

/**
* Requires and includes
*/
if (file_exists($CFG->libdir.'/openlib.php')){
    require_once($CFG->libdir.'/openlib.php');//openmod lib by rick chaides
}

/**
* Given an object containing all the necessary data,
* (defined by the form in mod.html) this function
* will create a new instance and return the id number
* of the new instance.
* @param object $project the form object from which create an instance 
* @return the new instance id
*/
function techproject_add_instance($project){

    $project->timemodified = time();

    $project->projectstart = make_timestamp($project->projectstartyear, 
            $project->projectstartmonth, $project->projectstartday, $project->projectstarthour, 
            $project->projectstartminute);
            
    $project->assessmentstart = make_timestamp($project->assessmentstartyear, 
            $project->assessmentstartmonth, $project->assessmentstartday, $project->assessmentstarthour, 
            $project->assessmentstartminute);

    $project->projectend = make_timestamp($project->projectendyear, 
            $project->projectendmonth, $project->projectendday, $project->projectendhour, 
            $project->projectendminute);
            
    if ($returnid = insert_record('techproject', $project)) {

        $event = NULL;
        $event->name        = get_string('projectstartevent','techproject', $project->name);
        $event->description = $project->description;
        $event->courseid    = $project->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'techproject';
        $event->instance    = $returnid;
        $event->eventtype   = 'projectstart';
        $event->timestart   = $project->projectstart;
        $event->timeduration = 0;
        add_event($event);
        
        $event->name        = get_string('projectendevent','techproject', $project->name);
        $event->eventtype   = 'projectend';
        $event->timestart   = $project->projectend;
        add_event($event);         
    }

    return $returnid;
}

/**
* some consistency check over dates
* returns true if the dates are valid, false otherwise
* @param object $project a form object to be checked for dates
* @return true if dates are OK
*/
function techproject_check_dates($project) {
    // but enforce non-empty or non negative projet period.
    return ($project->projectstart < $project->projectend);           
}

/**
* Given an object containing all the necessary data, 
* (defined by the form in mod.html) this function 
* will update an existing instance with new data.
* @uses $CFG
* @param object $project the form object from which update an instance
*/
function techproject_update_instance($project){
    global $CFG;
    
    $project->timemodified = time();

    $project->projectstart = make_timestamp($project->projectstartyear, 
            $project->projectstartmonth, $project->projectstartday, $project->projectstarthour, 
            $project->projectstartminute);
            
    $project->assessmentstart = make_timestamp($project->assessmentstartyear, 
            $project->assessmentstartmonth, $project->assessmentstartday, $project->assessmentstarthour, 
            $project->assessmentstartminute);

    $project->projectend = make_timestamp($project->projectendyear, 
            $project->projectendmonth, $project->projectendday, $project->projectendhour, 
            $project->projectendminute);
            
    if (!techproject_check_dates($project)) {
        return get_string('invalid dates', 'techproject');
    }
    
    if (!isset($project->projectusesrequs)) $project->projectusesrequs = 0;
    if (!isset($project->projectusesspecs)) $project->projectusesspecs = 0;
    if (!isset($project->projectusesdelivs)) $project->projectusesdelivs = 0;
    if (!isset($project->projectusesvalidations)) $project->projectusesvalidations = 0;

    $project->id = $project->instance;

    if ($returnid = update_record('techproject', $project)) {

        $dates = array(
            'projectstart' => $project->projectstart,
            'projectend' => $project->projectend,
            'assessmentstart' => $project->assessmentstart
        );
        $moduleid = get_field('modules', 'id', 'name', 'techproject');
        
        foreach ($dates as $type => $date) {
            if ($event = get_record('event', 'modulename', 'techproject', 'instance', $project->id, 'eventtype', $type)) {
                $event->name        = get_string($type.'event','techproject', $project->name);
                $event->description = $project->description;
                $event->eventtype   = $type;
                $event->timestart   = $date;
                update_event($event);
            } 
            else if ($date) {
                $event = NULL;
                $event->name        = get_string($type.'event','techproject', $project->name);
                $event->description = $project->description;
                $event->courseid    = $project->course;
                $event->groupid     = 0;
                $event->userid      = 0;
                $event->modulename  = 'techproject';
                $event->instance    = $project->instance;
                $event->eventtype   = $type;
                $event->timestart   = $date;
                $event->timeduration = 0;
                $event->visible     = get_field('course_modules', 'visible', 'module', $moduleid, 'instance', $project->id); 
                add_event($event);
            }
        }
    }
    return $returnid;
}

/**
* Given an ID of an instance of this module,
* this function will permanently delete the instance
* and any data that depends on it.
* @param integer $id the instance id to delete
* @return true if successfully deleted
*/
function techproject_delete_instance($id){

    if (! $project = get_record('techproject', 'id', $id)) {
        return false;
    }

    $result = true;

    /* Delete any dependent records here */

    /* Delete subrecords here */
    delete_records('techproject_heading', 'projectid', $project->id);
    delete_records('techproject_task', 'projectid', $project->id);
    delete_records('techproject_specification', 'projectid', $project->id);
    delete_records('techproject_requirement', 'projectid', $project->id);
    delete_records('techproject_milestone', 'projectid', $project->id);
    delete_records('techproject_deliverable', 'projectid', $project->id);

    // echo "delete entities ok!!<br/>";

    delete_records('techproject_task_to_spec', 'projectid', $project->id);
    delete_records('techproject_task_dependency', 'projectid', $project->id);
    delete_records('techproject_task_to_deliv', 'projectid', $project->id);
    delete_records('techproject_spec_to_req', 'projectid', $project->id);

    // delete domain subrecords
    delete_records('techproject_qualifier', 'projectid', $project->id);
    delete_records('techproject_assessment', 'projectid', $project->id);
    delete_records('techproject_criterion', 'projectid', $project->id);

	/* Delete any event associate with the project */
    delete_records('event', 'modulename', 'techproject', 'instance', $project->id);
    
	/* Delete the instance itself */
    if (! delete_records('techproject', 'id', $project->id)) {
        $result = false;
    }

    echo "full delete : $result<br/>";
    // return $result;
    return true;
}

/**
* gives back an object for student detailed reports
* @param object $course the current course
* @param object $user the current user
* @param object $mod the current course module
* @param object $project the current project
*/
function techproject_user_complete($course, $user, $mod, $project){
    return NULL;
}

/**
* gives back an object for student abstract reports
* @uses $CFG
* @param object $course the current course
* @param object $user the current user
* @param object $mod the current course module
* @param object $project the current project
*/
function techproject_user_outline($course, $user, $mod, $project){
    global $CFG;
    
    if ($project = get_record('techproject', 'id', $project->id)){
        
        // counting assigned tasks
        $assignedtasks = count_records('techproject_task', 'projectid' , $project->id, 'assignee', $user->id);
        $select = "projectid = {$project->id} AND assignee = $user->id AND done < 100";
        $uncompletedtasks = count_records_select('techproject_task', $select);
        $ownedtasks = count_records('techproject_task', 'projectid' , $project->id, 'owner', $user->id);
        
        $outline = new object();
        $outline->info = get_string('haveownedtasks', 'techproject', $ownedtasks);
        $outline->info .= '<br/>'.get_string('haveassignedtasks', 'techproject', $assignedtasks);
        $outline->info .= '<br/>'.get_string('haveuncompletedtasks', 'techproject', $uncompletedtasks);

        $sql = "
            SELECT MAX(modified) as modified FROM 
               {$CFG->prefix}techproject_task
            WHERE
                projectid = $project->id AND 
                (owner = $user->id OR
                assignee = $user->id)
        ";
        if ($lastrecord = get_record_sql($sql))
            $outline->time = $lastrecord->modified;
        else
            $outline->time = $project->timemodified;
        
        return $outline;
    }
    
    return NULL;
}

/**
 * Course resetting API
 * Called by course/reset.php
 * OLD OBSOLOETE WAY
 */
function techproject_reset_course_form($course) {
    echo get_string('resetproject', 'techproject'); 
    echo ':<br />';
    print_checkbox('reset_techproject_groups', 1, true, get_string('grouped','techproject'), '', '');  
    echo '<br />';
    print_checkbox('reset_techproject_group0', 1, true, get_string('groupless','techproject'), '', '');  
    echo '<br />';
    print_checkbox('reset_techproject_grades', 1, true, get_string('grades','techproject'), '', '');  
    echo '<br />';
    print_checkbox('reset_techproject_criteria', 1, true, get_string('criteria','techproject'), '', '');  
    echo '<br />';
    print_checkbox('reset_techproject_milestones', 1, true, get_string('milestones','techproject'), '', '');  
    echo '<br />';
    echo '</p>';
}

/**
 * Called by course/reset.php
 * @param $mform form passed by reference
 */
function techproject_reset_course_form_definition(&$mform) {
    global $COURSE;

    $mform->addElement('header', 'teachprojectheader', get_string('modulenameplural', 'techproject'));
    
    if(!$techprojects = get_records('techproject', 'course', $COURSE->id)){
        return;
    }

    $mform->addElement('static', 'hint', get_string('resetproject','techproject'));
    $mform->addElement('checkbox', 'reset_techproject_grades', get_string('resetting_grades', 'techproject'));
    $mform->addElement('checkbox', 'reset_techproject_criteria', get_string('resetting_criteria', 'techproject'));
    $mform->addElement('checkbox', 'reset_techproject_groups', get_string('resetting_groupprojects', 'techproject'));
    $mform->addElement('checkbox', 'reset_techproject_group0', get_string('resetting_courseproject', 'techproject'));
}

/**
* This function is used by the remove_course_userdata function in moodlelib.
* If this function exists, remove_course_userdata will execute it.
* This function will remove all posts from the specified forum.
* @uses $CFG
* @param object $data the reset options
* @param boolean $showfeedback if true, ask the function to be verbose
*/
function techproject_reset_userdata($data) {
    global $CFG;

    $status = array();
    $componentstr = get_string('modulenameplural', 'magtest');
    $strreset = get_string('reset');
    
    if ($data->reset_techproject_grades or $data->reset_techproject_criteria or $data->reset_techproject_groups){
        $sql = "
            DELETE FROM
                {$CFG->prefix}techproject_assessment
                WHERE
                    projectid IN ( SELECT 
                c.id 
             FROM 
                {$CFG->prefix}techproject AS c
             WHERE 
                c.course={$data->courseid} )
         ";
        if (execute_sql($sql, false)){
            $status[] = array('component' => $componentstr, 'item' => get_string('resetting_grades','techproject'), 'error' => false);
        }
    }

    if ($data->reset_techproject_criteria){
        $sql = "
            DELETE FROM
                {$CFG->prefix}techproject_criterion
                WHERE
                    projectid IN ( SELECT 
                c.id 
             FROM 
                {$CFG->prefix}techproject AS c
             WHERE 
                c.course={$data->courseid} )
         ";
        if(execute_sql($sql, false)){
            $status[] = array('component' => $componentstr, 'item' => get_string('resetting_criteria','techproject'), 'error' => false);
        }
    }

    if ($data->reset_techproject_groups){
        $subsql = "
                WHERE
                    projectid IN ( SELECT 
                c.id 
             FROM 
                {$CFG->prefix}techproject AS c
             WHERE 
                c.course={$data->courseid} ) AND
                groupid != 0
         ";

        $deletetables = array('spec_to_req', 
                              'task_to_spec', 
                              'task_to_deliv', 
                              'task_dependency', 
                              'requirement', 
                              'specification', 
                              'task', 
                              'deliverable',
                              'heading');

        if ($data->reset_techproject_milestones){
            $deletetables[] = 'milestone';
        }
                              
        foreach($deletetables as $atable){
            $sql = "
                DELETE FROM
                    {$CFG->prefix}techproject_{$atable}
                    {$subsql}
            ";
            execute_sql($sql, false);
        }        

        $status[] = array('component' => $componentstr, 'item' => get_string('resetting_groupprojects','techproject'), 'error' => false);
    }

    if ($data->reset_techproject_group0){
        $subsql = "
                WHERE
                    projectid IN ( SELECT 
                c.id 
             FROM 
                {$CFG->prefix}techproject AS c
             WHERE 
                c.course={$data->courseid} ) AND
                groupid = 0
         ";

        $deletetables = array('spec_to_req', 
                              'task_to_spec', 
                              'task_to_deliv', 
                              'task_dependency', 
                              'requirement', 
                              'specification', 
                              'task', 
                              'deliverable',
                              'heading');

        if ($data->reset_techproject_milestones){
            $deletetables[] = 'milestone';
        }
                              
        foreach($deletetables as $atable){
            $sql = "
                DELETE FROM
                    {$CFG->prefix}techproject_{$atable}
                    {$subsql}
            ";
            execute_sql($sql, false);
        }
        $status[] = array('component' => $componentstr, 'item' => get_string('resetting_courseproject','techproject'), 'error' => false);
    }
    
    return $status;
}


/**
* performs what needs to be done in asynchronous mode
*/
function techproject_cron(){
    // TODO : may cleanup some old group rubish ??

}

/**
*
*/


/**
* get the "grade" entries for this user and add the first and last names (of project owner, 
* better to get name of teacher...
* ...but not available in assessment record...)
* @param object $course the current course
* @param int $timestart the time from which to log
*/
function techproject_get_grade_logs($course, $timestart) {
    global $CFG, $USER;
    if (empty($USER->id)) {
        return false;
    }
    
    // TODO evaluate grading and assessment strategies
    return;
    
    $timethen = time() - $CFG->maxeditingtime;
    $query = "
        SELECT 
            l.time, 
            l.url, 
            u.firstname, 
            u.lastname, 
            a.projectid, 
            e.name
        FROM 
            {$CFG->prefix}log l,
            {$CFG->prefix}techproject e, 
            {$CFG->prefix}techproject_assessments a, 
            {$CFG->prefix}user u
        WHERE
            l.time > $timestart AND 
            l.time < $timethen AND 
            l.course = $course->id AND 
            l.module = 'techproject' AND 
            l.action = 'grade' AND 
            a.id = l.info AND 
            e.id = a.projectid AND 
            a.userid = $USER->id AND 
            u.id = e.userid AND 
            e.id = a.projectid
    ";
    return get_records_sql($query);
}

/*
* get the log entries by a particular change in entities, 
* @uses $CFG
* @param object $course the current course
* @param int $timestart the time from which to log
* @param string $changekey the key of the event type to be considered
*/
function techproject_get_entitychange_logs($course, $timestart, $changekey) {
    global $CFG;
    
    $timethen = time() - $CFG->maxeditingtime;
    $query = "
        SELECT 
            l.time, 
            l.url, 
            u.firstname, 
            u.lastname, 
            l.info as projectid, 
            p.name
        FROM 
            {$CFG->prefix}log l,
            {$CFG->prefix}techproject p, 
            {$CFG->prefix}user u
        WHERE 
            l.time > $timestart AND 
            l.time < $timethen AND 
            l.course = $course->id AND 
            l.module = 'techproject' AND 
            l.action = '$changekey' AND 
            p.id = l.info AND 
            u.id = l.userid
    ";
    return get_records_sql($query);
}

/**
* get the "submit" entries and add the first and last names...
* @uses $CFG
* @param object $course
* @param int $timestart
*/
function techproject_get_submit_logs($course, $timestart) {
    global $CFG;
    
    $timethen = time() - $CFG->maxeditingtime;
    $query = "
        SELECT 
            l.time, 
            l.url, 
            u.firstname, 
            u.lastname, 
            l.info as projectid, 
            e.name
        FROM 
            {$CFG->prefix}log l,
            {$CFG->prefix}techproject e, 
            {$CFG->prefix}user u
        WHERE 
            l.time > $timestart AND 
            l.time < $timethen AND 
            l.course = $course->id AND 
            l.module = 'techproject' AND 
            l.action = 'submit' AND 
            e.id = l.info AND 
            u.id = l.userid
    ";
    return get_records_sql($query);
}

/**
* Given a list of logs, assumed to be those since the last login
* this function prints a short list of changes related to this module
* If isteacher is true then perhaps additional information is printed.
* This function is called from course/lib.php: print_recent_activity()
* @uses $CFG
* @param object $course
* @param boolean $isteacher
* @param int $timestart
*/
function techproject_print_recent_activity($course, $isteacher, $timestart){
    global $CFG;

    // have a look for what has changed in requ
    $changerequcontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changerequ')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('techproject',$tempmod)) {
                    $changerequcontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changerequcontent) {
                print_headline(get_string('projectchangedrequ', 'techproject').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('techproject',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/techproject/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for what has changed in specs
    $changespeccontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changespec')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('techproject',$tempmod)) {
                    $changespeccontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changespeccontent) {
                print_headline(get_string('projectchangedspec', 'techproject').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('techproject',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/techproject/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for what has changed in tasks
    $changetaskcontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changetask')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('techproject',$tempmod)) {
                    $changetaskcontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changetaskcontent) {
                print_headline(get_string('projectchangedtask', 'techproject').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('techproject',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/techproject/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for what has changed in milestones
    $changemilescontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changemilestone')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('techproject',$tempmod)) {
                    $changemilescontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changemilescontent) {
                print_headline(get_string('projectchangedmilestone', 'techproject').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('techproject',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/techproject/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for what has changed in milestones
    $changedelivcontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changedeliverable')) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('techproject',$tempmod)) {
                    $changedelivcontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($changedelivcontent) {
                print_headline(get_string('projectchangeddeliverable', 'techproject').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('techproject',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                            $log->firstname = $course->student;
                            $log->lastname = '';
                        }
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/techproject/'.$log->url);
                    }
                }
            }
        }
    }

    // have a look for new gradings for this user (grade)
    $gradecontent = false;
    if ($logs = techproject_get_grade_logs($course, $timestart)) {
        // got some, see if any belong to a visible module
        foreach ($logs as $log) {
            // Create a temp valid module structure (only need courseid, moduleid)
            $tempmod->course = $course->id;
            $tempmod->id = $log->projectid;
            //Obtain the visible property from the instance
            if (instance_is_visible('techproject',$tempmod)) {
                $gradecontent = true;
                break;
                }
            }
        // if we got some "live" ones then output them
        if ($gradecontent) {
            print_headline(get_string('projectfeedback', 'techproject').":");
            foreach ($logs as $log) {
                //Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('techproject',$tempmod)) {
                    $log->firstname = $course->teacher;    // Keep anonymous
                    $log->lastname = '';
                    print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                               $CFG->wwwroot.'/mod/techproject/'.$log->url);
                }
            }
        }
    }

    // have a look for new project (only show to teachers) (submit)
    $submitcontent = false;
    if ($isteacher) {
        if ($logs = techproject_get_submit_logs($course, $timestart)) {
            // got some, see if any belong to a visible module
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
                if (instance_is_visible('techproject',$tempmod)) {
                    $submitcontent = true;
                    break;
                    }
                }
            // if we got some "live" ones then output them
            if ($submitcontent) {
                print_headline(get_string('projectproject', 'techproject').":");
                foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
                    if (instance_is_visible('techproject',$tempmod)) {
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/techproject/'.$log->url);
                    }
                }
            }
        }
    }
    return $changerequcontent or $changespeccontent or $changetaskcontent or $changemilescontent or $changedelivcontent or $gradecontent or $submitcontent;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user. It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function techproject_grades($cmid) {
    global $CFG;

    if (!$module = get_record('course_modules', 'id', $cmid)){
        return NULL;
    }    

    if (!$project = get_record('techproject', 'id', $module->instance)){
        return NULL;
    }

    if ($project->grade == 0) { // No grading
        return NULL;
    }

    $query = "
       SELECT
          a.*,
          c.weight
       FROM
          {$CFG->prefix}techproject_assessment as a
       LEFT JOIN
          {$CFG->prefix}techproject_criterion as c
       ON
          a.criterion = c.id
       WHERE
          a.projectid = {$project->id}
    ";
    // echo $query ;
    $grades = get_records_sql($query);
    if ($grades){
        if ($project->grade > 0 ){ // Grading numerically
            $finalgrades = array();
            foreach($grades as $aGrade){
                $finalgrades[$aGrade->userid] = @$finalgrades[$aGrade->userid] + $aGrade->grade * $aGrade->weight;
                $totalweights[$aGrade->userid] = @$totalweights[$aGrade->userid] + $aGrade->weight;
            }
                
            foreach(array_keys($finalgrades) as $aUserId){
                if($totalweights[$aGrade->userid] != 0){
                    $final[$aUserId] = round($finalgrades[$aUserId] / $totalweights[$aGrade->userid]);
                }
                else{
                    $final[$aUserId] = 0;
                }
            }
            $return->grades = @$final;
            $return->maxgrade = $project->grade;
        }
        else { // Scales
            $finalgrades = array();
            $scaleid = - ($project->grade);
            $maxgrade = '';
            if ($scale = get_record('scale', 'id', $scaleid)) {
                $scalegrades = make_menu_from_list($scale->scale);
                foreach ($grades as $aGrade) {
                    $finalgrades[$userid] = @$finalgrades[$userid] + $scalegrades[$aGgrade->grade] * $aGrade->weight;
                    $totalweights[$aGrade->userid] = @$totalweights[$aGrade->userid] + $aGrade->weight;
                }
                $maxgrade = $scale->name;

                foreach(array_keys($finalgrades) as $aUserId){
                    if($totalweights[$aGrade->userid] != 0){
                        $final[$userId] = round($finalgrades[$aUserId] / $totalweights[$aGrade->userid]);
                    }
                    else{
                        $final[$userId] = 0;
                    }
                }
            }
            $return->grades = @$final;
            $return->maxgrade = $maxgrade;
        }
        return $return;
    }
    return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of newmodule. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $moduleid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function techproject_get_participants($moduleid) {
    $usersreqs = get_records('techproject_requirement', 'projectid', $moduleid, '', 'userid,userid');
    $usersspecs = get_records('techproject_specification', 'projectid', $moduleid, '', 'userid,userid');
    $userstasks = get_records('techproject_task', 'projectid', $moduleid, '', 'userid,userid');
    $userstasksassigned = get_records('techproject_task', 'projectid', $moduleid, '', 'assignee,assignee');
    $userstasksowners = get_records('techproject_task', 'projectid', $moduleid, '', 'owner,owner');
    $usersdelivs = get_records('techproject_deliverable', 'projectid', $moduleid, '', 'userid,userid');
    $usersmiles = get_records('techproject_milestone', 'projectid', $moduleid, '', 'userid,userid');

    $allusers = array();    
    if(!empty($usersreqs)){
        $allusers = array_keys($usersreqs);
    }
    if(!empty($usersspecs)){
        $allusers = array_merge($allusers, array_keys($usersspecs));
    }
    if(!empty($userstasks)){
        $allusers = array_merge($allusers, array_keys($userstasks));
    }
    if(!empty($userstasksassigned)){
        $allusers = array_merge($allusers, array_keys($userstasksassigned));
    }
    if(!empty($userstasksowned)){
        $allusers = array_merge($allusers, array_keys($userstasksowned));
    }
    if(!empty($userstasksdelivs)){
        $allusers = array_merge($allusers, array_keys($userstasksdelivs));
    }
    if(!empty($userstasksmiles)){
        $allusers = array_merge($allusers, array_keys($userstasksmiles));
    }
    
    $userlist = implode("','", $allusers);
    
    $participants = get_records_list('user', 'id', "'$userlist'");
    return $participants;
}

/**
 * This function returns if a scale is being used by one newmodule
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed
 **/
function techproject_scale_used($cmid, $scaleid) {
    $return = false;

    // note : scales are assigned using negative index in the grade field of project (see mod/assignement/lib.php) 
    $rec = get_record('techproject','id',$cmid,'grade',-$scaleid);

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }
   
    return $return;
}

?>