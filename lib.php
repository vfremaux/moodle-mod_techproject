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
 * @package mod_techproject
 * @subpackage framework
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 */
defined('MOODLE_INTERNAL') || die();

if (file_exists($CFG->libdir.'/openlib.php')) {
    require_once($CFG->libdir.'/openlib.php'); // Openmod lib by rick chaides.
}

require_once($CFG->dirroot.'/calendar/lib.php');

/**
 * List of features supported in Techproject module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function techproject_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE: {
            return MOD_ARCHETYPE_OTHER;
        }

        case FEATURE_GROUPS: {
            return true;
        }

        case FEATURE_GROUPINGS: {
            return true;
        }

        case FEATURE_GROUPMEMBERSONLY: {
            return true;
        }

        case FEATURE_MOD_INTRO: {
            return true;
        }

        case FEATURE_COMPLETION_TRACKS_VIEWS: {
            return true;
        }

        case FEATURE_GRADE_HAS_GRADE: {
            return true;
        }

        case FEATURE_GRADE_OUTCOMES: {
            return false;
        }

        case FEATURE_BACKUP_MOODLE2: {
            return true;
        }

        case FEATURE_SHOW_DESCRIPTION: {
            return true;
        }

        default:
            return null;
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 * @param object $project the form object from which create an instance
 * @return the new instance id
 */
