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
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Notifies all project managers of a new specification being entered
 * @param objectref &$project
 * @param int $cmid
 * @param objectref &$specification
 * @param int $currentgroupid
 */
function techproject_notify_new_specification(&$project, $cmid, &$specification, $currentgroupid) {
    global $USER, $COURSE, $CFG, $DB;

    techproject_complete_user($USER);

    $class = get_string('specification', 'techproject');
    $params = array('code' => $specification->severity, 'domain' => 'severity', 'projectid' => $project->id);
    if (!$severity = $DB->get_record('techproject_qualifier', $params)) {
        $params = array('code' => $specification->severity, 'domain' => 'severity', 'projectid' => 0);
        $severity = $DB->get_record('techproject_qualifier', $params);
    }
    $params = array('code' => $specification->priority, 'domain' => 'priority', 'projectid' => $project->id);
    if (!$priority = $DB->get_record('techproject_qualifier', $params)) {
        $params = array('code' => $specification->priority, 'domain' => 'priority', 'projectid' => 0);
        $priority = $DB->get_record('techproject_qualifier', $params);
    }
    $params = array('code' => $specification->complexity, 'domain' => 'complexity', 'projectid' => $project->id);
    if (!$complexity = $DB->get_record('techproject_qualifier', $params)) {
        $params = array('code' => $specification->complexity, 'domain' => 'complexity', 'projectid' => 0);
        $complexity = $DB->get_record('techproject_qualifier', $params);
    }
    if (!$severity) {
        $severity->label = "N.Q.";
    }
    if (!$priority) {
        $priority->label = "N.Q.";
    }
    if (!$complexity) {
        $complexity->label = "N.Q.";
    }
    $qualifiers[] = get_string('severity', 'techproject').': '.$severity->label;
    $qualifiers[] = get_string('priority', 'techproject').': '.$priority->label;
    $qualifiers[] = get_string('complexity', 'techproject').': '.$complexity->label;
    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $message = techproject_compile_mail_template('newentrynotify', array(
        'PROJECT' => $projectheading->title,
        'CLASS' => $class,
        'USER' => fullname($USER),
        'ENTRYNODE' => implode(".", techproject_tree_get_upper_branch('techproject_specification', $specification->id, true, true)),
        'ENTRYABSTRACT' => stripslashes($specification->abstract),
        'ENTRYDESCRIPTION' => $specification->description,
        'QUALIFIERS' => implode('<br/>', $qualifiers),
        'ENTRYLINK' => $CFG->wwwroot."/mod/techproject/view.php?id={$cmid}&view=specifications&group={$currentgroupid}"
    ), 'techproject');
    $context = context_module::instance($cmid);
    $fields = \mod_techproject\compat::get_fields_for_get_cap();
    $managers = get_users_by_capability($context, 'mod/techproject:manage', $fields);
    if (!empty($managers)) {
        foreach ($managers as $manager) {
            techproject_complete_user($manager);
            $subject = $COURSE->shortname .' - '.get_string('notifynewspec', 'techproject');
            email_to_user($manager, $USER, $subject, html_to_text($message), $message);
        }
    }
}

/**
 * Notifies all project managers of a new requirement being entered
 * @param objectref &$project
 * @param int $cmid
 * @param objectref &$requirement
 * @param int $currentgroupid
 */
