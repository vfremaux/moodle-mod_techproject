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

class Description_Form extends moodleform {

    var $project;
    var $mode;
    var $editoroptions;

    public function __construct($action, &$project, $mode) {
        $this->project = $project;
        $this->mode = $mode;
        parent::__construct($action);
    }

    public function definition() {
        global $COURSE;

        $mform = $this->_form;

        $modcontext = context_module::instance($this->project->cmid);

        $maxfiles = 99;                // TODO: add some setting
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting
        $this->editoroptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);

        $mform->addElement('hidden', 'id'); // cmid
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'headingid');
        $mform->setType('headingid', PARAM_INT);
        $mform->addElement('hidden', 'work');
        $mform->setType('work', PARAM_TEXT);
        $mform->setDefault('work', $this->mode);

        $mform->addElement('text', 'title', get_string('projecttitle', 'techproject'), array('size' => "100%"));
        $mform->setType('title', PARAM_CLEANHTML);

        $mform->addElement('editor', 'abstract_editor', get_string('abstract', 'techproject'));
        $mform->setType('abstract_editor', PARAM_RAW);

        $mform->addElement('editor', 'rationale_editor', get_string('rationale', 'techproject'));
        $mform->setType('rationale_editor', PARAM_RAW);

        $mform->addElement('editor', 'environment_editor', get_string('environment', 'techproject'));
        $mform->setType('environment_editor', PARAM_RAW);

        $mform->addElement('text', 'organisation', get_string('organisation', 'techproject'), array('size' => "100%"));
        $mform->setType('organisation', PARAM_CLEANHTML);

        $mform->addElement('text', 'department', get_string('department', 'techproject'), array('size' => "100%"));
        $mform->setType('department', PARAM_CLEANHTML);

        $this->add_action_buttons(true);
    }

    function set_data($defaults) {

        $context = context_module::instance($this->project->cmid);

        $abstract_draftid_editor = file_get_submitted_draft_itemid('abstract_editor');
        $currenttext = file_prepare_draft_area($abstract_draftid_editor, $context->id, 'mod_techproject', 'abstract_editor', $defaults->id, array('subdirs' => true), $defaults->abstract);
        $defaults = file_prepare_standard_editor($defaults, 'abstract', $this->editoroptions, $context, 'mod_techproject', 'abstract', $defaults->id);
        $defaults->abstract = array('text' => $currenttext, 'format' => $defaults->format, 'itemid' => $abstract_draftid_editor);

        $rationale_draftid_editor = file_get_submitted_draft_itemid('rationale_editor');
        $currenttext = file_prepare_draft_area($rationale_draftid_editor, $context->id, 'mod_techproject', 'rationale_editor', $defaults->id, array('subdirs' => true), $defaults->rationale);
        $defaults = file_prepare_standard_editor($defaults, 'rationale', $this->editoroptions, $context, 'mod_techproject', 'rationale', $defaults->id);
        $defaults->rationale = array('text' => $currenttext, 'format' => $defaults->format, 'itemid' => $rationale_draftid_editor);

        $environment_draftid_editor = file_get_submitted_draft_itemid('environment_editor');
        $currenttext = file_prepare_draft_area($environment_draftid_editor, $context->id, 'mod_techproject', 'environment_editor', $defaults->id, array('subdirs' => true), $defaults->environment);
        $defaults = file_prepare_standard_editor($defaults, 'environment', $this->editoroptions, $context, 'mod_techproject', 'environment', $defaults->id);
        $defaults->environment = array('text' => $currenttext, 'format' => $defaults->format, 'itemid' => $environment_draftid_editor);

        parent::set_data($defaults);
    }
}
