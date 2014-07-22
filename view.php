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
 * Project : Technical Project Manager (IEEE like)
 *
 * This page prints a particular instance of project
 *
 * @package mod-techproject
 * @subpackage framework
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/mod/techproject/lib.php');
require_once($CFG->dirroot.'/mod/techproject/locallib.php');
require_once($CFG->dirroot.'/mod/techproject/notifylib.php');

$PAGE->requires->js('/mod/techproject/js/js.js');
$PAGE->requires->jquery();

// fixes locale for all date printing.
setLocale(LC_TIME, substr(current_language(), 0, 2));

$id = required_param('id', PARAM_INT);   // module id
$view = optional_param('view', @$_SESSION['currentpage'], PARAM_CLEAN);   // viewed page id
$nohtmleditorneeded = true;
$editorfields = '';

if ($view == 'view_gantt') {
    $lang = current_language();
    $PAGE->requires->js('/mod/techproject/js/dhtmlxGantt/codebase/dhtmlxcommon.js', true);
    $PAGE->requires->js('/mod/techproject/js/dhtmlxGantt/sources/dhtmlxgantt.js', true);
    $PAGE->requires->js('/mod/techproject/js/dhtmlxGantt/sources/lang/'.$lang.'/'.$lang.'.js', true);
}

$timenow = time();
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

$project->cmid = $cm->id;

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
$systemcontext = context_system::instance(0);

$strprojects = get_string('modulenameplural', 'techproject');
$strproject  = get_string('modulename', 'techproject');
$straction = (@$action) ? '-> '.get_string(@$action, 'techproject') : '';

// get some session toggles if possible
if (array_key_exists('editmode', $_GET) && !empty($_GET['editmode'])) {
    $_SESSION['editmode'] = $_GET['editmode'];
} else {
    if (!array_key_exists('editmode', $_SESSION)) {
        $_SESSION['editmode'] = 'off';
    }
}
$USER->editmode = $_SESSION['editmode'];

// check current group and change, for anyone who could
if (!$groupmode = groups_get_activity_groupmode($cm, $course)){ // groups are being used ?
    $currentgroupid = 0;
} else {
    $changegroup = isset($_GET['group']) ? $_GET['group'] : -1;  // Group change requested?
    if (isguestuser()){ // for guests, use session
        if ($changegroup >= 0) {
            $_SESSION['guestgroup'] = $changegroup;
        }
        $currentgroupid = 0 + @$_SESSION['guestgroup'];
    } else { // for normal users, change current group
        $currentgroupid = 0 + groups_get_course_group($course, true);
        if (!groups_is_member($currentgroupid , $USER->id) && !is_siteadmin($USER->id)) $USER->editmode = "off";
    }
}

// ...display header...
$url = new moodle_url('/mod/techproject/view.php', array('id' => $id));
$PAGE->set_title(format_string($project->name));
$PAGE->set_url($url);
$PAGE->set_heading('');
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(true);
$PAGE->set_button(update_module_button($cm->id, $course->id, $strproject));

$pagebuffer = $OUTPUT->header();

$pagebuffer .= "<div align=\"right\">";
$pagebuffer .= techproject_edition_enable_button($cm, $course, $project, $USER->editmode);
$pagebuffer .= "</div>";
// ...and if necessary set default action
if (has_capability('mod/techproject:gradeproject', $context)) {
    if (empty($action)) { // no action specified, either go straight to elements page else the admin page
        $action = 'teachersview';
    }
} elseif (!isguestuser()) { // it's a student then
    if (!$cm->visible) {
        notice(get_string('activityiscurrentlyhidden'));
    }
    if ($groupmode == SEPARATEGROUPS && !$currentgroupid && !$project->ungroupedsees){
        $action = 'notingroup';
    }
    if ($timenow < $project->projectstart) {
        $action = 'notavailable';
    } elseif (!@$action) {
        $action = 'studentsview';
    }
} else { // it's a guest, just watch if possible!
    if ($project->guestsallowed){
        $action = 'guestview';
    } else {
        $action = 'notavailable';
    }
}
// ...log activity...
add_to_log($course->id, 'techproject', 'view', "view.php?id=$cm->id", $project->id, $cm->id);

// Pass useful values to javasctript.

$moodlevars = new StdClass;
$moodlevars->view = $view;
$moodlevars->userid = $USER->id;
$moodlevars->cmid = $cm->id;
$moodlevarsjson = addslashes(json_encode($moodlevars));
$pagebuffer .= "<script type=\"text/javascript\">";
$pagebuffer .= "var moodlevars = eval('({$moodlevarsjson})');";
$pagebuffer .= "</script>";


