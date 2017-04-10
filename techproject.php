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
 * A common screenswitcher
 *
 * @package mod-techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die ();

// Memorizes current page - typical session switch.

if (!empty($view)) {
    $_SESSION['currentpage'] = $view;
} else if (empty($_SESSION['currentpage'])) {
    $_SESSION['currentpage'] = 'description';
}
$currentpage = $_SESSION['currentpage'];

// Memorizes edit mode - typical session switch.

$editmode = optional_param('editmode', '', PARAM_ALPHA);
if (!empty($editmode)) {
    $_SESSION['editmode'] = $editmode;
} else if (empty($_SESSION['editmode'])) {
    $_SESSION['editmode'] = 'off';
}

// Get general command name.
    $work = optional_param('work', '', PARAM_ALPHA);

// Make menu.

$pixurltask = $OUTPUT->pix_url('/p/task', 'techproject');
$pixurllock = $OUTPUT->pix_url('/p/lock', 'techproject');
$pixurlspec = $OUTPUT->pix_url('p/spec', 'techproject');
$pixurldeliv = $OUTPUT->pix_url('p/deliv', 'techproject');
$pixurlreq = $OUTPUT->pix_url('p/req', 'techproject');

$tabrequtitle = get_string('requirements', 'techproject');

if (!has_capability('mod/techproject:changerequs', $context)) {
    $tabrequlabel = $tabrequtitle.' <img src="'.$pixurllock.'" />';
} else {
    $tabrequlabel = $tabrequtitle;
}

$tabspectitle = get_string('specifications', 'techproject');
if (!has_capability('mod/techproject:changespecs', $context)) {
    $tabspeclabel = $tabspectitle.' <img src="'.$pixurllock.'" />';
} else {
    $tabspeclabel = $tabspectitle;
}
$tabtasktitle = get_string('tasks', 'techproject');
if (!has_capability('mod/techproject:changetasks', $context)) {
    $tabtasklabel = $tabtasktitle.' <img src="'.$pixurllock.'" />';
} else {
    $tabtasklabel = $tabtasktitle;
}
$tabmiletitle = get_string('milestones', 'techproject');
if (!has_capability('mod/techproject:changemiles', $context)) {
    $tabmilelabel = $tabmiletitle.' <img src="'.$pixurllock.'" />';
} else {
    $tabmilelabel = $tabmiletitle;
}
$tabdelivtitle = get_string('deliverables', 'techproject');
if (!has_capability('mod/techproject:changedelivs', $context)) {
    $tabdelivlabel = $tabdelivtitle.' <img src="'.$pixurllock.'" />';
} else {
    $tabdelivlabel = $tabdelivtitle;
}
$tabvalidtitle = get_string('validations', 'techproject');
if (!has_capability('mod/techproject:validate', $context)) {
    $tabvalidlabel = $tabvalidtitle.' <img src="'.$pixurllock.'" />';
} else {
    $tabvalidlabel = $tabvalidtitle;
}
$tabrequlabel = '<img src="'.$pixurlreq.'" height="14" /> '.$tabrequlabel;
$tabspeclabel = '<img src="'.$pixurlspec.'" height="14" /> '.$tabspeclabel;
$tabtasklabel = '<img src="'.$pixurltask.'" height="14" /> '.$tabtasklabel;
$tabdelivlabel = '<img src="'.$pixurldeliv.'" height="14" /> '.$tabdelivlabel;
$tabs = array();
$taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'description'));
$tabs[0][] = new tabobject('description', $taburl, get_string('description', 'techproject'));

if (has_capability('mod/techproject:viewpreproductionentities', $context, $USER->id)) {
    if (@$project->projectusesrequs) {
        $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'requirements'));
        $tabs[0][] = new tabobject('requirements', $taburl, $tabrequlabel, $tabrequtitle);
    }
    if (@$project->projectusesspecs) {
        $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'specifications'));
        $tabs[0][] = new tabobject('specifications', $taburl, $tabspeclabel, $tabspectitle);
    }
}
$taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'tasks'));
$tabs[0][] = new tabobject('tasks', $taburl, $tabtasklabel, $tabtasktitle);

$taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'milestones'));
$tabs[0][] = new tabobject('milestones', $taburl, $tabmilelabel, $tabmiletitle);

