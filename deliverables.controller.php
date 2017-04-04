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

// Controller.

if ($work == 'dodelete') {

    $delivid = required_param('delivid', PARAM_INT);
    $oldRecord = $DB->get_record('techproject_deliverable', array('id' => $delivid));
    techproject_tree_delete($delivid, 'techproject_deliverable');
    $event = \mod_techproject\event\deliverable_deleted::create_from_deliverable($project, $context, $oldRecord, $currentgroupid);
    $event->trigger();

} else if ($work == 'domove' || $work == 'docopy') {

    $ids = required_param_array('ids', PARAM_INT);
    $to = required_param('to', PARAM_ALPHA);

    switch ($to) {
        case 'requs':
            $table2 = 'techproject_requirement';
            $redir = 'requirement';
            break;

        case 'specs':
            $table2 = 'techproject_specification';
            $redir = 'specification';
            break;

        case 'tasks': 
            $table2 = 'techproject_task';
            $redir = 'task';
            break;

        case 'deliv':
            $table2 = 'techproject_deliverable';
            $redir = 'deliverable';
            break;
    }
    techproject_tree_copy_set($ids, 'techproject_deliverable', $table2);
    // add_to_log($course->id, 'techproject', 'change{$redir}', "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentgroupid}", 'copy/move', $cm->id);
    $event = \mod_techproject\event\deliverable_mutated::create_from_deliverable($project, $context, $olddeliverable, $currentgroupid, $redir);
    $event->trigger();

    if ($work == 'domove') {
        // bounce to deleteitems
        $work = 'dodeleteitems';
        $withredirect = 1;
    } else {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject') . get_string($redir, 'techproject'));
    }
}

if ($work == 'dodeleteitems') {

    $ids = required_param_array('ids', PARAM_INT);

    foreach ($ids as $anItem) {
        // save record for further cleanups and propagation
        $oldRecord = $DB->get_record('techproject_deliverable', array('id' => $anItem));
        $childs = $DB->get_records('techproject_deliverable', array('fatherid' => $anItem));

        // Update fatherid in childs.
        $query = "
            UPDATE
                {techproject_deliverable}
            SET
                fatherid = $oldRecord->fatherid
            WHERE
                fatherid = $anItem
        ";
        $DB->execute($query);
        $DB->delete_records('techproject_deliverable', array('id' => $anItem));

        // Delete all related records.

        $DB->delete_records('techproject_task_to_deliv', array('delivid' => $anItem));
    }

    // add_to_log($course->id, 'techproject', 'changedeliverable', "view.php?id={$cm->id}&amp;view=deliverable&amp;group={$currentgroupid}", 'deleteItems', $cm->id);
    $event = \mod_techproject\event\deliverable_deleted::create_from_deliverable($project, $context, $oldRecord, $currentgroupid);
    $event->trigger();

    if (isset($withredirect) && $withredirect) {
        $redirecturl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'view' => $redir.'s'));
        redirect($redirecturl, get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
    }

} else if ($work == 'doclearall') {

    // Delete all records. POWERFUL AND DANGEROUS COMMAND.
    $DB->delete_records('techproject_deliverable', array('projectid' => $project->id));
    $event = \mod_techproject\event\deliverable_cleared::create_for_group($project, $context, $currentgroupid);
    $event->trigger();

} else if ($work == 'doexport') {

    $ids = required_param_array('ids', PARAM_INT);
    $idlist = implode("','", $ids);

    $select = "
        id IN ('$idlist')
    ";

    $deliverables = $DB->get_records_select('techproject_deliverable', $select);
    $worktypes = techproject_get_options('delivstatus', $this->project->id);

    include_once($CFG->dirroot.'/mod/techproject/xmllib.php');

    $xmldelivstatusses = recordstoxml($delivstatusses, 'deliv_status_option', '', false, 'techproject');
    $xml = recordstoxml($deliverables, 'deliverable', $xmldelivstatusses, true, null);
    $escaped = str_replace('<', '&lt;', $xml);
    $escaped = str_replace('>', '&gt;', $escaped);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    echo $OUTPUT->simple_box("<pre>$escaped</pre>");
    // add_to_log($course->id, 'techproject', 'readdeliverable', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentgroupid}", 'export', $cm->id);
    $viewurl = new moodle_url('/mod/techproject/view.php', array('view' => 'deliverables', 'id' => $cm->id));
    echo $OUTPUT->continue_button($viewurl);
    return;

} else if ($work == 'up') {
    $delivid = required_param('delivid', PARAM_INT);
    techproject_tree_up($project, $currentgroupid, $delivid, 'techproject_deliverable');
} else if ($work == 'down') {
    $delivid = required_param('delivid', PARAM_INT);
    techproject_tree_down($project, $currentgroupid, $delivid, 'techproject_deliverable');
} else if ($work == 'left') {
    $delivid = required_param('delivid', PARAM_INT);
    techproject_tree_left($project, $currentgroupid,$delivid, 'techproject_deliverable');
} else if ($work == 'right') {
    $delivid = required_param('delivid', PARAM_INT);
    techproject_tree_right($project, $currentgroupid,$delivid, 'techproject_deliverable');
}

