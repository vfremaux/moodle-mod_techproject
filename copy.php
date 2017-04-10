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

echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/techproject/js/copy.js"></script>';

if (!has_capability('mod/techproject:manage', $context)) {
    print_error(get_string('notateacher', 'techproject'));
    return;
}

// Get groups, will be usefull at many locations.

$groups = groups_get_all_groups($course->id);

if ($work == 'docopy') {
    function techproject_protect_text_records(&$rec, $fieldlist) {
        $fields = explode(',', $fieldlist);
        foreach ($fields as $afield) {
            if (isset($rec->$afield)) {
                $rec->$afield = str_replace("'", "\\'", $rec->$afield);
            }
        }
    }

    /**
     * @return an array for all copied ids translations so foreign keys can be fixed
     */
    function techproject_unit_copy($project, $from, $to, $what, $detail = false) {
        $copied = array();

        foreach (array_keys($what) as $entitytable) {
            // Skip unchecked entites for copying.
            if (!$what[$entitytable]) {
                continue;
            }

            echo '<tr>';
            echo '<td align="left">'.get_string('copying', 'techproject').' '.get_string("{$entitytable}s", 'techproject').'...';
            $DB->delete_records("techproject_$entitytable", array('projectid' => $project->id, 'groupid' => $to));
            $select = "projectid = ? AND groupid = ?";
            if ($records = $DB->get_records_select("techproject_$entitytable", $select, array($project->id, $from))) {

                // Copying each record into target recordset.
                if ($detail) {
                    echo '<br/>';
                    $sp = '&nbsp;&nbsp;&nbsp;';
                    echo '<span class="smalltechnicals">'.$sp.'copying '.count($records)." from $entitytable</span>";
                }

                foreach ($records as $rec) {
                    $id = $rec->id;
                    if ($detail) {
                        echo '<br/>';
                        $sp = '&nbsp;&nbsp;&nbsp;';
                        echo '<span class="smalltechnicals">'.$sp.'copying item : ['. $id . '] '.@$rec->abstract.'</span>';
                    }
                    $rec->id = 0;
                    $rec->groupid = $to;
                    $fields = 'title,abstract,rationale,description,environement,organisation,department';
                    techproject_protect_text_records($rec, $fields);

                    // Unassigns users from entites in copied entities (not relevant context).
                    if (isset($rec->assignee)) {
                        $rec->assignee = 0;
                    }
                    if (isset($rec->owner)) {
                        $rec->owner = 0;
                    }
                    // If milestones are not copied, no way to keep milestone assignation.
                    if (isset($rec->milestoneid) && $what['milestone'] == 0) {
                        $rec->milestoneid = 0;
                    }
                    $insertedid = $DB->insert_record("techproject_$entitytable", $rec);
                    $copied[$entitytable][$id] = $insertedid;
                }
            }
            echo '</td>';
            echo '<td align="right"><span class="technicals">'.get_string('done', 'techproject').'</span></td>';
            echo '</tr>';
        }
        return $copied;
    }

    /**
     * this function fixes in new records (given in recordSet as a comma separated list of indexes
     * some foreign key (fKey) that should shift from an unfixedValue to a fixed value in translations
     * table.
     * @return true if no errors.
     */
    function techproject_fix_foreign_keys($project, $group, $table, $fkey, $translations, $recordset) {
        global $CFG, $DB;

        $result = 1;
        list($insql, $params) = $DB->get_in_or_equal($recordset, SQL_PARAMS_NAMED);
        foreach (array_keys($translations) as $unfixedvalue) {
            $query = "
                UPDATE
                    {techproject_{$table}}
                SET
                    $fkey = $translations[$unfixedvalue]
                WHERE
                    projectid = :projectid AND
                    $fkey = :unfixed AND
                    id $insql
            ";

            $params['projectid'] = $project->id;
            $parmas['unfixed'] = $unfixedvalue;
            $result = $result && $DB->execute($query, $params);
        }
        return $result;
    }
    $from = required_param('from', PARAM_INT);
    $to = required_param('to', PARAM_RAW);
    $detail = optional_param('detail', 0, PARAM_INT);
    $what['heading'] = optional_param('headings', 0, PARAM_INT);
    $what['requirement'] = optional_param('requs', 0, PARAM_INT);
    $what['spec_to_req'] = optional_param('specstoreq', 0, PARAM_INT);
    $what['specification'] = optional_param('specs', 0, PARAM_INT);
    $what['task_to_spec'] = optional_param('taskstospec', 0, PARAM_INT);
    $what['task_to_deliv'] = optional_param('tasktodeliv', 0, PARAM_INT);
    $what['task_dependency'] = optional_param('tasktotask', 0, PARAM_INT);
    $what['milestone'] = optional_param('miles', 0, PARAM_INT);
    $what['task'] = optional_param('tasks', 0, PARAM_INT);
    $what['deliverable'] = optional_param('deliv', 0, PARAM_INT);
    $targets = explode(',', $to);
    echo $OUTPUT->box_start('center', '70%');

    foreach ($targets as $atarget) {
        // Do copy data.
        echo '<table width="100%">';
        $copied = techproject_unit_copy($project, $from, $atarget, $what, $detail);

        // Do fix some foreign keys.
        echo '<tr><td align="left">'.get_string('fixingforeignkeys', 'techproject').'...</td><td align="right">';
        if (array_key_exists('spec_to_req', $copied) && count(array_values(@$copied['spec_to_req']))) {
            techproject_fix_foreign_keys($project, $atarget, 'spec_to_req', 'specid', $copied['specification'],
                                         array_values($copied['spec_to_req']));
            techproject_fix_foreign_keys($project, $atarget, 'spec_to_req', 'reqid', $copied['requirement'],
                                         array_values($copied['spec_to_req']));
        }
        if (array_key_exists('task_to_spec', $copied) && count(array_values(@$copied['task_to_spec']))) {
            techproject_fix_foreign_keys($project, $atarget, 'task_to_spec', 'taskid', $copied['task'],
                                         array_values($copied['task_to_spec']));
            techproject_fix_foreign_keys($project, $atarget, 'task_to_spec', 'specid', $copied['specification'],
                                         array_values($copied['task_to_spec']));
        }
        if (array_key_exists('task_to_deliv', $copied) && count(array_values(@$copied['task_to_deliv']))) {
            techproject_fix_foreign_keys($project, $atarget, 'task_to_deliv', 'taskid', $copied['task'],
                                         array_values($copied['task_to_deliv']));
            techproject_fix_foreign_keys($project, $atarget, 'task_to_deliv', 'delivid', $copied['deliverable'],
                                         array_values($copied['task_to_deliv']));
        }
        if (array_key_exists('task_dependency', $copied) && count(array_values(@$copied['task_dependency']))) {
            techproject_fix_foreign_keys($project, $atarget, 'task_dependency', 'master', $copied['task'],
                                         array_values($copied['task_dependency']));
            techproject_fix_foreign_keys($project, $atarget, 'task_dependency', 'slave', $copied['task'],
                                         array_values($copied['task_dependency']));
        }
        if (array_key_exists('milestone', $copied) &&
                array_key_exists('task', $copied) &&
                        count(array_values(@$copied['task'])) &&
                                count(array_values(@$copied['milestone']))) {
            techproject_fix_foreign_keys($project, $atarget, 'task', 'milestoneid', $copied['milestone'],
                                         array_values($copied['task']));
        }
        if (array_key_exists('milestone', $copied) &&
                array_key_exists('deliverable', $copied) &&
                        count(array_values(@$copied['deliverable'])) &&
                                count(array_values(@$copied['milestone']))) {
            techproject_fix_foreign_keys($project, $atarget, 'deliverable', 'milestoneid', $copied['milestone'],
                                         array_values($copied['deliverable']));
        }

        // Fixing fatherid values.
        if (array_key_exists('specification', $copied)) {
            techproject_fix_foreign_keys($project, $atarget, 'specification', 'fatherid',
                                         $copied['specification'], array_values($copied['specification']));
        }
        if (array_key_exists('requirement', $copied)) {
            techproject_fix_foreign_keys($project, $atarget, 'requirement', 'fatherid',
                                         $copied['requirement'], array_values($copied['requirement']));
        }
        if (array_key_exists('task', $copied)) {
            techproject_fix_foreign_keys($project, $atarget, 'task', 'fatherid', $copied['task'], array_values($copied['task']));
        }
        if (array_key_exists('deliverable', $copied)) {
            techproject_fix_foreign_keys($project, $atarget, 'deliverable', 'fatherid',
                                         $copied['deliverable'], array_values($copied['deliverable']));
        }

        // Must delete all grades in copied group.
        $DB->delete_records('techproject_assessment', array('projectid' => $project->id, 'groupid' => $atarget));
        echo '<span class="technicals">' . get_string('done', 'techproject') . '</td></tr>';
    }
    echo '</table>';
    echo $OUTPUT->box_end();
}