if (@$project->projectusesdelivs) {
    $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'deliverables'));
    $tabs[0][] = new tabobject('deliverables', $taburl, $tabdelivlabel, $tabdelivtitle);
}

if (@$project->projectusesvalidations) {
    $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'validations'));
    $tabs[0][] = new tabobject('validations', $taburl, $tabvalidlabel, $tabvalidtitle);
}

$taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'view_summary'));
$tabs[0][] = new tabobject('views', "view.php?id={$cm->id}&amp;view=view_summary", get_string('views', 'techproject'));

if (preg_match('/view_/', $currentpage)) {
    $tabs[1][] = new tabobject('view_summary', "view.php?id={$cm->id}&amp;view=view_summary", get_string('summary', 'techproject'));
    $tabs[1][] = new tabobject('view_byassignee', "view.php?id={$cm->id}&amp;view=view_byassignee", get_string('byassignee', 'techproject'));
    $tabs[1][] = new tabobject('view_bypriority', "view.php?id={$cm->id}&amp;view=view_bypriority", get_string('bypriority', 'techproject'));
    $tabs[1][] = new tabobject('view_byworktype', "view.php?id={$cm->id}&amp;view=view_byworktype", get_string('byworktype', 'techproject'));
    $tabs[1][] = new tabobject('view_detail', "view.php?id={$cm->id}&amp;view=view_detail", get_string('detail', 'techproject'));
    $tabs[1][] = new tabobject('view_todo', "view.php?id={$cm->id}&amp;view=view_todo", get_string('todo', 'techproject'));
    $tabs[1][] = new tabobject('view_gantt', "view.php?id={$cm->id}&amp;view=view_gantt", get_string('gantt', 'techproject'));
}
if (has_capability('mod/techproject:viewprojectcontrols', $context)) {
    $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'teacher_assess'));
    $tabs[0][] = new tabobject('teacher', $taburl, get_string('teacherstools', 'techproject'));
    if (preg_match("/teacher_/", $currentpage)) {
        if ($project->grade && has_capability('mod/techproject:gradeproject', $context)) {
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'teacher_assess'));
            $tabs[1][] = new tabobject('teacher_assess', "view.php?id={$cm->id}&amp;view=teacher_assess", get_string('assessments', 'techproject'));
            if ($project->teacherusescriteria && has_capability('mod/techproject:managecriteria', $context)) {
                $tabs[1][] = new tabobject('teacher_criteria', "view.php?id={$cm->id}&amp;view=teacher_criteria", get_string('criteria', 'techproject'));
            }
        }
        if (has_capability('mod/techproject:manage', $context)) {
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'teacher_projectcopy'));
            $tabs[1][] = new tabobject('teacher_projectcopy', $taburl, get_string('projectcopy', 'techproject'));
        }
        if ($project->enablecvs && has_capability('mod/techproject:manageremoterepository', $context)) {
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'teacher_cvs'));
            $tabs[1][] = new tabobject('teacher_cvs', "view.php?id={$cm->id}&amp;view=teacher_cvs", get_string('cvscontrol', 'techproject'));
        }
        $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'teacher_load'));
        $tabs[1][] = new tabobject('teacher_load', $taburl, get_string('load', 'techproject'));
    }
    if (has_capability('mod/techproject:configure', $context)) {
        $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'domains'));
        $tabs[0][] = new tabobject('domains', $taburl, get_string('domains', 'techproject'));
        if (preg_match("/domains_?/", $currentpage)) {
            $pattern = "/domains_heavyness|domains_complexity|domains_severity|domains_priority|";
            $pattern .= "domains_worktype|domains_taskstatus|domains_strength|domains_deliv_status/";
            if (!preg_match($pattern, $view)) {
                $view = 'domains_complexity';
            }
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'domains_strength'));
            $tabs[1][] = new tabobject('domains_strength', $taburl, get_string('strength', 'techproject'));
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'domains_heavyness'));
            $tabs[1][] = new tabobject('domains_heavyness', $taburl, get_string('heavyness', 'techproject'));
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'domains_complexity'));
            $tabs[1][] = new tabobject('domains_complexity', $taburl, get_string('complexity', 'techproject'));
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'domains_severity'));
            $tabs[1][] = new tabobject('domains_severity', $taburl, get_string('severity', 'techproject'));
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'domains_priority'));
            $tabs[1][] = new tabobject('domains_priority', $taburl, get_string('priority', 'techproject'));
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'domains_worktype'));
            $tabs[1][] = new tabobject('domains_worktype', $taburl, get_string('worktype', 'techproject'));
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'domains_taskstatus'));
            $tabs[1][] = new tabobject('domains_taskstatus', $taburl, get_string('taskstatus', 'techproject'));
            $taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => 'domains_deliv_status'));
            $tabs[1][] = new tabobject('domains_deliv_status', $taburl, get_string('deliv_status', 'techproject'));
            $currentpage = $view;
        }
    }
}

