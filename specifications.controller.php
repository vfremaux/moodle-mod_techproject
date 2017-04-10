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
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

if ($work == 'dodelete') {
    $specid = required_param('specid', PARAM_INT);
    $oldrecord = $DB->get_record('techproject_specification', array('id' => $specid));
    techproject_tree_delete($specid, 'techproject_specification');

    // Delete related records.
    $DB->delete_records('techproject_spec_to_req', array('specid' => $specid));
    $event = \mod_techproject\event\specification_deleted::create_from_specification($project, $context, $oldrecord, $currentgroupid);
    $event->trigger();

} else if ($work == 'domove' || $work == 'docopy') {

    $ids = required_param_array('ids', PARAM_INT);
    $to = required_param('to', PARAM_ALPHA);
    $autobind = false;
    $bindtable = '';
    switch ($to) {

        case 'requs': {
            $table2 = 'techproject_requirement';
            $redir = 'requirement';
            break;
        }

        case 'requswb': {
            $table2 = 'techproject_requirement';
            $redir = 'requirement';
            $autobind = true;
            $bindtable = 'techproject_spec_to_req';
            break;
        }

        case 'specs': {
            $table2 = 'techproject_specification';
            $redir = 'specification';
            break;
        }

        case 'tasks': {
            $table2 = 'techproject_task';
            $redir = 'task';
            break;
        }

        case 'taskswb': {
            $table2 = 'techproject_task';
            $redir = 'task';
            $autobind = true;
            $bindtable = 'techproject_task_to_spec';
            break;
        }

        case 'deliv': {
            $table2 = 'techproject_deliverable';
            $redir = 'deliverable';
            break;
        }
    }
    $fields = 'description,format,abstract,projectid,groupid,ordering';
    techproject_tree_copy_set($ids, 'techproject_specification', $table2, $fields, $autobind, $bindtable);
    $event = \mod_techproject\event\specification_mutated::create_from_specification($project, $context, implode(',', $ids), $currentgroupid, $redir);
    $event->trigger();

    if ($work == 'domove') {
        // Bounce to deleteitems.
        $work = 'dodeleteitems';
        $withredirect = 1;
    } else {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject').' : '.get_string($redir, 'techproject'));
    }

} else if ($work == 'domarkastemplate') {

    $specid = required_param('specid', PARAM_INT);
    $SESSION->techproject->spectemplateid = $specid;

} else if ($work == 'doapplytemplate') {

    $specids = required_param_array('ids', PARAM_INT);
    $templateid = $SESSION->techproject->spectemplateid;
    $ignoreroot = !optional_param('applyroot', false, PARAM_BOOL);

    foreach ($specids as $specid) {
        tree_copy_rec('specification', $templateid, $specid, $ignoreroot);
    }
}

if ($work == 'dodeleteitems') {

    $ids = required_param_array('ids', PARAM_INT);
    foreach ($ids as $anitem) {
        // Save record for further cleanups and propagation.
        $oldrecord = $DB->get_record('techproject_specification', array('id' => $anitem));
        $childs = $DB->get_records('techproject_specification', array('fatherid' => $anitem));

        // Update fatherid in childs.
        $query = "
            UPDATE
                {techproject_specification}
            SET
                fatherid = $oldrecord->fatherid
            WHERE
                fatherid = $anitem
        ";
        $DB->execute($query);

        $DB->delete_records('techproject_specification', array('id' => $anitem));

        // Delete all related records.
        $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'specid' => $anitem));
        $DB->delete_records('techproject_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'specid' => $anitem));

        $event = \mod_techproject\event\specification_deleted::create_from_specification($project, $context, $oldrecord, $currentgroupid);
        $event->trigger();
    }

    if (isset($withredirect) && $withredirect) {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }

} else if ($work == 'doclearall') {

    // Delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_specification', array('projectid' => $project->id));
    $DB->delete_records('techproject_task_to_spec', array('projectid' => $project->id));
    $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id));
    $event = \mod_techproject\event\specification_cleared::create_for_group($project, $context, $currentgroupid);
    $event->trigger();

} else if ($work == 'doexport') {

    $ids = required_param_array('ids', PARAM_INT);
    $idlist = implode("','", $ids);
    $select = "
       id IN ('$idlist')
    ";
    $specifications = $DB->get_records_select('techproject_specification', $select);
    $priorities = $DB->get_records('techproject_priority', array('projectid' => $project->id));
    if (empty($priorities)) {
        $priorities = $DB->get_records('techproject_priority', array('projectid' => 0));
    }
    $severities = $DB->get_records('techproject_severity', array('projectid' => $project->id));
    if (empty($severities)) {
        $severities = $DB->get_records('techproject_severity', array('projectid' => 0));
    }
    $complexities = $DB->get_records('techproject_complexity', array('projectid' => $project->id));
    if (empty($complexities)) {
        $complexities = $DB->get_records('techproject_complexity', array('projectid' => 0));
    }
    include($CFG->dirroot.'/mod/techproject/xmllib.php');
    $xmlpriorities = recordstoxml($priorities, 'priority_option', '', false, 'techproject');
    $xmlseverities = recordstoxml($severities, 'severity_option', '', false, 'techproject');
    $xmlcomplexities = recordstoxml($complexities, 'complexity_option', '', false, 'techproject');
    $xml = recordstoxml($specifications, 'specification', $xmlpriorities.$xmlseverities.$xmlcomplexities, true, null);
    $escaped = str_replace('<', '&lt;', $xml);
    $escaped = str_replace('>', '&gt;', $escaped);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    echo $OUTPUT->simple_box("<pre>$escaped</pre>");
    echo $OUTPUT->continue_button("view.php?view=specifications&amp;id=$cm->id");
    return;

} else if ($work == 'up') {

    $specid = required_param('specid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid, $specid, 'techproject_specification');

} else if ($work == 'down') {

    $specid = required_param('specid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid, $specid, 'techproject_specification');

} else if ($work == 'left') {

    $specid = required_param('specid', PARAM_INT);
    techproject_tree_left($project, $currentgroupid, $specid, 'techproject_specification');

} else if ($work == 'right') {

    $specid = required_param('specid', PARAM_INT);
    techproject_tree_right($project, $currentgroupid, $specid, 'techproject_specification');

}
