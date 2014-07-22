<?php

/**
 * Project : Technical Project Manager (IEEE like)
 *
 * Specification operations.
 *
 * @package mod-techproject
 * @category mod
 * @author Valery Fremaux (France) (valery.fremaux@club-internet.fr)
 * @date 2009/02/01
 * @version phase2
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * Notifies all project managers of a new specification being entered
 *
 */
function techproject_notify_new_specification(&$project, $cmid, &$specification, $currentgroupid){
    global $USER, $COURSE, $CFG, $DB;

    techproject_complete_user($USER);

    $class = get_string('specification', 'techproject');
    if (!$severity = $DB->get_record('techproject_qualifier', array('code' => $specification->severity, 'domain' => 'severity', 'projectid' => $project->id))){
        $severity = $DB->get_record('techproject_qualifier', array('code' => $specification->severity, 'domain' => 'severity', 'projectid' => 0));
    }
    if (!$priority = $DB->get_record('techproject_qualifier', array('code' => $specification->priority, 'domain' => 'priority', 'projectid' => $project->id))){
        $priority = $DB->get_record('techproject_qualifier', array('code' => $specification->priority, 'domain' => 'priority', 'projectid' => 0));
    }
    if (!$complexity = $DB->get_record('techproject_qualifier', array('code' => $specification->complexity, 'domain' => 'complexity', 'projectid' => $project->id))){
        $complexity = $DB->get_record('techproject_qualifier', array('code' => $specification->complexity, 'domain' => 'complexity', 'projectid' => 0));
    }
    if (!$severity) $severity->label = "N.Q.";
    if (!$priority) $priority->label = "N.Q.";
    if (!$complexity) $complexity->label = "N.Q.";
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
    $managers = get_users_by_capability($context, 'mod/techproject:manage', 'u.id, firstname, lastname, email, picture, mailformat');
    if (!empty($managers)) {
       foreach ($managers as $manager) {
           techproject_complete_user($manager);
           email_to_user($manager, $USER, $COURSE->shortname .' - '.get_string('notifynewspec', 'techproject'), html_to_text($message), $message);
       }
    }
}

/**
 * Notifies all project managers of a new requirement being entered
 *
 *
 */
function techproject_notify_new_requirement(&$project, $cmid, &$requirement, $currentgroupid) {
    global $USER, $COURSE, $CFG, $DB;

    techproject_complete_user($USER);

    $class = get_string('requirement', 'techproject');
    if (!$strength = $DB->get_record('techproject_qualifier', array('code' => $requirement->strength, 'domain' => 'strength', 'projectid' => $project->id))) {
        $strength = $DB->get_record('techproject_qualifier', array('code' => $requirement->strength, 'domain' => 'strength', 'projectid' => 0));
    }
    if (!$strength) $strength->label = "N.Q.";
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
    $managers = get_users_by_capability($context, 'mod/techproject:manage', 'u.id, firstname, lastname, email, picture, mailformat');

    if (!empty($managers)) {
        foreach ($managers as $manager) {
            techproject_complete_user($manager);
            email_to_user($manager, $USER, $COURSE->shortname .' - '.get_string('notifynewrequ', 'techproject'), html_to_text($message), $message);
        }
    }
}

/**
 * Notifies all project managers of a new task being entered
 *
 */