function techproject_add_instance($project) {
    global $DB;

    $project->timecreated = time();
    $project->timemodified = time();

    if ($returnid = $DB->insert_record('techproject', $project)) {

        $event = new StdClass;
        $event->name        = get_string('projectstartevent', 'techproject', $project->name);
        $event->description = $project->intro;
        $event->courseid    = $project->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'techproject';
        $event->instance    = $returnid;
        $event->eventtype   = 'projectstart';
        $event->timestart   = $project->projectstart;
        $event->timeduration = 0;
        calendar_event::create($event);
        $event->name        = get_string('projectendevent', 'techproject', $project->name);
        $event->eventtype   = 'projectend';
        $event->timestart   = $project->projectend;
        calendar_event::create($event);
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
    // But enforce non-empty or non negative projet period.
    return ($project->projectstart < $project->projectend);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 * @param object $project the form object from which update an instance
 */
function techproject_update_instance($project) {
    global $CFG, $DB;

    $project->timemodified = time();

    if (!techproject_check_dates($project)) {
        return get_string('invaliddates', 'techproject');
    }

    $project->id = $project->instance;

    if (!isset($project->projectusesrequs)) {
        $project->projectusesrequs = 0;
    }
    if (!isset($project->projectusesspecs)) {
        $project->projectusesspecs = 0;
    }
    if (!isset($project->projectusesdelivs)) {
        $project->projectusesdelivs = 0;
    }
    if (!isset($project->projectusesvalidations)) {
        $project->projectusesvalidations = 0;
    }

    $DB->update_record('techproject', $project);

    $dates = array(
        'projectstart' => $project->projectstart,
        'projectend' => $project->projectend,
        'assessmentstart' => $project->assessmentstart);
    $moduleid = $DB->get_field('modules', 'id', array('name' => 'techproject'));
    foreach ($dates as $type => $date) {
        $params = array('modulename' => 'techproject', 'instance' => $project->id, 'eventtype' => $type);
        if ($event = $DB->get_record('event', $params)) {
            $event->name = get_string($type.'event', 'techproject', $project->name);
            $event->description = $project->intro;
            $event->eventtype   = $type;
            $event->timestart = $date;
            $eventobj = calendar_event::load($event->id);
            $eventobj->update($event);
        } else if ($date) {
            $event = new StdClass;
            $event->name = get_string($type.'event', 'techproject', $project->name);
            $event->description = $project->intro;
            $event->courseid = $project->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'techproject';
            $event->instance = $project->instance;
            $event->eventtype = $type;
            $event->timestart = $date;
            $event->timeduration = 0;
            $params = array('module' => $moduleid, 'instance' => $project->id);
            $event->visible = $DB->get_field('course_modules', 'visible', $params);
            calendar_event::create($event);
        }
    }
    return $project->id;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 * @param integer $id the instance id to delete
 * @return true if successfully deleted
 */
function techproject_delete_instance($id) {
    global $DB;

    if (! $project = $DB->get_record('techproject', array('id' => $id))) {
        return false;
    }

    $result = true;

    // Delete any dependent records here.

    // Delete subrecords here.
    $DB->delete_records('techproject_heading', array('projectid' => $project->id));
    $DB->delete_records('techproject_task', array('projectid' => $project->id));
    $DB->delete_records('techproject_specification', array('projectid' => $project->id));
    $DB->delete_records('techproject_requirement', array('projectid' => $project->id));
    $DB->delete_records('techproject_milestone', array('projectid' => $project->id));
    $DB->delete_records('techproject_deliverable', array('projectid' => $project->id));

    $DB->delete_records('techproject_task_to_spec', array('projectid' => $project->id));
    $DB->delete_records('techproject_task_dependency', array('projectid' => $project->id));
    $DB->delete_records('techproject_task_to_deliv', array('projectid' => $project->id));
    $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id));

    // Delete domain subrecords.
    $DB->delete_records('techproject_qualifier', array('projectid' => $project->id));
    $DB->delete_records('techproject_assessment', array('projectid' => $project->id));
    $DB->delete_records('techproject_criterion', array('projectid' => $project->id));

    // Delete any event associate with the project.
    $DB->delete_records('event', array('modulename' => 'techproject', 'instance' => $project->id));

    // Delete the instance itself.
    if (! $DB->delete_records('techproject', array('id' => $project->id))) {
        $result = false;
    }

    return true;
}

/**
 * gives back an object for student detailed reports
 * @param object $course the current course
 * @param object $user the current user
 * @param object $mod the current course module
 * @param object $project the current project
 */
function techproject_user_complete($course, $user, $mod, $project) {
    return null;
}

/**
 * gives back an object for student abstract reports
 * @param object $course the current course
 * @param object $user the current user
 * @param object $mod the current course module
 * @param object $project the current project
 */
function techproject_user_outline($course, $user, $mod, $project) {
    global $CFG, $DB;

    if ($project = $DB->get_record('techproject', array('id' => $project->id))) {
        // Counting assigned tasks.
        $params = array('projectid' => $project->id, 'assignee' => $user->id);
        $assignedtasks = $DB->count_records('techproject_task', $params);
        $select = "
            projectid = ? AND
            assignee = ? AND
            done < 100 ";
        $uncompletedtasks = $DB->count_records_select('techproject_task', $select, array($project->id, $user->id));
        $ownedtasks = $DB->count_records('techproject_task', array('projectid' => $project->id, 'owner' => $user->id));
        $outline = new stdClass();
        $outline->info = get_string('haveownedtasks', 'techproject', $ownedtasks);
        $outline->info .= '<br/>'.get_string('haveassignedtasks', 'techproject', $assignedtasks);
        $outline->info .= '<br/>'.get_string('haveuncompletedtasks', 'techproject', $uncompletedtasks);

        $sql = "
            SELECT
                MAX(modified) as modified
            FROM
               {techproject_task}
            WHERE
                projectid = ? AND
                (owner = ? OR
                assignee = ?)
        ";
        if ($lastrecord = $DB->get_record_sql($sql, array($project->id, $user->id, $user->id))) {
            $outline->time = $lastrecord->modified;
        } else {
            $outline->time = $project->timemodified;
        }
        return $outline;
    }
    return null;
}

/**
 * Course resetting API
 * Called by course/reset.php
 * OLD OBSOLOETE WAY
 */
function techproject_reset_course_form($course) {
    echo get_string('resetproject', 'techproject');
    echo ':<br />';
    print_checkbox('reset_techproject_groups', 1, true, get_string('grouped', 'techproject'), '', '');
    echo '<br />';
    print_checkbox('reset_techproject_group0', 1, true, get_string('groupless', 'techproject'), '', '');
    echo '<br />';
    print_checkbox('reset_techproject_grades', 1, true, get_string('grades', 'techproject'), '', '');
    echo '<br />';
    print_checkbox('reset_techproject_criteria', 1, true, get_string('criteria', 'techproject'), '', '');
    echo '<br />';
    print_checkbox('reset_techproject_milestones', 1, true, get_string('milestones', 'techproject'), '', '');
    echo '<br />';
    echo '</p>';
}

/**
 * Called by course/reset.php
 * @param $mform form passed by reference
 */
function techproject_reset_course_form_definition(&$mform) {
    global $COURSE, $DB;

    $mform->addElement('header', 'teachprojectheader', get_string('modulenameplural', 'techproject'));
    if (!$techprojects = $DB->get_records('techproject', array('course' => $COURSE->id))) {
        return;
    }

    $mform->addElement('static', 'hint', get_string('resetproject', 'techproject'));
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
    global $CFG, $DB;

    $status = array();
    $componentstr = get_string('modulenameplural', 'techproject');
    $strreset = get_string('reset');
    if ($data->reset_techproject_grades || $data->reset_techproject_criteria || $data->reset_techproject_groups) {
        $sql = "
            DELETE FROM
                {techproject_assessment}
                WHERE
                    projectid IN ( SELECT
                c.id
             FROM
                {techproject} AS c
             WHERE
                c.course={$data->courseid} )
         ";
        if ($DB->execute($sql)) {
            $status[] = array('component' => $componentstr,
                              'item' => get_string('resetting_grades', 'techproject'),
                              'error' => false);
        }
    }

    if ($data->reset_techproject_criteria) {
        $sql = "
            DELETE FROM
                {techproject_criterion}
                WHERE
                    projectid IN ( SELECT
                c.id
             FROM
                {techproject} AS c
             WHERE
                c.course={$data->courseid} )
         ";
        if ($DB->execute($sql)) {
            $status[] = array('component' => $componentstr,
                              'item' => get_string('resetting_criteria', 'techproject'),
                              'error' => false);
        }
    }

    if ($data->reset_techproject_groups) {
        $subsql = "
                WHERE
                    projectid IN ( SELECT
                c.id
             FROM
                {techproject} AS c
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

        if ($data->reset_techproject_milestones) {
            $deletetables[] = 'milestone';
        }
        foreach ($deletetables as $atable) {
            $sql = "
                DELETE FROM
                    {techproject_{$atable}}
                    {$subsql}
            ";
            $DB->execute($sql);
        }

        $status[] = array('component' => $componentstr,
                          'item' => get_string('resetting_groupprojects', 'techproject'),
                          'error' => false);
    }

    if ($data->reset_techproject_group0) {
        $subsql = "
                WHERE
                    projectid IN ( SELECT
                c.id
             FROM
                {techproject} AS c
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

        if ($data->reset_techproject_milestones) {
            $deletetables[] = 'milestone';
        }
        foreach ($deletetables as $atable) {
            $sql = "
                DELETE FROM
                    {techproject_{$atable}}
                    {$subsql}
            ";
            $DB->execute($sql);
        }
        $status[] = array('component' => $componentstr,
                          'item' => get_string('resetting_courseproject', 'techproject'),
                          'error' => false);
    }
    return $status;
}

/**
 * get the "grade" entries for this user and add the first and last names (of project owner,
 * better to get name of teacher...
 * ...but not available in assessment record...)
 * @param object $course the current course
 * @param int $timestart the time from which to log
 */
function techproject_get_grade_logs($course, $timestart) {
    global $CFG, $USER, $DB;

    if (empty($USER->id)) {
        return false;
    }

    // TODO evaluate grading and assessment strategies.
    return;

    $timeend = time() - $CFG->maxeditingtime;
    $query = "
        SELECT
            l.time,
            l.url,
            u.firstname,
            u.lastname,
            a.projectid,
            e.name
        FROM
            {log} l,
            {techproject} e,
            {techproject_assessments} a,
            {user} u
        WHERE
            l.time > ? AND
            l.time < ? AND
            l.course = ? AND
            l.module = 'techproject' AND
            l.action = 'grade' AND
            a.id = l.info AND
            e.id = a.projectid AND
            a.userid = ? AND
            u.id = e.userid AND
            e.id = a.projectid
    ";
    $params = array($timestart,
                    $timeend,
                    $course->id,
                    $USER->id);
    return $DB->get_records_sql($query, $params);
}

/**
 * get the log entries by a particular change in entities,
 * @param object $course the current course
 * @param int $timestart the time from which to log
 * @param string $changekey the key of the event type to be considered
 */
function techproject_get_entitychange_logs($course, $timestart, $changekey) {
    global $CFG, $DB;

    $timeend = time() - $CFG->maxeditingtime;
    $query = "
        SELECT
            l.time,
            l.url,
            u.firstname,
            u.lastname,
            l.info as projectid,
            p.name
        FROM
            {log} l,
            {techproject} p,
            {user} u
        WHERE
            l.time > ? AND
            l.time < ? AND
            l.course = ? AND
            l.module = 'techproject' AND
            l.action = ? AND
            p.id = l.info AND
            u.id = l.userid
    ";
    $params = array($timestart,
                    $timeend,
                    $course->id,
                    $changekey);
    return $DB->get_records_sql($query, $params);
}

/**
 * get the "submit" entries and add the first and last names...
 * @uses $CFG
 * @param object $course
 * @param int $timestart
 */
function techproject_get_submit_logs($course, $timestart) {
    global $CFG, $DB;

    $timeend = time() - $CFG->maxeditingtime;
    $query = "
        SELECT
            l.time,
            l.url,
            u.firstname,
            u.lastname,
            l.info as projectid,
            e.name
        FROM
            {log} l,
            {techproject} e,
            {user} u
        WHERE
            l.time > ? AND
            l.time < ? AND
            l.course = ? AND
            l.module = 'techproject' AND
            l.action = 'submit' AND
            e.id = l.info AND
            u.id = l.userid
    ";
    $params = array($timestart,
                    $timeend,
                    $course->id);
    return $DB->get_records_sql($query, $params);
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
function techproject_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    // Have a look for what has changed in requ.
    $changerequcontent = false;
    if (!$isteacher) {
        // Teachers only need to see project.
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changerequ')) {
            // Got some, see if any belong to a visible module.
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid).
                $tempmod = new StdClass;
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                // Obtain the visible property from the instance.
                if (instance_is_visible('techproject', $tempmod)) {
                    $changerequcontent = true;
                    break;
                }
            }
            // If we got some "live" ones then output them.
            if ($changerequcontent) {
                print_headline(get_string('projectchangedrequ', 'techproject').':');
                foreach ($logs as $log) {
                    // Create a temp valid module structure (only need courseid, moduleid).
                    $tempmod = new StdClass;
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    // Obtain the visible property from the instance.
                    if (instance_is_visible('techproject', $tempmod)) {
                        if (!has_capability('mod/techproject:gradeproject', $context, $log->userid)) {
                            // Don't break anonymous rule.
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

    // Have a look for what has changed in specs.
    $changespeccontent = false;
    if (!$isteacher) {
        // Teachers only need to see project.
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changespec')) {
            // Got some, see if any belong to a visible module.
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid).
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                // Obtain the visible property from the instance.
                if (instance_is_visible('techproject', $tempmod)) {
                    $changespeccontent = true;
                    break;
                }
            }
            // If we got some "live" ones then output them.
            if ($changespeccontent) {
                print_headline(get_string('projectchangedspec', 'techproject').":");

                foreach ($logs as $log) {
                    // Create a temp valid module structure (only need courseid, moduleid).
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;

                    // Obtain the visible property from the instance.
                    if (instance_is_visible('techproject', $tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {
                            // Don't break anonymous rule.
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

    // Have a look for what has changed in tasks.
    $changetaskcontent = false;
    if (!$isteacher) {
        // Teachers only need to see project.
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changetask')) {
            // Got some, see if any belong to a visible module.
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid).
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                // Obtain the visible property from the instance.
                if (instance_is_visible('techproject', $tempmod)) {
                    $changetaskcontent = true;
                    break;
                }
            }
            // If we got some "live" ones then output them.
            if ($changetaskcontent) {
                print_headline(get_string('projectchangedtask', 'techproject').":");
                foreach ($logs as $log) {
                    // Create a temp valid module structure (only need courseid, moduleid).
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    // Obtain the visible property from the instance.
                    if (instance_is_visible('techproject', $tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {
                            // Don't break anonymous rule.
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

    // Have a look for what has changed in milestones.
    $changemilescontent = false;
    if (!$isteacher) { // teachers only need to see project
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changemilestone')) {
            // Got some, see if any belong to a visible module.
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid).
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                // Obtain the visible property from the instance.
                if (instance_is_visible('techproject', $tempmod)) {
                    $changemilescontent = true;
                    break;
                }
            }
            // If we got some "live" ones then output them.
            if ($changemilescontent) {
                print_headline(get_string('projectchangedmilestone', 'techproject').":");
                foreach ($logs as $log) {
                    // Create a temp valid module structure (only need courseid, moduleid).
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    // Obtain the visible property from the instance.
                    if (instance_is_visible('techproject', $tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {
                            // Don't break anonymous rule.
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

    // Have a look for what has changed in milestones.
    $changedelivcontent = false;
    if (!$isteacher) {
        // Teachers only need to see project.
        if ($logs = techproject_get_entitychange_logs($course, $timestart, 'changedeliverable')) {
            // Got some, see if any belong to a visible module.
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid).
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                // Obtain the visible property from the instance.
                if (instance_is_visible('techproject', $tempmod)) {
                    $changedelivcontent = true;
                    break;
                }
            }
            // If we got some "live" ones then output them.
            if ($changedelivcontent) {
                print_headline(get_string('projectchangeddeliverable', 'techproject').":");
                foreach ($logs as $log) {
                    // Create a temp valid module structure (only need courseid, moduleid).
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    // Obtain the visible property from the instance.
                    if (instance_is_visible('techproject', $tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {
                            // Don't break anonymous rule.
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

    // Have a look for new gradings for this user (grade).
    $gradecontent = false;
    if ($logs = techproject_get_grade_logs($course, $timestart)) {
        // Got some, see if any belong to a visible module.
        foreach ($logs as $log) {
            // Create a temp valid module structure (only need courseid, moduleid).
            $tempmod->course = $course->id;
            $tempmod->id = $log->projectid;
            // Obtain the visible property from the instance.
            if (instance_is_visible('techproject', $tempmod)) {
                $gradecontent = true;
                break;
            }
        }
        // If we got some "live" ones then output them.
        if ($gradecontent) {
            print_headline(get_string('projectfeedback', 'techproject').":");
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid).
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                // Obtain the visible property from the instance.
                if (instance_is_visible('techproject', $tempmod)) {
                    $log->firstname = $course->teacher;    // Keep anonymous.
                    $log->lastname = '';
                    print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                               $CFG->wwwroot.'/mod/techproject/'.$log->url);
                }
            }
        }
    }

    // Have a look for new project (only show to teachers) (submit).
    $submitcontent = false;
    if ($isteacher) {
        if ($logs = techproject_get_submit_logs($course, $timestart)) {
            // Got some, see if any belong to a visible module.
            foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid).
                $tempmod->course = $course->id;
                $tempmod->id = $log->projectid;
                // Obtain the visible property from the instance.
                if (instance_is_visible('techproject', $tempmod)) {
                    $submitcontent = true;
                    break;
                }
            }
            // If we got some "live" ones then output them.
            if ($submitcontent) {
                print_headline(get_string('projectproject', 'techproject').":");
                foreach ($logs as $log) {
                    // Create a temp valid module structure (only need courseid, moduleid).
                    $tempmod->course = $course->id;
                    $tempmod->id = $log->projectid;
                    // Obtain the visible property from the instance.
                    if (instance_is_visible('techproject', $tempmod)) {
                        print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                                                   $CFG->wwwroot.'/mod/techproject/'.$log->url);
                    }
                }
            }
        }
    }
    return $changerequcontent || $changespeccontent || $changetaskcontent ||
            $changemilescontent || $changedelivcontent || $gradecontent || $submitcontent;
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
 */
function techproject_grades($cmid) {
    global $CFG, $DB;

    if (!$module = $DB->get_record('course_modules', array('id' => $cmid))) {
        return null;
    }

    if (!$project = $DB->get_record('techproject', array('id' => $module->instance))) {
        return null;
    }

    if ($project->grade == 0) {
        // No grading.
        return null;
    }

    $query = "
       SELECT
          a.*,
          c.weight
       FROM
          {techproject_assessment} as a
       LEFT JOIN
          {techproject_criterion} as c
       ON
          a.criterion = c.id
       WHERE
          a.projectid = {$project->id}
    ";

    $grades = $DB->get_records_sql($query);
    if ($grades) {
        if ($project->grade > 0 ) {
            // Grading numerically.
            $finalgrades = array();
            foreach ($grades as $agrade) {
                $finalgrades[$agrade->userid] = @$finalgrades[$agrade->userid] + $agrade->grade * $agrade->weight;
                $totalweights[$agrade->userid] = @$totalweights[$agrade->userid] + $agrade->weight;
            }
            foreach (array_keys($finalgrades) as $uid) {
                if ($totalweights[$agrade->userid] != 0) {
                    $final[$uid] = round($finalgrades[$uid] / $totalweights[$agrade->userid]);
                } else {
                    $final[$uid] = 0;
                }
            }
            $return->grades = @$final;
            $return->maxgrade = $project->grade;
        } else {
            // Scales.
            $finalgrades = array();
            $scaleid = - ($project->grade);
            $maxgrade = '';
            if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                $scalegrades = make_menu_from_list($scale->scale);
                foreach ($grades as $agrade) {
                    $finalgrades[$agrade->userid] = @$finalgrades[$agrade->userid] + $scalegrades[$agrade->grade] * $agrade->weight;
                    $totalweights[$agrade->userid] = @$totalweights[$agrade->userid] + $agrade->weight;
                }
                $maxgrade = $scale->name;

                foreach (array_keys($finalgrades) as $uid) {
                    if ($totalweights[$uid] != 0) {
                        $final[$uid] = round($finalgrades[$uid] / $totalweights[$uid]);
                    } else {
                        $final[$uid] = 0;
                    }
                }
            }
            $return->grades = @$final;
            $return->maxgrade = $maxgrade;
        }
        return $return;
    }
    return null;
}

/**
 *
 */
function techproject_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('techproject', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of newmodule. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $moduleid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function techproject_get_participants($moduleid) {
    global $DB;

    $usersreqs = $DB->get_records('techproject_requirement', array('projectid' => $moduleid), '', 'userid,userid');
    $usersspecs = $DB->get_records('techproject_specification', array('projectid' => $moduleid), '', 'userid,userid');
    $userstasks = $DB->get_records('techproject_task', array('projectid' => $moduleid), '', 'userid,userid');
    $userstasksassigned = $DB->get_records('techproject_task', array('projectid' => $moduleid), '', 'assignee,assignee');
    $userstasksowners = $DB->get_records('techproject_task', array('projectid' => $moduleid), '', 'owner,owner');
    $usersdelivs = $DB->get_records('techproject_deliverable', array('projectid' => $moduleid), '', 'userid,userid');
    $usersmiles = $DB->get_records('techproject_milestone', array('projectid' => $moduleid), '', 'userid,userid');

    $allusers = array();
    if (!empty($usersreqs)) {
        $allusers = array_keys($usersreqs);
    }
    if (!empty($usersspecs)) {
        $allusers = array_merge($allusers, array_keys($usersspecs));
    }
    if (!empty($userstasks)) {
        $allusers = array_merge($allusers, array_keys($userstasks));
    }
    if (!empty($userstasksassigned)) {
        $allusers = array_merge($allusers, array_keys($userstasksassigned));
    }
    if (!empty($userstasksowned)) {
        $allusers = array_merge($allusers, array_keys($userstasksowned));
    }
    if (!empty($userstasksdelivs)) {
        $allusers = array_merge($allusers, array_keys($userstasksdelivs));
    }
    if (!empty($userstasksmiles)) {
        $allusers = array_merge($allusers, array_keys($userstasksmiles));
    }
    $userlist = implode("','", $allusers);
    $participants = $DB->get_records_list('user', array('id' => "'$userlist'"));
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
 */
function techproject_scale_used($cmid, $scaleid) {
    global $DB;

    $return = false;

    // Note : scales are assigned using negative index in the grade field of project (see mod/assignement/lib.php).
    $rec = $DB->get_record('techproject', array('id' => $cmid, 'grade' => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }
    return $return;
}

/**
 * Serves the techproject attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function techproject_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $fileareas = array('intro', 'requirementdescription', 'specificationdescription', 'milestonedescription', 'taskdescription',
                       'deliverabledescription', 'abstract', 'rationale', 'environment');
    $areastotables = array('requirementdescription' => 'techproject_requirement',
                           'specificationdescription' => 'techproject_specifciation',
                           'milestonedescription' => 'techproject_milestone',
                           'taskdescription' => 'techproject_task',
                           'deliverabledescription' => 'techproject_deliverable',
                           'abstract' => 'techproject_heading',
                           'rationale' => 'techproject_heading',
                           'environment' => 'techproject_heading');

    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $relatedtable = $areastotables[$filearea];

    $entryid = (int)array_shift($args);

    if (!$project = $DB->get_record('techproject', array('id' => $cm->instance))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_techproject/$filearea/$entryid/$relativepath";
    if (!($file = $fs->get_file_by_hash(sha1($fullpath))) or $file->is_directory()) {
        return false;
    }

    $entry = $DB->get_record($relatedtable, array('id' => $entryid));

    // Make sure groups allow this user to see this file.
    if ($entry->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {
        // Groups are being used.
        if (!groups_group_exists($entry->groupid)) { // Can't find group.
            return false;                           // Be safe and don't send it to anyone.
        }

        if (!groups_is_member($entry->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
            // Do not send posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS.
            return false;
        }
    }

    if ((!isloggedin() || isguestuser()) && !$project->guestsallowed) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true); // Download MUST be forced - security!
}

/**
 * This function allows the tool_dbcleaner to register integrity checks
 */
function techproject_dbcleaner_add_keys() {
    $keys = array(
        array('techproject', 'course', 'course', 'id', ''),
        array('techproject_heading', 'projectid', 'techproject', 'id', ''),
        array('techproject_requirement', 'projectid', 'techproject', 'id', ''),
        array('techproject_specification', 'projectid', 'techproject', 'id', ''),
        array('techproject_task', 'projectid', 'techproject', 'id', ''),
        array('techproject_milestone', 'projectid', 'techproject', 'id', ''),
        array('techproject_deliverable', 'projectid', 'techproject', 'id', ''),
        array('techproject_assessment', 'projectid', 'techproject', 'id', ''),
        array('techproject_criterion', 'projectid', 'techproject', 'id', ''),
        array('techproject_spec_to_req', 'projectid', 'techproject', 'id', ''),
    );

    return $keys;
}