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
 * @package mod-techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

echo $pagebuffer;

if (!has_capability('mod/techproject:manage', $context)) {
    print_error(get_string('notateacher', 'techproject'));
    return;
}

if ($work == 'update') {
    $criterion = new StdClass;
    $criterion->id = required_param('itemid', PARAM_INT);
    $criterion->projectid = $project->id;
    $criterion->criterion = required_param('criterion', PARAM_ALPHANUM);
    $criterion->label = required_param('label', PARAM_TEXT);
    $criterion->weight = required_param('weight', PARAM_INT);
    $criterion->isfree = optional_param('isfree', 0, PARAM_INT);
    if ($DB->get_record('techproject_criterion', array('id' => $criterion->id))) {
        $DB->update_record('techproject_criterion', $criterion);
    } else {
        $DB->insert_record('techproject_criterion', $criterion);
    }
} else if ($work == 'doconfirmdelete') {
    $criterion = new StdClass;
    $criterion->id = required_param('item', PARAM_INT);
    $criterion->isfree = optional_param('isfree', 0, PARAM_INT);
    if ($DB->get_record('techproject_criterion', array('id' => $criterion->id))) {
        $DB->delete_records('techproject_criterion', array('id' => $criterion->id));
        $params = array('projectid' => $project->id, 'criterion' => $criterion->id);
        $DB->delete_records('techproject_assessment', $params);
    }
}
if ($work == 'dodelete') {
    $criterion = new StdClass;
    $criterion->id = required_param('item', PARAM_INT);
    $criterion->isfree = optional_param('isfree', 0, PARAM_INT);
    echo $OUTPUT->heading(get_string('confirmdeletecriteria', 'techproject'));
    echo $OUTPUT->box(get_string('criteriadeleteadvice', 'techproject'), 'center', '70%');

    echo $renderer->criteria_delete_form_script();
    echo '<center>';
    echo $renderer->criteria_delete_form($cm, $criterion);
    echo '</center>';

    // Criteria update form ***************************************************.

} else {
    $freecriteriaset = $DB->get_records_select('techproject_criterion', "projectid = {$project->id} AND isfree = 1");
    $criteriaset = $DB->get_records_select('techproject_criterion', "projectid = {$project->id} AND isfree = 0");

    echo '<center>';
    echo '<table width="80%" cellspacing="10" style="padding : 10px">';
    echo '<tr>';
    echo '<td valign="top">';

    echo $OUTPUT->heading(get_string('freecriteriaset', 'techproject').' '.$OUTPUT->help_icon('freecriteriaset', 'techproject', false));
    echo $renderer->criteria_form_script('freecriteria');
    echo $renderer->criteria_form('freecriteria', $freecriteriaset, $cm);

    echo '</td>';
    echo '<td valign="top">';

    echo $OUTPUT->heading(get_string('itemcriteriaset', 'techproject').' '.$OUTPUT->help_icon('itemcriteriaset', 'techproject', false));
    echo $renderer->criteria_form_script('criteria');
    echo $renderer->criteria_form('criteria', $criteriaset, $cm);

    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</center>';
}

