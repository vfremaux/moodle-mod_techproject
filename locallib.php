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

require_once($CFG->dirroot.'/mod/techproject/treelib.php');
require_once($CFG->dirroot.'/mod/techproject/mailtemplatelib.php');

/**
 * hours for timework division unit
 */
define('HOURS', 1);

/**
 * halfdays for timework division unit
 */
define('HALFDAY', 2);

/**
 * days for timework division unit
 */
define('DAY', 3);

/**
 * checks the availability of the edition button and returns button code
 * @param object $cm the course module instance
 * @param object $course the actual course
 * @param object $project the project instance
 * @param string $editmode the actual value of the editmode session variable
 * @return the button code or an empty string
 */
function techproject_edition_enable_button($cm, $course, $project, $editmode) {
    global $CFG, $USER;

    // Protect agains some unwanted situations.
    $groupmode = 0 + groups_get_activity_groupmode($cm, $course);
    $currentgroupid = (isguestuser()) ? $_SESSION['guestgroup'] : groups_get_activity_group($cm);
    $context = context_course::instance($course->id);
    if (!has_capability('moodle/grade:edit', $context)) {
        if (isguestuser() && !$project->guestscanuse) {
            return '';
        }
        if (!isguestuser() && !groups_is_member($currentgroupid) && ($groupmode != NOGROUPS)) {
            return;
        }
        if (isguestuser() && ($currentgroupid || !$project->guestscanuse)) {
            return '';
        }
    }

    $projecturl = new moodle_url('/mod/techproject/view.php');
    if ($editmode == 'on') {
        $str = '<form method="get" style="display : inline" action="'.$projecturl.'">';
        $str .= '<input type="hidden" name="editmode" value="off" />';
        $str .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
        $str .= '<input type="submit" value="'.get_string('disableedit', 'techproject').'" />';
        $str .= '</form>';
    } else {
        $str = '<form method="get"  style="display : inline" action="'.$projecturl.'">';
        $str .= '<input type="hidden" name="editmode" value="on" />';
        $str .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
        $str .= '<input type="submit" value="'. get_string('enableedit', 'techproject') . '" />';
        $str .= '</form>';
    }
    return $str;
}

/**
 * prints assignement
 * @param project the current project
 */
function techproject_print_assignement_info($project, $return = false) {
    global $CFG, $SESSION, $DB, $OUTPUT;

    $str = '';

    if (! $course = $DB->get_record('course', array('id' => $project->course))) {
        print_error('errorinvalidcourseid');
    }
    if (! $cm = get_coursemodule_from_instance('techproject', $project->id, $course->id)) {
        print_error('errorinvalidcoursemoduleid');
    }

    // Print standard assignment heading.
    $str .= $OUTPUT->heading(format_string($project->name));
    $str .= $OUTPUT->box_start('center');

    // Print phase and date info.
    $string = '<b>'.get_string('currentphase', 'techproject').'</b>: '.techproject_phase($project, '', $course).'<br />';
    $dates = array(
        'projectstart' => $project->projectstart,
        'projectend' => $project->projectend,
        'assessmentstart' => $project->assessmentstart);

    foreach ($dates as $type => $date) {
        if ($date) {
            $strdifference = format_time($date - time());
            if (($date - time()) < 0) {
                $strdifference = "<font color=\"red\">$strdifference</font>";
            }
            $string .= '<b>'.get_string($type, 'techproject').'</b>: '.userdate($date)." ($strdifference)<br />";
        }
    }
    $str .= $string;

    $str .= $OUTPUT->box_end();

    if ($return) {
        return $str;
    }
    echo $str;
}

/**
 * phasing the project module in time. Phasing is a combination of
 * module standard phasing strategy and project defined milestones
 * @param project the current project
 * @param style not used
 * @return a printable representation of the current project phase
 */
function techproject_phase($project, $style = '') {
    global $CFG, $SESSION, $DB, $COURSE;

    $time = time();
    $course = $DB->get_record('course', array('id' => $project->course));
    $currentgroupid = 0 + groups_get_course_group($COURSE);

    // Getting all timed info.
    $sql = "
      SELECT
        m.*,
        deadline as phasedate,
        'milestone' as type
      FROM
        {techproject_milestone} as m
      WHERE
        projectid = ? AND
        groupid = ? AND
        deadlineenable = 1
    ";
    $dated = $DB->get_records_sql($sql, array($project->id, $currentgroupid));
    $adated = new StdClass;
    $adated->id = 'projectstart';
    $adated->phasedate = $project->projectstart;
    $adated->ordering = 0;
    $dated[] = $adated;
    $adated = new StdClass;
    $adated->id = 'projectend';
    $adated->phasedate = $project->projectend;
    $adated->ordering = count($dated) + 1;
    $dated[] = $adated;
    function sortbydate($a, $b) {
        if ($a->phasedate == $b->phasedate) {
            return 0;
        }
        return ($a->phasedate < $b->phasedate) ? -1 : 1;
    }
    usort($dated, "sortbydate");

    $i = 0;
    while ($time > $dated[$i]->phasedate && $i < count($dated) - 1) {
        $i++;
    }
    if ($dated[$i]->id == 'projectstart') {
        return get_string('phasestart', 'techproject');
    } else if ($dated[$i]->id == 'projectend') {
        return get_string('phaseend', 'techproject');
    } else {
        $formated = format_time($dated[$i]->phasedate - $time);
        return "M{$dated[$i]->ordering} : {$dated[$i]->abstract} (<font color=\"green\">".$formated."</font>)";
    }
}

/**
 * prints a specification entry with its tree branch
 * @param project the current project
 * @param group the group of students
 * @fatherid the father node
 * @param numspec the propagated autonumbering prefix
 * @param cmid the module id (for urls)
 */
