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

// Preconditions.

if (empty($project->projectusesrequs) && empty($project->projectusesspecs)) {
    $returnurl = new moodle_url('/mod/techproject/view.php', array('id' => $c->id));
    echo $OUTPUT->notification(get_string('validationrequirements', 'techproject'), $returnurl);
}

// Controller.

if ($work == 'new') {
    // Close all unclosed.
    $select = "
        projectid = ? AND
        groupid = ? AND
        dateclosed = 0
    ";
    if ($unclosedrecords = $DB->get_records_select('techproject_valid_session', $select, array($project->id, $currentgroupid))) {
        foreach ($unclosedrecords as $unclosed) {
            $unclosed->dateclosed = time();
            $DB->update_record('techproject_valid_session', $unclosed);
        }
    }
    $validation = new StdClass;
    $validation->groupid = $currentgroupid;
    $validation->projectid = $project->id;
    $validation->createdby = $USER->id;
    $validation->datecreated = time();
    $validation->dateclosed = 0;

    // Pre add validation session record.
    $validation->id = $DB->insert_record('techproject_valid_session', $validation);

    $validation->untracked = 0;
    $validation->refused = 0;
    $validation->missing = 0;
    $validation->buggy = 0;
    $validation->toenhance = 0;
    $validation->accepted = 0;
    $validation->regressions = 0;

    // Check if follow up so we need to copy previous test results as start.

    if (optional_param('followup', false, PARAM_BOOL)) {
        $select = "
            projectid = ? AND
            groupid = ?
        ";
        $params = array($project->id, $currentgroupid);
        $lastsessiondate = $DB->get_field_select('techproject_valid_session', 'MAX(datecreated)', $select, $params);
        $select = "
            datecreated = ? AND
            projectid = ? AND
            groupid = ?
        ";
        $params = array($lastsessiondate, $project->id, $currentgroupid);
        $lastsession = $DB->get_record_select('techproject_valid_session', $select, $params);
        // copy all states
        if ($states = $DB->get_records('techproject_valid_state', array('validationsessionid' => $lastsession->id))) {
            foreach ($states as $state) {
                $state->validationsessionid = $validation->id;
                $DB->insert_record('techproject_valid_state', $state);
                $validation->untracked += ($state->status == 'UNTRACKED') ? 1 : 0;
                $validation->refused += ($state->status == 'REFUSED') ? 1 : 0;
                $validation->missing += ($state->status == 'MISSING') ? 1 : 0;
                $validation->buggy += ($state->status == 'BUGGY') ? 1 : 0;
                $validation->toenhance += ($state->status == 'TOENHANCE') ? 1 : 0;
                $validation->accepted += ($state->status == 'ACCEPTED') ? 1 : 0;
                $validation->regressions += ($state->status == 'REGRESSION') ? 1 : 0;
            }
        }
    } else {
        if (@$project->projectusesrequs) {
            $select = "
                projectid = ? AND
                groupid = ?
            ";
            $items = $DB->count_records_select('techproject_requirement', $select, array($project->id, $currentgroupid));
        } else if (@$project->projectusesspecs) {
            $select = "
                projectid = ? AND
                groupid = ?
            ";
            $items = $DB->count_records_select('techproject_specification', $select, array($project->id, $currentgroupid));
        } else {
            print_error('errornotpossible', 'techproject');
        }
        $validation->untracked = $items;
    }

    // Second stage.
    $DB->update_record('techproject_valid_session', $validation);
} else if ($work == 'close') {
    $validation = new StdClass;
    $validation->id = required_param('validid', PARAM_INT);
    $validation->dateclosed = time();

    $res = $DB->update_record('techproject_valid_session', $validation);
} else if ($work == 'dodelete') {
    $validid = required_param('validid', PARAM_INT);

    // Delete all related records.
    $DB->delete_records('techproject_valid_state', array('validationsessionid' => $validid));
    $DB->delete_records('techproject_valid_session', array('id' => $validid));
}

// View.

echo $pagebuffer;

techproject_print_validations($project, $currentgroupid, 0, $cm->id);
$createvalidationstr = get_string('createvalidationsession', 'techproject');
$copyvalidationstr = get_string('copyvalidationsession', 'techproject');
if (has_capability('mod/techproject:managevalidations', context_module::instance($cm->id))) {
    echo '<p><center>';
    $params = array('id' => $cm->id, 'view' => 'validations', 'work' => 'new');
    $linkurl = new moodle_url('/mod/techproject/view.php', $params);
    echo '<a href="'.$linkurl.'">'.$createvalidationstr.'</a>';

    $params = array('id' => $cm->id, 'view' => 'validations', 'work' => 'new', 'followup' => 1);
    $linkurl = new moodle_url('/mod/techproject/view.php', $params);
    echo ' - <a href="'.$linkurl.'">'.$copyvalidationstr.'</a>';

    echo '</center></p>';
}