// Setup project copy operations by defining source and destinations.

echo $pagebuffer;

if ($work == 'what') {
    $from = required_param('from', PARAM_INT);
    $to = required_param('to', PARAM_RAW);
    // Check some inconsistancies here.
    if (in_array($from, $to)) {
        $errormessage = get_string('cannotselfcopy', 'techproject');
        $work = 'setup';
    } else {

        echo '<center>';

        echo $OUTPUT->heading(get_string('copywhat', 'techproject'));
        $toarr = array();

        foreach ($to as $atarget) {
            $toarr[] = $groups[$atarget]->name;
        }

        if ($from) {
            echo $OUTPUT->box($groups[$from]->name.' &gt;&gt; '.implode(',', $toarr), 'center');
        } else {
            echo $OUTPUT->box(get_string('groupless', 'techproject').' &gt;&gt; '.implode(',', $toarr), 'center');
        }

        echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/techproject/js/copy.js"></script>';

        echo '<form name="copywhatform" action="view.php" method="post">';
        echo '<input type="hidden" name="id" value="'.$cm->id.'"/>';
        echo '<input type="hidden" name="from" value="'.$from.'"/>';
        echo '<input type="hidden" name="to" value="'.implode(',', $to).'"/>';
        echo '<input type="hidden" name="work" value=""/>';
        echo '<table cellpadding="5">';
        echo '<tr valign="top">';
        echo '<td align="right"><b>'.get_string('what', 'techproject').'</b></td>';
        echo '<td align="left">';
        echo '<p><b>'.get_string('entities', 'techproject').'</b></p>';
        echo '<p><input type="checkbox" name="headings" value="1" checked="checked" />';

        print_string('headings', 'techproject');
        if (@$project->projectusesrequs) {
            $jshandler = 'formControl(\'requs\')';
            echo '<br/>';
            echo '<input type="checkbox" name="requs" value="1" checked="checked" onclick="'.$jshandler.'" /> ';
            echo get_string('requirements', 'techproject');
        }
        if (@$project->projectusesspecs) {
            echo '<br/>';
            $jshandler = 'formControl(\'specs\')';
            echo '<input type="checkbox" name="specs" value="1" checked="checked" onclick="'.$jshandler.'" /> ';
            echo get_string('specifications', 'techproject');
        }

        echo '<br/>';
        $jshandler = 'formControl(\'tasks\')';
        echo '<input type="checkbox" name="tasks" value="1" checked="checked" onclick="'.$jshandler.'" /> ';
        echo get_string('tasks', 'techproject');

        echo '<br/>';
        $jshandler = 'formControl(\'miles\')';
        echo '<input type="checkbox" name="miles" value="1" checked="checked" onclick="'.$jshandler.'" /> ';
        echo get_string('milestones', 'techproject');

        if (@$project->projectusesspecs) {
            echo '<br/>';
            $jshandler = 'formControl(\'deliv\')';
            echo '<input type="checkbox" name="deliv" value="1" checked="checked" onclick="'.$jshandler.'" /> ';
            echo get_string('deliverables', 'techproject');
        }

        echo '</p>';
        echo '<p><b>'.get_string('crossentitiesmappings', 'techproject').'</b></p>';

        if (@$project->projectusesrequs && @$project->projectusesspecs) {
            echo '<p>';
            echo '<input type="checkbox" name="spectoreq" value="1" checked="checked" /> ';
            echo '<span id="spectoreq_span"> '.get_string('spec_to_req', 'techproject').'</span>';
        }
        if (@$project->projectusesspecs) {
            echo '<br/>';
            echo '<input type="checkbox" name="tasktospec" value="1" checked="checked" /> ';
            echo '<span id="tasktospec_span"> '.get_string('task_to_spec', 'techproject').'</span>';
        }

        echo '<br/>';
        echo '<input type="checkbox" name="tasktotask" value="1" checked="checked" /> ';
        echo '<span id="tasktotask_span" class="">'.get_string('task_to_task', 'techproject').'</span>';

        if (@$project->projectusesdelivs) {
            echo '<br/>';
            echo '<input type="checkbox" name="tasktodeliv" value="1" checked="checked" /> ';
            echo '<span id="tasktodeliv_span"> '.get_string('task_to_deliv', 'techproject').'</span>';
        }

        echo '</p>';

        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '<p><input type="button" name="go_btn" value="'.get_string('continue').'" onclick="senddata()" />';
        echo '<input type="button" name="cancel_btn" value="'.get_string('cancel').'" onclick="cancel()" /></p>';
        echo '</form>';
        echo '</center>';
    }
}