function techproject_notify_new_task(&$project, $cmid, &$task, $currentgroupid) {
    global $USER, $COURSE, $CFG, $DB;

    $class = get_string('task', 'techproject');
    if (!$worktype = $DB->get_record('techproject_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => $project->id))) {
        $worktype = $DB->get_record('techproject_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => 0));
    }
    if (!empty($task->assignee)) {
        $assignee = fullname($DB->get_record('user', array('id' => $task->assignee)));
    } else {
        $assignee = get_string('unassigned', 'techproject');
    }
    $status = $DB->get_record('techproject_qualifier', array('code' => $task->status, 'domain' => 'taskstatus'));
    $planned = $task->planned;
    if (!$risk = $DB->get_record('techproject_qualifier', array('code' => $task->risk, 'domain' => 'risk', 'projectid' => $project->id))) {
        $risk = $DB->get_record('techproject_qualifier', array('code' => $task->risk, 'domain' => 'risk', 'projectid' => 0));
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

    $qualifiers[] = get_string('worktype', 'techproject').': '.$worktype->label;
    $qualifiers[] = get_string('assignee', 'techproject').': '.$assignee;
    $qualifiers[] = get_string('status', 'techproject').': '.$status->label;
    $qualifiers[] = get_string('planned', 'techproject').': '.$planned.' '.@$TIMEUNITS[$project->timeunit];
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
    $managers = get_users_by_capability($context, 'mod/techproject:manage', 'u.id, firstname, lastname, email, picture, mailformat');
    if (!empty($managers)) {
        foreach($managers as $manager){
            techproject_complete_user($manager);
            email_to_user($manager, $USER, $COURSE->shortname .' - '.get_string('notifynewtask', 'techproject'), html_to_text($message), $message);
        }
    }
}

/**
 * Notifies all project managers of a new task being entered
 *
 */
function techproject_notify_new_deliverable(&$project, $cmid, &$deliv, $currentgroupid) {
    global $USER, $COURSE, $CFG, $DB;

    $class = get_string('deliverable', 'techproject');
    if (!$status = $DB->get_record('techproject_qualifier', array('code' => $deliv->status, 'domain' => 'delivstatus', 'projectid' => $project->id))) {
        $status = $DB->get_record('techproject_qualifier', array('code' => $deliv->status, 'domain' => 'delivstatus', 'projectid' => 0));
    }

    if (!$status) {
        $status->label = "N.Q.";
    }

    $qualifiers[] = get_string('status', 'techproject').': '.$status->label;

    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $message = techproject_compile_mail_template('newentrynotify', array(
        'PROJECT' => $projectheading->title,
        'CLASS' => $class,
        'USER' => fullname($USER),
        'ENTRYNODE' => implode(".", techproject_tree_get_upper_branch('techproject_deliverable', $deliv->id, true, true)),
        'ENTRYABSTRACT' => $deliv->abstract,
        'ENTRYDESCRIPTION' => format_string($deliv->description),
        'QUALIFIERS' => implode('<br/>', $qualifiers),
        'ENTRYLINK' => $CFG->wwwroot."/mod/techproject/view.php?id={$cmid}&view=deliv&group={$currentgroupid}"
    ), 'techproject');
    $context = context_module::instance($cmid);
    $managers = get_users_by_capability($context, 'mod/techproject:manage', 'u.id, firstname, lastname, email, picture, mailformat');
    if (!empty($managers)) {
        foreach($managers as $manager){
            techproject_complete_user($manager);
            email_to_user($manager, $USER, $COURSE->shortname .' - '.get_string('notifynewdeliv', 'techproject'), html_to_text($message), $message);
        }
    }
}


/**
* Notifies all project managers of a new task being entered
*
*/
function techproject_notify_new_milestone(&$project, $cmid, &$milestone, $currentgroupid){
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
    $managers = get_users_by_capability($context, 'mod/techproject:manage', 'u.id, firstname, lastname, email, picture, mailformat');
    if (!empty($managers)){
        foreach($managers as $manager) {
            techproject_complete_user($manager);
            email_to_user ($manager, $USER, $COURSE->shortname .' - '.get_string('notifynewmile', 'techproject'), html_to_text($message), $message);
        }
    }
}

/**
 * Notifies an assignee when loosing a task monitoring
 *
 */
function techproject_notify_task_unassign(&$project, &$task, $oldassigneeid, $currentgroupid) {
    global $USER, $COURSE, $DB;

    techproject_complete_user($USER);

    $oldAssignee = $DB->get_record('user', array('id' => $oldassigneeid));

    if (!$owner = $DB->get_record('user', array('id' => $task->owner))) {
        $owner = $USER;
    }
    if (!$worktype = $DB->get_record('techproject_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => $project->id))) {
        if (!$worktype = $DB->get_record('techproject_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => 0))) {
            $worktype->label = get_string('unqualified', 'techproject');
        }
    }
    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $message = techproject_compile_mail_template('taskreleasenotify', array(
        'PROJECT' => $projectheading->title,
        'OWNER' => fullname($owner),
        'TASKNODE' => implode(".", techproject_tree_get_upper_branch('techproject_task', $task->id, true, true)),
        'TASKABSTRACT' => stripslashes($task->abstract),
        'TASKDESCRIPTION' => $task->description,
        'WORKTYPE' => $worktype->label,
        'DONE' => $task->done
    ), 'techproject');
    email_to_user ($oldAssignee, $owner, $COURSE->shortname .' - '.get_string('notifyreleasedtask', 'techproject'), html_to_text($message), $message);
}


/**
* Notifies an assignee when getting assigned
*
*/
function techproject_notify_task_assign(&$project, &$task, $currentgroupid){
    global $COURSE, $USER, $DB;

    techproject_complete_user($USER);

    if (!$assignee = $DB->get_record('user', array('id' => $task->assignee))){
        return;
    }
    if (!$owner = $DB->get_record('user', array('id' => $task->owner))){
        $owner = $USER;
    }
    if (!$worktype = $DB->get_record('techproject_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => $project->id))){
        if (!$worktype = $DB->get_record('techproject_qualifier', array('code' => $task->worktype, 'domain' => 'worktype', 'projectid' => 0))){
            $worktype->label = get_string('unqualified', 'techproject');
        }
    }
    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $message = techproject_compile_mail_template('newtasknotify', array(
        'PROJECT' => $projectheading->title,
        'OWNER' => fullname($owner),
        'TASKNODE' => implode(".", techproject_tree_get_upper_branch('techproject_task', $task->id, true, true)),
        'TASKABSTRACT' => stripslashes($task->abstract),
        'TASKDESCRIPTION' => $task->description,
        'WORKTYPE' => $worktype->label,
        'DONE' => $task->done
    ), 'techproject');
    email_to_user($assignee, $owner, $COURSE->shortname .' - '.get_string('notifynewtask', 'techproject'), html_to_text($message), $message);
}