function techproject_print_specifications($project, $group, $fatherid, $cmid, $propagated = null) {
    global $CFG, $USER, $DB, $OUTPUT;
    static $level = 0;
    static $startuplevelchecked = false;

    techproject_check_startup_level('specification', $fatherid, $level, $startuplevelchecked);

    $sql = "
        SELECT DISTINCT
            s.*,
            c.collapsed
        FROM
            {techproject_specification} s
        LEFT JOIN
            {techproject_collapse} c
        ON
            s.id = c.entryid AND
            c.entity = 'specifications' AND
            c.userid = ?
        WHERE
            s.groupid = ? AND
            s.projectid = ? AND
            s.fatherid = ?
        GROUP BY
            s.id
        ORDER BY
            s.ordering
    ";

    $params = array($USER->id,
                    $group,
                    $project->id,
                    $fatherid);

    if ($specifications = $DB->get_records_sql($sql, $params)) {
        $i = 1;
        foreach ($specifications as $specification) {
            echo '<div class="entitynode nodelevel'.$level.'">';
            $level++;
            $propagatedroot = $propagated;
            if ($propagated == null) {
                $propagatedroot = new StdClass();
            }
            techproject_print_single_specification($specification, $project, $group, $cmid, count($specifications));

            if ($specification->collapsed) {
                $propagatedroot->collapsed = true; // Give signal for lower branch.
                $visibility = 'display: none';
                $expansion = '';
            } else {
                $propagatedroot->collapsed = false; // Give signal for lower branch.
                $visibility = 'display: block';
                $expansion = 'style="visbility:hidden; display:none" ';
            }

            echo '<div id="sub'.$specification->id.'" class="treenode" style="'.$visibility.'" >';
            if (!@$propagated->collapsed) {
                techproject_print_specifications($project, $group, $specification->id, $cmid);
            }
            echo '</div>';
            $level--;
            echo '</div>';
        }
    } else {
        if ($level == 0) {
            echo $OUTPUT->box_start();
            print_string('nospecifications', 'techproject');
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * prints a single specification object
 * @param specification the current specification to print
 * @param project the current project
 * @param group the current group
 * @param cmid the current coursemodule (useful for making urls)
 * @param setSize the size of the set of objects we are printing an item of
 * @param fullsingle true if prints a single isolated element
 */
function techproject_print_single_specification($specification, $project, $group, $cmid, $setsize, $fullsingle = false) {
    global $CFG, $USER, $SESSION, $DB, $OUTPUT, $PAGE;

    $renderer = $PAGE->get_renderer('mod_techproject');
    $context = context_module::instance($cmid);
    $canedit = $USER->editmode == 'on' && has_capability('mod/techproject:changespecs', $context);
    $numspec = implode('.', techproject_tree_get_upper_branch('techproject_specification', $specification->id, true, true));
    if (!$fullsingle) {
        if (techproject_count_subs('techproject_specification', $specification->id) > 0) {
            $jshandler = 'javascript:toggle(\''.$specification->id.'\', \'sub'.$specification->id.'\', true);';
            $pixurl = $OUTPUT->pix_url('/p/switch_minus', 'techproject');
            $pix = '<img name="img'.$specification->id.'" src="'.$pixurl.'" alt="collapse" />';
            $hidesub = '<a href="'.$jshandler.'">'.$pix.'</a>';
        } else {
            $hidesub = '<img src="'.$OUTPUT->pix_url('/p/empty', 'techproject').'" />';
        }
    } else {
       $hidesub = '';
    }

    $speclevel = count(explode('.', $numspec)) - 1;
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $speclevel);

    // Assigned tasks by subspecs count.
    $specList = str_replace(",", "','", techproject_get_subtree_list('techproject_specification', $specification->id));
    $taskcount = techproject_print_entitycount('techproject_specification', 'techproject_task_to_spec', $project->id, $group,
                                               'spec', 'task', $specification->id, $specList);
    $reqcount = techproject_print_entitycount('techproject_specification', 'techproject_spec_to_req', $project->id, $group,
                                              'spec', 'req', $specification->id, $specList);

    // Completion count.
    $sql = "
        SELECT
            SUM(t.done) as completion,
            COUNT(*) as total
        FROM
            {techproject_task_to_spec} AS tts,
            {techproject_task} as t
        WHERE
            tts.taskid = t.id AND
            tts.specid = $specification->id
    ";
    $res = $DB->get_record_sql($sql);
    $completion = ($res->total != 0) ? $renderer->bar_graph_over($res->completion / $res->total, 0) : $renderer->bar_graph_over(-1, 0);
    $checkbox = ($canedit)? "<input type=\"checkbox\" id=\"sel{$specification->id}\" name=\"ids[]\" value=\"{$specification->id}\" /> " : '';
    $priorityoption = techproject_get_option_by_key('priority', $project->id, $specification->priority);
    $severityoption = techproject_get_option_by_key('severity', $project->id, $specification->severity);
    $complexityoption = techproject_get_option_by_key('complexity', $project->id, $specification->complexity);
    $pixurl = $OUTPUT->pix_url("priority_{$priorityoption->truelabel}", 'techproject');
    $prioritysignal = '<img src="'.$pixurl.'" title="'.$priorityoption->label.'" />';
    $pixurl = $OUTPUT->pix_url("severity_{$severityoption->truelabel}", 'techproject');
    $severitysignal = '<img src="'.$pixurl.'" title="'.$severityoption->label.'" />';
    $pixurl = $OUTPUT->pix_url("complexity_{$complexityoption->truelabel}", 'techproject');
    $complexitysignal = '<img src="'.$pixurl.'" title="'.$complexityoption->label.'" />';

    if (!$fullsingle) {
        $hideicon = (!empty($specification->description)) ? 'hide' : 'hide_shadow';
        $jshandler = 'javascript:toggle_show(\''.$numspec.'\', \''.$numspec.'\');';
        $pixurl = $OUTPUT->pix_url('/p/'.$hideicon, 'techproject');
        $pix = '<img name="eye'.$numspec.'" src="'.$pixurl.'" alt="collapse" />';
        $hidedesc = '<a href="'.$jshandler.'">'.$pix.'</a>';
    } else {
        $hidedesc = '';
    }

    $anchor = '<a name="node"'.$specification->id.'"></a>';

    $head = '<table width="100%" class="nodecaption">';
    $head .= '<tr>';
    $head .= '<td class="entity-left">';
    $abstract = "<span class=\"level{$speclevel}\">{$hidesub} {$anchor} S{$numspec} - ".format_string($specification->abstract)."</span>";
    $head .= "<b>{$checkbox}{$indent} {$abstract}</b>";
    $head = '</td>';
    $head = '<td class="entity-right">';
    $head = "{$severitysignal} {$prioritysignal} {$complexitysignal} {$reqcount} {$taskcount} {$completion} {$hidedesc}";
    $head = '</td>';
    $head = '</tr></table>';

    unset($innertable);
    $innertable = new html_table();
    $innertable->class = 'unclassed';
    $innertable->width = '100%';
    $innertable->style = array('parmname', 'parmvalue');
    $innertable->align = array ('left', 'left');
    $priority = "<span class=\"scale{$priorityoption->id}\" title=\"{$priorityoption->label}\">{$priorityoption->label}</span>";
    $innertable->data[] = array(get_string('priority', 'techproject'), $priority);
    $severity = "<span class=\"scale{$severityoption->id}\" title=\"{$severityoption->label}\">{$severityoption->label}</span>";
    $innertable->data[] = array(get_string('severity', 'techproject'), $severity);
    $parms = techproject_print_project_table($innertable, true);
    $description = file_rewrite_pluginfile_urls($specification->description, 'pluginfile.php', $context->id, 'mod_techproject',
                                                'specificationdescription', $specification->id);

    if (!$fullsingle || $fullsingle === 'HEAD') {
        $initialdisplay = 'none';
        $description = close_unclosed_tags(shorten_text(format_text($description, $specification->descriptionformat), 800));
    } else {
        $initialdisplay = 'block';
        $description = format_text($description, $specification->descriptionformat);
    }
    $desc = "<div id='{$numspec}' class='entitycontent' style='display: {$initialdisplay};'>{$parms}".$description;
    if (!$fullsingle) {
        $params = array('id' => $cmid, 'view' => 'view_detail', 'objectId' => $specification->id, 'objectClass' => 'specification');
        $detailurl = new moodle_url('/mod/techproject/view.php', $params);
        $desc .= '<br/><a href="'.$detailurl.'" >'.get_string('seedetail', 'techproject').'</a></p>';
    }
    $desc .= '</div>';

    $table = new html_table();
    $table->class = 'entity';
    $table->head  = array ($head);
    $table->cellpadding = 1;
    $table->cellspacing = 1;
    $table->width = '100%';
    $table->align = array ('left');
    $table->data[] = array($desc);
    $table->rowclass[] = 'description';

    if ($canedit) {
        $link = array();
        $params = array('id' => $cmid, 'work' => 'add', 'fatherid' => $specification->id, 'view' => 'specifications');
        $addsuburl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/p/newnode', 'techproject');
        $pix = '<img src="'.$pixurl.'" alt="'.get_string('addsubspec', 'techproject').'" />';
        $link[] = '<a href="'.$addsuburl.'">'.$pix.'</a>';

        $params = array('id' => $cmid, 'work' => 'update', 'specid' => $specification->id, 'view' => 'specifications');
        $updateurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/t/edit');
        $pix = '<img src="'.$pixurl.'" title="'.get_string('update').'" />';
        $link[] = '<a href="'.$updateurl.'">'.$pix.'</a>';

        $params = array('id' => $cmid, 'work' => 'dodelete', 'specid' => $specification->id, 'view' => 'specifications');
        $deleteurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/t/delete');
        $pix = '<img src="'.$pixurl.'" title="'.get_string('delete').'" />';
        $link[] = '<a href="'.$deleteurl.'">'.$pix.'</a>';

        $params = array('id' => $cmid, 'work' => 'domarkastemplate', 'specid' => $specification->id, 'view' => 'specifications');
        $templateurl = new moodle_url('/mod/techproject/view.php', $params);
        if ($specification->id == @$SESSION->techproject->spectemplateid) {
            $pixurl = $OUTPUT->pix_url('p/activetemplate', 'techproject');
        } else {
            $pixurl = $OUTPUT->pix_url('p/marktemplate', 'techproject');
        }
        $pix = '<img src="'.$pixurl.'" title="'.get_string('markastemplate', 'techproject').'" />';
        $link[] = '<a href="'.$templateurl.'#node'.$specification->id.'">'.$pix.'</a>';

        techproject_add_entity_tree_controls($cmid, 'specification', 'spec', $specification, $setsize, $link);
        $table->data[] = array($indent.implode (' ', $link));
        $table->rowclass[] = 'controls';
    }

    $table->style = 'generaltable';

    techproject_print_project_table($table);
    unset($table);
}

/**
 * prints a requirement entry with its tree branch
 * @param project the current project
 * @param group the group of students
 * @fatherid the father node
 * @param numrequ the propagated autonumbering prefix
 * @param cmid the module id (for urls)
 * @uses $CFG
 * @uses $USER
 */
function techproject_print_requirements($project, $group, $fatherid, $cmid, $propagated = null) {
    global $CFG, $USER, $DB, $OUTPUT;
    static $level = 0;
    static $startuplevelchecked = false;

    techproject_check_startup_level('requirement', $fatherid, $level, $startuplevelchecked);

    $sql = "
        SELECT DISTINCT
            r.*,
            COUNT(str.specid) as specifs,
            c.collapsed
        FROM
            {techproject_requirement} r
        LEFT JOIN
            {techproject_spec_to_req} str
        ON
            r.id = str.reqid
        LEFT JOIN
            {techproject_collapse} c
        ON
            r.id = c.entryid AND
            c.entity = 'requirements' AND
            c.userid = $USER->id
        WHERE
            r.groupid = $group AND
            r.projectid = {$project->id} AND
            fatherid = $fatherid
        GROUP BY
            r.id
        ORDER BY
            ordering
    ";
    if ($requirements = $DB->get_records_sql($sql)) {
        $i = 1;
        foreach ($requirements as $requirement) {
            echo '<div class="entitynode nodelevel'.$level.'">';
            $level++;
            $propagatedroot = $propagated;
            if ($propagated == null) {
                $propagatedroot = new StdClass();
            }
            techproject_print_single_requirement($requirement, $project, $group, $cmid, count($requirements));

            if ($requirement->collapsed) {
                // Give signal for lower branch.
                $propagatedroot->collapsed = true;
                $visibility = 'display: none';
            } else {
                // Give signal for lower branch.
                $propagatedroot->collapsed = false;
                $visibility = 'display: block';
            }
            echo '<div id="sub'.$requirement->id.'" class="treenode" style="'.$visibility.'" >';
            if (!@$propagated->collapsed) {
                techproject_print_requirements($project, $group, $requirement->id, $cmid, $propagatedroot);
            }
            echo '</div>';
            $level--;
            echo '</div>';
        }
    } else {
        if ($level == 0) {
            echo $OUTPUT->box_start();
            print_string('norequirements', 'techproject');
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * prints a single requirement object
 * @param requirement the current requirement to print
 * @param project the current project
 * @param group the current group
 * @param cmid the current coursemodule (useful for making urls)
 * @param setSize the size of the set of objects we are printing an item of
 * @param fullsingle true if prints a single isolated element
 */
function techproject_print_single_requirement($requirement, $project, $group, $cmid, $setsize, $fullsingle = false) {
    global $CFG, $USER, $DB, $OUTPUT, $PAGE;

    $renderer = $PAGE->get_renderer('mod_techproject');
    $context = context_module::instance($cmid);
    $canedit = $USER->editmode == 'on' && has_capability('mod/techproject:changerequs', $context);
    $numrequ = implode('.', techproject_tree_get_upper_branch('techproject_requirement', $requirement->id, true, true));
    if (!$fullsingle) {
        if (techproject_count_subs('techproject_requirement', $requirement->id) > 0) {
            $jshandler = 'javascript:toggle(\''.$requirement->id.'\', \'sub'.$requirement->id.'\', 1);';
            $pixurl = $OUTPUT->pix_url('/p/switch_minus', 'techproject');
            $pix = '<img name="img'.$requirement->id.'" src="'.$pixurl.'" alt="collapse" />';
            $hidesub = '<a href="'.$jshandler.'">'.$pix.'</a>';
        } else {
            $hidesub = '<img src="'.$OUTPUT->pix_url('/p/empty', 'techproject').'" />';
        }
    } else {
       $hidesub = '';
    }
    $sql = "
       SELECT
          SUM(t.done) as completion,
          count(*) as total
       FROM
          {techproject_requirement} as r,
          {techproject_spec_to_req} as str,
          {techproject_task_to_spec} as tts,
          {techproject_task} as t
       WHERE
          r.id = $requirement->id AND
          r.id = str.reqid AND
          str.specid = tts.specid AND
          tts.taskid = t.id AND
          r.projectid = {$project->id} AND
          r.groupid = {$group}
    ";
    $res = $DB->get_record_sql($sql);
    $completion = ($res->total != 0) ? $renderer->bar_graph_over($res->completion / $res->total, 0) : $renderer->bar_graph_over(-1, 0);

    $requlevel = count(explode('.', $numrequ)) - 1;
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $requlevel);

    // assigned by subrequs count
    $reqList = str_replace(",", "','", techproject_get_subtree_list('techproject_requirement', $requirement->id));
    $speccount = techproject_print_entitycount('techproject_requirement', 'techproject_spec_to_req', $project->id, $group,
                                               'req', 'spec', $requirement->id, $reqList);
    $checkbox = ($canedit) ? '<input type="checkbox" id="sel'.$requirement->id.'" name="ids[]" value="'.$requirement->id.'" /> ' : '';

    $strengthoption = techproject_get_option_by_key('strength', $project->id, $requirement->strength);
    $strengthsignal = '';
    if (!empty($requirement->strength)) {
        $strengthsignal = "<span class=\"scale_{$strengthoption->truelabel}\" title=\"{$strengthoption->label}\">s</span>";
    }

    $heavynessoption = techproject_get_option_by_key('heaviness', $project->id, $requirement->heavyness);
    $heavynessclass = '';
    if (!empty($requirement->heavyness)) {
        $heavynessclass = "scale_{$heavynessoption->truelabel}";
    }

    if (!$fullsingle) {
        $hideicon = (!empty($requirement->description)) ? 'hide' : 'hide_shadow' ;
        $jshandler = 'javascript:toggle_show(\''.$numrequ.'\',\''.$numrequ.'\');';
        $pixurl = $OUTPUT->pix_url("p/{$hideicon}", 'techproject');
        $pixurl = '<img name="eye'.$numrequ.'" src="'.$pixurl.'" alt="collapse" />';
        $hidedesc = '<a href="'.$jshandler.'">'.$pixurl.'</a>';
    } else {
        $hidedesc = '';
    }
    $head = '<table width="100%" class="nodecaption '.$heavynessclass.'">';
    $head .= '<tr>';
    $head .= '<td class="entity-left">';
    $head .= '<span class="level'.$requlevel.'">';
    $head .= $checkbox.$indent.$hidesub;
    $head .= ' <a name="node'.$requirement->id.'"></a>';
    $head .= 'R'.$numrequ.' - '.format_string($requirement->abstract);
    $head .= '</span>';
    $head .= '</td>';
    $head .= '<td class="entity-right">';
    $head .= $strengthsignal.' '.$speccount.' '.$completion.' '.$hidedesc;
    $head .= '</td></tr></table>';

    unset($innertable);
    $innertable = new html_table();
    $innertable->class = 'unclassed';
    $innertable->width = '100%';
    $innertable->style = array('parmname', 'parmvalue');
    $innertable->align = array ('left', 'left');
    $strength = "<span class=\"scale{$strengthoption->id}\" title=\"{$strengthoption->label}\">{$strengthoption->label}</span>";
    $innertable->data[] = array(get_string('strength', 'techproject'), $strength);
    $parms = techproject_print_project_table($innertable, true);
    $description = file_rewrite_pluginfile_urls($requirement->description, 'pluginfile.php', $context->id, 'mod_techproject',
                                                'requirementdescription', $requirement->id);

    if (!$fullsingle || $fullsingle === 'HEAD') {
        $initialdisplay = 'none';
        $description = close_unclosed_tags(shorten_text(format_text($description, $requirement->descriptionformat), 800));
    } else {
        $initialdisplay = 'block';
        $description = format_text($description, $requirement->descriptionformat);
    }
    $desc = "<div id='{$numrequ}' class='entitycontent' style='display: {$initialdisplay};'>{$parms}".$description;
    if (!$fullsingle) {
        $params = array('id' => $cmid, 'view' => 'view_detail', 'objectId' => $requirement->id, 'objectClass' => 'requirement');
        $detailurl = new moodle_url('/mod/techproject/view.php', $params);
        $desc .= '<br/><a href="'.$detailurl.'" >'.get_string('seedetail', 'techproject').'</a>';
    }
    $desc .='</div>';

    $table = new html_table();
    $table->class = 'entity';
    $table->cellpadding = 1;
    $table->cellspacing = 1;
    $table->head  = array ($head);
    $table->width  = '100%';
    $table->align = array("left");
    $table->data[] = array($desc);
    $table->rowclass[] = 'description';

    if ($canedit) {
        $link = array();

        $params = array('id' => $cmid, 'work' => 'add', 'fatherid' => $requirement->id, 'view' => 'requirements');
        $addsuburl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/p/newnode', 'techproject');
        $pix = '<img src="'.$pixurl.'" alt="'.get_string('addsubrequ', 'techproject').'" />';
        $link[] = '<a href="'.$addsuburl.'">'.$pix.'</a>';

        $params = array('id' => $cmid, 'work' => 'update', 'requid' => $requirement->id, 'view' => 'requirements');
        $updateurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/t/edit');
        $pix = '<img src="'.$pixurl.'" alt="'.get_string('update').'" />';
        $link[] = '<a href="'.$updateurl.'">'.$pix.'</a>';

        $params = array('id' => $cmid, 'work' => 'dodelete', 'requid' => $requirement->id, 'view' => 'requirements');
        $deleteurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/t/delete');
        $pix = '<img src="'.$pixurl.'" alt="'.get_string('delete').'" />';
        $link[] = '<a href="'.$deleteurl.'">'.$pix.'</a>';

        techproject_add_entity_tree_controls($cmid, 'requirement', 'requ', $requirement, $setsize, $link);
        $table->data[] = array($indent . implode(' ', $link));
        $table->rowclass[] = 'controls';
    }

    $table->style = "generaltable";
    techproject_print_project_table($table);
    unset($table);
}

/**
 * prints a task entry with its tree branch
 * @param project the current project
 * @param group the group of students
 * @fatherid the father node
 * @param numtask the propagated autonumbering prefix
 * @param cmid the module id (for urls)
 */
function techproject_print_tasks($project, $group, $fatherid, $cmid, $propagated = null) {
    global $CFG, $USER, $DB, $OUTPUT;
    static $level = 0;
    static $startuplevelchecked = false;

    techproject_check_startup_level('task', $fatherid, $level, $startuplevelchecked);

    // get current level task nodes
    $sql = "
        SELECT DISTINCT
            t.*,
            m.abstract as milestoneabstract,
            c.collapsed
        FROM 
            {techproject_task} t
        LEFT JOIN
            {techproject_milestone} m
        ON
            t.milestoneid = m.id
        LEFT JOIN
            {techproject_collapse} c
        ON
            t.id = c.entryid AND
            c.entity = 'tasks' AND
            c.userid = $USER->id
        WHERE 
            t.groupid = {$group} AND
            t.projectid = {$project->id} AND
            t.fatherid = {$fatherid}
        ORDER BY
            t.ordering
    ";
    if ($tasks = $DB->get_records_sql($sql)) {
        foreach ($tasks as $task) {
            echo '<div class="entitynode nodelevel'.$level.'">';
            $level++;
            $propagatedroot = $propagated;
            if ($propagated == null || !isset($propagated->milestoneid) && $task->milestoneid) {
                $propagatedroot = new StdClass();
                $propagatedroot->milestoneid = $task->milestoneid;
                $propagatedroot->milestoneabstract = $task->milestoneabstract;
            } else {
               $task->milestoneid = $propagated->milestoneid;
               $task->milestoneabstract = $propagated->milestoneabstract;
               $task->milestoneforced = 1;
            }
            if (!@$propagated->collapsed) {
                techproject_print_single_task($task, $project, $group, $cmid, count($tasks), false, '');
            }

            if ($task->collapsed) $propagatedroot->collapsed = true; // Give signal for lower branch.
            $visibility = ($task->collapsed) ? 'display: none' : 'display: block';
            echo '<div id="sub'.$task->id.'" class="treenode" style="'.$visibility.'" >';
            techproject_print_tasks($project, $group, $task->id, $cmid, $propagatedroot);
            echo '</div>';
            $level--;
            echo '</div>';
        }
    } else {
        if ($level == 0) {
            echo $OUTPUT->box_start();
            print_string('notasks', 'techproject');
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * prints a single task object
 * @param task the current task to print
 * @param project the current project
 * @param group the current group
 * @param cmid the current coursemodule (useful for making urls)
 * @param setSize the size of the set of objects we are printing an item of
 * @param fullsingle true if prints a single isolated element
 * @param style some command input to change things in output.
 *
 * style uses values : SHORT_WITHOUT_ASSIGNEE, SHORT_WITHOUT_TYPE, SHORT_WITH_ASSIGNEE_ORDERED
 *
 * // TODO clean up $fullsingle and $style commands
 */
function techproject_print_single_task($task, $project, $group, $cmid, $setsize, $fullsingle = false, $style = '') {
    global $CFG, $USER, $SESSION, $DB, $OUTPUT, $PAGE;

    $renderer = $PAGE->get_renderer('mod_techproject');
    $timeunits = array(get_string('unset', 'techproject'),
                       get_string('hours', 'techproject'),
                       get_string('halfdays', 'techproject'),
                       get_string('days', 'techproject'));
    $context = context_module::instance($cmid);
    $canedit = ($USER->editmode == 'on') &&
            has_capability('mod/techproject:changetasks', $context) &&
                    !preg_match("/NOEDIT/", $style);
    if (!has_capability('mod/techproject:changenotownedtasks', $context)) {
        if ($task->owner != $USER->id) {
            $canedit = false;
        }
    }
    $hasmasters = $DB->count_records('techproject_task_dependency', array('slave' => $task->id));
    $hasslaves = $DB->count_records('techproject_task_dependency', array('master' => $task->id));
    $pixurl = $OUTPUT->pix_url('p/task_alone', 'techproject');
    $taskdependency = '<img src="'.$pixurl.'" title="'.get_string('taskalone', 'techproject').'" />';
    if ($hasslaves && $hasmasters) {
        $pixurl = $OUTPUT->pix_url('/p/task_middle', 'techproject');
        $taskdependency = '<img src="'.$pixurl.'" title="'.get_string('taskmiddle', 'techproject').'" />';
    } else if ($hasmasters) {
        $pixurl = $OUTPUT->pix_url('/p/task_end', 'techproject');
        $taskdependency = '<img src="'.$pixurl.'" title="'.get_string('taskend', 'techproject').'" />';
    } else if ($hasslaves) {
        $pixurl = $OUTPUT->pix_url('/p/task_start', 'techproject');
        $taskdependency = '<img src="'.$pixurl.'" title="'.get_string('taskstart', 'techproject').'" />';
    }

    $numtask = implode('.', techproject_tree_get_upper_branch('techproject_task', $task->id, true, true));
    if (!$fullsingle) {
        if (techproject_count_subs('techproject_task', $task->id) > 0) {
            $jshandler = 'javascript:toggle(\''.$task->id.'\',\'sub'.$task->id.'\', 1);';
            $pixurl = $OUTPUT->pix_url('/p/switch_minus', 'techproject');
            $pix = '<img name="img'.$task->id.'" src="'.$pixurl.'" alt="collapse" />';
            $hidesub = '<a href="'.$jshandler.'">'.$pix.'</a>';
        } else {
            $hidesub = '<img src="'.$OUTPUT->pix_url('/p/empty', 'techproject').'" />';
        }
    } else {
       $hidesub = '';
    }

    $tasklevel = count(explode('.', $numtask)) - 1;
    $indent = (!$fullsingle) ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $tasklevel) : '';

    $taskcount = techproject_print_entitycount('techproject_task', 'techproject_task_to_spec', $project->id, $group,
                                               'task', 'spec', $task->id);
    $delivcount = techproject_print_entitycount('techproject_task', 'techproject_task_to_deliv', $project->id, $group,
                                                'task', 'deliv', $task->id);
    $checkbox = ($canedit) ? '<input type="checkbox" id="sel'.$task->id.'" name="ids[]" value="'.$task->id.'" /> ' : '';

    $over = ($task->planned && $task->planned < $task->used) ? floor((($task->used - $task->planned) / $task->planned) * 60) : 0;
    $barwidth = ($task->planned) ? 60 : 0; // Unplanned tasks should not provide progress bar.
    $completion = $renderer->bar_graph_over($task->done, $over, $barwidth, 5);
    $milestonepix = (isset($task->milestoneforced)) ? 'milestoneforced' : 'milestone';
    $pixurl = $OUTPUT->pix_url('/p/'.$milestonepix, 'techproject');
    $milestone = ($task->milestoneid) ? '<img src="'.$pixurl.'" title="'.format_string(@$task->milestoneabstract).'" />' : '';
    if (!$fullsingle || $fullsingle === 'HEAD') {
        $hideicon = (!empty($task->description)) ? 'hide' : 'hide_shadow';
        $jshandler = 'javascript:toggle_show(\''.$numtask.'\', \''.$numtask.'\');';
        $pixurl = $OUTPUT->pix_url("/p/{$hideicon}", 'techproject');
        $pix = '<img name="eye'.$numtask.'" src="'.$pixurl.'" alt="collapse" />';
        $hidetask = '<a href="'.$jshandler.'">'.$pix.'</a>';
    } else {
        $hidetask = '';
    }
    $assigneestr = '';
    $headdetaillink = '';
    $timeduestr = '';
    if (!preg_match('/SHORT_WITHOUT_ASSIGNEE/', $style) && $task->assignee) {
        $assignee = $DB->get_record('user', array('id' => $task->assignee));
        $assigneestr = '<span class="taskassignee">('.$assignee->lastname.' '.$assignee->firstname.')</span>';
        if ($task->taskendenable) {
            $tasklate = ($task->taskend < time()) ? 'toolate' : 'futuretime';
            $timeduestr = '<span class="'.$tasklate.' timedue">['.userdate($task->taskend).']</span>';
        } 
    } else {
        $params = array('id' => $cmid, 'view' => 'view_detail', 'objectId' => $task->id, 'objectClass' => 'task');
        $detailurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('p/hide', 'techproject');
        $pix = '<img src="'.$pixurl.'" title="'.get_string('detail', 'techproject').'" />';
        $headdetaillink = '<a href="'.$detailurl.'" >'.$pix.'</a>';
    }

    $worktypeicon = '';
    $worktypeoption = techproject_get_option_by_key('worktype', $project->id, $task->worktype);
    if ($style == '' || !$style === 'SHORT_WITHOUT_TYPE') {
        if (file_exists($CFG->dirroot.'/mod/techproject/pix/p/'.core_text::strtolower(@$worktypeoption->code).'.gif')) {
            $pixurl = $OUTPUT->pix_url('/p/'.strtolower($worktypeoption->code), 'techproject');
            $worktypeicon = '<img src="'.$pixurl.'" title="'.$worktypeoption->label.'" height="24" align="middle" />';
        }
    }
    $ordercell = '';
    if (preg_match('/SHORT_WITH_ASSIGNEE_ORDERED/', $style)) {
        static $order;
        if (!isset($order)) {
            $order = 1;
        } else {
            $order++;
        }
        $prioritydesc = techproject_get_option_by_key('priority', $project->id, $task->priority);
        $ordercell = "<td class=\"ordercell_{$prioritydesc->label}\" width=\"3%\" align=\"center\" title=\"{$prioritydesc->description}\">{$order}</td>";
    }

    $head = '<table width="100%" class="nodecaption">';
    $head .= '<tr>';
    $head .= $ordercell;
    $head .= '<td class="entity-left">';
    $head .= "&nbsp;{$worktypeicon} <span class=\"level{$tasklevel} {$style}\">";
    $head .= $checkbox.$indent.$hidesub;
    $head .= '<a name="node'.$task->id.'"></a>';
    $head .= "T{$numtask} - ".format_string($task->abstract)." {$headdetaillink} {$assigneestr} {$timeduestr}</span>";
    $head .= '</td>';
    $head .= '<td class="entity-right">';
    $head .= $taskcount.' '.$delivcount.' '.$completion.' '.$milestone.' '.$taskdependency.' '.$hidetask;
    $head .= '</td>';
    $head .= '</tr>';
    $head .= '</table>';

    $statusoption = techproject_get_option_by_key('taskstatus', $project->id, $task->status);

    // Affichage de la task.
    $innertable = new html_table();
    $innertable->width = '100%';
    $innertable->style = array('parmname', 'parmvalue');
    $innertable->align = array ('left', 'left');
    $innertable->data[] = array(get_string('worktype', 'techproject'), $worktypeoption->label);
    $innertable->data[] = array(get_string('status', 'techproject'), $statusoption->label);
    $innertable->data[] = array(get_string('costrate', 'techproject'), $task->costrate);
    $planned = $task->planned . ' ' . $timeunits[$project->timeunit];
    if (@$project->useriskcorrection) {
        $planned .= '<span class="riskshifted">('.($task->planned * (1 + ($task->risk / 100))).' '.$timeunits[$project->timeunit].')</span>';
    }
    $innertable->data[] = array(get_string('costplanned', 'techproject'), $planned);
    $quote = $task->quoted.' '.$project->costunit;
    if ($project->useriskcorrection) {
        $quote .= '<span class="riskshifted">('.($task->quoted * (1 + ($task->risk / 100))).' '.$project->costunit.')</span>';
    }
    $innertable->data[] = array(get_string('quoted', 'techproject'), $quote);
    $innertable->data[] = array(get_string('risk', 'techproject'), $task->risk);
    $innertable->data[] = array(get_string('done', 'techproject'), $task->done.'%');
    $innertable->data[] = array(get_string('used', 'techproject'), $task->used.' '.$timeunits[$project->timeunit]);
    $innertable->data[] = array(get_string('spent', 'techproject'), $task->spent.' '.$project->costunit);
    $innertable->data[] = array(get_string('mastertasks', 'techproject'), $hasmasters);
    $innertable->data[] = array(get_string('slavetasks', 'techproject'), $hasslaves);
    $parms = techproject_print_project_table($innertable, true);
    $description = file_rewrite_pluginfile_urls($task->description, 'pluginfile.php', $context->id, 'mod_techproject',
                                                'taskdescription', $task->id);

    if (!$fullsingle || $fullsingle === 'HEAD') {
        $initialdisplay = 'none';
        $description = close_unclosed_tags(shorten_text(format_text($description, $task->descriptionformat), 800));
    } else {
        $initialdisplay = 'block';
        $description = format_text($description, $task->descriptionformat);
    }
    $desc = "<div id='{$numtask}' class='entitycontent' style='display: {$initialdisplay};'>{$parms}".$description;
    if (!$fullsingle || $fullsingle === 'SHORT' || $fullsingle === 'SHORT_WITHOUT_TYPE') {
        $params = array('id' => $cmid, 'view' => 'view_detail', 'objectId' => $task->id, 'objectClass' => 'task');
        $detailurl = new moodle_url('/mod/techproject/view.php', $params);
        $desc .= '<br/><a href="'.$detailurl.'" >'.get_string('seedetail', 'techproject').'</a></p>';
    }
    $desc .= "</div>";

    $table = new html_table();
    $table->class = 'entity';
    $table->head  = array ($head);
    $table->cellspacing = 1;
    $table->cellpadding = 1;
    $table->width = '100%';
    $table->align = array('left');
    $table->data[] = array($desc);
    $table->rowclass[] = 'description';

    if ($canedit) {
        $link = array();

        $params = array('id' => $cmid, 'work' => 'add', 'fatherid' => $task->id, 'view' => 'tasks');
        $editurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/p/newnode', 'techproject');
        $pix = '<img src="'.$pixurl.'" title="'.get_string('addsubtask', 'techproject').'" />';
        $link[] = '<a href="'.$editurl.'">'.$pix.'</a>';

        $params = array('id' => $cmid, 'work' => 'update', 'taskid' => $task->id, 'view' => 'tasks');
        $updateurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/t/edit');
        $pix = '<img src="'.$pixurl.'" title="'.get_string('updatetask', 'techproject').'" />';
        $link[] = '<a href="'.$updateurl.'">'.$pix.'</a>';

        if ($task->id == @$SESSION->techproject->tasktemplateid) {
            $pixurl = $OUTPUT->pix_url('/p/activetemplate', 'techproject');
        } else {
            $pixurl = $OUTPUT->pix_url('/p/marktemplate', 'techproject');
        }
        $params = array('id' => $cmid, 'work' => 'domarkastemplate', 'taskid' => $task->id, 'view' => 'tasks');
        $markurl = new moodle_url('/mod/techproject/view.php', $params);
        $pix = '<img src="'.$pixurl.'" title="'.get_string('markastemplate', 'techproject').'" />';
        $link[] = '<a href="'.$markurl.'#node'.$task->id.'">'.$pix.'</a>';
 
        $params = array('id' => $cmid, 'work' => 'dodelete', 'taskid' => $task->id, 'view' => 'tasks');
        $deleteurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/t/delete');
        $pix = '<img src="'.$pixurl.'" title="'.get_string('deletetask', 'techproject').'" />';
        $link[] = '<a href="'.$deleteurl.'">'.$pix.'</a>';

        techproject_add_entity_tree_controls($cmid, 'task', 'task', $task, $setsize, $link);
        $table->data[] = array($indent . implode(' ', $link));
        $table->rowclass[] = 'controls';
    }

    $table->style = "generaltable";
    techproject_print_project_table($table);
    unset($table);
}

/**
 * prints a milestone entry
 * @param project the current project
 * @param group the group of students
 * @param numstage the propagated autonumbering prefix
 * @param cmid the module id (for urls)
 */
function techproject_print_milestones($project, $group, $numstage, $cmid) {
    global $CFG, $USER, $DB, $OUTPUT, $PAGE;

    $renderer = $PAGE->get_renderer('mod_techproject');
    $timeunits = array(get_string('unset', 'techproject'),
                       get_string('hours', 'techproject'),
                       get_string('halfdays', 'techproject'),
                       get_string('days', 'techproject'));
    $context = context_module::instance($cmid);
    $canedit = $USER->editmode == 'on' && has_capability('mod/techproject:changemiles', $context);

    $select = "projectid = ? AND groupid = ? ";
    if ($milestones = $DB->get_records_select('techproject_milestone', $select, array($project->id, $group),'ordering ASC' )) {
        $i = 1;
        foreach ($milestones as $milestone) {
            echo '<div class="entitynode nodelevel0">';
            // Counting effective deliverables.
            $deliverables = $DB->get_records('techproject_deliverable', array('milestoneid' => $milestone->id), '', 'id');
            $delivcount = 0;
            if ($deliverables) {
                foreach ($deliverables as $adeliverable) {
                    $delivcount += techproject_count_leaves('techproject_deliverable', $adeliverable->id);
                }
            }

            // counting effective tasks
            $tasks = $DB->get_records('techproject_task', array('milestoneid' => $milestone->id), '', 'id');
            $taskcount = 0;
            if ($tasks) {
                foreach ($tasks as $atask) {
                    $taskcount += techproject_count_leaves('techproject_task', $atask->id);
                }
            }

            $sql = "
                SELECT
                   count(*) as count,
                   SUM(done) as done,
                   SUM(planned) as planned,
                   SUM(quoted) as quoted,
                   SUM(used) as used,
                   SUM(spent) as spent
                FROM
                   {techproject_task}
                WHERE
                   milestoneid = $milestone->id
            ";
            $toptasks = $DB->get_record_sql($sql);
            $milestone->done = ($toptasks->count != 0) ? round($toptasks->done / $toptasks->count, 1) : 0;
            $over = ($toptasks->planned && $toptasks->planned < $toptasks->used) ? floor((($toptasks->used - $toptasks->planned) / $toptasks->planned) * 60) : 0;
            $completion = $renderer->bar_graph_over($milestone->done, $over, 60, 5);

            // Printing milestone.
            $passed = ($milestone->deadline < usertime(time())) ? 'passedtime' : 'futuretime';
            $milestonedeadline = ($milestone->deadlineenable) ? "(<span class='{$passed}'>".userdate($milestone->deadline).'</span>)': '';
            $checkbox = ($canedit) ? "<input type=\"checkbox\" name=\"ids[]\" value=\"{$milestone->id}\" />" : '';
            $taskcount = "<img src=\"".$OUTPUT->pix_url('/p/task', 'techproject')."\" />[".$taskcount."]";
            $deliverablecount = " <img src=\"".$OUTPUT->pix_url('/p/deliv', 'techproject')."\" />[".$delivcount."]";

            $hide = '';
            $head = '<table width="100%" class="nodecaption">';
            $head .= '<tr>';
            $head .= '<td class="entity-left">';
            $head .= $checkbox;
            $head .= ' <a name="mile'.$milestone->id.'"></a>';
            $head .= "M{$i} - ".format_string($milestone->abstract)." {$milestonedeadline}";
            $head .= '</td>';
            $head .= '<td class="entity-right">';
            $head .= "{$taskcount} {$deliverablecount} {$completion} {$hide}";
            $heand .= '</td>';
            $head .= '</tr>';
            $head .= '</table>';
            $rows = array();

            $row = '<tr>';
            $row .= '<td class="projectparam" valign="top">'.get_string('totalplanned', 'techproject').'</td>';
            $row .= "<td valign=\"top\" class=\"projectdata\">{$toptasks->planned} ".$timeunits[$project->timeunit].'</td>';
            $row .= '</tr>';
            $rows[] = $row;

            $row = '<tr>';
            $row .= '<td class="projectparam" valign="top">'.get_string('totalquote', 'techproject').'</td>';
            $row .= "<td valign=\"top\" class=\"projectdata\">{$toptasks->quoted} ".$project->costunit.'</td>';
            $row .= '</tr>';
            $rows[] = $row;

            $row = '<tr>';
            $row .= '<td class="projectparam" valign="top">'.get_string('done', 'techproject').'</td>';
            $row .= "<td valign=\"top\" class=\"projectdata\">{$toptasks->done} %</td>";
            $row .= '</tr>';
            $rows[] = $row;

            $row = '<tr>';
            $row .= '<td class="projectparam" valign="top">'.get_string('totaltime', 'techproject').'</td>';
            $row .= "<td valign=\"top\" class=\"projectdata\">{$toptasks->used} ".$timeunits[$project->timeunit].'</td>';
            $row .= '</tr>';
            $rows[] = $row;

            $row = '<tr>';
            $row .= '<td class="projectparam" valign="top">'.get_string('totalcost', 'techproject').'</td>';
            $row .= "<td valign=\"top\" class=\"projectdata\">{$toptasks->spent} ".$project->costunit.'</td>';
            $row .= '</tr>';
            $rows[] = $row;

            $row = '<tr>';
            $row .= '<td class="projectparam" valign="top">'.get_string('assignedtasks', 'techproject').'</td>';
            $row .= "<td valign=\"top\" class=\"projectdata\">{$toptasks->count}</td>";
            $row .= '</tr>';
            $rows[] = $row;

            $row = '<tr>';
            $row .= '<td class="projectparam" valign="top">'.get_string('assigneddeliverables', 'techproject').'</td>';
            $row .= "<td valign=\"top\" class=\"projectdata\">{$delivcount}</td>";
            $row .= '</tr>';
            $rows[] = $row;

            $description = file_rewrite_pluginfile_urls($milestone->description, 'pluginfile.php', $context->id, 'mod_techproject',
                                                        'milestonedescription', $milestone->id);
            $row = '<tr>';
            $row .= '<td class="projectdata description" valign="top" colspan="2">';
            $row .= format_string(format_text($description, $milestone->descriptionformat));
            $row .= '</td>';
            $row .= '</tr>';
            $rows[] = $row;

            $desc = "<table width=\"100%\" border=\"1\" id=\"{$i}\" class=\"entitycontent\" style=\"display: none;\">".join($rows)."</table>";

            $table = new html_table();
            $table->class = 'entity';
            $table->head  = array ($head);
            $table->width  = '100%';
            $table->cellpadding = 1;
            $table->cellspacing = 1;
            $table->align = array ('left');
            $table->data[] = array($desc);
            $table->rowclass[] = 'description';

            if ($canedit) {
                $link = array();
                $params = array('id' => $cmid, 'work' => 'update', 'milestoneid' => $milestone->id, 'view' => 'milestones');
                $updateurl = new moodle_url('/mod/techproject/view.php', $params);
                $pixurl = $OUTPUT->pix_url('/t/edit');
                $pix = '<img src="'.$pixurl.'" alt="'.get_string('update').'" />';
                $link[] = '<a href="'.$updateurl.'">'.$pix.'</a>';

                if ($toptasks->count == 0 || $project->allowdeletewhenassigned) {
                    $params = array('id' => $cmid, 'work' => 'dodelete', 'milestoneid' => $milestone->id, 'view' => 'milestones');
                    $deleteurl = new moodle_url('/mod/techproject/view.php', $params);
                    $pixurl = $OUTPUT->pix_url('/t/delete');
                    $pix .= '<img src="'.$pixurl.'" alt="'.get_string('delete').'" />';
                    $link[] = '<a href="'.$deleteurl.'">'.$pix.'</a>';
                }
                if ($i > 1) {
                    $params = array('id' => $cmid, 'work' => 'up', 'milestoneid' => $milestone->id, 'view' => 'milestones');
                    $upurl = new moodle_url('/mod/techproject/view.php', $params);
                    $pixurl = $OUTPUT->pix_url('/t/up');
                    $pix = '<img src="'.$pixurl.'" alt="'.get_string('up', 'techproject').'" />';
                    $link[] = '<a href="'.$upurl.'">'.$pix.'</a>';
                }
                if ($i < count($milestones)) {
                    $params = array('id' => $cmid, 'work' => 'down', 'milestoneid' => $milestone->id, 'view' => 'milestones');
                    $downurl = new moodle_url('/mod/techproject/view.php', $params);
                    $pixurl = $OUTPUT->pix_url('/t/down');
                    $pix = '<img src="'.$pixurl.'" alt="'.get_string('down', 'techproject').'" />';
                    $link[] = '<a href="'.$downurl.'">'.$pix.'</a>';
                }
                $table->data[] = array(implode(' ', $link));
            }

            $table->style = "generaltable";
            techproject_print_project_table($table);
            unset($table);

            $i++;
            echo '</div>';
        }
    } else {
        echo $OUTPUT->box_start();
        print_string('nomilestones', 'techproject');
        echo $OUTPUT->box_end();
    }
}

/**
 * prints a deliverable entry with its tree branch
 * @param project the current project
 * @param group the group of students
 * @fatherid the father node
 * @param numspec the propagated autonumbering prefix
 * @param cmid the module id (for urls)
 * @uses $CFG
 * @uses $USER
 */
function techproject_print_deliverables($project, $group, $fatherid, $cmid, $propagated = null) {
    global $CFG, $USER, $DB, $OUTPUT;
    static $level = 0;
    static $startuplevelchecked = false;

    techproject_check_startup_level('deliverable', $fatherid, $level, $startuplevelchecked);

    $sql = "
        SELECT
            d.*,
            m.abstract as milestoneabstract,
            c.collapsed
        FROM
            {techproject_deliverable} as d
        LEFT JOIN
            {techproject_milestone} as m
        ON
            d.milestoneid = m.id
        LEFT JOIN
            {techproject_collapse} as c
        ON
            d.id = c.entryid AND
            c.entity = 'deliverables' AND
            c.userid = $USER->id
        WHERE
            d.groupid = {$group} AND
            d.projectid = {$project->id} AND
            d.fatherid = {$fatherid}
        ORDER BY
            d.ordering
    ";

    if ($deliverables = $DB->get_records_sql($sql)) {
        foreach ($deliverables as $deliverable) {
            $level++;
            echo '<div class="entitynode nodelevel'.$level.'">';
            $propagatedroot = $propagated;
            if (!$propagated || (!isset($propagated->milestoneid) && $deliverable->milestoneid)) {
                if (is_null($propagatedroot)) $propagatedroot = new StdClass();
                $propagatedroot->milestoneid = $deliverable->milestoneid;
                $propagatedroot->milestoneabstract = $deliverable->milestoneabstract;
            } else {
               $deliverable->milestoneid = $propagated->milestoneid;
               $deliverable->milestoneabstract = $propagated->milestoneabstract;
               $deliverable->milestoneforced = 1;
            }
            techproject_print_single_deliverable($deliverable, $project, $group, $cmid, count($deliverables));

            $visibility = ($deliverable->collapsed) ? 'display: none' : 'display: block';
            echo "<div id=\"sub{$deliverable->id}\" style=\"$visibility\" >";
            techproject_print_deliverables($project, $group, $deliverable->id, $cmid, $propagatedroot);
            echo '</div>';
            $level--;
            echo '</div>';
        }
    } else {
        if ($level == 0) {
            echo $OUTPUT->box_start();
            print_string('nodeliverables', 'techproject');
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * prints a single task object
 * @param task the current task to print
 * @param project the current project
 * @param group the current group
 * @param cmid the current coursemodule (useful for making urls)
 * @param setSize the size of the set of objects we are printing an item of
 * @param fullsingle true if prints a single isolated element
 */
function techproject_print_single_deliverable($deliverable, $project, $group, $cmid, $setsize, $fullsingle = false) {
    global $CFG, $USER, $DB, $OUTPUT, $PAGE;

    $fs = get_file_storage();

    $renderer = $PAGE->get_renderer('mod_techproject');
    $context = context_module::instance($cmid);
    $canedit = $USER->editmode == 'on' && has_capability('mod/techproject:changedelivs', $context);
    $numdeliv = implode('.', techproject_tree_get_upper_branch('techproject_deliverable', $deliverable->id, true, true));
    if (!$fullsingle) {
        if (techproject_count_subs('techproject_deliverable', $deliverable->id) > 0) {
            $jshandler = 'javascript:toggle(\''.$deliverable->id.'\',\'sub'.$deliverable->id.'\', 1);';
            $pixurl = $OUTPUT->pix_url('p/switch_minus', 'techproject');
            $pix = '<img name="img'.$deliverable->id.'" src="'.$pixurl.'" alt="collapse" />';
            $hidesub = '<a href="'.$jshandler.'">'.$pix.'</a>';
        } else {
            $hidesub = '<img src="'.$OUTPUT->pix_url('p/empty', 'techproject').'" />';
        }
    } else {
       $hidesub = '';
    }

    $delivlevel = count(explode('.', $numdeliv)) - 1;
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $delivlevel);

    // Get completion indicator for deliverables through assigned tasks.
    $sql = "
       SELECT
           count(*) as count,
           SUM(done) as done,
           SUM(planned) as planned,
           SUM(quoted) as quoted,
           SUM(used) as used,
           SUM(spent) as spent
       FROM
          {techproject_task} as t,
          {techproject_task_to_deliv} as ttd
       WHERE
          t.projectid = {$project->id} AND
          t.groupid = $group AND
          t.id = ttd.taskid AND
          ttd.delivid = {$deliverable->id}
    ";
    $completion = '';
    if ($res = $DB->get_record_sql($sql)) {
         if ($res->count != 0) {
            $deliverable->done = ($res->count != 0) ? round($res->done / $res->count, 1) : 0;
            $over = ($res->planned && $res->planned < $res->used) ? floor((($res->used - $res->planned) / $res->planned) * 60) : 0;
            $completion = $renderer->bar_graph_over($deliverable->done, $over, 60, 5);
        }
    }

    $milestonepix = (isset($deliverable->milestoneforced)) ? 'milestoneforced' : 'milestone';
    $pixurl = $OUTPUT->pix_url("p/{$milestonepix}", 'techproject');
    $milestone = ($deliverable->milestoneid) ? '<img src="'.$pixurl.'" title="'.@$deliverable->milestoneabstract.'" />' : '';

    $taskcount = techproject_print_entitycount('techproject_deliverable', 'techproject_task_to_deliv', $project->id, $group, 'deliv',
                                               'task', $deliverable->id);
    $checkbox = ($canedit) ? '<input type="checkbox" name="ids[]" value="'.$deliverable->id.'" />' : '';

    if (!$fullsingle) {
        $hideicon = (!empty($deliverable->description)) ? 'hide' : 'hide_shadow';
        $jshandler = 'javascript:toggle_show(\''.$numdeliv.'\',\''.$numdeliv.'\');';
        $pixurl = $OUTPUT->pix_url("/p/{$hideicon}", 'techproject');
        $hidedeliv = '<a href="'.$jshandler.'"><img name="eye'.$numdeliv.'" src="'.$pixurl.'" alt="collapse" /></a>';
    } else {
        $hidedeliv = '';
    }
    if ($deliverable->localfile) {
        $files = $fs->get_area_files($context->id, 'mod_techproject', 'deliverable', $deliverable->id, false);
        if ($files) {
            $storedfile = array_pop($files);
            $localfileurl = moodle_url::make_pluginfile_url($context->id, 'mod_techproject', 'deliverable', $deliverable->id,
                                                            $storedfile->get_filepath(), $storedfile->get_filename());
            $abstract = '<a href="'.$localfileurl.'" target="_blank">'.$deliverable->abstract.'</a>';
        } else {
            $abstract = format_string($deliverable->abstract);
        }
    } else if ($deliverable->url) {
        $abstract = '<a href="'.$deliverable->url.'" target="_blank">'.$deliverable->abstract.'</a>';
    } else {
       $abstract = format_string($deliverable->abstract);
    }
    $head = '<table width="100%" class="nodecaption">';
    $head .= '<tr>';
    $head .= '<td class="entity-left">';
    $head .= "<b>{$checkbox} {$indent}";
    $head .= '<span class="level'.$delivlevel.'">';
    $head .= $hidesub;
    $head .= ' <a name="node'.$deliverable->id.'"></a>';
    $head .= "D{$numdeliv} - {$abstract}";
    $head .= '</span></b>';
    $head .= '</td>';
    $head .= '<td class="entity-right">';
    $head .= "{$taskcount} {$completion} {$milestone} {$hidedeliv}";
    $head .= '</td>';
    $head .= '</tr>';
    $head .= '</table>';

    $statusoption = techproject_get_option_by_key('delivstatus', $project->id, $deliverable->status);

    unset($innertable);
    $innertable = new html_table();
    $innertable->width = '100%';
    $innertable->style = array('parmname', 'parmvalue');
    $innertable->align = array ('left', 'left');
    $innertable->data[] = array(get_string('status', 'techproject'), $statusoption->label);
    $innertable->data[] = array(get_string('fromtasks', 'techproject'), $taskcount);
    $parms = techproject_print_project_table($innertable, true);
    $description = file_rewrite_pluginfile_urls($deliverable->description, 'pluginfile.php', $context->id, 'mod_techproject',
                                                'deliverabledescription', $deliverable->id);

    if (!$fullsingle || $fullsingle === 'HEAD') {
        $initialdisplay = 'none';
        $description = close_unclosed_tags(shorten_text(format_text($description, $deliverable->descriptionformat), 800));
    } else {
        $initialdisplay = 'block';
        $description = format_text($description, $deliverable->descriptionformat);
    }
    $desc = "<div id='{$numdeliv}' class='entitycontent' style='display: {$initialdisplay};'>{$parms}".$description;
    if (!$fullsingle) {
        $params = array('id' => $cmid, 'view' => 'view_detail', 'objectId' => $deliverable->id, 'objectClass' => 'deliverable');
        $detailurl = new moodle_url('/mod/techproject/view.php', $params);
        $desc .= '<br/><a href="'.$detailurl.'" >'.get_string('seedetail', 'techproject')."</a></div>"; 
    }
    $desc .= "</div>";

    $table = new html_table();
    $table->class = 'entity';
    $table->head  = array ($head);
    $table->cellspacing = 1;
    $table->cellpadding = 1;
    $table->width = '100%';
    $table->align = array ('left');
    $table->data[] = array($desc);
    $table->rowclass[] = 'description';

    if ($canedit) {
        $link = array();
        $params = array('id' => $cmid, 'work' => 'add', 'fatherid' => $deliverable->id, 'view' => 'deliverables');
        $addsuburl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/p/newnode', 'techproject');
        $pix = '<img src="'.$pixurl.'" alt="'.get_string('addsubdeliv', 'techproject').'" />';
        $link[] = '<a href="'.$addsuburl.'">'.$pix.'</a>';

        $params = array('id' => $cmid, 'work' => 'update', 'delivid' => $deliverable->id, 'view' => 'deliverables');
        $updateurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/t/edit');
        $pix = '<img src="'.$pixurl.'" alt="'.get_string('update').'" />';
        $link[] = '<a href="'.$updateurl.'">'.$pix.'</a>';

        $params = array('id' => $cmid, 'work' => 'dodelete', 'delivid' => $deliverable->id, 'view' => 'deliverables');
        $deleteurl = new moodle_url('/mod/techproject/view.php', $params);
        $pixurl = $OUTPUT->pix_url('/t/delete');
        $pix = '<img src="'.$pixurl.'" alt="'.get_string('delete').'" />';
        $link[] = '<a href="'.$deleteurl.'">'.$pix.'</a>';

        techproject_add_entity_tree_controls($cmid, 'deliverable', 'deliv', $deliverable, $setsize, $link);
        $table->data[] = array($indent . implode (' ' , $link));
        $table->rowclass[] = 'controls';
    }
    
    $table->style = "generaltable";
    techproject_print_project_table($table);
    unset($table);
}

/**
 * prints the heading section of the project
 * @param project the project object, passed by reference (read only)
 * @param group the actual group
 * @return void prints only viewable sequences
 */
function techproject_print_heading(&$project, $group) {
    global $CFG, $DB, $OUTPUT;

    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $group));

    // If missing create one.
    if (!$projectheading) {
        $projectheading = new StdClass;
        $projectheading->id = 0;
        $projectheading->projectid = $project->id;
        $projectheading->groupid = $group;
        $projectheading->title = '';
        $projectheading->abstract = '';
        $projectheading->rationale = '';
        $projectheading->environment = '';
        $projectheading->organisation = '';
        $projectheading->department = '';
        $DB->insert_record('techproject_heading', $projectheading);
    }
    echo $OUTPUT->heading(get_string('projectis', 'techproject') . $projectheading->title);
    echo '<br/>';
    echo $OUTPUT->box_start('center', '100%');
    if ($projectheading->organisation != '') {
        echo $OUTPUT->heading(format_string($projectheading->organisation), 3);
        echo $OUTPUT->heading(format_string($projectheading->department), 4);
    }
    echo $OUTPUT->heading(get_string('abstract', 'techproject'), 2);
    echo (empty($projectheading->abstract)) ? $project->intro : $projectheading->abstract;
    if ($projectheading->rationale != '') {
        echo $OUTPUT->heading(get_string('rationale', 'techproject'), 2);
        echo $projectheading->rationale, false;
    }
    if ($projectheading->environment != '') {
        echo $OUTPUT->heading(get_string('environment', 'techproject'), 2);
        echo format_string($projectheading->environment, false);
    }
    echo $OUTPUT->box_end();
}

function techproject_print_resume($project, $currentgroupid, $fatherid, $numresume) {
    assert(1);
}

/**
 * gets any option domain as an array of records. The domain defaults to the option set
 * defined for a null projectid.
 * @param string $domain the domain table to fetch
 * @param int $projectid the project id the option set id for
 * @return the array of domain records
 */
function techproject_get_options($domain, $projectid) {
    global $DB;

    if (!function_exists('get_localized')) {
        function get_localized(&$var) {
            $var->label = get_string($var->label, 'techproject');
            $var->description = get_string($var->description, 'techproject');
        }

        function get_filtered(&$var) {
            $var->label = format_string($var->label, 'techproject');
            $var->description = format_string($var->description, 'techproject');
        }
    }

    $select = " domain = ? AND  projectid = ? ";
    if (!$options = $DB->get_records_select('techproject_qualifier', $select, array($domain, $projectid))) {
        $select = " domain = ? AND  projectid = 0 ";
        if ($siteoptions = $DB->get_records_select('techproject_qualifier', $select, array($domain))) {
            $options = array_values($siteoptions);
            for ($i = 0; $i < count($options); $i++) {
                get_localized($options[$i]);
            }
        } else {
            $options = array();
        }
    } else {
        for ($i = 0; $i < count($options); $i++) {
            get_filtered($options[$i]);
        }
    }
    return $options;
}

/**
 * gets any option domain as an array of records. The domain defaults to the option set
 * defined for a null projectid.
 * @param string $domain the domain subtable to fetch
 * @param int $projectid the project id the option set id for
 * @param string $value the reference value
 * @return an array with a single object
 */
function techproject_get_option_by_key($domain, $projectid, $value) {
    global $DB;

    if (!function_exists('get_localized')) {
        function get_localized(&$var) {
            $var->truelabel = $var->label;
            $var->label = get_string($var->label, 'techproject');
            $var->description = get_string(@$var->description, 'techproject');
        }
    }

    $params = array('domain' => $domain, 'projectid' => $projectid, 'code' => $value);
    if (!$option = $DB->get_record('techproject_qualifier', $params)) {
        $params = array('domain' => $domain, 'projectid' => 0, 'code' => $value);
        if ($option = $DB->get_record('techproject_qualifier', $params)) {
            get_localized($option);
        } else {
            $option = new StdClass();
            $option->id = 0;
            $option->truelabel = 'default';
            $option->label = get_string('unqualified', 'techproject');
            $option->description = '';
        }
    }
    return $option;
}

/**
 * checks for some circularities in the dependencies
 * @param int $taskid the current task
 * @param int $masterid the master task to be checked for
 * @return boolean true/false
 */
function techproject_check_task_circularity($taskid, $masterid) {
    global $DB;

    if ($slavetasks = $DB->get_records('techproject_task_dependency', array('master' => $taskid))) {
        foreach ($slavetasks as $atask) {
            if ($atask->id == $masterid) {
                return true;
            }
            if (techproject_check_task_circularity($atask->id, $masterid)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * prints an indicator of how much related objects there are
 * @param table1 the table-tree of the requiring entity
 * @param table2 the table where cross dependencies is
 * @param project the current project context
 * @param group the current gorup in project
 * @param what what to search for (first key of crossdependency)
 * @param relwhat relative to which other entity (second key of cross dependency)
 * @param id an item root of the query. Will be expansed to all its subtree.
 * @param whatlist a list of nodes resulting of a previous id expansion
 */
function techproject_print_entitycount($table1, $table2, $projectid, $groupid, $what, $relwhat, $id, $whatList = '') {
    global $CFG, $DB, $OUTPUT;

    // Get concerned subtree if not provided.
    if (!isset($whatList) || empty($whatList)) {
        $whatList = str_replace(",", "','", techproject_get_subtree_list($table1, $id));
    }

    // Assigned reqs by subspecs count.
    $sql = "
       SELECT
          COUNT(*) as subs
       FROM
            {{$table2}}
       WHERE
            {$what}id IN ('{$whatList}') AND
            projectid = {$projectid} AND
            groupid = {$groupid}
    ";
    $res = $DB->get_record_sql($sql);
    $subcount = "[".$res->subs."]";

    // Directly assigned reqs count (must count separately).
    $sql = "
        SELECT
            COUNT(t2.{$relwhat}Id) as subs
        FROM
            {{$table1}} AS t1
        LEFT JOIN
            {{$table2}} AS t2
        ON
            t1.id = t2.{$what}Id
        WHERE
            t1.groupid = {$groupid} AND
            t1.projectid = {$projectid} AND
            t1.id = {$id}
        GROUP BY
            t1.id
    ";
    $res = $DB->get_record_sql($sql);
    if (!$res) {
        $res->subs = 0;
    } else {
        $res->subs += 0;
    }
    if ($res->subs > 0 || $subcount > 0) {
        $pixurl = $OUTPUT->pix_url('p/'.$relwhat, 'techproject');
        $output = '<img src="'.$pixurl.'" title="'.get_string('bounditems', 'techproject', $relwhat).'" />('.$res->subs.') '.$subcount;
    } else {
        $output = '';
    }
    return $output;
}

/**
 * prints a select box with commands applicable to the item selection
 * @param additional an additional set of commands if needed
 *
 */
function techproject_print_group_commands($additional = '') {
    global $CFG;

    $optionlist[''] = get_string('choosewhat', 'techproject');
    $optionlist['deleteitems'] = get_string('deleteselected', 'techproject');
    $optionlist['copy'] = get_string('copyselected', 'techproject');
    $optionlist['move'] = get_string('moveselected', 'techproject');
    $optionlist['export'] = get_string('xmlexportselected', 'techproject');
    if (!empty($additional)) {
        foreach ($additional as $acommand) {
            $optionlist[$acommand] = get_string($acommand.'selected', 'techproject');
        }
    }
    echo '<p>'.get_string('withchosennodes', 'techproject');
    $attrs = array('onchange' => 'sendgroupdata()');
    echo html_writer::select($optionlist, 'cmd', '', array('' => get_string('choosewhat', 'techproject')), $attrs);
    echo '</p>';
}

/**
 * Print a nicely formatted table. Hack from the original print_table from weblib.php
 *
 * @param array $table is an object with several properties.
 *     <ul<li>$table->head - An array of heading names.
 *     <li>$table->align - An array of column alignments
 *     <li>$table->size  - An array of column sizes
 *     <li>$table->wrap - An array of "nowrap"s or nothing
 *     <li>$table->data[] - An array of arrays containing the data.
 *     <li>$table->width  - A percentage of the page
 *     <li>$table->tablealign  - Align the whole table
 *     <li>$table->cellpadding  - Padding on each cell
 *     <li>$table->cellspacing  - Spacing between cells
 * </ul>
 * @param bool $return whether to return an output string or echo now
 * @return boolean or $string
 * @todo Finish documenting this function
 */
function techproject_print_project_table($table, $return = false) {
    $output = '';

    if (isset($table->align)) {
        foreach ($table->align as $key => $aa) {
            if ($aa) {
                $align[$key] = ' align="'. $aa .'"';
            } else {
                $align[$key] = '';
            }
        }
    }
    if (isset($table->size)) {
        foreach ($table->size as $key => $ss) {
            if ($ss) {
                $size[$key] = ' width="'. $ss .'"';
            } else {
                $size[$key] = '';
            }
        }
    }
    if (isset($table->wrap)) {
        foreach ($table->wrap as $key => $ww) {
            if ($ww) {
                $wrap[$key] = ' nowrap="nowrap" ';
            } else {
                $wrap[$key] = '';
            }
        }
    }

    if (empty($table->width)) {
        $table->width = '80%';
    }

    if (empty($table->tablealign)) {
        $table->tablealign = 'center';
    }

    if (empty($table->cellpadding)) {
        $table->cellpadding = '5';
    }

    if (empty($table->cellspacing)) {
        $table->cellspacing = '1';
    }

    if (empty($table->class)) {
        $table->class = 'generaltable';
    }

    $tableid = empty($table->id) ? '' : 'id="'.$table->id.'"';

    $output .= '<table width="'.$table->width.'" align="'.$table->tablealign.'" ';
    $output .= " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\" class=\"$table->class\" $tableid>\n";

    $countcols = 0;

    if (!empty($table->head)) {
        $countcols = count($table->head);
        $output .= '<tr>';
        foreach ($table->head as $key => $heading) {

            if (!isset($size[$key])) {
                $size[$key] = '';
            }
            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            $output .= '<th valign="top" '. $align[$key].$size[$key] .' nowrap="nowrap" class="c'.$key.'">'. $heading .'</th>';
        }
        $output .= '</tr>'."\n";
    }

    if (!empty($table->data)) {
        $oddeven = 1;
        foreach ($table->data as $key => $row) {
            $oddeven = $oddeven ? 0 : 1;
            $output .= '<tr class="r'.$oddeven.'">'."\n";
            if ($row == 'hr' and $countcols) {
                $output .= '<td colspan="'. $countcols .'"><div class="tabledivider"></div></td>';
            } else {  /// it's a normal row of data
                foreach ($row as $key => $item) {
                    if (!isset($size[$key])) {
                        $size[$key] = '';
                    }
                    if (!isset($align[$key])) {
                        $align[$key] = '';
                    }
                    if (!isset($wrap[$key])) {
                        $wrap[$key] = '';
                    }
                    $output .= '<td '. $align[$key].$size[$key].$wrap[$key] .' class="'.$table->style[$key].'">'. $item .'</td>';
                }
            }
            $output .= '</tr>'."\n";
        }
    }
    $output .= '</table>'."\n";

    if ($return) {
        return $output;
    }

    echo $output;
    return true;
}

/**
 * calculates the autograde.
 * Autograde is the mean of :
 * - the ratio of uncovered requirements (full chain to deliverables)
 * - the ratio of uncovered deliverables (full chain to reqauirements)
 * - the completion ratio over requirements
 * - the balance of charge between members
 * @param object $project the project
 * @param int group
 * @return the grade
 */
function techproject_autograde($project, $groupid) {
    global $CFG, $DB;

    echo $OUTPUT->heading(get_string('autograde', 'techproject'));

    // Get course module.
    $module = $DB->get_record('modules', array('name' => 'techproject'));
    $params = array('course' => $project->course, 'instance' => $project->id, 'module' => $module->id);
    $cm = $DB->get_record('course_modules', $params);
    $course = $DB->get_record('course', array('id' => $project->course));
    $coursecontext = context_course::instance($course->id);

    // Step 1 : get requirements to cover as an Id list.
    $select = "projectid = ? AND groupid = ? AND fatherid = 0";
    $rootrequirements = $DB->get_records_select('techproject_requirement', $select, array($project->id, $group));
    $effectiverequirements = array();
    foreach ($rootrequirements as $aroot) {
        $leaves = techproject_count_leaves('techproject_requirement', $aroot->id, true);
        $effectiverequirements = array_merge($effectiverequirements, $leaves);
    }
    $effectiverequirementscount = count($effectiverequirements);

    /*
     * now we know how many requirements are to be covered
     * For each of those elements, do we have a chain to deliverables ?
     * chain origin can start from an upper requirement
     */
    $coveredreqs = 0;
    foreach ($effectiverequirements as $arequirement) {
        $upperbranchlist = techproject_tree_get_upper_branch('techproject_requirement', $arequirement, true, false);
        $upperbranchlist = str_replace(',', "','", $upperbranchlist);
        $sql = "
            SELECT
                COUNT(*) as coveringChains
            FROM
                {techproject_spec_to_req} as str,
                {techproject_task_to_spec} as tts,
                {techproject_task_to_deliv} as ttd
            WHERE
                str.reqid IN ('{$upperbranchlist}') AND
                str.specid = tts.specid AND 
                tts.taskid = ttd.taskid AND
                str.projectid = {$project->id} AND
                str.groupid = {$groupid}
        ";
        $res = $DB->get_record_sql($sql);
        if($res->coveringChains > 0) {
            $coveredreqs++;
        }
    }
    $requrate = ($effectiverequirementscount) ? $coveredreqs / $effectiverequirementscount : 0;
    echo '<br/>';
    echo '<b>'.get_string('requirementsrate', 'techproject').' :</b>';
    echo ' '.$coveredreqs.' '.get_string('over', 'techproject').' '.$effectiverequirementscount.' : '.sprintf("%.02f", $requrate);

    // Now we know how many requirements are really covered directly or indirectly.

    // Step 2 : get deliverables to cover as an Id list.
    $select = "projectid = ? AND groupid = ? AND fatherid = 0";
    $rootdeliverables = $DB->get_records_select('techproject_deliverable', $select, array($project->id, $group));
    $effectivedeliverables = array();
    foreach ($rootdeliverables as $aroot) {
        $leaves = techproject_count_leaves('techproject_deliverable', $aroot->id, true);
        $effectivedeliverables = array_merge($effectivedeliverables, $leaves);
    }
    $effectivedeliverablescount = count($effectivedeliverables);

    /*
     * now we know how many deliverables are to be covered
     * For each of those elements, do we have a chain to requirements ?
     * chain origin can start from an upper deliverable
     */
    $covereddelivs = 0;
    foreach ($effectivedeliverables as $adeliverable) {
        $upperbranchlist = techproject_tree_get_upper_branch('techproject_deliverable', $adeliverable, true, false);
        $upperbranchlist = str_replace(',', "','", $upperbranchlist);
        $sql = "
            SELECT
                COUNT(*) as coveringChains
            FROM
                {techproject_spec_to_req} as str,
                {techproject_task_to_spec} as tts,
                {techproject_task_to_deliv} as ttd
            WHERE
                str.specid = tts.specid AND 
                tts.taskid = ttd.taskid AND
                ttd.delivid IN ('{$upperbranchlist}') AND
                str.projectid = {$project->id} AND
                str.groupid = {$groupid}
        ";
        $res = $DB->get_record_sql($sql);
        if($res->coveringChains > 0) {
            $covereddelivs++;
        }
    }
    $delivrate = ($effectivedeliverablescount) ? $covereddelivs / $effectivedeliverablescount : 0;
    echo '<br/><b>'.get_string('deliverablesrate', 'techproject').' :</b> '.$covereddelivs.' '.get_string('over', 'techproject').' '.$effectivedeliverablescount.' : '.sprintf("%.02f", $delivrate);
    // now we know how many deliverables are really covered directly or indirectly.

    // Step 3 : calculating global completion indicator on tasks (only meaning root tasks is enough).
    $select = "projectid = ? AND groupid = ? AND fatherid = 0";
    $roottasks = $DB->get_records_select('techproject_task', $select, array($project->id, $group)); 
    $completion = 0;
    if ($roottasks) {
        foreach ($roottasks as $atask) {
            $completion += $atask->done;
        }
        $done = (count($roottasks)) ? sprintf("%.02f", $completion / count($roottasks) / 100) : 0;
        echo '<br/><b>'.get_string('completionrate', 'techproject').' :</b> '.$done;
    }

    // Step 4 : calculating variance (balance) of task assignation between members.
    if ($roottasks) {
        $leaftasks = array();
        // Get leaves.
        foreach ($roottasks as $atask) {
            $leaftasks = array_merge($leaftasks, techproject_count_leaves('techproject_task', $atask->id, true));
        }

        /*
         * collecting and accumulating charge planned
         * get student list
         */
        if (!groups_get_activity_groupmode($cm, $course)) {
            $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',u.mail, u.picture';
            $groupstudents = get_users_by_capability($coursecontext, 'mod/techproject:canbeevaluated', $fields, 'u.lastname');
        } else {
            $groupmembers = get_group_members($groupid);
            $groupstudents = array();
            if ($groupmembers) {
                foreach ($groupmembers as $amember) {
                    if (has_capability('mod/techproject:canbeevaluated', $coursecontext, $amember->id)) {
                        $groupstudents[] = clone($amember);
                    }
                }
            }
        }

        // Intitializes charge table.
        foreach ($groupstudents as $astudent) {
            $membercharge[$astudent->id] = 0;
        }

        // Getting real charge.
        foreach ($leaftasks as $aleaf) {
            $membercharge[$aleaf->assignee] = @$membercharge[$aleaf->assignee] + $aleaf->planned;
        }

        // Calculating charge mean and variance.
        $totalcharge = array_sum(array_values($membercharge));
        $assigneecount = count(array_keys($membercharge));
        $meancharge = ($assigneecount == 0) ? 0 : $totalcharge / $assigneecount ;
        $quadraticsum = 0;
        foreach (array_values($membercharge) as $acharge) {
            $quadraticsum += ($acharge - $meancharge) * ($acharge - $meancharge);
        }
        $sigma = sqrt($quadraticsum/$assigneecount);
        echo '<br/><b>' . get_string('chargedispersion', 'techproject') . ' :</b> ' . sprintf("%.02f", $sigma);
    }
    $totalgrade = round((0 + @$done + @$requrate + @$delivrate) / 3, 2);
    echo '<br/><b>' . get_string('mean', 'techproject') . ' :</b> ' . sprintf("%.02f", $totalgrade);
    if ($project->grade > 0) {
        echo '<br/><b>'.get_string('scale', 'techproject').' :</b> '.$project->grade;
        echo '<br/><b>'.get_string('grade', 'techproject').' :</b> '.round($project->grade * $totalgrade);
    }
    return $totalgrade;
}

/**
 * get all ungrouped students in a course.
 * @param int $courseid
 * @return an array of users
 */
function techproject_get_users_not_in_group($courseid) {
    $coursecontext = context_course::instance($courseid);
    $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',picture,email';
    $users = get_users_by_capability($coursecontext, 'mod/techproject:beassignedtasks', $fields, 'lastname');
    if ($users) {
        if ($groups = groups_get_all_groups($courseid)) {
            foreach ($groups as $agroup) {
                if ($agroup->id == 0) {
                    continue;
                }
                $groupset = groups_get_members($agroup->id);
                if ($groupset) {
                    foreach (array_keys($groupset) as $userid) {
                        unset($users[$userid]);
                    }
                }
            }
        }
    }
    return $users;
}

/**
 * get group users according to group situation
 * @param int $courseid
 * @param object $cm
 * @param int $groupid
 * @return an array of users
 */
function techproject_get_group_users($courseid, $cm, $groupid) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $courseid));
    if (!groups_get_activity_groupmode($cm, $course)) {
        $coursecontext = context_course::instance($courseid);
        $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',picture,email';
        $users = get_users_by_capability($coursecontext, 'mod/techproject:beassignedtasks', $fields, 'lastname');
    } else {
        if ($groupid) {
            $users = groups_get_members($groupid);
        } else {
            // We could not rely on the legacy function.
            $users = techproject_get_users_not_in_group($courseid);
        }
        if ($users) {
            $context = context_module::instance($cm->id);

            // Equ of array_filter, but needs variable parameter so we cound not use it.
            foreach ($users as $userid => $user) {
                if (!has_capability('mod/techproject:beassignedtasks', $context, $user->id)) {
                    unset($users[$userid]);
                }
            }
        }
    }
    return $users;
}

/**
 *
 * @param object $project
 * @param int $groupid
 * @uses $COURSE
 */
function techproject_get_full_xml(&$project, $groupid) {
    global $COURSE, $CFG, $DB;

    include_once($CFG->dirroot.'/mod/techproject/xmllib.php');

    // Getting heading.
    $heading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $groupid));
    $projects[$heading->projectid] = $heading;
    $xmlheading = recordstoxml($projects, 'project', '', false, null);

    // Getting requirements.
    techproject_tree_get_tree('techproject_requirement', $project->id, $groupid, $requirements, 0);
    $strengthes = $DB->get_records_select('techproject_qualifier', " projectid = ? AND domain = 'strength' ", array($project->id));
    if (empty($strenghes)) {
        $select = " projectid = 0 AND domain = 'strength' ";
        $strengthes = $DB->get_records_select('techproject_qualifier', $select, array());
    }
    $xmlstrengthes = recordstoxml($strengthes, 'strength', '', false, 'techproject');
    $xmlrequs = recordstoxml($requirements, 'requirement', $xmlstrengthes, false);

    // Getting specifications.
    techproject_tree_get_tree('techproject_specification', $project->id, $groupid, $specifications, 0);
    $priorities = $DB->get_records_select('techproject_qualifier', " projectid = ? AND domain = 'priority' ", array($project->id));
    if (empty($priorities)) {
        $priorities = $DB->get_records_select('techproject_qualifier', " projectid =  0 AND domain = 'priority' ", array());
    }
    $severities = $DB->get_records_select('techproject_qualifier', " projectid = ? AND domain = 'severity' ", array($project->id));
    if (empty($severities)) {
        $severities = $DB->get_records_select('techproject_qualifier', " projectid = 0 AND domain = 'severity' ", array());
    }
    $complexities = $DB->get_records_select('techproject_qualifier', " projectid = ? AND domain = 'complexity' ", array($project->id));
    if (empty($complexities)) {
        $complexities = $DB->get_records_select('techproject_qualifier', " projectid = 0 AND domain = 'complexity' ", array());
    }
    $xmlpriorities = recordstoxml($priorities, 'priority_option', '', false, 'techproject');
    $xmlseverities = recordstoxml($severities, 'severity_option', '', false, 'techproject');
    $xmlcomplexities = recordstoxml($complexities, 'complexity_option', '', false, 'techproject');
    $xmlspecs = recordstoxml($specifications, 'specification', $xmlpriorities.$xmlseverities.$xmlcomplexities, false, null);

    // Getting tasks.
    techproject_tree_get_tree('techproject_task', $project->id, $groupid, $tasks, 0);
    if (!empty($tasks)) {
        foreach ($tasks as $taskid => $task) {
            $tasks[$taskid]->taskstart = ($task->taskstart) ? usertime($task->taskstart) : 0;
            $tasks[$taskid]->taskend = ($task->taskend) ? usertime($task->taskend) : 0;
        }
    }
    $worktypes = $DB->get_records_select('techproject_qualifier', " projectid = ? AND domain = 'worktype' ", array($project->id));
    if (empty($worktypes)) {
        $worktypes = $DB->get_records_select('techproject_qualifier', " projectid = 0 AND domain = 'worktype' ", array());
    }
    $taskstatusses = $DB->get_records_select('techproject_qualifier', " projectid = ? AND domain = 'taskstatus' ", array($project->id));
    if (empty($taskstatusses)) {
        $taskstatusses = $DB->get_records_select('techproject_qualifier', " projectid = 0 AND domain = 'taskstatus' ");
    }
    $xmlworktypes = recordstoxml($worktypes, 'worktype_option', '', false, 'techproject');
    $xmltaskstatusses = recordstoxml($taskstatusses, 'task_status_option', '', false, 'techproject');
    $xmltasks = recordstoxml($tasks, 'task', $xmlworktypes.$xmltaskstatusses, false, null);

    // Getting deliverables.
    techproject_tree_get_tree('techproject_deliverable', $project->id, $groupid, $deliverables, 0);
    $delivstatusses = $DB->get_records_select('techproject_qualifier', " projectid = ? AND domain = 'delivstatus' ", array($project->id));
    if (empty($delivstatusses)) {
        $delivstatusses = $DB->get_records_select('techproject_qualifier', " projectid = 0 AND domain = 'delivstatus' ", array());
    }
    $xmldelivstatusses = recordstoxml($delivstatusses, 'deliv_status_option', '', false, 'techproject');
    $xmldelivs = recordstoxml($deliverables, 'deliverable', $xmldelivstatusses, false, null);

    // Getting milestones.
    techproject_tree_get_list('techproject_milestone', $project->id, $groupid, $milestones, 0);
    $xmlmiles = recordstoxml($milestones, 'milestone', '', false, null);

    /// Finally, get the master record and make a full XML with it
    $techproject = $DB->get_record('techproject', array('id' => $project->id));
    $techproject->wwwroot = $CFG->wwwroot;
    $techprojects[$techproject->id] = $techproject;
    $xslfilter = techproject_get_internal_file($techproject, 'xsl');
    $xmlstylesheet = "<?xml-stylesheet href=\"{$xslfilter}\" type=\"text/xsl\"?>\n";
    $xml = recordstoxml($techprojects, 'techproject', $xmlheading.$xmlrequs.$xmlspecs.$xmltasks.$xmldelivs.$xmlmiles, true, null, $xmlstylesheet);

    return $xml;
}

/**
 *
 */
function techproject_get_internal_file($project, $filearea) {
    global $CFG;

    $fs = get_file_storage();

    $context = get_coursemodule_from_instance('techproject', $project->id);

    $files = $fs->get_area_files($context->id, 'mod_techproject', $filearea, 0, 'filename', false);

    if (!empty($files)) {
        $f = array_pop($files);
        $filepathname = $f->get_contenthash();
        return $CFG->dataroot.'/filedir/'.techproject_path_from_hash($filepathname).'/'.$filepathname;
    }

    return $CFG->dirroot.'/mod/techproject/'.$filearea.'/default.'.$filearea;
}

/**
 * Retrieve real path from hash to a stored file.
 *
 * @param array $contenthash
 * @return string the path
 */
function techproject_path_from_hash($contenthash) {
    $l1 = $contenthash[0].$contenthash[1];
    $l2 = $contenthash[2].$contenthash[3];
    return "$l1/$l2";
}

/**
 * utility functions for cleaning user-edited text which would break XHTML rules.
 *
 */

/**
 *
 * @param string $text the input text fragment to be checked
 * @param string $taglist a comma separated list of tag name that should be checked for correct closure
 */
function close_unclosed_tags($text, $taglist = 'p,b,i,li') {
    $tags = explode(',', $taglist);
    foreach ($tags as $atag) {
        $text = close_unclosed($text, "<{$atag}>", "</{$atag}>");
    }
    return $text;
}

/**
 * this is an internal function called by close_unclosed_tags
 * @param string $string the input HTML string
 * @param string $opentag an opening HTML tag we want to check closed
 * @param string $closetag what to close with
 */
function close_unclosed($string, $opentag, $closetag) {
    $count = 0;
    $opensizetags = 0;
    $closedsizetags = 0;
    for ($i = 0; $i <= strlen($string); $i++) {
        $pos = strpos($string, $opentag, $count);
        if (!($pos === false)) {
            $opensizetags++;
            $count = ($pos += 1);
        }
    }
    $count = 0;
    for ($i = 0; $i <= strlen($string); $i++) {
        $pos = strpos($string, $closetag, $count);
        if (!($pos === false)) {
            $closedsizetags++;
            $count = ($pos += 1);
        }
    }
    while ($closedsizetags < $opensizetags) {
        $string .= "$closetag\n";
        $closedsizetags++;
    }
    return $string;
}

/**
 * Get qualifier domain or a domain value
 * @param string $domain the qualifier domain name
 * @param int $id if id is given returns a single value
 * @param boolean $how this parameter tels how to search results. When an id is given it tells what the id is as an identifier (a Mysql record id or a code). 
 * @param int $scope the value scope which is assimilable to a project id or 0 if global scope
 * @param string $sortby 
 */
function techproject_get_domain($domain, $id, $how = false, $scope, $sortby = 'label') {
    global $DB;

    if (empty($id)) {

        // Internationalize if needed (for array walks).
        if (!function_exists('format_string_walk')) {
            function format_string_walk(&$a) {
                global $OUTPUT;
                $a = $OUTPUT->format_string($a);
            }
        }

        if ($how == 'menu') {
            $select = " projectid = ? AND domain = ? ";
            if ($records = $DB->get_records_select_menu('techproject_qualifier', $select, array($scope, $domain), $sortby, 'id, label')) {
                array_walk($records, 'format_string_walk');
                return $records;
            } else {
                return null;
            }
        } else {
            $select = " projectid = ? AND domain = ? ";
            return $DB->get_records_select('techproject_qualifier', $select, array($scope, $domain), $sortby);
        }
    }
    if ($how == 'bycode') {
        $params = array('domain' => $domain, 'projectid' => $scope, 'code' => $id);
        return format_string($DB->get_field('techproject_qualifier', 'label', $params));
    } else {
        $params = array('domain' => $domain, 'projectid' => $scope, 'id' => $id);
        return format_string($DB->get_field("techproject_$domain", 'label', $params));
    }
}

/**
 * print validation will print a requirement tree with validation columns
 *
 */
function techproject_print_validations($project, $groupid, $fatherid, $cmid) {
    global $CFG, $USER, $DB, $OUTPUT;

    static $level = 0;

    $select = " projectid = ? AND groupid = ? ";
    if ($validationsessions = $DB->get_records_select('techproject_valid_session', $select, array($project->id, $groupid))) {
        $validationcaptions = '';
        $deletestr = '<span title="'.get_string('delete').'" style="color:red">x</span>';
        $closestr = get_string('close', 'techproject');
        $updatestr = get_string('update', 'techproject');
        foreach ($validationsessions as $sessid => $session) {
            $params = array('validationsessionid' => $session->id);
            $validationsessions[$sessid]->states = $DB->get_records('techproject_valid_state', $params, '', 'reqid,status,comment');
            $validationcaption = '&lt;'.userdate($session->datecreated).'&gt;';
            if (has_capability('mod/techproject:managevalidations', context_module::instance($cmid))) {
                $params = array('id' => $cmid, 'view' => 'validations', 'work' => 'dodelete', 'validid' => $sessid);
                $viewurl = new moodle_url('/mod/techproject/view.php', $params);
                $validationcaption .= ' <a href="'.$viewurl.'">'.$deletestr.'</a>';
            }
            if ($session->dateclosed == 0) {
                if (has_capability('mod/techproject:managevalidations', context_module::instance($cmid))) {
                    $params = array('id' => $cmid, 'view' => 'validations', 'work' => 'close', 'validid' => $sessid);
                    $viewurl = new moodle_url('/mod/techproject/view.php', $params);
                    $validationcaption .= ' <a href="'.$viewurl.'">'.$closestr.'</a>';
                }
                if (has_capability('mod/techproject:validate', context_module::instance($cmid))) {
                    $params = array('id' => $cmid, 'view' => 'validation', 'validid' => $sessid);
                    $viewurl = new moodle_url('/mod/techproject/view.php', $params);
                    $validationcaption .= ' <a href="'.$viewurl.'">'.$updatestr.'</a>';
                }
            }
            $validationcaptions .= "<td>$validationcaption</td>";
        }
        if ($level == 0) {
            $caption = '<table width="100%" class="validations">';
            $caption .= "<tr><td align='left' width='50%'></td>$validationcaptions</tr>";
            $caption .= '</table>';
            echo $caption;
        }
        if (!empty($project->projectusesrequs)) {
            $entityname = 'requirement';
        } else if (!empty($project->projectusesspecs)) {
            $entityname = 'specification';
        } else if (!empty($project->projectusesdelivs)) {
            $entityname = 'deliverable';
        } else {
            print_error('errornovalidatingentity', 'techproject');
        }
        $sql = "
            SELECT
                e.*,
                c.collapsed
            FROM
                {techproject_{$entityname}} e
            LEFT JOIN
                {techproject_collapse} c
            ON
                e.id = c.entryid AND
                c.entity = '{$entityname}s' AND
                c.userid = $USER->id
            WHERE
                e.groupid = $groupid AND
                e.projectid = {$project->id} AND
                fatherid = $fatherid
            GROUP BY
                e.id
            ORDER BY
                ordering
        ";
        if ($entities = $DB->get_records_sql($sql)) {
            $i = 1;
            foreach ($entities as $entity) {
                echo '<div class="nodelevel'.$level.'">';
                $level++;
                techproject_print_single_entity_validation($validationsessions, $entity, $project, $groupid, $cmid, count($entities), $entityname);
                $visibility = ($entity->collapsed) ? 'display: none' : 'display: block';
                echo '<div id="sub'.$entity->id.'" class="treenode" style="'.$visibility.'" >';
                techproject_print_validations($project, $groupid, $entity->id, $cmid);
                echo '</div>';
                $level--;
                echo '</div>';
            }
        } else {
            if ($level == 0) {
                echo $OUTPUT->box_start();
                print_string('emptyproject', 'techproject');
                echo $OUTPUT->box_end();
            }
        }
    } else {
        if ($level == 0) {
            echo $OUTPUT->box_start();
            print_string('novalidationsession', 'techproject');
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * prints a single validation entity object
 * @param entity the current entity to print
 * @param project the current project
 * @param group the current group
 * @param cmid the current coursemodule (useful for making urls)
 * @param setSize the size of the set of objects we are printing an item of
 * @param fullsingle true if prints a single isolated element
 */
function techproject_print_single_entity_validation(&$validationsessions, &$entity, &$project, $group, $cmid,
                                                    $countentities, $entityname) {
    global $CFG, $USER, $DB, $OUTPUT;

    static $classswitch = 'even';

    $context = context_module::instance($cmid);
    $canedit = has_capability('mod/techproject:validate', $context);
    $numrec = implode('.', techproject_tree_get_upper_branch('techproject_'.$entityname, $entity->id, true, true));
    if (techproject_count_subs('techproject_'.$entityname, $entity->id) > 0) {
        $jshandler = 'javascript:toggle(\''.$entity->id.'\',\'sub'.$entity->id.'\', 1);';
        $pixurl = $OUTPUT->pix_url('p/switch_minus', 'techproject');
        $pix = '<img name="img'.$entity->id.'" src="'.$pixurl.'" alt="collapse" />';
        $hidesub = '<a href="'.$jshandler.'">'.$pix.'</a>';
    } else {
        $hidesub = '<img src="'.$OUTPUT->pix_url('p/empty', 'techproject').'" />';
    }
    $select = " projectid = ? AND groupid = ? AND reqid = ? ";
    $validations = $DB->get_records_select('techproject_valid_state', $select, array($project->id, $group, $entity->id));

    $level = count(explode('.', $numrec)) - 1;
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);

    $validationcells = '';
    foreach ($validationsessions as $session) {
        if (!isset($session->states[$entity->id])) {
            $newstate = new StdClass();
            $newstate->projectid = $project->id;
            $newstate->reqid = $entity->id;
            $newstate->groupid = $group;
            $newstate->validationsessionid = $session->id;
            $newstate->validatorid = $USER->id;
            $newstate->lastchangeddate = time();
            $newstate->status = 'UNTRACKED';
            $newstate->comment = '';
            $stateid = $DB->insert_record('techproject_valid_state', $newstate);
            $session->states[$entity->id] = $newstate;
        }
        $colwidth = floor(50 / count($validationsessions));
        $validationcells .= '<td width="'.$colwidth.'%" class="validationrowbordered '.$classswitch.' validation-'.$session->states[$entity->id]->status.'">';
        $validationcells .= '<span title="'.$session->states[$entity->id]->comment.'">'.get_string(strtolower($session->states[$entity->id]->status), 'techproject').'</span>';
        $validationcells .= '</td>';
    }

    $entitymark = strtoupper(substr($entityname, 0, 1));
    $head = '<table class="nodecaption">';
    $head .= '<tr valign="top">';
    $head .= '<td align="left" width="50%" class="validationrow '.$classswitch.'">';
    $head .= '<span class="level'.$level.'">';
    $head .= "{$indent}{$hidesub} ";
    $head .= '<a name="req'.$entity->id.'"></a>';
    $head .= "{$entitymark}{$numrec} - ".format_string($entity->abstract);
    $head .= '</span>';
    $head .= '</td>';
    $head .= $validationcells;
    $head .= '</tr>';
    $head .= '</table>';
    $classswitch = ($classswitch == 'odd') ? 'even' : 'odd';

    echo $head;
}

function techproject_print_validation_states_form($validsessid, &$project, $groupid, $fatherid = 0, $cmid = 0) {
    global $CFG, $USER, $DB;
    static $level = 0;

    if (!empty($project->projectusesrequs)) {
        $entityname = 'requirement';
    } else if (!empty($project->projectusesspecs)) {
        $entityname = 'specification';
    } else if (!empty($project->projectusesdelivs)) {
        $entityname = 'deliverable';
    } else {
        print_error('errornovalidatingentity', 'techproject');
    }
    $sql = "
        SELECT
            vs.*,
            c.collapsed,
            e.abstract,
            e.fatherid
        FROM
            {techproject_{$entityname}} e
        LEFT JOIN
            {techproject_collapse} c
        ON
            e.id = c.entryid AND
            c.entity = '{$entityname}s' AND
            c.userid = ?
        LEFT JOIN
            {techproject_valid_state} vs
        ON
            e.id = vs.reqid AND
            vs.projectid = ? AND
            vs.validationsessionid = ?
        WHERE
            e.groupid = ? AND
            e.projectid = ? AND
            e.fatherid = ?
        GROUP BY
            e.id
        ORDER BY 
            ordering
    ";
    if ($states = $DB->get_records_sql($sql, array($USER->id, $project->id, $validsessid, $groupid, $project->id, $fatherid))) {
        echo "<form name=\"statesform\" action=\"#\" method=\"POST\" >";
        $i = 1;
        foreach ($states as $state) {
            echo '<div class="nodelevel'.$level.'">';
            $level++;
            techproject_print_single_validation_form($state, $entityname);

            $visibility = ($state->collapsed) ? 'display: none' : 'display: block';
            echo '<div id="sub'.$state->reqid.'" class="treenode" style="'.$visibility.'" >';
            techproject_print_validation_states_form($validsessid, $project, $groupid, $state->reqid, $cmid);
            echo '</div>';
            $level--;
            echo '</div>';
        }

        if ($level == 0) {
            $updatestr = get_string('update');
            echo '<center><input type="submit" name="go_btn" value="'.$updatestr.'" >';
            echo '</form>';
        }
    }
}

/**
 *
 *
 */
function techproject_print_single_validation_form($state, $entityname) {
    global $CFG, $OUTPUT;

    $numentity = implode('.', techproject_tree_get_upper_branch('techproject_'.$entityname, $state->reqid, true, true));
    if (techproject_count_subs('techproject_'.$entityname, $state->reqid) > 0) {
        $jshandler = 'javascript:toggle(\''.$state->reqid.'\',\'sub'.$state->reqid.'\');';
        $pixurl = $OUTPUT->pix_url('p/switch_minus', 'techproject');
        $pix = '<img name="img'.$state->reqid.'" src="'.$pixurl.'" alt="collapse" />';
        $hidesub = '<a href="'.$jshandler.'">'.$pix.'</a>';
    } else {
        $hidesub = '<img src="'.$OUTPUT->pix_url('p/empty', 'techproject').'" />';
    }

    $entitylevel = count(explode('.', $numentity)) - 1;
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $entitylevel);

    echo '<table width="100%" >';
    echo '<tr valign="top">';
    echo "<td width=\"*\" class=\"level{$entitylevel}\">$indent $hidesub $numentity $state->abstract</td>";

    echo '<td width="20%" class="validation-'.$state->status.'">';
    $validationstatus['UNTRACKED'] = get_string('untracked', 'techproject');
    $validationstatus['REFUSED'] = get_string('refused', 'techproject');
    $validationstatus['MISSING'] = get_string('missing', 'techproject');
    $validationstatus['BUGGY'] = get_string('buggy', 'techproject');
    $validationstatus['TOENHANCE'] = get_string('toenhance', 'techproject');
    $validationstatus['ACCEPTED'] = get_string('accepted', 'techproject');
    $validationstatus['REGRESSION'] = get_string('regression', 'techproject');
    echo html_writer::select($validationstatus, 'state_'.$state->id, $state->status);
    echo '</td>';
    echo '<td width="25%">';
    echo "<textarea name=\"comment_{$state->id}\" rows=\"3\" cols=\"20\">{$state->comment}</textarea>";
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}

/**
 * check milestone constraints
 *
 */
function milestone_check_constraints($project, $milestone) {
    global $CFG, $DB;

    $control = null;
    switch($project->timeunit) {
        case HOURS: {
            $plannedtime = 3600;
            break;
        }

        case HALFDAY: {
            $plannedtime = 3600 * 12;
            break;
        }

        case DAY: {
            $plannedtime = 3600 * 24;
            break ;
        }

        default:
            $plannedtime = 0;
    }

    // Checking too soon task.
    $sql = "
       SELECT
          id,
          abstract,
          MAX(taskend) as latest
       FROM
          {techproject_task}
       WHERE
          milestoneid = {$milestone->id}
       GROUP BY
          milestoneid
    ";
    $latesttask = $DB->get_record_sql($sql);
    if ($latesttask && $milestone->deadline < $latesttask->latest) {
        $control['milestonedeadline'] = get_string('assignedtaskendsafter', 'techproject').'<br/>'.userdate($latesttask->latest);
    }
    return $control;
}

/**
 *
 *
 *
 */

/**
 * a form constraint checking function
 * @param object $project the surrounding project cntext
 * @param object $task form object to be checked
 * @return a control hash array telling error statuses
 */
function task_check_constraints($project, $task) {
    global $DB;

    $control = null;
    switch($project->timeunit) {
        case HOURS: {
            $plannedtime = 3600 ;
            break ;
        }

        case HALFDAY: {
            $plannedtime = 3600 * 12;
            break ;
        }

        case DAY: {
            $plannedtime = 3600 * 24;
            break;
        }
        default:
            $plannedtime = 0;
    }

    // Checking too soon task.
    if ($task->taskstartenable && $task->taskstart < $project->projectstart) {
        $control['taskstartdate'] = get_string('tasktoosoon', 'techproject').'<br/>'.userdate($project->projectstart);
    }

    if ($task->taskstartenable && $task->milestoneid) {
        // Task too late (planned to milestone).
        $milestone = $DB->get_record('techproject_milestone', array('projectid' => $project->id, 'id' => $task->milestoneid));
        if ($milestone->deadlineenable && ($task->taskstart + $plannedtime > $milestone->deadline)) {
            $control['taskstartdate'] = get_string('taskstartsaftermilestone', 'techproject').'<br/>'.userdate($milestone->deadline);
        }

    } else if ($task->taskstartenable && ($task->taskstart + $plannedtime > $project->projectend)) {
        // Task too late (absolute).
        $control['taskstartdate'] = get_string('tasktoolate', 'techproject').'<br/>'.userdate($project->projectend);

    } else if ($task->taskendenable && $task->milestoneid) {
        // Checking too late end.
        $milestone = $DB->get_record('techproject_milestone', array('projectid' => $project->id, 'id' => $task->milestoneid));
        if ($milestone->deadlineenable && ($task->taskend > $milestone->deadline)) {
            $control['taskenddate'] = get_string('taskfinishesaftermilestone', 'techproject').'<br/>'.userdate($milestone->deadline);
        }
    } else if ($task->taskendenable && $task->taskend > $project->projectend) {
        // Checking too late end.
        $control['taskenddate'] = get_string('taskfinishestoolate', 'techproject').'<br/>'.userdate($project->projectend);

    } else if ($task->taskendenable && $task->taskstartenable && $task->taskend <= $task->taskstart) {
        // Checking switched end and start.
        $control['taskenddate'] = get_string('taskfinishesbeforeitstarts', 'techproject');
    } else if ($task->taskendenable && $task->taskstartenable && $task->taskend < $task->taskstart + $plannedtime) {
        // Checking unfeseabletask.
        $control['taskenddate'] = get_string('tasktooshort', 'techproject') . '<br/> >> ' . userdate($task->taskstart + $plannedtime);
    }
    return $control;
}

function techproject_check_startup_level($entity, $fatherid, &$level, &$startuplevelcheck) {
    global $DB;
    
    if (!$startuplevelcheck) {
        if (!$fatherid) {
            $level = 0;
        } else {
            $level = 1;
            $rec = new StdClass();
            $rec->fatherid = $fatherid;
            while ($rec->fatherid) {
                $rec = $DB->get_record('techproject_'.$entity, array('id' => $rec->fatherid), 'id, fatherid');
                $level++;
            }
        }
        $startuplevelcheck = true;
    }
}

function techproject_print_localfile($deliverable, $cmid, $type = null, $align = 'left') {
    global $CFG, $DB, $OUTPUT;

    if (!$context = context_module::instance($cmid)) {
        return '';
    }

    $fs = get_file_storage();

    $imagereturn = '';
    $output = '';

    if ($files = $fs->get_area_files($context->id, 'mod_techproject', 'localfile', $deliverable->id, 'timemodified', false)) {
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $iconimage = '<img src="'.$OUTPUT->pix_url(file_mimetype_icon($mimetype)).'" class="icon" alt="'.$mimetype.'" />';
            $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_techproject/localfile/'.$deliverable->id.'/'.$filename);

            if ($type == 'html') {
                $output .= '<a href="'.$path.'">'.$iconimage.'</a> ';
                $output .= '<a href="'.$path.'">'.s($filename).'</a>';
                $output .= '<br />';

            } else if ($type == 'text') {
                $output .= "$strattachment ".s($filename).":\n$path\n";

            } else {
                if (in_array($mimetype, array('image/gif', 'image/jpeg', 'image/png'))) {
                    // Image attachments don't get printed as links.
                    $imagereturn .= '<br /><img src="'.$path.'" alt="" />';
                } else {
                    $output .= '<a href="'.$path.'">'.$iconimage.'</a> ';
                    $output .= format_text('<a href="'.$path.'">'.s($filename).'</a>', FORMAT_HTML, array('context' => $context));
                    $output .= '<br />';
                }
            }
        }
    }

    if ($type) {
        return $output;
    } else {
        echo $output;
        return $imagereturn;
    }
}

/**
 * special user records fix for demanding fullname() function
 * caches some result for efficiency
 */
function techproject_complete_user(&$user) {
    global $DB;
    static $usercomps;

    if (empty($usercomps[$user->id])) {
        $fields = get_all_user_name_fields(true);
        $usercomps[$user->id] = $DB->get_record('user', array('id' => $user->id), $fields);
    }
    foreach ($usercomps[$user->id] AS $var => $value) {
        $user->$var = $value;
    }
}

/**
 * Adds controls for tree movement. uses a trick to avoid navigator abusive
 * caching by sending time with request.
 */
function techproject_add_entity_tree_controls($cmid, $entityname, $shortentityname, $entity, $setsize, &$link) {
    global $OUTPUT;

    if ($entity->ordering > 1) {
        $params = array('id' => $cmid, 'work' => 'up', $shortentityname.'id' => $entity->id, 'view' => $entityname.'s', 't' => time());
        $upurl = new moodle_url('/mod/techproject/view.php', $params);
        $link[] = '<a href="'.$upurl.'#node'.$entity->id.'">
             <img src="'.$OUTPUT->pix_url('/t/up').'" alt="'.get_string('up', 'techproject').'" /></a>';
    }
    if ($entity->ordering < $setsize) {
        $params = array('id' => $cmid, 'work' => 'down', $shortentityname.'id' => $entity->id, 'view' => $entityname.'s', 't' => time());
        $downurl = new moodle_url('/mod/techproject/view.php', $params);
        $link[] = '<a href="'.$downurl.'#node'.$entity->id.'">
             <img src="'.$OUTPUT->pix_url('/t/down').'" alt="'.get_string('down', 'techproject').'" /></a>';
    }
    if ($entity->fatherid != 0) {
        $params = array('id' => $cmid, 'work' => 'left', $shortentityname.'id' => $entity->id, 'view' => $entityname.'s', 't' => time());
        $lefturl = new moodle_url('/mod/techproject/view.php', $params);
        $link[] = '<a href="'.$lefturl.'#node'.$entity->id.'">
             <img src="'.$OUTPUT->pix_url('/t/left').'" alt="'.get_string('left', 'techproject').'" /></a>';
    }
    if ($entity->ordering > 1) {
        $params = array('id' => $cmid, 'work' => 'right', $shortentityname.'id' => $entity->id, 'view' => $entityname.'s', 't' => time());
        $righturl = new moodle_url('/mod/techproject/view.php', $params);
        $link[] = '<a href="'.$righturl.'#node'.$entity->id.'">
             <img src="'.$OUTPUT->pix_url('/t/right').'" alt="'.get_string('right', 'techproject').'" /></a>';
    }
}

function techproject_detail_make_sub_table($objectclass, $object, $cmid) {
    global $DB;

    // Make link tables.
    $res = $DB->get_records("techproject_{$objectclass}", array('fatherid' => $object->id));
    $linktable = array();
    if ($res) {
        foreach ($res as $anode) {
            $numrequ = implode('.', techproject_tree_get_upper_branch("techproject_{$objectclass}", $anode->id, true, true));
            $params = array('id' => $cmid, 'objectId' => $anode->id, 'objectClass' => $objectclass);
            $linkurl = new moodle_url('/mod/techproject/view.php', $params);
            $linktable[] = '<a class="browselink" href="'.$linkurl.'">'.$numrequ.' '.$anode->abstract.'</a>';
        }
    }
    return $linktable;
}