function techproject_notify_new_requirement(&$project, $cmid, &$requirement, $currentgroupid) {
    global $USER, $COURSE, $CFG, $DB;

    techproject_complete_user($USER);

    $class = get_string('requirement', 'techproject');
    $params = array('code' => $requirement->strength, 'domain' => 'strength', 'projectid' => $project->id);
    if (!$strength = $DB->get_record('techproject_qualifier', $params)) {
        $params = array('code' => $requirement->strength, 'domain' => 'strength', 'projectid' => 0);
        $strength = $DB->get_record('techproject_qualifier', $params);
    }
    if (!$strength) {
        $strength->label = "N.Q.";
    }
    $qualifiers[] = get_string('strength', 'techproject').': '.$strength->label;
    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $message = techproject_compile_mail_template('newentrynotify', array(
        'PROJECT' => $projectheading->title,
        'CLASS' => $class,
        'USER' => fullname($USER),
        'ENTRYNODE' => implode(".", techproject_tree_get_upper_branch('techproject_requirement', $requirement->id, true, true)),
        'ENTRYABSTRACT' => stripslashes($requirement->abstract),
        'ENTRYDESCRIPTION' => $requirement->description,
        'QUALIFIERS' => implode('<br/>', $qualifiers),
        'ENTRYLINK' => $CFG->wwwroot."/mod/techproject/view.php?id={$cmid}&view=requirements&group={$currentgroupid}"
    ), 'techproject');
    $context = context_module::instance($cmid);
    $fields = \mod_techproject\compat::get_fields_for_get_cap();
    $managers = get_users_by_capability($context, 'mod/techproject:manage', $fields);

    if (!empty($managers)) {
        foreach ($managers as $manager) {
            techproject_complete_user($manager);
            $subject = $COURSE->shortname .' - '.get_string('notifynewrequ', 'techproject');
            email_to_user($manager, $USER, $subject, html_to_text($message), $message);
        }
    }
}

/**
 * Notifies all project managers of a new task being entered
 * @param objectref &$project
 * @param int $cmid
 * @param objectref &$task
 * @param int $currentgroupid
 */
function techproject_notify_new_task(&$project, $cmid, &$task, $currentgroupid) {
    global $USER, $COURSE, $CFG, $DB;

    $class = get_string('task', 'techproject');
    $params = array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => $project->id);
    if (!$worktype = $DB->get_record('techproject_qualifier', $params)) {
        $params = array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => 0);
        $worktype = $DB->get_record('techproject_qualifier', $params);
    }
    if (!empty($task->assignee)) {
        $assignee = fullname($DB->get_record('user', array('id' => $task->assignee)));
    } else {
        $assignee = get_string('unassigned', 'techproject');
    }
    $status = $DB->get_record('techproject_qualifier', array('code' => $task->status, 'domain' => 'taskstatus'));
    $planned = $task->planned;
    $params = array('code' => $task->risk, 'domain' => 'risk', 'projectid' => $project->id);
    if (!$risk = $DB->get_record('techproject_qualifier', $params)) {
        $params = array('code' => $task->risk, 'domain' => 'risk', 'projectid' => 0);
        $risk = $DB->get_record('techproject_qualifier', $params);
    }

    if (!$worktype) {
        $worktype->label = "N.Q.";
    }
    if (!$status) {
        $status->label = "N.Q.";
    }
    if (!$risk) {
        $risk->label = "N.Q.";
    }

    $timeunits = array(get_string('unset', 'techproject'),
                       get_string('hours', 'techproject'),
                       get_string('halfdays', 'techproject'),
                       get_string('days', 'techproject'));

    $qualifiers[] = get_string('worktype', 'techproject').': '.$worktype->label;
    $qualifiers[] = get_string('assignee', 'techproject').': '.$assignee;
    $qualifiers[] = get_string('status', 'techproject').': '.$status->label;
    $qualifiers[] = get_string('planned', 'techproject').': '.$planned.' '.@$timeunits[$project->timeunit];
    $qualifiers[] = get_string('risk', 'techproject').': '.$risk->label;
    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $message = techproject_compile_mail_template('newentrynotify', array(
        'PROJECT' => $projectheading->title,
        'CLASS' => $class,
        'USER' => fullname($USER),
        'ENTRYNODE' => implode(".", techproject_tree_get_upper_branch('techproject_task', $task->id, true, true)),
        'ENTRYABSTRACT' => stripslashes($task->abstract),
        'ENTRYDESCRIPTION' => $task->description,
        'QUALIFIERS' => implode('<br/>', $qualifiers),
        'ENTRYLINK' => $CFG->wwwroot."/mod/techproject/view.php?id={$cmid}&view=tasks&group={$currentgroupid}"
    ), 'techproject');
    $context = context_module::instance($cmid);
    $fields = \mod_techproject\compat::get_fields_for_get_cap();
    $managers = get_users_by_capability($context, 'mod/techproject:manage', $fields);
    if (!empty($managers)) {
        foreach ($managers as $manager) {
            techproject_complete_user($manager);
            $subject = $COURSE->shortname .' - '.get_string('notifynewtask', 'techproject');
            email_to_user($manager, $USER, $subject, html_to_text($message), $message);
        }
    }
}


