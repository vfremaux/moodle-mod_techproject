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

require('../../../config.php');
require_once($CFG->dirroot.'/mod/techproject/locallib.php');
require_once($CFG->dirroot.'/mod/techproject/treelib.php');

$id = required_param('id', PARAM_INT); // course id
$projectid = required_param('project', PARAM_INT);
$groupid = required_param('group', PARAM_INT);
$target = required_param('target', PARAM_TEXT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    die("Error : Invalid Course ID");
}

$cm = get_coursemodule_from_instance('techproject', $projectid, $id);

require_login($course, $cm);

$parent = 0;

switch ($target) {
    case 'reqs':
    case 'reqswb':
        $targettable = 'requirement';
        break;
    case 'specs':
    case 'specswb':
        $targettable = 'specification';
        break;
    case 'tasks':
    case 'taskswb':
        $targettable = 'task';
        break;
    case 'deliv':
    case 'delivwb':
        $targettable = 'deliverable';
        break;
}

// echo "techproject_$targettable";

$targettree = techproject_get_tree_options('techproject_'.$targettable, $projectid, $groupid);

echo '<select name="parent">';
echo '<option value="0">'.get_string('rootnode', 'techproject').'</option>';
foreach ($targettree as $anode) {
    echo "<option value=\"{$anode->id}\">{$anode->ordering} - ".shorten_text($anode->abstract, 90)."</option>";
}
echo "</select>";
