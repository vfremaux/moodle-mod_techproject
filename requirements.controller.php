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

if ($work == 'dodelete') {
    $requid = required_param('requid', PARAM_INT);
    $oldRecord = $DB->get_record('techproject_requirement', array('id' => $requid));
    techproject_tree_delete($requid, 'techproject_requirement');

    // delete all related records
    $DB->delete_records('techproject_spec_to_req', array('reqid' => $requid));
    // add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentgroupid}", 'delete', $cm->id);
    $event = \mod_techproject\event\requirement_deleted::create_from_requirement($project, $context, $oldRecord, $currentgroupid);
    $event->trigger();
}
else if ($work == 'domove' || $work == 'docopy') {
    $ids = required_param_array('ids', PARAM_INT);
    $to = required_param('to', PARAM_ALPHA);
    $autobind = false;
    $bindtable = '';
    switch($to){
        case 'specs' :
            $table2 = 'techproject_specification'; 
            $redir = 'specification'; 
            $autobind = false;
            break;

        case 'specswb':
            $table2 = 'techproject_specification'; 
            $redir = 'specification'; 
            $autobind = true;
            $bindtable = 'techproject_spec_to_req';
            break;

        case 'tasks':
            $table2 = 'techproject_task'; 
            $redir = 'task'; 
            break;

        case 'deliv':
            $table2 = 'techproject_deliverable'; 
            $redir = 'deliverable'; 
            break;

        default:
            print_error('badcopycase', 'techproject', new moodle_url('/mod/techproject/view.php', array('id' => $cm->id)));
    }
    techproject_tree_copy_set($ids, 'techproject_requirement', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
    // add_to_log($course->id, 'techproject', "change{$redir}", "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentgroupid}", 'delete', $cm->id);
    $event = \mod_techproject\event\requirement_mutated::create_from_requirement($project, $context, implode(',', $ids), $currentgroupid, $redir);
    $event->trigger();

    if ($work == 'domove') {
        // bounce to deleteitems
        $work = 'dodeleteitems';
        $withredirect = 1;
    } else {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject').' : '.get_string($redir, 'techproject'));
    }
}
if ($work == 'dodeleteitems') {
    $ids = required_param_array('ids', PARAM_INT);

    foreach ($ids as $anItem) {

        // Save record for further cleanups and propagation.
        $oldRecord = $DB->get_record('techproject_requirement', array('id' => $anItem));
        $childs = $DB->get_records('techproject_requirement', array('fatherid' => $anItem));

        // Update fatherid in childs.
        $query = "
            UPDATE
                {techproject_requirement}
            SET
                fatherid = $oldRecord->fatherid
            WHERE
                fatherid = $anItem
        ";
        $DB->execute($query);

        // Delete record for this item.
        $DB->delete_records('techproject_requirement', array('id' => $anItem));

        // Delete all related records for this item.
        $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentgroupid, 'reqid' => $anItem));
        $event = \mod_techproject\event\requirement_deleted::create_from_requirement($project, $context, $oldRecord, $currentgroupid);
        $event->trigger();
    }

    if (isset($withredirect) && $withredirect) {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }

} else if ($work == 'doclearall') {

    // delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_requirement', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    //add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentgroupid}", 'clear', $cm->id);
    $event = \mod_techproject\event\requirement_cleared::create_for_group($project, $context, $currentgroupid);
    $event->trigger();

} else if ($work == 'doexport') {

    $ids = required_param_array('ids', PARAM_INT);
    $idlist = implode("','", $ids);
    $select = "
       id IN ('$idlist')
    ";
    $requirements = $DB->get_records_select('techproject_requirement', $select);
    $strengthes = $DB->get_records_select('techproject_qualifier', " projectid = $project->id AND domain = 'strength' ");
    if (empty($strenghes)){
        $strengthes = $DB->get_records_select('techproject_qualifier', " projectid = 0 AND domain = 'strength' ");
    }
    include "xmllib.php";
    $xmlstrengthes = recordstoxml($strengthes, 'strength', '', false, 'techproject');
    $xml = recordstoxml($requirements, 'requirement', $xmlstrengthes);
    $escaped = str_replace('<', '&lt;', $xml);
    $escaped = str_replace('>', '&gt;', $escaped);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    echo $OUTPUT->simple_box("<pre>$escaped</pre>");
    // add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentgroupid}", 'export', $cm->id);

    echo $OUTPUT->continue_button(new moodle_url('/mod/techproject/view.php', array('view' => 'requirements', 'id' => $cm->id)));
    return;

} else if ($work == 'up') {

    $requid = required_param('requid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid, $requid, 'techproject_requirement');

} else if ($work == 'down') {

    $requid = required_param('requid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid, $requid, 'techproject_requirement');

} else if ($work == 'left') {

    $requid = required_param('requid', PARAM_INT);
    techproject_tree_left($project, $currentgroupid, $requid, 'techproject_requirement');

} else if ($work == 'right') {

    $requid = required_param('requid', PARAM_INT);
    techproject_tree_right($project, $currentgroupid, $requid, 'techproject_requirement');

}