/****************** display final grade (for students) ************************************/
if ($action == 'displayfinalgrade' ) {
    echo $pagebuffer;
    echo get_string('endofproject', 'techproject');
/****************** assignment not available (for students)***********************/
} elseif ($action == 'notavailable') {
    echo $pagebuffer;
    echo $OUTPUT->heading(get_string('notavailable', 'techproject'));

/****************** student's view  ***********************/
} elseif ($action == 'studentsview') {

    if ($timenow > $project->projectend) { // if project is over, just cannot change anything more
        $pagebuffer .= $OUTPUT->box('<span class="inconsistency">'.get_string('projectisover','techproject').'</span>', 'center', '70%');
        $USER->editmode = 'off';
    }
        /// Print settings and things in a table across the top
    $pagebuffer .= '<table width="100%" cellpadding="3" cellspacing="0"><tr valign="top">';

    /// Allow the student to change groups (for this session), seeing other's work
    if ($groupmode) { // if group are used
        $groups = groups_get_all_groups($course->id);
        if ($groups) {
            $grouptable = array();
            foreach ($groups as $aGroup) {
                // I can see only the groups i belong to.
                if (($groupmode == SEPARATEGROUPS) && !groups_is_member($aGroup->id, $USER->id)) {
                    continue;
                }
                // Mark group as mine if i am member.
                if (($groupmode == VISIBLEGROUPS) && groups_is_member($aGroup->id, $USER->id)) {
                    $aGroup->name .= ' (*)';
                }
                $grouptable[$aGroup->id] = $aGroup->name;
            }
            $pagebuffer .= '<td>';
            $pagebuffer .= groups_print_activity_menu($cm, $url, true);
            $pagebuffer .= '</td>';
        }
    }
    $pagebuffer .= '</table>';
    // Ungrouped students can view group 0's project (teacher's) but not change it if ungroupedsees is off.
    // in visible mode, student from other groups cannot edit our material.
    if ($groupmode != SEPARATEGROUPS && (!$currentgroupid || !groups_is_member($currentgroupid, $USER->id))) {
        if (!$project->ungroupedsees) {
            $USER->editmode = 'off';
        }
        include('techproject.php');
    } else { // just view unique project workspace
        include('techproject.php');
    }
}

/****************** guest's view - display projects without editing capabilities  ************/
elseif ($action == 'guestview') {

    $demostr = '';
    if (!$project->guestscanuse || $currentgroupid != 0){ // guest can sometimes edit group 0
        $USER->editmode = 'off';
    } elseif ($project->guestscanuse && !$currentgroupid && $timenow < $project->projectend) { // guest could have edited but project is closed
        $demostr = '(' . get_string('demomodeclosedproject', 'techproject') . ') ' . $OUTPUT->help_icon('demomode', 'techproject', false);
        $USER->editmode = 'off';
    } else {
       $demostr = '(' . get_string('demomode', 'techproject') . ') ' . $OUTPUT->help_icon('demomode', 'techproject', false);
    }
    /// Print settings and things in a table across the top
    $pagebuffer .= '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

    /// Allow the guest to change groups (for this session) only for visible groups
    if ($groupmode == VISIBLEGROUPS) {
        $groups = groups_get_all_groups($course->id);
        if ($groups){
            $grouptable = array();
            foreach ($groups as $aGroup) {
                $grouptable[$aGroup->id] = $aGroup->name;
            }
            $pagebuffer .= '<td>';
            $pagebuffer .= groups_print_activity_menu($cm, $url, true);
            $pagebuffer .= '</td>';
        }
    }        
    $pagebuffer .= '</table>';
    include('techproject.php');

/****************** teacher's view - display admin page  ************/
} elseif ($action == 'teachersview') {
    /// Check to see if groups are being used in this workshop
    /// and if so, set $currentgroupid to reflect the current group
    $currentgroupid = 0 + groups_get_course_group($course, true); 
    /// Print settings and things in a table across the top
    $pagebuffer .= '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

    /// Allow the teacher to change groups (for this session)
    if ($groupmode) {
        $groups = groups_get_all_groups($course->id);
        if (!empty($groups)){
            $grouptable = array();
            foreach($groups as $aGroup){
                $grouptable[$aGroup->id] = $aGroup->name;
            }
            $pagebuffer .= '<td>';
            $pagebuffer .= groups_print_activity_menu($cm, $url, true);
            $pagebuffer .= '</td>';
        }
    }        
    $pagebuffer .= '</tr></table>';
    if (empty($currentgroupid)){
        $currentgroupid = 0;
    }
    include('techproject.php');

/****************** show description  ************/
} elseif ($action == 'showdescription') {
    echo $pagebuffer;
    techproject_print_assignement_info($project);
    echo $OUTPUT->box(format_text($project->description, $project->format), 'center', '70%', '', 5, 'generalbox', 'intro');
    echo $OUTPUT->continue_button($_SERVER["HTTP_REFERER"]);

/*************** student is not in a group **************************************/
} elseif ($action == 'notingroup') {
    echo $pagebuffer;
    echo $OUTPUT->box(format_text(get_string('notingroup', 'techproject'), 'HTML'), 'center', '70%', '', 5, 'generalbox', 'intro');
    echo $OUTPUT->continue_button($_SERVER["HTTP_REFERER"]);     

/*************** no man's land **************************************/
} else {
    echo $pagebuffer;
    print_error('errorfatalaction', 'techproject', $action);
}

echo $OUTPUT->footer($course);