// Copy last confirmation form.

if ($work == 'confirm') {
    $from = required_param('from', PARAM_INT);
    $to = required_param('to', PARAM_RAW);
    $copyheadings = optional_param('headings', 0, PARAM_INT);
    $copyrequirements = optional_param('requs', 0, PARAM_INT);
    $copyspecifications = optional_param('specs', 0, PARAM_INT);
    $copymilestones = optional_param('miles', 0, PARAM_INT);
    $copytasks = optional_param('tasks', 0, PARAM_INT);
    $copydeliverables = optional_param('deliv', 0, PARAM_INT);
    $copyspectoreq = optional_param('spectoreq', 0, PARAM_INT);
    $copytasktospec = optional_param('tasktospec', 0, PARAM_INT);
    $copytasktotask = optional_param('tasktotask', 0, PARAM_INT);
    $copytasktodeliv = optional_param('tasktodeliv', 0, PARAM_INT);

    echo '<center>';

    echo $OUTPUT->heading(get_string('copyconfirm', 'techproject'));
    echo $OUTPUT->box(get_string('copyadvice', 'techproject'), 'center');

    echo '<form name="confirmcopyform" action="view.php" method="post">';
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
    echo '<input type="hidden" name="from" value="'.$from.'" />';
    echo '<input type="hidden" name="to" value="'.$to.'" />';
    echo '<input type="hidden" name="headings" value="'.$copyheadings.'" />';
    echo '<input type="hidden" name="requs" value="'.$copyrequirements.'" />';
    echo '<input type="hidden" name="specs" value="'.$copyspecifications.'" />';
    echo '<input type="hidden" name="tasks" value="'.$copytasks.'" />';
    echo '<input type="hidden" name="miles" value="'.$copymilestones.'" />';
    echo '<input type="hidden" name="deliv" value="'.$copydeliverables.'" />';
    echo '<input type="hidden" name="spectoreq" value="'.$copyspectoreq.'" />';
    echo '<input type="hidden" name="tasktospec" value="'.$copytasktospec.'" />';
    echo '<input type="hidden" name="tasktotask" value="'.$copytasktotask.'" />';
    echo '<input type="hidden" name="tasktodeliv" value="'.$copytasktodeliv.'" />';
    echo '<input type="hidden" name="work" value="" />';
    echo '<input type="checkbox" name="detail" value="1" /> '._string('givedetail', 'techproject');
    echo '<input type="button" name="go_btn" value="'.get_string('continue').'" onclick="senddataconfirm()" />';
    echo '<input type="button" name="cancel_btn" value="'.get_string('cancel').'" onclick="cancelconfirm()" />';
    echo '</form>';
    echo '</center>';
}