/**
 * Notifies all project managers of a new task being entered
 * @param objectref &$project
 * @param int $cmid
 * @param objectref &$milestone
 * @param int $currentgroupid
 */
function techproject_notify_new_milestone(&$project, $cmid, &$milestone, $currentgroupid) {
    global $USER, $COURSE, $CFG, $DB;

    techproject_complete_user($USER);

    $class = get_string('milestone', 'techproject');
    $qualifiers[] = get_string('datedued', 'techproject').': '.userdate($milestone->deadline);
    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));

    $message = techproject_compile_mail_template('newentrynotify', array(
        'PROJECT' => $projectheading->title,
        'CLASS' => $class,
        'USER' => fullname($USER),
        'ENTRYNODE' => implode(".", techproject_tree_get_upper_branch('techproject_milestone', $milestone->id, true, true)),
        'ENTRYABSTRACT' => stripslashes($milestone->abstract),
        'ENTRYDESCRIPTION' => $milestone->description,
        'QUALIFIERS' => implode('<br/>', $qualifiers),
        'ENTRYLINK' => $CFG->wwwroot."/mod/techproject/view.php?id={$cmid}&view=milestones&group={$currentgroupid}"
    ), 'techproject');

    $context = context_module::instance($cmid);
    $fields = \mod_techproject\compat::get_fields_for_get_cap();
    $managers = get_users_by_capability($context, 'mod/techproject:manage', $fields);
    if (!empty($managers)) {
        foreach ($managers as $manager) {
            techproject_complete_user($manager);
            $subject = $COURSE->shortname .' - '.get_string('notifynewmile', 'techproject');
            email_to_user ($manager, $USER, $subject, html_to_text($message), $message);
        }
    }
}

/**
 * Notifies an assignee when loosing a task monitoring
 * @param objectref &$project
 * @param objectref &$task
 * @param int $oldassigneeid
 * @param int $currentgroupid
 */
function techproject_notify_task_unassign(&$project, &$task, $oldassigneeid, $currentgroupid) {
    global $USER, $COURSE, $DB;

    techproject_complete_user($USER);

    $oldassignee = $DB->get_record('user', array('id' => $oldassigneeid));

    if (!$owner = $DB->get_record('user', array('id' => $task->owner))) {
        $owner = $USER;
    }
    $params = array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => $project->id);
    if (!$worktype = $DB->get_record('techproject_qualifier', $params)) {
        $params = array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => 0);
        if (!$worktype = $DB->get_record('techproject_qualifier', $params)) {
            $worktype->label = get_string('unqualified', 'techproject');
        }
    }
    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $message = techproject_compile_mail_template('taskreleasenotify', array(
        'PROJECT' => $projectheading->title,
        'OWNER' => fullname($owner),
        'TASKNODE' => implode('.', techproject_tree_get_upper_branch('techproject_task', $task->id, true, true)),
        'TASKABSTRACT' => stripslashes($task->abstract),
        'TASKDESCRIPTION' => $task->description,
        'WORKTYPE' => $worktype->label,
        'DONE' => $task->done
    ), 'techproject');
    $subject = $COURSE->shortname .' - '.get_string('notifyreleasedtask', 'techproject');
    email_to_user ($oldassignee, $owner, $subject, html_to_text($message), $message);
}

