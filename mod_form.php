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
 * This file defines the main newmodule configuration form
 * It uses the standard core Moodle (>1.8) formslib. For
 * more info about them, please visit:
 *
 * http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * The form must provide support for, at least these fields:
 *   - name: text element of 64cc max
 *
 * Also, it's usual to use these fields:
 *   - intro: one htmlarea element to describe the activity
 *            (will be showed in the list of activities of
 *             newmodule type (index.php) and in the header
 *             of the newmodule main page (view.php).
 *   - introformat: The format used to write the contents
 *             of the intro field. It automatically defaults
 *             to HTML when the htmleditor is used and can be
 *             manually selected if the htmleditor is not used
 *             (standard formats are: MOODLE, HTML, PLAIN, MARKDOWN)
 *             See lib/weblib.php Constants and the format_text()
 *             function for more info
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/techproject/locallib.php');

class mod_techproject_mod_form extends moodleform_mod {

    public function definition() {

        global $COURSE;
        $mform =& $this->_form;

        $yesnooptions[0] = get_string('no');
        $yesnooptions[1] = get_string('yes');

        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the required "intro" field to hold the description of the instance
        $this->add_intro_editor(true, get_string('introtechproject', 'techproject'));

        $startyear = date('Y', time());
        $mform->addElement('date_time_selector', 'projectstart', get_string('projectstart', 'techproject'), array('optional' => true, 'startyear' => $startyear));
        $mform->setDefault('projectstart', time());
        $mform->addElement('date_time_selector', 'projectend', get_string('projectend', 'techproject'), array('optional' => true, 'startyear' => $startyear));
        $mform->setDefault('projectend', time()+90*DAYSECS);

        $mform->addElement('date_time_selector', 'assessmentstart', get_string('assessmentstart', 'techproject'), array('optional' => true, 'startyear' => $startyear));
        $mform->setDefault('assessmentstart', time()+75*DAYSECS);
        $mform->addHelpButton('assessmentstart', 'assessmentstart', 'techproject');

        $unitoptions[HOURS] = get_string('hours', 'techproject');
        $unitoptions[HALFDAY] = get_string('halfdays', 'techproject');
        $unitoptions[DAY] = get_string('days', 'techproject');
        $mform->addElement('select', 'timeunit', get_string('timeunit', 'techproject'), $unitoptions); 

        $mform->addElement('text', 'costunit', get_string('costunit', 'techproject')); 
        $mform->setType('costunit', PARAM_TEXT);

        $mform->addElement('select', 'allownotifications', get_string('allownotifications', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('allownotifications', 'allownotifications', 'techproject');
        $mform->setType('allownotifications', PARAM_BOOL);

        /*
        $mform->addElement('select', 'enablecvs', get_string('enablecvs', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('enablecvs', 'enablecvs', 'techproject');
        $mform->setType('enablecvs', PARAM_BOOL);
        */

        $mform->addElement('select', 'useriskcorrection', get_string('useriskcorrection', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('useriskcorrection', 'useriskcorrection', 'techproject');
        $mform->setType('useriskcorrection', PARAM_BOOL);

        $mform->addElement('header', 'features', get_string('features', 'techproject'));
        $mform->addElement('checkbox', 'projectusesrequs', get_string('requirements', 'techproject')); 
        $mform->addElement('checkbox', 'projectusesspecs', get_string('specifications', 'techproject')); 
        $mform->addElement('checkbox', 'projectusesdelivs', get_string('deliverables', 'techproject')); 
        $mform->addElement('checkbox', 'projectusesvalidations', get_string('validations', 'techproject')); 

        $mform->addElement('header', 'header2', get_string('access', 'techproject'));

        $mform->addElement('select', 'guestsallowed', get_string('guestsallowed', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('guestsallowed', 'guestsallowed', 'techproject');
        $mform->setType('guestsallowed', PARAM_BOOL);

        $mform->addElement('select', 'guestscanuse', get_string('guestscanuse', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('guestscanuse', 'guestscanuse', 'techproject');
        $mform->setType('guestscanuse', PARAM_BOOL);

        $mform->addElement('select', 'ungroupedsees', get_string('ungroupedsees', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('ungroupedsees', 'ungroupedsees', 'techproject');
        $mform->setType('ungroupedsees', PARAM_BOOL);

        $mform->addElement('select', 'allowdeletewhenassigned', get_string('allowdeletewhenassigned', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('allowdeletewhenassigned', 'allowdeletewhenassigned', 'techproject');

        $mform->addElement('static', 'tudentscanchange', get_string('studentscanchange', 'techproject'), get_string('seecapabilitysettings', 'techproject')); 

        $mform->addElement('header', 'header2', get_string('grading', 'techproject'));
        $mform->addElement('select', 'teacherusescriteria', get_string('teacherusescriteria', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('teacherusescriteria', 'teacherusescriteria', 'techproject');
        $mform->setType('teacherusescriteria', PARAM_BOOL);

        $mform->addElement('select', 'autogradingenabled', get_string('autogradingenabled', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('autogradingenabled', 'autogradingenabled', 'techproject');
        $mform->setType('autogradingenabled', PARAM_BOOL);

        $mform->addElement('text', 'autogradingweight', get_string('autogradingweight', 'techproject')); 
        $mform->addHelpButton('autogradingweight', 'autogradingweight', 'techproject');
        $mform->setType('autogradingweight', PARAM_NUMBER);

        $mform->addElement('text', 'accesskey', get_string('accesskey', 'techproject')); 
        $mform->addHelpButton('accesskey', 'accesskey', 'techproject');
        $mform->setType('accesskey', PARAM_TEXT);

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}