// Copy first setup form.

if ($work == '' || $work == 'setup') {
    echo '<center>';
    echo $OUTPUT->heading(get_string('copysetup', 'techproject'));
    if (isset($errormessage)) {
        echo $OUTPUT->box("<span style=\"color:white\">$errormessage</span>", 'center', '70%', 'warning');
    }

    echo '<form name="copysetupform" action="view.php" method="post">';
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
    echo '<input type="hidden" name="work" value="" />';
    echo '<table width="90%">';
    echo '<tr valign="top">';
    echo '<td align="right"><b>'.get_string('from', 'techproject').'</b></td>';
    echo '<td align="left">';

    $fromgroups = array();
    if (!empty($groups)) {
        foreach (array_keys($groups) as $agroupid) {
            $fromgroups[$groups[$agroupid]->id] = $groups[$agroupid]->name;
        }
    }
    echo html_writer::select($fromgroups, 'from', 0 + groups_get_activity_group($cm, true), get_string('groupless', 'techproject'));

    echo '</td>';
    echo '</tr>';
    echo '<tr valign="top">';
    echo '<td align="right"><b>'.get_string('upto', 'techproject').'</b></td>';
    echo '<td align="left">';
    echo '<select name="to[]" multiple="multiple" size="6" style="width : 80%">';

    echo '<option value="0">'.get_string('groupless', 'techproject').'</option>';
    if (!empty($groups)) {
        foreach ($groups as $agroup) {
            echo '<option value="'.$agroup->id.'">'.$agroup->name.'</option>';
        }
    }

    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<input type="button" name="go_btn" value="'.get_string('continue').'" onclick="senddatasetup()" />';
    echo '</form>';
    echo '</center>';
}
