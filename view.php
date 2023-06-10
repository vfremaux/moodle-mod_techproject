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

// Fixes locale for all date printing.
setlocale(LC_TIME, substr(current_language(), 0, 2));

$id = required_param('id', PARAM_INT);   // Module id.
$view = optional_param('view', @$_SESSION['currentpage'], PARAM_CLEAN); // Viewed page id.

if ($view == 'view_gantt') {
    $lang = current_language();
    $PAGE->requires->js('/mod/techproject/js/dhtmlxGantt/codebase/dhtmlxcommon.js', true);
    $PAGE->requires->js('/mod/techproject/js/dhtmlxGantt/sources/dhtmlxgantt.js', true);
    $PAGE->requires->js('/mod/techproject/js/dhtmlxGantt/sources/lang/'.$lang.'/'.$lang.'.js', true);
    $PAGE->requires->css('/mod/techproject/js/dhtmlxGantt/codebase/dhtmlxgantt.css');
}

$timenow = time();

// Get some useful stuff.
if (! $cm = get_coursemodule_from_id('techproject', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}
if (! $project = $DB->get_record('techproject', array('id' => $cm->instance))) {
    print_error('invalidtechprojectid', 'techproject');
}

\mod_techproject\compat::page_init($cm, $project);

$project->cmid = $cm->id;

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
$systemcontext = context_system::instance(0);

$strprojects = get_string('modulenameplural', 'techproject');
$strproject  = get_string('modulename', 'techproject');
$straction = (@$action) ? '-> '.get_string(@$action, 'techproject') : '';

// Get some session toggles if possible.
if (array_key_exists('editmode', $_GET) && !empty($_GET['editmode'])) {
    $_SESSION['editmode'] = $_GET['editmode'];
} else {
    if (!array_key_exists('editmode', $_SESSION)) {
        $_SESSION['editmode'] = 'off';
    }
}
$USER->editmode = $_SESSION['editmode'];

// Check current group and change, for anyone who could.
if (!$groupmode = groups_get_activity_groupmode($cm, $course)) {
    // Groups are being used ?
    $currentgroupid = 0;
} else {
    $changegroup = isset($_GET['group']) ? $_GET['group'] : -1;  // Group change requested?
    if (isguestuser()) { // For guests, use session.
        if ($changegroup >= 0) {
            $_SESSION['guestgroup'] = $changegroup;
        }
        $currentgroupid = 0 + @$_SESSION['guestgroup'];
    } else {
        // For normal users, change current group.
        $currentgroupid = 0 + groups_get_course_group($course, true);
        if (!groups_is_member($currentgroupid , $USER->id) && !is_siteadmin($USER->id)) {
            $USER->editmode = "off";
        }
    }
}

// Display header...

$url = new moodle_url('/mod/techproject/view.php', array('id' => $id));
$PAGE->set_title(format_string($project->name));
$PAGE->set_url($url);
$PAGE->set_heading('');
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(true);
$renderer = $PAGE->get_renderer('mod_techproject');

$pagebuffer = $OUTPUT->header();

$pagebuffer .= '<div align="right">';
$pagebuffer .= techproject_edition_enable_button($cm, $course, $project, $USER->editmode);
$pagebuffer .= '</div>';
// ...and if necessary set default action.
if (has_capability('mod/techproject:gradeproject', $context)) {
    if (empty($action)) {
        // No action specified, either go straight to elements page else the admin page.
        $action = 'teachersview';
    }
} else if (!isguestuser()) {
    // It's a student then.
    if (!$cm->visible) {
        echo $OUTPUT->notification(get_string('activityiscurrentlyhidden'));
    }
    if ($groupmode == SEPARATEGROUPS && !$currentgroupid && !$project->ungroupedsees) {
        $action = 'notingroup';
    }
    if ($timenow < $project->projectstart) {
        $action = 'notavailable';
    } else if (!@$action) {
        $action = 'studentsview';
    }
} else {
    // It's a guest, just watch if possible!
    if ($project->guestsallowed) {
        $action = 'guestview';
    } else {
        $action = 'notavailable';
    }
}

// Pass useful values to javasctript.

$moodlevars = new StdClass;
$moodlevars->view = $view;
$moodlevars->userid = $USER->id;
$moodlevars->cmid = $cm->id;
$moodlevarsjson = addslashes(json_encode($moodlevars));
$pagebuffer .= "<script type=\"text/javascript\">";
$pagebuffer .= "var moodlevars = eval('({$moodlevarsjson})');";
$pagebuffer .= "</script>";


// Display final grade (for students) ************************************.
if ($action == 'displayfinalgrade' ) {
    echo $pagebuffer;
    echo get_string('endofproject', 'techproject');
} else if ($action == 'notavailable') {
    // Assignment not available (for students) ***********************.
    echo $pagebuffer;
    echo $OUTPUT->heading(get_string('notavailable', 'techproject'));

} else if ($action == 'studentsview') {
    // Student's view  ***************************************.

    if ($timenow > $project->projectend) {
        // If project is over, just cannot change anything more.
        $pagebuffer .= $OUTPUT->box('<span class="inconsistency">'.get_string('projectisover', 'techproject').'</span>');
        $USER->editmode = 'off';
    }
    // Print settings and things in a table across the top.
    $pagebuffer .= '<table width="100%" cellpadding="3" cellspacing="0"><tr valign="top">';

    // Allow the student to change groups (for this session), seeing other's work.
    if ($groupmode) {
        // If group are used.
        $groups = groups_get_all_groups($course->id);
        if ($groups) {
            $grouptable = array();
            foreach ($groups as $agroup) {
                // I can see only the groups i belong to.
                if (($groupmode == SEPARATEGROUPS) && !groups_is_member($agroup->id, $USER->id)) {
                    continue;
                }
                // Mark group as mine if i am member.
                if (($groupmode == VISIBLEGROUPS) && groups_is_member($agroup->id, $USER->id)) {
                    $agroup->name .= ' (*)';
                }
                $grouptable[$agroup->id] = $agroup->name;
            }
            $pagebuffer .= '<td>';
            $pagebuffer .= groups_print_activity_menu($cm, $url, true);
            $pagebuffer .= '</td>';
        }
    }
    $pagebuffer .= '</table>';
    /*
     * Ungrouped students can view group 0's project (teacher's) but not change it if ungroupedsees is off.
     * in visible mode, student from other groups cannot edit our material.
     */
    if ($groupmode != SEPARATEGROUPS && (!$currentgroupid || !groups_is_member($currentgroupid, $USER->id))) {
        if (!$project->ungroupedsees) {
            $USER->editmode = 'off';
        }
    }
    include($CFG->dirroot.'/mod/techproject/techproject.php');

} else if ($action == 'guestview') {
    // Guest's view - display projects without editing capabilities  ************.

    $demostr = '';
    if (!$project->guestscanuse || $currentgroupid != 0) {
        // Guest can sometimes edit group 0.
        $USER->editmode = 'off';
    } else if ($project->guestscanuse &&
            !$currentgroupid &&
                    $timenow < $project->projectend) {
        // Guest could have edited but project is closed.
        $demostr = '('.get_string('demomodeclosedproject', 'techproject').') '.$OUTPUT->help_icon('demomode', 'techproject', false);
        $USER->editmode = 'off';
    } else {
        $demostr = '('.get_string('demomode', 'techproject').') '.$OUTPUT->help_icon('demomode', 'techproject', false);
    }
    // Print settings and things in a table across the top.
    $pagebuffer .= '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

    // Allow the guest to change groups (for this session) only for visible groups.
    if ($groupmode == VISIBLEGROUPS) {
        $groups = groups_get_all_groups($course->id);
        if ($groups) {
            $grouptable = array();
            foreach ($groups as $agroup) {
                $grouptable[$agroup->id] = $agroup->name;
            }
            $pagebuffer .= '<td>';
            $pagebuffer .= groups_print_activity_menu($cm, $url, true);
            $pagebuffer .= '</td>';
        }
    }
    $pagebuffer .= '</table>';
    include($CFG->dirroot.'/mod/techproject/techproject.php');

} else if ($action == 'teachersview') {
    // Teacher's view - display admin page  ************.
    /*
     * Check to see if groups are being used in this workshop
     * and if so, set $currentgroupid to reflect the current group
     */
    $currentgroupid = 0 + groups_get_course_group($course, true);
    // Print settings and things in a table across the top.
    $pagebuffer .= '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

    // Allow the teacher to change groups (for this session).
    if ($groupmode) {
        $groups = groups_get_all_groups($course->id);
        if (!empty($groups)) {
            $grouptable = array();
            foreach ($groups as $agroup) {
                $grouptable[$agroup->id] = $agroup->name;
            }
            $pagebuffer .= '<td>';
            $pagebuffer .= groups_print_activity_menu($cm, $url, true);
            $pagebuffer .= '</td>';
        }
    }
    $pagebuffer .= '</tr></table>';
    if (empty($currentgroupid)) {
        $currentgroupid = 0;
    }
    include($CFG->dirroot.'/mod/techproject/techproject.php');

} else if ($action == 'showdescription') {
    // Show description  **********************************************.
    echo $pagebuffer;
    techproject_print_assignement_info($project);
    echo $OUTPUT->box(format_text($project->description, $project->format), 'center', '70%', '', 5, 'generalbox', 'intro');
    echo $OUTPUT->continue_button($_SERVER["HTTP_REFERER"]);

} else if ($action == 'notingroup') {
    // Student is not in a group *******************************************.
    echo $pagebuffer;
    echo $OUTPUT->box(format_text(get_string('notingroup', 'techproject'), 'HTML'), 'center', '70%', '', 5, 'generalbox', 'intro');
    echo $OUTPUT->continue_button($_SERVER["HTTP_REFERER"]);

} else {
    // No man's land *********************************************************************.
    echo $pagebuffer;
    print_error('errorfatalaction', 'techproject', $action);
}

echo $OUTPUT->footer($course);
