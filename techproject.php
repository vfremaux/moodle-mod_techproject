<?php // $Id: techproject.php,v 1.1.1.1 2012-08-01 10:16:17 vf Exp $

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * A common screenswitcher 
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */
    if (!defined('MOODLE_INTERNAL')) die ("You cannot enter directly in this script");

///memorizes current page - typical session switch

    if (!empty($view)) {
    	$_SESSION['currentpage'] = $view;
    } elseif (empty($_SESSION['currentpage'])) {
    	$_SESSION['currentpage'] = 'description';
    }
    $currentpage = $_SESSION['currentpage'];

/// memorizes edit mode - typical session switch

    $editmode = optional_param('editmode', '', PARAM_ALPHA);
    if (!empty($editmode)) {
    	$_SESSION['editmode'] = $editmode;
    } elseif (empty($_SESSION['editmode'])) {
    	$_SESSION['editmode'] = 'off';
    }

/// get general command name
    $work = optional_param('work', '', PARAM_ALPHA);

/// Print groupe name
    /*
    if ($currentGroupId) {
    	$group = $DB->get_record("groups", array("id" => $currentGroupId));
    	echo "<center><b>". get_string('groupname', 'techproject') . $group->name . "</b></center><br/>";
    }
    */

/// Make menu

    $tabrequtitle = get_string('requirements', 'techproject');
    $tabrequlabel = (!has_capability('mod/techproject:changerequs', $context)) ? $tabrequtitle . " <img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/lock.gif\" />" : $tabrequtitle ;
    $tabspectitle = get_string('specifications', 'techproject');
    $tabspeclabel = (!has_capability('mod/techproject:changespecs', $context)) ? "<img src=\"".$OUPTUT->pix_url('p/spec', 'techproject').'" /> ' . $tabspectitle . " <img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/lock.gif\" />" : $tabspectitle ;
    $tabtasktitle = get_string('tasks', 'techproject');
    $tabtasklabel = (!has_capability('mod/techproject:changetasks', $context)) ? "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/task.gif\" /> " . $tabtasktitle . " <img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/lock.gif\" />" : $tabtasktitle ;
    $tabmiletitle = get_string('milestones', 'techproject');
    $tabmilelabel = (!has_capability('mod/techproject:changemiles', $context)) ? $tabmiletitle . " <img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/lock.gif\" />" : $tabmiletitle ;
    $tabdelivtitle = get_string('deliverables', 'techproject');
    $tabdelivlabel = (!has_capability('mod/techproject:changedelivs', $context)) ? $tabdelivtitle . " <img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/lock.gif\" />" : $tabdelivtitle ;
    $tabvalidtitle = get_string('validations', 'techproject');
    $tabvalidlabel = (!has_capability('mod/techproject:validate', $context)) ? $tabvalidtitle . " <img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/lock.gif\" />" : $tabvalidtitle ;
    $tabrequlabel = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/req.gif\" height=\"14\" /> " . $tabrequlabel;
    $tabspeclabel = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/spec.gif\" height=\"14\" /> " . $tabspeclabel;
    $tabtasklabel = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/task.gif\" height=\"14\" /> " . $tabtasklabel;
    $tabdelivlabel = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/deliv.gif\" height=\"14\" /> " . $tabdelivlabel;
    $tabs = array();
    $tabs[0][] = new tabobject('description', "view.php?id={$cm->id}&amp;view=description", get_string('description', 'techproject'));
    if(has_capability('mod/techproject:viewpreproductionentities', $context, $USER->id)){
    	if (@$project->projectusesrequs){
	        $tabs[0][] = new tabobject('requirements', "view.php?id={$cm->id}&amp;view=requirements", $tabrequlabel, $tabrequtitle);
	    }
    	if (@$project->projectusesspecs){
	        $tabs[0][] = new tabobject('specifications', "view.php?id={$cm->id}&amp;view=specifications", $tabspeclabel, $tabspectitle);
	    }
    }
    $tabs[0][] = new tabobject('tasks', "view.php?id={$cm->id}&amp;view=tasks", $tabtasklabel, $tabtasktitle);
    $tabs[0][] = new tabobject('milestones', "view.php?id={$cm->id}&amp;view=milestones", $tabmilelabel, $tabmiletitle);
	if (@$project->projectusesdelivs){
	    $tabs[0][] = new tabobject('deliverables', "view.php?id={$cm->id}&amp;view=deliverables", $tabdelivlabel, $tabdelivtitle);
	}
	if (@$project->projectusesvalidations){
	    $tabs[0][] = new tabobject('validations', "view.php?id={$cm->id}&amp;view=validations", $tabvalidlabel, $tabvalidtitle);
	}
    $tabs[0][] = new tabobject('views', "view.php?id={$cm->id}&amp;view=view_summary", get_string('views', 'techproject'));
    if (preg_match("/view_/", $currentpage)){
        $tabs[1][] = new tabobject('view_summary', "view.php?id={$cm->id}&amp;view=view_summary", get_string('summary', 'techproject'));
        $tabs[1][] = new tabobject('view_byassignee', "view.php?id={$cm->id}&amp;view=view_byassignee", get_string('byassignee', 'techproject'));
        $tabs[1][] = new tabobject('view_bypriority', "view.php?id={$cm->id}&amp;view=view_bypriority", get_string('bypriority', 'techproject'));
        $tabs[1][] = new tabobject('view_byworktype', "view.php?id={$cm->id}&amp;view=view_byworktype", get_string('byworktype', 'techproject'));
        $tabs[1][] = new tabobject('view_detail', "view.php?id={$cm->id}&amp;view=view_detail", get_string('detail', 'techproject'));
        $tabs[1][] = new tabobject('view_todo', "view.php?id={$cm->id}&amp;view=view_todo", get_string('todo', 'techproject'));
        $tabs[1][] = new tabobject('view_gantt', "view.php?id={$cm->id}&amp;view=view_gantt", get_string('gantt', 'techproject'));
    }
    if (has_capability('mod/techproject:viewprojectcontrols', $context)){
        $tabs[0][] = new tabobject('teacher', "view.php?id={$cm->id}&amp;view=teacher_assess", get_string('teacherstools', 'techproject'));
        if (preg_match("/teacher_/", $currentpage)){
            if ($project->grade && has_capability('mod/techproject:gradeproject', $context)){
                 $tabs[1][] = new tabobject('teacher_assess', "view.php?id={$cm->id}&amp;view=teacher_assess", get_string('assessments', 'techproject'));
                 if ($project->teacherusescriteria && has_capability('mod/techproject:managecriteria', $context)){
                    $tabs[1][] = new tabobject('teacher_criteria', "view.php?id={$cm->id}&amp;view=teacher_criteria", get_string('criteria', 'techproject'));
                }
            }
            if (has_capability('mod/techproject:manage', $context)){
                $tabs[1][] = new tabobject('teacher_projectcopy', "view.php?id={$cm->id}&amp;view=teacher_projectcopy", get_string('projectcopy', 'techproject'));
            }
            if ($project->enablecvs && has_capability('mod/techproject:manageremoterepository', $context)) {
                $tabs[1][] = new tabobject('teacher_cvs', "view.php?id={$cm->id}&amp;view=teacher_cvs", get_string('cvscontrol', 'techproject'));
            }
            $tabs[1][] = new tabobject('teacher_load', "view.php?id={$cm->id}&amp;view=teacher_load", get_string('load', 'techproject'));
        }
        if (has_capability('mod/techproject:configure', $context)){
            $tabs[0][] = new tabobject('domains', $CFG->wwwroot."/mod/techproject/view.php?view=domains&id={$id}", get_string('domains', 'techproject'));
            if (preg_match("/domains_?/", $currentpage)){
                if (!preg_match("/domains_heavyness|domains_complexity|domains_severity|domains_priority|domains_worktype|domains_taskstatus|domains_strength|domains_deliv_status/", $view)) $view = 'domains_complexity';
                $tabs[1][] = new tabobject('domains_strength', "view.php?id={$id}&amp;view=domains_strength", get_string('strength', 'techproject'));
                $tabs[1][] = new tabobject('domains_heavyness', "view.php?id={$id}&amp;view=domains_heavyness", get_string('heavyness', 'techproject'));
                $tabs[1][] = new tabobject('domains_complexity', "view.php?id={$id}&amp;view=domains_complexity", get_string('complexity', 'techproject'));
                $tabs[1][] = new tabobject('domains_severity', "view.php?id={$id}&amp;view=domains_severity", get_string('severity', 'techproject'));
                $tabs[1][] = new tabobject('domains_priority', "view.php?id={$id}&amp;view=domains_priority", get_string('priority', 'techproject'));
                $tabs[1][] = new tabobject('domains_worktype', "view.php?id={$id}&amp;view=domains_worktype", get_string('worktype', 'techproject'));
                $tabs[1][] = new tabobject('domains_taskstatus', "view.php?id={$id}&amp;view=domains_taskstatus", get_string('taskstatus', 'techproject'));
                $tabs[1][] = new tabobject('domains_deliv_status', "view.php?id={$id}&amp;view=domains_deliv_status", get_string('deliv_status', 'techproject'));
                $currentpage = $view;
            }
        }
    }

    if (preg_match("/^view_/", $currentpage)) {
        $activated[] = 'views';
    } elseif (preg_match("/^teacher_/", $currentpage)) {
        $activated[] = 'teacher';
    } elseif (preg_match("/^domains_/", $currentpage)) {
        $activated[] = 'domains';
    } else {
        $activated = NULL;
    }
    $pagebuffer .= print_tabs($tabs, $_SESSION['currentpage'], NULL, $activated, true);
    $pagebuffer .= '<br/>';
