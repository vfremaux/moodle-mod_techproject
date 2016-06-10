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

defined('MOODLE_INTERNAL') || die();

/**
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

if ($work == 'delete') {
    $specid = required_param('specid', PARAM_INT);
    $oldRecord = $DB->get_record('techproject_specification', array('id' => $specid));
    techproject_tree_delete($specid, 'techproject_specification');

    // delete related records
    $DB->delete_records('techproject_spec_to_req', array('specid' => $specid));
    // add_to_log($course->id, 'techproject', 'changespecification', "view.php?id=$cm->id&amp;view=specifications&amp;group={$currentgroupid}", 'delete', $cm->id);
    $event = \mod_techproject\event\specification_deleted::create_from_specification($project, $context, $oldRecord, $currentgroupid);
    $event->trigger();

} elseif ($work == 'domove' || $work == 'docopy') {

    $ids = required_param_array('ids', PARAM_INT);
    $to = required_param('to', PARAM_ALPHA);
    $autobind = false;
    $bindtable = '';
    switch($to) {

        case 'requs':
            $table2 = 'techproject_requirement'; 
            $redir = 'requirement';
            break;

        case 'requswb':
            $table2 = 'techproject_requirement'; 
            $redir = 'requirement'; 
            $autobind = true; 
            $bindtable = 'techproject_spec_to_req';
            break;

        case 'specs':
            $table2 = 'techproject_specification'; 
            $redir = 'specification'; 
            break;

        case 'tasks':
            $table2 = 'techproject_task'; 
            $redir = 'task';
            break;

        case 'taskswb':
            $table2 = 'techproject_task'; 
            $redir = 'task'; 
            $autobind = true ; 
            $bindtable = 'techproject_task_to_spec';
            break;
        case 'deliv' : 
            $table2 = 'techproject_deliverable'; 
            $redir = 'deliverable'; 
            break;
    }
    techproject_tree_copy_set($ids, 'techproject_specification', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
    // add_to_log($course->id, 'techproject', "change{$redir}", "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentgroupid}", 'copy/move', $cm->id);
    $event = \mod_techproject\event\specification_mutated::create_from_specification($project, $context, implode(',', $ids), $currentgroupid, $redir);
    $event->trigger();

    if ($work == 'domove') {
        // bounce to deleteitems
        $work = 'dodeleteitems';
        $withredirect = 1;
    } else {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject').' : '.get_string($redir, 'techproject'));
    }

} elseif ($work == 'domarkastemplate') {
    
    $specid = required_param('specid', PARAM_INT);
    $SESSION->techproject->spectemplateid = $specid;

} elseif ($work == 'doapplytemplate') {

    $specids = required_param_array('ids', PARAM_INT);
    $templateid = $SESSION->techproject->spectemplateid;
    $ignoreroot = !optional_param('applyroot', false, PARAM_BOOL);

    foreach($specids as $specid){
        tree_copy_rec('specification', $templateid, $specid, $ignoreroot);
    }
}

if ($work == 'dodeleteitems') {

    $ids = required_param_array('ids', PARAM_INT);
    foreach ($ids as $anItem) {
        // Save record for further cleanups and propagation.
        $oldRecord = $DB->get_record('techproject_specification', array('id' => $anItem));
        $childs = $DB->get_records('techproject_specification', array('fatherid' => $anItem));

        // Update fatherid in childs.
        $query = "
            UPDATE
                {techproject_specification}
            SET
                fatherid = $oldRecord->fatherid
            WHERE
                fatherid = $anItem
        ";
        $DB->execute($query);

        $DB->delete_records('techproject_specification', array('id' => $anItem));

        // Delete all related records.
        $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'specid' => $anItem));
        $DB->delete_records('techproject_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'specid' => $anItem));

        $event = \mod_techproject\event\specification_deleted::create_from_specification($project, $context, $oldRecord, $currentgroupid);
        $event->trigger();
    }
    //add_to_log($course->id, 'techproject', 'deletespecification', "view.php?id={$cm->id}&amp;view=specifications&amp;group={$currentgroupid}", 'deleteItems', $cm->id);

    if (isset($withredirect) && $withredirect) {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }

} elseif ($work == 'doclearall') {

    // Delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_specification', array('projectid' => $project->id));
    $DB->delete_records('techproject_task_to_spec', array('projectid' => $project->id));
    $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id));
    // add_to_log($course->id, 'techproject', 'changespecification', "view.php?id={$cm->id}&amp;view=specifications&amp;group={$currentgroupid}", 'clear', $cm->id);
    $event = \mod_techproject\event\specification_cleared::create_for_group($project, $context, $currentgroupid);
    $event->trigger();

} elseif ($work == 'doexport') {

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
    include "xmllib.php";
    $xmlpriorities = recordstoxml($priorities, 'priority_option', '', false, 'techproject');
    $xmlseverities = recordstoxml($severities, 'severity_option', '', false, 'techproject');
    $xmlcomplexities = recordstoxml($complexities, 'complexity_option', '', false, 'techproject');
    $xml = recordstoxml($specifications, 'specification', $xmlpriorities.$xmlseverities.$xmlcomplexities, true, null);
    $escaped = str_replace('<', '&lt;', $xml);
    $escaped = str_replace('>', '&gt;', $escaped);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    echo $OUTPUT->simple_box("<pre>$escaped</pre>");
    // add_to_log($course->id, 'techproject', 'readspecification', "view.php?id={$cm->id}&amp;view=specifications&amp;group={$currentgroupid}", 'export', $cm->id);
    echo $OUTPUT->continue_button("view.php?view=specifications&amp;id=$cm->id");
    return;

} elseif ($work == 'up') {

    $specid = required_param('specid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid,$specid, 'techproject_specification');

} elseif ($work == 'down') {

    $specid = required_param('specid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid,$specid, 'techproject_specification');

} elseif ($work == 'left') {

    $specid = required_param('specid', PARAM_INT);
    techproject_tree_left($project, $currentgroupid,$specid, 'techproject_specification');

} elseif ($work == 'right') {

    $specid = required_param('specid', PARAM_INT);
    techproject_tree_right($project, $currentgroupid,$specid, 'techproject_specification');

}
