<?php //$Id: mod_form.php,v 1.1.1.1 2012-08-01 10:16:14 vf Exp $

include_once 'locallib.php';

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

class mod_techproject_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE;
        $mform =& $this->_form;

		$yesnooptions[0] = get_string('no');
		$yesnooptions[1] = get_string('yes');

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

    /// Adding the required "intro" field to hold the description of the instance
        $this->add_intro_editor(true, get_string('introtechproject', 'techproject'));

        $mform->addElement('date_time_selector', 'projectstart', get_string('projectstart', 'techproject'), array('optional'=>true));
        $mform->setDefault('projectstart', time());
        // $mform->addHelpButton('projectstart', 'projectstart', 'techproject');
        $mform->addElement('date_time_selector', 'projectend', get_string('projectend', 'techproject'), array('optional'=>true));
        $mform->setDefault('projectend', time()+90*DAYSECS);
        // $mform->addHelpButton('projectend', 'projectend', 'techproject');

        $mform->addElement('date_time_selector', 'assessmentstart', get_string('assessmentstart', 'techproject'), array('optional'=>true));
        $mform->setDefault('assessmentstart', time()+75*DAYSECS);
        $mform->addHelpButton('assessmentstart', 'assessmentstart', 'techproject');

        $unitoptions[HOURS] = get_string('hours', 'techproject');
        $unitoptions[HALFDAY] = get_string('halfdays', 'techproject');
        $unitoptions[DAY] = get_string('days', 'techproject');
        $mform->addElement('select', 'timeunit', get_string('timeunit', 'techproject'), $unitoptions); 

        $mform->addElement('text', 'costunit', get_string('costunit', 'techproject')); 

        $mform->addElement('select', 'allownotifications', get_string('allownotifications', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('allownotifications', 'allownotifications', 'techproject');

        $mform->addElement('select', 'enablecvs', get_string('enablecvs', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('enablecvs', 'enablecvs', 'techproject');

        $mform->addElement('select', 'useriskcorrection', get_string('useriskcorrection', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('useriskcorrection', 'useriskcorrection', 'techproject');

        $mform->addElement('header', 'features', get_string('features', 'techproject'));
        $mform->addElement('checkbox', 'projectusesrequs', get_string('requirements', 'techproject')); 
        $mform->addElement('checkbox', 'projectusesspecs', get_string('specifications', 'techproject')); 
        $mform->addElement('checkbox', 'projectusesdelivs', get_string('deliverables', 'techproject')); 
        $mform->addElement('checkbox', 'projectusesvalidations', get_string('validations', 'techproject')); 

		$mform->addElement('header', 'header2', get_string('access', 'techproject'));


        $mform->addElement('select', 'guestsallowed', get_string('guestsallowed', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('guestsallowed', 'guestsallowed', 'techproject');

        $mform->addElement('select', 'guestscanuse', get_string('guestscanuse', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('guestscanuse', 'guestscanuse', 'techproject');

        $mform->addElement('select', 'ungroupedsees', get_string('ungroupedsees', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('ungroupedsees', 'ungroupedsees', 'techproject');

        $mform->addElement('select', 'allowdeletewhenassigned', get_string('allowdeletewhenassigned', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('allowdeletewhenassigned', 'allowdeletewhenassigned', 'techproject');

        $mform->addElement('static', 'tudentscanchange', get_string('studentscanchange', 'techproject'), get_string('seecapabilitysettings', 'techproject')); 

		$mform->addElement('header', 'header2', get_string('grading', 'techproject'));
        $mform->addElement('select', 'teacherusescriteria', get_string('teacherusescriteria', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('teacherusescriteria', 'teacherusescriteria', 'techproject');
        $mform->addElement('select', 'autogradingenabled', get_string('autogradingenabled', 'techproject'), $yesnooptions); 
        $mform->addHelpButton('autogradingenabled', 'autogradingenabled', 'techproject');

        $mform->addElement('text', 'autogradingweight', get_string('autogradingweight', 'techproject')); 
        $mform->addHelpButton('autogradingweight', 'autogradingweight', 'techproject');

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
	}
}

?>