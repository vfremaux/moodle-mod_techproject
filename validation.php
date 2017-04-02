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

$defaultformat = FORMAT_MOODLE;

// Controller.

$validsessid = required_param('validid', PARAM_INT);
if (!$validsession = $DB->get_record('techproject_valid_session', array('id' => $validsessid))) {
    print_error('errorbadvalidsessionid', 'techproject');
}

if ($formdata = data_submitted()) {
    $statekeys = preg_grep('/state_\d+/', array_keys($_POST));
    if (!empty($statekeys)) {
        foreach ($statekeys as $statekey) {
            $stateid = str_replace('state_', '', $statekey);
            $staterec = $DB->get_record('techproject_valid_state', array('id' => $stateid));
            $staterec->status = clean_param($_POST[$statekey], PARAM_TEXT);
            $staterec->comment = clean_param($_POST['comment_'.$stateid], PARAM_TEXT);
            $staterec->validatorid = $USER->id;
            $staterec->lastchangedate = time();
            $DB->update_record('techproject_valid_state', $staterec);
        }
    }
}
echo $pagebuffer;

echo $OUTPUT->heading(get_string('updatevalidation', 'techproject'));

techproject_print_validation_states_form($validsessid, $project, $currentgroupid, 0, $cm->id);

echo '<br/>';
echo '<center>';
echo '<hr>';
$params = array('id' => $cm->id, 'view' => 'validations');
$buttonurl = new moodle_url('/mod/techproject/view.php', $params);
echo $OUTPUT->single_button($buttonurl, get_string('backtosessions', 'techproject'), 'get');
echo '</center>';
