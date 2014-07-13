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
 * Ajax receptor for updating collapse status.
 * when Moodle enables ajax, will also, when expanding, return all the underlying div structure
 *
 * @package mod-techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/07/22
 * @version phase2
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require('../../../config.php');
require_once($CFG->dirroot.'/mod/techproject/locallib.php');

$id = required_param('id', PARAM_INT);   // Course module id.
$entity = required_param('entity', PARAM_ALPHA);
$entryid = required_param('entryid', PARAM_INT);
$state = required_param('state', PARAM_INT);

// Get some useful stuff...
if (! $cm = get_coursemodule_from_id('techproject', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}
if (! $project = $DB->get_record('techproject', array('id' => $cm->instance))) {
    print_error('invalidtechprojectid', 'techproject');
}

$group = 0 + groups_get_course_group($course, true);

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
if ($state) {
    $collapse = new StdClass();
    $collapse->userid = $USER->id;
    $collapse->projectid = $project->id;
    $collapse->entryid = $entryid;
    $collapse->entity = $entity;
    $collapse->collapsed = 1;
    $DB->insert_record('techproject_collapse', $collapse);
} else {
    $DB->delete_records('techproject_collapse', array('userid' => $USER->id, 'entryid' => $entryid, 'entity' => $entity));

    // Prepare for showing branch.
    if ($CFG->enableajax) {
        $printfuncname = "techproject_print_{$entity}";
        $printfuncname($project, $group, $entryid, $cm->id);
    }
}
