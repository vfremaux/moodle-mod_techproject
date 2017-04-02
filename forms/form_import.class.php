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

require_once($CFG->libdir.'/formslib.php');

class Import_Form extends moodleform {

    protected $fileoptions;

    public function definition() {
        global $COURSE;

        $mform = $this->_form;

        $maxfiles = 1;                // TODO: add some setting.
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting.
        $this->fileoptions = array('subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Current groupid.
        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        $options = array('requs' => get_string('requirements', 'techproject'),
                         'specs' => get_string('specifications', 'techproject'),
                         'tasks' => get_string('tasks', 'techproject'),
                         'deliv' => get_string('deliverables', 'techproject'));

        $mform->addElement('header', 'importhdr', get_string('imports', 'techproject'));

        $mform->addElement('select', 'entitytype', get_string('entitytype', 'techproject'), $options);
        $mform->addRule('entitytype', '', 'required');

        $mform->addElement('filepicker', 'entityfile', get_string('importdata', 'techproject'), $this->fileoptions);
        $mform->addHelpButton('entityfile', 'importdata', 'techproject');

        if (has_capability('mod/techproject:manage', $this->_customdata['context'])) {

            $mform->addElement('header', 'exporthdr', get_string('exports', 'techproject'));

            $group[] = $mform->createElement('filepicker', 'xslfile', get_string('xslfile', 'techproject'), $this->fileoptions);
            $group[] = $mform->createElement('advcheckbox', 'clearxslfile', '', get_string('clearcustomxslsheet', 'techproject'));
            $mform->addGroup($group, 'xslfilegroup', get_string('loadcustomxslsheet', 'techproject'), array(' '), false);

            $group = array();
            $group[] = $mform->createElement('filepicker', 'cssfile', get_string('cssfile', 'techproject'), $this->fileoptions);
            $group[] = $mform->createElement('advcheckbox', 'clearcssfile', '', get_string('clearcustomcsssheet', 'techproject'));
            $mform->addGroup($group, 'xslfilegroup', get_string('loadcustomcsssheet', 'techproject'), array(' '), false);

        }

        $mform->addElement('submit', 'doexportall', get_string('exportallforcurrentgroup', 'techproject'));

        $this->add_action_buttons(true);
    }

    public function set_data($defaults) {

        $context = $this->_customdata['context'];

        // Do not prepare importfile area as we just process it on the fly.

        $draftidfilepicker = file_get_submitted_draft_itemid('xslfile');
        file_prepare_draft_area($draftidfilepicker, $context->id, 'mod_techproject', 'xsl', $defaults->id, $this->fileoptions);

        $draftidfilepicker = file_get_submitted_draft_itemid('cssfile');
        file_prepare_draft_area($draftidfilepicker, $context->id, 'mod_techproject', 'css', $defaults->id, $this->fileoptions);

        parent::set_data($defaults);
    }
}
