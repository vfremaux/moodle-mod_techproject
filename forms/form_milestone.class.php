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

require_once($CFG->libdir.'/formslib.php');

class Milestone_Form extends moodleform {

    var $mode;
    var $current;
    var $project;

    public function __construct($action, &$project, $mode, $mileid) {
        global $DB;

        $this->mode = $mode;
        $this->project = $project;
        if ($mileid) {
            $this->current = $DB->get_record('techproject_milestone', array('id' => $mileid));
        }
        parent::__construct($action);
    }

    public function definition(){
        global $COURSE, $DB;

        $mform = $this->_form;
        
        $currentGroup = 0 + groups_get_course_group($COURSE);

        $modcontext = context_module::instance($this->project->cmid);

        $maxfiles = 99;                // TODO: add some setting
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting
        $this->descriptionoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'milestoneid');
        $mform->setType('milestoneid', PARAM_INT);
        $mform->addElement('hidden', 'work');
        $mform->setType('work', PARAM_TEXT);
        $mform->setDefault('work', $this->mode);

        $mform->addElement('text', 'abstract', get_string('milestonetitle', 'techproject'), array('size' => "100%"));
        $mform->setType('abstract', PARAM_CLEANHTML);

        $mform->addElement('editor', 'description_editor', get_string('description', 'techproject'), null, $this->descriptionoptions);                

        $startyear = date('Y', time());
        $mform->addElement('date_time_selector', 'deadline', get_string('deadline', 'techproject'), array('optional' => true, 'startyear' => $startyear));

        $this->add_action_buttons(true);
    }

    function set_data($defaults) {

        $context = context_module::instance($this->project->cmid);

        $draftid_editor = file_get_submitted_draft_itemid('description_editor');
        $currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_techproject', 'description_editor', $defaults->id, array('subdirs' => true), $defaults->description);
        $defaults = file_prepare_standard_editor($defaults, 'description', $this->descriptionoptions, $context, 'mod_techproject', 'milestonedescription', $defaults->id);
        $defaults->description = array('text' => $currenttext, 'format' => $defaults->descriptionformat, 'itemid' => $draftid_editor);

        parent::set_data($defaults);
    }
}