/**
 * Notifies all project managers of a new requirement being entered
 * @param objectref &$project
 * @param int $cmid
 * @param objectref &$requirement
 * @param int $currentgroupid
 */
function techproject_notify_new_deliverable(&$project, $cmid, &$deliverable, $currentgroupid) {
    global $USER, $COURSE, $CFG, $DB;

    techproject_complete_user($USER);

    $class = get_string('deliverable', 'techproject');
    $params = array('code' => $deliverable->status, 'domain' => 'strength', 'projectid' => $project->id);
    if (!$strength = $DB->get_record('techproject_qualifier', $params)) {
        $params = array('code' => $deliverable->strength, 'domain' => 'strength', 'projectid' => 0);
        $strength = $DB->get_record('techproject_qualifier', $params);
    }
    if (!$strength) {
        $strength->label = "N.Q.";
    }
    $qualifiers[] = get_string('strength', 'techproject').': '.$strength->label;
    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $message = techproject_compile_mail_template('newentrynotify', array(
        'PROJECT' => $projectheading->title,
        'CLASS' => $class,
        'USER' => fullname($USER),
        'ENTRYNODE' => implode(".", techproject_tree_get_upper_branch('techproject_deliverable', $deliverable->id, true, true)),
        'ENTRYABSTRACT' => stripslashes($deliverable->abstract),
        'ENTRYDESCRIPTION' => $deliverable->description,
        'QUALIFIERS' => implode('<br/>', $qualifiers),
        'ENTRYLINK' => $CFG->wwwroot."/mod/techproject/view.php?id={$cmid}&view=deliverables&group={$currentgroupid}"
    ), 'techproject');
    $context = context_module::instance($cmid);
    $fields = \mod_techproject\compat::get_fields_for_get_cap();
    $managers = get_users_by_capability($context, 'mod/techproject:manage', $fields);

    if (!empty($managers)) {
        foreach ($managers as $manager) {
            techproject_complete_user($manager);
            $subject = $COURSE->shortname .' - '.get_string('notifynewrequ', 'techproject');
            email_to_user($manager, $USER, $subject, html_to_text($message), $message);
        }
    }
}

/**
 * Notifies an assignee when getting assigned
 * @param objectref &$project
 * @param objectref &$task
 * @param int $currentgroupid
 */
function techproject_notify_task_assign(&$project, &$task, $currentgroupid) {
    global $COURSE, $USER, $DB;

    techproject_complete_user($USER);

    if (!$assignee = $DB->get_record('user', array('id' => $task->assignee))) {
        return;
    }
    if (!$owner = $DB->get_record('user', array('id' => $task->owner))) {
        $owner = $USER;
    }
    $params = array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => $project->id);
    if (!$worktype = $DB->get_record('techproject_qualifier', $params)) {
        $sqlparams = array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => 0);
        if (!$worktype = $DB->get_record('techproject_qualifier', $sqlparams)) {
            $worktype->label = get_string('unqualified', 'techproject');
        }
    }
    $params = array('projectid' => $project->id, 'groupid' => $currentgroupid);
    $projectheading = $DB->get_record('techproject_heading', $params);
    $message = techproject_compile_mail_template('newtasknotify', array(
        'PROJECT' => $projectheading->title,
        'OWNER' => fullname($owner),
        'TASKNODE' => implode(".", techproject_tree_get_upper_branch('techproject_task', $task->id, true, true)),
        'TASKABSTRACT' => stripslashes($task->abstract),
        'TASKDESCRIPTION' => $task->description,
        'WORKTYPE' => $worktype->label,
        'DONE' => $task->done
    ), 'techproject');
    $subject = $COURSE->shortname .' - '.get_string('notifynewtask', 'techproject');
    email_to_user($assignee, $owner, $subject, html_to_text($message), $message);
}