if (preg_match('/^view_/', $currentpage)) {
    $activated[] = 'views';
} else if (preg_match('/^teacher_/', $currentpage)) {
    $activated[] = 'teacher';
} else if (preg_match('/^domains_/', $currentpage)) {
    $activated[] = 'domains';
} else {
    $activated = null;
}
$pagebuffer .= print_tabs($tabs, $_SESSION['currentpage'], null, $activated, true);
$pagebuffer .= '<br/>';

// Route to detailed screens.

if ($currentpage == 'description') {
    $pagebuffer .= techproject_print_assignement_info($project, true);
    include($CFG->dirroot.'/mod/techproject/description.php');
} else if ($currentpage == 'requirements') {
    include($CFG->dirroot.'/mod/techproject/requirement.php');
} else if ($currentpage == 'specifications') {
    include($CFG->dirroot.'/mod/techproject/specification.php');
} else if ($currentpage == 'tasks') {
    include($CFG->dirroot.'/mod/techproject/task.php');
} else if ($currentpage == 'milestones') {
    include($CFG->dirroot.'/mod/techproject/milestone.php');
} else if ($currentpage == 'deliverables') {
    include($CFG->dirroot.'/mod/techproject/deliverables.php');
} else if ($currentpage == 'validation') {
    include($CFG->dirroot.'/mod/techproject/validation.php');
} else if ($currentpage == 'validations') {
    include($CFG->dirroot.'/mod/techproject/validations.php');
} else if (preg_match('/view_/', $currentpage)) {
    if ($currentpage == 'view_summary') {
        include($CFG->dirroot.'/mod/techproject/summary.php');
    } else if ($currentpage == 'view_byassignee') {
        include($CFG->dirroot.'/mod/techproject/byassignee.php');
    } else if ($currentpage == 'view_bypriority') {
        include($CFG->dirroot.'/mod/techproject/bypriority.php');
    } else if ($currentpage == 'view_byworktype') {
        include($CFG->dirroot.'/mod/techproject/byworktype.php');
    } else if ($currentpage == 'view_detail') {
        include($CFG->dirroot.'/mod/techproject/detail.php');
    } else if ($currentpage == 'view_todo') {
        include($CFG->dirroot.'/mod/techproject/todo.php');
    } else if ($currentpage == 'view_gantt') {
        include($CFG->dirroot.'/mod/techproject/gantt.php');
    }
} else if (preg_match('/teacher_/', $currentpage)) {
    // falldown if no grading enabled.
    if (!$project->grade && ($currentpage == 'teacher_assess' || $currentpage == 'teacher_criteria')) {
        $currentpage = 'teacher_projectcopy';
    }
    if ($currentpage == 'teacher_assess') {
        include($CFG->dirroot.'/mod/techproject/assessments.php');
    }
    if ($currentpage == 'teacher_criteria') {
        include($CFG->dirroot.'/mod/techproject/criteria.php');
    }
    if ($currentpage == 'teacher_projectcopy') {
        include($CFG->dirroot.'/mod/techproject/copy.php');
    }
    if ($currentpage == 'teacher_cvs') {
        include($CFG->dirroot.'/mod/techproject/cvs.php');
    }
    if ($currentpage == 'teacher_load') {
        include($CFG->dirroot.'/mod/techproject/imports.php');
    }
} else if (preg_match('/domains_/', $currentpage)) {
    $action = optional_param('what', '', PARAM_RAW);
    $domain = str_replace('domains_', '', $currentpage);
    include($CFG->dirroot.'/mod/techproject/view_domain.php');
} else {
    print_error('errorfatalscreen', 'techproject', $currentpage);
}