/// Route to detailed screens

    if ($currentpage == 'description') {
    	$pagebuffer .= techproject_print_assignement_info($project, true);
        include 'description.php';
    } elseif ($currentpage == 'requirements') {
    	include("requirement.php");
    } elseif ($currentpage == 'specifications') {
    	include("specification.php");
    } elseif ($currentpage == 'tasks') {
    	include("task.php");
    } elseif ($currentpage == 'milestones') {
    	include("milestone.php");
    } elseif ($currentpage == 'deliverables') {
    	include("deliverables.php");
    } elseif ($currentpage == 'validation') {
    	include("validation.php");
    } elseif ($currentpage == 'validations') {
    	include("validations.php");
    } elseif (preg_match("/view_/", $currentpage)) {
        if ($currentpage == 'view_summary') {
    	    include("summary.php");
        } elseif ($currentpage == 'view_byassignee') {
    	    include("byassignee.php");
        } elseif ($currentpage == 'view_bypriority') {
    	    include("bypriority.php");
        } elseif ($currentpage == 'view_byworktype') {
    	    include("byworktype.php");
        } elseif ($currentpage == 'view_detail') {
    	    include("detail.php");
        } elseif ($currentpage == 'view_todo') {
    	    include("todo.php");
        } elseif ($currentpage == 'view_gantt') {
    	    include("gantt.php");
        }
    } elseif (preg_match("/teacher_/", $currentpage)) {
        // falldown if no grading enabled.
        if (!$project->grade && ($currentpage == 'teacher_assess' || $currentpage == 'teacher_criteria')) $currentpage = 'teacher_projectcopy';
        if ($currentpage == 'teacher_assess') {
    	    include("assessments.php");
        }
        if ($currentpage == 'teacher_criteria') {
    	    include("criteria.php");
        }
        if ($currentpage == 'teacher_projectcopy') {
    	    include("copy.php");
        }
        if ($currentpage == 'teacher_cvs') {
    	    include("cvs.php");
        }
        if ($currentpage == 'teacher_load') {
    	    include("imports.php");
        }
    } elseif (preg_match("/domains_/", $currentpage)) {
        $action = optional_param('what', '', PARAM_RAW);
        $domain = str_replace('domains_', '', $currentpage);
        include "view_domain.php";
    } else {
    	print_error('errorfatalscreen', 'techproject', $currentpage);
    }
