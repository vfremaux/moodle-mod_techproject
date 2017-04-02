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
    $requid = required_param('requid', PARAM_INT);
    $oldrecord = $DB->get_record('techproject_requirement', array('id' => $requid));
    techproject_tree_delete($requid, 'techproject_requirement');

    // Delete all related records.
    $DB->delete_records('techproject_spec_to_req', array('reqid' => $requid));
    $event = \mod_techproject\event\requirement_deleted::create_from_requirement($project, $context, $oldrecord, $currentgroupid);
    $event->trigger();
} else if ($work == 'domove' || $work == 'docopy') {
    $ids = required_param_array('ids', PARAM_INT);
    $to = required_param('to', PARAM_ALPHA);
    $autobind = false;
    $bindtable = '';

    switch ($to) {
        case 'specs' : {
            $table2 = 'techproject_specification';
            $redir = 'specification';
            $autobind = false;
            break;
        }

        case 'specswb': {
            $table2 = 'techproject_specification';
            $redir = 'specification';
            $autobind = true;
            $bindtable = 'techproject_spec_to_req';
            break;
        }

        case 'tasks': {
            $table2 = 'techproject_task';
            $redir = 'task';
            break;
        }

        case 'deliv': {
            $table2 = 'techproject_deliverable';
            $redir = 'deliverable';
            break;
        }

        default:
            print_error('badcopycase', 'techproject', new moodle_url('/mod/techproject/view.php', array('id' => $cm->id)));
    }

    $fields = 'description,format,abstract,projectid,groupid,ordering';
    techproject_tree_copy_set($ids, 'techproject_requirement', $table2, $fields, $autobind, $bindtable);
    $event = \mod_techproject\event\requirement_mutated::create_from_requirement($project, $context, implode(',', $ids), $currentgroupid, $redir);
    $event->trigger();

    if ($work == 'domove') {
        // Bounce to deleteitems.
        $work = 'dodeleteitems';
        $withredirect = 1;
    } else {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject').' : '.get_string($redir, 'techproject'));
    }
}
if ($work == 'dodeleteitems') {
    $ids = required_param_array('ids', PARAM_INT);

    foreach ($ids as $anitem) {

        // Save record for further cleanups and propagation.
        $oldrecord = $DB->get_record('techproject_requirement', array('id' => $anitem));
        $childs = $DB->get_records('techproject_requirement', array('fatherid' => $anitem));

        // Update fatherid in childs.
        $query = "
            UPDATE
                {techproject_requirement}
            SET
                fatherid = $oldrecord->fatherid
            WHERE
                fatherid = $anitem
        ";
        $DB->execute($query);

        // Delete record for this item.
        $DB->delete_records('techproject_requirement', array('id' => $anitem));

        // Delete all related records for this item.
        $params = array('projectid' => $project->id, 'groupid' => $currentgroupid, 'reqid' => $anitem);
        $DB->delete_records('techproject_spec_to_req', $params);
        $event = \mod_techproject\event\requirement_deleted::create_from_requirement($project, $context, $oldrecord, $currentgroupid);
        $event->trigger();
    }

    if (isset($withredirect) && $withredirect) {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }

} else if ($work == 'doclearall') {

    // Delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_requirement', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $DB->delete_records('techproject_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentgroupid));
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
    if (empty($strenghes)) {
        $strengthes = $DB->get_records_select('techproject_qualifier', " projectid = 0 AND domain = 'strength' ");
    }
    include($CFG->dirroot.'/mod/techproject/xmllib.php');
    $xmlstrengthes = recordstoxml($strengthes, 'strength', '', false, 'techproject');
    $xml = recordstoxml($requirements, 'requirement', $xmlstrengthes);
    $escaped = str_replace('<', '&lt;', $xml);
    $escaped = str_replace('>', '&gt;', $escaped);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    echo $OUTPUT->simple_box("<pre>$escaped</pre>");

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
